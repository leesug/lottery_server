<?php
// 오류 표시 설정
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 데이터베이스 연결
$db = getDbConnection();

// 간단한 HTML 출력
echo "<!DOCTYPE html>";
echo "<html><head><title>디버깅 페이지</title></head><body>";
echo "<h1>유효성 검증 설정 디버깅</h1>";

// 테이블 생성 코드 테스트
try {
    echo "<p>테스트 시작...</p>";
    
    // 테이블 존재 여부 확인
    $checkTableQuery = "SHOW TABLES LIKE 'lottery_validation_settings'";
    $tableCheckStmt = $db->prepare($checkTableQuery);
    $tableCheckStmt->execute();
    $tableExists = ($tableCheckStmt->rowCount() > 0);
    
    echo "<p>테이블 존재 여부: " . ($tableExists ? "있음" : "없음") . "</p>";
    
    if (!$tableExists) {
        echo "<p>테이블 생성 시도 중...</p>";
        
        // 테이블이 없으면 생성
        $createTableSQL = "CREATE TABLE IF NOT EXISTS lottery_validation_settings (
            id INT PRIMARY KEY,
            enable_validation TINYINT(1) DEFAULT 1,
            validation_method VARCHAR(20) DEFAULT 'internal',
            api_endpoint VARCHAR(255) DEFAULT NULL,
            api_key VARCHAR(255) DEFAULT NULL,
            verify_check_digit TINYINT(1) DEFAULT 1,
            verify_sequence TINYINT(1) DEFAULT 1,
            verify_batch TINYINT(1) DEFAULT 1,
            verify_issue TINYINT(1) DEFAULT 1,
            validate_expiry TINYINT(1) DEFAULT 1,
            max_failures INT DEFAULT 3,
            block_duration INT DEFAULT 30,
            log_validations TINYINT(1) DEFAULT 1,
            alert_threshold INT DEFAULT 5,
            notify_admin TINYINT(1) DEFAULT 1,
            ip_restriction TINYINT(1) DEFAULT 0,
            allowed_ips TEXT DEFAULT NULL,
            time_restriction TINYINT(1) DEFAULT 0,
            allowed_time_start TIME DEFAULT '09:00:00',
            allowed_time_end TIME DEFAULT '18:00:00',
            created_by INT,
            updated_by INT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $result = $db->exec($createTableSQL);
        echo "<p>테이블 생성 결과: " . ($result !== false ? "성공" : "실패") . "</p>";
        
        if ($result === false) {
            echo "<p>오류 정보: " . json_encode($db->errorInfo()) . "</p>";
        } else {
            echo "<p>기본 데이터 삽입 중...</p>";
            
            // 기본 설정 데이터 삽입
            $insertDefaultSQL = "INSERT INTO lottery_validation_settings (
                id, enable_validation, validation_method, verify_check_digit, verify_sequence, 
                verify_batch, verify_issue, validate_expiry, max_failures, block_duration, 
                log_validations, alert_threshold, notify_admin, created_by
            ) VALUES (
                1, 1, 'internal', 1, 1, 1, 1, 1, 3, 30, 1, 5, 1, 1
            )";
            
            $result = $db->exec($insertDefaultSQL);
            echo "<p>기본 데이터 삽입 결과: " . ($result !== false ? "성공" : "실패") . "</p>";
            
            if ($result === false) {
                echo "<p>오류 정보: " . json_encode($db->errorInfo()) . "</p>";
            }
        }
    }
    
    // 유효성 검증 실패 테이블 존재 여부 확인
    $checkFailuresTableQuery = "SHOW TABLES LIKE 'lottery_validation_failures'";
    $failuresTableCheckStmt = $db->prepare($checkFailuresTableQuery);
    $failuresTableCheckStmt->execute();
    $failuresTableExists = ($failuresTableCheckStmt->rowCount() > 0);
    
    echo "<p>실패 테이블 존재 여부: " . ($failuresTableExists ? "있음" : "없음") . "</p>";
    
    if (!$failuresTableExists) {
        echo "<p>실패 테이블 생성 시도 중...</p>";
        
        // 테이블이 없으면 생성
        $createFailuresTableSQL = "CREATE TABLE IF NOT EXISTS lottery_validation_failures (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(50) NOT NULL,
            barcode VARCHAR(100) NOT NULL,
            failure_reason VARCHAR(255),
            attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ip_address (ip_address),
            INDEX idx_attempt_time (attempt_time)
        )";
        
        $result = $db->exec($createFailuresTableSQL);
        echo "<p>실패 테이블 생성 결과: " . ($result !== false ? "성공" : "실패") . "</p>";
        
        if ($result === false) {
            echo "<p>오류 정보: " . json_encode($db->errorInfo()) . "</p>";
        }
    }
    
    echo "<p>테스트 완료</p>";
    
} catch (Exception $e) {
    echo "<p>오류 발생: " . $e->getMessage() . "</p>";
    echo "<p>스택 트레이스: " . $e->getTraceAsString() . "</p>";
}

echo "</body></html>";
