<?php

namespace Nurtjahjo\StoremgmCA\Application\DTO;

/**
 * Data Transfer Object untuk membawa hasil paginasi.
 * Standar output untuk semua Use Case yang menampilkan daftar data.
 */
class PaginatedResult
{
    /**
     * @param array $data Data (Entities) untuk halaman saat ini.
     * @param int   $total Total jumlah item di semua halaman.
     * @param int   $currentPage Halaman saat ini.
     * @param int   $perPage Jumlah item per halaman.
     */
    public function __construct(
        public readonly array $data,
        public readonly int $total,
        public readonly int $currentPage,
        public readonly int $perPage
    ) {}
}
