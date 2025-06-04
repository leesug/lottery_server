<?php
/**
 * 당첨금 설정 페이지
 * 
 * 이 페이지는 복권 당첨금 구조 설정 및 관리 기능을 제공합니다.
 * - 당첨금 분배 비율 설정
 * - 당첨금 한도 설정
 * - 이월금 규칙 설정
 */

// 필요한 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 페이지 제목 설정
$pageTitle = "당첨금 설정";
$currentSection = "prize";
$currentPage = "settings.php";

// 데이터베이스 연결
$conn = getDBConnection();

// 메시지 처리
$message = '';
$message_type = '';

// 당첨금 설정 저장 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        // CSRF 토큰 검증
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("보안 토큰이 유효하지 않습니다. 페이지를 새로고침한 후 다시 시도해주세요.");
        }
        
        // 상품별 당첨금 설정
        if ($_POST['action'] === 'save_prize_settings') {
            $product_id = intval($_POST['product_id']);
            
            if ($product_id <= 0) {
                throw new Exception("복권 상품을 선택해주세요.");
            }
            
            // 당첨금 구조 구성
            $prize_structure = [];
            
            // 1등부터 5등까지의 분배 비율
            for ($i = 1; $i <= 5; $i++) {
                $percentage = isset($_POST["tier{$i}_percentage"]) ? floatval($_POST["tier{$i}_percentage"]) : 0;
                
                if ($percentage < 0 || $percentage > 100) {
                    throw new Exception("{$i}등 당첨금 비율은 0에서 100 사이여야 합니다.");
                }
                
                $prize_structure[$i] = $percentage;
            }
            
            // 총 비율이 100%인지 확인
            $total_percentage = array_sum($prize_structure);
            if (abs($total_percentage - 100) > 0.01) {
                throw new Exception("당첨금 분배 비율의 합이 100%여야 합니다. 현재: {$total_percentage}%");
            }
            
            // 최소 당첨금 설정
            $min_prizes = [];
            for ($i = 1; $i <= 5; $i++) {
                $min_prizes[$i] = isset($_POST["tier{$i}_min_prize"]) ? intval($_POST["tier{$i}_min_prize"]) : 0;
                
                if ($min_prizes[$i] < 0) {
                    throw new Exception("{$i}등 최소 당첨금은 0 이상이어야 합니다.");
                }
            }
            
            // 최대 당첨금 설정
            $max_prizes = [];
            for ($i = 1; $i <= 5; $i++) {
                $max_prizes[$i] = isset($_POST["tier{$i}_max_prize"]) ? intval($_POST["tier{$i}_max_prize"]) : 0;
                
                // 최대 당첨금이 0인 경우 무제한
                if ($max_prizes[$i] > 0 && $max_prizes[$i] < $min_prizes[$i]) {
                    throw new Exception("{$i}등 최대 당첨금은 최소 당첨금보다 크거나 같아야 합니다.");
                }
            }
            
            // 이월 규칙 설정
            $carryover_rules = [];
            for ($i = 1; $i <= 5; $i++) {
                $carryover_rules[$i] = [
                    'enabled' => isset($_POST["tier{$i}_carryover_enabled"]) ? 1 : 0,
                    'no_winner' => isset($_POST["tier{$i}_carryover_no_winner"]) ? 1 : 0,
                    'target_tier' => isset($_POST["tier{$i}_carryover_target"]) ? intval($_POST["tier{$i}_carryover_target"]) : 1,
                    'percentage' => isset($_POST["tier{$i}_carryover_percentage"]) ? floatval($_POST["tier{$i}_carryover_percentage"]) : 100
                ];
                
                // 유효성 검사
                if ($carryover_rules[$i]['enabled'] && 
                    ($carryover_rules[$i]['percentage'] <= 0 || 
                     $carryover_rules[$i]['percentage'] > 100)) {
                    throw new Exception("{$i}등 이월 비율은 0보다 크고 100 이하여야 합니다.");
                }
            }
            
            // 당첨금 풀 비율 설정
            $prize_pool_percentage = isset($_POST['prize_pool_percentage']) ? floatval($_POST['prize_pool_percentage']) : 50;
            
            if ($prize_pool_percentage <= 0 || $prize_pool_percentage > 100) {
                throw new Exception("당첨금 풀 비율은 0보다 크고 100 이하여야 합니다.");
            }
            
            // 설정 저장용 데이터 구성
            $settings_data = [
                'prize_structure' => $prize_structure,
                'min_prizes' => $min_prizes,
                'max_prizes' => $max_prizes,
                'carryover_rules' => $carryover_rules,
                'prize_pool_percentage' => $prize_pool_percentage
            ];
            
            // JSON으로 변환
            $settings_json = json_encode($settings_data);
            
            // DB 업데이트를 대신 system_settings 테이블에 저장
            $query = "
                INSERT INTO system_settings
                    (setting_key, setting_value, updated_by, updated_at)
                VALUES
                    ('prize_structure_" . $product_id . "', ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    setting_value = VALUES(setting_value),
                    updated_by = VALUES(updated_by),
                    updated_at = NOW()
            ";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([
                $settings_json,
                $_SESSION['user_id']
            ]);
            
            $message = "당첨금 설정이 성공적으로 저장되었습니다.";
            $message_type = "success";
        }
        // 당첨금 분배 모드 설정
        else if ($_POST['action'] === 'save_prize_mode') {
            $prize_mode = $_POST['prize_mode'];
            $valid_modes = ['percentage', 'fixed', 'hybrid'];
            
            if (!in_array($prize_mode, $valid_modes)) {
                throw new Exception("유효하지 않은 당첨금 분배 모드입니다.");
            }
            
            // 시스템 설정 업데이트
            $query = "
                INSERT INTO system_settings
                    (setting_key, setting_value, updated_by, updated_at)
                VALUES
                    ('prize_distribution_mode', ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    setting_value = VALUES(setting_value),
                    updated_by = VALUES(updated_by),
                    updated_at = NOW()
            ";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([
                $prize_mode,
                $_SESSION['user_id']
            ]);
            
            $message = "당첨금 분배 모드가 성공적으로 저장되었습니다.";
            $message_type = "success";
        }
    } catch (Exception $e) {
        $message = "오류: " . $e->getMessage();
        $message_type = "danger";
    }
}

// CSRF 토큰 생성
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 복권 상품 목록 가져오기
function getLotteryProducts($conn) {
    $query = "SELECT id, product_code, name FROM lottery_products WHERE status = 'active' ORDER BY name";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 특정 복권 상품 정보 가져오기
function getLotteryProduct($conn, $product_id) {
    $query = "SELECT id, product_code, name, price, description FROM lottery_products WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$product_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// 당첨금 분배 모드 가져오기
function getPrizeDistributionMode($conn) {
    $query = "SELECT setting_value FROM system_settings WHERE setting_key = 'prize_distribution_mode'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? $result['setting_value'] : 'percentage';
}

// 필요한 데이터 가져오기
$lottery_products = getLotteryProducts($conn);
$prize_mode = getPrizeDistributionMode($conn);

// 현재 선택된 상품
$selected_product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : (count($lottery_products) > 0 ? $lottery_products[0]['id'] : 0);
$selected_product = $selected_product_id > 0 ? getLotteryProduct($conn, $selected_product_id) : null;

// 당첨금 설정 파싱
$prize_settings = null;
if ($selected_product) {
    // system_settings 테이블에서 상품별 당첨금 설정 가져오기
    $query = "SELECT setting_value FROM system_settings WHERE setting_key = 'prize_structure_" . $selected_product_id . "'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && !empty($result['setting_value'])) {
        $prize_settings = json_decode($result['setting_value'], true);
    }
}

// 기본 당첨금 설정
if (!$prize_settings) {
    $prize_settings = [
        'prize_structure' => [
            1 => 75, // 1등: 총 상금의 75%
            2 => 12.5, // 2등: 총 상금의 12.5%
            3 => 6.25, // 3등: 총 상금의 6.25%
            4 => 4, // 4등: 총 상금의 4%
            5 => 2.25 // 5등: 총 상금의 2.25%
        ],
        'min_prizes' => [
            1 => 0, // 1등: 최소 금액 없음
            2 => 0, // 2등: 최소 금액 없음
            3 => 0, // 3등: 최소 금액 없음
            4 => 50000, // 4등: 최소 5만
            5 => 5000 // 5등: 최소 5천
        ],
        'max_prizes' => [
            1 => 0, // 1등: 최대 금액 없음 (무제한)
            2 => 0, // 2등: 최대 금액 없음 (무제한)
            3 => 0, // 3등: 최대 금액 없음 (무제한)
            4 => 0, // 4등: 최대 금액 없음 (무제한)
            5 => 0 // 5등: 최대 금액 없음 (무제한)
        ],
        'carryover_rules' => [
            1 => [
                'enabled' => 1, // 이월 활성화
                'no_winner' => 1, // 당첨자 없을 때만 이월
                'target_tier' => 1, // 다음 회차 1등으로 이월
                'percentage' => 100 // 100% 이월
            ],
            2 => [
                'enabled' => 0, // 이월 비활성화
                'no_winner' => 1,
                'target_tier' => 1,
                'percentage' => 100
            ],
            3 => [
                'enabled' => 0,
                'no_winner' => 1,
                'target_tier' => 1,
                'percentage' => 100
            ],
            4 => [
                'enabled' => 0,
                'no_winner' => 1,
                'target_tier' => 1,
                'percentage' => 100
            ],
            5 => [
                'enabled' => 0,
                'no_winner' => 1,
                'target_tier' => 1,
                'percentage' => 100
            ]
        ],
        'prize_pool_percentage' => 50 // 판매액의 50%를 당첨금 풀로 사용
    ];
}

// 템플릿 헤더 포함
include_once TEMPLATES_PATH . '/dashboard_header.php';
include_once TEMPLATES_PATH . '/page_header.php';
?>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
            
            <!-- 당첨금 분배 모드 설정 -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">당첨금 분배 모드 설정</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action" value="save_prize_mode">
                        
                        <div class="form-group">
                            <label>당첨금 분배 모드</label>
                            <div class="custom-control custom-radio">
                                <input class="custom-control-input" type="radio" id="mode_percentage" name="prize_mode" value="percentage" <?php echo $prize_mode === 'percentage' ? 'checked' : ''; ?>>
                                <label for="mode_percentage" class="custom-control-label">비율 모드: 당첨금 풀을 각 등수별 비율에 따라 분배</label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input class="custom-control-input" type="radio" id="mode_fixed" name="prize_mode" value="fixed" <?php echo $prize_mode === 'fixed' ? 'checked' : ''; ?>>
                                <label for="mode_fixed" class="custom-control-label">고정 모드: 등수별로 미리 정해진 금액을 지급</label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input class="custom-control-input" type="radio" id="mode_hybrid" name="prize_mode" value="hybrid" <?php echo $prize_mode === 'hybrid' ? 'checked' : ''; ?>>
                                <label for="mode_hybrid" class="custom-control-label">혼합 모드: 상위 등수는 비율로, 하위 등수는 고정 금액으로 지급</label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">모드 저장</button>
                    </form>
                </div>
            </div>
            
            <!-- 상품별 당첨금 설정 -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">상품별 당첨금 설정</h3>
                </div>
                <div class="card-body">
                    <!-- 상품 선택 -->
                    <div class="form-group">
                        <label>복권 상품 선택</label>
                        <div class="input-group">
                            <select class="form-control" id="product_selector">
                                <option value="">상품을 선택하세요</option>
                                <?php foreach ($lottery_products as $product): ?>
                                    <option value="<?php echo $product['id']; ?>" <?php echo $selected_product_id == $product['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($product['name']); ?> (<?php echo htmlspecialchars($product['product_code']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" id="btn_select_product">선택</button>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($selected_product): ?>
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="action" value="save_prize_settings">
                            <input type="hidden" name="product_id" value="<?php echo $selected_product['id']; ?>">
                            
                            <div class="card">
                                <div class="card-header bg-info">
                                    <h4 class="card-title"><?php echo htmlspecialchars($selected_product['name']); ?> 당첨금 설정</h4>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label>당첨금 풀 비율 (%)</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="prize_pool_percentage" step="0.01" min="0" max="100" value="<?php echo $prize_settings['prize_pool_percentage']; ?>" required>
                                            <div class="input-group-append">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">전체 판매액 중 당첨금으로 사용할 비율입니다.</small>
                                    </div>
                                    
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr class="bg-light">
                                                    <th>등수</th>
                                                    <th>분배 비율 (%)</th>
                                                    <th>최소 당첨금</th>
                                                    <th>최대 당첨금</th>
                                                    <th>이월 설정</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <tr>
                                                        <td><strong><?php echo $i; ?>등</strong></td>
                                                        <td>
                                                            <div class="input-group">
                                                                <input type="number" class="form-control percentage-input" name="tier<?php echo $i; ?>_percentage" step="0.01" min="0" max="100" value="<?php echo $prize_settings['prize_structure'][$i]; ?>" required>
                                                                <div class="input-group-append">
                                                                    <span class="input-group-text">%</span>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text">NPR</span>
                                                                </div>
                                                                <input type="number" class="form-control" name="tier<?php echo $i; ?>_min_prize" min="0" value="<?php echo $prize_settings['min_prizes'][$i]; ?>">
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text">NPR</span>
                                                                </div>
                                                                <input type="number" class="form-control" name="tier<?php echo $i; ?>_max_prize" min="0" value="<?php echo $prize_settings['max_prizes'][$i]; ?>">
                                                                <small class="form-text text-muted">0 = 무제한</small>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="custom-control custom-checkbox mb-2">
                                                                <input type="checkbox" class="custom-control-input" id="tier<?php echo $i; ?>_carryover_enabled" name="tier<?php echo $i; ?>_carryover_enabled" <?php echo $prize_settings['carryover_rules'][$i]['enabled'] ? 'checked' : ''; ?>>
                                                                <label class="custom-control-label" for="tier<?php echo $i; ?>_carryover_enabled">이월 활성화</label>
                                                            </div>
                                                            <div class="custom-control custom-checkbox mb-2">
                                                                <input type="checkbox" class="custom-control-input" id="tier<?php echo $i; ?>_carryover_no_winner" name="tier<?php echo $i; ?>_carryover_no_winner" <?php echo $prize_settings['carryover_rules'][$i]['no_winner'] ? 'checked' : ''; ?>>
                                                                <label class="custom-control-label" for="tier<?php echo $i; ?>_carryover_no_winner">당첨자 없을 때만 이월</label>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>이월 대상 등수</label>
                                                                <select class="form-control" name="tier<?php echo $i; ?>_carryover_target">
                                                                    <?php for ($j = 1; $j <= 5; $j++): ?>
                                                                        <option value="<?php echo $j; ?>" <?php echo $prize_settings['carryover_rules'][$i]['target_tier'] == $j ? 'selected' : ''; ?>>
                                                                            <?php echo $j; ?>등
                                                                        </option>
                                                                    <?php endfor; ?>
                                                                </select>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>이월 비율 (%)</label>
                                                                <div class="input-group">
                                                                    <input type="number" class="form-control" name="tier<?php echo $i; ?>_carryover_percentage" step="0.01" min="0" max="100" value="<?php echo $prize_settings['carryover_rules'][$i]['percentage']; ?>">
                                                                    <div class="input-group-append">
                                                                        <span class="input-group-text">%</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endfor; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <div class="alert alert-info">
                                        <strong>참고:</strong> 당첨금 분배 비율의 합은 100%가 되어야 합니다. <span id="total_percentage" class="font-weight-bold">현재: <?php echo array_sum($prize_settings['prize_structure']); ?>%</span>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">설정 저장</button>
                                </div>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-info">
                            설정할 복권 상품을 선택해주세요.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 상품 선택 버튼 이벤트
    document.getElementById('btn_select_product').addEventListener('click', function() {
        var productId = document.getElementById('product_selector').value;
        if (productId) {
            window.location.href = 'settings.php?product_id=' + productId;
        }
    });
    
    // 퍼센트 입력 값 변경 시 합계 계산
    var percentageInputs = document.querySelectorAll('.percentage-input');
    percentageInputs.forEach(function(input) {
        input.addEventListener('input', calculateTotalPercentage);
    });
    
    function calculateTotalPercentage() {
        var total = 0;
        percentageInputs.forEach(function(input) {
            total += parseFloat(input.value || 0);
        });
        
        var totalElement = document.getElementById('total_percentage');
        totalElement.textContent = '현재: ' + total.toFixed(2) + '%';
        
        // 100%에 가까우면 녹색, 아니면 빨간색으로 표시
        if (Math.abs(total - 100) < 0.01) {
            totalElement.className = 'font-weight-bold text-success';
        } else {
            totalElement.className = 'font-weight-bold text-danger';
        }
    }
});
</script>

    </div>
</section>
<!-- /.content -->

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
