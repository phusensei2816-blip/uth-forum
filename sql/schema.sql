-- =========================================================
-- UTH Forum - Database schema
-- Import: mysql -u root -p < schema.sql
-- =========================================================

CREATE DATABASE IF NOT EXISTS uth_forum CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE uth_forum;

-- ---------------------------------------------------------
-- Users (student / teacher / admin)
-- ---------------------------------------------------------
CREATE TABLE users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  email         VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name     VARCHAR(120) DEFAULT NULL,
  role          ENUM('student','teacher','admin') NOT NULL DEFAULT 'student',
  avatar        VARCHAR(255) DEFAULT NULL,
  is_active     TINYINT(1) NOT NULL DEFAULT 1,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Class groups (created by teachers, joined via invite code)
-- ---------------------------------------------------------
CREATE TABLE classes (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(150) NOT NULL,
  description  TEXT,
  teacher_id   INT UNSIGNED NOT NULL,
  invite_code  VARCHAR(12) NOT NULL UNIQUE,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE class_members (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  class_id   INT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED NOT NULL,
  joined_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_member (class_id, user_id),
  FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id)  REFERENCES users(id)   ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Posts (news / discussion). Student posts require admin approval.
-- Teacher announcements are auto-approved and can be flagged urgent.
-- ---------------------------------------------------------
CREATE TABLE posts (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id          INT UNSIGNED NOT NULL,
  class_id         INT UNSIGNED DEFAULT NULL,
  title            VARCHAR(200) NOT NULL,
  content          MEDIUMTEXT NOT NULL,          -- rich text (HTML) from editor
  is_announcement  TINYINT(1) NOT NULL DEFAULT 0, -- flagged urgent announcement (teacher only)
  status           ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  reject_reason    VARCHAR(255) DEFAULT NULL,
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)  REFERENCES users(id)   ON DELETE CASCADE,
  FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL,
  INDEX idx_status (status),
  INDEX idx_class (class_id)
) ENGINE=InnoDB;

CREATE TABLE comments (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  post_id    INT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED NOT NULL,
  content    TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE likes (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  post_id    INT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_like (post_id, user_id),
  FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Files (materials / assignments uploaded to a class, or attachments to a post)
-- ---------------------------------------------------------
CREATE TABLE files (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  class_id     INT UNSIGNED DEFAULT NULL,
  post_id      INT UNSIGNED DEFAULT NULL,
  uploader_id  INT UNSIGNED NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_name  VARCHAR(255) NOT NULL,
  filesize     INT UNSIGNED NOT NULL,
  mime_type    VARCHAR(100) DEFAULT NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (class_id)    REFERENCES classes(id) ON DELETE CASCADE,
  FOREIGN KEY (post_id)     REFERENCES posts(id)   ON DELETE CASCADE,
  FOREIGN KEY (uploader_id) REFERENCES users(id)   ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Chat: 1-1 (receiver_id set) or class/group (class_id set)
-- ---------------------------------------------------------
CREATE TABLE messages (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sender_id   INT UNSIGNED NOT NULL,
  receiver_id INT UNSIGNED DEFAULT NULL,
  class_id    INT UNSIGNED DEFAULT NULL,
  content     TEXT NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (sender_id)   REFERENCES users(id)   ON DELETE CASCADE,
  FOREIGN KEY (receiver_id) REFERENCES users(id)   ON DELETE CASCADE,
  FOREIGN KEY (class_id)    REFERENCES classes(id) ON DELETE CASCADE,
  INDEX idx_conv (sender_id, receiver_id),
  INDEX idx_class_chat (class_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Site visits (very simple counter for the admin dashboard)
-- ---------------------------------------------------------
CREATE TABLE visits (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  visited_at  DATE NOT NULL,
  hits        INT UNSIGNED NOT NULL DEFAULT 0,
  UNIQUE KEY uniq_day (visited_at)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Seed: one admin account (password: Admin@123 -- change after first login)
-- ---------------------------------------------------------
INSERT INTO users (username, email, password_hash, full_name, role)
VALUES ('admin', 'admin@ut.edu.vn', '$2y$10$G5b2wq6xVYV3M1z2p8m1Bu2m3E7fq0KfF2Q4Y9Zq0e1S8k2t2c3Uu', 'Quản trị viên', 'admin');
-- NOTE: the hash above is a placeholder. Generate a real one with:
--   php -r "echo password_hash('Admin@123', PASSWORD_DEFAULT);"
-- and UPDATE users SET password_hash='...' WHERE username='admin';
