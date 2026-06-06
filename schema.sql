-- =====================================================================
--  WebIT — TENANT (per-customer content) database template
--  One database per site. Lives on the platform as the SOURCE OF TRUTH.
--  A read-only copy of the CONTENT tables is synced DOWN to the customer's
--  own host. The TRANSACTIONAL tables (orders/subscribers/submissions) are
--  written by the live end-user site and synced UP to the platform.
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------- CONTENT (platform -> host, read-only) --------------

CREATE TABLE IF NOT EXISTS settings (
    `key`   VARCHAR(80) PRIMARY KEY,
    `value` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pages (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug        VARCHAR(190) NOT NULL UNIQUE,
    title       VARCHAR(190) NOT NULL,
    meta_desc   VARCHAR(255) NULL,
    position    INT NOT NULL DEFAULT 0,
    status      ENUM('draft','published') NOT NULL DEFAULT 'draft',
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS content_blocks (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id     INT UNSIGNED NOT NULL,
    type        VARCHAR(40) NOT NULL,          -- hero|text|image|gallery|form|product_grid
    position    INT NOT NULL DEFAULT 0,
    payload     JSON NOT NULL,                 -- block-specific content
    KEY idx_block_page (page_id),
    CONSTRAINT fk_block_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS menus (
    id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name   VARCHAR(80) NOT NULL,
    slug   VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS menu_items (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    menu_id   INT UNSIGNED NOT NULL,
    label     VARCHAR(120) NOT NULL,
    url       VARCHAR(255) NOT NULL,
    position  INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_mi_menu FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS media (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    filename    VARCHAR(255) NOT NULL,
    url         VARCHAR(255) NOT NULL,
    mime        VARCHAR(80) NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS products (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sku         VARCHAR(80) NOT NULL UNIQUE,
    name        VARCHAR(190) NOT NULL,
    description TEXT NULL,
    price       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock       INT NOT NULL DEFAULT 0,
    status      ENUM('active','hidden') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------- TRANSACTIONAL (host -> platform, sync up) -------------
-- Written by the live end-user site; pulled upward to the platform. Each
-- row carries a sync flag so only new rows travel up.

-- client_uid: a client-generated unique id making upward sync idempotent
-- (a retried POST never duplicates a row). synced_up is a client-side flag.

CREATE TABLE IF NOT EXISTS orders (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_uid    VARCHAR(64) NULL UNIQUE,
    reference     VARCHAR(60) NOT NULL UNIQUE,
    customer_name VARCHAR(160) NULL,
    customer_email VARCHAR(190) NULL,
    total         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    payload       JSON NULL,                  -- line items / shipping
    synced_up     TINYINT(1) NOT NULL DEFAULT 0,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_orders_sync (synced_up)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS subscribers (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_uid  VARCHAR(64) NULL UNIQUE,
    email       VARCHAR(190) NOT NULL,
    name        VARCHAR(160) NULL,
    source      VARCHAR(80) NULL,
    synced_up   TINYINT(1) NOT NULL DEFAULT 0,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_sub_email (email),
    KEY idx_sub_sync (synced_up)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS form_submissions (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_uid  VARCHAR(64) NULL UNIQUE,
    form_slug   VARCHAR(80) NOT NULL,
    payload     JSON NOT NULL,
    synced_up   TINYINT(1) NOT NULL DEFAULT 0,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_fs_sync (synced_up)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
