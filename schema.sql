-- AFHMC Web Form Database Schema
-- Database: afhmc_db

CREATE DATABASE IF NOT EXISTS `afhmc_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `afhmc_db`;

-- Single Unified Table for Complete AFHMC Application & Health Screening
CREATE TABLE IF NOT EXISTS `afhmc_applications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `submitted_at` DATETIME NOT NULL,
    -- Membership details
    `membership_type` VARCHAR(50) DEFAULT NULL,
    `title` VARCHAR(20) DEFAULT NULL,
    `rank` VARCHAR(50) DEFAULT NULL,
    `first_name` VARCHAR(100) DEFAULT NULL,
    `last_name` VARCHAR(100) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `occupation` VARCHAR(100) DEFAULT NULL,
    `nationality` VARCHAR(50) DEFAULT NULL,
    `official_address` TEXT DEFAULT NULL,
    `passport_nic` VARCHAR(50) DEFAULT NULL,
    `dob` VARCHAR(30) DEFAULT NULL,
    `telephone` VARCHAR(30) DEFAULT NULL,
    `mobile` VARCHAR(30) DEFAULT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `signature` VARCHAR(100) DEFAULT NULL,
    `recommendation` TEXT DEFAULT NULL,
    -- Official use fields
    `category` VARCHAR(50) DEFAULT NULL,
    `date_issue` VARCHAR(30) DEFAULT NULL,
    `date_expiry` VARCHAR(30) DEFAULT NULL,
    `date_received` VARCHAR(30) DEFAULT NULL,
    `receipt_no` VARCHAR(50) DEFAULT NULL,
    `amount_paid` VARCHAR(50) DEFAULT NULL,
    `card_number` VARCHAR(50) DEFAULT NULL,
    `commander` VARCHAR(100) DEFAULT NULL,
    `approval_date` VARCHAR(30) DEFAULT NULL,
    -- Health screening fields
    `gender` VARCHAR(20) DEFAULT NULL,
    `age` VARCHAR(10) DEFAULT NULL,
    `cardio_1` VARCHAR(10) DEFAULT NULL,
    `cardio_2` VARCHAR(10) DEFAULT NULL,
    `cardio_3` VARCHAR(10) DEFAULT NULL,
    `cardio_4` VARCHAR(10) DEFAULT NULL,
    `cardio_5` VARCHAR(10) DEFAULT NULL,
    `cardio_6` VARCHAR(10) DEFAULT NULL,
    `cardio_7` VARCHAR(10) DEFAULT NULL,
    `cardio_8` VARCHAR(10) DEFAULT NULL,
    `cardio_9` VARCHAR(10) DEFAULT NULL,
    `cardio_10` VARCHAR(10) DEFAULT NULL,
    `medications` TEXT DEFAULT NULL,
    `back_problem` VARCHAR(100) DEFAULT NULL,
    `orthopedic` TEXT DEFAULT NULL,
    `hernia` VARCHAR(10) DEFAULT NULL,
    `surgeries` VARCHAR(10) DEFAULT NULL,
    `surgery_details` TEXT DEFAULT NULL,
    `stress` VARCHAR(20) DEFAULT NULL,
    `activity` VARCHAR(50) DEFAULT NULL,
    `last_exam` VARCHAR(50) DEFAULT NULL,
    `health_signature` VARCHAR(100) DEFAULT NULL,
    `health_date` VARCHAR(30) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
