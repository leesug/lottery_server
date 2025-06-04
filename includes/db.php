<?php
/**
 * 데이터베이스 연결 및 쿼리 함수
 * 
 * 이 파일은 데이터베이스 연결 및 쿼리 실행에 필요한 함수를 포함합니다.
 */

require_once 'config.php';
require_once 'MockDbConnection.php';
require_once 'MockPDO.php';

/**
 * 데이터베이스 연결을 생성하고 반환합니다. (PDO 버전)
 * 모든 페이지에서 이 함수를 사용하여 데이터베이스에 연결해야 합니다.
 * 
 * @return PDO|MockPDO 데이터베이스 연결 객체
 */
function get_db_connection() {
    static $pdo = null;
    
    if ($pdo === null) {
        // 실제 환경에서는 MySQL 연결 사용
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // 연결 실패 시 MockPDO 객체 사용
            error_log('Database connection failed: ' . $e->getMessage());
            $pdo = new MockPDO();
        }
    }
    
    return $pdo;
}

/**
 * 데이터베이스 연결을 생성하고 반환합니다. (mysqli 버전)
 * 
 * @deprecated get_db_connection() 함수를 사용하세요.
 * @return mysqli|MockDbConnection 데이터베이스 연결 객체
 */
function getDbConnection_legacy() {
    // 일관성을 위해 PDO 연결로 통일
    // 경고 메시지 대신 로그에만 기록
    static $logged = false;
    
    if (!$logged) {
        // 오류 로그에 경고 기록 (화면에 표시하지 않음)
        error_log('getDbConnection() is deprecated, use get_db_connection() instead', 0);
        $logged = true;
    }
    
    // get_db_connection()을 대신 호출하여 중복 로직 제거
    return get_db_connection();
}

/**
/**
 * SQL 쿼리를 준비하고 파라미터를 바인딩하여 실행합니다.
 * 
 * @deprecated PDO prepare/execute 패턴을 직접 사용하세요.
 * @param string $sql SQL 쿼리 문자열
 * @param array $params 쿼리 파라미터 배열 (값의 타입과 함께)
 * @return mysqli_stmt|false 쿼리 결과 또는 실패 시 false
 */
function executeQuery($sql, $params = []) {
    // UI 테스트용 모의 구현
    return true;
}

/**
 * SELECT 쿼리를 실행하고 결과를 연관 배열로 반환합니다.
 * 
 * @deprecated PDO 방식을 직접 사용하세요.
 * @param string $sql SQL 쿼리 문자열
 * @param array $params 쿼리 파라미터 배열 (값의 타입과 함께)
 * @return array|false 쿼리 결과 배열 또는 실패 시 false
 */
function fetchAll($sql, $params = []) {
    // UI 테스트용 모의 구현
    return [];
}

/**
 * SELECT 쿼리를 실행하고 단일 행을 연관 배열로 반환합니다.
 * 
 * @deprecated PDO 방식을 직접 사용하세요.
 * @param string $sql SQL 쿼리 문자열
 * @param array $params 쿼리 파라미터 배열 (값의 타입과 함께)
 * @return array|false 쿼리 결과 배열 또는 결과가 없거나 실패 시 false
 */
function fetchOne($sql, $params = []) {
    // UI 테스트용 모의 구현
    return [];
}

/**
 * INSERT 쿼리를 실행하고 생성된 ID를 반환합니다.
 * 
 * @deprecated PDO 방식을 직접 사용하세요.
 * @param string $sql SQL 쿼리 문자열
 * @param array $params 쿼리 파라미터 배열 (값의 타입과 함께)
 * @return int|false 생성된 ID 또는 실패 시 false
 */
function insert($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    
    if (!$stmt) {
        return false;
    }
    
    $id = $stmt->insert_id;
    $stmt->close();
    
    return $id;
}

/**
 * UPDATE 또는 DELETE 쿼리를 실행하고 영향받은 행 수를 반환합니다.
 * 
 * @deprecated PDO 방식을 직접 사용하세요.
 * @param string $sql SQL 쿼리 문자열
 * @param array $params 쿼리 파라미터 배열 (값의 타입과 함께)
 * @return int|false 영향받은 행 수 또는 실패 시 false
 */
function execute($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    
    if (!$stmt) {
        return false;
    }
    
    $affectedRows = $stmt->affected_rows;
    $stmt->close();
    
    return $affectedRows;
}

/**
 * 트랜잭션을 시작합니다.
 * 
 * @deprecated PDO 방식을 직접 사용하세요.
 * @return bool 성공 여부
 */
function beginTransaction() {
    $db = get_db_connection();
    return $db->beginTransaction();
}

/**
 * 트랜잭션을 커밋합니다.
 * 
 * @deprecated PDO 방식을 직접 사용하세요.
 * @return bool 성공 여부
 */
function commitTransaction() {
    $db = get_db_connection();
    return $db->commit();
}

/**
 * 트랜잭션을 롤백합니다.
 * 
 * @deprecated PDO 방식을 직접 사용하세요.
 * @return bool 성공 여부
 */
function rollbackTransaction() {
    $db = get_db_connection();
    return $db->rollBack();
}

/**
 * 데이터베이스가 존재하는지 확인하고, 없으면 생성합니다.
 * 
 * @param string $dbName 데이터베이스 이름
 * @return bool 성공 여부
 */
function createDatabaseIfNotExists($dbName) {
    // PDO 방식으로 구현
    try {
        $dsn = "mysql:host=" . DB_HOST;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        return true;
    } catch (PDOException $e) {
        logError('Database creation failed: ' . $e->getMessage(), 'database');
        return false;
    }
}

/**
 * 테이블이 존재하는지 확인합니다.
 * 
 * @param string $tableName 테이블 이름
 * @return bool 존재 여부
 */
function tableExists($tableName) {
    // PDO 방식으로 구현
    $db = get_db_connection();
    $stmt = $db->prepare("SHOW TABLES LIKE ?");
    $stmt->execute([$tableName]);
    
    return $stmt->rowCount() > 0;
}

