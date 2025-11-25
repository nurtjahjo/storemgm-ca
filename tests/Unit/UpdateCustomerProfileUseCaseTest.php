<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Nurtjahjo\StoremgmCA\Application\UseCase\UpdateCustomerProfileUseCase;
use Nurtjahjo\StoremgmCA\Domain\Repository\CustomerProfileRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Entity\CustomerProfile;

class UpdateCustomerProfileUseCaseTest extends TestCase
{
    private $repo;
    private $useCase;

    protected function setUp(): void
    {
        $this->repo = $this->createMock(CustomerProfileRepositoryInterface::class);
        $this->useCase = new UpdateCustomerProfileUseCase($this->repo);
    }

    public function test_saves_profile_data()
    {
        $userId = 'user-123';
        $bill = 'Jalan Merdeka No 1';
        $ship = 'Jalan Sudirman No 5';

        // Expectation: Repository save dipanggil dengan data yang benar
        $this->repo->expects($this->once())
            ->method('save')
            ->with($this->callback(function (CustomerProfile $p) use ($userId, $bill, $ship) {
                return $p->getUserId() === $userId &&
                       $p->getBillingAddress() === $bill &&
                       $p->getShippingAddress() === $ship;
            }));

        $this->useCase->execute($userId, $bill, $ship);
    }
}
