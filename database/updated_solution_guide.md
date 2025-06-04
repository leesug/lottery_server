# TRUNCATE 오류 해결 방법 안내

## 문제 원인
이번 오류는 외래 키 제약 조건이 있는 테이블을 TRUNCATE 하려고 할 때 발생했습니다:
`#1701 - Cannot truncate a table referenced in a foreign key constraint`

MySQL에서는 외래 키 제약 조건이 있는 경우에도 `FOREIGN_KEY_CHECKS = 0`로 설정해도 TRUNCATE 명령이 제대로 작동하지 않을 수 있습니다.

## 해결 방법 (총 3가지 옵션)

### 1. 테이블 재구축 방법 (rebuild_tables.sql)
이 방법은 테이블을 완전히 삭제하고 다시 생성합니다. 기존에 있던
모든 데이터가 삭제되므로 신중하게 사용해야 합니다.

```sql
-- 테이블 완전히 삭제 후 재생성
DROP TABLE IF EXISTS equipment_maintenance;
DROP TABLE IF EXISTS equipment;
-- 이하 생략...
```

### 2. DELETE 명령어 사용 방법 (alternative_solution.sql)
이 방법은 TRUNCATE 대신 DELETE를 사용하여 데이터만 삭제합니다.
테이블 구조는 유지됩니다.

```sql
-- DELETE로 데이터만 삭제
DELETE FROM equipment_maintenance;
DELETE FROM equipment;
-- 이하 생략...
```

### 3. REPLACE INTO 사용 방법 (replace_solution.sql)
이 방법은 기존 데이터는 유지하면서 중복 키가 있는 레코드만 대체합니다.

```sql
-- 중복 키가 있을 경우 레코드를 대체
REPLACE INTO stores (...) VALUES (...);
```

## 실행 방법

1. phpMyAdmin에서 실행:
   - phpMyAdmin에 접속 (http://localhost/phpmyadmin/)
   - lottery 데이터베이스 선택
   - SQL 탭 클릭
   - 위 방법 중 하나의 SQL 파일 내용을 복사하여 붙여넣기
   - '실행' 버튼 클릭

2. 명령줄에서 실행:
   ```
   mysql -u root -p lottery < C:\xampp\htdocs\server\database\rebuild_tables.sql
   ```
   (또는 다른 SQL 파일 경로 사용)

## 파일 설명

1. **rebuild_tables.sql**: 모든 테이블을 삭제하고 다시 생성합니다. 가장 철저한 방법입니다.

2. **alternative_solution.sql**: DELETE 명령어를 사용하여 데이터만 삭제합니다. 기존 테이블 구조는 유지됩니다.

3. **replace_solution.sql**: REPLACE INTO를 사용하여 중복 키가 있는 경우 해당 레코드만 대체합니다.

위 방법 중 하나를 선택하여 실행하시면 중복 키 오류 문제를 해결할 수 있습니다. 일반적으로는 상황에 따라 아래와 같이 선택하시는 것이 좋습니다:

- 모든 데이터를 새로 시작하고 싶다면: rebuild_tables.sql
- 기존 테이블 구조는 유지하면서 데이터만 초기화하고 싶다면: alternative_solution.sql
- 최소 변경으로 일부 레코드만 수정하고 싶다면: replace_solution.sql
