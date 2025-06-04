# 히스토리 페이지 오류 수정

## 문제 상황
오류 메시지: **Deprecated: htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in C:\xampp\htdocs\server\dashboard\draw\history.php on line 538**

## 원인 분석
1. `history.php` 파일에서 538라인에 있는 `htmlspecialchars()` 함수가 null 값을 인자로 받고 있었습니다.
2. 파일 하단에 이중으로 헤더와 푸터를 포함하는 코드가 있어 페이지 구조가 혼란스러웠습니다:
   - 첫 번째 헤더/푸터 포함: dashboard_header.php와 dashboard_footer.php 템플릿
   - 두 번째 헤더/푸터 포함: header.php, sidebar.php, footer.php 템플릿

## 해결 방법
1. `htmlspecialchars()` 함수 호출에 null 병합 연산자를 사용하여 빈 문자열이 전달되도록 수정:
   ```php
   htmlspecialchars($draw['winning_numbers'] ?? '');
   ```

2. 이중 헤더/푸터 포함 문제 해결:
   - 중복된 코드 블록 제거
   - 페이지 하단에 dashboard_footer.php만 포함되도록 수정

## 테스트 결과
- 페이지가 정상적으로 로드됨
- PHP 경고 메시지가 더 이상 표시되지 않음
- 페이지 레이아웃이 정상적으로 표시됨

## 이후 권장 사항
1. 다른 페이지들도 유사한 문제가 있는지 확인
2. 모든 htmlspecialchars() 함수 호출에 null 병합 연산자 적용 검토
3. 모든 페이지에서 이중 헤더/푸터 포함이 있는지 검사
