<?php
// 용지번호 입력 및 검증
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 로그인 체크
checkLogin();

// 페이지 제목
$pageTitle = "용지번호 입력";
$currentSection = "store";
$currentPage = "paper-input.php";

// 데이터베이스 연결
$conn = get_db_connection();

// 판매점 ID 확인
$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
$action_type = isset($_GET['action']) ? $_GET['action'] : 'login'; // login, paper_change, manual_input

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

// 현재 사용중인 용지 정보 조회
$usageQuery = "
    SELECT pu.*, pr.*, pb.box_code
    FROM paper_usage pu
    INNER JOIN paper_rolls pr ON pu.roll_id = pr.id
    INNER JOIN paper_boxes pb ON pr.box_id = pb.id
    WHERE pu.store_id = ? AND pu.is_active = 1
";
$stmt = $conn->prepare($usageQuery);
$stmt->execute([$store_id]);
$currentUsage = $stmt->fetch();

// 용지 길이 설정 조회
$lengthSettings = [];
$settingsQuery = "SELECT item_type, length_mm FROM paper_length_settings WHERE is_active = 1";
$stmt = $conn->query($settingsQuery);
while ($row = $stmt->fetch()) {
    $lengthSettings[$row['item_type']] = $row['length_mm'];
}

// POST 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_serial = str_pad(trim($_POST['input_serial'] ?? ''), 10, '0', STR_PAD_LEFT);
    $new_roll_id = isset($_POST['new_roll_id']) ? intval($_POST['new_roll_id']) : 0;
    $notes = trim($_POST['notes'] ?? '');
    
    $errors = [];
    $warnings = [];
    
    // 유효성 검증
    if (!preg_match('/^\d{10}$/', $input_serial)) {
        $errors[] = "일련번호는 10자리 숫자여야 합니다.";
    }
    
    if (empty($errors)) {
        try {
            $conn->beginTransaction();
            
            // 용지 교체인 경우
            if ($action_type == 'paper_change' && $new_roll_id > 0) {
                // 새 롤 정보 확인
                $rollQuery = "SELECT * FROM paper_rolls WHERE id = ? AND status = 'registered'";
                $stmt = $conn->prepare($rollQuery);
                $stmt->execute([$new_roll_id]);
                $newRoll = $stmt->fetch();
                
                if (!$newRoll) {
                    throw new Exception("선택한 용지롤을 찾을 수 없거나 이미 사용중입니다.");
                }
                
                // 입력된 번호가 새 롤 범위에 있는지 확인
                if ($input_serial < $newRoll['start_serial'] || $input_serial > $newRoll['end_serial']) {
                    $errors[] = "입력한 번호가 선택한 롤의 범위에 없습니다.";
                } else {
                    // 기존 용지 사용 완료 처리
                    if ($currentUsage) {
                        $updateOldQuery = "UPDATE paper_usage SET is_active = 0 WHERE id = ?";
                        $conn->prepare($updateOldQuery)->execute([$currentUsage['id']]);
                        
                        $updateRollQuery = "UPDATE paper_rolls SET status = 'used', used_at = NOW() WHERE id = ?";
                        $conn->prepare($updateRollQuery)->execute([$currentUsage['roll_id']]);
                    }
                    
                    // 새 용지 활성화
                    $insertUsageQuery = "
                        INSERT INTO paper_usage (
                            store_id, roll_id, current_serial, estimated_serial,
                            printed_length_mm, remaining_length_mm, serial_difference
                        ) VALUES (?, ?, ?, ?, 0, ?, 0)
                    ";
                    $stmt = $conn->prepare($insertUsageQuery);
                    $stmt->execute([
                        $store_id,
                        $new_roll_id,
                        $input_serial,
                        $input_serial,
                        $lengthSettings['roll_total_length']
                    ]);
                    
                    // 롤 상태 업데이트
                    $updateNewRollQuery = "
                        UPDATE paper_rolls 
                        SET status = 'active', 
                            store_id = ?, 
                            activated_at = NOW() 
                        WHERE id = ?
                    ";
                    $conn->prepare($updateNewRollQuery)->execute([$store_id, $new_roll_id]);
                    
                    // 재고 이력 기록
                    $stockHistoryQuery = "
                        INSERT INTO paper_stock_history (
                            store_id, transaction_type, roll_id, created_by
                        ) VALUES (?, 'activate', ?, ?)
                    ";
                    $conn->prepare($stockHistoryQuery)->execute([
                        $store_id, $new_roll_id, $_SESSION['user_id']
                    ]);
                }
            } else {
                // 일반 번호 입력 (로그인 또는 수동 입력)
                if (!$currentUsage) {
                    $errors[] = "활성화된 용지가 없습니다. 먼저 용지를 활성화해주세요.";
                } else {
                    // 추정 번호 계산
                    $estimated_serial = calculateEstimatedSerial($currentUsage, $lengthSettings);
                    
                    // 차이 계산
                    $serial_difference = intval($input_serial) - intval($estimated_serial);
                    
                    // 오차 범위 확인
                    $error_tolerance = $lengthSettings['error_tolerance'] ?? 12;
                    $is_valid = abs($serial_difference) <= $error_tolerance;
                    
                    if (!$is_valid) {
                        if (abs($serial_difference) <= 20) {
                            $warnings[] = "일련번호 차이가 허용 범위(±{$error_tolerance})를 초과했습니다. (차이: {$serial_difference})";
                            $error_level = 'warning';
                        } else {
                            $errors[] = "일련번호 차이가 너무 큽니다. (차이: {$serial_difference}) 관리자에게 문의하세요.";
                            $error_level = 'critical';
                        }
                    } else {
                        $error_level = 'normal';
                    }
                    
                    if (empty($errors)) {
                        // 추적 기록 저장
                        $trackingQuery = "
                            INSERT INTO paper_serial_tracking (
                                store_id, roll_id, input_serial, estimated_serial,
                                serial_difference, action_type, printed_length_before,
                                printed_length_after, is_valid, error_level, notes, created_by
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ";
                        $stmt = $conn->prepare($trackingQuery);
                        $stmt->execute([
                            $store_id,
                            $currentUsage['roll_id'],
                            $input_serial,
                            $estimated_serial,
                            $serial_difference,
                            $action_type,
                            $currentUsage['printed_length_mm'],
                            $currentUsage['printed_length_mm'], // 변경 없음
                            $is_valid ? 1 : 0,
                            $error_level,
                            $notes,
                            $_SESSION['user_id']
                        ]);
                        
                        // 현재 번호 업데이트
                        $updateUsageQuery = "
                            UPDATE paper_usage 
                            SET current_serial = ?,
                                estimated_serial = ?,
                                serial_difference = ?
                            WHERE id = ?
                        ";
                        $conn->prepare($updateUsageQuery)->execute([
                            $input_serial,
                            $estimated_serial,
                            $serial_difference,
                            $currentUsage['id']
                        ]);
                        
                        // 경고 레벨 알림 생성
                        if ($error_level != 'normal') {
                            $alertQuery = "
                                INSERT INTO paper_alerts (
                                    store_id, roll_id, alert_type, alert_level,
                                    serial_difference, message, is_notified
                                ) VALUES (?, ?, ?, ?, ?, ?, 1)
                            ";
                            $alertMessage = $error_level == 'warning' 
                                ? "일련번호 차이 경고: {$serial_difference}"
                                : "일련번호 차이 위험: {$serial_difference}";
                            
                            $conn->prepare($alertQuery)->execute([
                                $store_id,
                                $currentUsage['roll_id'],
                                'serial_' . $error_level,
                                $error_level == 'warning' ? 2 : 3,
                                $serial_difference,
                                $alertMessage
                            ]);
                        }
                    }
                }
            }
            
            if (empty($errors)) {
                $conn->commit();
                
                if (!empty($warnings)) {
                    $_SESSION['warning_message'] = implode('<br>', $warnings);
                } else {
                    $_SESSION['success_message'] = "용지번호가 성공적으로 입력되었습니다.";
                }
                
                // Welcome 메시지 출력 (실제로는 프린터로 출력)
                if ($action_type == 'login') {
                    // Welcome 메시지 카운트 증가
                    $updateWelcomeQuery = "
                        UPDATE paper_usage 
                        SET welcome_count = welcome_count + 1,
                            printed_length_mm = printed_length_mm + ?
                        WHERE id = ?
                    ";
                    $conn->prepare($updateWelcomeQuery)->execute([
                        $lengthSettings['welcome_message'],
                        $currentUsage['id'] ?? $conn->lastInsertId()
                    ]);
                }
                
                header("Location: paper-dashboard.php");
                exit;
            }
            
        } catch (Exception $e) {
            $conn->rollBack();
            $errors[] = "처리 중 오류가 발생했습니다: " . $e->getMessage();
        }
    }
}

// 사용 가능한 롤 목록 조회 (용지 교체 시)
$availableRolls = [];
if ($action_type == 'paper_change') {
    $rollsQuery = "
        SELECT pr.*, pb.box_code 
        FROM paper_rolls pr
        INNER JOIN paper_boxes pb ON pr.box_id = pb.id
        WHERE pr.status = 'registered' 
        AND (pr.store_id IS NULL OR pr.store_id = ?)
        ORDER BY pr.start_serial
    ";
    $stmt = $conn->prepare($rollsQuery);
    $stmt->execute([$store_id]);
    $availableRolls = $stmt->fetchAll();
}

// 추정 번호 계산 함수
function calculateEstimatedSerial($usage, $settings) {
    $start_serial = intval($usage['start_serial']);
    $serial_interval = $settings['serial_interval'] ?? 70;
    
    // 인쇄된 길이를 일련번호 간격으로 나누어 지나간 구간 수 계산
    $intervals_passed = floor($usage['printed_length_mm'] / $serial_interval);
    
    return str_pad($start_serial + $intervals_passed, 10, '0', STR_PAD_LEFT);
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
                    <h6 class="font-weight-bold mb-3">현재 용지 정보</h6>
                    <table class="table table-sm">
                        <tr>
                            <th>롤 코드</th>
                            <td><?php echo $currentUsage['roll_code']; ?></td>
                        </tr>
                        <tr>
                            <th>박스 코드</th>
                            <td><?php echo $currentUsage['box_code']; ?></td>
                        </tr>
                        <tr>
                            <th>번호 범위</th>
                            <td><?php echo $currentUsage['start_serial']; ?> ~<br><?php echo $currentUsage['end_serial']; ?></td>
                        </tr>
                        <tr>
                            <th>현재 번호</th>
                            <td class="fw-bold"><?php echo $currentUsage['current_serial']; ?></td>
                        </tr>
                        <tr>
                            <th>추정 번호</th>
                            <td><?php echo $currentUsage['estimated_serial']; ?></td>
                        </tr>
                        <tr>
                            <th>사용량</th>
                            <td>
                                <?php 
                                $usage_percentage = ($currentUsage['printed_length_mm'] / $currentUsage['length_mm']) * 100;
                                echo number_format($currentUsage['printed_length_mm'] / 1000, 1); 
                                ?>m / <?php echo number_format($currentUsage['length_mm'] / 1000); ?>m
                                <div class="progress mt-1" style="height: 15px;">
                                    <div class="progress-bar <?php echo getUsageProgressClass($usage_percentage); ?>" 
                                         style="width: <?php echo $usage_percentage; ?>%">
                                        <?php echo number_format($usage_percentage, 1); ?>%
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </table>
                    <?php else: ?>
                    <div class="alert alert-warning">
                        활성화된 용지가 없습니다.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 번호 입력 폼 -->
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <?php
                        switch($action_type) {
                            case 'login': echo '로그인 시 용지번호 입력'; break;
                            case 'paper_change': echo '용지 교체'; break;
                            case 'manual_input': echo '수동 용지번호 입력'; break;
                            default: echo '용지번호 입력';
                        }
                        ?>
                    </h6>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <?php if ($action_type == 'paper_change' && !empty($availableRolls)): ?>
                        <!-- 용지 교체 시 새 롤 선택 -->
                        <div class="mb-3">
                            <label for="new_roll_id" class="form-label">새 용지롤 선택 <span class="text-danger">*</span></label>
                            <select name="new_roll_id" id="new_roll_id" class="form-select" required>
                                <option value="">선택하세요</option>
                                <?php foreach ($availableRolls as $roll): ?>
                                    <option value="<?php echo $roll['id']; ?>">
                                        <?php echo $roll['roll_code']; ?> 
                                        (<?php echo $roll['start_serial']; ?> ~ <?php echo $roll['end_serial']; ?>)
                                        - 박스: <?php echo $roll['box_code']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="input_serial" class="form-label">
                                용지 뒷면 번호 입력 <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-lg">
                                <input type="text" name="input_serial" id="input_serial" 
                                       class="form-control text-center" 
                                       placeholder="0000000000" 
                                       maxlength="10" 
                                       pattern="\d{10}" 
                                       required 
                                       autofocus>
                            </div>
                            <div class="form-text">
                                용지 뒷면에 인쇄된 10자리 일련번호를 입력하세요.
                            </div>
                        </div>

                        <!-- 숫자 키패드 -->
                        <div class="mb-3">
                            <div class="btn-group d-flex flex-wrap" role="group">
                                <?php for ($i = 1; $i <= 9; $i++): ?>
                                    <button type="button" class="btn btn-outline-primary btn-lg flex-fill m-1" 
                                            style="width: 30%;" onclick="appendNumber('<?php echo $i; ?>')">
                                        <?php echo $i; ?>
                                    </button>
                                    <?php if ($i % 3 == 0): ?><div class="w-100"></div><?php endif; ?>
                                <?php endfor; ?>
                                <button type="button" class="btn btn-outline-warning btn-lg flex-fill m-1" 
                                        style="width: 30%;" onclick="clearInput()">
                                    <i class="fas fa-eraser"></i> Clear
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-lg flex-fill m-1" 
                                        style="width: 30%;" onclick="appendNumber('0')">
                                    0
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-lg flex-fill m-1" 
                                        style="width: 30%;" onclick="backspace()">
                                    <i class="fas fa-backspace"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">비고</label>
                            <textarea name="notes" id="notes" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="text-center">
                            <button type="button" class="btn btn-secondary" onclick="history.back()">취소</button>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-check"></i> 확인
                            </button>
                            <?php if ($action_type == 'login'): ?>
                            <button type="button" class="btn btn-success" onclick="printWelcome()">
                                <i class="fas fa-print"></i> Welcome 재인쇄
                            </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 도움말 -->
            <div class="card shadow">
                <div class="card-body">
                    <h6 class="font-weight-bold">오차 허용 범위</h6>
                    <ul>
                        <li><span class="text-success">정상</span>: ±12 이내</li>
                        <li><span class="text-warning">경고</span>: ±13 ~ ±20</li>
                        <li><span class="text-danger">오류</span>: ±20 초과</li>
                    </ul>
                    <small class="text-muted">
                        * 오차가 발생하는 이유: 프린터의 물리적 특성상 미세한 인쇄 오차가 누적될 수 있습니다.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// 숫자 입력
function appendNumber(num) {
    const input = document.getElementById('input_serial');
    if (input.value.length < 10) {
        input.value += num;
    }
}

// 백스페이스
function backspace() {
    const input = document.getElementById('input_serial');
    input.value = input.value.slice(0, -1);
}

// 전체 지우기
function clearInput() {
    document.getElementById('input_serial').value = '';
}

// Welcome 재인쇄
function printWelcome() {
    if (confirm('Welcome 메시지를 재인쇄하시겠습니까?')) {
        // AJAX로 재인쇄 처리
        alert('Welcome 메시지가 출력됩니다.');
    }
}

// 입력 필드 포커스
document.getElementById('input_serial').focus();
</script>

<?php
function getUsageProgressClass($percentage) {
    if ($percentage >= 98) return 'bg-danger';
    if ($percentage >= 95) return 'bg-warning';
    if ($percentage >= 90) return 'bg-info';
    return 'bg-success';
}
?>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
