-- ============================================================
-- ClickDigim White-Label System — Full Database Schema
-- Database: whitelevel_db
-- Generated from: database-schema.md
-- FK creation order strictly respected
-- ============================================================

CREATE DATABASE IF NOT EXISTS `whitelevel_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `whitelevel_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- ──────────────────────────────────────────────────────────────
-- Drop all tables in reverse FK dependency order
-- (safe to re-run this file from scratch at any time)
-- ──────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS `testimonials`;
DROP TABLE IF EXISTS `notification`;
DROP TABLE IF EXISTS `appointments`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `invoice`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `cart_items`;
DROP TABLE IF EXISTS `cart`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `blog_post_tags`;
DROP TABLE IF EXISTS `blog_post_categories`;
DROP TABLE IF EXISTS `blog_tags`;
DROP TABLE IF EXISTS `blog_categories`;
DROP TABLE IF EXISTS `blog`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `services`;
DROP TABLE IF EXISTS `leads_relation_table`;
DROP TABLE IF EXISTS `leads`;
DROP TABLE IF EXISTS `admin_user`;
DROP TABLE IF EXISTS `frontend`;
DROP TABLE IF EXISTS `tenants_service_info`;
DROP TABLE IF EXISTS `clickdigim_customers_profile`;

-- ──────────────────────────────────────────────────────────────
-- 1. clickdigim_customers_profile
--    One row per franchise tenant. No dependencies.
-- ──────────────────────────────────────────────────────────────
CREATE TABLE `clickdigim_customers_profile` (
  `CCP_id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `name`                VARCHAR(255)  NOT NULL,
  `phone`               VARCHAR(50)   NULL,
  `address`             TEXT          NULL,
  `contact`             VARCHAR(255)  NULL,
  `stripe_customer_id`  VARCHAR(100)  NULL,
  `paypal_customer_id`  VARCHAR(100)  NULL,
  `default_currency`    VARCHAR(3)    NOT NULL DEFAULT 'USD',
  `created_at`          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`CCP_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- 2. tenants_service_info
--    Technical config per tenant. Depends on: clickdigim_customers_profile
-- ──────────────────────────────────────────────────────────────
CREATE TABLE `tenants_service_info` (
  `t_id`                    INT UNSIGNED                          NOT NULL AUTO_INCREMENT,
  `CCP_id`                  INT UNSIGNED                          NOT NULL,
  `seller_code`             VARCHAR(10)                           NULL,
  `public_key`              VARCHAR(255)                          NOT NULL,
  `smtp_settings`           JSON                                  NULL,
  `primary_domain`          VARCHAR(255)                          NULL,
  `status`                  ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `subscription_start_at`   DATE                                  NOT NULL,
  `subscription_expires_at` DATE                                  NOT NULL,
  `created_at`              DATETIME                              NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`              DATETIME                              NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`t_id`),
  UNIQUE KEY `tsi_public_key_unique` (`public_key`),
  UNIQUE KEY `tsi_seller_code_unique` (`seller_code`),
  CONSTRAINT `fk_tsi_tenant` FOREIGN KEY (`CCP_id`)
    REFERENCES `clickdigim_customers_profile` (`CCP_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- 3. frontend
--    Tenant subdomains. Depends on: tenants_service_info
-- ──────────────────────────────────────────────────────────────
CREATE TABLE `frontend` (
  `f_id`       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `t_id`       INT UNSIGNED NOT NULL,
  `subdomain`  VARCHAR(255) NOT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`f_id`),
  UNIQUE KEY `frontend_subdomain_unique` (`subdomain`),
  CONSTRAINT `fk_frontend_tsi` FOREIGN KEY (`t_id`)
    REFERENCES `tenants_service_info` (`t_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- 4. admin_user
--    All admin users across all tenants and ClickDigim itself.
--    Depends on: clickdigim_customers_profile (self-ref for created_by)
-- ──────────────────────────────────────────────────────────────
CREATE TABLE `admin_user` (
  `admin_id`      INT UNSIGNED                                                             NOT NULL AUTO_INCREMENT,
  `CCP_id`        INT UNSIGNED                                                             NULL,
  `name`          VARCHAR(255)                                                             NOT NULL,
  `email`         VARCHAR(255)                                                             NOT NULL,
  `password`      VARCHAR(255)                                                             NOT NULL,
  `role`          ENUM('super_admin','admin','media_manager','community_manager','support') NOT NULL DEFAULT 'admin',
  `is_active`     TINYINT(1)                                                               NOT NULL DEFAULT 1,
  `created_by`    INT UNSIGNED                                                             NULL,
  `last_login_at` DATETIME                                                                 NULL,
  `created_at`    DATETIME                                                                 NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME                                                                 NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `admin_email_unique` (`email`),
  CONSTRAINT `fk_admin_tenant`     FOREIGN KEY (`CCP_id`)
    REFERENCES `clickdigim_customers_profile` (`CCP_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_admin_created_by` FOREIGN KEY (`created_by`)
    REFERENCES `admin_user` (`admin_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- 5. leads
--    Global identity store — email only. No dependencies.
-- ──────────────────────────────────────────────────────────────
CREATE TABLE `leads` (
  `L_id`       INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `email`      VARCHAR(255)  NOT NULL,
  `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`L_id`),
  UNIQUE KEY `leads_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- 6. leads_relation_table
--    Per-tenant lead profile. Depends on: leads + clickdigim_customers_profile
-- ──────────────────────────────────────────────────────────────
CREATE TABLE `leads_relation_table` (
  `LR_id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `L_id`                INT UNSIGNED  NOT NULL,
  `CCP_id`              INT UNSIGNED  NOT NULL,
  `name`                VARCHAR(255)  NULL,
  `phone`               VARCHAR(50)   NULL,
  `country`             VARCHAR(100)  NULL,
  `legal_business_name` VARCHAR(255)  NULL,
  `website_url`         VARCHAR(500)  NULL,
  `created_at`          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`LR_id`),
  UNIQUE KEY `lrt_lead_tenant_unique` (`L_id`, `CCP_id`),
  CONSTRAINT `fk_lrt_lead`   FOREIGN KEY (`L_id`)
    REFERENCES `leads` (`L_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lrt_tenant` FOREIGN KEY (`CCP_id`)
    REFERENCES `clickdigim_customers_profile` (`CCP_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- 7. services
--    One row per service per tenant. Depends on: clickdigim_customers_profile
-- ──────────────────────────────────────────────────────────────
CREATE TABLE `services` (
  `s_id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `CCP_id`          INT UNSIGNED  NOT NULL,
  `service_name`    VARCHAR(255)  NOT NULL,
  `service_code`    VARCHAR(100)  NOT NULL,
  `unit_price`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `service_payload` JSON          NULL,
  `created_at`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`s_id`),
  UNIQUE KEY `services_ccp_code_unique` (`CCP_id`, `service_code`),
  CONSTRAINT `fk_services_tenant` FOREIGN KEY (`CCP_id`)
    REFERENCES `clickdigim_customers_profile` (`CCP_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- 8. products
--    Tenant product catalogue. Depends on: clickdigim_customers_profile
-- ──────────────────────────────────────────────────────────────
CREATE TABLE `products` (
  `p_id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `CCP_id`             INT UNSIGNED  NOT NULL,
  `product_name`       VARCHAR(255)  NOT NULL,
  `unit_price`         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `product_properties` JSON          NULL,
  `created_at`         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`p_id`),
  CONSTRAINT `fk_products_tenant` FOREIGN KEY (`CCP_id`)
    REFERENCES `clickdigim_customers_profile` (`CCP_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- 9. blog
--    Blog posts per tenant. Depends on: clickdigim_customers_profile + admin_user
-- ──────────────────────────────────────────────────────────────
CREATE TABLE `blog` (
  `b_id`               INT UNSIGNED                         NOT NULL AUTO_INCREMENT,
  `CCP_id`             INT UNSIGNED                         NOT NULL,
  `title`              VARCHAR(500)                         NOT NULL,
  `slug`               VARCHAR(500)                         NOT NULL,
  `subtitle`           VARCHAR(500)                         NULL,
  `excerpt`            TEXT                                 NULL,
  `content`            LONGTEXT                             NOT NULL,
  `featured_image`     VARCHAR(500)                         NULL,
  `featured_image_alt` VARCHAR(500)                         NULL,
  `author_id`          INT UNSIGNED                         NULL,
  `read_time`          INT                                  NOT NULL DEFAULT 5,
  `status`             ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
  `published_at`       DATETIME                             NULL,
  `meta_title`         VARCHAR(500)                         NULL,
  `meta_description`   TEXT                                 NULL,
  `views`              INT                                  NOT NULL DEFAULT 0,
  `is_paid`            TINYINT(1)                           NOT NULL DEFAULT 0,
  `price`              DECIMAL(8,2)                         NULL,
  `preview_percentage` TINYINT                              NOT NULL DEFAULT 30,
  `created_at`         DATETIME                             NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         DATETIME                             NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`b_id`),
  UNIQUE KEY `blog_ccp_slug_unique` (`CCP_id`, `slug`),
  INDEX `idx_blog_status` (`status`, `published_at`),
  CONSTRAINT `fk_blog_tenant` FOREIGN KEY (`CCP_id`)
    REFERENCES `clickdigim_customers_profile` (`CCP_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_blog_author` FOREIGN KEY (`author_id`)
    REFERENCES `admin_user` (`admin_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- 10. blog_categories
--     Depends on: clickdigim_customers_profile
-- ──────────────────────────────────────────────────────────────
CREATE TABLE `blog_categories` (
  `cat_id`      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `CCP_id`      INT UNSIGNED NOT NULL,
  `name`        VARCHAR(255) NOT NULL,
  `slug`        VARCHAR(255) NOT NULL,
  `description` TEXT         NULL,
  `color`       VARCHAR(7)   NOT NULL DEFAULT '#3B82F6',
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cat_id`),
  UNIQUE KEY `blog_cat_ccp_slug_unique` (`CCP_id`, `slug`),
  CONSTRAINT `fk_blogcat_tenant` FOREIGN KEY (`CCP_id`)
    REFERENCES `clickdigim_customers_profile` (`CCP_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- 11. blog_tags
--     Depends on: clickdigim_customers_profile
-- ──────────────────────────────────────────────────────────────
CREATE TABLE `blog_tags` (
  `tag_id`     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `CCP_id`     INT UNSIGNED NOT NULL,
  `name`       VARCHAR(255) NOT NULL,
  `slug`       VARCHAR(255) NOT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`tag_id`),
  UNIQUE KEY `blog_tag_ccp_slug_unique` (`CCP_id`, `slug`),
  CONSTRAINT `fk_blogtag_tenant` FOREIGN KEY (`CCP_id`)
    REFERENCES `clickdigim_customers_profile` (`CCP_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- 12. blog_post_categories  (pivot)
--     Depends on: blog + blog_categories
-- ──────────────────────────────────────────────────────────────
CREATE TABLE `blog_post_categories` (
  `b_id`   INT UNSIGNED NOT NULL,
  `cat_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`b_id`, `cat_id`),
  CONSTRAINT `fk_bpc_blog` FOREIGN KEY (`b_id`)
    REFERENCES `blog` (`b_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bpc_cat` FOREIGN KEY (`cat_id`)
    REFERENCES `blog_categories` (`cat_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- 13. blog_post_tags  (pivot)
--     Depends on: blog + blog_tags
-- ──────────────────────────────────────────────────────────────
CREATE TABLE `blog_post_tags` (
  `b_id`   INT UNSIGNED NOT NULL,
  `tag_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`b_id`, `tag_id`),
  CONSTRAINT `fk_bpt_blog` FOREIGN KEY (`b_id`)
    REFERENCES `blog` (`b_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bpt_tag` FOREIGN KEY (`tag_id`)
    REFERENCES `blog_tags` (`tag_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- 14. orders
--     One order per checkout. Depends on: leads_relation_table
-- ──────────────────────────────────────────────────────────────
CREATE TABLE `orders` (
  `o_id`          INT UNSIGNED                                  NOT NULL AUTO_INCREMENT,
  `LR_id`         INT UNSIGNED                                  NOT NULL,
  `total_amount`  DECIMAL(10,2)                                 NOT NULL,
  `currency_code` VARCHAR(3)                                    NOT NULL DEFAULT 'USD',
  `status`        ENUM('pending','paid','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `order_date`    DATETIME                                      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `order_notes`   TEXT                                          NULL,
  `created_at`    DATETIME                                      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME                                      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`o_id`),
  CONSTRAINT `fk_order_lr` FOREIGN KEY (`LR_id`)
    REFERENCES `leads_relation_table` (`LR_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- 15. cart
--     Active shopping cart. Depends on: leads_relation_table + clickdigim_customers_profile
--     CCP_id is kept directly for fast tenant-scoped cart queries (deliberate trade-off)
-- ──────────────────────────────────────────────────────────────
CREATE TABLE `cart` (
  `cart_id`    INT UNSIGNED                           NOT NULL AUTO_INCREMENT,
  `LR_id`      INT UNSIGNED                           NOT NULL,
  `CCP_id`     INT UNSIGNED                           NOT NULL,
  `status`     ENUM('active','converted','abandoned') NOT NULL DEFAULT 'active',
  `created_at` DATETIME                               NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME                               NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cart_id`),
  CONSTRAINT `fk_cart_lr`     FOREIGN KEY (`LR_id`)
    REFERENCES `leads_relation_table` (`LR_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cart_tenant` FOREIGN KEY (`CCP_id`)
    REFERENCES `clickdigim_customers_profile` (`CCP_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- 16. cart_items
--     Items in a cart. Depends on: cart + services + products
-- ──────────────────────────────────────────────────────────────
CREATE TABLE `cart_items` (
  `ci_id`        INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `cart_id`      INT UNSIGNED  NOT NULL,
  `service_id`   INT UNSIGNED  NULL,
  `product_id`   INT UNSIGNED  NULL,
  `quantity`     INT UNSIGNED  NOT NULL DEFAULT 1,
  `unit_price`   DECIMAL(10,2) NOT NULL,
  `item_payload` JSON          NULL,
  `created_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ci_id`),
  CONSTRAINT `fk_ci_cart`    FOREIGN KEY (`cart_id`)
    REFERENCES `cart` (`cart_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ci_service` FOREIGN KEY (`service_id`)
    REFERENCES `services` (`s_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ci_product` FOREIGN KEY (`product_id`)
    REFERENCES `products` (`p_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- 17. order_items
--     One row per service/product in an order.
--     Depends on: orders + leads_relation_table + services + products
-- ──────────────────────────────────────────────────────────────
CREATE TABLE `order_items` (
  `OI_id`        INT UNSIGNED                                   NOT NULL AUTO_INCREMENT,
  `o_id`         INT UNSIGNED                                   NOT NULL,
  `LR_id`        INT UNSIGNED                                   NOT NULL,
  `service_id`   INT UNSIGNED                                   NULL,
  `product_id`   INT UNSIGNED                                   NULL,
  `quantity`     INT UNSIGNED                                   NOT NULL DEFAULT 1,
  `unit_price`   DECIMAL(10,2)                                  NOT NULL,
  `subtotal`     DECIMAL(10,2)                                  NOT NULL,
  `item_payload` JSON                                           NULL,
  `status`       ENUM('pending','paid','cancelled','completed') NOT NULL DEFAULT 'pending',
  `created_at`   DATETIME                                       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME                                       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`OI_id`),
  CONSTRAINT `fk_oi_order`   FOREIGN KEY (`o_id`)
    REFERENCES `orders` (`o_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_oi_lr`      FOREIGN KEY (`LR_id`)
    REFERENCES `leads_relation_table` (`LR_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_oi_service` FOREIGN KEY (`service_id`)
    REFERENCES `services` (`s_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_oi_product` FOREIGN KEY (`product_id`)
    REFERENCES `products` (`p_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- 18. invoice
--     One invoice per order. Depends on: orders + leads_relation_table
-- ──────────────────────────────────────────────────────────────
CREATE TABLE `invoice` (
  `i_id`           INT UNSIGNED                                      NOT NULL AUTO_INCREMENT,
  `LR_id`          INT UNSIGNED                                      NOT NULL,
  `order_id`       INT UNSIGNED                                      NOT NULL,
  `invoice_number` VARCHAR(100)                                      NOT NULL,
  `total_amount`   DECIMAL(10,2)                                     NOT NULL,
  `issue_date`     DATE                                              NOT NULL,
  `due_date`       DATE                                              NULL,
  `status`         ENUM('draft','sent','paid','overdue','cancelled') NOT NULL DEFAULT 'draft',
  `created_at`     DATETIME                                          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME                                          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`i_id`),
  UNIQUE KEY `invoice_number_unique` (`invoice_number`),
  CONSTRAINT `fk_invoice_lr`    FOREIGN KEY (`LR_id`)
    REFERENCES `leads_relation_table` (`LR_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_invoice_order` FOREIGN KEY (`order_id`)
    REFERENCES `orders` (`o_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- 19. payments
--     One row per payment capture. CCP_id NOT stored (derive via LR_id join).
--     Depends on: invoice + leads_relation_table
-- ──────────────────────────────────────────────────────────────
CREATE TABLE `payments` (
  `P_id`            INT UNSIGNED                               NOT NULL AUTO_INCREMENT,
  `invoice_id`      INT UNSIGNED                               NOT NULL,
  `LR_id`           INT UNSIGNED                               NOT NULL,
  `payment_date`    DATETIME                                   NOT NULL,
  `amount`          DECIMAL(10,2)                              NOT NULL,
  `payment_method`  VARCHAR(50)                                NOT NULL,
  `transaction_ref` VARCHAR(255)                               NULL,
  `status`          ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `created_at`      DATETIME                                   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME                                   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`P_id`),
  CONSTRAINT `fk_payments_invoice` FOREIGN KEY (`invoice_id`)
    REFERENCES `invoice` (`i_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payments_lr`      FOREIGN KEY (`LR_id`)
    REFERENCES `leads_relation_table` (`LR_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- 20. appointments
--     One row per booked slot. Separate from order_items because:
--     - Slot availability needs indexed queries (not queryable in JSON)
--     - balance_token must be UNIQUE (IDOR protection)
--     - Status/meeting_link/google_event_id change after order is paid
--     Depends on: leads_relation_table + services + orders
-- ──────────────────────────────────────────────────────────────
CREATE TABLE `appointments` (
  `apt_id`                INT UNSIGNED                                                              NOT NULL AUTO_INCREMENT,
  `LR_id`                 INT UNSIGNED                                                              NOT NULL,
  `service_id`            INT UNSIGNED                                                              NOT NULL,
  `o_id`                  INT UNSIGNED                                                              NULL,
  `appointment_date`      DATE                                                                      NOT NULL,
  `appointment_time`      TIME                                                                      NOT NULL,
  `duration_minutes`      INT                                                                       NOT NULL DEFAULT 60,
  `timezone`              VARCHAR(100)                                                              NULL,
  `notes`                 TEXT                                                                      NULL,
  `status`                ENUM('pending','confirmed','cancelled','completed')                       NOT NULL DEFAULT 'pending',
  `meeting_link`          VARCHAR(500)                                                              NULL,
  `google_event_id`       VARCHAR(255)                                                              NULL,
  `payment_reference`     VARCHAR(100)                                                              NULL,
  `amount_paid`           DECIMAL(10,2)                                                             NULL,
  `after_amount`          DECIMAL(10,2)                                                             NULL,
  `balance_token`         VARCHAR(64)                                                               NULL,
  `second_payment_status` ENUM('pending','paid','failed')                                          NULL,
  `overall_payment_status` ENUM('unpaid','deposit_paid','balance_requested','completed','failed')  NOT NULL DEFAULT 'unpaid',
  `created_at`            DATETIME                                                                  NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            DATETIME                                                                  NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`apt_id`),
  UNIQUE KEY `appointments_balance_token_unique` (`balance_token`),
  UNIQUE KEY `unique_service_slot` (`service_id`, `appointment_date`, `appointment_time`),
  INDEX `idx_apt_slot` (`service_id`, `appointment_date`, `appointment_time`),
  CONSTRAINT `fk_apt_lr`      FOREIGN KEY (`LR_id`)
    REFERENCES `leads_relation_table` (`LR_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_apt_service` FOREIGN KEY (`service_id`)
    REFERENCES `services` (`s_id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_apt_order`   FOREIGN KEY (`o_id`)
    REFERENCES `orders` (`o_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- 21. notification
--     Admin dashboard activity feed. Depends on: leads_relation_table
-- ──────────────────────────────────────────────────────────────
CREATE TABLE `notification` (
  `n_id`       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `LR_id`      INT UNSIGNED NOT NULL,
  `date`       DATE         NOT NULL,
  `time`       TIME         NOT NULL,
  `type`       VARCHAR(60)  NOT NULL,
  `message`    TEXT         NOT NULL,
  `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`n_id`),
  INDEX `idx_notification_lr` (`LR_id`),
  CONSTRAINT `fk_notification_lr` FOREIGN KEY (`LR_id`)
    REFERENCES `leads_relation_table` (`LR_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- 22. testimonials
--     Ratings/reviews per tenant. Depends on: leads_relation_table
--     LR_id is NOT NULL — every testimonial must belong to a tracked lead.
--     CCP_id is NOT stored — derive via LR_id → leads_relation_table.CCP_id
-- ──────────────────────────────────────────────────────────────
CREATE TABLE `testimonials` (
  `testimonial_id` INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `LR_id`          INT UNSIGNED     NOT NULL,
  `author_name`    VARCHAR(255)     NOT NULL,
  `author_title`   VARCHAR(255)     NULL,
  `company_name`   VARCHAR(255)     NULL,
  `country`        VARCHAR(100)     NULL,
  `rating`         TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `quote`          TEXT             NOT NULL,
  `image_path`     VARCHAR(500)     NULL,
  `avatar_url`     VARCHAR(500)     NULL,
  `is_active`      TINYINT(1)       NOT NULL DEFAULT 0,
  `display_order`  INT UNSIGNED     NOT NULL DEFAULT 999,
  `created_at`     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`testimonial_id`),
  INDEX `idx_testimonial_lr` (`LR_id`, `is_active`, `display_order`),
  CONSTRAINT `fk_testimonial_lr` FOREIGN KEY (`LR_id`)
    REFERENCES `leads_relation_table` (`LR_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SEED DATA
-- ============================================================

-- ── ClickDigim itself (CCP_id = 1) ───────────────────────────
-- Must exist before any services can be seeded (FK on CCP_id)
INSERT INTO `clickdigim_customers_profile`
  (`CCP_id`, `name`, `contact`, `default_currency`)
VALUES
  (1, 'ClickDigim', 'Admin', 'USD');

-- ── services (CCP_id = 1 = ClickDigim itself) ────────────────

INSERT INTO `services`
  (`CCP_id`, `service_name`, `service_code`, `unit_price`, `service_payload`)
VALUES

-- Free appointment
(1, 'Free Appointment', 'free-appointment', 0.00,
  '{"appointment_type":"free","is_paid":false,"deposit_pct":0,"fields":[{"key":"appointment_date","type":"date","required":true},{"key":"appointment_time","type":"time","required":true},{"key":"timezone","type":"string","required":false},{"key":"notes","type":"textarea","required":false}]}'
),

-- Paid appointments
(1, 'Quick Consultation', 'quick-consultation', 74.99,
  '{"appointment_type":"paid","is_paid":true,"deposit_pct":50,"fields":[{"key":"appointment_date","type":"date","required":true},{"key":"appointment_time","type":"time","required":true},{"key":"timezone","type":"string","required":false},{"key":"notes","type":"textarea","required":false}]}'
),
(1, 'Standard Meeting', 'standard-meeting', 100.00,
  '{"appointment_type":"paid","is_paid":true,"deposit_pct":50,"fields":[{"key":"appointment_date","type":"date","required":true},{"key":"appointment_time","type":"time","required":true},{"key":"timezone","type":"string","required":false},{"key":"notes","type":"textarea","required":false}]}'
),
(1, 'Extended Session', 'extended-session', 150.00,
  '{"appointment_type":"paid","is_paid":true,"deposit_pct":50,"fields":[{"key":"appointment_date","type":"date","required":true},{"key":"appointment_time","type":"time","required":true},{"key":"timezone","type":"string","required":false},{"key":"notes","type":"textarea","required":false}]}'
),
(1, 'Full Strategy Session', 'full-strategy', 200.00,
  '{"appointment_type":"paid","is_paid":true,"deposit_pct":50,"fields":[{"key":"appointment_date","type":"date","required":true},{"key":"appointment_time","type":"time","required":true},{"key":"timezone","type":"string","required":false},{"key":"notes","type":"textarea","required":false}]}'
),

-- Growth Plan
(1, 'Growth Plan', 'growth-plan', 1500.00,
  '{"fields":[{"key":"business_name","type":"string","required":true}]}'
),

-- Ad Booking
(1, 'Ad Booking', 'ad-booking', 5.00,
  '{"fields":[{"key":"title","type":"string","required":true},{"key":"summary","type":"string","required":true},{"key":"keyword","type":"string","required":true},{"key":"target_url","type":"url","required":true},{"key":"slot_number","type":"integer","min":1,"max":4,"required":true},{"key":"duration_type","type":"enum","options":["hours","days"],"required":true},{"key":"duration_value","type":"integer","min":1,"required":true},{"key":"start_date","type":"date","required":true},{"key":"end_date","type":"date","required":true},{"key":"image_path","type":"file","required":true}]}'
),

-- Featured Blog
(1, 'Featured Blog', 'featured-blog', 250.00,
  '{"fields":[{"key":"suggested_keywords","type":"string","required":false},{"key":"products","type":"string","required":true},{"key":"quantity","type":"integer","required":true}]}'
),

-- Add-ons
(1, 'Multi-Site Pack', 'multi-site-pack', 1200.00, '{"fields":[]}'),
(1, 'Press Release',   'press-release',   400.00,  '{"fields":[]}'),

-- White Label Franchise plans (ClickDigim selling to new tenants)
(1, 'White Label Franchise - Starter',    'wl-franchise-starter',    299.00,
  '{"fields":[{"key":"business_name","type":"string","required":true},{"key":"plan_name","type":"string","required":true},{"key":"billing_period","type":"string","required":true}]}'
),
(1, 'White Label Franchise - Growth',     'wl-franchise-growth',     599.00,
  '{"fields":[{"key":"business_name","type":"string","required":true},{"key":"plan_name","type":"string","required":true},{"key":"billing_period","type":"string","required":true}]}'
),
(1, 'White Label Franchise - Enterprise', 'wl-franchise-enterprise', 999.00,
  '{"fields":[{"key":"business_name","type":"string","required":true},{"key":"plan_name","type":"string","required":true},{"key":"billing_period","type":"string","required":true}]}'
),

-- Blog Article Unlock
(1, 'Blog Article Unlock', 'blog-article-unlock', 0.00,
  '{"fields":[{"key":"post_id","type":"integer","required":true}]}'
);
