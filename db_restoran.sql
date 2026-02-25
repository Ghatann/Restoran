-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 25 Feb 2026 pada 02.31
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_restoran`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_orderans`
--

CREATE TABLE `detail_orderans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_orderan` bigint(20) UNSIGNED NOT NULL,
  `id_menu` bigint(20) UNSIGNED NOT NULL,
  `jumlah` int(11) NOT NULL DEFAULT 1,
  `mode_pesanan` enum('takeaway','dinein') NOT NULL DEFAULT 'dinein',
  `catatan` text DEFAULT NULL,
  `status` enum('processing','done') NOT NULL DEFAULT 'processing',
  `subtotal` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `detail_orderans`
--

INSERT INTO `detail_orderans` (`id`, `id_orderan`, `id_menu`, `jumlah`, `mode_pesanan`, `catatan`, `status`, `subtotal`, `created_at`, `updated_at`) VALUES
(9, 8, 3, 1, 'dinein', '', 'done', 20000, '2026-02-22 18:05:13', '2026-02-22 18:05:38'),
(10, 8, 4, 2, 'dinein', '', 'done', 30000, '2026-02-22 18:05:13', '2026-02-22 18:05:37'),
(11, 9, 4, 1, 'dinein', '', 'done', 15000, '2026-02-22 18:06:10', '2026-02-22 18:08:28'),
(12, 10, 3, 2, 'dinein', '', 'done', 40000, '2026-02-22 18:07:32', '2026-02-22 18:08:28'),
(13, 10, 4, 1, 'dinein', '', 'done', 15000, '2026-02-22 18:07:32', '2026-02-22 18:08:28'),
(14, 11, 3, 1, 'dinein', '', 'done', 20000, '2026-02-22 18:16:39', '2026-02-22 18:16:50'),
(15, 12, 3, 1, 'dinein', '', 'done', 20000, '2026-02-22 18:19:28', '2026-02-22 18:20:07'),
(16, 13, 3, 1, 'dinein', '', 'done', 20000, '2026-02-22 18:22:15', '2026-02-22 18:22:39'),
(17, 14, 3, 1, 'dinein', '', 'done', 20000, '2026-02-22 18:34:35', '2026-02-22 18:35:09'),
(18, 14, 9, 1, 'dinein', '', 'done', 25000, '2026-02-22 18:34:35', '2026-02-22 18:35:07'),
(19, 14, 10, 1, 'dinein', '', 'done', 22000, '2026-02-22 18:34:35', '2026-02-22 18:35:06');

-- --------------------------------------------------------

--
-- Struktur dari tabel `karyawans`
--

CREATE TABLE `karyawans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nama_karyawan` varchar(255) NOT NULL,
  `no_hp` varchar(255) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `jabatan` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `karyawans`
--

INSERT INTO `karyawans` (`id`, `nama_karyawan`, `no_hp`, `alamat`, `jabatan`, `created_at`, `updated_at`) VALUES
(1, 'Ghatan', NULL, NULL, 'admin', '2026-02-23 00:05:39', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `kategoris`
--

CREATE TABLE `kategoris` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nama_kategori` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `mejas`
--

CREATE TABLE `mejas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nomor_meja` varchar(255) NOT NULL,
  `status` enum('available','booking') NOT NULL DEFAULT 'available',
  `kapasitas` int(11) NOT NULL DEFAULT 4,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `mejas`
--

INSERT INTO `mejas` (`id`, `nomor_meja`, `status`, `kapasitas`, `created_at`, `updated_at`) VALUES
(2, 'A', 'available', 2, '2026-02-22 17:16:39', '2026-02-22 18:19:47'),
(4, 'B', 'available', 4, '2026-02-22 17:53:49', '2026-02-22 18:22:22'),
(5, 'C', 'available', 6, '2026-02-22 17:53:52', '2026-02-22 17:56:08'),
(6, 'D', 'available', 3, '2026-02-22 18:34:05', '2026-02-22 18:34:45');

-- --------------------------------------------------------

--
-- Struktur dari tabel `menus`
--

CREATE TABLE `menus` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nama_menu` varchar(255) NOT NULL,
  `harga` int(11) NOT NULL DEFAULT 0,
  `id_kategori` bigint(20) UNSIGNED DEFAULT NULL,
  `stok_porsi` int(11) NOT NULL DEFAULT 0,
  `foto` varchar(255) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `status` enum('available','sold_out') NOT NULL DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `menus`
--

INSERT INTO `menus` (`id`, `nama_menu`, `harga`, `id_kategori`, `stok_porsi`, `foto`, `deskripsi`, `status`, `created_at`, `updated_at`) VALUES
(3, 'Mie Ayam', 20000, NULL, 11, '1771808480_699ba6e0d82d1.avif', NULL, 'available', '2026-02-22 18:01:20', '2026-02-22 18:27:23'),
(4, 'Nasi Goreng', 15000, NULL, 10, '1771808490_699ba6ea2d397.jpg', NULL, 'available', '2026-02-22 18:01:30', '2026-02-22 18:01:30'),
(6, 'Ayam Geprek', 21000, NULL, 0, '1771809838_699bac2e70650.jpg', NULL, 'sold_out', '2026-02-22 18:23:58', '2026-02-22 18:23:58'),
(9, 'ikan patin', 25000, NULL, 0, '1771810117_699bad453d4b4.jpg', NULL, 'available', '2026-02-22 18:28:37', '2026-02-22 18:28:37'),
(10, 'Ikan Bandeng', 22000, NULL, 1, '1771810426_699bae7abf528.jpg', NULL, 'available', '2026-02-22 18:33:46', '2026-02-22 18:33:46');

-- --------------------------------------------------------

--
-- Struktur dari tabel `orderans`
--

CREATE TABLE `orderans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nama_konsumen` varchar(255) DEFAULT NULL,
  `total_bayar` int(11) NOT NULL DEFAULT 0,
  `tanggal_orderan` datetime DEFAULT NULL,
  `status` enum('pending','dibayar','batal') NOT NULL DEFAULT 'pending',
  `id_user` bigint(20) UNSIGNED DEFAULT NULL,
  `id_meja` bigint(20) UNSIGNED DEFAULT NULL,
  `metode_pembayaran` enum('cash','qris','cashless') NOT NULL DEFAULT 'cash',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `orderans`
--

INSERT INTO `orderans` (`id`, `nama_konsumen`, `total_bayar`, `tanggal_orderan`, `status`, `id_user`, `id_meja`, `metode_pembayaran`, `created_at`, `updated_at`) VALUES
(1, NULL, 20000, '2026-02-23 01:30:36', 'dibayar', 1, 2, 'cash', '2026-02-22 17:30:36', '2026-02-22 17:30:56'),
(2, NULL, 15000, '2026-02-23 01:33:46', 'dibayar', 1, 2, 'cash', '2026-02-22 17:33:46', '2026-02-22 17:36:49'),
(3, NULL, 15000, '2026-02-23 01:51:34', 'dibayar', 6, 2, 'cash', '2026-02-22 17:51:34', '2026-02-22 17:51:45'),
(4, NULL, 15000, '2026-02-23 01:54:13', 'dibayar', 1, 4, 'cash', '2026-02-22 17:54:13', '2026-02-22 17:54:58'),
(5, NULL, 20000, '2026-02-23 01:54:22', 'dibayar', 1, 2, 'cash', '2026-02-22 17:54:22', '2026-02-22 17:54:44'),
(6, NULL, 140000, '2026-02-23 01:55:40', 'dibayar', 2, 5, 'cash', '2026-02-22 17:55:40', '2026-02-22 17:56:08'),
(7, NULL, 105000, '2026-02-23 01:56:03', 'dibayar', 2, 4, 'cash', '2026-02-22 17:56:03', '2026-02-22 17:56:06'),
(8, NULL, 50000, '2026-02-23 02:05:13', 'dibayar', 6, 2, 'cash', '2026-02-22 18:05:13', '2026-02-22 18:05:19'),
(9, NULL, 15000, '2026-02-23 02:06:10', 'dibayar', 6, 2, 'cash', '2026-02-22 18:06:10', '2026-02-22 18:08:08'),
(10, 'Budi', 55000, '2026-02-23 02:07:32', 'dibayar', 6, 4, 'cash', '2026-02-22 18:07:32', '2026-02-22 18:10:06'),
(11, NULL, 20000, '2026-02-23 02:16:39', 'dibayar', 6, 2, 'cash', '2026-02-22 18:16:39', '2026-02-22 18:16:44'),
(12, NULL, 20000, '2026-02-23 02:19:28', 'dibayar', 1, 2, 'cash', '2026-02-22 18:19:28', '2026-02-22 18:19:47'),
(13, NULL, 20000, '2026-02-23 02:22:15', 'dibayar', 6, 4, 'cash', '2026-02-22 18:22:15', '2026-02-22 18:22:22'),
(14, 'Ghatann', 67000, '2026-02-23 02:34:35', 'dibayar', 6, 6, 'cash', '2026-02-22 18:34:35', '2026-02-22 18:34:45');

-- --------------------------------------------------------

--
-- Struktur dari tabel `update_stokharians`
--

CREATE TABLE `update_stokharians` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_menu` bigint(20) UNSIGNED NOT NULL,
  `jumlah_porsi` int(11) NOT NULL DEFAULT 0,
  `tanggal_update` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','kasir','chef') NOT NULL DEFAULT 'kasir',
  `id_karyawan` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `id_karyawan`, `created_at`, `updated_at`) VALUES
(1, 'ghatan', 'ghatan@gmail.com', '$2y$10$q5eR3DP.4R7xIHB4t333EOxtgyPaxHwHQVQJUHtjkZBRq03tq7y16', 'admin', 1, '2026-02-23 00:05:39', NULL),
(2, 'chef', 'chef@gmail.com', '$2y$10$woeizELOWlqN84XUceFBPuw5JAsiVhK6SLdbDMaRvlbSG26ukvhXu', 'chef', NULL, NULL, NULL),
(6, 'kasir', 'kasir@gmail.com', '$2y$10$woeizELOWlqN84XUceFBPuw5JAsiVhK6SLdbDMaRvlbSG26ukvhXu', 'kasir', NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `detail_orderans`
--
ALTER TABLE `detail_orderans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_detail_orderan` (`id_orderan`),
  ADD KEY `fk_detail_menu` (`id_menu`);

--
-- Indeks untuk tabel `karyawans`
--
ALTER TABLE `karyawans`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `kategoris`
--
ALTER TABLE `kategoris`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `mejas`
--
ALTER TABLE `mejas`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `menus`
--
ALTER TABLE `menus`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_menus_kategori` (`id_kategori`);

--
-- Indeks untuk tabel `orderans`
--
ALTER TABLE `orderans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_orderan_user` (`id_user`),
  ADD KEY `fk_orderan_meja` (`id_meja`);

--
-- Indeks untuk tabel `update_stokharians`
--
ALTER TABLE `update_stokharians`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_stok_menu` (`id_menu`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_users_karyawan` (`id_karyawan`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `detail_orderans`
--
ALTER TABLE `detail_orderans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT untuk tabel `karyawans`
--
ALTER TABLE `karyawans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `kategoris`
--
ALTER TABLE `kategoris`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `mejas`
--
ALTER TABLE `mejas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `menus`
--
ALTER TABLE `menus`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `orderans`
--
ALTER TABLE `orderans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT untuk tabel `update_stokharians`
--
ALTER TABLE `update_stokharians`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `detail_orderans`
--
ALTER TABLE `detail_orderans`
  ADD CONSTRAINT `fk_detail_menu` FOREIGN KEY (`id_menu`) REFERENCES `menus` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_detail_orderan` FOREIGN KEY (`id_orderan`) REFERENCES `orderans` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `menus`
--
ALTER TABLE `menus`
  ADD CONSTRAINT `fk_menus_kategori` FOREIGN KEY (`id_kategori`) REFERENCES `kategoris` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `orderans`
--
ALTER TABLE `orderans`
  ADD CONSTRAINT `fk_orderan_meja` FOREIGN KEY (`id_meja`) REFERENCES `mejas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_orderan_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `update_stokharians`
--
ALTER TABLE `update_stokharians`
  ADD CONSTRAINT `fk_stok_menu` FOREIGN KEY (`id_menu`) REFERENCES `menus` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_karyawan` FOREIGN KEY (`id_karyawan`) REFERENCES `karyawans` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
