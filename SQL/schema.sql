-- ============================================================
-- AI Trading Investment Platform — Fresh Schema
-- All monetary columns: DECIMAL(15,2). No varchar for money.
-- ============================================================

CREATE DATABASE IF NOT EXISTS aitrading_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE aitrading_db;

-- ------------------------------------------------------------
-- Admin accounts
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admin (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(255) NOT NULL,
  email      VARCHAR(255) NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default admin: admin@aitrading.com / Admin@1234 (change immediately)
INSERT INTO admin (name, email, password)
VALUES ('Admin', 'admin@aitrading.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- ------------------------------------------------------------
-- Users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  email         VARCHAR(255) NOT NULL UNIQUE,
  fname         VARCHAR(255) NOT NULL,
  lname         VARCHAR(255) NOT NULL,
  password      VARCHAR(255) NOT NULL,                  -- bcrypt
  phone         VARCHAR(50)  DEFAULT NULL,
  gender        VARCHAR(20)  NOT NULL DEFAULT '',
  country       VARCHAR(100) DEFAULT NULL,
  address       TEXT         NOT NULL DEFAULT '',
  occupation    VARCHAR(255) DEFAULT 'User',
  refcode       VARCHAR(20)  NOT NULL UNIQUE,
  ref_balance   DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  profit        DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  balance       DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  bonus         DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  Id_photo      VARCHAR(255) DEFAULT NULL,              -- KYC document filename
  profile_photo VARCHAR(255) DEFAULT NULL,
  pin           VARCHAR(10)  DEFAULT NULL,
  referral      VARCHAR(20)  DEFAULT NULL,              -- refcode of who referred this user
  reset_token   VARCHAR(255) DEFAULT NULL,
  reset_expires DATETIME     DEFAULT NULL,
  date          DATETIME     DEFAULT CURRENT_TIMESTAMP,
  status        ENUM('Not Verified','submitted','Verified') NOT NULL DEFAULT 'Not Verified'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Investment Plans (replaces package1)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS plans (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  pname     VARCHAR(122) NOT NULL,
  bot_type  VARCHAR(50)  NOT NULL DEFAULT 'scalper',   -- scalper|arbitrage|trend|quantum
  increase  DECIMAL(8,4) NOT NULL,                     -- ROI percentage
  duration  INT          NOT NULL,                     -- days
  min_amt   DECIMAL(15,2) NOT NULL,
  max_amt   DECIMAL(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO plans (pname, bot_type, increase, duration, min_amt, max_amt) VALUES
  ('AI Scalper',    'scalper',    5.00,  1,   50.00,   500.00),
  ('AI Arbitrage',  'arbitrage',  8.00,  2,   600.00,  5000.00),
  ('AI Trend',      'trend',     15.00,  5,   5000.00, 10000.00),
  ('AI Quantum',    'quantum',   25.00,  7,   10000.00,30000.00);

-- ------------------------------------------------------------
-- Bot display settings (cosmetic, per plan)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bot_settings (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  plan_id        INT NOT NULL,
  bot_name       VARCHAR(100) NOT NULL DEFAULT 'AI Bot v1.0',
  bot_type       VARCHAR(50)  NOT NULL DEFAULT 'scalper',
  win_rate       DECIMAL(5,2) NOT NULL DEFAULT 87.50,
  accuracy       DECIMAL(5,2) NOT NULL DEFAULT 92.30,
  uptime_pct     DECIMAL(5,2) NOT NULL DEFAULT 99.70,
  trades_per_day INT          NOT NULL DEFAULT 45,
  FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO bot_settings (plan_id, bot_name, bot_type, win_rate, accuracy, uptime_pct, trades_per_day) VALUES
  (1, 'AI Scalper v2.4',    'scalper',   87.50, 92.30, 99.70, 120),
  (2, 'AI Arbitrage v1.9',  'arbitrage', 84.20, 89.60, 99.50, 45),
  (3, 'AI Trend v3.1',      'trend',     79.80, 86.10, 99.80, 18),
  (4, 'AI Quantum v1.0',    'quantum',   76.50, 83.40, 99.90, 8);

-- ------------------------------------------------------------
-- Investments
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS investments (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  userId       INT          NOT NULL,
  planId       INT          NOT NULL,
  invest_amt   DECIMAL(15,2) NOT NULL,
  plan_name    VARCHAR(122) NOT NULL,
  bot_type     VARCHAR(50)  NOT NULL DEFAULT 'scalper',
  increase     DECIMAL(8,4) NOT NULL,
  duration     INT          NOT NULL,
  total_profit DECIMAL(15,2) NOT NULL,
  start_date   DATETIME     NOT NULL,
  end_date     DATETIME     NOT NULL,
  status       ENUM('running','completed','cancelled') NOT NULL DEFAULT 'running',
  date         DATETIME     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (planId) REFERENCES plans(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Deposits
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS deposits (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  userId   INT          NOT NULL,
  method   VARCHAR(50)  NOT NULL,
  tranxId  VARCHAR(100) NOT NULL UNIQUE,
  amount   DECIMAL(15,2) NOT NULL,
  proof    VARCHAR(255) DEFAULT NULL,
  hash     VARCHAR(255) DEFAULT NULL,
  status   ENUM('pending','completed','rejected') NOT NULL DEFAULT 'pending',
  date     DATETIME     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Withdrawals
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS withdrawals (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  userId         INT          NOT NULL,
  email          VARCHAR(255) NOT NULL,
  type           ENUM('balance','refbonus') NOT NULL DEFAULT 'balance',
  method         VARCHAR(50)  NOT NULL,
  amount         DECIMAL(15,2) NOT NULL,
  wallet_address VARCHAR(255) DEFAULT NULL,
  tranxId        VARCHAR(100) NOT NULL UNIQUE,
  status         ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  date           DATETIME     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Simulated AI Trades (public feed)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ai_trades (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  pair        VARCHAR(20)  NOT NULL,
  action      ENUM('BUY','SELL') NOT NULL,
  entry_price DECIMAL(15,4) NOT NULL,
  exit_price  DECIMAL(15,4) DEFAULT NULL,
  profit_pct  DECIMAL(8,4)  DEFAULT NULL,
  status      ENUM('open','closed') NOT NULL DEFAULT 'open',
  bot_type    VARCHAR(50)  NOT NULL DEFAULT 'scalper',
  created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed with some initial trades so the feed isn't empty on first launch
INSERT INTO ai_trades (pair, action, entry_price, exit_price, profit_pct, status, bot_type) VALUES
  ('BTC/USDT', 'BUY',  67234.5000, 68891.2000, 2.4600, 'closed', 'scalper'),
  ('ETH/USDT', 'SELL',  3521.8000,  3448.5000,  2.0800, 'closed', 'arbitrage'),
  ('SOL/USDT', 'BUY',    178.4000,   183.9000,  3.0800, 'closed', 'trend'),
  ('BTC/USDT', 'BUY',  66980.0000, 67450.5000,  0.7000, 'closed', 'scalper'),
  ('BNB/USDT', 'SELL',   598.2000,   581.1000,  2.8600, 'closed', 'quantum'),
  ('ETH/USDT', 'BUY',   3489.0000,  3561.0000,  2.0600, 'closed', 'trend'),
  ('BTC/USDT', 'SELL', 68100.0000, 67500.0000,  0.8800, 'closed', 'scalper'),
  ('SOL/USDT', 'BUY',    181.0000,   185.3000,  2.3800, 'closed', 'arbitrage'),
  ('BTC/USDT', 'BUY',  67800.0000,  NULL,        NULL,  'open',   'quantum'),
  ('ETH/USDT', 'BUY',   3540.0000,  NULL,        NULL,  'open',   'scalper');

-- ------------------------------------------------------------
-- Profit Transfer Audit Log
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS transactions (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT          NOT NULL,
  amount     DECIMAL(15,2) NOT NULL,
  type       VARCHAR(50)  NOT NULL DEFAULT 'transfer',
  status     VARCHAR(50)  NOT NULL DEFAULT 'completed',
  created_at DATETIME     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Platform Settings
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  sitename        VARCHAR(255) NOT NULL DEFAULT 'AI Trading',
  siteurl         VARCHAR(255) NOT NULL DEFAULT 'http://localhost/AiTrading',
  sitemail        VARCHAR(255) NOT NULL DEFAULT 'support@aitrading.com',
  ref_bonus       DECIMAL(5,2) NOT NULL DEFAULT 10.00,   -- referral commission %
  whatsapp        VARCHAR(50)  DEFAULT NULL,
  livechat        TEXT         DEFAULT NULL,
  -- Wallet addresses (server-side only, never sent to client in JS)
  btc_wallet      VARCHAR(255) DEFAULT NULL,
  eth_wallet      VARCHAR(255) DEFAULT NULL,
  usdt_trc20      VARCHAR(255) DEFAULT NULL,
  usdt_erc20      VARCHAR(255) DEFAULT NULL,
  ltc_wallet      VARCHAR(255) DEFAULT NULL,
  bnb_wallet      VARCHAR(255) DEFAULT NULL,
  doge_wallet     VARCHAR(255) DEFAULT NULL,
  sol_wallet      VARCHAR(255) DEFAULT NULL,
  -- AI feed config
  ai_autogen      TINYINT(1)   NOT NULL DEFAULT 1,       -- 1 = cron auto-generates trades
  -- Cosmetic stats shown on landing page
  stat_managed    VARCHAR(20)  DEFAULT '$12M+',
  stat_bots       VARCHAR(20)  DEFAULT '48K+',
  stat_winrate    VARCHAR(20)  DEFAULT '87.5%',
  stat_countries  VARCHAR(20)  DEFAULT '80+'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO settings (sitename, siteurl, sitemail) VALUES
  ('AI Trading', 'http://localhost/AiTrading', 'support@aitrading.com');
