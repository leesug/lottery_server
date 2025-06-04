# 복권 인쇄 설정 관리 기능 문제 해결

## 문제 상황
- `printing-settings.php` 파일에서 PDOException 발생
- 오류 메시지: "SQLSTATE[42S02]: Base table or view not found: 1146 Table 'lotto_server.lottery_print_settings' doesn't exist"
- 오류 발생 위치: 파일의 201번 라인에서 `SELECT * FROM lottery_print_settings` 쿼리 실행 시점

## 해결 방법

### 1. 테이블 자동 생성 기능 추가
- `lottery_print_settings` 테이블 존재 여부를 확인하는 코드 추가
- 테이블이 없는 경우 자동으로 생성하는 로직 구현
- 기본 설정 값으로 초기 데이터 삽입

### 2. PDO 방식으로 코드 수정
- 모든 데이터베이스 작업을 PDO 스타일로 통일
- 예외 처리(`try-catch`) 추가로 안정성 향상
- 실패 시 상세한 오류 로깅 기능 구현

### 3. 관련 테이블 확인 및 생성
- `lottery_printers` 테이블 존재 여부도 확인하도록 기능 확장
- 샘플 프린터 데이터 자동 생성 기능 추가

### 4. 폴백 메커니즘 구현
- 데이터베이스 오류 발생 시에도 페이지가 작동할 수 있도록 기본값 제공
- 사용자에게 적절한 오류 메시지 표시

## 구현된 테이블 스키마

### lottery_print_settings 테이블
```sql
CREATE TABLE lottery_print_settings (
    id INT PRIMARY KEY,
    printer_type VARCHAR(50) DEFAULT 'thermal',
    paper_size VARCHAR(50) DEFAULT 'a4',
    dpi_setting VARCHAR(20) DEFAULT '300',
    color_mode VARCHAR(20) DEFAULT 'color',
    default_margin INT DEFAULT 5,
    enable_duplex TINYINT(1) DEFAULT 0,
    enable_cut_marks TINYINT(1) DEFAULT 0,
    enable_watermark TINYINT(1) DEFAULT 0,
    security_ink VARCHAR(50) DEFAULT 'none',
    uv_ink TINYINT(1) DEFAULT 0,
    quality_check TINYINT(1) DEFAULT 1,
    barcode_verification TINYINT(1) DEFAULT 1,
    error_logging TINYINT(1) DEFAULT 1,
    watermark_text VARCHAR(255) DEFAULT '',
    watermark_opacity INT DEFAULT 30,
    background_image VARCHAR(255) DEFAULT '',
    logo_image VARCHAR(255) DEFAULT '',
    updated_by INT,
    updated_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)
```

### lottery_printers 테이블
```sql
CREATE TABLE lottery_printers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    printer_name VARCHAR(100) NOT NULL,
    printer_model VARCHAR(100),
    location VARCHAR(200),
    ip_address VARCHAR(50),
    port VARCHAR(10),
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
)
```

## 테스트 결과
- 페이지 첫 로드 시 테이블 자동 생성 확인
- 설정 저장 및 수정 기능 정상 작동
- 이미지 업로드 기능 정상 작동
- 테스트 인쇄 기능 정상 작동
- 모든 예외 상황에서 적절한 오류 처리 확인

## 추후 개선 사항
- 프린터 자동 감지 및 추가 기능
- 인쇄 품질 테스트 후 결과 분석 기능
- 인쇄 대기열 관리 및 모니터링 기능
- 프린터별 설정 프로파일 저장 및 적용 기능