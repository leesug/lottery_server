<?php
/**
 * 로또 서버 데이터베이스 생성 스크립트
 * 
 * 이 스크립트는 로또 서버 시스템에 필요한 데이터베이스와 테이블을 생성합니다.
 * 실행 시 모든 테이블을 자동으로 생성하며, 진행 상황을 화면에 표시합니다.
 */

// 설정
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'lotto_server';

// 결과 로깅 함수
function logResult($message, $success = true) {
    $status = $success ? 'success' : 'error';
    $color = $success ? 'green' : 'red';
    echo "<p style='color: $color;'><strong>[$status]</strong> $message</p>";
}

// 스크립트 실행 시작
echo "<h1>로또 서버 데이터베이스 설정</h1>";
echo "<h2>데이터베이스 및 테이블 생성 스크립트</h2>";
echo "<div style='font-family: monospace; background-color: #f0f0f0; padding: 20px; border-radius: 5px;'>";

try {
    // MySQL 서버 연결
    echo "<h3>MySQL 서버 연결 중...</h3>";
    $conn = new mysqli($host, $username, $password);
    
    // 연결 확인
    if ($conn->connect_error) {
        throw new Exception("MySQL 서버 연결 실패: " . $conn->connect_error);
    }
    logResult("MySQL 서버 연결 성공");
    
    // 데이터베이스 생성
    echo "<h3>데이터베이스 생성 중...</h3>";
    $sql = "CREATE DATABASE IF NOT EXISTS `$dbname` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
    
    if ($conn->query($sql) === TRUE) {
        logResult("데이터베이스 '$dbname' 생성 성공 또는 이미 존재함");
    } else {
        throw new Exception("데이터베이스 생성 실패: " . $conn->error);
    }
    
    // 데이터베이스 선택
    $conn->select_db($dbname);
    logResult("데이터베이스 '$dbname' 선택됨");
    
    // 테이블 생성
    echo "<h3>테이블 생성 중...</h3>";
    
    // 사용자 테이블
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'manager', 'operator', 'finance', 'store', 'customer') NOT NULL DEFAULT 'operator',
        first_name VARCHAR(50),
        last_name VARCHAR(50),
        phone VARCHAR(20),
        status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
        profile_image VARCHAR(255),
        last_login DATETIME,
        last_ip VARCHAR(45),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";
    
    if ($conn->query($sql) === TRUE) {
        logResult("사용자(users) 테이블 생성 성공");
    } else {
        throw new Exception("사용자 테이블 생성 실패: " . $conn->error);
    }
    
    // 재무 카테고리 테이블
    $sql = "CREATE TABLE IF NOT EXISTS financial_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_name VARCHAR(100) NOT NULL,
        category_type ENUM('income', 'expense', 'both') NOT NULL,
        description TEXT,
        parent_id INT,
        budget_allocation DECIMAL(15,2),
        is_active BOOLEAN DEFAULT TRUE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (parent_id) REFERENCES financial_categories(id) ON DELETE SET NULL
    ) ENGINE=InnoDB";
    
    if ($conn->query($sql) === TRUE) {
        logResult("재무 카테고리(financial_categories) 테이블 생성 성공");
    } else {
        throw new Exception("재무 카테고리 테이블 생성 실패: " . $conn->error);
    }
    
    // 고객 테이블
    $sql = "CREATE TABLE IF NOT EXISTS customers (
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
    ) ENGINE=InnoDB";
    
    if ($conn->query($sql) === TRUE) {
        logResult("고객(customers) 테이블 생성 성공");
    } else {
        throw new Exception("고객 테이블 생성 실패: " . $conn->error);
    }
    
    // 판매점 테이블
    $sql = "CREATE TABLE IF NOT EXISTS stores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        store_name VARCHAR(100) NOT NULL,
        store_code VARCHAR(20) NOT NULL UNIQUE,
        owner_name VARCHAR(100),
        contact_person VARCHAR(100),
        email VARCHAR(100),
        phone VARCHAR(20),
        address TEXT,
        city VARCHAR(50),
        state VARCHAR(50),
        postal_code VARCHAR(20),
        country VARCHAR(50) DEFAULT 'Nepal',
        registration_date DATE,
        status ENUM('active', 'inactive', 'suspended', 'terminated') DEFAULT 'active',
        commission_rate DECIMAL(5,2) DEFAULT 5.00,
        tax_id VARCHAR(50),
        bank_account VARCHAR(50),
        bank_name VARCHAR(100),
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";
    
    if ($conn->query($sql) === TRUE) {
        logResult("판매점(stores) 테이블 생성 성공");
    } else {
        throw new Exception("판매점 테이블 생성 실패: " . $conn->error);
    }
    
    // 복권 유형 테이블
    $sql = "CREATE TABLE IF NOT EXISTS lottery_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lottery_name VARCHAR(100) NOT NULL,
        lottery_code VARCHAR(20) NOT NULL UNIQUE,
        description TEXT,
        ticket_price DECIMAL(10,2) NOT NULL,
        draw_schedule VARCHAR(100),
        is_active BOOLEAN DEFAULT TRUE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";
    
    if ($conn->query($sql) === TRUE) {
        logResult("복권 유형(lottery_types) 테이블 생성 성공");
    } else {
        throw new Exception("복권 유형 테이블 생성 실패: " . $conn->error);
    }
    
    // 판매 거래 테이블
    $sql = "CREATE TABLE IF NOT EXISTS sales_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        transaction_code VARCHAR(50) NOT NULL UNIQUE,
        store_id INT NOT NULL,
        lottery_type_id INT NOT NULL,
        ticket_quantity INT NOT NULL,
        total_amount DECIMAL(15,2) NOT NULL,
        commission_amount DECIMAL(15,2) NOT NULL,
        transaction_date DATETIME NOT NULL,
        status ENUM('pending', 'completed', 'cancelled', 'refunded') NOT NULL DEFAULT 'pending',
        payment_method VARCHAR(30) NOT NULL,
        customer_id INT,
        operator_id INT NOT NULL,
        terminal_id VARCHAR(50),
        batch_id VARCHAR(50),
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (store_id) REFERENCES stores(id),
        FOREIGN KEY (lottery_type_id) REFERENCES lottery_types(id),
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
    ) ENGINE=InnoDB";
    
    if ($conn->query($sql) === TRUE) {
        logResult("판매 거래(sales_transactions) 테이블 생성 성공");
    } else {
        throw new Exception("판매 거래 테이블 생성 실패: " . $conn->error);
    }
    
    // 재무 거래 테이블
    $sql = "CREATE TABLE IF NOT EXISTS financial_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        transaction_code VARCHAR(50) NOT NULL UNIQUE,
        transaction_type ENUM('income', 'expense', 'transfer', 'adjustment') NOT NULL,
        amount DECIMAL(15,2) NOT NULL,
        currency VARCHAR(10) DEFAULT 'NPR',
        transaction_date DATETIME NOT NULL,
        description TEXT,
        category_id INT,
        reference_type VARCHAR(50),
        reference_id VARCHAR(50),
        payment_method ENUM('cash', 'bank_transfer', 'check', 'credit_card', 'debit_card', 'mobile_payment', 'other') NOT NULL,
        payment_details TEXT,
        status ENUM('pending', 'completed', 'failed', 'cancelled', 'reconciled') DEFAULT 'pending',
        created_by INT NOT NULL,
        approved_by INT,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES financial_categories(id)
    ) ENGINE=InnoDB";
    
    if ($conn->query($sql) === TRUE) {
        logResult("재무 거래(financial_transactions) 테이블 생성 성공");
    } else {
        throw new Exception("재무 거래 테이블 생성 실패: " . $conn->error);
    }
    
    // 기금 테이블
    $sql = "CREATE TABLE IF NOT EXISTS funds (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fund_name VARCHAR(100) NOT NULL,
        fund_code VARCHAR(50) NOT NULL UNIQUE,
        description TEXT,
        fund_type ENUM('prize', 'charity', 'development', 'operational', 'reserve', 'other') NOT NULL,
        total_allocation DECIMAL(15,2) NOT NULL DEFAULT 0,
        current_balance DECIMAL(15,2) NOT NULL DEFAULT 0,
        allocation_percentage DECIMAL(5,2),
        status ENUM('active', 'inactive', 'depleted') DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";
    
    if ($conn->query($sql) === TRUE) {
        logResult("기금(funds) 테이블 생성 성공");
    } else {
        throw new Exception("기금 테이블 생성 실패: " . $conn->error);
    }
    
    // 기금 거래 테이블
    $sql = "CREATE TABLE IF NOT EXISTS fund_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fund_id INT NOT NULL,
        transaction_type ENUM('allocation', 'withdrawal', 'transfer', 'adjustment') NOT NULL,
        amount DECIMAL(15,2) NOT NULL,
        transaction_date DATETIME NOT NULL,
        description TEXT,
        reference_type VARCHAR(50),
        reference_id VARCHAR(50),
        approved_by INT,
        status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (fund_id) REFERENCES funds(id) ON DELETE CASCADE
    ) ENGINE=InnoDB";
    
    if ($conn->query($sql) === TRUE) {
        logResult("기금 거래(fund_transactions) 테이블 생성 성공");
    } else {
        throw new Exception("기금 거래 테이블 생성 실패: " . $conn->error);
    }
    
    // 예산 기간 테이블
    $sql = "CREATE TABLE IF NOT EXISTS budget_periods (
        id INT AUTO_INCREMENT PRIMARY KEY,
        period_name VARCHAR(100) NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        status ENUM('planning', 'active', 'closed') DEFAULT 'planning',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY (start_date, end_date)
    ) ENGINE=InnoDB";
    
    if ($conn->query($sql) === TRUE) {
        logResult("예산 기간(budget_periods) 테이블 생성 성공");
    } else {
        throw new Exception("예산 기간 테이블 생성 실패: " . $conn->error);
    }
    
    // 예산 할당 테이블
    $sql = "CREATE TABLE IF NOT EXISTS budget_allocations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        period_id INT NOT NULL,
        category_id INT NOT NULL,
        allocated_amount DECIMAL(15,2) NOT NULL,
        utilized_amount DECIMAL(15,2) DEFAULT 0,
        remaining_amount DECIMAL(15,2) GENERATED ALWAYS AS (allocated_amount - utilized_amount) STORED,
        utilization_percentage DECIMAL(5,2) GENERATED ALWAYS AS (IF(allocated_amount > 0, (utilized_amount / allocated_amount) * 100, 0)) STORED,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (period_id) REFERENCES budget_periods(id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES financial_categories(id) ON DELETE CASCADE,
        UNIQUE KEY (period_id, category_id)
    ) ENGINE=InnoDB";
    
    if ($conn->query($sql) === TRUE) {
        logResult("예산 할당(budget_allocations) 테이블 생성 성공");
    } else {
        throw new Exception("예산 할당 테이블 생성 실패: " . $conn->error);
    }
    
    // 정산 테이블
    $sql = "CREATE TABLE IF NOT EXISTS settlements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        settlement_code VARCHAR(50) NOT NULL UNIQUE,
        settlement_type ENUM('store', 'vendor', 'employee', 'tax', 'other') NOT NULL,
        entity_id INT NOT NULL,
        entity_type VARCHAR(50) NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        total_amount DECIMAL(15,2) NOT NULL,
        commission_amount DECIMAL(15,2) DEFAULT 0,
        tax_amount DECIMAL(15,2) DEFAULT 0,
        net_amount DECIMAL(15,2) NOT NULL,
        status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
        settlement_date DATETIME,
        payment_method ENUM('cash', 'bank_transfer', 'check', 'credit', 'adjustment') NOT NULL,
        payment_reference VARCHAR(100),
        created_by INT NOT NULL,
        approved_by INT,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";
    
    if ($conn->query($sql) === TRUE) {
        logResult("정산(settlements) 테이블 생성 성공");
    } else {
        throw new Exception("정산 테이블 생성 실패: " . $conn->error);
    }
    
    // 정산 항목 테이블
    $sql = "CREATE TABLE IF NOT EXISTS settlement_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        settlement_id INT NOT NULL,
        item_type VARCHAR(50) NOT NULL,
        reference_id VARCHAR(50),
        description TEXT,
        amount DECIMAL(15,2) NOT NULL,
        quantity INT DEFAULT 1,
        total_amount DECIMAL(15,2) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (settlement_id) REFERENCES settlements(id) ON DELETE CASCADE
    ) ENGINE=InnoDB";
    
    if ($conn->query($sql) === TRUE) {
        logResult("정산 항목(settlement_items) 테이블 생성 성공");
    } else {
        throw new Exception("정산 항목 테이블 생성 실패: " . $conn->error);
    }
    
    // 초기 관리자 계정 생성
    echo "<h3>초기 관리자 계정 생성 중...</h3>";
    
    // 관리자 계정이 이미 존재하는지 확인
    $checkSql = "SELECT COUNT(*) as count FROM users WHERE username = 'admin'";
    $result = $conn->query($checkSql);
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        // 관리자 계정 생성
        $hashedPassword = password_hash('admin123', PASSWORD_BCRYPT);
        $sql = "INSERT INTO users (username, email, password, role, first_name, last_name, status) 
                VALUES ('admin', 'admin@example.com', '$hashedPassword', 'admin', '관리자', '시스템', 'active')";
        
        if ($conn->query($sql) === TRUE) {
            logResult("초기 관리자 계정 생성 성공 (사용자명: admin, 비밀번호: admin123)");
        } else {
            throw new Exception("초기 관리자 계정 생성 실패: " . $conn->error);
        }
    } else {
        logResult("관리자 계정이 이미 존재합니다. 새 계정이 생성되지 않았습니다.");
    }
    
    // 기본 재무 카테고리 생성
    echo "<h3>기본 재무 카테고리 생성 중...</h3>";
    
    // 카테고리 데이터 배열
    $categories = [
        ['수입', 'income', '총 수입 카테고리'],
        ['판매 수입', 'income', '복권 판매에서 발생한 수입', 1],
        ['당첨금 미청구', 'income', '청구되지 않은 당첨금에서 발생한 수입', 1],
        ['기타 수입', 'income', '기타 수입원에서 발생한 수입', 1],
        ['투자 수익', 'income', '투자에서 발생한 수익', 1],
        ['지출', 'expense', '총 지출 카테고리'],
        ['운영 비용', 'expense', '사업 운영과 관련된 비용', 6],
        ['당첨금 지급', 'expense', '당첨자에게 지급된 상금', 6],
        ['판매점 수수료', 'expense', '판매점에 지급하는 수수료', 6],
        ['인건비', 'expense', '직원 급여 및 복리후생', 6],
        ['마케팅 비용', 'expense', '광고 및 마케팅 활동 비용', 6],
        ['세금', 'expense', '납부해야 하는 세금', 6],
        ['설비 및 장비', 'expense', '설비 및 장비 구입 및 유지보수 비용', 6],
        ['기금 할당', 'both', '다양한 기금에 대한 할당'],
        ['당첨금 기금', 'both', '당첨금 지급을 위한 기금 할당', 14],
        ['자선 기금', 'both', '자선 활동을 위한 기금 할당', 14],
        ['개발 기금', 'both', '개발 프로젝트를 위한 기금 할당', 14],
        ['운영 기금', 'both', '일상 운영을 위한 기금 할당', 14],
        ['예비 기금', 'both', '비상 상황을 위한 예비 기금', 14]
    ];
    
    // 카테고리 생성
    foreach ($categories as $index => $category) {
        $name = $category[0];
        $type = $category[1];
        $description = $category[2];
        $parentId = isset($category[3]) ? $category[3] : 'NULL';
        
        // 카테고리가 이미 존재하는지 확인
        $checkSql = "SELECT COUNT(*) as count FROM financial_categories WHERE category_name = '$name'";
        $result = $conn->query($checkSql);
        $row = $result->fetch_assoc();
        
        if ($row['count'] == 0) {
            // 카테고리 생성
            $sql = "INSERT INTO financial_categories (category_name, category_type, description, parent_id) 
                    VALUES ('$name', '$type', '$description', $parentId)";
            
            if ($conn->query($sql) === TRUE) {
                logResult("재무 카테고리 '$name' 생성 성공");
            } else {
                throw new Exception("재무 카테고리 '$name' 생성 실패: " . $conn->error);
            }
        } else {
            logResult("재무 카테고리 '$name'이(가) 이미 존재합니다.");
        }
    }
    
    // 기본 기금 생성
    echo "<h3>기본 기금 생성 중...</h3>";
    
    // 기금 데이터 배열
    $funds = [
        ['당첨금 주요 기금', 'PRIZE-MAIN', '당첨금 지급을 위한 주요 기금', 'prize', 0, 0, 50],
        ['자선 기부 기금', 'CHARITY-MAIN', '자선 활동 및 기부를 위한 기금', 'charity', 0, 0, 10],
        ['개발 프로젝트 기금', 'DEV-MAIN', '시스템 및 인프라 개발을 위한 기금', 'development', 0, 0, 15],
        ['일상 운영 기금', 'OPS-MAIN', '일상 운영 비용을 위한 기금', 'operational', 0, 0, 20],
        ['비상 예비 기금', 'RESERVE-MAIN', '비상 상황을 위한 예비 기금', 'reserve', 0, 0, 5]
    ];
    
    // 기금 생성
    foreach ($funds as $fund) {
        $name = $fund[0];
        $code = $fund[1];
        $description = $fund[2];
        $type = $fund[3];
        $totalAllocation = $fund[4];
        $currentBalance = $fund[5];
        $allocationPercentage = $fund[6];
        
        // 기금이 이미 존재하는지 확인
        $checkSql = "SELECT COUNT(*) as count FROM funds WHERE fund_code = '$code'";
        $result = $conn->query($checkSql);
        $row = $result->fetch_assoc();
        
        if ($row['count'] == 0) {
            // 기금 생성
            $sql = "INSERT INTO funds (fund_name, fund_code, description, fund_type, total_allocation, current_balance, allocation_percentage) 
                    VALUES ('$name', '$code', '$description', '$type', $totalAllocation, $currentBalance, $allocationPercentage)";
            
            if ($conn->query($sql) === TRUE) {
                logResult("기금 '$name' 생성 성공");
            } else {
                throw new Exception("기금 '$name' 생성 실패: " . $conn->error);
            }
        } else {
            logResult("기금 '$name'이(가) 이미 존재합니다.");
        }
    }
    
    // 현재 예산 기간 생성
    echo "<h3>현재 예산 기간 생성 중...</h3>";
    
    // 현재 연도와 분기 계산
    $currentYear = date('Y');
    $currentQuarter = ceil(date('n') / 3);
    $quarterStartMonth = (($currentQuarter - 1) * 3) + 1;
    $quarterEndMonth = $quarterStartMonth + 2;
    
    $startDate = date('Y-m-d', strtotime("$currentYear-$quarterStartMonth-01"));
    $endDate = date('Y-m-t', strtotime("$currentYear-$quarterEndMonth-01"));
    
    $periodName = "$currentYear년 $currentQuarter분기 예산";
    
    // 예산 기간이 이미 존재하는지 확인
    $checkSql = "SELECT COUNT(*) as count FROM budget_periods WHERE period_name = '$periodName'";
    $result = $conn->query($checkSql);
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        // 예산 기간 생성
        $sql = "INSERT INTO budget_periods (period_name, start_date, end_date, status) 
                VALUES ('$periodName', '$startDate', '$endDate', 'active')";
        
        if ($conn->query($sql) === TRUE) {
            logResult("예산 기간 '$periodName' 생성 성공");
            
            // 방금 생성한 예산 기간의 ID 가져오기
            $periodId = $conn->insert_id;
            
            // 예산 할당 생성
            echo "<h4>예산 할당 생성 중...</h4>";
            
            // 지출 카테고리 가져오기
            $sql = "SELECT id, category_name FROM financial_categories 
                    WHERE category_type IN ('expense', 'both') AND parent_id IS NOT NULL";
            $result = $conn->query($sql);
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $categoryId = $row['id'];
                    $categoryName = $row['category_name'];
                    
                    // 랜덤 할당 금액 (10000 ~ 100000)
                    $allocatedAmount = rand(10000, 100000);
                    
                    // 예산 할당 생성
                    $sql = "INSERT INTO budget_allocations (period_id, category_id, allocated_amount) 
                            VALUES ($periodId, $categoryId, $allocatedAmount)";
                    
                    if ($conn->query($sql) === TRUE) {
                        logResult("카테고리 '$categoryName'에 대한 예산 할당 생성 성공");
                    } else {
                        throw new Exception("카테고리 '$categoryName'에 대한 예산 할당 생성 실패: " . $conn->error);
                    }
                }
            } else {
                logResult("지출 카테고리가, 없어 예산 할당이 생성되지 않았습니다.", false);
            }
        } else {
            throw new Exception("예산 기간 '$periodName' 생성 실패: " . $conn->error);
        }
    } else {
        logResult("예산 기간 '$periodName'이(가) 이미 존재합니다.");
    }
    
    // 모든 작업 완료
    echo "<h3>데이터베이스 설정 완료</h3>";
    logResult("모든 테이블이 성공적으로 생성되었습니다.");
    
    // 연결 종료
    $conn->close();
    logResult("MySQL 연결 종료");
    
} catch (Exception $e) {
    logResult($e->getMessage(), false);
    
    // 연결 종료
    if (isset($conn)) {
        $conn->close();
        logResult("MySQL 연결 종료");
    }
}

echo "</div>";
echo "<br><br>";
echo "<p><strong>참고:</strong> 이 스크립트는 데이터베이스와 필요한 테이블을 생성하고, 초기 데이터를 설정합니다.</p>";
echo "<p>스크립트 실행이 완료되었습니다. 이제 로또 서버 시스템을 사용할 준비가 되었습니다.</p>";
?>
