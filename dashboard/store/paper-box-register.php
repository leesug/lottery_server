<?php
// 용지박스 등록
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// 세션 시작
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 기본 사용자 정보 설정 (세션 관리가 비활성화되어 있으므로)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;  // 기본 관리자 ID
    $_SESSION['username'] = '관리자';
    $_SESSION['role'] = 'admin';
}

// 로그인 체크
checkLogin();

// 페이지 제목
$pageTitle = "용지박스 등록";
$currentSection = "store";
$currentPage = "paper-box-register.php";

// 데이터베이스 연결
$conn = get_db_connection();

// 미할당 롤 목록 조회
$unassignedRollsQuery = "
    SELECT 
        id, roll_code, qr_code, start_serial, end_serial, serial_count
    FROM paper_rolls
    WHERE box_id IS NULL AND status = 'registered'
    ORDER BY roll_code
";
$unassignedRolls = $conn->query($unassignedRollsQuery)->fetchAll();

// POST 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $success = false;
    
    $box_code = trim($_POST['box_code'] ?? '');
    // B로 시작하지 않으면 자동으로 추가
    if (!empty($box_code) && !str_starts_with($box_code, 'B')) {
        $box_code = 'B' . $box_code;
    }
    $qr_code = trim($_POST['qr_code'] ?? '');
    $serial_prefix = 'B';  // 일련번호 접두사를 B로 고정
    $notes = trim($_POST['notes'] ?? '');
    $selected_rolls = $_POST['selected_rolls'] ?? [];
    
    // 유효성 검증
    if (empty($box_code)) {
        $errors[] = "박스 코드를 입력해주세요.";
    }
    
    if (empty($qr_code)) {
        $errors[] = "QR 코드를 입력해주세요.";
    }
    
    if (empty($selected_rolls)) {
        $errors[] = "최소 1개 이상의 롤을 선택해주세요.";
    }
    
    // 중복 확인
    if (empty($errors)) {
        $checkQuery = "SELECT id FROM paper_boxes WHERE box_code = ? OR qr_code = ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->execute([$box_code, $qr_code]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "이미 등록된 박스 코드 또는 QR 코드입니다.";
        }
    }
    
    if (empty($errors)) {
        try {
            $conn->beginTransaction();
            
            // 박스 등록
            $insertBoxQuery = "
                INSERT INTO paper_boxes (
                    box_code, qr_code, serial_prefix, total_rolls,
                    status, notes, created_by
                ) VALUES (?, ?, ?, ?, 'registered', ?, ?)
            ";
            $stmt = $conn->prepare($insertBoxQuery);
            $stmt->execute([
                $box_code,
                $qr_code,
                $serial_prefix,
                count($selected_rolls),
                $notes,
                $_SESSION['user_id']
            ]);
            
            $box_id = $conn->lastInsertId();
            
            // 선택된 롤들을 박스에 할당
            $updateRollQuery = "UPDATE paper_rolls SET box_id = ? WHERE id = ? AND box_id IS NULL";
            $stmt = $conn->prepare($updateRollQuery);
            
            foreach ($selected_rolls as $roll_id) {
                $stmt->execute([$box_id, $roll_id]);
            }
            
            $conn->commit();
            
            $_SESSION['success_message'] = "용지박스가 성공적으로 등록되었습니다. (박스: {$box_code}, 롤: " . count($selected_rolls) . "개)";
            header('Location: paper-dashboard.php');
            exit;
            
        } catch (Exception $e) {
            $conn->rollBack();
            $errors[] = "처리 중 오류가 발생했습니다: " . $e->getMessage();
        }
    }
}

include_once TEMPLATES_PATH . '/dashboard_header.php';
?>

<!-- 컨텐츠 시작 -->
<div class="container-fluid">
    <!-- 페이지 헤더 -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h3 mb-0"><?php echo $pageTitle; ?></h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="/dashboard">대시보드</a></li>
                    <li class="breadcrumb-item"><a href="/dashboard/store">판매점 관리</a></li>
                    <li class="breadcrumb-item"><a href="paper-dashboard.php">용지 관리</a></li>
                    <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
                </ol>
            </nav>
        </div>
        <div class="col-md-6 text-end">
            <a href="paper-roll-register.php" class="btn btn-success">
                <i class="fas fa-scroll"></i> 롤 등록
            </a>
            <a href="paper-dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> 목록으로
            </a>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (empty($unassignedRolls)): ?>
        <div class="alert alert-warning">
            <h5 class="alert-heading">
                <i class="fas fa-exclamation-triangle"></i> 등록 가능한 롤이 없습니다
            </h5>
            <p>박스에 할당할 수 있는 롤이 없습니다. 먼저 롤을 등록해주세요.</p>
            <hr>
            <a href="paper-roll-register.php" class="btn btn-primary">
                <i class="fas fa-scroll"></i> 롤 등록하기
            </a>
        </div>
    <?php else: ?>
        <!-- 등록 폼 -->
        <form method="post" action="">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">박스 정보</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="box_code" class="form-label">박스 코드 <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">B</span>
                                    <input type="text" name="box_code" id="box_code" class="form-control" 
                                           placeholder="20250122001" pattern="[0-9]+" required>
                                </div>
                                <small class="text-muted">박스 코드는 B로 시작하며, 숫자만 입력하세요.</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="qr_code" class="form-label">QR 코드 <span class="text-danger">*</span></label>
                                <input type="text" name="qr_code" id="qr_code" class="form-control" 
                                       placeholder="QR 코드 데이터" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="notes" class="form-label">비고</label>
                                <input type="text" name="notes" id="notes" class="form-control" 
                                       placeholder="메모 사항">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 롤 선택 -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">롤 선택</h6>
                    <div>
                        <span class="badge bg-info me-2">
                            선택된 롤: <span id="selected_count">0</span>개
                        </span>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllRolls()">
                            <i class="fas fa-check-square"></i> 전체 선택
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAllRolls()">
                            <i class="fas fa-square"></i> 선택 해제
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        박스에 포함할 롤을 선택해주세요. 일반적으로 8~10개의 롤이 하나의 박스에 포함됩니다.
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="5%" class="text-center">
                                        <input type="checkbox" id="select_all" onchange="toggleAllRolls()">
                                    </th>
                                    <th width="20%">롤 코드</th>
                                    <th width="25%">QR 코드</th>
                                    <th width="20%">시작 번호</th>
                                    <th width="20%">종료 번호</th>
                                    <th width="10%" class="text-center">개수</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($unassignedRolls as $roll): ?>
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox" name="selected_rolls[]" 
                                                   value="<?php echo $roll['id']; ?>" 
                                                   onchange="updateSelectedCount()">
                                        </td>
                                        <td><?php echo htmlspecialchars($roll['roll_code']); ?></td>
                                        <td><?php echo htmlspecialchars($roll['qr_code']); ?></td>
                                        <td><?php echo $roll['start_serial']; ?></td>
                                        <td><?php echo $roll['end_serial']; ?></td>
                                        <td class="text-center"><?php echo number_format($roll['serial_count']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="text-center">
                <button type="button" class="btn btn-secondary" onclick="history.back()">취소</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> 등록
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
// 선택된 롤 개수 업데이트
function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('input[name="selected_rolls[]"]:checked');
    document.getElementById('selected_count').textContent = checkboxes.length;
}

// 전체 선택/해제 토글
function toggleAllRolls() {
    const selectAll = document.getElementById('select_all');
    const checkboxes = document.querySelectorAll('input[name="selected_rolls[]"]');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
    
    updateSelectedCount();
}

// 전체 선택
function selectAllRolls() {
    const checkboxes = document.querySelectorAll('input[name="selected_rolls[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = true;
    });
    document.getElementById('select_all').checked = true;
    updateSelectedCount();
}

// 전체 해제
function deselectAllRolls() {
    const checkboxes = document.querySelectorAll('input[name="selected_rolls[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    document.getElementById('select_all').checked = false;
    updateSelectedCount();
}
</script>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
