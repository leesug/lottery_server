# 작업 분석

## 현재 작업: funds.php 에러 수정

### 요구사항 분석
- 오류 내용: `Table 'lotto_server.lottery_funds' doesn't exist` PDOException 발생
- 오류 위치: `C:\xampp\htdocs\server\dashboard\external-monitoring\government\funds.php` 139번 라인
- 해결 방향: 존재하지 않는 `lottery_funds` 테이블을 적절한 테이블로 대체

### 작업 우선순위
1. 데이터베이스 스키마 분석
2. 오류 발생 쿼리 확인 및 수정
3. 연관된 다른 쿼리 일관성 있게 수정
4. 테스트 및 문서화

### 작업 세부 내용
1. ✅ 데이터베이스 테이블 구조 확인
   - fund 관련 테이블 확인 (funds, fund_transactions, fund_transfers)
   - 테이블 구조와 컬럼 분석

2. ✅ 오류 수정
   - `lottery_funds` -> `fund_transactions`와 `funds` 테이블 조인으로 변경
   - 기금 분야별 금액 집계 로직 변경: fund_name 필드를 기준으로 필터링
   - `sales` -> `sales_transactions` 테이블명 변경
   - JOIN 조건 변경: reference_id와 reference_type 활용
   
3. ✅ 문서화
   - project_consistency.md 업데이트: funds 관련 테이블 구조 추가
   - project_plan.md 업데이트: funds.php 수정 사항 기록

### 추가 필요 작업
- 테스트를 통한 페이지 기능 확인
- 다른 기금 관련 페이지에서도 동일한 수정 필요 여부 확인
