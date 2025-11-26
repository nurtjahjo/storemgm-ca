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
        $cart = $this->cartRepo->findByUserId($userId);
        if (!$cart || empty($cart->getItems())) {
            throw new CartIsEmptyException("Cart is empty.");
        }

        $totalUsd = 0.0;
        $orderItems = [];
        $orderId = Uuid::uuid4()->toString();

        foreach ($cart->getItems() as $cartItem) {
            $product = $this->productRepo->findById($cartItem->getProductId());
            
            if (!$product || $product->getStatus() !== 'published') {
                throw new ProductNotFoundException("Product unavailable.");
            }

            // Logika Harga: Cek apakah beli atau sewa
            $price = $product->getPriceUsd(); // Default harga beli
            if ($cartItem->getPurchaseType() === 'rent' && $product->canRent()) {
                // Gunakan harga sewa jika tersedia
                $price = $product->getRentalPriceUsd() ?? $price;
            }

            $totalUsd += $price->getAmount() * $cartItem->getQuantity();

            // UPDATE: Masukkan purchaseType ke OrderItem
            $orderItems[] = new OrderItem(
                id: Uuid::uuid4()->toString(),
                orderId: $orderId,
                productId: $product->getId(),
                quantity: $cartItem->getQuantity(),
                priceAtPurchase: $price,
                purchaseType: $cartItem->getPurchaseType() // <--- PENTING! Disalin dari CartItem
            );
        }

        $totalIdr = null;
        $exchangeRate = null;
        
        if ($locale === 'id') {
            $exchangeRate = $this->currencyConverter->getExchangeRate('USD', 'IDR');
            $totalIdr = $totalUsd * $exchangeRate;
        }

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

        $this->orderRepo->save($order);
        
        $paymentData = $this->paymentGateway->createTransaction($order);

        if (isset($paymentData['transaction_id'])) {
            $order->setPaymentGatewayTransactionId($paymentData['transaction_id']);
            $this->orderRepo->save($order);
        }

        $this->cartRepo->delete($cart->getId());

        return [
            'order_id' => $orderId,
            'payment' => $paymentData
        ];
    }
}
