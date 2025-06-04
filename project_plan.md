# KHUSHI LOTTERY 서버 개발 프로젝트 계획

## 프로젝트 개요
네팔 KHUSHI LOTTERY 시스템의 서버측 기능 개발

## 주요 개발 과제

### 1. 판매점 관리 기능 개선
#### 1.1 예치금 관리 시스템 ✅ [완료]
- [x] 예치금 구조 테이블 생성 (store_deposits)
  - 기기보증금: 280,000 루피
  - 판매보증금: 200,000 루피
  - 총 예치금: 480,000 루피
- [x] 예치금 입금/증액/감액 기능
- [x] 판매한도 계산 로직 구현
  - 기본: 판매보증금 × 1.05
  - 등급별 레버리지 적용 (S: 1.2, A: 1.1, B-D: 1.0)
- [x] 판매한도 모니터링 및 알림
  - 75%, 90%, 95%, 98% 단계별 알림
  - 100% 도달시 판매 차단

#### 1.2 대리점 코드 부여 시스템 ✅ [완료]
- [x] 대리점 등록시 고유 코드 자동 생성 (STORE + 8자리 숫자)
- [x] 코드 체계: STORE10000000 ~ STORE99999999 범위
- [x] 중복 확인 로직 구현 (store-add.php의 generateStoreCode 함수)

### 2. 판매관리 기능 구현
#### 2.1 판매현황 조회 ✅ [완료]
- [x] 회차별 판매현황
- [x] 일주일별 판매현황
- [x] 일별 판매현황
- [x] 대시보드 통계 구현

### 3. 용지관리 시스템 구현
#### 3.1 데이터베이스 설계 ✅ [완료]
- [x] paper_boxes (용지박스)
- [x] paper_rolls (용지롤)
- [x] paper_usage (용지사용현황)
- [x] paper_serial_tracking (일련번호추적)

#### 3.2 용지 등록 및 관리 ✅ [완료]
- [x] 용지박스 QR 코드 등록
- [x] 용지롤 QR 코드 등록
- [x] 용지 일련번호 관리 (10자리, 70mm 간격)
- [x] 용지 상태 관리 (현재사용중/대기중/사용완료/만료)

#### 3.3 용지번호 추정 시스템 ✅ [완료]
- [x] 티켓 길이별 용지 사용량 계산
  - Welcome: 80mm
  - 1게임: 95mm, 2게임: 98mm, 3게임: 101mm
  - 4게임: 105mm, 5게임: 108mm
- [x] 현재 용지번호 추정 알고리즘
- [x] 오차 허용 범위 검증 (±12)

#### 3.4 용지 교체 알림 ✅ [완료]
- [x] 사용량 기반 알림 (90%, 95%, 98%, 99%)
- [x] 용지 잔량 모니터링

### 4. 외부관련접속 (은행) 기능
#### 4.1 당첨확인 시스템 ✅ [완료]
- [x] 용지번호 기반 당첨 티켓 검증
- [x] 용지 뒷면 번호 예측 및 오차 검증
- [x] 위변조 방지 검증

## 데이터베이스 테이블 구조

### 예치금 관련 테이블
```sql
-- store_deposits (판매점 예치금)
CREATE TABLE store_deposits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    store_id INT NOT NULL,
    equipment_deposit DECIMAL(12,2) DEFAULT 280000,
    sales_deposit DECIMAL(12,2) DEFAULT 200000,
    total_deposit DECIMAL(12,2) DEFAULT 480000,
    sales_limit DECIMAL(12,2),
    used_limit DECIMAL(12,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- deposit_transactions (예치금 거래내역)
CREATE TABLE deposit_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    store_id INT NOT NULL,
    type ENUM('deposit', 'increase', 'decrease', 'refund'),
    amount DECIMAL(12,2),
    balance_after DECIMAL(12,2),
    reference_no VARCHAR(50),
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 용지관리 관련 테이블
```sql
-- paper_boxes (용지박스)
CREATE TABLE paper_boxes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    box_code VARCHAR(50) UNIQUE,
    qr_code VARCHAR(100) UNIQUE,
    status ENUM('registered', 'assigned', 'used'),
    store_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- paper_rolls (용지롤)
CREATE TABLE paper_rolls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    roll_code VARCHAR(50) UNIQUE,
    qr_code VARCHAR(100) UNIQUE,
    box_id INT,
    start_serial CHAR(10),
    end_serial CHAR(10),
    length_mm INT DEFAULT 63000,
    status ENUM('registered', 'active', 'used', 'expired'),
    store_id INT,
    activated_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- paper_usage (용지사용현황)
CREATE TABLE paper_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    store_id INT NOT NULL,
    roll_id INT NOT NULL,
    current_serial CHAR(10),
    estimated_serial CHAR(10),
    printed_length_mm INT DEFAULT 0,
    remaining_length_mm INT DEFAULT 63000,
    welcome_count INT DEFAULT 0,
    game1_count INT DEFAULT 0,
    game2_count INT DEFAULT 0,
    game3_count INT DEFAULT 0,
    game4_count INT DEFAULT 0,
    game5_count INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## 작업 우선순위
1. 예치금 관리 시스템 (가장 중요)
2. 용지관리 시스템 (핵심 보안 기능)
3. 판매현황 조회
4. 은행 당첨확인 시스템

## 작업 일정
- 1단계: 데이터베이스 테이블 생성 및 기본 구조 설정
- 2단계: 예치금 관리 기능 구현
- 3단계: 용지관리 시스템 구현
- 4단계: 판매현황 및 통계 기능 구현
- 5단계: 은행 연동 기능 구현

## 완료된 작업
- 2025-01-22: project_plan.md 생성 및 작업 계획 수립
- 2025-01-22: 예치금 관리 시스템 테이블 생성 완료
  - store_deposits (판매점 예치금)
  - deposit_transactions (예치금 거래내역)
  - sales_limit_alerts (판매한도 알림 설정)
  - store_grade_leverage (등급별 레버리지)
  - deposit_limit_history (예치금 한도 변경 이력)
- 2025-01-22: 예치금 관리 페이지 개발 완료
  - deposit-dashboard.php (예치금 관리 대시보드)
  - deposit-transaction.php (예치금 입금/증액/감액)
  - deposit-reset-limit.php (판매한도 리셋 API)
  - system/deposit-grade-settings.php (등급별 레버리지 설정)
- 2025-01-22: 용지관리 시스템 테이블 생성 완료
  - paper_boxes (용지박스)
  - paper_rolls (용지롤)
  - paper_usage (용지사용현황)
  - paper_serial_tracking (일련번호추적)
  - paper_alerts (용지알림)
  - paper_stock_history (재고이력)
  - paper_length_settings (길이설정)
- 2025-01-22: 용지관리 페이지 개발 완료
  - paper-dashboard.php (용지관리 대시보드)
  - paper-box-register.php (용지박스 등록)
  - paper-input.php (용지번호 입력 및 검증)
- 2025-01-22: 판매관리 기능 구현 완료
  - sales-status.php (판매현황 - 회차별/주별/일별)
- 2025-01-22: 은행 당첨확인 시스템 구현 완료
  - bank-verification.php (용지번호 기반 티켓 검증)
- 2025-01-22: 대리점 코드 자동 부여 시스템 구현 완료
  - store-add.php의 generateStoreCode 함수 구현 (STORE + 8자리 숫자)
- 2025-06-04: project_plan.md 업데이트 - 모든 주요 기능 완료 상태 반영

## 진행중인 작업
- 없음

## 대기중인 작업
- 추가 개선사항
  - 용지 활성화 기능 고도화
  - 판매점별 용지 현황 상세 조회
  - 용지 사용량 예측 알고리즘 개선
  - 대시보드 UI/UX 개선
  - 실시간 알림 시스템 강화

## 주의사항
- 모든 파일은 18KB를 초과하지 않도록 분할
- 로그는 C:\xampp\htdocs\server\logs 폴더에 저장
- 일관성 있는 코드 스타일 유지
- 보안 고려사항 준수 (SQL 인젝션 방지, CSRF 토큰 등)
- 대리점 코드는 STORE + 8자리 숫자 형식 (예: STORE12345678)
