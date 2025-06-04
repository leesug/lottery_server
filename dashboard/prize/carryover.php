<?php
/**
 * 당첨금 이월 페이지
 * 
 * 이 페이지는 복권 당첨금 이월 관리 기능을 제공합니다.
 * - 이월금 내역 조회
 * - 이월금 계획 관리
 * - 이월금 적용
 */

// 필요한 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 페이지 제목 설정
$pageTitle = "당첨금 이월";
$currentSection = "prize";
$currentPage = "carryover.php";

// 데이터베이스 연결
$conn = getDBConnection();

// 메시지 처리
$message = '';
$message_type = '';

// 이월금 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        // CSRF 토큰 검증
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("보안 토큰이 유효하지 않습니다. 페이지를 새로고침한 후 다시 시도해주세요.");
        }
        
        // 이월금 적용
        if ($_POST['action'] === 'apply_carryover') {
            $source_draw_id = intval($_POST['source_draw_id']);
            $target_draw_id = intval($_POST['target_draw_id']);
            $carryover_amount = floatval($_POST['carryover_amount']);
            $carryover_tier = intval($_POST['carryover_tier']);
            $notes = $_POST['notes'];
            
            if ($source_draw_id <= 0) {
                throw new Exception("유효하지 않은 원본 추첨입니다.");
            }
            
            if ($target_draw_id <= 0) {
                throw new Exception("유효하지 않은 대상 추첨입니다.");
            }
            
            if ($carryover_amount <= 0) {
                throw new Exception("이월 금액은 0보다 커야 합니다.");
            }
            
            if ($carryover_tier <= 0 || $carryover_tier > 5) {
                throw new Exception("유효하지 않은 당첨 등수입니다.");
            }
            
            // 원본 추첨 정보 확인
            $source_draw = getDrawInfo($conn, $source_draw_id);
            if (!$source_draw) {
                throw new Exception("원본 추첨 정보를 찾을 수 없습니다.");
            }
            
            // 대상 추첨 정보 확인
            $target_draw = getDrawInfo($conn, $target_draw_id);
            if (!$target_draw) {
                throw new Exception("대상 추첨 정보를 찾을 수 없습니다.");
            }
            
            // 원본 추첨이 완료되었는지 확인
            if ($source_draw['status'] !== 'completed') {
                throw new Exception("원본 추첨이 아직 완료되지 않았습니다.");
            }
            
            // 대상 추첨이 미래 일정인지 확인
            if ($target_draw['status'] === 'completed') {
                throw new Exception("이미 완료된 추첨으로는 이월할 수 없습니다.");
            }
            
            // 추첨 회차가 연속적인지 확인
            if ($source_draw['product_id'] != $target_draw['product_id']) {
                throw new Exception("원본 추첨과 대상 추첨의 복권 상품이 다릅니다.");
            }
            
            // 원본 추첨에 충분한 금액이 있는지 확인
            $available_amount = getUnusedCarryoverAmount($conn, $source_draw_id);
            if ($available_amount < $carryover_amount) {
                throw new Exception("사용 가능한 이월 금액(₩" . number_format($available_amount) . ")보다 많은 금액을 이월할 수 없습니다.");
            }
            
            // 트랜잭션 시작
            $conn->beginTransaction();
            
            // 이월금 기록
            $insert_query = "
                INSERT INTO prize_carryovers
                    (source_draw_id, target_draw_id, carryover_amount, carryover_tier, notes, created_by, created_at)
                VALUES
                    (?, ?, ?, ?, ?, ?, NOW())
            ";
            
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->execute([
                $source_draw_id,
                $target_draw_id,
                $carryover_amount,
                $carryover_tier,
                $notes,
                $_SESSION['user_id']
            ]);
            
            // 트랜잭션 커밋
            $conn->commit();
            
            $message = "당첨금 이월이 성공적으로 처리되었습니다.";
            $message_type = "success";
        }
        // 이월금 취소
        else if ($_POST['action'] === 'cancel_carryover' && isset($_POST['carryover_id'])) {
            $carryover_id = intval($_POST['carryover_id']);
            
            // 이월금 정보 확인
            $carryover_info = getCarryoverInfo($conn, $carryover_id);
            if (!$carryover_info) {
                throw new Exception("이월금 정보를 찾을 수 없습니다.");
            }
            
            // 대상 추첨이 아직 완료되지 않았는지 확인
            $target_draw = getDrawInfo($conn, $carryover_info['target_draw_id']);
            if ($target_draw['status'] === 'completed') {
                throw new Exception("이미 추첨이 완료된 회차의 이월금은 취소할 수 없습니다.");
            }
            
            // 트랜잭션 시작
            $conn->beginTransaction();
            
            // 이월금 취소
            $update_query = "
                UPDATE prize_carryovers
                SET
                    status = 'cancelled',
                    updated_by = ?,
                    updated_at = NOW()
                WHERE
                    id = ?
            ";
            
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->execute([
                $_SESSION['user_id'],
                $carryover_id
            ]);
            
            // 트랜잭션 커밋
            $conn->commit();
            
            $message = "당첨금 이월이 취소되었습니다.";
            $message_type = "success";
        }
    } catch (Exception $e) {
        if (isset($conn) && $conn->inTransaction()) {
            $conn->rollBack();
        }
        
        $message = "오류: " . $e->getMessage();
        $message_type = "danger";
    }
}

// CSRF 토큰 생성
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 추첨 정보 가져오기
function getDrawInfo($conn, $draw_id) {
    $query = "
        SELECT 
            ld.*,
            lp.name as product_name,
            lp.product_code
        FROM 
            draws ld
        JOIN 
            lottery_products lp ON ld.product_id = lp.id
        WHERE 
            ld.id = ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$draw_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// 이월금 정보 가져오기
function getCarryoverInfo($conn, $carryover_id) {
    $query = "
        SELECT 
            pc.*,
            sd.draw_code as source_draw_number,
            td.draw_code as target_draw_number,
            sd.draw_date as source_draw_date,
            td.draw_date as target_draw_date,
            p.name as product_name,
            p.product_code,
            u.username as created_by_name
        FROM 
            prize_carryovers pc
        JOIN 
            draws sd ON pc.source_draw_id = sd.id
        JOIN 
            draws td ON pc.target_draw_id = td.id
        JOIN 
            lottery_products p ON sd.product_id = p.id
        LEFT JOIN 
            users u ON pc.created_by = u.id
        WHERE 
            pc.id = ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$carryover_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// 이월금 목록 가져오기
function getCarryovers($conn, $limit = 50) {
    $query = "
        SELECT 
            pc.*,
            sd.draw_code as source_draw_number,
            td.draw_code as target_draw_number,
            sd.draw_date as source_draw_date,
            td.draw_date as target_draw_date,
            p.name as product_name,
            p.product_code,
            u.username as created_by_name
        FROM 
            prize_carryovers pc
        JOIN 
            draws sd ON pc.source_draw_id = sd.id
        JOIN 
            draws td ON pc.target_draw_id = td.id
        JOIN 
            lottery_products p ON sd.product_id = p.id
        LEFT JOIN 
            users u ON pc.created_by = u.id
        ORDER BY 
            pc.created_at DESC
        LIMIT ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 사용 가능한 이월 금액 계산
function getUnusedCarryoverAmount($conn, $draw_id) {
    // 해당 추첨의 당첨금 정보
    $query = "
        SELECT 
            d.id,
            d.draw_code as draw_number,
            d.product_id,
            SUM(w.prize_amount) as total_prize_amount,
            COUNT(CASE WHEN w.prize_tier = 1 THEN 1 END) as first_prize_winners
        FROM 
            draws d
        LEFT JOIN 
            tickets t ON d.product_id = t.product_id
        LEFT JOIN 
            winnings w ON t.id = w.ticket_id
        WHERE 
            d.id = ?
        GROUP BY 
            d.id, d.draw_code, d.product_id
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$draw_id]);
    $draw_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$draw_info) {
        return 0;
    }
    
    // 1등 당첨자가 있는 경우 이월 금액 없음
    if ($draw_info['first_prize_winners'] > 0) {
        return 0;
    }
    
    // 해당 추첨의 상품 정보에서 1등 당첨금 비율 확인
    $product_query = "
        SELECT prize_structure
        FROM lottery_products
        WHERE id = ?
    ";
    
    $product_stmt = $conn->prepare($product_query);
    $product_stmt->execute([$draw_info['product_id']]);
    $product_info = $product_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product_info || empty($product_info['prize_structure'])) {
        return 0;
    }
    
    $prize_settings = json_decode($product_info['prize_structure'], true);
    
    if (!isset($prize_settings['prize_structure'][1])) {
        return 0;
    }
    
    // 1등 당첨금 풀 계산
    $first_prize_percentage = $prize_settings['prize_structure'][1] / 100;
    $prize_pool_percentage = isset($prize_settings['prize_pool_percentage']) ? $prize_settings['prize_pool_percentage'] / 100 : 0.5;
    
    // 해당 추첨의 총 판매액 확인
    $sales_query = "
        SELECT SUM(price) as total_sales
        FROM tickets
        WHERE draw_id = ?
    ";
    
    $sales_stmt = $conn->prepare($sales_query);
    $sales_stmt->execute([$draw_id]);
    $sales_info = $sales_stmt->fetch(PDO::FETCH_ASSOC);
    
    $total_sales = $sales_info ? $sales_info['total_sales'] : 0;
    $first_prize_pool = $total_sales * $prize_pool_percentage * $first_prize_percentage;
    
    // 이미 사용된 이월 금액 확인
    $used_query = "
        SELECT SUM(carryover_amount) as used_amount
        FROM prize_carryovers
        WHERE source_draw_id = ? AND status = 'active'
    ";
    
    $used_stmt = $conn->prepare($used_query);
    $used_stmt->execute([$draw_id]);
    $used_info = $used_stmt->fetch(PDO::FETCH_ASSOC);
    
    $used_amount = $used_info ? $used_info['used_amount'] : 0;
    
    return max(0, $first_prize_pool - $used_amount);
}

// 이월 가능한 추첨 목록 가져오기
function getCarryoverSources($conn) {
    $query = "
        SELECT 
            d.id,
            d.draw_code,
            d.draw_date,
            d.product_id,
            p.name as product_name,
            p.product_code,
            SUM(t.price) as total_sales,
            COUNT(CASE WHEN w.prize_tier = 1 THEN 1 END) as first_prize_winners
        FROM 
            draws d
        JOIN 
            lottery_products p ON d.product_id = p.id
        LEFT JOIN 
            tickets t ON p.id = t.product_id
        LEFT JOIN 
            winnings w ON t.id = w.ticket_id
        WHERE 
            d.status = 'completed'
        GROUP BY 
            d.id, d.draw_code, d.draw_date, d.product_id, p.name, p.product_code
        HAVING 
            first_prize_winners = 0
        ORDER BY 
            d.draw_date DESC
        LIMIT 50
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $sources = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 각 추첨의 사용 가능한 이월 금액 계산
    foreach ($sources as &$source) {
        $source['available_amount'] = getUnusedCarryoverAmount($conn, $source['id']);
    }
    
    // 사용 가능한 이월 금액이 있는 추첨만 반환
    return array_filter($sources, function($source) {
        return $source['available_amount'] > 0;
    });
}

// 이월 대상 추첨 목록 가져오기
function getCarryoverTargets($conn, $product_id = 0) {
    $query = "
        SELECT 
            d.id,
            d.draw_code,
            d.draw_date,
            d.product_id,
            p.name as product_name,
            p.product_code
        FROM 
            draws d
        JOIN 
            lottery_products p ON d.product_id = p.id
        WHERE 
            d.status = 'scheduled'
            AND d.draw_date > NOW()
    ";
    
    $params = [];
    
    if ($product_id > 0) {
        $query .= " AND d.product_id = ?";
        $params[] = $product_id;
    }
    
    $query .= " ORDER BY d.draw_date ASC LIMIT 50";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 필요한 데이터 가져오기
$carryovers = getCarryovers($conn);
$carryover_sources = getCarryoverSources($conn);
$selected_source_id = isset($_GET['source_id']) ? intval($_GET['source_id']) : 0;
$carryover_targets = [];

if ($selected_source_id > 0) {
    $selected_source = null;
    foreach ($carryover_sources as $source) {
        if ($source['id'] == $selected_source_id) {
            $selected_source = $source;
            break;
        }
    }
    
    if ($selected_source) {
        $carryover_targets = getCarryoverTargets($conn, $selected_source['product_id']);
    }
}

// 템플릿 헤더 포함
include_once TEMPLATES_PATH . '/dashboard_header.php';
include_once TEMPLATES_PATH . '/page_header.php';
?>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
            
            <!-- 이월금 생성 -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">당첨금 이월 생성</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($carryover_sources)): ?>
                        <div class="form-group">
                            <label>이월 가능한 추첨 선택</label>
                            <select class="form-control" id="source_selector">
                                <option value="">추첨을 선택하세요</option>
                                <?php foreach ($carryover_sources as $source): ?>
                                    <option value="<?php echo $source['id']; ?>" <?php echo $selected_source_id == $source['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($source['product_name']); ?> 
                                        <?php echo $source['draw_code']; ?>회 
                                        (<?php echo date('Y-m-d', strtotime($source['draw_date'])); ?>) - 
                                        사용 가능액: <?php echo number_format($source['available_amount']); ?> NPR
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">1등 당첨자가 없는 완료된 추첨만 표시됩니다.</small>
                        </div>
                        
                        <div class="form-group">
                            <button type="button" class="btn btn-primary" id="btn_select_source">이월 소스 선택</button>
                        </div>
                        
                        <?php if ($selected_source_id > 0 && !empty($carryover_targets)): ?>
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="action" value="apply_carryover">
                                <input type="hidden" name="source_draw_id" value="<?php echo $selected_source_id; ?>">
                                
                                <div class="alert alert-info">
                                    <?php 
                                    foreach ($carryover_sources as $source) {
                                        if ($source['id'] == $selected_source_id) {
                                            echo "선택된 추첨: " . htmlspecialchars($source['product_name']) . " " . $source['draw_code'] . "회 (" . date('Y-m-d', strtotime($source['draw_date'])) . ")";
                                            echo "<br>사용 가능한 이월 금액: " . number_format($source['available_amount']) . " NPR";
                                            break;
                                        }
                                    }
                                    ?>
                                </div>
                                
                                <div class="form-group">
                                    <label>이월 대상 추첨</label>
                                    <select class="form-control" name="target_draw_id" required>
                                        <option value="">대상 추첨을 선택하세요</option>
                                        <?php foreach ($carryover_targets as $target): ?>
                                            <option value="<?php echo $target['id']; ?>">
                                                <?php echo htmlspecialchars($target['product_name']); ?> 
                                                <?php echo $target['draw_code']; ?>회 
                                                (<?php echo date('Y-m-d', strtotime($target['draw_date'])); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>이월 금액</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">NPR</span>
                                        </div>
                                        <input type="number" class="form-control" name="carryover_amount" min="1" 
                                               max="<?php 
                                                     foreach ($carryover_sources as $source) {
                                                         if ($source['id'] == $selected_source_id) {
                                                             echo $source['available_amount'];
                                                             break;
                                                         }
                                                     }
                                                    ?>" 
                                               required>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>이월 대상 등수</label>
                                    <select class="form-control" name="carryover_tier" required>
                                        <option value="1">1등</option>
                                        <option value="2">2등</option>
                                        <option value="3">3등</option>
                                        <option value="4">4등</option>
                                        <option value="5">5등</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>메모</label>
                                    <textarea class="form-control" name="notes" rows="3"></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-success">이월 적용</button>
                            </form>
                        <?php elseif ($selected_source_id > 0): ?>
                            <div class="alert alert-warning">
                                이월 가능한 대상 추첨이 없습니다. 미래 회차의 추첨을 먼저 등록해주세요.
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            현재 이월 가능한 추첨이 없습니다. 1등 당첨자가 없는 추첨이 완료되면 이월이 가능합니다.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- 이월금 내역 -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">이월금 내역</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($carryovers)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr class="bg-light">
                                        <th>원본 추첨</th>
                                        <th>대상 추첨</th>
                                        <th>이월 금액</th>
                                        <th>이월 등수</th>
                                        <th>상태</th>
                                        <th>생성일</th>
                                        <th>생성자</th>
                                        <th>메모</th>
                                        <th>관리</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($carryovers as $carryover): ?>
                                        <tr>
                                            <td>
                                                <?php echo htmlspecialchars($carryover['product_name']); ?> 
                                                <?php echo $carryover['source_draw_number']; ?>회
                                                <br>
                                                <small><?php echo date('Y-m-d', strtotime($carryover['source_draw_date'])); ?></small>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($carryover['product_name']); ?> 
                                                <?php echo $carryover['target_draw_number']; ?>회
                                                <br>
                                                <small><?php echo date('Y-m-d', strtotime($carryover['target_draw_date'])); ?></small>
                                            </td>
                                            <td><?php echo number_format($carryover['carryover_amount']); ?> NPR</td>
                                            <td><?php echo $carryover['carryover_tier']; ?>등</td>
                                            <td>
                                                <?php
                                                $status_class = '';
                                                $status_text = '';
                                                
                                                switch ($carryover['status']) {
                                                    case 'active':
                                                        $status_class = 'badge badge-success';
                                                        $status_text = '활성';
                                                        break;
                                                    case 'cancelled':
                                                        $status_class = 'badge badge-danger';
                                                        $status_text = '취소됨';
                                                        break;
                                                    case 'applied':
                                                        $status_class = 'badge badge-info';
                                                        $status_text = '적용됨';
                                                        break;
                                                    default:
                                                        $status_class = 'badge badge-secondary';
                                                        $status_text = '알 수 없음';
                                                }
                                                ?>
                                                <span class="<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                            </td>
                                            <td><?php echo date('Y-m-d H:i', strtotime($carryover['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($carryover['created_by_name']); ?></td>
                                            <td><?php echo htmlspecialchars($carryover['notes']); ?></td>
                                            <td>
                                                <?php if ($carryover['status'] === 'active'): ?>
                                                    <form method="POST" action="" onsubmit="return confirm('이 이월금을 취소하시겠습니까?');">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                        <input type="hidden" name="action" value="cancel_carryover">
                                                        <input type="hidden" name="carryover_id" value="<?php echo $carryover['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">취소</button>
                                                    </form>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            아직 이월금 내역이 없습니다.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 원본 추첨 선택 버튼 이벤트
    document.getElementById('btn_select_source').addEventListener('click', function() {
        var sourceId = document.getElementById('source_selector').value;
        if (sourceId) {
            window.location.href = 'carryover.php?source_id=' + sourceId;
        }
    });
});
</script>

    </div>
</section>
<!-- /.content -->

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
