-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 31 Agu 2025 pada 03.10
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
-- Database: `evakuasi_db`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`) VALUES
(2, 'admin', 'admin123'),
(3, 'admin123', '$2y$10$.K6xK95HEhD1qtS9pDkq5.jJ27RQmAGj5.DatskCNLx11Koji5XaW'),
(4, 'aaa', '$2y$10$BVlHB5vDvQFBENvxRhwLYenidg9d6SDSsOyurMSvEVq2zP5ARVrui');

-- --------------------------------------------------------

--
-- Struktur dari tabel `titik_evakuasi`
--

CREATE TABLE `titik_evakuasi` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `latitude` double NOT NULL,
  `longitude` double NOT NULL,
  `keterangan` text DEFAULT NULL,
  `waktu_dibuat` timestamp NOT NULL DEFAULT current_timestamp(),
  `kapasitas` varchar(100) DEFAULT NULL,
  `fasilitas` varchar(255) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `titik_evakuasi`
--

INSERT INTO `titik_evakuasi` (`id`, `nama`, `latitude`, `longitude`, `keterangan`, `waktu_dibuat`, `kapasitas`, `fasilitas`, `foto`, `deskripsi`) VALUES
(58, 'Evakuasi 1', -6.82036, 107.143082, '', '2025-08-16 08:23:16', '100', '', '', 'Dekat Masjid'),
(59, 'EVAKUASI 2', -6.825816, 107.153789, '', '2025-08-18 15:35:57', '50', 'KASUR', '', 'LAPANG');

-- --------------------------------------------------------

--
-- Struktur dari tabel `wilayah_bencana`
--

CREATE TABLE `wilayah_bencana` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `geojson` longtext NOT NULL,
  `color` varchar(16) DEFAULT '#f03',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('bahaya','siaga','waspada') DEFAULT 'waspada',
  `luas` double DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `wilayah_bencana`
--

INSERT INTO `wilayah_bencana` (`id`, `nama`, `geojson`, `color`, `created_at`, `status`, `luas`) VALUES
(54, 'GEMPA BUMI', '{\"type\":\"FeatureCollection\",\"features\":[{\"type\":\"Feature\",\"properties\":{},\"geometry\":{\"type\":\"Polygon\",\"coordinates\":[[[107.143677,-6.819107],[107.143216,-6.819273],[107.143135,-6.81964],[107.143344,-6.819982],[107.143843,-6.81996],[107.144026,-6.819465],[107.144004,-6.819289],[107.143677,-6.819107]]]}}]}', '#dc3545', '2025-08-16 07:59:35', 'bahaya', 7220.306676311596),
(55, 'GEMPA BUMI', '{\"type\":\"FeatureCollection\",\"features\":[{\"type\":\"Feature\",\"properties\":{},\"geometry\":{\"type\":\"Polygon\",\"coordinates\":[[[107.135925,-6.818643],[107.136333,-6.821521],[107.14056,-6.819005],[107.138114,-6.818557],[107.135925,-6.818643]]]}}]}', '#ffc107', '2025-08-16 09:27:44', 'siaga', 88485.84717974285),
(56, 'GEMPA BUMI', '{\"type\":\"FeatureCollection\",\"features\":[{\"type\":\"Feature\",\"properties\":{},\"geometry\":{\"type\":\"Polygon\",\"coordinates\":[[[107.121506,-6.816304],[107.121721,-6.823386],[107.123952,-6.822022],[107.121506,-6.816304]]]}}]}', '#0d6efd', '2025-08-16 09:28:09', 'waspada', 99008.17180583766);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `titik_evakuasi`
--
ALTER TABLE `titik_evakuasi`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `wilayah_bencana`
--
ALTER TABLE `wilayah_bencana`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `titik_evakuasi`
--
ALTER TABLE `titik_evakuasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT untuk tabel `wilayah_bencana`
--
ALTER TABLE `wilayah_bencana`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
