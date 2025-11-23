<?php

namespace Nurtjahjo\StoremgmCA\Application\UseCase;

use Ramsey\Uuid\Uuid;
use DateTime;
use Nurtjahjo\StoremgmCA\Domain\Repository\CartRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Repository\OrderRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Repository\ProductRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Service\PaymentGatewayInterface;
use Nurtjahjo\StoremgmCA\Domain\Service\CurrencyConverterInterface;
use Nurtjahjo\StoremgmCA\Domain\Service\LoggerInterface;
use Nurtjahjo\StoremgmCA\Domain\Entity\Order;
use Nurtjahjo\StoremgmCA\Domain\Entity\OrderItem;
use Nurtjahjo\StoremgmCA\Domain\ValueObject\Money;
use Nurtjahjo\StoremgmCA\Domain\Exception\CartIsEmptyException;
use Nurtjahjo\StoremgmCA\Domain\Exception\ProductNotFoundException;

class CheckoutUseCase
{
    public function __construct(
        private CartRepositoryInterface $cartRepo,
        private OrderRepositoryInterface $orderRepo,
        private ProductRepositoryInterface $productRepo,
        private PaymentGatewayInterface $paymentGateway,
        private CurrencyConverterInterface $currencyConverter,
        private LoggerInterface $logger
    ) {}

    public function execute(string $userId, string $locale = 'en'): array
    {
        // 1. Ambil Keranjang User
        $cart = $this->cartRepo->findByUserId($userId);
        if (!$cart || empty($cart->getItems())) {
            throw new CartIsEmptyException("Cart is empty.");
        }

        // 2. Hitung Total & Validasi Stok/Harga
        $totalUsd = 0.0;
        $orderItems = [];
        $orderId = Uuid::uuid4()->toString();

        foreach ($cart->getItems() as $cartItem) {
            $product = $this->productRepo->findById($cartItem->getProductId());
            
            // Validasi ketersediaan produk (misal status published)
            if (!$product || $product->getStatus() !== 'published') {
                // Dalam implementasi nyata, mungkin kita skip item ini dan beri notifikasi,
                // tapi untuk sekarang kita lempar exception.
                throw new ProductNotFoundException("Product {$cartItem->getProductId()} is no longer available.");
            }

            $price = $product->getPriceUsd();
            $totalUsd += $price->getAmount() * $cartItem->getQuantity();

            $orderItems[] = new OrderItem(
                Uuid::uuid4()->toString(),
                $orderId,
                $product->getId(),
                $cartItem->getQuantity(),
                $price // Simpan harga snapshot saat ini
            );
        }

        // 3. Konversi Mata Uang (Jika Locale ID)
        $totalIdr = null;
        $exchangeRate = null;
        
        if ($locale === 'id') {
            $exchangeRate = $this->currencyConverter->getExchangeRate('USD', 'IDR');
            $totalIdr = $totalUsd * $exchangeRate;
        }

        // 4. Buat Order Entity
        $order = new Order(
            $orderId,
            $userId,
            new Money($totalUsd, 'USD'),
            $totalIdr,
            $exchangeRate,
            'pending',
            null,
            new DateTime(),
            new DateTime()
        );
        $order->setItems($orderItems);

        // 5. Simpan Order ke Database
        $this->orderRepo->save($order);
        $this->logger->log("Order created: {$orderId} for User: {$userId}", 'INFO');

        // 6. Inisiasi Pembayaran ke Payment Gateway
        $paymentData = $this->paymentGateway->createTransaction($order);

        // Update Order dengan ID Transaksi dari PG (jika ada di response)
        if (isset($paymentData['transaction_id'])) {
            $order->setPaymentGatewayTransactionId($paymentData['transaction_id']);
            $this->orderRepo->save($order); // Update lagi
        }

        // 7. Kosongkan Keranjang (Soft Delete atau Hapus Item)
        // PENTING: Idealnya keranjang dikosongkan SETELAH pembayaran sukses,
        // tapi untuk produk digital seringkali dikosongkan saat order dibuat (Pending).
        // Kita pilih hapus sekarang untuk mencegah double order.
        $this->cartRepo->delete($cart->getId());

        // 8. Kembalikan data pembayaran ke Frontend (Redirect URL / Token)
        return [
            'order_id' => $orderId,
            'payment' => $paymentData
        ];
    }
}
