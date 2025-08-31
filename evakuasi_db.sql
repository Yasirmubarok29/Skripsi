-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 28 Jul 2025 pada 06.46
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
(2, 'posko kaka', -6.801272, 107.082233, 'dekat oyo', '2025-07-27 18:06:42'),
(3, 'uuu', -6.822059, 107.149415, 'akdpjd', '2025-07-27 21:21:22'),
(4, 'p', -6.801033, 107.119503, '', '2025-07-27 22:06:27'),
(5, 'o', -6.824208, 107.109436, '', '2025-07-27 22:06:32'),
(6, 'n', -6.821991, 107.163313, '', '2025-07-27 22:06:36'),
(7, 'j', -6.793501, 107.110981, '', '2025-07-27 22:06:41');

-- --------------------------------------------------------

--
-- Struktur dari tabel `wilayah_bencana`
--

CREATE TABLE `wilayah_bencana` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `geojson` longtext NOT NULL,
  `color` varchar(16) DEFAULT '#f03',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `wilayah_bencana`
--

INSERT INTO `wilayah_bencana` (`id`, `nama`, `geojson`, `color`, `created_at`) VALUES
(1, 'gunung meletus', '{\"type\":\"FeatureCollection\",\"features\":[{\"type\":\"Feature\",\"properties\":{},\"geometry\":{\"type\":\"Polygon\",\"coordinates\":[[[107.133453,-6.807647],[107.127102,-6.819739],[107.13517,-6.826041],[107.148216,-6.827574],[107.149075,-6.821102],[107.1465,-6.811564],[107.133453,-6.807647]]]}}]}', '#ff0000', '2025-07-27 19:41:11'),
(2, 'aman', '{\"type\":\"FeatureCollection\",\"features\":[{\"type\":\"Feature\",\"properties\":{},\"geometry\":{\"type\":\"Polygon\",\"coordinates\":[[[107.087791,-6.813847],[107.088684,-6.823007],[107.102589,-6.826072],[107.107824,-6.814576],[107.1007,-6.810829],[107.087791,-6.813847]]]}}]}', '#1eff00', '2025-07-27 21:26:42'),
(4, 'www', '{\"type\":\"FeatureCollection\",\"features\":[{\"type\":\"Feature\",\"properties\":{},\"geometry\":{\"type\":\"Polygon\",\"coordinates\":[[[107.074677,-6.797417],[107.091156,-6.848169],[107.120682,-6.844763],[107.174927,-6.837951],[107.160851,-6.807976],[107.124802,-6.792307],[107.074677,-6.797417]]]}}]}', '#000000', '2025-07-27 22:05:44');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `wilayah_bencana`
--
ALTER TABLE `wilayah_bencana`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
