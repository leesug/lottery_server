-- 데이터베이스 생성
CREATE DATABASE IF NOT EXISTS lotto_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 데이터베이스 선택
USE lotto_system;

-- 사용자 테이블 생성
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'agent', 'user') NOT NULL DEFAULT 'user',
    status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 단말기 테이블 생성
CREATE TABLE IF NOT EXISTS terminals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    terminal_code VARCHAR(50) NOT NULL UNIQUE,
    location VARCHAR(255) NOT NULL,
    agent_id INT,
    status ENUM('active', 'inactive', 'maintenance') NOT NULL DEFAULT 'active',
    last_connection TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (agent_id) REFERENCES users(id)
);

-- 로또 티켓 테이블 생성
CREATE TABLE IF NOT EXISTS tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_number VARCHAR(50) NOT NULL UNIQUE,
    terminal_id INT NOT NULL,
    user_id INT,
    numbers VARCHAR(100) NOT NULL,
    draw_date DATE NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    status ENUM('active', 'won', 'lost', 'cancelled') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (terminal_id) REFERENCES terminals(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 당첨 내역 테이블 생성
CREATE TABLE IF NOT EXISTS winnings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT,
    draw_date DATE NOT NULL,
    winning_numbers VARCHAR(100) NOT NULL,
    matched_numbers VARCHAR(100) NOT NULL,
    prize_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'claimed', 'paid') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 로그인 이력 테이블 생성
CREATE TABLE IF NOT EXISTS login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) NOT NULL,
    status ENUM('success', 'failed') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 시스템 로그 테이블 생성
CREATE TABLE IF NOT EXISTS system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    log_type ENUM('info', 'warning', 'error', 'critical') NOT NULL,
    message TEXT NOT NULL,
    source VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45),
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 비밀번호 재설정 토큰 테이블 생성
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expiry_date DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 기본 관리자 계정 생성 (비밀번호: admin123)
INSERT INTO users (username, email, password, role) VALUES 
('관리자', 'admin@lotto.com', '$2y$12$QTJbh.3IMgXH4dX1EJJMeuOiSrKYQnMFiV9YqXwv59.lngEsZoJ6W', 'admin');

-- 샘플 데이터: 에이전트 계정
INSERT INTO users (username, email, password, role) VALUES 
('에이전트1', 'agent1@lotto.com', '$2y$12$QTJbh.3IMgXH4dX1EJJMeuOiSrKYQnMFiV9YqXwv59.lngEsZoJ6W', 'agent');

-- 샘플 데이터: 일반 사용자 계정
INSERT INTO users (username, email, password, role) VALUES 
('사용자1', 'user1@example.com', '$2y$12$QTJbh.3IMgXH4dX1EJJMeuOiSrKYQnMFiV9YqXwv59.lngEsZoJ6W', 'user');

-- 샘플 데이터: 단말기
INSERT INTO terminals (terminal_code, location, agent_id, status) VALUES
('TERM001', '서울시 강남구 강남대로 123', 2, 'active'),
('TERM002', '서울시 서초구 서초대로 456', 2, 'active'),
('TERM003', '서울시 종로구 종로 789', NULL, 'inactive');

-- 샘플 데이터: 티켓
INSERT INTO tickets (ticket_number, terminal_id, user_id, numbers, draw_date, amount, status) VALUES
('T20250515001', 1, 3, '1,7,15,23,32,45', '2025-05-17', 1000.00, 'active'),
('T20250515002', 1, NULL, '2,8,16,24,33,44', '2025-05-17', 1000.00, 'active'),
('T20250515003', 2, 3, '3,9,17,25,34,43', '2025-05-17', 1000.00, 'active');

-- 샘플 데이터: 당첨 내역
INSERT INTO winnings (ticket_id, user_id, draw_date, winning_numbers, matched_numbers, prize_amount, status) VALUES
(1, 3, '2025-05-10', '1,7,15,23,32,45', '1,7,15,23,32,45', 50000000.00, 'pending'),
(3, 3, '2025-05-10', '3,9,17,25,34,43', '3,9,17', 5000.00, 'claimed');
