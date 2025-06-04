# 판매점 계약 관리 기능 구현

이 프로젝트는 로또 서버의 판매점 계약 관리 기능을 구현한 것입니다.

## 구현된 기능

1. 계약 목록 페이지 (store-contracts.php)
2. 계약 상세 정보 페이지 (contract-details.php)
3. 계약 등록 페이지 (contract-add.php)
4. 계약 수정 페이지 (contract-edit.php)
5. 계약 갱신 페이지 (contract-renew.php)
6. 계약 인쇄 페이지 (contract-print.php)
7. 계약 상태 변경 처리 (contract-status-change.php)

## 설치 및 실행 방법

1. MySQL 데이터베이스에 'lotto_system' 데이터베이스 생성
2. SQL 스크립트 실행하여 필요한 테이블 생성 (database/schema.sql)
3. 테스트 데이터 입력 (database/test-data.sql)
4. 웹 서버에서 `/server` 경로로 접속

## 데이터베이스 스키마

판매점 계약 관리에 필요한 테이블은 다음과 같습니다:

- stores: 판매점 정보
- store_contracts: 판매점 계약 정보
- store_performance: 판매점 성과 정보
- store_equipment: 판매점 장비 정보
- store_training: 판매점 교육 정보

## 구현 화면

1. 계약 목록 화면
2. 계약 상세 정보 화면
3. 계약 등록 화면 
4. 계약 수정 화면
5. 계약 갱신 화면
6. 계약 인쇄 화면

## 개발 참고사항

테스트를 위해 데이터베이스 연결 없이 테스트할 수 있도록 임시 코드가 포함되어 있습니다. 
실제 배포 시에는 데이터베이스 연결 코드를 활성화하고 임시 코드를 제거해야 합니다.

1. store-contracts.php의 시뮬레이션 코드 제거
2. 올바른 header.php, footer.php 파일 연결
3. auth.php의 임시 인증 코드 제거

## 추가 개발 필요 사항

1. 판매점 성과 관리 기능 구현
2. 판매점 장비 관리 기능 구현
3. 판매점 교육 관리 기능 구현
4. API 엔드포인트 구현
5. 테스트 및 디버깅