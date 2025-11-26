<?php

namespace Nurtjahjo\StoremgmCA\Application\UseCase;

use Nurtjahjo\StoremgmCA\Domain\Repository\ProductRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Exception\ProductNotFoundException;
use Nurtjahjo\StoremgmCA\Domain\Exception\StoreManagementException;
use Nurtjahjo\StoremgmCA\Domain\ValueObject\Money;
use Nurtjahjo\StoremgmCA\Domain\Service\LoggerInterface;

class UpdateProductCommercialsUseCase
{
    public function __construct(
        private ProductRepositoryInterface $productRepo,
        private LoggerInterface $logger
    ) {}

    public function execute(string $productId, float $price, bool $canRent, ?float $rentPrice, ?int $rentDuration): void
    {
        if ($price < 0) throw new StoreManagementException("Price cannot be negative.");
        if ($canRent && ($rentPrice === null || $rentDuration === null)) {
            throw new StoreManagementException("Rent price and duration required if rent is enabled.");
        }

        $product = $this->productRepo->findById($productId);
        if (!$product) throw new ProductNotFoundException("Product $productId not found.");

        $moneyPrice = new Money($price, 'USD');
        $moneyRent = $rentPrice ? new Money($rentPrice, 'USD') : null;

        $product->setCommercials($moneyPrice, $canRent, $moneyRent, $rentDuration);
        
        $this->productRepo->save($product);
        $this->logger->log("Updated commercials for product $productId");
    }
}
