<?php
/**
 * 관리자 계정 추가 스크립트
 */

// 필요한 파일 포함
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// 관리자 로그인 정보
$admin_email = 'admin@lotto.com';
$admin_password = 'admin123';
$admin_username = 'Admin';
$admin_role = 'admin';

// 데이터베이스 연결
$conn = getDbConnection();

// 이미 존재하는 사용자인지 확인
$sql = "SELECT * FROM users WHERE email = ?";
$params = [
    ['type' => 's', 'value' => $admin_email]
];

$user = fetchOne($sql, $params);

if ($user) {
    // 사용자가 존재하면 비밀번호 업데이트
    echo "관리자 계정이 이미 존재합니다. 비밀번호를 업데이트합니다.<br>";
    
    $hashedPassword = password_hash($admin_password, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
    
    $update_sql = "UPDATE users SET password = ?, status = 'active' WHERE email = ?";
    $update_params = [
        ['type' => 's', 'value' => $hashedPassword],
        ['type' => 's', 'value' => $admin_email]
    ];
    
    $result = execute($update_sql, $update_params);
    
    if ($result !== false) {
        echo "비밀번호가 성공적으로 업데이트되었습니다.<br>";
    } else {
        echo "비밀번호 업데이트 중 오류가 발생했습니다.<br>";
    }
} else {
    // 관리자 계정 생성
    echo "관리자 계정을 생성합니다.<br>";
    
    $hashedPassword = password_hash($admin_password, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
    
    $insert_sql = "INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, ?, 'active')";
    $insert_params = [
        ['type' => 's', 'value' => $admin_username],
        ['type' => 's', 'value' => $admin_email],
        ['type' => 's', 'value' => $hashedPassword],
        ['type' => 's', 'value' => $admin_role]
    ];
    
    $result = insert($insert_sql, $insert_params);
    
    if ($result !== false) {
        echo "관리자 계정이 성공적으로 생성되었습니다.<br>";
    } else {
        echo "관리자 계정 생성 중 오류가 발생했습니다.<br>";
    }
}

// 사용자 테이블 구조 확인
try {
    $table_info = fetchAll("DESCRIBE users");
    echo "<br>사용자 테이블 구조:<br>";
    echo "<pre>";
    print_r($table_info);
    echo "</pre>";
} catch (Exception $e) {
    echo "테이블 구조 조회 중 오류가 발생했습니다: " . $e->getMessage() . "<br>";
}

// 관리자 계정 정보 확인
try {
    $admin_info = fetchOne("SELECT id, username, email, role, status FROM users WHERE email = ?", [
        ['type' => 's', 'value' => $admin_email]
    ]);
    
    echo "<br>관리자 계정 정보:<br>";
    echo "<pre>";
    print_r($admin_info);
    echo "</pre>";
} catch (Exception $e) {
    echo "관리자 계정 정보 조회 중 오류가 발생했습니다: " . $e->getMessage() . "<br>";
}
