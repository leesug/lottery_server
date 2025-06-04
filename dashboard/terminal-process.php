<?php
/**
 * 단말기 처리 페이지
 * 
 * 단말기 추가, 수정, 삭제 등의 작업을 처리합니다.
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
    header("Location: /server/dashboard/terminals.php");
    exit;
}

// 요청 처리
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'add':
        // 단말기 추가
        addTerminal();
        break;
    
    case 'edit':
        // 단말기 수정
        editTerminal();
        break;
    
    default:
        // 잘못된 액션
        $_SESSION['flash_message'] = "잘못된 요청입니다.";
        $_SESSION['flash_type'] = "danger";
        header("Location: /server/dashboard/terminals.php");
        exit;
}

/**
 * 새 단말기를 추가합니다.
 */
function addTerminal() {
    // 필수 입력값 확인
    $terminal_code = $_POST['terminal_code'] ?? '';
    $location = $_POST['location'] ?? '';
    $status = $_POST['status'] ?? 'active';
    $agent_id = !empty($_POST['agent_id']) ? (int)$_POST['agent_id'] : null;
    
    if (empty($terminal_code) || empty($location)) {
        $_SESSION['flash_message'] = "필수 입력값이 누락되었습니다.";
        $_SESSION['flash_type'] = "danger";
        header("Location: /server/dashboard/terminals.php");
        exit;
    }
    
    // 단말기 코드 중복 확인
    $existingTerminal = fetchOne("SELECT id FROM terminals WHERE terminal_code = ?", [
        ['type' => 's', 'value' => $terminal_code]
    ]);
    
    if ($existingTerminal) {
        $_SESSION['flash_message'] = "이미 존재하는 단말기 코드입니다.";
        $_SESSION['flash_type'] = "danger";
        header("Location: /server/dashboard/terminals.php");
        exit;
    }
    
    // 에이전트 유효성 확인 (있는 경우)
    if ($agent_id) {
        $agent = fetchOne("SELECT id FROM users WHERE id = ? AND role = 'agent' AND status = 'active'", [
            ['type' => 'i', 'value' => $agent_id]
        ]);
        
        if (!$agent) {
            $_SESSION['flash_message'] = "유효하지 않은 에이전트입니다.";
            $_SESSION['flash_type'] = "danger";
            header("Location: /server/dashboard/terminals.php");
            exit;
        }
    }
    
    // 단말기 추가
    $result = insert("INSERT INTO terminals (terminal_code, location, agent_id, status) VALUES (?, ?, ?, ?)", [
        ['type' => 's', 'value' => $terminal_code],
        ['type' => 's', 'value' => $location],
        ['type' => 'i', 'value' => $agent_id],
        ['type' => 's', 'value' => $status]
    ]);
    
    if ($result) {
        logInfo("새 단말기 추가: $terminal_code", 'terminal');
        $_SESSION['flash_message'] = "단말기가 성공적으로 추가되었습니다.";
        $_SESSION['flash_type'] = "success";
    } else {
        logError("단말기 추가 실패: $terminal_code", 'terminal');
        $_SESSION['flash_message'] = "단말기 추가 중 오류가 발생했습니다.";
        $_SESSION['flash_type'] = "danger";
    }
    
    header("Location: /server/dashboard/terminals.php");
    exit;
}

/**
 * 기존 단말기를 수정합니다.
 */
function editTerminal() {
    // 필수 입력값 확인
    $terminal_id = $_POST['terminal_id'] ?? 0;
    $terminal_code = $_POST['terminal_code'] ?? '';
    $location = $_POST['location'] ?? '';
    $status = $_POST['status'] ?? 'active';
    $agent_id = !empty($_POST['agent_id']) ? (int)$_POST['agent_id'] : null;
    
    if (empty($terminal_id) || empty($terminal_code) || empty($location)) {
        $_SESSION['flash_message'] = "필수 입력값이 누락되었습니다.";
        $_SESSION['flash_type'] = "danger";
        header("Location: /server/dashboard/terminals.php");
        exit;
    }
    
    // 단말기 존재 확인
    $existingTerminal = fetchOne("SELECT id, terminal_code FROM terminals WHERE id = ?", [
        ['type' => 'i', 'value' => $terminal_id]
    ]);
    
    if (!$existingTerminal) {
        $_SESSION['flash_message'] = "존재하지 않는 단말기입니다.";
        $_SESSION['flash_type'] = "danger";
        header("Location: /server/dashboard/terminals.php");
        exit;
    }
    
    // 단말기 코드 중복 확인 (다른 단말기와 중복 여부)
    if ($terminal_code !== $existingTerminal['terminal_code']) {
        $duplicateCheck = fetchOne("SELECT id FROM terminals WHERE terminal_code = ? AND id != ?", [
            ['type' => 's', 'value' => $terminal_code],
            ['type' => 'i', 'value' => $terminal_id]
        ]);
        
        if ($duplicateCheck) {
            $_SESSION['flash_message'] = "이미 존재하는 단말기 코드입니다.";
            $_SESSION['flash_type'] = "danger";
            header("Location: /server/dashboard/terminals.php");
            exit;
        }
    }
    
    // 에이전트 유효성 확인 (있는 경우)
    if ($agent_id) {
        $agent = fetchOne("SELECT id FROM users WHERE id = ? AND role = 'agent' AND status = 'active'", [
            ['type' => 'i', 'value' => $agent_id]
        ]);
        
        if (!$agent) {
            $_SESSION['flash_message'] = "유효하지 않은 에이전트입니다.";
            $_SESSION['flash_type'] = "danger";
            header("Location: /server/dashboard/terminals.php");
            exit;
        }
    }
    
    // 단말기 수정
    $result = execute("UPDATE terminals SET terminal_code = ?, location = ?, agent_id = ?, status = ? WHERE id = ?", [
        ['type' => 's', 'value' => $terminal_code],
        ['type' => 's', 'value' => $location],
        ['type' => 'i', 'value' => $agent_id],
        ['type' => 's', 'value' => $status],
        ['type' => 'i', 'value' => $terminal_id]
    ]);
    
    if ($result) {
        logInfo("단말기 업데이트: ID $terminal_id, 코드 $terminal_code", 'terminal');
        $_SESSION['flash_message'] = "단말기가 성공적으로 수정되었습니다.";
        $_SESSION['flash_type'] = "success";
    } else {
        logError("단말기 업데이트 실패: ID $terminal_id", 'terminal');
        $_SESSION['flash_message'] = "단말기 수정 중 오류가 발생했습니다.";
        $_SESSION['flash_type'] = "danger";
    }
    
    header("Location: /server/dashboard/terminals.php");
    exit;
}
