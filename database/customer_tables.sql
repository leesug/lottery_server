-- 고객 관리 테이블 생성 스크립트

-- 고객 정보 테이블
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_code VARCHAR(20) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(50),
    state VARCHAR(50),
    postal_code VARCHAR(20),
    country VARCHAR(50),
    registration_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive', 'blocked') DEFAULT 'active',
    verification_status ENUM('verified', 'unverified') DEFAULT 'unverified',
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 고객 거래 내역 테이블
CREATE TABLE IF NOT EXISTS customer_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    transaction_type ENUM('purchase', 'prize_claim', 'refund', 'deposit', 'withdrawal', 'other') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    reference_number VARCHAR(50),
    status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    details TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 고객 문서 테이블
CREATE TABLE IF NOT EXISTS customer_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    document_type ENUM('id_proof', 'address_proof', 'bank_details', 'other') NOT NULL,
    document_number VARCHAR(50),
    document_path VARCHAR(255) NOT NULL,
    uploaded_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    verification_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    verification_date DATETIME,
    verified_by INT,
    rejection_reason TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 고객 설정 테이블
CREATE TABLE IF NOT EXISTS customer_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL UNIQUE,
    language VARCHAR(10) DEFAULT 'en',
    notification_email BOOLEAN DEFAULT TRUE,
    notification_sms BOOLEAN DEFAULT TRUE,
    notification_push BOOLEAN DEFAULT TRUE,
    marketing_consent BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 인덱스 생성
CREATE INDEX idx_customer_code ON customers(customer_code);
CREATE INDEX idx_customer_email ON customers(email);
CREATE INDEX idx_customer_status ON customers(status);
CREATE INDEX idx_customer_verification ON customers(verification_status);
CREATE INDEX idx_transaction_customer ON customer_transactions(customer_id);
CREATE INDEX idx_transaction_type ON customer_transactions(transaction_type);
CREATE INDEX idx_transaction_date ON customer_transactions(transaction_date);
CREATE INDEX idx_document_customer ON customer_documents(customer_id);
CREATE INDEX idx_document_type ON customer_documents(document_type);
CREATE INDEX idx_document_verification ON customer_documents(verification_status);
