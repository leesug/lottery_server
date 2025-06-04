<?php
/**
 * 모의 데이터베이스 연결 클래스
 * 실제 데이터베이스 작업 없이 UI 테스트를 위한 클래스
 */

require_once 'MockPDO.php';

class MockDbConnection {
    public $connect_error = false;
    public $insert_id = 1;
    public $affected_rows = 0;
    
    /**
     * SQL 쿼리를 실행하는 메소드
     * 
     * @param string $sql SQL 쿼리 문자열
     * @return MockDbResult 쿼리 결과 객체
     */
    public function query($sql) {
        // 결과를 모의로 반환
        return new MockDbResult();
    }
    
    /**
     * SQL 쿼리를 준비하는 메소드
     * 
     * @param string $sql SQL 쿼리 문자열
     * @return MockPDOStatement 준비된 쿼리 객체
     */
    public function prepare($sql) {
        // MockPDOStatement 객체를 반환
        return new MockPDOStatement();
    }
    
    /**
     * 트랜잭션을 시작하는 메소드
     * 
     * @return bool 항상 true 반환
     */
    public function begin_transaction() {
        return true;
    }
    
    /**
     * 트랜잭션을 커밋하는 메소드
     * 
     * @return bool 항상 true 반환
     */
    public function commit() {
        return true;
    }
    
    /**
     * 트랜잭션을 롤백하는 메소드
     * 
     * @return bool 항상 true 반환
     */
    public function rollback() {
        return true;
    }
}

/**
 * 모의 데이터베이스 결과 클래스
 */
class MockDbResult {
    public $num_rows = 0;
    private $data = [];
    private $index = 0;
    
    /**
     * 결과 행을 연관 배열로 가져오는 메소드
     * 
     * @return array 빈 배열 반환
     */
    public function fetch_assoc() {
        return [];
    }
    
    /**
     * 결과 리소스를 해제하는 메소드
     */
    public function free() {
        return true;
    }
    
    /**
     * 결과 리소스를 닫는 메소드
     */
    public function close() {
        return true;
    }
}
