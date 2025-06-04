# 바코드 관리 기능 문제 해결

## 문제 상황
- `barcode-management.php` 파일에서 두 가지 오류 발생
  1. `Undefined property: PDOStatement::$num_rows in line 230`
  2. `Uncaught PDOException: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'product_name' in 'field list' in line 238`

## 원인 분석
1. PDO와 mysqli 방식이 혼합되어 사용되고 있었음
   - mysqli의 `num_rows` 속성을 PDO 객체에서 사용하려 했던 것이 첫 번째 문제
   - PDO에서는 `rowCount()` 메서드를 사용해야 함

2. 'product_name' 컬럼이 테이블에 존재하지 않음
   - lottery_products 테이블은 'product_name'이 아닌 'name' 컬럼을 사용함
   - 프로젝트 일관성 파일에서 테이블 스키마 확인 결과 확인됨

## 해결 방법

### 1. PDO 방식으로 코드 통일
- 모든 데이터베이스 연산을 PDO 방식으로 변경
  - `num_rows` → `rowCount()`
  - `fetch_assoc()` → `fetch(PDO::FETCH_ASSOC)`
  - `bind_param()` → `bindParam()`
  - `$db->error` → `$stmt->errorInfo()`
  - `$db->insert_id` → `$db->lastInsertId()`
  - `$result->free()` 제거 (PDO에서는 필요 없음)

### 2. 컬럼명 수정
- 'product_name' 참조를 모두 'name'으로 변경
- 쿼리와 표시 부분 모두 수정
- JOIN 쿼리에서 `p.name AS product_name` 형태로 별칭 사용하여 호환성 유지

### 3. 테이블 자동 생성 기능 추가
- `lottery_barcode_settings` 테이블이 없는 경우 자동으로 생성
- `lottery_barcode_generation_tasks` 테이블도 필요시 자동 생성 
- 기본 설정 데이터 추가 기능 구현
- 테이블 존재 여부 확인 로직 구현

### 4. 예외 처리 강화
- 모든 데이터베이스 작업에 try-catch 블록 추가
- 에러 로깅 기능 강화
- 오류 메시지를 상세하게 표시하되 보안에 민감한 정보는 제외

## 테이블 스키마

### lottery_barcode_settings 테이블
```sql
CREATE TABLE IF NOT EXISTS lottery_barcode_settings (
    id INT PRIMARY KEY,
    barcode_type VARCHAR(50) DEFAULT 'qrcode_v2',
    prefix VARCHAR(10) DEFAULT 'LT',
    length INT DEFAULT 12,
    check_digit TINYINT(1) DEFAULT 1,
    encryption TINYINT(1) DEFAULT 0,
    encryption_key VARCHAR(255) DEFAULT NULL,
    created_by INT,
    updated_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
)
```

### lottery_barcode_generation_tasks 테이블
```sql
CREATE TABLE IF NOT EXISTS lottery_barcode_generation_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lottery_product_id INT NOT NULL,
    issue_id INT NOT NULL,
    batch_id INT,
    quantity INT NOT NULL,
    start_number VARCHAR(50),
    processed INT DEFAULT 0,
    status ENUM('pending', 'in_progress', 'completed', 'failed', 'cancelled', 'error') DEFAULT 'pending',
    progress INT DEFAULT 0,
    attempts INT DEFAULT 1,
    error_message TEXT,
    sample_barcodes TEXT,
    created_by INT,
    updated_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME,
    completed_at DATETIME,
    INDEX idx_product (lottery_product_id),
    INDEX idx_issue (issue_id),
    INDEX idx_batch (batch_id),
    INDEX idx_status (status)
)
```

## 수정된 코드의 개선 사항
1. 일관된 PDO 스타일 사용으로 코드 가독성 향상
2. 테이블 자동 생성 기능으로 초기 설정 없이도 작동 가능
3. 보다 구체적인 오류 메시지 및 로깅 기능 향상
4. 테이블 스키마의 프로젝트 일관성 파일 반영
5. 바코드 관리 기능 유지보수성 개선

## 향후 개선 사항
1. 바코드 생성 작업 진행 상황 실시간 모니터링 기능
2. 대량 바코드 생성 시 성능 최적화
3. 바코드 생성 및 검증 기능 확장
4. 복권 상품 및 발행 코드 자동 연계 기능