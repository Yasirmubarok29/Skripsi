-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 02 Agu 2025 pada 13.50
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
  `waktu_dibuat` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `titik_evakuasi`
--

INSERT INTO `titik_evakuasi` (`id`, `nama`, `latitude`, `longitude`, `keterangan`, `waktu_dibuat`) VALUES
(15, 'TITIK EVAKUASI A', -6.821684, 107.115935, '', '2025-07-31 22:35:22'),
(17, 'TITIK EVAKUASI C', -6.806987, 107.121258, '', '2025-07-31 22:37:29'),
(18, 'TITIK EVAKUASI B', -6.8199, 107.129618, '', '2025-07-31 22:45:23');

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
  `status` enum('bahaya','siaga','waspada') DEFAULT 'waspada'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `wilayah_bencana`
--

INSERT INTO `wilayah_bencana` (`id`, `nama`, `geojson`, `color`, `created_at`, `status`) VALUES
(28, 'Banjir', '{\"type\":\"FeatureCollection\",\"features\":[{\"type\":\"Feature\",\"properties\":{},\"geometry\":{\"type\":\"Polygon\",\"coordinates\":[[[107.118759,-6.815521],[107.120605,-6.818162],[107.125025,-6.819739],[107.127171,-6.817651],[107.127171,-6.81633],[107.125669,-6.813092],[107.122965,-6.812964],[107.121248,-6.812452],[107.118759,-6.815521]]]}}]}', '#ff0000', '2025-08-01 05:55:13', 'bahaya');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT untuk tabel `wilayah_bencana`
--
ALTER TABLE `wilayah_bencana`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
