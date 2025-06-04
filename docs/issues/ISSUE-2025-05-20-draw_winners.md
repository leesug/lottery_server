# 오류 수정: draw_winners 테이블 참조 문제

## 문제 개요
- **파일**: `C:\xampp\htdocs\server\dashboard\draw\history.php`
- **라인**: 118
- **오류 메시지**: `Fatal error: Uncaught PDOException: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'dw.draw_id' in 'on clause'`

## 원인 분석
- SQL 쿼리에서 `draw_winners` 테이블을 참조하고 있으나, 실제 데이터베이스에는 이 테이블이 존재하지 않음
- 데이터베이스 스키마를 확인한 결과, 해당 정보는 `winnings` 테이블에 저장되어 있음
- `winnings` 테이블과 `draw_winners` 테이블의 구조가 다름:
  - 필드명 차이: `prize_tier` → `prize_rank`

## 수정 내용
1. `getDrawHistory` 함수의 SQL 쿼리 수정:
   - `draw_winners dw` → `winnings w`
   - `dw.id` → `w.id`
   - `dw.draw_id` → `w.draw_id`

2. `getDrawStatistics` 함수의 `tier_query` 수정:
   - `draw_winners dw` → `winnings w`
   - `dw.prize_tier` → `w.prize_rank`
   - `dw.prize_amount` → `w.prize_amount`
   - `dw.draw_id` → `w.draw_id`
   - GROUP BY 절 수정: `dw.prize_tier` → `w.prize_rank`

3. `getDrawStatistics` 함수의 `total_query` 수정:
   - `draw_winners dw` → `winnings w`
   - `dw.id` → `w.id`
   - `dw.draw_id` → `w.draw_id`

## 후속 조치
- project_consistency.md 파일에 당첨 관련 테이블 구조 정보 추가
- bugfix.log 파일에 오류 수정 내역 기록
- project_plan.md 파일 업데이트
- 다른 페이지에서 `draw_winners` 테이블 참조 여부 확인 필요

## 수정 완료
- 2025-05-20 14:30
