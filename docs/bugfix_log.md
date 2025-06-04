# 버그 수정 로그

## 2025-05-20 추첨 이력 페이지 오류 수정
- 파일: `C:\xampp\htdocs\server\dashboard\draw\history.php` 
- 문제: `htmlspecialchars()` 함수에 null 값이 전달되어 Deprecated 경고 발생 (538라인)
- 해결: null 병합 연산자(??)를 사용하여 null 값이 전달되지 않도록 수정
- 관련 이슈: 이중 헤더/푸터 포함 문제도 함께 해결
- 자세한 내용: [draw_history_bugfix.md](draw_history_bugfix.md)

## 2025-05-19 예산 할당 페이지 오류 수정
- 파일: `C:\xampp\htdocs\server\dashboard\finance\budget.php`
- 문제: 존재하지 않는 budget_period_id 컬럼 참조 오류
- 해결: 정확한 컬럼명 period_id로 수정
- 관련 이슈: 배열 키 접근 전 isset() 추가하여 undefined array key 경고 수정

## 2025-05-16 자금 잔액 페이지 오류 수정
- 파일: `C:\xampp\htdocs\server\dashboard\finance\balance.php`
- 문제: mysqli 스타일 코드 사용 및 존재하지 않는 컬럼 참조
- 해결: PDO 스타일로 코드 수정 및 누락된 함수 추가
- 관련 이슈: 존재하지 않는 last_transaction_date 컬럼 참조 제거

## 2025-05-15 매장 목록 및 장비 관리 페이지 오류 수정
- 파일: `C:\xampp\htdocs\server\dashboard\store\store-list.php`, `C:\xampp\htdocs\server\dashboard\store\equipment-list.php`
- 문제: mysqli 스타일 코드 사용으로 인한 호환성 문제
- 해결: PDO 스타일로 코드 수정
- 관련 이슈: 데이터베이스 연결 함수를 일관되게 get_db_connection()으로 통일
