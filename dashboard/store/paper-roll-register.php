<?php
// 용지롤 등록
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// 로그인 체크
checkLogin();

// 페이지 제목
$pageTitle = "용지롤 등록";
$currentSection = "store";
$currentPage = "paper-roll-register.php";

// 데이터베이스 연결
$conn = get_db_connection();

// 삭제 처리
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $roll_id = intval($_GET['id']);
    
    try {
        // 박스에 할당되지 않은 롤인지 확인
        $checkQuery = "SELECT roll_code, box_id FROM paper_rolls WHERE id = ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->execute([$roll_id]);
        $roll = $stmt->fetch();
        
        if (!$roll) {
            $_SESSION['error_message'] = "해당 롤을 찾을 수 없습니다.";
        } elseif ($roll['box_id'] != null) {
            $_SESSION['error_message'] = "박스에 할당된 롤은 삭제할 수 없습니다.";
        } else {
            // 삭제 실행
            $deleteQuery = "DELETE FROM paper_rolls WHERE id = ? AND box_id IS NULL";
            $stmt = $conn->prepare($deleteQuery);
            $stmt->execute([$roll_id]);
            
            if ($stmt->rowCount() > 0) {
                $_SESSION['success_message'] = "롤 '{$roll['roll_code']}'이(가) 삭제되었습니다.";
            } else {
                $_SESSION['error_message'] = "롤 삭제에 실패했습니다.";
            }
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = "삭제 중 오류가 발생했습니다: " . $e->getMessage();
    }
    
    header('Location: paper-roll-register.php');
    exit;
}

// CSV 업로드 처리
if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == UPLOAD_ERR_OK) {
    $errors = [];
    $success_count = 0;
    $total_count = 0;
    
    $tmpName = $_FILES['csv_file']['tmp_name'];
    $csvData = file_get_contents($tmpName);
    $lines = explode("\n", $csvData);
    
    // BOM 제거
    $lines[0] = str_replace("\xEF\xBB\xBF", '', $lines[0]);
    
    // 헤더 확인
    $header = str_getcsv($lines[0]);
    $expectedHeaders = ['roll_code', 'qr_code', 'start_serial', 'end_serial'];
    
    $headerValid = true;
    foreach ($expectedHeaders as $expectedHeader) {
        if (!in_array($expectedHeader, array_map('trim', array_map('strtolower', $header)))) {
            $headerValid = false;
            break;
        }
    }
    
    if (!$headerValid) {
        $errors[] = "CSV 파일 형식이 올바르지 않습니다. 필수 컬럼: roll_code, qr_code, start_serial, end_serial";
    } else {
        $conn->beginTransaction();
        
        try {
            for ($i = 1; $i < count($lines); $i++) {
                if (trim($lines[$i]) === '') continue;
                
                $data = str_getcsv($lines[$i]);
                if (count($data) < 4) continue;
                
                $total_count++;
                
                $roll_code = trim($data[0]);
                $qr_code = trim($data[1]);
                $start_serial = str_pad(trim($data[2]), 10, '0', STR_PAD_LEFT);
                $end_serial = str_pad(trim($data[3]), 10, '0', STR_PAD_LEFT);
                
                // 유효성 검증
                if (empty($roll_code) || empty($qr_code) || empty($start_serial) || empty($end_serial)) {
                    $errors[] = "라인 {$i}: 필수 항목이 누락되었습니다.";
                    continue;
                }
                
                if ($start_serial > $end_serial) {
                    $errors[] = "라인 {$i}: 시작 번호가 종료 번호보다 큽니다.";
                    continue;
                }
                
                // 중복 확인 - 각각 체크하여 구체적인 오류 메시지 제공
                $duplicateChecks = [
                    ['query' => "SELECT roll_code FROM paper_rolls WHERE roll_code = ?", 'params' => [$roll_code], 'message' => "롤 코드가 중복됩니다"],
                    ['query' => "SELECT qr_code FROM paper_rolls WHERE qr_code = ?", 'params' => [$qr_code], 'message' => "QR 코드가 중복됩니다"],
                    ['query' => "SELECT roll_code FROM paper_rolls WHERE start_serial = ? AND end_serial = ?", 'params' => [$start_serial, $end_serial], 'message' => "일련번호 범위가 중복됩니다"]
                ];
                
                $isDuplicate = false;
                foreach ($duplicateChecks as $check) {
                    $stmt = $conn->prepare($check['query']);
                    $stmt->execute($check['params']);
                    if ($stmt->rowCount() > 0) {
                        $existingData = $stmt->fetch();
                        $errors[] = "라인 {$i}: {$check['message']} ({$roll_code}: {$start_serial}~{$end_serial})";
                        $isDuplicate = true;
                        break;
                    }
                }
                
                if ($isDuplicate) {
                    continue;
                }
                
                // 롤 등록
                $serial_count = intval($end_serial) - intval($start_serial) + 1;
                
                $insertQuery = "
                    INSERT INTO paper_rolls (
                        roll_code, qr_code, start_serial, end_serial,
                        serial_count, status
                    ) VALUES (?, ?, ?, ?, ?, 'registered')
                ";
                $stmt = $conn->prepare($insertQuery);
                $stmt->execute([
                    $roll_code,
                    $qr_code,
                    $start_serial,
                    $end_serial,
                    $serial_count
                ]);
                
                $success_count++;
            }
            
            $conn->commit();
            $_SESSION['success_message'] = "CSV 업로드 완료: 전체 {$total_count}개 중 {$success_count}개 롤이 등록되었습니다.";
            
        } catch (Exception $e) {
            $conn->rollBack();
            $errors[] = "처리 중 오류가 발생했습니다: " . $e->getMessage();
        }
    }
}

// POST 처리 (개별 등록)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_FILES['csv_file'])) {
    $errors = [];
    
    $roll_code = trim($_POST['roll_code'] ?? '');
    // R 접두사 처리 - 중복 방지
    if (!empty($roll_code)) {
        if (substr($roll_code, 0, 1) !== 'R') {
            $roll_code = 'R' . $roll_code;
        } elseif (substr($roll_code, 0, 2) === 'RR') {
            $roll_code = substr($roll_code, 1); // RR을 R로 변경
        }
    }
    
    $qr_code = trim($_POST['qr_code'] ?? '');
    $start_serial = str_pad(trim($_POST['start_serial'] ?? ''), 10, '0', STR_PAD_LEFT);
    $end_serial = str_pad(trim($_POST['end_serial'] ?? ''), 10, '0', STR_PAD_LEFT);
    $notes = trim($_POST['notes'] ?? '');
    
    // 유효성 검증
    if (empty($roll_code)) {
        $errors[] = "롤 코드를 입력해주세요.";
    }
    
    if (empty($qr_code)) {
        $errors[] = "QR 코드를 입력해주세요.";
    }
    
    if (empty($start_serial) || empty($end_serial)) {
        $errors[] = "일련번호 범위를 입력해주세요.";
    }
    
    if ($start_serial > $end_serial) {
        $errors[] = "시작 번호가 종료 번호보다 클 수 없습니다.";
    }
    
    // 중복 확인
    if (empty($errors)) {
        $checkQuery = "SELECT id FROM paper_rolls WHERE roll_code = ? OR qr_code = ? OR (start_serial = ? AND end_serial = ?)";
        $stmt = $conn->prepare($checkQuery);
        $stmt->execute([$roll_code, $qr_code, $start_serial, $end_serial]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "이미 등록된 롤 코드, QR 코드 또는 일련번호 범위입니다.";
        }
    }
    
    if (empty($errors)) {
        try {
            $serial_count = intval($end_serial) - intval($start_serial) + 1;
            
            error_log("Attempting to insert roll: $roll_code");
            
            $insertQuery = "
                INSERT INTO paper_rolls (
                    roll_code, qr_code, start_serial, end_serial,
                    serial_count, status, notes
                ) VALUES (?, ?, ?, ?, ?, 'registered', ?)
            ";
            $stmt = $conn->prepare($insertQuery);
            $result = $stmt->execute([
                $roll_code,
                $qr_code,
                $start_serial,
                $end_serial,
                $serial_count,
                $notes
            ]);
            
            error_log("Insert result: " . ($result ? "SUCCESS" : "FAILED"));
            error_log("Last insert ID: " . $conn->lastInsertId());
            
            $_SESSION['success_message'] = "용지롤이 성공적으로 등록되었습니다. (롤: {$roll_code}, 번호: {$start_serial} ~ {$end_serial})";
            header('Location: paper-roll-register.php');
            exit;
            
        } catch (Exception $e) {
            error_log("Exception during roll insert: " . $e->getMessage());
            $errors[] = "처리 중 오류가 발생했습니다: " . $e->getMessage();
        }
    }
}

// 미할당 롤 목록 조회
$unassignedRollsQuery = "
    SELECT 
        id, roll_code, qr_code, start_serial, end_serial, serial_count,
        created_at
    FROM paper_rolls
    WHERE box_id IS NULL AND status = 'registered'
    ORDER BY created_at DESC
    LIMIT 50
";
error_log("Unassigned rolls query: " . $unassignedRollsQuery);
$unassignedRolls = $conn->query($unassignedRollsQuery)->fetchAll();
error_log("Found " . count($unassignedRolls) . " unassigned rolls");

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
            <a href="paper-dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> 목록으로
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?php 
            echo $_SESSION['success_message']; 
            unset($_SESSION['success_message']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <?php 
            echo $_SESSION['error_message']; 
            unset($_SESSION['error_message']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- 탭 메뉴 -->
    <ul class="nav nav-tabs mb-4" id="registerTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="single-tab" data-bs-toggle="tab" 
                    data-bs-target="#single" type="button" role="tab">
                <i class="fas fa-edit"></i> 개별 등록
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="bulk-tab" data-bs-toggle="tab" 
                    data-bs-target="#bulk" type="button" role="tab">
                <i class="fas fa-file-csv"></i> CSV 대량 등록
            </button>
        </li>
    </ul>

    <!-- 탭 내용 -->
    <div class="tab-content" id="registerTabContent">
        <!-- 개별 등록 탭 -->
        <div class="tab-pane fade show active" id="single" role="tabpanel">
            <form method="post" action="">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">롤 정보 입력</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="roll_code" class="form-label">롤 코드 <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">R</span>
                                        <input type="text" name="roll_code" id="roll_code" class="form-control" 
                                               placeholder="예: 20250122001" required>
                                    </div>
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
                            <div class="col-md-5">
                                <div class="mb-3">
                                    <label for="start_serial" class="form-label">시작 번호 <span class="text-danger">*</span></label>
                                    <input type="text" name="start_serial" id="start_serial" class="form-control" 
                                           placeholder="0000000001" maxlength="10" required onchange="updateSerialInfo()">
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="mb-3">
                                    <label for="end_serial" class="form-label">종료 번호 <span class="text-danger">*</span></label>
                                    <input type="text" name="end_serial" id="end_serial" class="form-control" 
                                           placeholder="0000000900" maxlength="10" required onchange="updateSerialInfo()">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label class="form-label">개수</label>
                                    <input type="text" id="serial_count" class="form-control" readonly value="-">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="notes" class="form-label">비고</label>
                                    <textarea name="notes" id="notes" class="form-control" rows="2" 
                                              placeholder="메모 사항"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            일련번호는 10자리로 입력해주세요. 짧은 번호는 자동으로 앞에 0이 채워집니다.
                        </div>
                    </div>
                    <div class="card-footer text-center">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 등록
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> 초기화
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- CSV 대량 등록 탭 -->
        <div class="tab-pane fade" id="bulk" role="tabpanel">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">CSV 파일 업로드</h6>
                </div>
                <div class="card-body">
                    <form method="post" action="" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label for="csv_file" class="form-label">CSV 파일 선택</label>
                            <input type="file" name="csv_file" id="csv_file" class="form-control" 
                                   accept=".csv" required>
                        </div>

                        <div class="alert alert-warning">
                            <h6 class="alert-heading">
                                <i class="fas fa-exclamation-triangle"></i> CSV 파일 형식
                            </h6>
                            <p>CSV 파일은 다음과 같은 형식이어야 합니다:</p>
                            <ul>
                                <li>첫 번째 행: 헤더 (roll_code, qr_code, start_serial, end_serial)</li>
                                <li>인코딩: UTF-8 (한글 지원)</li>
                                <li>구분자: 쉼표(,)</li>
                            </ul>
                            <div class="mt-3">
                                <strong>예시:</strong>
                                <pre class="bg-light p-2 rounded">roll_code,qr_code,start_serial,end_serial
R20250122001,QR-R20250122001,0000000001,0000000900
R20250122002,QR-R20250122002,0000000901,0000001800
R20250122003,QR-R20250122003,0000001801,0000002700</pre>
                            </div>
                        </div>

                        <div class="text-center">
                            <a href="paper-roll-template.csv" class="btn btn-info" download>
                                <i class="fas fa-download"></i> 템플릿 다운로드
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload"></i> 업로드
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 미할당 롤 목록 -->
    <div class="card shadow">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                미할당 롤 목록 (최근 50개)
            </h6>
        </div>
        <div class="card-body">
            <?php if (empty($unassignedRolls)): ?>
                <p class="text-center text-muted">미할당 롤이 없습니다.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>롤 코드</th>
                                <th>QR 코드</th>
                                <th>시작 번호</th>
                                <th>종료 번호</th>
                                <th>개수</th>
                                <th>등록일</th>
                                <th>관리</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($unassignedRolls as $roll): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($roll['roll_code']); ?></td>
                                    <td><?php echo htmlspecialchars($roll['qr_code']); ?></td>
                                    <td><?php echo $roll['start_serial']; ?></td>
                                    <td><?php echo $roll['end_serial']; ?></td>
                                    <td class="text-end"><?php echo number_format($roll['serial_count']); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($roll['created_at'])); ?></td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-danger btn-sm" 
                                                onclick="deleteRoll(<?php echo $roll['id']; ?>, '<?php echo htmlspecialchars($roll['roll_code']); ?>')">
                                            <i class="fas fa-trash"></i> 삭제
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// 일련번호 정보 업데이트
function updateSerialInfo() {
    const startInput = document.getElementById('start_serial');
    const endInput = document.getElementById('end_serial');
    const countInput = document.getElementById('serial_count');
    
    const start = parseInt(startInput.value) || 0;
    const end = parseInt(endInput.value) || 0;
    
    if (start > 0 && end > 0 && end >= start) {
        const count = end - start + 1;
        countInput.value = count.toLocaleString();
    } else if (end < start) {
        countInput.value = '오류: 시작 > 종료';
    } else {
        countInput.value = '-';
    }
}

// 롤 삭제 함수
function deleteRoll(rollId, rollCode) {
    if (confirm(`정말로 롤 '${rollCode}'을(를) 삭제하시겠습니까?\n\n이 작업은 되돌릴 수 없습니다.`)) {
        window.location.href = 'paper-roll-register.php?action=delete&id=' + rollId;
    }
}

// Bootstrap 탭 초기화
document.addEventListener('DOMContentLoaded', function() {
    var triggerTabList = [].slice.call(document.querySelectorAll('#registerTab button'));
    triggerTabList.forEach(function (triggerEl) {
        var tabTrigger = new bootstrap.Tab(triggerEl);
        
        triggerEl.addEventListener('click', function (event) {
            event.preventDefault();
            tabTrigger.show();
        });
    });
});
</script>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
