-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 24, 2026 at 03:31 PM
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
-- Database: `phpecommerce`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `email`, `password`, `created_at`) VALUES
(1, 'Admin', 'admin@ecommerce.com', '$2y$10$vrQPDS.2/7wslJDWODi5OeQDBxSVzeeYalrjTjeNXATahmyOjnBqK', '2026-02-09 10:41:29');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `quantity`, `created_at`, `updated_at`) VALUES
(12, 1, 12, 3, '2026-03-12 06:54:56', '2026-03-12 06:55:06');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `image`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Electronics', 'Electronic devices and gadgets', NULL, 'active', '2026-02-09 10:41:29', '2026-02-09 10:41:29'),
(2, 'Fashion', 'Clothing and accessories', NULL, 'active', '2026-02-09 10:41:29', '2026-02-09 10:41:29'),
(3, 'Home & Kitchen', 'Home appliances and kitchen items', NULL, 'active', '2026-02-09 10:41:29', '2026-02-09 10:41:29'),
(4, 'Books', 'Books and magazines', NULL, 'active', '2026-02-09 10:41:29', '2026-02-09 17:52:33'),
(5, 'Sports', 'Sports equipment and accessories', NULL, 'active', '2026-02-09 10:41:29', '2026-02-09 10:41:29'),
(6, 'Software', 'Programming &amp; Web Development.', NULL, 'active', '2026-02-09 17:39:03', '2026-02-09 17:39:03');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'COD',
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `order_status` enum('pending','processing','completed','cancelled') DEFAULT 'pending',
  `shipping_name` varchar(100) NOT NULL,
  `shipping_email` varchar(100) NOT NULL,
  `shipping_mobile` varchar(15) NOT NULL,
  `shipping_address` text NOT NULL,
  `shipping_city` varchar(50) NOT NULL,
  `shipping_state` varchar(50) NOT NULL,
  `shipping_pincode` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_number`, `total_amount`, `payment_method`, `payment_status`, `order_status`, `shipping_name`, `shipping_email`, `shipping_mobile`, `shipping_address`, `shipping_city`, `shipping_state`, `shipping_pincode`, `created_at`, `updated_at`) VALUES
(1, 1, 'ORD2026020981E093', 3499.00, 'COD', 'pending', 'completed', 'Md Raushan Jilani', 'mdraushanji22@gmail.com', '06280779503', 'House-pihwara P.O Uttara P.S Saharghat Disrict Madhubani', 'Madhubani', 'Bihar', '847308', '2026-02-09 10:46:48', '2026-02-09 12:33:08'),
(2, 1, 'ORD20260209CBFC8A', 2999.00, 'COD', 'pending', 'processing', 'Md Raushan Jilani', 'mdraushanji22@gmail.com', '06280779503', 'House-pihwara P.O Uttara P.S Saharghat Disrict Madhubani', 'Madhubani', 'Bihar', '1100045', '2026-02-09 10:49:48', '2026-02-09 17:32:01'),
(3, 2, 'ORD2026021016E774', 1298.00, 'COD', 'pending', 'completed', 'MD RAUSHAN', 'jilani@gmail.com', '6280779503', 'Dabri Dwarka Main Road, Sitapuri Part 1, New Delhi, South West, Delhi, 110045', 'New Delhi', 'Delhi', '110045', '2026-02-09 18:54:41', '2026-02-09 18:58:20'),
(4, 2, 'ORD202602157EE691', 5000.00, 'COD', 'pending', 'completed', 'MD RAUSHAN', 'jilani@gmail.com', '6280779503', 'House-pihwara P.O Uttara P.S Saharghat Disrict Madhubani, Bihar, 847308, India\r\nHouse-pihwara P.O Uttara P.S Saharghat Disrict Madhubani, Madhubani, 847308, India', 'New Delhi', 'Delhi', '110045', '2026-02-15 12:00:23', '2026-02-15 12:00:58'),
(5, 1, 'ORD2026031226800F', 16692.00, 'COD', 'pending', 'pending', 'Raushan', 'mdraushanji22@gmail.com', '06280779503', 'Block E, Gali number - 13, RZC-8, Part 1, Sitapuri,', 'New Delhi', 'Delhi', '110045', '2026-03-11 18:57:22', '2026-03-11 18:57:22'),
(6, 4, 'ORD20260723DDB595', 999.00, 'COD', 'pending', 'completed', 'Md Raushan Jilani', 'mdraushan@test.com', '06280779503', 'Dabri Dwarka Main Road, Sitapuri Part 1, New Delhi, South West, Delhi, 110045', 'Delhi', 'Delhi', '110045', '2026-07-23 16:22:53', '2026-07-23 16:26:02');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_title` varchar(255) NOT NULL,
  `product_price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_title`, `product_price`, `quantity`, `subtotal`, `created_at`) VALUES
(1, 1, 6, 'Coffee Maker', 3499.00, 1, 3499.00, '2026-02-09 10:46:48'),
(2, 2, 1, 'Wireless Headphones', 2999.00, 1, 2999.00, '2026-02-09 10:49:48'),
(3, 3, 12, 'BRUTON Exclusive Trendy Sports Running Shoes.', 299.00, 1, 299.00, '2026-02-09 18:54:41'),
(4, 3, 13, 'PHP, MySQL &amp; JavaScript All-in-One for Dummies.', 999.00, 1, 999.00, '2026-02-09 18:54:41'),
(6, 5, 10, 'Drop (Set of 2 Books)', 399.00, 4, 1596.00, '2026-03-11 18:57:22'),
(7, 5, 11, 'Toyzone Panther Electric Jeep (Blue).', 8799.00, 1, 8799.00, '2026-03-11 18:57:22'),
(8, 5, 12, 'BRUTON Exclusive Trendy Sports Running Shoes.', 299.00, 1, 299.00, '2026-03-11 18:57:22'),
(9, 5, 13, 'PHP, MySQL &amp; JavaScript All-in-One for Dummies.', 999.00, 1, 999.00, '2026-03-11 18:57:22'),
(10, 5, 2, 'Smart Watch', 4999.00, 1, 4999.00, '2026-03-11 18:57:22'),
(11, 6, 13, 'PHP, MySQL &amp; JavaScript All-in-One for Dummies.', 999.00, 1, 999.00, '2026-07-23 16:22:53');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `title`, `image`, `description`, `price`, `stock`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Wireless Headphones', '6989df284d8d60.99680781.jpg', 'High-quality wireless headphones with noise cancellation', 2999.00, 49, 'active', '2026-02-09 10:41:29', '2026-02-09 13:20:40'),
(2, 1, 'Smart Watch', '6989dd710d9225.78791276.jpg', 'Fitness tracker with heart rate monitor', 4999.00, 29, 'active', '2026-02-09 10:41:29', '2026-03-11 18:57:22'),
(3, 1, 'Bluetooth Speaker', '698a120694b964.64402760.jpg', 'Portable wireless speaker with deep bass', 1999.00, 40, 'active', '2026-02-09 10:41:29', '2026-02-09 16:57:42'),
(4, 2, 'Mens T-Shirt', '698a12756c98c9.69680333.jpg', 'Cotton casual t-shirt', 499.00, 100, 'active', '2026-02-09 10:41:29', '2026-02-09 16:59:33'),
(5, 2, 'Womens Jeans', '698a12ef077c72.91511752.jpg', 'Comfortable denim jeans', 1299.00, 80, 'active', '2026-02-09 10:41:29', '2026-02-09 17:01:35'),
(6, 3, 'Coffee Maker', '698a134cc12024.64645011.jpg', 'Automatic coffee maker machine', 3499.00, 24, 'active', '2026-02-09 10:41:29', '2026-02-09 17:03:08'),
(7, 3, 'Blender', '698a13e51f9ae5.97722832.jpg', 'High-speed blender for smoothies', 2499.00, 35, 'active', '2026-02-09 10:41:29', '2026-02-09 17:05:41'),
(8, 4, 'Fiction Novel', '698a1485c50a99.06081676.jpg', 'Bestselling fiction book', 299.00, 200, 'active', '2026-02-09 10:41:29', '2026-02-09 17:08:21'),
(9, 5, 'Yoga Mat', '698a14f0796c45.90553801.jpg', 'Non-slip exercise yoga mat', 799.00, 60, 'active', '2026-02-09 10:41:29', '2026-02-09 17:10:08'),
(10, 4, 'Drop (Set of 2 Books)', '6989dd258f0ae6.74843945.jpg', 'The common dilemmas faced by the Youth, the questions posed by them and the responses to those questions have been woven into a narrative that has shaped into a unique novel - &amp;quot;Drop.&amp;quot; Meet the Characters from this Novel: Andy:', 399.00, 1, 'active', '2026-02-09 11:22:12', '2026-03-11 18:57:22'),
(11, 1, 'Toyzone Panther Electric Jeep (Blue).', '6989e1a9ba8828.64631347.jpg', 'Turn playtime into an adventure with this Toyzone Electric Ride-On Car, designed for kids aged 2 years and above. With its eye-catching design and smooth performance. And for 3 and 4 years chhild', 8799.00, 2, 'active', '2026-02-09 13:31:21', '2026-03-11 18:57:22'),
(12, 5, 'BRUTON Exclusive Trendy Sports Running Shoes.', '698a1712234592.55101099.jpg', '✔ RUNNING SHOES: The BRUTON Men&amp;#039;s Running Shoes are designed to provide your feet with the support and comfort they require throughout your workout.', 299.00, 8, 'active', '2026-02-09 17:17:40', '2026-03-11 18:57:22'),
(13, 6, 'PHP, MySQL &amp; JavaScript All-in-One for Dummies.', '698a20298c83c4.58979129.jpg', 'It takes a powerful suite of technologies to drive the most-visited websites in the world.', 999.00, 2, 'active', '2026-02-09 17:58:01', '2026-07-23 16:22:53');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `is_primary`, `created_at`) VALUES
(1, 10, '6989c364b3e158.29644842.jpg', 1, '2026-02-09 11:22:12'),
(3, 1, '6989c9575aa846.13997360.jpg', 0, '2026-02-09 11:47:35');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `mobile`, `password`, `address`, `city`, `state`, `pincode`, `created_at`, `updated_at`) VALUES
(1, 'Md Raushan Jilani', 'mdraushanji22@gmail.com', '06280779503', '$2y$10$7XqlS3BSavRrFt0zjMKSIugW2ryDWqvjvbYjbYpKtW.efEbePk81a', 'Block E, Gali number - 13, RZC-8, Part 1, Sitapuri,', 'New Delhi', 'Delhi', '110045', '2026-02-09 10:45:01', '2026-02-09 18:25:55'),
(2, 'MD RAUSHAN', 'jilani@gmail.com', '6280779503', '$2y$10$XeR30E/hW3Cj.I9xXqfUq.bBjUC1lHk2.BT4cZqOrgchUAAtmj7/u', 'Dabri Dwarka Main Road, Sitapuri Part 1, New Delhi, South West, Delhi, 110045', 'New Delhi', 'Delhi', '110045', '2026-02-09 17:28:47', '2026-02-09 18:51:56'),
(3, 'Bruce', 'bruce@gmail.com', '6280779503', '$2y$10$WS/Ofw7WHG7QfBlZ7dbGWuNz0x9mDWdyfSvqru4ywTjG9g73n5xS.', NULL, NULL, NULL, NULL, '2026-02-13 16:59:19', '2026-02-13 16:59:19'),
(4, 'Md Raushan Jilani', 'mdraushan@test.com', '06280779503', '$2y$10$iF1Cy4F/EenACwiraWfSteNNORa0.vv6oYAfSQ4/ls8fRshWnYdfy', 'Dabri Dwarka Main Road, Sitapuri Part 1, New Delhi, South West, Delhi, 110045', 'Delhi', 'Delhi', '110045', '2026-07-23 16:21:58', '2026-07-23 16:23:31');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
