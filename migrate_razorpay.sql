-- Razorpay Payment Integration Migration
-- Run this SQL to add Razorpay columns to the orders table

ALTER TABLE `orders` 
ADD COLUMN `razorpay_order_id` varchar(100) DEFAULT NULL AFTER `payment_method`,
ADD COLUMN `razorpay_payment_id` varchar(100) DEFAULT NULL AFTER `razorpay_order_id`;
