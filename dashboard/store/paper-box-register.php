<?php
// 용지박스 등록
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 로그인 체크
checkLogin();

// 페이지 제목
$pageTitle = "용지박스 등록";
$currentSection = "store";
$currentPage = "paper-box-register.php";

// 데이터베이스 연결
$conn = get_db_connection();

// POST 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $success = false;
    
    $registration_type = $_POST['registration_type'] ?? '';
    $box_code = trim($_POST['box_code'] ?? '');
    $qr_code = trim($_POST['qr_code'] ?? '');
    $serial_prefix = trim($_POST['serial_prefix'] ?? '');
    $total_rolls = intval($_POST['total_rolls'] ?? 10);
    $notes = trim($_POST['notes'] ?? '');
    
    // 롤 정보 배열
    $rolls = [];
    if (isset($_POST['roll_codes']) && is_array($_POST['roll_codes'])) {
        for ($i = 0; $i < count($_POST['roll_codes']); $i++) {
            if (!empty($_POST['roll_codes'][$i])) {
                $rolls[] = [
                    'roll_code' => trim($_POST['roll_codes'][$i]),
                    'qr_code' => trim($_POST['roll_qr_codes'][$i] ?? ''),
                    'start_serial' => str_pad(trim($_POST['start_serials'][$i] ?? ''), 10, '0', STR_PAD_LEFT),
                    'end_serial' => str_pad(trim($_POST['end_serials'][$i] ?? ''), 10, '0', STR_PAD_LEFT)
                ];
            }
        }
    }
    
    // 유효성 검증
    if (empty($box_code)) {
        $errors[] = "박스 코드를 입력해주세요.";
    }
    
    if (empty($qr_code)) {
        $errors[] = "QR 코드를 입력해주세요.";
    }
    
    if (empty($serial_prefix)) {
        $errors[] = "일련번호 접두사를 입력해주세요.";
    }
    
    if ($total_rolls < 1 || $total_rolls > 100) {
        $errors[] = "롤 수는 1~100 사이여야 합니다.";
    }
    
    if (count($rolls) != $total_rolls) {
        $errors[] = "입력된 롤 수({$total_rolls})와 실제 롤 정보 수(" . count($rolls) . ")가 일치하지 않습니다.";
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
                $total_rolls,
                $notes,
                $_SESSION['user_id']
            ]);
            
            $box_id = $conn->lastInsertId();
            
            // 롤 등록
            foreach ($rolls as $roll) {
                // 롤 중복 확인
                $checkRollQuery = "SELECT id FROM paper_rolls WHERE roll_code = ? OR qr_code = ?";
                $stmt = $conn->prepare($checkRollQuery);
                $stmt->execute([$roll['roll_code'], $roll['qr_code']]);
                if ($stmt->rowCount() > 0) {
                    throw new Exception("중복된 롤 코드 또는 QR 코드: {$roll['roll_code']}");
                }
                
                // 일련번호 범위 확인
                if ($roll['start_serial'] > $roll['end_serial']) {
                    throw new Exception("잘못된 일련번호 범위: {$roll['roll_code']}");
                }
                
                $serial_count = intval($roll['end_serial']) - intval($roll['start_serial']) + 1;
                
                $insertRollQuery = "
                    INSERT INTO paper_rolls (
                        roll_code, qr_code, box_id, start_serial, end_serial,
                        serial_count, status
                    ) VALUES (?, ?, ?, ?, ?, ?, 'registered')
                ";
                $stmt = $conn->prepare($insertRollQuery);
                $stmt->execute([
                    $roll['roll_code'],
                    $roll['qr_code'],
                    $box_id,
                    $roll['start_serial'],
                    $roll['end_serial'],
                    $serial_count
                ]);
            }
            
            $conn->commit();
            
            $_SESSION['success_message'] = "용지박스가 성공적으로 등록되었습니다. (박스: {$box_code}, 롤: " . count($rolls) . "개)";
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

    <!-- 등록 폼 -->
    <form method="post" action="">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">박스 정보</h6>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">등록 방식</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="registration_type" 
                                       id="type_qr" value="qr" checked onclick="toggleInputMethod()">
                                <label class="form-check-label" for="type_qr">
                                    <i class="fas fa-qrcode"></i> QR 스캔
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="registration_type" 
                                       id="type_manual" value="manual" onclick="toggleInputMethod()">
                                <label class="form-check-label" for="type_manual">
                                    <i class="fas fa-keyboard"></i> 수동 입력
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="box_code" class="form-label">박스 코드 <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">B</span>
                                <input type="text" name="box_code" id="box_code" class="form-control" 
                                       placeholder="예: B20250122001" required>
                                <button type="button" class="btn btn-outline-secondary" id="scan_box_qr">
                                    <i class="fas fa-qrcode"></i> 스캔
                                </button>
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
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="serial_prefix" class="form-label">일련번호 접두사 <span class="text-danger">*</span></label>
                            <input type="text" name="serial_prefix" id="serial_prefix" class="form-control" 
                                   placeholder="예: 2025A" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="total_rolls" class="form-label">롤 수 <span class="text-danger">*</span></label>
                            <input type="number" name="total_rolls" id="total_rolls" class="form-control" 
                                   value="10" min="1" max="100" required onchange="updateRollInputs()">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="notes" class="form-label">비고</label>
                            <input type="text" name="notes" id="notes" class="form-control" 
                                   placeholder="메모 사항">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 롤 정보 -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">롤 정보</h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    각 롤의 정보를 입력해주세요. 일련번호는 10자리로 자동 변환됩니다.
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="20%">롤 코드</th>
                                <th width="25%">QR 코드</th>
                                <th width="20%">시작 번호</th>
                                <th width="20%">종료 번호</th>
                                <th width="10%">개수</th>
                            </tr>
                        </thead>
                        <tbody id="roll_inputs">
                            <!-- JavaScript로 동적 생성 -->
                        </tbody>
                    </table>
                </div>
                
                <div class="text-end mt-3">
                    <button type="button" class="btn btn-info" onclick="autoFillSerials()">
                        <i class="fas fa-magic"></i> 일련번호 자동 채우기
                    </button>
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
</div>

<script>
// 페이지 로드 시 롤 입력 필드 생성
document.addEventListener('DOMContentLoaded', function() {
    updateRollInputs();
});

// 입력 방식 토글
function toggleInputMethod() {
    const isQR = document.getElementById('type_qr').checked;
    document.getElementById('scan_box_qr').style.display = isQR ? 'block' : 'none';
}

// 롤 입력 필드 업데이트
function updateRollInputs() {
    const count = parseInt(document.getElementById('total_rolls').value) || 10;
    const tbody = document.getElementById('roll_inputs');
    tbody.innerHTML = '';
    
    for (let i = 0; i < count; i++) {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td class="text-center">${i + 1}</td>
            <td>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">R</span>
                    <input type="text" name="roll_codes[]" class="form-control" 
                           placeholder="예: R20250122001${String(i + 1).padStart(2, '0')}" required>
                </div>
            </td>
            <td>
                <input type="text" name="roll_qr_codes[]" class="form-control form-control-sm" 
                       placeholder="QR 코드">
            </td>
            <td>
                <input type="text" name="start_serials[]" class="form-control form-control-sm serial-input" 
                       placeholder="0000000001" maxlength="10" onchange="updateSerialCount(${i})" required>
            </td>
            <td>
                <input type="text" name="end_serials[]" class="form-control form-control-sm serial-input" 
                       placeholder="0000000900" maxlength="10" onchange="updateSerialCount(${i})" required>
            </td>
            <td class="text-center">
                <span id="serial_count_${i}">-</span>
            </td>
        `;
        tbody.appendChild(row);
    }
}

// 일련번호 개수 업데이트
function updateSerialCount(index) {
    const startInputs = document.getElementsByName('start_serials[]');
    const endInputs = document.getElementsByName('end_serials[]');
    const countSpan = document.getElementById('serial_count_' + index);
    
    const start = parseInt(startInputs[index].value) || 0;
    const end = parseInt(endInputs[index].value) || 0;
    
    if (start > 0 && end > 0 && end >= start) {
        countSpan.textContent = (end - start + 1).toLocaleString();
    } else {
        countSpan.textContent = '-';
    }
}

// 일련번호 자동 채우기
function autoFillSerials() {
    const startInputs = document.getElementsByName('start_serials[]');
    const endInputs = document.getElementsByName('end_serials[]');
    const serialsPerRoll = 900; // 기본값
    
    let currentSerial = 1;
    
    for (let i = 0; i < startInputs.length; i++) {
        startInputs[i].value = String(currentSerial).padStart(10, '0');
        currentSerial += serialsPerRoll - 1;
        endInputs[i].value = String(currentSerial).padStart(10, '0');
        currentSerial++;
        
        updateSerialCount(i);
    }
}

// QR 스캔 시뮬레이션 (실제 구현 시 QR 스캐너 라이브러리 사용)
document.getElementById('scan_box_qr').addEventListener('click', function() {
    alert('QR 스캐너 기능은 실제 단말기에서 작동합니다.');
    // 테스트용 데이터
    document.getElementById('box_code').value = 'B' + new Date().toISOString().slice(0, 10).replace(/-/g, '') + '001';
    document.getElementById('qr_code').value = 'QR-' + Date.now();
});
</script>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
