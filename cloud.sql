-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主机： 127.0.0.1
-- 生成日期： 2025-05-02 16:19:36
-- 服务器版本： 10.4.32-MariaDB
-- PHP 版本： 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `cloud`
--

-- --------------------------------------------------------

--
-- 表的结构 `chat_messages`
--

CREATE TABLE `chat_messages` (
  `message_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `sender_type` enum('user','admin') NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `chat_messages`
--

INSERT INTO `chat_messages` (`message_id`, `session_id`, `sender_type`, `message`, `is_read`, `created_at`) VALUES
(131, 21, 'admin', 'Welcome to our support chat! How can we help you today?', 0, '2025-04-30 08:18:44'),
(132, 21, '', 'Chat closed by administrator.', 0, '2025-04-30 08:19:02');

-- --------------------------------------------------------

--
-- 表的结构 `chat_sessions`
--

CREATE TABLE `chat_sessions` (
  `session_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `guest_name` varchar(100) DEFAULT NULL,
  `guest_email` varchar(100) DEFAULT NULL,
  `status` enum('active','closed') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `chat_sessions`
--

INSERT INTO `chat_sessions` (`session_id`, `user_id`, `guest_name`, `guest_email`, `status`, `created_at`, `updated_at`) VALUES
(21, NULL, 'aa', 'bb@mail.com', 'closed', '2025-04-30 08:18:44', '2025-04-30 08:19:02');

-- --------------------------------------------------------

--
-- 表的结构 `order`
--

CREATE TABLE `order` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `shipping_address` text DEFAULT NULL,
  `order_status` enum('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `order`
--

INSERT INTO `order` (`order_id`, `user_id`, `amount`, `shipping_address`, `order_status`, `updated_at`) VALUES
(37, 13, 320.00, 'dsa, dsa, dsa 52000, MY', 'pending', '2025-05-02 06:44:48');

-- --------------------------------------------------------

--
-- 表的结构 `order_item`
--

CREATE TABLE `order_item` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `order_item`
--

INSERT INTO `order_item` (`order_item_id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(51, 37, 2003, 8, 40.00);

-- --------------------------------------------------------

--
-- 表的结构 `product`
--

CREATE TABLE `product` (
  `productid` int(11) NOT NULL,
  `productname` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `imagepath` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 100
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `product`
--

INSERT INTO `product` (`productid`, `productname`, `description`, `price`, `imagepath`, `category`, `stock_quantity`) VALUES
(1001, 'Graduation Balloon', 'Celebratory balloon for graduation events', 15.00, 'image/ballon1.jpeg', 'Balloons', 500),
(1002, 'Letter Balloon', 'Customizable letter balloon for personalized messages', 25.00, 'image/letterballon1.jpeg', 'Balloons', 500),
(1003, 'Congratulations Balloon Bundle', 'Set of graduation-themed balloons', 35.00, 'image/ballon2.jpeg', 'Balloons', 499),
(2001, 'Graduate Bear', 'Adorable teddy bear in graduation attire', 20.00, 'image/bear1.jpeg', 'Bears', 500),
(2002, 'Key Chain Bear', 'Small teddy bear keychain, perfect as a graduation gift', 15.00, 'image/bear2.jpeg', 'Bears', 499),
(2003, 'Deluxe Graduation Bear', 'Premium teddy bear with graduation cap and diploma', 40.00, 'image/bear3.jpeg', 'Bears', 484),
(3001, 'Graduation Bouquet', 'Beautiful flower arrangement for graduation celebration', 68.88, 'image/flower1.jpeg', 'Flowers', 500),
(3002, 'Sunshine Bouquet', 'Bright and colorful flower arrangement', 58.88, 'image/flower2.jpeg', 'Flowers', 500),
(3003, 'Classic Rose Bouquet', 'Elegant rose arrangement for special occasions', 58.88, 'image/flower3.jpeg', 'Flowers', 500),
(3004, 'Mixed Flower Bouquet', 'Arrangement with various seasonal flowers', 58.88, 'image/flower4.jpeg', 'Flowers', 500),
(4001, 'Graduate Photo Frame', 'Commemorative photo frame for graduation pictures', 18.00, 'image/photoframe1.jpeg', 'Balloons', 498);

-- --------------------------------------------------------

--
-- 表的结构 `users`
--

CREATE TABLE `users` (
  `id` int(5) NOT NULL,
  `first_name` varchar(200) DEFAULT NULL,
  `last_name` varchar(200) DEFAULT NULL,
  `usergmail` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `zip` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `usergmail`, `password`, `address`, `city`, `state`, `zip`, `country`, `phone`) VALUES
(13, 'aa', 'bb', 'aa@gmail.com', '$2y$10$iq.53497xbMBzIlz/1mtM.IWJIvfILfUMxdVf8eh84GtW9yCuSIPK', 'dsa', 'dsa', 'dsa', '52000', 'MY', '0123456789');

--
-- 转储表的索引
--

--
-- 表的索引 `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `session_id` (`session_id`);

--
-- 表的索引 `chat_sessions`
--
ALTER TABLE `chat_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `user_id` (`user_id`);

--
-- 表的索引 `order`
--
ALTER TABLE `order`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- 表的索引 `order_item`
--
ALTER TABLE `order_item`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- 表的索引 `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`productid`);

--
-- 表的索引 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usergmail` (`usergmail`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=133;

--
-- 使用表AUTO_INCREMENT `chat_sessions`
--
ALTER TABLE `chat_sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- 使用表AUTO_INCREMENT `order`
--
ALTER TABLE `order`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- 使用表AUTO_INCREMENT `order_item`
--
ALTER TABLE `order_item`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- 使用表AUTO_INCREMENT `product`
--
ALTER TABLE `product`
  MODIFY `productid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5005;

--
-- 使用表AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- 限制导出的表
--

--
-- 限制表 `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `chat_sessions` (`session_id`) ON DELETE CASCADE;

--
-- 限制表 `chat_sessions`
--
ALTER TABLE `chat_sessions`
  ADD CONSTRAINT `chat_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- 限制表 `order`
--
ALTER TABLE `order`
  ADD CONSTRAINT `order_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- 限制表 `order_item`
--
ALTER TABLE `order_item`
  ADD CONSTRAINT `order_item_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `order` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_item_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`productid`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
