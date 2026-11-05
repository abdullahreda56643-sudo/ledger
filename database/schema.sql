CREATE DATABASE IF NOT EXISTS ledger
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ledger;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('owner','worker') NOT NULL DEFAULT 'worker',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS parties (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  kind ENUM('customer','supplier','both') NOT NULL DEFAULT 'customer',
  notes VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_name (name),
  UNIQUE INDEX idx_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  party_id INT UNSIGNED NOT NULL,
  tx_date DATE NOT NULL,
  type ENUM('sell','pay','supply','pay_supplier','opening','adjust') NOT NULL,
  direction ENUM('debit','credit') NOT NULL,
  weight DECIMAL(10,3) NULL,
  unit_price BIGINT NULL,
  amount BIGINT NOT NULL,
  note VARCHAR(255) NULL,
  group_id CHAR(26) NULL,
  is_void TINYINT(1) NOT NULL DEFAULT 0,
  voided_by INT UNSIGNED NULL,
  voided_at DATETIME NULL,
  void_reason VARCHAR(255) NULL,
  created_by INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_transactions_party FOREIGN KEY (party_id) REFERENCES parties(id),
  CONSTRAINT fk_transactions_creator FOREIGN KEY (created_by) REFERENCES users(id),
  CONSTRAINT fk_transactions_voider FOREIGN KEY (voided_by) REFERENCES users(id),
  CONSTRAINT chk_transaction_amount CHECK (amount > 0),
  INDEX idx_party_date (party_id, tx_date, id),
  INDEX idx_date (tx_date),
  INDEX idx_group (group_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  action VARCHAR(50) NOT NULL,
  table_name VARCHAR(50) NULL,
  record_id BIGINT UNSIGNED NULL,
  details JSON NULL,
  ip VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_audit_record (table_name, record_id),
  INDEX idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(45) NOT NULL,
  username VARCHAR(50) NOT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_login_throttle (ip, username, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;