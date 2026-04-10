-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 10, 2026 at 03:02 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `haircare`
--

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('active','ordered','abandoned') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `carts`
--

INSERT INTO `carts` (`id`, `user_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 'active', '2026-04-08 17:09:41', '2026-04-08 17:09:41');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `category_sales`
-- (See below for the actual view)
--
CREATE TABLE `category_sales` (
`category` enum('shampoo','conditioner','treatment','hair oil')
,`order_count` bigint(21)
,`units_sold` decimal(32,0)
,`revenue` decimal(42,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `low_stock_alerts`
-- (See below for the actual view)
--
CREATE TABLE `low_stock_alerts` (
`category` enum('shampoo','conditioner','treatment','hair oil')
,`low_stock_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `type` varchar(50) DEFAULT 'order_cancelled',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `type`, `title`, `message`, `related_id`, `user_id`, `is_read`, `created_at`) VALUES
(1, 'order_cancelled', 'Order Cancelled', 'User ID 2 cancelled order #7.', 7, NULL, 1, '2026-04-08 18:05:04'),
(2, 'order_status_update', 'Order Status Updated', 'Your order status has been updated to: <strong>Processing</strong>', 6, 2, 0, '2026-04-08 18:13:39'),
(3, 'order_status_update', 'Order Status Updated', 'Your order status has been updated to: <strong>Delivered</strong>', 6, 2, 0, '2026-04-09 18:11:14'),
(4, 'order_status_update', 'Order Status Updated', 'Your order status has been updated to: <strong>Confirmed</strong>', 7, 2, 0, '2026-04-10 06:28:58'),
(5, 'order_status_update', 'Order Status Updated', 'Your order status has been updated to: <strong>Shipped</strong>', 7, 2, 0, '2026-04-10 06:29:02'),
(6, 'wishlist_restocked', '???? Back in Stock!', 'condihgfh is now available. Add it to your cart before it sells out!', 2, 2, 0, '2026-04-10 06:29:41'),
(7, 'order_cancelled', 'Order Cancelled', 'User ID 2 cancelled order #10.', 10, NULL, 0, '2026-04-10 08:38:18');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `delivery_code` varchar(20) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'confirmed',
  `subtotal` decimal(10,2) NOT NULL,
  `tax` decimal(10,2) NOT NULL,
  `shipping` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `address` text NOT NULL,
  `payment_method` varchar(50) DEFAULT 'cod',
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `transaction_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `esewa_transaction_uuid` varchar(100) DEFAULT NULL,
  `esewa_ref_id` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_number`, `delivery_code`, `status`, `subtotal`, `tax`, `shipping`, `total`, `address`, `payment_method`, `payment_status`, `transaction_id`, `created_at`, `esewa_transaction_uuid`, `esewa_ref_id`) VALUES
(5, 2, 'ORD-532062-260408', 'DLV-F3658B98', 'shipped', 5000.00, 400.00, 0.00, 5400.00, 'hhjh', 'cod', 'pending', NULL, '2026-04-08 17:30:01', NULL, NULL),
(6, 2, 'ORD-5735A6-2604', 'DLV-084A91DF', 'delivered', 600.00, 48.00, 0.00, 648.00, 'bmlk', 'cod', 'pending', NULL, '2026-04-08 17:46:20', NULL, NULL),
(7, 2, 'ORD-3A60FA-2604', 'DLV-4CB60BE7', 'shipped', 555.00, 44.40, 0.00, 599.40, 'lklk', 'cod', 'pending', NULL, '2026-04-08 17:48:49', NULL, NULL),
(8, 2, 'ORD-C27B86-2604', 'DLV-30584826', 'confirmed', 600.00, 48.00, 0.00, 648.00, 'fgg', 'khalti', 'pending', NULL, '2026-04-10 08:25:43', NULL, NULL),
(9, 2, 'ORD-C0D328-2604', 'DLV-6B088664', 'confirmed', 600.00, 48.00, 0.00, 648.00, 'cdv', 'esewa', 'pending', NULL, '2026-04-10 08:28:00', 'ORD-C0D328-2604', NULL),
(10, 2, 'ORD-8077CA-2604', 'DLV-F85970D4', 'cancelled', 1500.00, 120.00, 0.00, 1620.00, 'qedfef', 'esewa', 'pending', NULL, '2026-04-10 08:34:34', 'ORD-8077CA-2604', NULL),
(11, 2, 'ORD-6E8854-2604', 'DLV-C57D547E', 'confirmed', 1500.00, 120.00, 0.00, 1620.00, 'bfcb', 'esewa', 'pending', NULL, '2026-04-10 08:47:11', 'ORD-6E8854-2604', NULL),
(12, 2, 'ORD-28D745-2604', 'DLV-A6FDD762', 'confirmed', 1500.00, 120.00, 0.00, 1620.00, 'mvhfgh', 'esewa', 'pending', NULL, '2026-04-10 09:00:06', 'ORD-28D745-2604', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `price`) VALUES
(1, 5, 1, 'sampoo', 1, 5000.00),
(2, 6, 2, 'condihgfh', 1, 600.00),
(3, 7, 3, 'sampoo', 1, 555.00),
(4, 8, 2, 'condihgfh', 1, 600.00),
(5, 9, 2, 'condihgfh', 1, 600.00),
(6, 10, 4, 'hr', 1, 1500.00),
(7, 11, 4, 'hr', 1, 1500.00),
(8, 12, 4, 'hr', 1, 1500.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `category` enum('shampoo','conditioner','treatment','hair oil') DEFAULT 'shampoo',
  `hair_textures` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`hair_textures`)),
  `scalp_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`scalp_types`)),
  `hair_problems` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`hair_problems`)),
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `image_url` varchar(500) DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `category`, `hair_textures`, `scalp_types`, `hair_problems`, `description`, `price`, `stock_quantity`, `image_url`, `created_at`) VALUES
(1, 'sampoo', 'shampoo', NULL, NULL, NULL, 'iilwjisk', 5000.00, 0, 'uploads/products/69d68d6d12754_s1.png', '2026-04-08 16:51:15'),
(2, 'condihgfh', 'shampoo', NULL, NULL, NULL, 'jkhkn', 600.00, 0, 'uploads/products/69d898d589bcd_s1.png', '2026-04-08 17:46:06'),
(4, 'hr', NULL, '[\"straight\"]', '[\"dry\"]', '[\"hair fall\"]', 'dfesfderg', 1500.00, 0, 'uploads/products/69d8b60045612_PeachAndBrownGlamoursDesktopWallpaper.png', '2026-04-10 08:34:08');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `product_id`, `order_id`, `rating`, `comment`, `is_approved`, `created_at`) VALUES
(1, 2, 2, 6, 4, '', 1, '2026-04-09 18:11:39');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email_notifications` tinyint(1) DEFAULT 1,
  `promo_emails` tinyint(1) DEFAULT 0,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `email_notifications`, `promo_emails`, `first_name`, `last_name`, `role`, `created_at`, `updated_at`) VALUES
(1, 'admin@gmail.com', '$2y$10$INNuSQntsyQzrWDZ/QblZenfyQUUwdik1Zu7gSoJkRgmAbuLK7TRW', 1, 0, 'admin', 'admin', 'admin', '2026-04-08 16:19:21', '2026-04-08 16:19:52'),
(2, 'rojinadan@gmail.com', '$2y$10$RC4DgiRsQSSD5JkJVfLLlevFd.4q4sOHs.y4Q.gTxn6CqsRv0H2Rm', 1, 1, 'Rojina', 'Dangol', 'user', '2026-04-08 16:43:21', '2026-04-09 17:41:41');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_id`, `product_id`, `added_at`) VALUES
(1, 2, 1, '2026-04-08 17:30:34'),
(2, 2, 2, '2026-04-08 17:50:09');

-- --------------------------------------------------------

--
-- Structure for view `category_sales`
--
DROP TABLE IF EXISTS `category_sales`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `category_sales`  AS SELECT `p`.`category` AS `category`, count(distinct `o`.`id`) AS `order_count`, sum(`oi`.`quantity`) AS `units_sold`, sum(`oi`.`quantity` * `oi`.`price`) AS `revenue` FROM ((`orders` `o` join `order_items` `oi` on(`o`.`id` = `oi`.`order_id`)) join `products` `p` on(`oi`.`product_id` = `p`.`id`)) WHERE `o`.`status` in ('confirmed','processing','shipped','delivered') GROUP BY `p`.`category` ;

-- --------------------------------------------------------

--
-- Structure for view `low_stock_alerts`
--
DROP TABLE IF EXISTS `low_stock_alerts`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `low_stock_alerts`  AS SELECT `products`.`category` AS `category`, count(0) AS `low_stock_count` FROM `products` WHERE `products`.`stock_quantity` <= 5 AND `products`.`stock_quantity` > 0 GROUP BY `products`.`category` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_status` (`user_id`,`status`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_cart_product` (`cart_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_read` (`is_read`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD UNIQUE KEY `delivery_code` (`delivery_code`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_order_product` (`order_id`,`product_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_email` (`email`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
