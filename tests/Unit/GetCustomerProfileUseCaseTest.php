<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Nurtjahjo\StoremgmCA\Application\UseCase\GetCustomerProfileUseCase;
use Nurtjahjo\StoremgmCA\Domain\Repository\CustomerProfileRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Entity\CustomerProfile;

class GetCustomerProfileUseCaseTest extends TestCase
{
    private $repo;
    private $useCase;

    protected function setUp(): void
    {
        $this->repo = $this->createMock(CustomerProfileRepositoryInterface::class);
        $this->useCase = new GetCustomerProfileUseCase($this->repo);
    }

    public function test_returns_existing_profile()
    {
        $userId = 'user-123';
        $mockProfile = new CustomerProfile($userId, 'Alamat Tagihan', 'Alamat Kirim');

        $this->repo->expects($this->once())
            ->method('findByUserId')
            ->with($userId)
            ->willReturn($mockProfile);

        $result = $this->useCase->execute($userId);

        $this->assertEquals($userId, $result->getUserId());
        $this->assertEquals('Alamat Tagihan', $result->getBillingAddress());
    }

    public function test_returns_empty_profile_if_not_found()
    {
        $userId = 'new-user';
        
        // Repository mengembalikan null (belum pernah simpan profil)
        $this->repo->method('findByUserId')->willReturn(null);

        $result = $this->useCase->execute($userId);

        // UseCase harus mengembalikan objek CustomerProfile kosong, bukan null/error
        $this->assertInstanceOf(CustomerProfile::class, $result);
        $this->assertEquals($userId, $result->getUserId());
        $this->assertNull($result->getBillingAddress());
        $this->assertNull($result->getShippingAddress());
    }
}
