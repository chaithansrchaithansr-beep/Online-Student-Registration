-- ============================================================================
-- schema.sql - MySQL Schema for Online Student Registration System
-- ============================================================================
-- Run this script in phpMyAdmin or MySQL CLI if setting up manually
-- (e.g., on InfinityFree / shared hosting, select your pre-created database
-- in phpMyAdmin and run the CREATE TABLE statement below).

CREATE DATABASE IF NOT EXISTS `student_registration`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `student_registration`;

CREATE TABLE IF NOT EXISTS `students` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `full_name` VARCHAR(100) NOT NULL,
    `usn` VARCHAR(20) NOT NULL,
    `email` VARCHAR(120) NOT NULL,
    `phone` VARCHAR(15) NOT NULL,
    `department` VARCHAR(20) NOT NULL,
    `semester` VARCHAR(25) NOT NULL,
    `address` TEXT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_students_usn` (`usn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
