<?php
/**
 * 재무 관리 - 예산 기간 추가 페이지
 * 
 * 이 페이지는 새로운 예산 기간을 추가하는 기능을 제공합니다.
 */

// 세션 시작 및 필수 파일 포함
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 인증 확인
checkAuth();

// 필요한 권한 확인
$requiredPermissions = ['finance_budget_create'];
checkPermissions($requiredPermissions);

// 데이터베이스 연결
$conn = getDBConnection();

// 폼 제출 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF 토큰 검증
    verifyCsrfToken($_POST['csrf_token']);
    
    // 폼 데이터 검증
    $periodName = sanitizeInput($_POST['period_name']);
    $startDate = sanitizeInput($_POST['start_date']);
    $endDate = sanitizeInput($_POST['end_date']);
    $status = sanitizeInput($_POST['status']);
    $notes = sanitizeInput($_POST['notes']);
    
    // 유효성 검사
    $errors = [];
    
    if (empty($periodName)) {
        $errors[] = "예산 기간명을 입력해주세요.";
    }
    
    if (empty($startDate)) {
        $errors[] = "시작일을 입력해주세요.";
    }
    
    if (empty($endDate)) {
        $errors[] = "종료일을 입력해주세요.";
    }
    
    if (!empty($startDate) && !empty($endDate) && strtotime($startDate) >= strtotime($endDate)) {
        $errors[] = "종료일은 시작일보다 나중이어야 합니다.";
    }
    
    // 같은 기간에 이미 존재하는 예산 기간이 있는지 확인
    if (!empty($startDate) && !empty($endDate)) {
        $checkSql = "SELECT COUNT(*) as count FROM budget_periods 
                    WHERE (start_date <= ? AND end_date >= ?) 
                    OR (start_date <= ? AND end_date >= ?) 
                    OR (start_date >= ? AND end_date <= ?)";
        
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("ssssss", $endDate, $startDate, $endDate, $startDate, $startDate, $endDate);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $overlapCount = $checkResult->fetch_assoc()['count'];
        
        if ($overlapCount > 0) {
            $errors[] = "입력한 기간이 기존 예산 기간과 중복됩니다.";
        }
    }
    
    // 'active' 상태로 추가하려는 경우 다른 활성 기간이 있는지 확인
    if ($status === 'active') {
        $activeCheckSql = "SELECT COUNT(*) as count FROM budget_periods WHERE status = 'active'";
        $activeCheckResult = $conn->query($activeCheckSql);
        $activeCount = $activeCheckResult->fetch_assoc()['count'];
        
        if ($activeCount > 0) {
            $errors[] = "이미 활성화된 예산 기간이 있습니다. 먼저 기존 활성 기간을 종료해주세요.";
        }
    }
    
    // 오류가 없으면 새 예산 기간 추가
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO budget_periods (
                    period_name, 
                    start_date, 
                    end_date, 
                    status, 
                    notes, 
                    created_at
                ) VALUES (?, ?, ?, ?, ?, NOW())";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("데이터베이스 준비 오류: " . $conn->error);
            }
            
            $stmt->bind_param("sssss", $periodName, $startDate, $endDate, $status, $notes);
            
            $result = $stmt->execute();
            if (!$result) {
                throw new Exception("예산 기간 추가 중 오류가 발생했습니다: " . $stmt->error);
            }
            
            $newPeriodId = $stmt->insert_id;
            
            // 성공 메시지 설정
            setAlert('예산 기간이 성공적으로 추가되었습니다.', 'success');
            
            // 로그 기록
            logActivity('finance', 'budget_period_add', "예산 기간 추가: {$periodName}, {$startDate} ~ {$endDate}, 상태: {$status}");
            
            // 예산 할당 페이지로 이동 (바로 할당 설정을 할 수 있도록)
            redirectTo("budget-allocations.php?period_id={$newPeriodId}");
            
        } catch (Exception $e) {
            // 오류 로깅
            logError("예산 기간 추가 오류: " . $e->getMessage());
            
            // 오류 메시지 설정
            setAlert('예산 기간 추가 중 오류가 발생했습니다: ' . $e->getMessage(), 'error');
        }
    } else {
        // 폼 오류 메시지 설정
        setAlert('입력 양식에 오류가 있습니다: ' . implode(' ', $errors), 'error');
    }
}

// 기본 상태
$defaultStatus = 'planning'; // 기본적으로 계획 상태로 시작

// 페이지 제목 설정
$pageTitle = "예산 기간 추가";
$currentSection = "finance";
$currentPage = "budget";

// 헤더 및 네비게이션 포함
include '../../templates/header.php';
include '../../templates/navbar.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><?php echo $pageTitle; ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="../../dashboard/">대시보드</a></li>
                        <li class="breadcrumb-item"><a href="../">재무 관리</a></li>
                        <li class="breadcrumb-item"><a href="budget-periods.php">예산 기간</a></li>
                        <li class="breadcrumb-item active">예산 기간 추가</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-plus-circle mr-1"></i>
                                예산 기간 정보 입력
                            </h3>
                            <div class="card-tools">
                                <a href="budget-periods.php" class="btn btn-default btn-sm">
                                    <i class="fas fa-arrow-left mr-1"></i> 목록으로 돌아가기
                                </a>
                            </div>
                        </div>
                        <form id="addPeriodForm" method="post" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="period_name">예산 기간명 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="period_name" name="period_name" value="<?php echo isset($_POST['period_name']) ? htmlspecialchars($_POST['period_name']) : ''; ?>" required>
                                    <small class="form-text text-muted">예시: 2025년 1분기, 2025년 회계연도 등</small>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="start_date">시작일 <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : ''; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="end_date">종료일 <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : ''; ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="status">상태 <span class="text-danger">*</span></label>
                                    <select class="form-control" id="status" name="status" required>
                                        <option value="planning" <?php if (isset($_POST['status']) && $_POST['status'] === 'planning' || $defaultStatus === 'planning') echo 'selected'; ?>>계획 중</option>
                                        <option value="active" <?php if (isset($_POST['status']) && $_POST['status'] === 'active') echo 'selected'; ?>>활성</option>
                                        <option value="closed" <?php if (isset($_POST['status']) && $_POST['status'] === 'closed') echo 'selected'; ?>>종료</option>
                                    </select>
                                    <small class="form-text text-muted">활성 상태는 한 번에 하나만 가능합니다.</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="notes">비고</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                                </div>
                            </div>
                            
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i> 저장
                                </button>
                                <a href="budget-periods.php" class="btn btn-default">
                                    <i class="fas fa-times mr-1"></i> 취소
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-info-circle mr-1"></i>
                                도움말
                            </h3>
                        </div>
                        <div class="card-body">
                            <h5>예산 기간이란?</h5>
                            <p>예산 기간은 예산을 계획하고 집행하는 시간적 단위입니다. 분기별, 반기별 또는 연간 기간으로 설정할 수 있습니다.</p>
                            
                            <h5>상태 설명</h5>
                            <ul>
                                <li><strong>계획 중</strong>: 예산 기간이 아직 시작되지 않고 계획 중인 상태</li>
                                <li><strong>활성</strong>: 현재 진행 중인 예산 기간</li>
                                <li><strong>종료</strong>: 완료된 예산 기간</li>
                            </ul>
                            
                            <div class="alert alert-info mt-3">
                                <i class="icon fas fa-info-circle"></i>
                                예산 기간을 저장하면 바로 예산 할당 페이지로 이동합니다. 각 카테고리별 예산을 할당할 수 있습니다.
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-calendar-alt mr-1"></i>
                                일반적인 예산 기간 형식
                            </h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>유형</th>
                                        <th>기간명 예시</th>
                                        <th>기간</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>분기별</td>
                                        <td>2025년 1분기</td>
                                        <td>1월 1일 ~ 3월 31일</td>
                                    </tr>
                                    <tr>
                                        <td>반기별</td>
                                        <td>2025년 상반기</td>
                                        <td>1월 1일 ~ 6월 30일</td>
                                    </tr>
                                    <tr>
                                        <td>연간</td>
                                        <td>2025 회계연도</td>
                                        <td>1월 1일 ~ 12월 31일</td>
                                    </tr>
                                    <tr>
                                        <td>월간</td>
                                        <td>2025년 1월</td>
                                        <td>1월 1일 ~ 1월 31일</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('예산 기간 추가 페이지 로드됨');
    
    // 날짜 유효성 검증
    var startDateInput = document.getElementById('start_date');
    var endDateInput = document.getElementById('end_date');
    
    function validateDates() {
        var startDate = new Date(startDateInput.value);
        var endDate = new Date(endDateInput.value);
        
        if (startDate && endDate && startDate >= endDate) {
            endDateInput.setCustomValidity('종료일은 시작일보다 나중이어야 합니다.');
        } else {
            endDateInput.setCustomValidity('');
        }
    }
    
    startDateInput.addEventListener('change', validateDates);
    endDateInput.addEventListener('change', validateDates);
    
    // 자동 기간명 생성 도우미
    document.getElementById('start_date').addEventListener('change', function() {
        var periodNameInput = document.getElementById('period_name');
        var startDate = new Date(this.value);
        
        // 기간명이 비어있는 경우에만 자동 생성 제안
        if (periodNameInput.value.trim() === '' && startDate) {
            var year = startDate.getFullYear();
            var month = startDate.getMonth() + 1;
            var quarter = Math.ceil(month / 3);
            
            // 분기별 이름 제안
            var suggestedName = year + '년 ' + quarter + '분기';
            
            // 사용자에게 제안
            if (confirm('기간명을 "' + suggestedName + '"으로 설정하시겠습니까?')) {
                periodNameInput.value = suggestedName;
            }
        }
    });
    
    // 상태 변경 시 경고
    document.getElementById('status').addEventListener('change', function() {
        if (this.value === 'active') {
            alert('활성 상태는 한 번에 하나의 예산 기간만 가능합니다. 다른 활성 기간이 있는 경우 저장되지 않습니다.');
        }
    });
    
    // 폼 제출 전 최종 검증
    document.getElementById('addPeriodForm').addEventListener('submit', function(e) {
        validateDates();
        
        if (endDateInput.validity.customError) {
            e.preventDefault();
            alert(endDateInput.validationMessage);
        }
    });
});
</script>

<?php
// 푸터 포함
include '../../templates/footer.php';
?>