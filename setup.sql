-- Linkware database setup
-- Run this once in phpMyAdmin or via MySQL CLI

CREATE TABLE IF NOT EXISTS articles (
    id         VARCHAR(64)  PRIMARY KEY,
    title      TEXT         NOT NULL,
    category   VARCHAR(32)  NOT NULL DEFAULT 'implementation',
    excerpt    TEXT,
    body       LONGTEXT,
    status     VARCHAR(16)  NOT NULL DEFAULT 'draft',
    date       DATE,
    read_time  INT          NOT NULL DEFAULT 5,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS messages (
    id         VARCHAR(64)  PRIMARY KEY,
    date       DATETIME     NOT NULL,
    name       VARCHAR(255),
    org        VARCHAR(255),
    email      VARCHAR(255),
    service    VARCHAR(255),
    message    TEXT,
    is_read    TINYINT(1)   NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS subscribers (
    id    VARCHAR(64)  PRIMARY KEY,
    date  DATETIME     NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
    `key`   VARCHAR(64) PRIMARY KEY,
    `value` TEXT        NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default settings
INSERT IGNORE INTO settings (`key`, `value`) VALUES
    ('site_title',    'Linkware'),
    ('site_tagline',  'Capital Markets, Engineered'),
    ('admin_password','linkware2025');

-- After running setup.sql, run seed.php to insert the default articles.
