<?php
/**
 * 재무 관리 - 예산 할당 페이지
 * 
 * 이 페이지는 특정 예산 기간에 대한 카테고리별 예산 할당을 관리하는 기능을 제공합니다.
 */

// 세션 시작 및 필수 파일 포함
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 인증 확인
checkAuth();

// 필요한 권한 확인
$requiredPermissions = ['finance_budget_allocations'];
checkPermissions($requiredPermissions);

// 기간 ID 확인
if (!isset($_GET['period_id']) || !is_numeric($_GET['period_id'])) {
    setAlert('기간 ID가 유효하지 않습니다.', 'error');
    redirectTo('budget-periods.php');
}

$periodId = intval($_GET['period_id']);

// 데이터베이스 연결
$conn = getDBConnection();

// 예산 기간 정보 조회
$sql = "SELECT * FROM budget_periods WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    logError("Database prepare error: " . $conn->error);
    die("데이터베이스 오류가 발생했습니다.");
}

$stmt->bind_param("i", $periodId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setAlert('존재하지 않는 예산 기간입니다.', 'error');
    redirectTo('budget-periods.php');
}

$period = $result->fetch_assoc();

// 종료된 기간은 편집 불가
if ($period['status'] === 'closed') {
    setAlert('종료된 예산 기간은 예산 할당을 수정할 수 없습니다.', 'error');
    redirectTo("budget-period-details.php?id={$periodId}");
}

// 예산 카테고리 조회
$categoriesSql = "SELECT * FROM financial_categories 
                 WHERE is_active = 1 
                 ORDER BY category_type, category_name";
$categoriesResult = $conn->query($categoriesSql);
$categories = [];

while ($row = $categoriesResult->fetch_assoc()) {
    $categories[] = $row;
}

// 기존 예산 할당 조회
$allocationsSql = "SELECT * FROM budget_allocations WHERE period_id = ?";
$allocationsStmt = $conn->prepare($allocationsSql);
$allocationsStmt->bind_param("i", $periodId);
$allocationsStmt->execute();
$allocationsResult = $allocationsStmt->get_result();
$allocations = [];

while ($row = $allocationsResult->fetch_assoc()) {
    $allocations[$row['category_id']] = $row;
}

// 예산 할당 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF 토큰 검증
    verifyCsrfToken($_POST['csrf_token']);
    
    // 할당 데이터 검증
    $categoryIds = isset($_POST['category_id']) ? $_POST['category_id'] : [];
    $allocatedAmounts = isset($_POST['allocated_amount']) ? $_POST['allocated_amount'] : [];
    $notes = isset($_POST['notes']) ? $_POST['notes'] : [];
    
    // 트랜잭션 시작
    $conn->begin_transaction();
    
    try {
        foreach ($categoryIds as $index => $categoryId) {
            $categoryId = intval($categoryId);
            $allocatedAmount = floatval($allocatedAmounts[$index]);
            $note = sanitizeInput($notes[$index] ?? '');
            
            // 이미 할당된 카테고리인지 확인
            $existingAllocationSql = "SELECT id FROM budget_allocations 
                                    WHERE period_id = ? AND category_id = ?";
            $existingAllocationStmt = $conn->prepare($existingAllocationSql);
            $existingAllocationStmt->bind_param("ii", $periodId, $categoryId);
            $existingAllocationStmt->execute();
            $existingAllocationResult = $existingAllocationStmt->get_result();
            
            if ($existingAllocationResult->num_rows > 0) {
                // 기존 할당 업데이트
                $existingAllocation = $existingAllocationResult->fetch_assoc();
                $allocationId = $existingAllocation['id'];
                
                $updateSql = "UPDATE budget_allocations SET 
                            allocated_amount = ?, 
                            notes = ?, 
                            updated_at = NOW()
                            WHERE id = ?";
                
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("dsi", $allocatedAmount, $note, $allocationId);
                $updateResult = $updateStmt->execute();
                
                if (!$updateResult) {
                    throw new Exception("예산 할당 업데이트 중 오류가 발생했습니다: " . $updateStmt->error);
                }
            } else {
                // 새 할당 추가
                $insertSql = "INSERT INTO budget_allocations (
                            period_id, 
                            category_id, 
                            allocated_amount, 
                            utilized_amount, 
                            notes, 
                            created_at
                        ) VALUES (?, ?, ?, 0, ?, NOW())";
                
                $insertStmt = $conn->prepare($insertSql);
                $insertStmt->bind_param("iids", $periodId, $categoryId, $allocatedAmount, $note);
                $insertResult = $insertStmt->execute();
                
                if (!$insertResult) {
                    throw new Exception("예산 할당 추가 중 오류가 발생했습니다: " . $insertStmt->error);
                }
            }
        }
        
        // 트랜잭션 커밋
        $conn->commit();
        
        // 성공 메시지 설정
        setAlert('예산 할당이 성공적으로 저장되었습니다.', 'success');
        
        // 로그 기록
        logActivity('finance', 'budget_allocations_save', "예산 할당 저장: 기간 ID {$periodId}, 기간명 {$period['period_name']}");
        
        // 예산 기간 상세 페이지로 리디렉션
        redirectTo("budget-period-details.php?id={$periodId}");
        
    } catch (Exception $e) {
        // 트랜잭션 롤백
        $conn->rollback();
        
        // 오류 로깅
        logError("예산 할당 저장 오류: " . $e->getMessage());
        
        // 오류 메시지 설정
        setAlert('예산 할당 저장 중 오류가 발생했습니다: ' . $e->getMessage(), 'error');
    }
}

// 페이지 제목 설정
$pageTitle = "예산 할당 관리";
$currentSection = "finance";
$currentPage = "budget";

// 헤더 및 네비게이션 포함
include '../../templates/header.php';
include '../../templates/navbar.php';

// 카테고리 유형 한글명 반환 함수
function getCategoryTypeLabel($type) {
    $labels = [
        'income' => '수입',
        'expense' => '지출',
        'both' => '수입/지출'
    ];
    
    return isset($labels[$type]) ? $labels[$type] : ucfirst($type);
}
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
                        <li class="breadcrumb-item"><a href="budget-period-details.php?id=<?php echo $periodId; ?>"><?php echo htmlspecialchars($period['period_name']); ?></a></li>
                        <li class="breadcrumb-item active">예산 할당</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-money-bill-wave mr-1"></i>
                                <?php echo htmlspecialchars($period['period_name']); ?> 예산 할당
                            </h3>
                            <div class="card-tools">
                                <a href="budget-period-details.php?id=<?php echo $periodId; ?>" class="btn btn-default btn-sm">
                                    <i class="fas fa-arrow-left mr-1"></i> 예산 기간으로 돌아가기
                                </a>
                            </div>
                        </div>
                        
                        <form id="allocationForm" method="post" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            
                            <div class="card-body">
                                <?php if (empty($categories)): ?>
                                <div class="alert alert-warning">
                                    <i class="icon fas fa-exclamation-triangle"></i>
                                    활성화된 재무 카테고리가 없습니다. 먼저 <a href="financial-categories.php">재무 카테고리 관리</a> 페이지에서 카테고리를 추가해주세요.
                                </div>
                                <?php else: ?>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <div class="alert alert-info">
                                                <i class="icon fas fa-info-circle"></i>
                                                <strong><?php echo htmlspecialchars($period['period_name']); ?></strong><br>
                                                <?php echo date('Y-m-d', strtotime($period['start_date'])); ?> ~ <?php echo date('Y-m-d', strtotime($period['end_date'])); ?><br>
                                                상태: <?php echo getStatusBadge($period['status'])['label']; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6 text-right">
                                            <button type="button" class="btn btn-primary mb-2" id="addCategoryBtn">
                                                <i class="fas fa-plus mr-1"></i> 카테고리 추가
                                            </button>
                                            <button type="button" class="btn btn-info mb-2 ml-2" id="toggleAllBtn">
                                                <i class="fas fa-eye mr-1"></i> 모두 보기/숨기기
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div id="allocation-container">
                                        <!-- 지출 카테고리 -->
                                        <h4 class="mt-4">지출 카테고리</h4>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-hover" id="expense-table">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 30%">카테고리</th>
                                                        <th style="width: 20%">할당액 (NPR)</th>
                                                        <th style="width: 20%">사용액 (NPR)</th>
                                                        <th style="width: 25%">비고</th>
                                                        <th style="width: 5%">액션</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                <?php
                                                $expenseCategories = array_filter($categories, function($c) {
                                                    return $c['category_type'] === 'expense' || $c['category_type'] === 'both';
                                                });
                                                
                                                $hasExpenseAllocation = false;
                                                foreach ($expenseCategories as $category) {
                                                    $allocation = isset($allocations[$category['id']]) ? $allocations[$category['id']] : null;
                                                    
                                                    if ($allocation) {
                                                        $hasExpenseAllocation = true;
                                                        ?>
                                                        <tr class="allocation-row">
                                                            <td>
                                                                <?php echo htmlspecialchars($category['category_name']); ?>
                                                                <input type="hidden" name="category_id[]" value="<?php echo $category['id']; ?>">
                                                            </td>
                                                            <td>
                                                                <input type="number" step="0.01" min="0" class="form-control" name="allocated_amount[]" value="<?php echo $allocation['allocated_amount']; ?>" required>
                                                            </td>
                                                            <td>
                                                                <input type="number" step="0.01" min="0" class="form-control" value="<?php echo $allocation['utilized_amount']; ?>" readonly>
                                                            </td>
                                                            <td>
                                                                <input type="text" class="form-control" name="notes[]" value="<?php echo htmlspecialchars($allocation['notes'] ?? ''); ?>">
                                                            </td>
                                                            <td>
                                                                <button type="button" class="btn btn-danger btn-sm remove-btn">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                        <?php
                                                    }
                                                }
                                                
                                                if (!$hasExpenseAllocation) {
                                                    echo '<tr><td colspan="5" class="text-center">할당된 지출 카테고리가 없습니다.</td></tr>';
                                                }
                                                ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <!-- 수입 카테고리 -->
                                        <h4 class="mt-4">수입 카테고리</h4>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-hover" id="income-table">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 30%">카테고리</th>
                                                        <th style="width: 20%">예상액 (NPR)</th>
                                                        <th style="width: 20%">실현액 (NPR)</th>
                                                        <th style="width: 25%">비고</th>
                                                        <th style="width: 5%">액션</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                <?php
                                                $incomeCategories = array_filter($categories, function($c) {
                                                    return $c['category_type'] === 'income' || $c['category_type'] === 'both';
                                                });
                                                
                                                $hasIncomeAllocation = false;
                                                foreach ($incomeCategories as $category) {
                                                    $allocation = isset($allocations[$category['id']]) ? $allocations[$category['id']] : null;
                                                    
                                                    if ($allocation) {
                                                        $hasIncomeAllocation = true;
                                                        ?>
                                                        <tr class="allocation-row">
                                                            <td>
                                                                <?php echo htmlspecialchars($category['category_name']); ?>
                                                                <input type="hidden" name="category_id[]" value="<?php echo $category['id']; ?>">
                                                            </td>
                                                            <td>
                                                                <input type="number" step="0.01" min="0" class="form-control" name="allocated_amount[]" value="<?php echo $allocation['allocated_amount']; ?>" required>
                                                            </td>
                                                            <td>
                                                                <input type="number" step="0.01" min="0" class="form-control" value="<?php echo $allocation['utilized_amount']; ?>" readonly>
                                                            </td>
                                                            <td>
                                                                <input type="text" class="form-control" name="notes[]" value="<?php echo htmlspecialchars($allocation['notes'] ?? ''); ?>">
                                                            </td>
                                                            <td>
                                                                <button type="button" class="btn btn-danger btn-sm remove-btn">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                        <?php
                                                    }
                                                }
                                                
                                                if (!$hasIncomeAllocation) {
                                                    echo '<tr><td colspan="5" class="text-center">할당된 수입 카테고리가 없습니다.</td></tr>';
                                                }
                                                ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i> 저장
                                </button>
                                <a href="budget-period-details.php?id=<?php echo $periodId; ?>" class="btn btn-default">
                                    <i class="fas fa-times mr-1"></i> 취소
                                </a>
                                <button type="button" class="btn btn-success float-right" id="calculateTotalBtn">
                                    <i class="fas fa-calculator mr-1"></i> 총액 계산
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 카테고리 추가 모달 -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">카테고리 추가</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>카테고리 유형</label>
                    <select class="form-control" id="filterCategoryType">
                        <option value="all">모든 유형</option>
                        <option value="expense">지출</option>
                        <option value="income">수입</option>
                        <option value="both">수입/지출</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>카테고리 검색</label>
                    <input type="text" class="form-control" id="searchCategory" placeholder="카테고리명 검색...">
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="categoryTable">
                        <thead>
                            <tr>
                                <th>카테고리명</th>
                                <th>유형</th>
                                <th>설명</th>
                                <th>액션</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // 현재 할당되지 않은 카테고리만 표시
                            foreach ($categories as $category) {
                                // 이미 할당된 카테고리는 제외
                                if (isset($allocations[$category['id']])) {
                                    continue;
                                }
                                ?>
                                <tr data-category-id="<?php echo $category['id']; ?>" data-category-type="<?php echo $category['category_type']; ?>">
                                    <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                                    <td><?php echo getCategoryTypeLabel($category['category_type']); ?></td>
                                    <td><?php echo htmlspecialchars($category['description'] ?? ''); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-primary btn-sm add-category-btn" 
                                            data-id="<?php echo $category['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($category['category_name']); ?>"
                                            data-type="<?php echo $category['category_type']; ?>">
                                            <i class="fas fa-plus"></i> 추가
                                        </button>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (count($categories) === count($allocations)): ?>
                <div class="alert alert-info mt-3">
                    <i class="icon fas fa-info-circle"></i>
                    모든 카테고리가 이미 이 예산 기간에 할당되어 있습니다.
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
            </div>
        </div>
    </div>
</div>

<!-- 총액 계산 모달 -->
<div class="modal fade" id="totalCalculationModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">예산 총액 계산</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-box bg-info">
                            <div class="info-box-content">
                                <span class="info-box-text">총 수입 예산</span>
                                <span class="info-box-number" id="totalIncome">0.00 NPR</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-box bg-danger">
                            <div class="info-box-content">
                                <span class="info-box-text">총 지출 예산</span>
                                <span class="info-box-number" id="totalExpense">0.00 NPR</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="info-box bg-success">
                            <div class="info-box-content">
                                <span class="info-box-text">예상 잔액 (수입 - 지출)</span>
                                <span class="info-box-number" id="balanceAmount">0.00 NPR</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('예산 할당 페이지 로드됨: 기간 ID <?php echo $periodId; ?>');
    
    // 카테고리 추가 버튼 클릭 이벤트
    document.getElementById('addCategoryBtn').addEventListener('click', function() {
        $('#addCategoryModal').modal('show');
    });
    
    // 총액 계산 버튼 클릭 이벤트
    document.getElementById('calculateTotalBtn').addEventListener('click', function() {
        calculateTotals();
        $('#totalCalculationModal').modal('show');
    });
    
    // 행 제거 버튼 클릭 이벤트
    document.querySelectorAll('.remove-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            if (confirm('이 카테고리의 예산 할당을 제거하시겠습니까?')) {
                this.closest('tr').remove();
            }
        });
    });
    
    // 카테고리 필터링 이벤트
    document.getElementById('filterCategoryType').addEventListener('change', function() {
        filterCategories();
    });
    
    // 카테고리 검색 이벤트
    document.getElementById('searchCategory').addEventListener('input', function() {
        filterCategories();
    });
    
    // 모두 보기/숨기기 토글 버튼
    document.getElementById('toggleAllBtn').addEventListener('click', function() {
        var rows = document.querySelectorAll('.allocation-row');
        var isHidden = rows.length > 0 && rows[0].style.display === 'none';
        
        rows.forEach(function(row) {
            row.style.display = isHidden ? '' : 'none';
        });
    });
    
    // 카테고리 필터링 함수
    function filterCategories() {
        var typeFilter = document.getElementById('filterCategoryType').value;
        var searchText = document.getElementById('searchCategory').value.toLowerCase();
        
        document.querySelectorAll('#categoryTable tbody tr').forEach(function(row) {
            var categoryType = row.getAttribute('data-category-type');
            var categoryName = row.querySelector('td:first-child').textContent.toLowerCase();
            
            var typeMatch = typeFilter === 'all' || categoryType === typeFilter || 
                           (typeFilter === 'expense' && categoryType === 'both') || 
                           (typeFilter === 'income' && categoryType === 'both');
                           
            var searchMatch = searchText === '' || categoryName.includes(searchText);
            
            row.style.display = (typeMatch && searchMatch) ? '' : 'none';
        });
    }
    
    // 카테고리 추가 이벤트
    document.querySelectorAll('.add-category-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            var categoryId = this.getAttribute('data-id');
            var categoryName = this.getAttribute('data-name');
            var categoryType = this.getAttribute('data-type');
            
            // 지출 또는 지출/수입 카테고리인 경우
            if (categoryType === 'expense' || categoryType === 'both') {
                addCategoryRow('expense-table', categoryId, categoryName);
            }
            
            // 수입 또는 지출/수입 카테고리인 경우
            if (categoryType === 'income' || categoryType === 'both') {
                addCategoryRow('income-table', categoryId, categoryName);
            }
            
            // 모달 닫기
            $('#addCategoryModal').modal('hide');
            
            // 해당 행 숨기기 (이미 추가됨)
            var row = this.closest('tr');
            row.style.display = 'none';
        });
    });
    
    // 카테고리 행 추가 함수
    function addCategoryRow(tableId, categoryId, categoryName) {
        var table = document.getElementById(tableId);
        var tbody = table.querySelector('tbody');
        
        // 첫 번째 행이 "할당된 카테고리가 없습니다" 메시지인 경우 제거
        if (tbody.rows.length === 1 && tbody.rows[0].cells.length === 1 && 
            tbody.rows[0].cells[0].colSpan === 5) {
            tbody.innerHTML = '';
        }
        
        // 새 행 생성
        var newRow = document.createElement('tr');
        newRow.className = 'allocation-row';
        
        // 행 내용 설정
        newRow.innerHTML = `
            <td>
                ${categoryName}
                <input type="hidden" name="category_id[]" value="${categoryId}">
            </td>
            <td>
                <input type="number" step="0.01" min="0" class="form-control" name="allocated_amount[]" value="0.00" required>
            </td>
            <td>
                <input type="number" step="0.01" min="0" class="form-control" value="0.00" readonly>
            </td>
            <td>
                <input type="text" class="form-control" name="notes[]" value="">
            </td>
            <td>
                <button type="button" class="btn btn-danger btn-sm remove-btn">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        
        // 제거 버튼 이벤트 추가
        var removeBtn = newRow.querySelector('.remove-btn');
        removeBtn.addEventListener('click', function() {
            if (confirm('이 카테고리의 예산 할당을 제거하시겠습니까?')) {
                newRow.remove();
                
                // 테이블이 비었을 경우 메시지 추가
                if (tbody.rows.length === 0) {
                    var messageRow = document.createElement('tr');
                    var messageCell = document.createElement('td');
                    messageCell.colSpan = 5;
                    messageCell.textContent = '할당된 카테고리가 없습니다.';
                    messageCell.className = 'text-center';
                    messageRow.appendChild(messageCell);
                    tbody.appendChild(messageRow);
                }
            }
        });
        
        // 테이블에 추가
        tbody.appendChild(newRow);
    }
    
    // 총액 계산 함수
    function calculateTotals() {
        var totalIncome = 0;
        var totalExpense = 0;
        
        // 수입 테이블 합계 계산
        document.querySelectorAll('#income-table tbody tr.allocation-row').forEach(function(row) {
            var amount = parseFloat(row.querySelector('input[name="allocated_amount[]"]').value) || 0;
            totalIncome += amount;
        });
        
        // 지출 테이블 합계 계산
        document.querySelectorAll('#expense-table tbody tr.allocation-row').forEach(function(row) {
            var amount = parseFloat(row.querySelector('input[name="allocated_amount[]"]').value) || 0;
            totalExpense += amount;
        });
        
        // 잔액 계산
        var balance = totalIncome - totalExpense;
        
        // 표시 업데이트
        document.getElementById('totalIncome').textContent = totalIncome.toLocaleString() + ' NPR';
        document.getElementById('totalExpense').textContent = totalExpense.toLocaleString() + ' NPR';
        document.getElementById('balanceAmount').textContent = balance.toLocaleString() + ' NPR';
        
        // 잔액이 음수인 경우 스타일 변경
        var balanceElement = document.getElementById('balanceAmount');
        if (balance < 0) {
            balanceElement.parentElement.parentElement.className = 'info-box bg-danger';
        } else {
            balanceElement.parentElement.parentElement.className = 'info-box bg-success';
        }
    }
});

// 상태 한글명 및 배지 색상 반환 함수
function getStatusBadge(status) {
    var badges = {
        'planning': '계획 중',
        'active': '활성',
        'closed': '종료'
    };
    
    return badges[status] || status;
}
</script>

<?php
// 푸터 포함
include '../../templates/footer.php';
?>