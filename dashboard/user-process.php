<?php
/**
 * 사용자 처리 페이지
 * 
 * 사용자 추가, 수정, 비밀번호 재설정 등의 작업을 처리합니다.
 */

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// 로그인 확인
requireLogin();

// 관리자 권한 확인
requireAdmin();

// CSRF 토큰 검증
if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
    $_SESSION['flash_message'] = "잘못된 요청입니다.";
    $_SESSION['flash_type'] = "danger";
    header("Location: /server/dashboard/users.php");
    exit;
}

// 요청 처리
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'add':
        // 사용자 추가
        addUser();
        break;
    
    case 'edit':
        // 사용자 수정
        editUser();
        break;
    
    case 'reset_password':
        // 비밀번호 재설정
        resetUserPassword();
        break;
    
    default:
        // 잘못된 액션
        $_SESSION['flash_message'] = "잘못된 요청입니다.";
        $_SESSION['flash_type'] = "danger";
        header("Location: /server/dashboard/users.php");
        exit;
}

/**
 * 새 사용자를 추가합니다.
 */
function addUser() {
    // 필수 입력값 확인
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'user';
    $status = $_POST['status'] ?? 'active';
    
    if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
        $_SESSION['flash_message'] = "필수 입력값이 누락되었습니다.";
        $_SESSION['flash_type'] = "danger";
        header("Location: /server/dashboard/users.php");
        exit;
    }
    
    // 비밀번호 일치 확인
    if ($password !== $confirmPassword) {
        $_SESSION['flash_message'] = "비밀번호가 일치하지 않습니다.";
        $_SESSION['flash_type'] = "danger";
        header("Location: /server/dashboard/users.php");
        exit;
    }
    
    // 이메일 유효성 확인
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['flash_message'] = "유효하지 않은 이메일 형식입니다.";
        $_SESSION['flash_type'] = "danger";
        header("Location: /server/dashboard/users.php");
        exit;
    }
    
    // 이메일 중복 확인
    $existingUser = fetchOne("SELECT id FROM users WHERE email = ?", [
        ['type' => 's', 'value' => $email]
    ]);
    
    if ($existingUser) {
        $_SESSION['flash_message'] = "이미 사용 중인 이메일입니다.";
        $_SESSION['flash_type'] = "danger";
        header("Location: /server/dashboard/users.php");
        exit;
    }
    
    // 사용자 이름 중복 확인
    $existingUsername = fetchOne("SELECT id FROM users WHERE username = ?", [
        ['type' => 's', 'value' => $username]
    ]);
    
    if ($existingUsername) {
        $_SESSION['flash_message'] = "이미 사용 중인 사용자 이름입니다.";
        $_SESSION['flash_type'] = "danger";
        header("Location: /server/dashboard/users.php");
        exit;
    }
    
    // 비밀번호 해시 생성
    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
    
    // 사용자 추가
    $result = insert("INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, ?, ?)", [
        ['type' => 's', 'value' => $username],
        ['type' => 's', 'value' => $email],
        ['type' => 's', 'value' => $passwordHash],
        ['type' => 's', 'value' => $role],
        ['type' => 's', 'value' => $status]
    ]);
    
    if ($result) {
        logInfo("새 사용자 추가: $username ($email), 역할: $role", 'user');
        $_SESSION['flash_message'] = "사용자가 성공적으로 추가되었습니다.";
        $_SESSION['flash_type'] = "success";
    } else {
        logError("사용자 추가 실패: $username ($email)", 'user');
        $_SESSION['flash_message'] = "사용자 추가 중 오류가 발생했습니다.";
        $_SESSION['flash_type'] = "danger";
    }
    
    header("Location: /server/dashboard/users.php");
    exit;
}

/**
 * 기존 사용자를 수정합니다.
 */
function editUser() {
    // 필수 입력값 확인
    $userId = $_POST['user_id'] ?? 0;
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $role = $_POST['role'] ?? 'user';
    $status = $_POST['status'] ?? 'active';
    
    if (empty($userId) || empty($username) || empty($email)) {
        $_SESSION['flash_message'] = "필수 입력값이 누락되었습니다.";
        $_SESSION['flash_type'] = "danger";
        header("Location: /server/dashboard/users.php");
        exit;
    }
    
    // 자신을 수정하려는지 확인
    if ((int)$userId === getCurrentUserId()) {
        $_SESSION['flash_message'] = "자신의 계정은 이 페이지에서 수정할 수 없습니다.";
        $_SESSION['flash_type'] = "danger";
        header("Location: /server/dashboard/users.php");
        exit;
    }
    
    // 사용자 존재 확인
    $existingUser = fetchOne("SELECT id, username, email FROM users WHERE id = ?", [
        ['type' => 'i', 'value' => $userId]
    ]);
    
    if (!$existingUser) {
        $_SESSION['flash_message'] = "존재하지 않는 사용자입니다.";
        $_SESSION['flash_type'] = "danger";
        header("Location: /server/dashboard/users.php");
        exit;
    }
    
    // 이메일 유효성 확인
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['flash_message'] = "유효하지 않은 이메일 형식입니다.";
        $_SESSION['flash_type'] = "danger";
        header("Location: /server/dashboard/users.php");
        exit;
    }
    
    // 이메일 중복 확인 (다른 사용자와 중복 여부)
    if ($email !== $existingUser['email']) {
        $duplicateEmail = fetchOne("SELECT id FROM users WHERE email = ? AND id != ?", [
            ['type' => 's', 'value' => $email],
            ['type' => 'i', 'value' => $userId]
        ]);
        
        if ($duplicateEmail) {
            $_SESSION['flash_message'] = "이미 사용 중인 이메일입니다.";
            $_SESSION['flash_type'] = "danger";
            header("Location: /server/dashboard/users.php");
            exit;
        }
    }
    
    // 사용자 이름 중복 확인 (다른 사용자와 중복 여부)
    if ($username !== $existingUser['username']) {
        $duplicateUsername = fetchOne("SELECT id FROM users WHERE username = ? AND id != ?", [
            ['type' => 's', 'value' => $username],
            ['type' => 'i', 'value' => $userId]
        ]);
        
        if ($duplicateUsername) {
            $_SESSION['flash_message'] = "이미 사용 중인 사용자 이름입니다.";
            $_SESSION['flash_type'] = "danger";
            header("Location: /server/dashboard/users.php");
            exit;
        }
    }
    
    // 사용자 수정
    $result = execute("UPDATE users SET username = ?, email = ?, role = ?, status = ? WHERE id = ?", [
        ['type' => 's', 'value' => $username],
        ['type' => 's', 'value' => $email],
        ['type' => 's', 'value' => $role],
        ['type' => 's', 'value' => $status],
        ['type' => 'i', 'value' => $userId]
    ]);
    
    if ($result) {
        logInfo("사용자 업데이트: ID $userId, $username ($email), 역할: $role", 'user');
        $_SESSION['flash_message'] = "사용자 정보가 성공적으로 수정되었습니다.";
        $_SESSION['flash_type'] = "success";
    } else {
        logError("사용자 업데이트 실패: ID $userId", 'user');
        $_SESSION['flash_message'] = "사용자 정보 수정 중 오류가 발생했습니다.";
        $_SESSION['flash_type'] = "danger";
    }
    
    header("Location: /server/dashboard/users.php");
    exit;
}

/**
 * 사용자의 비밀번호를 재설정합니다.
 */
function resetUserPassword() {
    // 필수 입력값 확인
    $userId = $_POST['user_id'] ?? 0;
    $newPassword = $_POST['new_password'] ?? '';
    $confirmNewPassword = $_POST['confirm_new_password'] ?? '';
    
    if (empty($userId) || empty($newPassword) || empty($confirmNewPassword)) {
        $_SESSION['flash_message'] = "필수 입력값이 누락되었습니다.";
        $_SESSION['flash_type'] = "danger";
        header("Location: /server/dashboard/users.php");
        exit;
    }
    
    // 자신의 비밀번호를 재설정하려는지 확인
    if ((int)$userId === getCurrentUserId()) {
        $_SESSION['flash_message'] = "자신의 비밀번호는 이 페이지에서 변경할 수 없습니다.";
        $_SESSION['flash_type'] = "danger";
        header("Location: /server/dashboard/users.php");
        exit;
    }
    
    // 비밀번호 일치 확인
    if ($newPassword !== $confirmNewPassword) {
        $_SESSION['flash_message'] = "새 비밀번호가 일치하지 않습니다.";
        $_SESSION['flash_type'] = "danger";
        header("Location: /server/dashboard/users.php");
        exit;
    }
    
    // 사용자 존재 확인
    $existingUser = fetchOne("SELECT id, username FROM users WHERE id = ?", [
        ['type' => 'i', 'value' => $userId]
    ]);
    
    if (!$existingUser) {
        $_SESSION['flash_message'] = "존재하지 않는 사용자입니다.";
        $_SESSION['flash_type'] = "danger";
        header("Location: /server/dashboard/users.php");
        exit;
    }
    
    // 비밀번호 해시 생성
    $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
    
    // 비밀번호 업데이트
    $result = execute("UPDATE users SET password = ? WHERE id = ?", [
        ['type' => 's', 'value' => $passwordHash],
        ['type' => 'i', 'value' => $userId]
    ]);
    
    if ($result) {
        logInfo("사용자 비밀번호 재설정: ID $userId, {$existingUser['username']}", 'user');
        $_SESSION['flash_message'] = "사용자 비밀번호가 성공적으로 재설정되었습니다.";
        $_SESSION['flash_type'] = "success";
    } else {
        logError("사용자 비밀번호 재설정 실패: ID $userId", 'user');
        $_SESSION['flash_message'] = "사용자 비밀번호 재설정 중 오류가 발생했습니다.";
        $_SESSION['flash_type'] = "danger";
    }
    
    header("Location: /server/dashboard/users.php");
    exit;
}
