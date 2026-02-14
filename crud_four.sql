-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 14, 2026 at 10:08 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `crud_four`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(6) UNSIGNED NOT NULL,
  `first_name` varchar(30) NOT NULL,
  `last_name` varchar(30) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `email` varchar(50) NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `personal_image` varchar(255) NOT NULL DEFAULT 'user_image.jpg',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `phone`, `email`, `gender`, `personal_image`, `created_at`) VALUES
(1, 'Hamdy', 'Hamdy', '01007237555', 'hamdytamer253@gmail.com', 'Male', 'user_image.jpg', '2025-11-30 17:40:25'),
(2, 'Mark', 'Sam', '01006707433', 'Marksam707@gmail.com', 'Male', '692c81c88cb12.jpg', '2025-11-30 17:41:28'),
(3, 'Christina', 'Hany', '01527847052', 'christinahany253@gmail.com', 'Female', '692c822d5ba9d.png', '2025-11-30 17:43:09'),
(4, 'Clara', 'Johnson', '01094774892', 'clarahany707@yahoo.com', 'Female', '692c825830011.png', '2025-11-30 17:43:52'),
(5, 'Jessica', 'Thomson', '01000556172', 'jessicathomson782@yahoo.com', 'Male', '692c82c33bff9.png', '2025-11-30 17:45:39'),
(6, 'Omar', 'Waleed', '01008493278', 'omarwaleed577@gmail.com', 'Male', 'user_image.jpg', '2026-02-08 20:41:03');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
