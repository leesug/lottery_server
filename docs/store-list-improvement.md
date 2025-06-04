# 판매점 목록 화면 레이아웃 개선 작업

## 문제점 분석
판매점 관리 > 판매점 목록 화면에서 다음과 같은 문제가 발견되었습니다:
1. 판매점 목록 페이지가 고객 목록 페이지와 다른 스타일과 구조로 되어 있어 일관성이 없음
2. panel 클래스를 사용하여 AdminLTE 템플릿과 맞지 않는 구조를 가지고 있음
3. 검색 및 필터 영역의 레이아웃이 고객 목록 페이지와 다름
4. 페이지네이션 영역의 스타일이 일관되지 않음

## 수정 내용
1. panel 클래스를 card 클래스로 변경하여 AdminLTE 템플릿과 일관된 구조로 수정
   - 모든 panel-heading, panel-title, panel-body를 card-header, card-title, card-body로 변경
   - card-tools 클래스를 추가하여 헤더 영역의 버튼 스타일 통일
   - card-footer 클래스를 적용하여 페이지네이션 영역 개선

2. 검색 및 필터 영역 개선
   - 고객 목록 페이지와 동일한 폼 구조 적용
   - 검색 필드에 fas fa-search 아이콘 추가
   - 필터 적용 및 초기화 버튼 스타일 통일

3. 판매점 목록 테이블 개선
   - table-responsive 클래스 추가
   - 테이블에 table-striped, table-hover 클래스 적용
   - 정렬 아이콘 (fas fa-sort-up, fas fa-sort-down) 추가
   - 상태 표시에 badge 클래스 적용

4. 페이지네이션 UI 개선
   - pagination-sm 클래스 적용
   - page-item, page-link 클래스 적용
   - active 클래스를 사용한 현재 페이지 강조 표시

5. 메시지 알림 영역 개선
   - alert-dismissible, fade, show 클래스 추가
   - 닫기 버튼 추가

6. 내보내기 메뉴 개선
   - 드롭다운 메뉴로 변경
   - Excel, CSV 내보내기 옵션 추가

## 결과
판매점 목록 페이지가 고객 목록 페이지와 동일한 시각적 일관성을 가지고 정상적으로 표시됩니다. AdminLTE 템플릿의 표준을 따라 카드 스타일, 테이블 스타일, 페이지네이션 스타일이 모두 통일되었습니다.
