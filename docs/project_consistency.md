# 프로젝트 일관성 문서

## 데이터베이스 스키마

### lotto_server 데이터베이스

#### 테이블 구조

##### draws
- id: 고유 식별자
- draw_code: 회차 코드
- product_id: 복권 상품 ID (lottery_products 테이블과 연결)
- draw_date: 추첨일
- status: 추첨 상태 (scheduled, in_progress, completed, cancelled, verified)
- 기타 추첨 관련 필드들

##### sales_transactions
- id: 고유 식별자
- transaction_code: 거래 코드
- store_id: 매장 ID
- lottery_type_id: 복권 유형 ID (draws.product_id와 연결)
- ticket_quantity: 티켓 수량
- total_amount: 총 판매액
- payment_method: 결제 방법 (온라인/오프라인으로 활용)
- transaction_date: 거래 일자
- 기타 거래 관련 필드들

##### funds
- id: 고유 식별자
- fund_name: 기금 이름
- fund_code: 기금 코드
- fund_type: 기금 유형 (prize, charity, development, operational, reserve, other)
- total_allocation: 총 할당액
- current_balance: 현재 잔액
- allocation_percentage: 할당 비율
- status: 상태 (active, inactive, depleted)

##### fund_transactions
- id: 고유 식별자
- fund_id: 기금 ID (funds 테이블 참조)
- transaction_type: 거래 유형 (allocation, withdrawal, transfer, adjustment)
- amount: 금액
- transaction_date: 거래 일자
- reference_type: 참조 유형 (draw 등)
- reference_id: 참조 ID (draws.id 등)
- status: 상태 (pending, completed, cancelled)

##### lottery_products
- id: 고유 식별자
- name: 상품명
- 기타 복권 상품 관련 필드들

## 파일 구조

### 서버 경로

- includes/currency.php: 통화 설정 파일
  - 통화 단위 정의 (INR - 인도 루피)
  - 통화 형식 함수 (formatCurrency) 제공

- includes/sales_functions.php: 판매 데이터 관련 함수
  - 회차별 판매 정보 조회 (getSalesDataByDraw)
  - 판매 상세 정보 조회 (getSalesDetailsByDrawId)
  - 판매 통계 정보 조회 (getSalesStatistics)
  - 판매 관리와 정부 감시 페이지에서 공통으로 사용

- dashboard/external-monitoring/government/sales.php: 판매액 현황 페이지
  - 회차별 판매액 데이터 표시
  - 그래프 및 상세 정보 제공
  - 수정: 테이블명과 컬럼명 참조 오류 수정 (sales -> sales_transactions)
  - 수정: 컬럼 매핑 수정 (draw_id -> lottery_type_id, sales_amount -> total_amount 등)
  - 수정: 통화 단위를 원(₩)에서 루피(₹)로 변경
  - 수정: 판매 관리와 동일한 함수 사용하여 데이터 일관성 유지

- dashboard/external-monitoring/government/funds.php: 기금액 현황 페이지
  - 회차별 기금액 데이터 표시
  - 기금 분야별 그래프 및 상세 정보 제공
  - 수정: 테이블명 참조 오류 수정 (lottery_funds -> fund_transactions, funds)
  - 수정: 컬럼 매핑 수정 (분야별 기금액 집계 로직 변경)
  - 수정: 통화 단위를 원(₩)에서 루피(₹)로 변경

- dashboard/sales/dashboard.php 및 dashboard-content.php: 판매 관리 대시보드
  - 판매 현황 및 통계 정보 표시
  - 수정: 실제 판매 데이터 연동 (기존 Mock 데이터 대신)
  - 수정: sales_functions.php를 통한 데이터 조회 로직 통합

## 데이터 관계

- draws.product_id <-> sales_transactions.lottery_type_id (복권 유형 연결)
- draws.id <-> draw_results.draw_id (추첨 결과 연결)
- 판매 채널은 sales_transactions.payment_method 필드로 구분 (online/offline)
- fund_transactions.reference_id <-> draws.id (기금 거래와 회차 연결, reference_type = 'draw'인 경우)
- fund_transactions.fund_id <-> funds.id (기금 거래와 기금 유형 연결)
