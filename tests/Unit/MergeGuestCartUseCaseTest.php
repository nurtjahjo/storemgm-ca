<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Nurtjahjo\StoremgmCA\Application\UseCase\MergeGuestCartUseCase;
use Nurtjahjo\StoremgmCA\Domain\Repository\CartRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Service\LoggerInterface;
use Nurtjahjo\StoremgmCA\Domain\Entity\Cart;
use Nurtjahjo\StoremgmCA\Domain\Entity\CartItem;

class MergeGuestCartUseCaseTest extends TestCase
{
    private $cartRepo;
    private $logger;
    private $useCase;

    protected function setUp(): void
    {
        // Mock Dependencies
        $this->cartRepo = $this->createMock(CartRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->useCase = new MergeGuestCartUseCase(
            $this->cartRepo,
            $this->logger
        );
    }

    public function test_do_nothing_if_guest_cart_not_found()
    {
        // Skenario: Repo return null saat cari guest cart
        $this->cartRepo->method('findByGuestId')->willReturn(null);

        // Expectation: Tidak ada method modifikasi yang dipanggil
        $this->cartRepo->expects($this->never())->method('transferOwnership');
        $this->cartRepo->expects($this->never())->method('delete');

        $this->useCase->execute('user-1', 'guest-missing');
    }

    public function test_do_nothing_if_guest_cart_is_empty()
    {
        // Skenario: Guest cart ada, tapi items kosong
        $guestCart = $this->createMock(Cart::class);
        $guestCart->method('getItems')->willReturn([]); // Kosong

        $this->cartRepo->method('findByGuestId')->willReturn($guestCart);

        // Expectation: Tidak ada aksi
        $this->cartRepo->expects($this->never())->method('transferOwnership');

        $this->useCase->execute('user-1', 'guest-empty');
    }

    public function test_transfers_ownership_if_user_has_no_cart()
    {
        $userId = 'user-123';
        $guestCartId = 'guest-abc';

        // 1. Setup Guest Cart (Ada isi)
        $guestCart = $this->createMock(Cart::class);
        $guestCart->method('getId')->willReturn($guestCartId);
        // Mock getItems agar tidak dianggap kosong
        $guestCart->method('getItems')->willReturn([
             new CartItem('item-1', $guestCartId, 'prod-1', 1)
        ]);

        $this->cartRepo->method('findByGuestId')->with($guestCartId)->willReturn($guestCart);

        // 2. Setup User Cart (NULL / Belum punya)
        $this->cartRepo->method('findByUserId')->with($userId)->willReturn(null);

        // 3. Expectation: Panggil transferOwnership
        $this->cartRepo->expects($this->once())
            ->method('transferOwnership')
            ->with($guestCartId, $userId);
        
        // Expectation: DELETE tidak dipanggil (karena cuma ganti owner, bukan merge & delete)
        $this->cartRepo->expects($this->never())->method('delete');

        // Execute
        $this->useCase->execute($userId, $guestCartId);
    }

    public function test_merges_items_if_user_already_has_cart()
    {
        $userId = 'user-123';
        $guestCartId = 'guest-abc';
        $userCartId = 'cart-user-xyz';

        // 1. Setup Guest Cart (Item: Prod-A)
        $guestCart = $this->createMock(Cart::class);
        $guestCart->method('getId')->willReturn($guestCartId);
        $itemA = new CartItem('item-a', $guestCartId, 'prod-A', 1);
        $guestCart->method('getItems')->willReturn([$itemA]);

        $this->cartRepo->method('findByGuestId')->willReturn($guestCart);

        // 2. Setup User Cart (Item: Prod-B)
        $userCart = $this->createMock(Cart::class);
        $userCart->method('getId')->willReturn($userCartId);
        // User Cart BELUM punya Prod-A
        $userCart->method('hasProduct')->with('prod-A')->willReturn(false);

        $this->cartRepo->method('findByUserId')->willReturn($userCart);

        // 3. Expectation: Item dipindahkan
        $this->cartRepo->expects($this->once())
            ->method('moveItemToCart')
            ->with('item-a', $userCartId);

        // 4. Expectation: Cart Guest dihapus setelah kosong
        $this->cartRepo->expects($this->once())
            ->method('delete')
            ->with($guestCartId);

        // Execute
        $this->useCase->execute($userId, $guestCartId);
    }

    public function test_skips_duplicate_items_during_merge()
    {
        // Skenario: Guest beli Buku X, tapi User ternyata sudah punya Buku X di keranjangnya.
        // Karena produk digital qty=1, item dari guest diabaikan.

        $userId = 'user-123';
        $guestCartId = 'guest-abc';

        // 1. Guest Cart (Item: Prod-A)
        $guestCart = $this->createMock(Cart::class);
        $guestCart->method('getId')->willReturn($guestCartId);
        $itemA = new CartItem('item-a', $guestCartId, 'prod-A', 1);
        $guestCart->method('getItems')->willReturn([$itemA]);

        $this->cartRepo->method('findByGuestId')->willReturn($guestCart);

        // 2. User Cart (Sudah punya Prod-A)
        $userCart = $this->createMock(Cart::class);
        $userCart->method('getId')->willReturn('cart-user-xyz');
        // User Cart SUDAH punya Prod-A
        $userCart->method('hasProduct')->with('prod-A')->willReturn(true);

        $this->cartRepo->method('findByUserId')->willReturn($userCart);

        // 3. Expectation: moveItemToCart TIDAK dipanggil
        $this->cartRepo->expects($this->never())->method('moveItemToCart');

        // 4. Expectation: Cart Guest tetap dihapus (cleanup)
        $this->cartRepo->expects($this->once())->method('delete')->with($guestCartId);

        $this->useCase->execute($userId, $guestCartId);
    }
}
