# 오류 수정: 데이터베이스 테이블 참조 문제 (두 번째 수정)

## 문제 개요
- **파일**: `C:\xampp\htdocs\server\dashboard\draw\history.php`
- **라인**: 118
- **오류 메시지**: `Fatal error: Uncaught PDOException: SQLSTATE[42S02]: Base table or view not found: 1146 Table 'lotto_server.winnings' doesn't exist`

## 원인 분석
- 이전 수정에서 'draw_winners' 테이블을 'winnings' 테이블로 변경했으나, 실제 데이터베이스에는 'winnings' 테이블이 존재하지 않았음
- 실제 데이터베이스에는 'draw_winners' 테이블이 이미 존재했으며, 구조도 달랐음:
  - 'draw_result_id' 필드명 (draw_id가 아님)
  - 'rank' 필드명 (prize_tier나 prize_rank가 아님)

## 수정 내용
1. `getDrawHistory` 함수의 SQL 쿼리 수정:
   - 테이블 변경: 'winnings' -> 'draw_winners'
   - 필드 변경: 'w.draw_id' -> 'dw.draw_result_id'

2. `getDrawStatistics` 함수의 `tier_query` 수정:
   - 테이블 변경: 'winnings' -> 'draw_winners'
   - 필드 변경: 'w.prize_rank' -> 'dw.rank'
   - 필드 변경: 'w.draw_id' -> 'dw.draw_result_id'
   - GROUP BY 절 수정: 'w.prize_rank' -> 'dw.rank'

3. `getDrawStatistics` 함수의 `total_query` 수정:
   - 테이블 변경: 'winnings' -> 'draw_winners'
   - 필드 변경: 'w.id' -> 'dw.id'
   - 필드 변경: 'w.draw_id' -> 'dw.draw_result_id'

## 학습 내용
- 테이블 구조 변경 전에 항상 실제 데이터베이스 구조 먼저 확인 필요
- 변경 사항을 적용하기 전에 모든 필드명과 참조 관계 철저히 확인 필요
- 예상 데이터 모델과 실제 구현이 다를 수 있으므로, 항상 실제 DB 구조 직접 확인 필요

## 후속 조치
- project_consistency.md 파일의 당첨 관련 테이블 정보 수정
- bugfix.log 파일에 오류 수정 내역 추가
- project_plan.md 파일 업데이트
- 다른 페이지에서 테이블 참조 오류가 있는지 검토 필요

## 수정 완료
- 2025-05-20 14:35
