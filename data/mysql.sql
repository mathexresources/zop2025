USE `zop2025`;

-- Drop tables in correct dependency order
DROP TABLE IF EXISTS `inventory`;
DROP TABLE IF EXISTS `items`;
DROP TABLE IF EXISTS `item_type_categories`;
DROP TABLE IF EXISTS `item_types`;
DROP TABLE IF EXISTS `category_tags`;
DROP TABLE IF EXISTS `tags`;
DROP TABLE IF EXISTS `user_roles`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `warehouses`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `password_resets`;

-- Create Users Table
CREATE TABLE `users`
(
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `username`   VARCHAR(100) NOT NULL UNIQUE,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name`  VARCHAR(100) NOT NULL,
    `phone`      VARCHAR(20),
    `email`      VARCHAR(100) NOT NULL UNIQUE,
    `password`   VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- Create Roles Table
CREATE TABLE `roles`
(
    `id`   INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- Create UserRoles Junction Table
CREATE TABLE `user_roles`
(
    `user_id` INT NOT NULL,
    `role_id` INT NOT NULL,
    PRIMARY KEY (`user_id`, `role_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- Create Warehouses Table
CREATE TABLE `warehouses`
(
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `name`        VARCHAR(100) NOT NULL,
    `description` TEXT,
    `location`    VARCHAR(255),
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- Create Categories Table
CREATE TABLE `categories`
(
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `name`        VARCHAR(100) NOT NULL,
    `description` TEXT,
    `parent_id`   INT,
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- Create Tags Table
CREATE TABLE `tags`
(
    `id`   INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- Create CategoryTags Junction Table
CREATE TABLE `category_tags`
(
    `category_id` INT NOT NULL,
    `tag_id`      INT NOT NULL,
    PRIMARY KEY (`category_id`, `tag_id`),
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- Create Item Types Table
CREATE TABLE `item_types`
(
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `name`        VARCHAR(100) NOT NULL,
    `description` TEXT,
    `weight`      DECIMAL(10, 2),
    `size_x`      DECIMAL(10, 2),
    `size_y`      DECIMAL(10, 2),
    `size_z`      DECIMAL(10, 2),
    `item_attrs`  JSON,
    `notes`       TEXT,
    `category_id` INT          NOT NULL,
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- Create ItemTypeCategories Junction Table
CREATE TABLE `item_type_categories`
(
    `item_type_id` INT NOT NULL,
    `category_id`  INT NOT NULL,
    PRIMARY KEY (`item_type_id`, `category_id`),
    FOREIGN KEY (`item_type_id`) REFERENCES `item_types` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- Create Items Table
CREATE TABLE `items`
(
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `item_type_id`  INT       NOT NULL,
    `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`item_type_id`) REFERENCES `item_types` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- Create Inventory Table (Junction between Items and Warehouses)
CREATE TABLE `inventory`
(
    `id`           INT AUTO_INCREMENT PRIMARY KEY,
    `item_id`      INT       NOT NULL,
    `warehouse_id` INT       NOT NULL,
    `quantity`     INT       NOT NULL DEFAULT 0,
    `last_updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`item_id`) REFERENCES `item_types` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

CREATE TABLE password_resets
(
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT         NOT NULL,
    token      VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME    NOT NULL,
    created_at DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP
);


-- Insert Default Roles
INSERT INTO `roles` (`name`)
VALUES ('user'),
       ('manager'),
       ('admin');

-- Insert Admin User
INSERT INTO `users` (`username`, `first_name`, `last_name`, `phone`, `email`, `password`)
VALUES ('admin', 'Admin', 'User', '1234567890', 'admin@admin.admin',
        '$2y$10$cLPM3vuNrGruKRNhsUxote7M4HBkfRxa8k7IUapYyHVa0MYk4T4sW');
-- username: admin, password: secret1

-- Assign Admin Role
INSERT INTO `user_roles` (`user_id`, `role_id`)
VALUES (1, 3);
