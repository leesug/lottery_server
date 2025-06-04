<?php
// 은행 당첨확인 시스템
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 로그인 체크 (은행 권한 필요)
checkLogin();
checkPermission('bank_verification');

// 페이지 제목
$pageTitle = "당첨 티켓 검증";
$currentSection = "external";
$currentPage = "bank-verification.php";

// 데이터베이스 연결
$conn = get_db_connection();

// 검증 결과
$verificationResult = null;
$errors = [];

// POST 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $verification_type = $_POST['verification_type'] ?? '';
    $ticket_code = trim($_POST['ticket_code'] ?? '');
    $blockchain_code = trim($_POST['blockchain_code'] ?? '');
    $paper_serial = str_pad(trim($_POST['paper_serial'] ?? ''), 10, '0', STR_PAD_LEFT);
    
    if ($verification_type == 'ticket') {
        // 티켓 코드로 검증
        if (empty($ticket_code)) {
            $errors[] = "티켓 코드를 입력해주세요.";
        }
    } else {
        // 블록체인 코드로 검증
        if (empty($blockchain_code)) {
            $errors[] = "블록체인 코드를 입력해주세요.";
        }
    }
    
    if (empty($paper_serial)) {
        $errors[] = "용지 일련번호를 입력해주세요.";
    }
    
    if (empty($errors)) {
        try {
            // 티켓 정보 조회
            if ($verification_type == 'ticket') {
                $ticketQuery = "
                    SELECT t.*, d.draw_no, d.draw_date, d.winning_numbers,
                           s.store_name, s.store_code,
                           w.prize_tier, w.prize_amount, w.status as winning_status
                    FROM tickets t
                    INNER JOIN draws d ON t.draw_id = d.id
                    INNER JOIN stores s ON t.store_id = s.id
                    LEFT JOIN winnings w ON t.id = w.ticket_id
                    WHERE t.ticket_code = ?
                ";
                $stmt = $conn->prepare($ticketQuery);
                $stmt->execute([$ticket_code]);
            } else {
                $ticketQuery = "
                    SELECT t.*, d.draw_no, d.draw_date, d.winning_numbers,
                           s.store_name, s.store_code,
                           w.prize_tier, w.prize_amount, w.status as winning_status
                    FROM tickets t
                    INNER JOIN draws d ON t.draw_id = d.id
                    INNER JOIN stores s ON t.store_id = s.id
                    LEFT JOIN winnings w ON t.id = w.ticket_id
                    WHERE t.blockchain_code = ?
                ";
                $stmt = $conn->prepare($ticketQuery);
                $stmt->execute([$blockchain_code]);
            }
            
            $ticket = $stmt->fetch();
            
            if (!$ticket) {
                $errors[] = "티켓을 찾을 수 없습니다.";
            } else {
                // 티켓 발행 시점의 용지 사용 정보 조회
                $usageQuery = "
                    SELECT pu.*, pr.start_serial, pr.end_serial, pr.serial_interval_mm,
                           pst.input_serial, pst.estimated_serial, pst.serial_difference,
                           pst.created_at as last_input_time
                    FROM paper_usage pu
                    INNER JOIN paper_rolls pr ON pu.roll_id = pr.id
                    LEFT JOIN (
                        SELECT * FROM paper_serial_tracking 
                        WHERE store_id = ? AND created_at <= ?
                        ORDER BY created_at DESC
                        LIMIT 1
                    ) pst ON pst.store_id = pu.store_id
                    WHERE pu.store_id = ? 
                    AND pu.created_at <= ?
                    ORDER BY pu.created_at DESC
                    LIMIT 1
                ";
                $stmt = $conn->prepare($usageQuery);
                $stmt->execute([
                    $ticket['store_id'],
                    $ticket['created_at'],
                    $ticket['store_id'],
                    $ticket['created_at']
                ]);
                $paperUsage = $stmt->fetch();
                
                if ($paperUsage) {
                    // 용지 길이 설정 조회
                    $lengthSettings = [];
                    $settingsQuery = "SELECT item_type, length_mm FROM paper_length_settings WHERE is_active = 1";
                    $stmt = $conn->query($settingsQuery);
                    while ($row = $stmt->fetch()) {
                        $lengthSettings[$row['item_type']] = $row['length_mm'];
                    }
                    
                    // 티켓 발행 시점까지의 인쇄 길이 계산
                    $printQuery = "
                        SELECT 
                            SUM(CASE 
                                WHEN created_at < ? THEN games_count * 
                                    CASE games_count
                                        WHEN 1 THEN ?
                                        WHEN 2 THEN ?
                                        WHEN 3 THEN ?
                                        WHEN 4 THEN ?
                                        WHEN 5 THEN ?
                                    END
                                ELSE 0
                            END) as printed_before,
                            COUNT(CASE WHEN created_at < ? THEN 1 END) as tickets_before
                        FROM tickets
                        WHERE store_id = ? 
                        AND DATE(created_at) = DATE(?)
                    ";
                    $stmt = $conn->prepare($printQuery);
                    $stmt->execute([
                        $ticket['created_at'],
                        $lengthSettings['game_1_ticket'],
                        $lengthSettings['game_2_ticket'],
                        $lengthSettings['game_3_ticket'],
                        $lengthSettings['game_4_ticket'],
                        $lengthSettings['game_5_ticket'],
                        $ticket['created_at'],
                        $ticket['store_id'],
                        $ticket['created_at']
                    ]);
                    $printStats = $stmt->fetch();
                    
                    // 예상 용지번호 계산
                    $base_serial = intval($paperUsage['input_serial'] ?: $paperUsage['start_serial']);
                    $printed_length = $printStats['printed_before'] ?? 0;
                    
                    // Welcome 메시지 추가 (당일 첫 티켓인 경우)
                    if ($printStats['tickets_before'] == 0) {
                        $printed_length += $lengthSettings['welcome_message'];
                    }
                    
                    $serial_interval = $paperUsage['serial_interval_mm'] ?? 70;
                    $intervals_passed = floor($printed_length / $serial_interval);
                    $estimated_serial = str_pad($base_serial + $intervals_passed, 10, '0', STR_PAD_LEFT);
                    
                    // 입력된 용지번호와 비교
                    $serial_difference = intval($paper_serial) - intval($estimated_serial);
                    $error_tolerance = $lengthSettings['error_tolerance'] ?? 12;
                    
                    // 검증 결과 구성
                    $verificationResult = [
                        'ticket' => $ticket,
                        'paper_usage' => $paperUsage,
                        'input_serial' => $paper_serial,
                        'estimated_serial' => $estimated_serial,
                        'serial_difference' => $serial_difference,
                        'is_valid' => abs($serial_difference) <= $error_tolerance,
                        'error_level' => abs($serial_difference) <= $error_tolerance ? 'normal' : 
                                        (abs($serial_difference) <= 20 ? 'warning' : 'critical'),
                        'printed_length' => $printed_length,
                        'tickets_before' => $printStats['tickets_before']
                    ];
                    
                    // 검증 로그 저장
                    $logQuery = "
                        INSERT INTO external_monitoring_logs (
                            log_type, user_id, ip_address, request_data,
                            response_data, status, created_at
                        ) VALUES (
                            'bank_verification', ?, ?, ?, ?, ?, NOW()
                        )
                    ";
                    $requestData = json_encode([
                        'ticket_code' => $ticket_code,
                        'blockchain_code' => $blockchain_code,
                        'paper_serial' => $paper_serial
                    ]);
                    $responseData = json_encode([
                        'ticket_id' => $ticket['id'],
                        'is_valid' => $verificationResult['is_valid'],
                        'serial_difference' => $serial_difference
                    ]);
                    
                    $conn->prepare($logQuery)->execute([
                        $_SESSION['user_id'],
                        $_SERVER['REMOTE_ADDR'] ?? '',
                        $requestData,
                        $responseData,
                        $verificationResult['is_valid'] ? 'success' : 'failed'
                    ]);
                } else {
                    $errors[] = "용지 사용 정보를 찾을 수 없습니다.";
                }
            }
            
        } catch (Exception $e) {
            $errors[] = "검증 중 오류가 발생했습니다: " . $e->getMessage();
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
                    <li class="breadcrumb-item"><a href="/dashboard/external-monitoring">외부 모니터링</a></li>
                    <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
                </ol>
            </nav>
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
        <!-- 검증 입력 폼 -->
        <div class="col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">티켓 정보 입력</h6>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="mb-3">
                            <label class="form-label">검증 방식</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="verification_type" 
                                           id="type_ticket" value="ticket" checked>
                                    <label class="form-check-label" for="type_ticket">
                                        티켓 코드
                                    </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="verification_type" 
                                           id="type_blockchain" value="blockchain">
                                    <label class="form-check-label" for="type_blockchain">
                                        블록체인 코드
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3" id="ticket_code_group">
                            <label for="ticket_code" class="form-label">티켓 코드</label>
                            <input type="text" name="ticket_code" id="ticket_code" class="form-control" 
                                   placeholder="예: TKT-2025-000001">
                        </div>

                        <div class="mb-3" id="blockchain_code_group" style="display: none;">
                            <label for="blockchain_code" class="form-label">블록체인 코드</label>
                            <input type="text" name="blockchain_code" id="blockchain_code" class="form-control" 
                                   placeholder="블록체인 검증 코드">
                        </div>

                        <div class="mb-3">
                            <label for="paper_serial" class="form-label">
                                용지 일련번호 <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="paper_serial" id="paper_serial" class="form-control" 
                                   placeholder="0000000000" maxlength="10" pattern="\d{10}" required>
                            <div class="form-text">
                                티켓이 인쇄된 용지 뒷면의 10자리 일련번호
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check-circle"></i> 검증
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> 초기화
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 검증 기준 안내 -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">검증 기준</h6>
                </div>
                <div class="card-body">
                    <h6>용지번호 오차 허용 범위</h6>
                    <ul>
                        <li><span class="text-success">정상</span>: ±12 이내</li>
                        <li><span class="text-warning">주의</span>: ±13 ~ ±20</li>
                        <li><span class="text-danger">위험</span>: ±20 초과</li>
                    </ul>
                    
                    <hr>
                    
                    <h6>검증 절차</h6>
                    <ol>
                        <li>티켓 코드 또는 블록체인 코드 입력</li>
                        <li>용지 뒷면 일련번호 입력</li>
                        <li>시스템이 예상 번호와 비교</li>
                        <li>오차 범위 확인 및 검증 결과 표시</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- 검증 결과 -->
        <div class="col-lg-7">
            <?php if ($verificationResult): ?>
                <?php 
                $ticket = $verificationResult['ticket'];
                $isValid = $verificationResult['is_valid'];
                $errorLevel = $verificationResult['error_level'];
                ?>
                
                <!-- 검증 결과 요약 -->
                <div class="card shadow mb-4 border-<?php echo $isValid ? 'success' : ($errorLevel == 'warning' ? 'warning' : 'danger'); ?>">
                    <div class="card-header py-3 bg-<?php echo $isValid ? 'success' : ($errorLevel == 'warning' ? 'warning' : 'danger'); ?> text-white">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-<?php echo $isValid ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                            검증 결과: <?php echo $isValid ? '정상' : ($errorLevel == 'warning' ? '주의 필요' : '위험'); ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <th width="40%">입력 번호</th>
                                        <td><strong><?php echo $verificationResult['input_serial']; ?></strong></td>
                                    </tr>
                                    <tr>
                                        <th>예상 번호</th>
                                        <td><?php echo $verificationResult['estimated_serial']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>차이</th>
                                        <td class="<?php echo $isValid ? 'text-success' : ($errorLevel == 'warning' ? 'text-warning' : 'text-danger'); ?>">
                                            <strong><?php echo ($verificationResult['serial_difference'] >= 0 ? '+' : '') . $verificationResult['serial_difference']; ?></strong>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-<?php echo $isValid ? 'success' : ($errorLevel == 'warning' ? 'warning' : 'danger'); ?> mb-0">
                                    <?php if ($isValid): ?>
                                        <i class="fas fa-check-circle"></i> 용지번호가 정상 범위 내에 있습니다.
                                    <?php elseif ($errorLevel == 'warning'): ?>
                                        <i class="fas fa-exclamation-triangle"></i> 용지번호 차이가 주의 수준입니다.
                                        추가 확인이 필요할 수 있습니다.
                                    <?php else: ?>
                                        <i class="fas fa-times-circle"></i> 용지번호 차이가 너무 큽니다.
                                        위변조 가능성을 확인하세요.
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 티켓 정보 -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">티켓 정보</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <th width="35%">티켓 코드</th>
                                        <td><strong><?php echo $ticket['ticket_code']; ?></strong></td>
                                    </tr>
                                    <tr>
                                        <th>회차</th>
                                        <td><?php echo $ticket['draw_no']; ?>회 (<?php echo date('Y-m-d', strtotime($ticket['draw_date'])); ?>)</td>
                                    </tr>
                                    <tr>
                                        <th>게임수</th>
                                        <td><?php echo $ticket['games_count']; ?>게임</td>
                                    </tr>
                                    <tr>
                                        <th>선택방식</th>
                                        <td><?php echo $ticket['selection_type'] == 'auto' ? '자동' : '수동'; ?></td>
                                    </tr>
                                    <tr>
                                        <th>발행일시</th>
                                        <td><?php echo date('Y-m-d H:i:s', strtotime($ticket['created_at'])); ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <th width="35%">판매점</th>
                                        <td><?php echo htmlspecialchars($ticket['store_name']); ?> (<?php echo $ticket['store_code']; ?>)</td>
                                    </tr>
                                    <tr>
                                        <th>금액</th>
                                        <td>₩ <?php echo number_format($ticket['ticket_amount']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>상태</th>
                                        <td>
                                            <span class="badge bg-<?php echo $ticket['status'] == 'valid' ? 'success' : 'secondary'; ?>">
                                                <?php echo $ticket['status'] == 'valid' ? '유효' : '무효'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>당첨여부</th>
                                        <td>
                                            <?php if ($ticket['prize_tier']): ?>
                                                <span class="badge bg-warning text-dark">
                                                    <?php echo $ticket['prize_tier']; ?>등 당첨
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">미당첨</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php if ($ticket['prize_amount']): ?>
                                    <tr>
                                        <th>당첨금</th>
                                        <td class="text-primary fw-bold">₩ <?php echo number_format($ticket['prize_amount']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>

                        <!-- 선택 번호 -->
                        <?php if ($ticket['selected_numbers']): ?>
                            <hr>
                            <h6 class="font-weight-bold">선택 번호</h6>
                            <?php 
                            $games = json_decode($ticket['selected_numbers'], true);
                            foreach ($games as $idx => $numbers):
                            ?>
                                <div class="mb-2">
                                    <strong>게임 <?php echo chr(65 + $idx); ?>:</strong>
                                    <?php foreach ($numbers as $num): ?>
                                        <span class="badge bg-primary rounded-circle" style="width: 30px; height: 30px; line-height: 22px;">
                                            <?php echo $num; ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 추가 정보 -->
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-secondary">검증 상세 정보</h6>
                    </div>
                    <div class="card-body">
                        <small class="text-muted">
                            <strong>계산 기준:</strong><br>
                            - 발행 시각까지 인쇄된 길이: <?php echo number_format($verificationResult['printed_length']); ?>mm<br>
                            - 당일 발행 순서: <?php echo $verificationResult['tickets_before'] + 1; ?>번째 티켓<br>
                            - 일련번호 간격: 70mm<br>
                            - 허용 오차: ±12
                        </small>
                    </div>
                </div>
            <?php else: ?>
                <!-- 초기 화면 -->
                <div class="card shadow">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-shield-alt fa-5x text-muted mb-4"></i>
                        <h5 class="text-muted">티켓 정보를 입력하여 검증을 시작하세요</h5>
                        <p class="text-muted">
                            용지 일련번호를 통해 티켓의 진위를 확인할 수 있습니다.
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// 검증 방식 토글
document.querySelectorAll('input[name="verification_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        if (this.value === 'ticket') {
            document.getElementById('ticket_code_group').style.display = 'block';
            document.getElementById('blockchain_code_group').style.display = 'none';
            document.getElementById('ticket_code').required = true;
            document.getElementById('blockchain_code').required = false;
        } else {
            document.getElementById('ticket_code_group').style.display = 'none';
            document.getElementById('blockchain_code_group').style.display = 'block';
            document.getElementById('ticket_code').required = false;
            document.getElementById('blockchain_code').required = true;
        }
    });
});
</script>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
