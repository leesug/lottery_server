# 중복 오류 해결 방법 안내

중복 키 오류(#1062 - 중복된 입력 값 'STORE12345678': key 'store_code')를 해결하기 위한 안내입니다.

## 문제 원인
이 오류는 stores 테이블에 이미 'STORE12345678'라는 store_code를 가진 레코드가 존재하는데, 
동일한 코드로 새 데이터를 삽입하려고 할 때 발생합니다.

## 해결 방법

1. 새로 생성된 `reset_test_data.sql` 스크립트를 사용하여 기존 테이블 데이터를 삭제하고 새로 삽입합니다.

2. phpMyAdmin에서 실행하는 방법:
   - phpMyAdmin에 접속 (주소: http://localhost/phpmyadmin/)
   - 왼쪽 사이드바에서 'lottery' 데이터베이스 선택
   - 상단 메뉴에서 'SQL' 탭 클릭
   - `reset_test_data.sql` 파일의 내용을 복사하여 붙여넣기
   - 'Go' 또는 '실행' 버튼 클릭

3. 명령줄에서 실행하는 방법:
   ```
   mysql -u root -p lottery < C:\xampp\htdocs\server\database\reset_test_data.sql
   ```

## 대안적인 방법

아래 방법 중 하나를 사용하여 개별적으로 해결할 수도 있습니다:

1. 기존 레코드 삭제:
   ```sql
   DELETE FROM stores WHERE store_code = 'STORE12345678';
   ```
   그 후 다시 INSERT 문을 실행합니다.

2. INSERT IGNORE 사용:
   ```sql
   INSERT IGNORE INTO stores (store_code, ...) VALUES ('STORE12345678', ...);
   ```
   이 방법은 중복 키가 있을 경우 해당 행만 무시합니다.

3. REPLACE INTO 사용:
   ```sql
   REPLACE INTO stores (store_code, ...) VALUES ('STORE12345678', ...);
   ```
   이 방법은 중복 키가 있을 경우 기존 행을 삭제하고 새 행을 삽입합니다.

4. 새로운 store_code 사용:
   ```sql
   INSERT INTO stores (store_code, ...) VALUES ('STORE12345679', ...);
   ```
   store_code 값을 변경하여 중복을 피합니다.

생성된 `reset_test_data.sql` 스크립트를 실행하면 모든 테스트 데이터가 초기화되어 깔끔하게 재설정됩니다.
