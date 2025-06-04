# 프로젝트 일관성 문서

이 문서는 당첨금 관리 모듈의 일관성을 유지하기 위한 지침과 정보를 제공합니다.

## 파일 구조

### 서버 폴더 구조
- `/server/dashboard/prize/`: 당첨금 관리 관련 페이지
  - `settings.php`: 당첨금 설정 페이지
  - `payment.php`: 당첨금 지급 페이지
  - `statistics.php`: 당첨금 통계 페이지
  - `carryover.php`: 당첨금 이월 페이지
  - `dashboard.php`: 당첨금 관리 대시보드

### 템플릿 파일
- `/server/templates/dashboard_header.php`: 대시보드 공통 헤더
- `/server/templates/dashboard_footer.php`: 대시보드 공통 푸터
- `/server/templates/page_header.php`: 페이지 타이틀 및 브레드크럼 표시 템플릿

### 공통 코드 구조
모든 페이지는 다음과 같은 구조를 따릅니다:
```php
// 필요한 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 페이지 제목 설정
$pageTitle = "페이지 제목";
$currentSection = "prize"; // 현재 섹션(대메뉴)
$currentPage = "파일명.php"; // 현재 페이지 파일명

// 데이터베이스 연결
$conn = getDBConnection();

// 비즈니스 로직 처리
// ...

// 템플릿 헤더 포함
include_once TEMPLATES_PATH . '/dashboard_header.php';
include_once TEMPLATES_PATH . '/page_header.php';

// 페이지 콘텐츠
// ...

// 템플릿 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';
```

### 데이터베이스 테이블 구조

#### winnings 테이블 (당첨금 정보)
```sql
CREATE TABLE IF NOT EXISTS `winnings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_id` INT UNSIGNED NOT NULL COMMENT '티켓 ID',
  `draw_id` INT UNSIGNED NOT NULL COMMENT '추첨 ID',
  `prize_tier` INT NOT NULL COMMENT '당첨 등수',
  `prize_amount` DECIMAL(18,2) NOT NULL COMMENT '당첨 금액',
  `status` ENUM('pending', 'claimed', 'paid') NOT NULL DEFAULT 'pending' COMMENT '상태 (대기중, 확인됨, 지급완료)',
  `claimed_at` DATETIME NULL COMMENT '확인 일시',
  `claimed_by` INT UNSIGNED NULL COMMENT '확인 처리자',
  `customer_info` TEXT NULL COMMENT '고객 정보 (JSON)',
  `paid_at` DATETIME NULL COMMENT '지급 일시',
  `paid_by` INT UNSIGNED NULL COMMENT '지급 처리자',
  `payment_method` VARCHAR(50) NULL COMMENT '지급 방법',
  `payment_reference` VARCHAR(100) NULL COMMENT '지급 참조번호',
  `notes` TEXT NULL COMMENT '메모',
  `created_at` DATETIME NOT NULL COMMENT '생성 일시',
  `updated_at` DATETIME NULL COMMENT '수정 일시',
  PRIMARY KEY (`id`),
  INDEX `IDX_WINNINGS_TICKET` (`ticket_id`),
  INDEX `IDX_WINNINGS_DRAW` (`draw_id`),
  INDEX `IDX_WINNINGS_STATUS` (`status`)
);
```

#### payment_history 테이블 (지급 이력)
```sql
CREATE TABLE IF NOT EXISTS `payment_history` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `winning_id` INT UNSIGNED NOT NULL COMMENT '당첨금 ID',
  `ticket_id` INT UNSIGNED NOT NULL COMMENT '티켓 ID',
  `draw_id` INT UNSIGNED NOT NULL COMMENT '추첨 ID',
  `amount` DECIMAL(18,2) NOT NULL COMMENT '지급 금액',
  `payment_method` VARCHAR(50) NOT NULL COMMENT '지급 방법',
  `payment_reference` VARCHAR(100) NULL COMMENT '지급 참조번호',
  `processed_by` INT UNSIGNED NOT NULL COMMENT '처리자',
  `notes` TEXT NULL COMMENT '메모',
  `created_at` DATETIME NOT NULL COMMENT '생성 일시',
  PRIMARY KEY (`id`),
  INDEX `IDX_PAYMENT_WINNING` (`winning_id`),
  INDEX `IDX_PAYMENT_TICKET` (`ticket_id`),
  INDEX `IDX_PAYMENT_DRAW` (`draw_id`)
);
```

#### prize_carryovers 테이블 (당첨금 이월 정보)
```sql
CREATE TABLE IF NOT EXISTS `prize_carryovers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `source_draw_id` INT UNSIGNED NOT NULL COMMENT '원본 추첨 ID',
  `target_draw_id` INT UNSIGNED NOT NULL COMMENT '대상 추첨 ID',
  `carryover_amount` DECIMAL(18,2) NOT NULL COMMENT '이월 금액',
  `carryover_tier` INT NOT NULL DEFAULT 1 COMMENT '이월 대상 등수',
  `status` ENUM('active', 'cancelled', 'applied') NOT NULL DEFAULT 'active' COMMENT '상태 (활성, 취소됨, 적용됨)',
  `notes` TEXT NULL COMMENT '메모',
  `created_by` INT UNSIGNED NOT NULL COMMENT '생성자',
  `created_at` DATETIME NOT NULL COMMENT '생성 일시',
  `updated_by` INT UNSIGNED NULL COMMENT '수정자',
  `updated_at` DATETIME NULL COMMENT '수정 일시',
  PRIMARY KEY (`id`),
  INDEX `IDX_CARRYOVER_SOURCE` (`source_draw_id`),
  INDEX `IDX_CARRYOVER_TARGET` (`target_draw_id`),
  INDEX `IDX_CARRYOVER_STATUS` (`status`)
);
```

#### lottery_products 테이블 업데이트
- 기존 lottery_products 테이블에 prize_structure 컬럼 추가
- 당첨금 구조 정보를 JSON 형태로 저장

## 주요 참조 테이블 

### 당첨금 페이지에서 참조하는 테이블
- `lottery_products`: 복권 상품 정보
- `draws`: 추첨 정보
- `tickets`: 티켓 정보 
- `winnings`: 당첨 정보
- `payment_history`: 지급 이력
- `prize_carryovers`: 이월 정보
- `users`: 사용자(관리자) 정보
- `customers`: 고객 정보
- `system_settings`: 시스템 설정 정보

## 코드 일관성 가이드

### 테이블 명명 규칙
- 테이블 명은 snake_case 사용 (`prize_carryovers`, `payment_history`)
- 복수형으로 명명 (`winnings`, `settings`, 등)

### 컬럼 명명 규칙
- 컬럼 명은 snake_case 사용 (`prize_tier`, `carryover_amount`)
- 외래 키는 `[테이블명 단수형]_id` 형식 사용 (`ticket_id`, `draw_id`)

### 쿼리 작성 규칙
- 테이블 별칭은 짧게 사용 (w, t, d, p 등)
- JOIN 시 LEFT JOIN 사용하여 누락 데이터 방지
- 날짜 필터링 시 DATE() 함수 사용
- GROUP BY 절에 모든 비집계 열 포함

### 페이지 레이아웃 규칙
- 모든 페이지는 dashboard_header.php와 dashboard_footer.php를 포함
- 페이지 타이틀 및 브레드크럼은 page_header.php 템플릿 사용
- 페이지 콘텐츠는 <section class="content">로 감싸기
- 알림 메시지는 page_header.php 템플릿에서 처리

### 보안 고려사항
- SQL 인젝션 방지를 위해 항상 PDO 파라미터 바인딩 사용
- CSRF 토큰을 사용하여 폼 제출 보호
- 민감한 정보는 암호화하여 저장

이 문서는 프로젝트 진행 중 계속 업데이트됩니다.
