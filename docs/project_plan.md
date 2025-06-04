# 프로젝트 계획

## 완료된 작업

- **sales.php 수정**: `draw_number` 컬럼 참조 오류 해결
  - 테이블명 변경: `sales` -> `sales_transactions`
  - 컬럼 매핑 수정: `draw_id` -> `lottery_type_id`, `sales_amount` -> `total_amount`
  - JOIN 조건 수정: `d.id = s.draw_id` -> `d.product_id = s.lottery_type_id`
  - 판매 채널 구분 필드 매핑: `sales_channel` -> `payment_method`

- **funds.php 수정**: `lottery_funds` 테이블 참조 오류 해결
  - 테이블명 변경: `lottery_funds` -> `fund_transactions` + `funds` (조인)
  - 기금 데이터 조회 쿼리 수정: 분야별 기금액 집계 로직 변경
  - 기금 정보 상세보기 쿼리 수정
  - 판매 정보 조회 부분 수정: `sales` -> `sales_transactions`

- **통화 단위 변경**: 원(₩)에서 루피(₹)로 통화 단위 변경
  - currency.php 파일 생성: 통화 설정 및 형식화 함수 제공
  - sales.php 및 funds.php의 금액 표시 방식 수정
  - 통화 형식 통일 (formatCurrency 함수 사용)

- **판매 관리와 정부 감시 페이지 연동**:
  - sales_functions.php 파일 생성: 판매 데이터 처리 공통 함수 구현
  - 정부 감시 페이지에서 판매 관리 데이터 활용
  - 판매 관리 대시보드에 실제 데이터 연동

## 현재 작업

- 통화 단위 변경 및 판매 데이터 연동 테스트

## 할 일

- 나머지 페이지(prizes.php, winners.php 등)의 통화 단위 변경
- 판매 관리와 정부 감시 페이지 간 데이터 일관성 추가 확인
- 대시보드 기능 추가 개발
