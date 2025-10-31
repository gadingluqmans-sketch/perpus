-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 16, 2025 at 12:06 PM
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
-- Database: `library_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`) VALUES
(1, 'admin', '123qwerty56');

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `id` int(11) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `author` varchar(100) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `stock` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`id`, `title`, `author`, `year`, `stock`) VALUES
(1, 'Pride and Prejudice', 'Jane Austen', 1813, 5),
(2, 'Adventures of Huckleberry Finn', 'Mark Twain', 1884, 4),
(3, 'Frankenstein; or, The Modern Prometheus', 'Mary Wollstonecraft Shelley', 1818, 3),
(4, 'The Picture of Dorian Gray', 'Oscar Wilde', 1890, 2),
(5, 'Dracula', 'Bram Stoker', 1897, 3),
(6, 'Moby-Dick; or, The Whale', 'Herman Melville', 1851, 3),
(7, 'Les Misérables', 'Victor Hugo', 1862, 2),
(8, 'War and Peace', 'Leo Tolstoy', 1869, 3),
(9, 'Crime and Punishment', 'Fyodor Dostoevsky', 1866, 2),
(10, 'The Count of Monte Cristo', 'Alexandre Dumas', 1844, 3),
(11, 'A Tale of Two Cities', 'Charles Dickens', 1859, 2),
(12, 'The Adventures of Sherlock Holmes', 'Arthur Conan Doyle', 1892, 3),
(13, 'The Brothers Karamazov', 'Fyodor Dostoevsky', 1880, 1),
(14, 'The War of the Worlds', 'H. G. Wells', 1898, 3),
(15, 'The Call of the Wild', 'Jack London', 1903, 2);
-- --------------------------------------------------------

--
-- Table structure for table `borrowings`
--

CREATE TABLE `borrowings` (
  `id` int(11) NOT NULL,
  `borrower_name` varchar(100) DEFAULT NULL,
  `borrower_email` varchar(100) DEFAULT NULL,
  `book_id` int(11) DEFAULT NULL,
  `borrow_date` datetime DEFAULT current_timestamp(),
  `due_date` date DEFAULT NULL,
  `return_date` datetime DEFAULT NULL,
  `returned` tinyint(1) DEFAULT 0,
  `fine` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `borrowings`
--

INSERT INTO `borrowings` (`id`, `borrower_name`, `borrower_email`, `book_id`, `borrow_date`, `due_date`, `return_date`, `returned`, `fine`) VALUES
(1, 'us1', 'usernumber1@example.com', 3, '2025-10-15 18:27:50', NULL, '2025-10-15 00:00:00', 1, 203757083),
(2, 'us2', 'usernumber2@example.com', 1, '2025-10-15 20:00:31', '2025-10-18', '2025-10-15 00:00:00', 1, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `borrowings`
--
ALTER TABLE `borrowings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `book_id` (`book_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `borrowings`
--
ALTER TABLE `borrowings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `borrowings`
--
ALTER TABLE `borrowings`
  ADD CONSTRAINT `borrowings_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
