<?php
// 용지 활성화
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 로그인 체크
checkLogin();

// 페이지 제목
$pageTitle = "용지 활성화";
$currentSection = "store";
$currentPage = "paper-activate.php";

// 데이터베이스 연결
$conn = get_db_connection();

// 판매점 ID 확인
$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;

if ($store_id <= 0) {
    header('Location: paper-dashboard.php');
    exit;
}

// 판매점 정보 조회
$storeQuery = "
    SELECT s.*, r.region_name
    FROM stores s
    LEFT JOIN regions r ON s.region_id = r.id
    WHERE s.id = ?
";
$stmt = $conn->prepare($storeQuery);
$stmt->execute([$store_id]);
$store = $stmt->fetch();

if (!$store) {
    header('Location: paper-dashboard.php');
    exit;
}

// 현재 사용중인 용지 확인
$currentUsageQuery = "
    SELECT pu.*, pr.roll_code
    FROM paper_usage pu
    INNER JOIN paper_rolls pr ON pu.roll_id = pr.id
    WHERE pu.store_id = ? AND pu.is_active = 1
";
$stmt = $conn->prepare($currentUsageQuery);
$stmt->execute([$store_id]);
$currentUsage = $stmt->fetch();

// 사용 가능한 롤 목록 조회
$availableRollsQuery = "
    SELECT pr.*, pb.box_code 
    FROM paper_rolls pr
    INNER JOIN paper_boxes pb ON pr.box_id = pb.id
    WHERE pr.status = 'registered' 
    AND pb.store_id = ?
    ORDER BY pr.start_serial
";
$stmt = $conn->prepare($availableRollsQuery);
$stmt->execute([$store_id]);
$availableRolls = $stmt->fetchAll();

// QR 스캔으로 롤 조회
$scannedRoll = null;
if (isset($_GET['qr']) && !empty($_GET['qr'])) {
    $qrCode = trim($_GET['qr']);
    $scanQuery = "
        SELECT pr.*, pb.box_code 
        FROM paper_rolls pr
        INNER JOIN paper_boxes pb ON pr.box_id = pb.id
        WHERE pr.qr_code = ? AND pr.status = 'registered'
    ";
    $stmt = $conn->prepare($scanQuery);
    $stmt->execute([$qrCode]);
    $scannedRoll = $stmt->fetch();
}

// POST 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roll_id = isset($_POST['roll_id']) ? intval($_POST['roll_id']) : 0;
    $initial_serial = str_pad(trim($_POST['initial_serial'] ?? ''), 10, '0', STR_PAD_LEFT);
    $activation_type = $_POST['activation_type'] ?? 'qr'; // qr 또는 manual
    
    $errors = [];
    
    // 유효성 검증
    if ($roll_id <= 0) {
        $errors[] = "활성화할 용지롤을 선택해주세요.";
    }
    
    if (!preg_match('/^\d{10}$/', $initial_serial)) {
        $errors[] = "일련번호는 10자리 숫자여야 합니다.";
    }
    
    if (empty($errors)) {
        // 선택한 롤 정보 확인
        $rollQuery = "
            SELECT pr.*, pb.box_code, pb.store_id as box_store_id
            FROM paper_rolls pr
            INNER JOIN paper_boxes pb ON pr.box_id = pb.id
            WHERE pr.id = ? AND pr.status = 'registered'
        ";
        $stmt = $conn->prepare($rollQuery);
        $stmt->execute([$roll_id]);
        $roll = $stmt->fetch();
        
        if (!$roll) {
            $errors[] = "선택한 용지롤을 찾을 수 없거나 이미 사용중입니다.";
        } elseif ($initial_serial < $roll['start_serial'] || $initial_serial > $roll['end_serial']) {
            $errors[] = "입력한 일련번호가 선택한 롤의 범위에 없습니다.";
        } else {
            try {
                $conn->beginTransaction();
                
                // 기존 활성 용지가 있으면 비활성화
                if ($currentUsage) {
                    $deactivateQuery = "UPDATE paper_usage SET is_active = 0 WHERE id = ?";
                    $conn->prepare($deactivateQuery)->execute([$currentUsage['id']]);
                    
                    // 이전 롤 상태 업데이트
                    $updateOldRollQuery = "UPDATE paper_rolls SET status = 'used', used_at = NOW() WHERE id = ?";
                    $conn->prepare($updateOldRollQuery)->execute([$currentUsage['roll_id']]);
                }
                
                // 새 용지 사용 기록 생성
                $insertUsageQuery = "
                    INSERT INTO paper_usage (
                        store_id, roll_id, current_serial, estimated_serial,
                        printed_length_mm, remaining_length_mm, serial_difference,
                        welcome_count, is_active
                    ) VALUES (?, ?, ?, ?, 0, 63000, 0, 0, 1)
                ";
                $stmt = $conn->prepare($insertUsageQuery);
                $stmt->execute([
                    $store_id,
                    $roll_id,
                    $initial_serial,
                    $initial_serial
                ]);
                
                // 롤 상태 업데이트
                $updateRollQuery = "
                    UPDATE paper_rolls 
                    SET status = 'active', 
                        store_id = ?, 
                        activated_at = NOW() 
                    WHERE id = ?
                ";
                $conn->prepare($updateRollQuery)->execute([$store_id, $roll_id]);
                
                // 박스 할당 (아직 할당되지 않은 경우)
                if (!$roll['box_store_id']) {
                    $updateBoxQuery = "
                        UPDATE paper_boxes 
                        SET store_id = ?, 
                            assigned_at = NOW(),
                            status = 'assigned'
                        WHERE id = ?
                    ";
                    $conn->prepare($updateBoxQuery)->execute([$store_id, $roll['box_id']]);
                }
                
                // 재고 이력 기록
                $stockHistoryQuery = "
                    INSERT INTO paper_stock_history (
                        store_id, transaction_type, roll_id, reference_no, notes, created_by
                    ) VALUES (?, 'activate', ?, ?, ?, ?)
                ";
                $reference_no = $activation_type == 'qr' ? 'QR-' . date('YmdHis') : 'MAN-' . date('YmdHis');
                $notes = $activation_type == 'qr' ? 'QR 스캔으로 활성화' : '수동 선택으로 활성화';
                
                $conn->prepare($stockHistoryQuery)->execute([
                    $store_id,
                    $roll_id,
                    $reference_no,
                    $notes,
                    $_SESSION['user_id']
                ]);
                
                // 일련번호 추적 기록
                $trackingQuery = "
                    INSERT INTO paper_serial_tracking (
                        store_id, roll_id, input_serial, estimated_serial,
                        serial_difference, action_type, printed_length_before,
                        printed_length_after, is_valid, error_level, created_by
                    ) VALUES (?, ?, ?, ?, 0, 'paper_change', 0, 0, 1, 'normal', ?)
                ";
                $conn->prepare($trackingQuery)->execute([
                    $store_id,
                    $roll_id,
                    $initial_serial,
                    $initial_serial,
                    $_SESSION['user_id']
                ]);
                
                $conn->commit();
                
                // Welcome 메시지 출력 플래그
                $_SESSION['print_welcome'] = true;
                $_SESSION['success_message'] = "용지가 성공적으로 활성화되었습니다.";
                
                header("Location: paper-dashboard.php");
                exit;
                
            } catch (Exception $e) {
                $conn->rollBack();
                $errors[] = "활성화 중 오류가 발생했습니다: " . $e->getMessage();
            }
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

    <div class="row">
        <!-- 판매점 정보 -->
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">판매점 정보</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>판매점명</th>
                            <td><?php echo htmlspecialchars($store['store_name']); ?></td>
                        </tr>
                        <tr>
                            <th>판매점코드</th>
                            <td><?php echo $store['store_code']; ?></td>
                        </tr>
                        <tr>
                            <th>지역</th>
                            <td><?php echo htmlspecialchars($store['region_name']); ?></td>
                        </tr>
                    </table>
                    
                    <?php if ($currentUsage): ?>
                    <hr>
                    <div class="alert alert-warning">
                        <h6 class="alert-heading">
                            <i class="fas fa-exclamation-triangle"></i> 사용중인 용지 있음
                        </h6>
                        <p class="mb-0">
                            현재 사용중: <?php echo $currentUsage['roll_code']; ?><br>
                            새 용지를 활성화하면 기존 용지는 자동으로 사용 완료 처리됩니다.
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 활성화 방법 선택 -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">활성화 방법</h6>
                </div>
                <div class="card-body">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="method" id="method_qr" checked>
                        <label class="form-check-label" for="method_qr">
                            <i class="fas fa-qrcode"></i> QR 코드 스캔
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="method" id="method_manual">
                        <label class="form-check-label" for="method_manual">
                            <i class="fas fa-list"></i> 목록에서 선택
                        </label>
                    </div>
                    
                    <hr>
                    
                    <div id="qr_scan_area">
                        <button type="button" class="btn btn-primary w-100" onclick="startQRScan()">
                            <i class="fas fa-camera"></i> QR 스캔 시작
                        </button>
                        <div class="text-center mt-2">
                            <small class="text-muted">용지롤의 QR 코드를 스캔하세요</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 용지 선택 및 활성화 -->
        <div class="col-md-8">
            <form method="post" action="">
                <input type="hidden" name="activation_type" id="activation_type" value="qr">
                
                <!-- QR 스캔 결과 -->
                <?php if ($scannedRoll): ?>
                <div class="card shadow mb-4 border-success">
                    <div class="card-header py-3 bg-success text-white">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-check-circle"></i> QR 스캔 완료
                        </h6>
                    </div>
                    <div class="card-body">
                        <input type="hidden" name="roll_id" value="<?php echo $scannedRoll['id']; ?>">
                        <table class="table table-sm">
                            <tr>
                                <th width="30%">롤 코드</th>
                                <td><strong><?php echo $scannedRoll['roll_code']; ?></strong></td>
                            </tr>
                            <tr>
                                <th>박스 코드</th>
                                <td><?php echo $scannedRoll['box_code']; ?></td>
                            </tr>
                            <tr>
                                <th>일련번호 범위</th>
                                <td><?php echo $scannedRoll['start_serial']; ?> ~ <?php echo $scannedRoll['end_serial']; ?></td>
                            </tr>
                            <tr>
                                <th>총 일련번호</th>
                                <td><?php echo number_format($scannedRoll['serial_count']); ?>개</td>
                            </tr>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 수동 선택 -->
                <div class="card shadow mb-4" id="manual_selection" style="<?php echo $scannedRoll ? 'display:none;' : ''; ?>">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">사용 가능한 용지롤</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($availableRolls)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> 사용 가능한 용지롤이 없습니다.
                                먼저 용지를 등록해주세요.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th width="5%">선택</th>
                                            <th>롤 코드</th>
                                            <th>박스 코드</th>
                                            <th>시작 번호</th>
                                            <th>종료 번호</th>
                                            <th>개수</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($availableRolls as $roll): ?>
                                            <tr>
                                                <td class="text-center">
                                                    <input type="radio" name="roll_id" value="<?php echo $roll['id']; ?>" 
                                                           data-start="<?php echo $roll['start_serial']; ?>"
                                                           data-end="<?php echo $roll['end_serial']; ?>">
                                                </td>
                                                <td><?php echo $roll['roll_code']; ?></td>
                                                <td><?php echo $roll['box_code']; ?></td>
                                                <td><?php echo $roll['start_serial']; ?></td>
                                                <td><?php echo $roll['end_serial']; ?></td>
                                                <td class="text-center"><?php echo number_format($roll['serial_count']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 초기 일련번호 입력 -->
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">초기 일련번호 입력</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="initial_serial" class="form-label">
                                용지 뒷면 일련번호 <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-lg">
                                <input type="text" name="initial_serial" id="initial_serial" 
                                       class="form-control text-center" 
                                       placeholder="0000000000" 
                                       maxlength="10" 
                                       pattern="\d{10}" 
                                       required>
                            </div>
                            <div class="form-text">
                                활성화할 용지의 현재 위치에 있는 10자리 일련번호를 입력하세요.
                            </div>
                        </div>

                        <div id="serial_range_info" class="alert alert-info" style="display:none;">
                            <i class="fas fa-info-circle"></i> 
                            선택한 롤의 번호 범위: <span id="range_display"></span>
                        </div>

                        <div class="text-center">
                            <button type="button" class="btn btn-secondary" onclick="history.back()">취소</button>
                            <button type="submit" class="btn btn-primary btn-lg" <?php echo empty($availableRolls) && !$scannedRoll ? 'disabled' : ''; ?>>
                                <i class="fas fa-check"></i> 용지 활성화
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// 활성화 방법 토글
document.getElementById('method_qr').addEventListener('change', function() {
    if (this.checked) {
        document.getElementById('qr_scan_area').style.display = 'block';
        document.getElementById('manual_selection').style.display = 'none';
        document.getElementById('activation_type').value = 'qr';
    }
});

document.getElementById('method_manual').addEventListener('change', function() {
    if (this.checked) {
        document.getElementById('qr_scan_area').style.display = 'none';
        document.getElementById('manual_selection').style.display = 'block';
        document.getElementById('activation_type').value = 'manual';
    }
});

// 롤 선택 시 범위 표시
document.querySelectorAll('input[name="roll_id"]').forEach(radio => {
    radio.addEventListener('change', function() {
        if (this.checked) {
            const start = this.dataset.start;
            const end = this.dataset.end;
            document.getElementById('range_display').textContent = start + ' ~ ' + end;
            document.getElementById('serial_range_info').style.display = 'block';
        }
    });
});

// QR 스캔 시뮬레이션
function startQRScan() {
    // 실제 구현시 QR 스캐너 라이브러리 사용
    alert('QR 스캐너는 실제 단말기에서 작동합니다.');
    
    // 테스트용: 임의의 QR 코드로 리다이렉트
    // window.location.href = '?store_id=<?php echo $store_id; ?>&qr=QR-TEST-' + Date.now();
}

// 초기 일련번호 입력 포커스
document.getElementById('initial_serial').focus();
</script>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
