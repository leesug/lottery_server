<?php
/**
 * 추첨번호 관리 페이지
 */

require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// 로그인 확인
requireLogin();

// 관리자 권한 확인
requireAdmin();

// 페이지 제목 설정
$pageTitle = '추첨번호 관리';
$pageHeader = '추첨번호 관리';

// 추가 CSS
$extraCss = '/server/assets/css/lottery.css';

// 회차 ID 확인
$drawId = isset($_GET['draw_id']) ? (int)$_GET['draw_id'] : 0;

// 더미 데이터 모드
$dummyData = true;

// 회차 정보 가져오기
$draw = null;
try {
    $draw = fetchOne("SELECT * FROM draws WHERE id = ?", [
        ['type' => 'i', 'value' => $drawId]
    ]);
    $dummyData = false;
} catch (Exception $e) {
    // 데이터베이스 연결 실패 또는 테이블 없음
    logError("회차 정보 조회 실패: " . $e->getMessage(), 'lottery');
}

// 더미 데이터 생성
if (!$draw && $drawId > 0) {
    // 더미 회차 정보 생성
    $draw = [
        'id' => $drawId,
        'draw_number' => 1000 + $drawId,
        'start_date' => date('Y-m-d', strtotime("-" . ($drawId * 7) . " days")),
        'end_date' => date('Y-m-d', strtotime("-" . ($drawId * 7 - 6) . " days")),
        'draw_date' => date('Y-m-d', strtotime("-" . ($drawId * 7 - 7) . " days")),
        'status' => $drawId == 1 ? 'active' : ($drawId == 2 ? 'pending' : 'completed'),
        'description' => (1000 + $drawId) . "회차 로또복권",
    ];
}

// 회차의 추첨번호 가져오기
$winningNumbers = null;
try {
    if (!$dummyData) {
        $winningNumbers = fetchOne("SELECT * FROM winning_numbers WHERE draw_id = ?", [
            ['type' => 'i', 'value' => $drawId]
        ]);
    }
} catch (Exception $e) {
    // 데이터베이스 연결 실패 또는 테이블 없음
    logError("추첨번호 조회 실패: " . $e->getMessage(), 'lottery');
}

// 더미 추첨번호 데이터 생성
if (!$winningNumbers && $drawId > 0) {
    // 상태가 completed인 회차만 추첨번호 생성
    if ($draw['status'] === 'completed') {
        $mainNumbers = [7, 12, 15, 24, 30, 42]; // 더미 당첨번호
        $bonusNumber = 33; // 더미 보너스 번호
        
        $winningNumbers = [
            'id' => $drawId,
            'draw_id' => $drawId,
            'numbers' => implode(',', $mainNumbers),
            'bonus_number' => $bonusNumber,
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'updated_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
        ];
    }
}

// 추첨번호 설정 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_winning_numbers'])) {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $_SESSION['flash_message'] = "잘못된 요청입니다.";
        $_SESSION['flash_type'] = "danger";
        header("Location: /server/dashboard/lottery/winning-numbers.php?draw_id=" . $drawId);
        exit;
    }
    
    // 번호 유효성 검사
    $numbers = [];
    for ($i = 1; $i <= 6; $i++) {
        $number = isset($_POST['number' . $i]) ? (int)$_POST['number' . $i] : 0;
        if ($number < 1 || $number > 45) {
            $_SESSION['flash_message'] = "로또 번호는 1부터 45 사이의 값이어야 합니다.";
            $_SESSION['flash_type'] = "danger";
            header("Location: /server/dashboard/lottery/winning-numbers.php?draw_id=" . $drawId);
            exit;
        }
        $numbers[] = $number;
    }
    
    // 중복 번호 검사
    if (count(array_unique($numbers)) !== 6) {
        $_SESSION['flash_message'] = "중복된 번호가 있습니다.";
        $_SESSION['flash_type'] = "danger";
        header("Location: /server/dashboard/lottery/winning-numbers.php?draw_id=" . $drawId);
        exit;
    }
    
    // 보너스 번호 유효성 검사
    $bonusNumber = isset($_POST['bonus_number']) ? (int)$_POST['bonus_number'] : 0;
    if ($bonusNumber < 1 || $bonusNumber > 45) {
        $_SESSION['flash_message'] = "보너스 번호는 1부터 45 사이의 값이어야 합니다.";
        $_SESSION['flash_type'] = "danger";
        header("Location: /server/dashboard/lottery/winning-numbers.php?draw_id=" . $drawId);
        exit;
    }
    
    // 보너스 번호가 당첨 번호와 중복 검사
    if (in_array($bonusNumber, $numbers)) {
        $_SESSION['flash_message'] = "보너스 번호는 당첨 번호와 중복될 수 없습니다.";
        $_SESSION['flash_type'] = "danger";
        header("Location: /server/dashboard/lottery/winning-numbers.php?draw_id=" . $drawId);
        exit;
    }
    
    // 번호 정렬 (오름차순)
    sort($numbers);
    $numbersStr = implode(',', $numbers);
    
    try {
        // 추첨번호 저장
        if (!$dummyData) {
            if ($winningNumbers) {
                // 기존 추첨번호 업데이트
                $result = execute("UPDATE winning_numbers SET numbers = ?, bonus_number = ? WHERE draw_id = ?", [
                    ['type' => 's', 'value' => $numbersStr],
                    ['type' => 'i', 'value' => $bonusNumber],
                    ['type' => 'i', 'value' => $drawId]
                ]);
            } else {
                // 새 추첨번호 등록
                $result = insert("INSERT INTO winning_numbers (draw_id, numbers, bonus_number) VALUES (?, ?, ?)", [
                    ['type' => 'i', 'value' => $drawId],
                    ['type' => 's', 'value' => $numbersStr],
                    ['type' => 'i', 'value' => $bonusNumber]
                ]);
            }
            
            if ($result) {
                // 회차 상태를 completed로 변경
                execute("UPDATE draws SET status = 'completed' WHERE id = ?", [
                    ['type' => 'i', 'value' => $drawId]
                ]);
                
                logInfo("추첨번호 설정: 회차 ID $drawId, 번호 $numbersStr, 보너스 $bonusNumber", 'lottery');
                $_SESSION['flash_message'] = "추첨번호가 성공적으로 설정되었습니다.";
                $_SESSION['flash_type'] = "success";
            } else {
                logError("추첨번호 설정 실패: 회차 ID $drawId", 'lottery');
                $_SESSION['flash_message'] = "추첨번호 설정 중 오류가 발생했습니다.";
                $_SESSION['flash_type'] = "danger";
            }
        } else {
            // 더미 데이터 모드에서는 메시지만 표시
            $_SESSION['flash_message'] = "더미 데이터 모드: 회차 ID $drawId의 추첨번호 ($numbersStr + $bonusNumber) 설정 요청됨";
            $_SESSION['flash_type'] = "warning";
        }
    } catch (Exception $e) {
        // 데이터베이스 오류 처리
        logError("추첨번호 설정 중 예외 발생: " . $e->getMessage(), 'lottery');
        $_SESSION['flash_message'] = "추첨번호 설정 중 데이터베이스 오류: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
    }
    
    header("Location: /server/dashboard/lottery/winning-numbers.php?draw_id=" . $drawId);
    exit;
}

// 추첨번호 자동 생성 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_winning_numbers'])) {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $_SESSION['flash_message'] = "잘못된 요청입니다.";
        $_SESSION['flash_type'] = "danger";
        header("Location: /server/dashboard/lottery/winning-numbers.php?draw_id=" . $drawId);
        exit;
    }
    
    // 무작위 번호 생성 (1-45 사이의 6개 번호)
    $numbers = [];
    while (count($numbers) < 6) {
        $number = rand(1, 45);
        if (!in_array($number, $numbers)) {
            $numbers[] = $number;
        }
    }
    
    // 번호 정렬 (오름차순)
    sort($numbers);
    
    // 보너스 번호 생성 (1-45 사이, 당첨 번호와 중복되지 않게)
    $bonusNumber = 0;
    while ($bonusNumber === 0) {
        $number = rand(1, 45);
        if (!in_array($number, $numbers)) {
            $bonusNumber = $number;
        }
    }
    
    $numbersStr = implode(',', $numbers);
    
    try {
        // 추첨번호 저장
        if (!$dummyData) {
            if ($winningNumbers) {
                // 기존 추첨번호 업데이트
                $result = execute("UPDATE winning_numbers SET numbers = ?, bonus_number = ? WHERE draw_id = ?", [
                    ['type' => 's', 'value' => $numbersStr],
                    ['type' => 'i', 'value' => $bonusNumber],
                    ['type' => 'i', 'value' => $drawId]
                ]);
            } else {
                // 새 추첨번호 등록
                $result = insert("INSERT INTO winning_numbers (draw_id, numbers, bonus_number) VALUES (?, ?, ?)", [
                    ['type' => 'i', 'value' => $drawId],
                    ['type' => 's', 'value' => $numbersStr],
                    ['type' => 'i', 'value' => $bonusNumber]
                ]);
            }
            
            if ($result) {
                // 회차 상태를 completed로 변경
                execute("UPDATE draws SET status = 'completed' WHERE id = ?", [
                    ['type' => 'i', 'value' => $drawId]
                ]);
                
                logInfo("추첨번호 자동 생성: 회차 ID $drawId, 번호 $numbersStr, 보너스 $bonusNumber", 'lottery');
                $_SESSION['flash_message'] = "추첨번호가 성공적으로 생성되었습니다.";
                $_SESSION['flash_type'] = "success";
            } else {
                logError("추첨번호 자동 생성 실패: 회차 ID $drawId", 'lottery');
                $_SESSION['flash_message'] = "추첨번호 생성 중 오류가 발생했습니다.";
                $_SESSION['flash_type'] = "danger";
            }
        } else {
            // 더미 데이터 모드에서는 메시지만 표시
            $_SESSION['flash_message'] = "더미 데이터 모드: 회차 ID $drawId의 추첨번호 ($numbersStr + $bonusNumber) 자동 생성됨";
            $_SESSION['flash_type'] = "warning";
        }
    } catch (Exception $e) {
        // 데이터베이스 오류 처리
        logError("추첨번호 자동 생성 중 예외 발생: " . $e->getMessage(), 'lottery');
        $_SESSION['flash_message'] = "추첨번호 자동 생성 중 데이터베이스 오류: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
    }
    
    header("Location: /server/dashboard/lottery/winning-numbers.php?draw_id=" . $drawId);
    exit;
}

// 현재 페이지 정보
$pageTitle = "당첨번호 관리";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 템플릿 헤더 포함
include_once TEMPLATES_PATH . '/dashboard_header.php';
?>

<!-- 추첨번호 관리 -->
<div class="content-wrapper">
    <!-- 페이지 헤더 -->
    <div class="page-header">
        <h2 class="page-title"><?php echo $pageHeader; ?></h2>
        <p class="page-description">로또 회차별 추첨번호를 등록하고 관리합니다.</p>
    </div>
    
    <?php if ($dummyData): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> 현재 더미 데이터를 표시하고 있습니다. 실제 데이터베이스 테이블이 없거나 연결되지 않았습니다.
        </div>
    <?php endif; ?>
    
    <!-- 회차 선택 -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">회차 선택</h5>
        </div>
        <div class="card-body">
            <?php if ($draw): ?>
                <div class="alert alert-primary">
                    <strong><?php echo $draw['draw_number']; ?>회차</strong> - 
                    판매기간: <?php echo formatDate($draw['start_date']); ?> ~ <?php echo formatDate($draw['end_date']); ?>, 
                    추첨일: <?php echo formatDate($draw['draw_date']); ?>, 
                    상태: <span class="badge badge-<?php 
                        echo $draw['status'] === 'active' ? 'success' : 
                            ($draw['status'] === 'pending' ? 'warning' : 
                                ($draw['status'] === 'completed' ? 'primary' : 'danger'));
                    ?>">
                        <?php 
                            echo $draw['status'] === 'active' ? '진행중' : 
                                ($draw['status'] === 'pending' ? '대기중' : 
                                    ($draw['status'] === 'completed' ? '완료' : '취소'));
                        ?>
                    </span>
                </div>
                
                <?php if ($draw['status'] === 'pending'): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> 아직 판매가 시작되지 않은 회차입니다.
                    </div>
                <?php elseif ($draw['status'] === 'active'): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> 현재 판매가 진행 중인 회차입니다. 판매 종료 후 추첨번호를 등록해야 합니다.
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> 회차를 선택해주세요.
                </div>
                
                <a href="/server/dashboard/lottery/draw-manage.php" class="btn btn-primary">
                    <i class="fas fa-list"></i> 회차 목록으로 이동
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($draw): ?>
        <!-- 추첨번호 관리 카드 -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><?php echo $draw['draw_number']; ?>회차 추첨번호</h5>
            </div>
            <div class="card-body">
                <?php if ($winningNumbers): ?>
                    <!-- 추첨번호 표시 -->
                    <div class="winning-numbers-display">
                        <div class="winning-numbers-title">당첨번호</div>
                        <div class="winning-numbers-balls">
                            <?php 
                                $numbers = explode(',', $winningNumbers['numbers']);
                                foreach ($numbers as $number):
                            ?>
                                <div class="winning-ball"><?php echo $number; ?></div>
                            <?php endforeach; ?>
                            <div class="winning-ball-plus">+</div>
                            <div class="winning-ball bonus"><?php echo $winningNumbers['bonus_number']; ?></div>
                        </div>
                        <div class="winning-numbers-info">
                            추첨일: <?php echo formatDate($draw['draw_date']); ?><br>
                            등록일: <?php echo formatDate($winningNumbers['created_at']); ?>
                        </div>
                        
                        <?php if ($draw['status'] !== 'canceled'): ?>
                            <div class="mt-4">
                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#editNumbersModal">
                                    <i class="fas fa-edit"></i> 번호 수정
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- 추첨번호 설정 폼 -->
                    <?php if ($draw['status'] === 'completed' || $draw['status'] === 'active'): ?>
                        <div class="winning-numbers-form">
                            <form method="post" action="">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                
                                <div class="form-group">
                                    <label>당첨번호 직접 입력</label>
                                    <div class="row">
                                        <?php for ($i = 1; $i <= 6; $i++): ?>
                                            <div class="col-md-2 col-4 mb-2">
                                                <input type="number" class="form-control" name="number<?php echo $i; ?>" min="1" max="45" required>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>보너스 번호</label>
                                    <div class="row">
                                        <div class="col-md-2 col-4">
                                            <input type="number" class="form-control" name="bonus_number" min="1" max="45" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <button type="submit" name="set_winning_numbers" class="btn btn-primary">
                                        <i class="fas fa-save"></i> 추첨번호 등록
                                    </button>
                                    <button type="submit" name="generate_winning_numbers" class="btn btn-info ml-2">
                                        <i class="fas fa-random"></i> 번호 자동 생성
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> 
                            <?php if ($draw['status'] === 'pending'): ?>
                                아직 판매가 시작되지 않은 회차입니다. 판매 종료 후 추첨번호를 등록할 수 있습니다.
                            <?php elseif ($draw['status'] === 'canceled'): ?>
                                취소된 회차입니다. 추첨번호를 등록할 수 없습니다.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if ($draw && $winningNumbers): ?>
<!-- 추첨번호 수정 모달 -->
<div class="modal fade" id="editNumbersModal" tabindex="-1" role="dialog" aria-labelledby="editNumbersModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editNumbersModalLabel"><?php echo $draw['draw_number']; ?>회차 추첨번호 수정</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="" method="post">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <div class="form-group">
                        <label>당첨번호 수정</label>
                        <div class="row">
                            <?php 
                                $numbers = explode(',', $winningNumbers['numbers']);
                                for ($i = 0; $i < 6; $i++):
                            ?>
                                <div class="col-md-2 col-4 mb-2">
                                    <input type="number" class="form-control" name="number<?php echo ($i + 1); ?>" min="1" max="45" value="<?php echo $numbers[$i]; ?>" required>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>보너스 번호 수정</label>
                        <div class="row">
                            <div class="col-md-2 col-4">
                                <input type="number" class="form-control" name="bonus_number" min="1" max="45" value="<?php echo $winningNumbers['bonus_number']; ?>" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                    <button type="submit" name="set_winning_numbers" class="btn btn-primary">저장</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- 로또 볼 스타일 -->
<style>
.winning-numbers-display {
    padding: 20px;
    background-color: #f8f9fa;
    border-radius: 10px;
    text-align: center;
    margin-bottom: 20px;
}

.winning-numbers-title {
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 15px;
}

.winning-numbers-balls {
    display: flex;
    justify-content: center;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 15px;
}

.winning-ball {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background-color: #f39c12;
    color: white;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 1.3rem;
    font-weight: bold;
}

.winning-ball-plus {
    font-size: 1.5rem;
    font-weight: bold;
    margin: 0 10px;
}

.winning-ball.bonus {
    background-color: #e74c3c;
}

.winning-numbers-info {
    font-size: 0.9rem;
    color: #6c757d;
}

.winning-numbers-form {
    max-width: 600px;
    margin: 0 auto;
}
</style>

<?php
// 자바스크립트 설정
$inlineJs = <<<JS
// 추첨번호 관리 페이지 초기화
document.addEventListener('DOMContentLoaded', function() {
    console.log('추첨번호 관리 페이지 초기화');
    
    // 번호 유효성 검사
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (e.submitter && e.submitter.name === 'generate_winning_numbers') {
                // 자동 생성 버튼이면 유효성 검사 건너뛰기
                return true;
            }
            
            // 번호 유효성 검사
            const numbers = [];
            for (let i = 1; i <= 6; i++) {
                const numberInput = this.querySelector(`[name="number\${i}"]`);
                if (numberInput) {
                    const number = parseInt(numberInput.value);
                    if (isNaN(number) || number < 1 || number > 45) {
                        e.preventDefault();
                        alert(`\${i}번째 번호는 1부터 45 사이의 값이어야 합니다.`);
                        return false;
                    }
                    numbers.push(number);
                }
            }
            
            // 번호 중복 검사
            const uniqueNumbers = [...new Set(numbers)];
            if (uniqueNumbers.length !== 6) {
                e.preventDefault();
                alert('중복된 번호가 있습니다.');
                return false;
            }
            
            // 보너스 번호 유효성 검사
            const bonusInput = this.querySelector('[name="bonus_number"]');
            if (bonusInput) {
                const bonusNumber = parseInt(bonusInput.value);
                if (isNaN(bonusNumber) || bonusNumber < 1 || bonusNumber > 45) {
                    e.preventDefault();
                    alert('보너스 번호는 1부터 45 사이의 값이어야 합니다.');
                    return false;
                }
                
                // 보너스 번호 중복 검사
                if (numbers.includes(bonusNumber)) {
                    e.preventDefault();
                    alert('보너스 번호는 당첨 번호와 중복될 수 없습니다.');
                    return false;
                }
            }
        });
    });
});
JS;

// 푸터 포함
include_once '../../templates/footer.php';
?>
