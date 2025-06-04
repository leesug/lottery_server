<?php
/**
 * 회차 관리 처리 페이지
 * 
 * 회차 추가, 수정, 삭제 등의 작업을 처리합니다.
 */

require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// 로그인 확인
requireLogin();

// 관리자 권한 확인
requireAdmin();

// CSRF 토큰 검증
if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
    $_SESSION['flash_message'] = "잘못된 요청입니다.";
    $_SESSION['flash_type'] = "danger";
    header("Location: /server/dashboard/lottery/draw-manage.php");
    exit;
}

// 요청 처리
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'add':
        // 회차 추가
        addDraw();
        break;
    
    case 'edit':
        // 회차 수정
        editDraw();
        break;
    
    default:
        // 잘못된 액션
        $_SESSION['flash_message'] = "잘못된 요청입니다.";
        $_SESSION['flash_type'] = "danger";
        header("Location: /server/dashboard/lottery/draw-manage.php");
        exit;
}

/**
 * 새 회차를 추가합니다.
 */
function addDraw() {
    // 필수 입력값 확인
    $drawNumber = $_POST['draw_number'] ?? '';
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $drawDate = $_POST['draw_date'] ?? '';
    $status = $_POST['status'] ?? 'pending';
    $description = $_POST['description'] ?? '';
    
    if (empty($drawNumber) || empty($startDate) || empty($endDate) || empty($drawDate)) {
        $_SESSION['flash_message'] = "필수 입력값이 누락되었습니다.";
        $_SESSION['flash_type'] = "danger";
        header("Location: /server/dashboard/lottery/draw-manage.php");
        exit;
    }
    
    // 날짜 유효성 검사
    $startDateTime = strtotime($startDate);
    $endDateTime = strtotime($endDate);
    $drawDateTime = strtotime($drawDate);
    
    if ($endDateTime <= $startDateTime) {
        $_SESSION['flash_message'] = "판매종료일은 판매시작일보다 이후여야 합니다.";
        $_SESSION['flash_type'] = "danger";
        header("Location: /server/dashboard/lottery/draw-manage.php");
        exit;
    }
    
    if ($drawDateTime <= $endDateTime) {
        $_SESSION['flash_message'] = "추첨일은 판매종료일보다 이후여야 합니다.";
        $_SESSION['flash_type'] = "danger";
        header("Location: /server/dashboard/lottery/draw-manage.php");
        exit;
    }
    
    try {
        // 회차 번호 중복 확인
        $existingDraw = fetchOne("SELECT id FROM draws WHERE draw_number = ?", [
            ['type' => 's', 'value' => $drawNumber]
        ]);
        
        if ($existingDraw) {
            $_SESSION['flash_message'] = "이미 존재하는 회차 번호입니다.";
            $_SESSION['flash_type'] = "danger";
            header("Location: /server/dashboard/lottery/draw-manage.php");
            exit;
        }
        
        // 회차 추가
        $result = insert("INSERT INTO draws (draw_number, start_date, end_date, draw_date, status, description) VALUES (?, ?, ?, ?, ?, ?)", [
            ['type' => 's', 'value' => $drawNumber],
            ['type' => 's', 'value' => $startDate],
            ['type' => 's', 'value' => $endDate],
            ['type' => 's', 'value' => $drawDate],
            ['type' => 's', 'value' => $status],
            ['type' => 's', 'value' => $description]
        ]);
        
        if ($result) {
            logInfo("새 회차 추가: 회차번호 $drawNumber", 'lottery');
            $_SESSION['flash_message'] = "회차가 성공적으로 추가되었습니다.";
            $_SESSION['flash_type'] = "success";
        } else {
            logError("회차 추가 실패: 회차번호 $drawNumber", 'lottery');
            $_SESSION['flash_message'] = "회차 추가 중 오류가 발생했습니다.";
            $_SESSION['flash_type'] = "danger";
        }
    } catch (Exception $e) {
        // 데이터베이스 오류 처리 (테이블이 없는 경우 등)
        logError("회차 추가 중 예외 발생: " . $e->getMessage(), 'lottery');
        
        // 데이터베이스 테이블 존재 여부 확인
        $tableExists = false;
        try {
            $tables = fetchAll("SHOW TABLES LIKE 'draws'");
            $tableExists = (count($tables) > 0);
        } catch (Exception $e) {
            $tableExists = false;
        }
        
        if (!$tableExists) {
            // 테이블이 없는 경우 테이블 생성 안내
            $_SESSION['flash_message'] = "draws 테이블이 존재하지 않습니다. 데이터베이스에 필요한 테이블을 생성하세요.";
        } else {
            $_SESSION['flash_message'] = "회차 추가 중 데이터베이스 오류: " . $e->getMessage();
        }
        
        $_SESSION['flash_type'] = "danger";
    }
    
    header("Location: /server/dashboard/lottery/draw-manage.php");
    exit;
}

/**
 * 기존 회차를 수정합니다.
 */
function editDraw() {
    // 필수 입력값 확인
    $drawId = $_POST['draw_id'] ?? 0;
    $drawNumber = $_POST['draw_number'] ?? '';
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $drawDate = $_POST['draw_date'] ?? '';
    $status = $_POST['status'] ?? 'pending';
    $description = $_POST['description'] ?? '';
    
    if (empty($drawId) || empty($drawNumber) || empty($startDate) || empty($endDate) || empty($drawDate)) {
        $_SESSION['flash_message'] = "필수 입력값이 누락되었습니다.";
        $_SESSION['flash_type'] = "danger";
        header("Location: /server/dashboard/lottery/draw-manage.php");
        exit;
    }
    
    // 날짜 유효성 검사
    $startDateTime = strtotime($startDate);
    $endDateTime = strtotime($endDate);
    $drawDateTime = strtotime($drawDate);
    
    if ($endDateTime <= $startDateTime) {
        $_SESSION['flash_message'] = "판매종료일은 판매시작일보다 이후여야 합니다.";
        $_SESSION['flash_type'] = "danger";
        header("Location: /server/dashboard/lottery/draw-manage.php");
        exit;
    }
    
    if ($drawDateTime <= $endDateTime) {
        $_SESSION['flash_message'] = "추첨일은 판매종료일보다 이후여야 합니다.";
        $_SESSION['flash_type'] = "danger";
        header("Location: /server/dashboard/lottery/draw-manage.php");
        exit;
    }
    
    try {
        // 회차 존재 확인
        $existingDraw = fetchOne("SELECT id, draw_number FROM draws WHERE id = ?", [
            ['type' => 'i', 'value' => $drawId]
        ]);
        
        if (!$existingDraw) {
            $_SESSION['flash_message'] = "존재하지 않는 회차입니다.";
            $_SESSION['flash_type'] = "danger";
            header("Location: /server/dashboard/lottery/draw-manage.php");
            exit;
        }
        
        // 회차 번호 중복 확인 (다른 회차와 중복 여부)
        if ($drawNumber !== $existingDraw['draw_number']) {
            $duplicateCheck = fetchOne("SELECT id FROM draws WHERE draw_number = ? AND id != ?", [
                ['type' => 's', 'value' => $drawNumber],
                ['type' => 'i', 'value' => $drawId]
            ]);
            
            if ($duplicateCheck) {
                $_SESSION['flash_message'] = "이미 존재하는 회차 번호입니다.";
                $_SESSION['flash_type'] = "danger";
                header("Location: /server/dashboard/lottery/draw-manage.php");
                exit;
            }
        }
        
        // 회차 수정
        $result = execute("UPDATE draws SET draw_number = ?, start_date = ?, end_date = ?, draw_date = ?, status = ?, description = ? WHERE id = ?", [
            ['type' => 's', 'value' => $drawNumber],
            ['type' => 's', 'value' => $startDate],
            ['type' => 's', 'value' => $endDate],
            ['type' => 's', 'value' => $drawDate],
            ['type' => 's', 'value' => $status],
            ['type' => 's', 'value' => $description],
            ['type' => 'i', 'value' => $drawId]
        ]);
        
        if ($result) {
            logInfo("회차 업데이트: ID $drawId, 회차번호 $drawNumber", 'lottery');
            $_SESSION['flash_message'] = "회차가 성공적으로 수정되었습니다.";
            $_SESSION['flash_type'] = "success";
        } else {
            logError("회차 업데이트 실패: ID $drawId", 'lottery');
            $_SESSION['flash_message'] = "회차 수정 중 오류가 발생했습니다.";
            $_SESSION['flash_type'] = "danger";
        }
    } catch (Exception $e) {
        // 데이터베이스 오류 처리
        logError("회차 수정 중 예외 발생: " . $e->getMessage(), 'lottery');
        $_SESSION['flash_message'] = "회차 수정 중 데이터베이스 오류: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
    }
    
    header("Location: /server/dashboard/lottery/draw-manage.php");
    exit;
}
