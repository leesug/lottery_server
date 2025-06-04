<?php
/**
 * 추첨 실행 페이지
 * 
 * 이 페이지는 복권 당첨 번호를 추첨하고 등록하는 기능을 제공합니다.
 * - 추첨 준비
 * - 추첨 실행
 * - 추첨 결과 확인
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 데이터베이스 연결
$db = get_db_connection();

// 현재 페이지 정보
$pageTitle = "추첨 실행";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 메시지 초기화
$message = '';
$message_type = '';

// 추첨 ID 검증
$draw_id = isset($_GET['draw_id']) ? intval($_GET['draw_id']) : 0;
if ($draw_id <= 0) {
    header("Location: plan.php?error=invalid_draw");
    exit;
}

// 템플릿 헤더 포함
include_once TEMPLATES_PATH . '/dashboard_header.php';
?>

<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><?php echo $pageTitle; ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?php echo SERVER_URL; ?>/dashboard/index.php">홈</a></li>
                    <li class="breadcrumb-item">추첨 관리</li>
                    <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- /.content-header -->

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- 알림 메시지 -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-12">
                <!-- 추첨 실행 카드 -->
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">추첨 실행 - <?php echo $draw_id; ?>번 추첨</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-box bg-gradient-info">
                                    <span class="info-box-icon"><i class="fas fa-ticket-alt"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">추첨 준비 상태</span>
                                        <span class="info-box-number">판매 종료 완료</span>
                                        <div class="progress">
                                            <div class="progress-bar" style="width: 100%"></div>
                                        </div>
                                        <span class="progress-description">
                                            추첨 준비 완료
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-box bg-gradient-success">
                                    <span class="info-box-icon"><i class="fas fa-money-bill-wave"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">예상 당첨금</span>
                                        <span class="info-box-number">₹ 10,000,000</span>
                                        <div class="progress">
                                            <div class="progress-bar" style="width: 90%"></div>
                                        </div>
                                        <span class="progress-description">
                                            총 5,000 매 판매 완료
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="card card-default">
                                    <div class="card-header">
                                        <h3 class="card-title">추첨 실행 단계</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <!-- 추첨 단계 -->
                                                <div class="stepper-wrapper">
                                                    <div class="stepper-item completed">
                                                        <div class="step-counter"><i class="fas fa-check"></i></div>
                                                        <div class="step-name">판매 종료</div>
                                                    </div>
                                                    <div class="stepper-item completed">
                                                        <div class="step-counter"><i class="fas fa-check"></i></div>
                                                        <div class="step-name">데이터 검증</div>
                                                    </div>
                                                    <div class="stepper-item active">
                                                        <div class="step-counter">3</div>
                                                        <div class="step-name">추첨 실행</div>
                                                    </div>
                                                    <div class="stepper-item">
                                                        <div class="step-counter">4</div>
                                                        <div class="step-name">결과 공개</div>
                                                    </div>
                                                    <div class="stepper-item">
                                                        <div class="step-counter">5</div>
                                                        <div class="step-name">정산 처리</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row mt-4">
                                            <div class="col-md-12 text-center">
                                                <button type="button" class="btn btn-lg btn-success" id="executeDrawBtn">
                                                    <i class="fas fa-random mr-2"></i> 추첨 실행하기
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div class="row mt-4" id="resultSection" style="display: none;">
                                            <div class="col-md-12">
                                                <div class="alert alert-success">
                                                    <h5><i class="icon fas fa-check"></i> 추첨 완료!</h5>
                                                    추첨이 성공적으로 완료되었습니다. 추첨 결과를 확인하세요.
                                                </div>
                                                
                                                <div class="text-center">
                                                    <a href="results.php?draw_id=<?php echo $draw_id; ?>" class="btn btn-primary">
                                                        <i class="fas fa-trophy mr-2"></i> 추첨 결과 보기
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="row">
                            <div class="col-md-6">
                                <a href="plan.php" class="btn btn-secondary btn-lg">
                                    <i class="fas fa-arrow-left mr-2"></i> 추첨 계획으로 돌아가기
                                </a>
                            </div>
                            <div class="col-md-6 text-right">
                                <a href="results.php?draw_id=<?php echo $draw_id; ?>" class="btn btn-info btn-lg">
                                    <i class="fas fa-eye mr-2"></i> 추첨 결과 보기
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.stepper-wrapper {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
    position: relative;
}

.stepper-item {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
}

.stepper-item::before {
    position: absolute;
    content: "";
    border-bottom: 2px solid #ccc;
    width: 100%;
    top: 20px;
    left: -50%;
    z-index: 1;
}

.stepper-item::after {
    position: absolute;
    content: "";
    border-bottom: 2px solid #ccc;
    width: 100%;
    top: 20px;
    left: 50%;
    z-index: 1;
}

.stepper-item:first-child::before {
    content: none;
}

.stepper-item:last-child::after {
    content: none;
}

.step-counter {
    position: relative;
    z-index: 5;
    display: flex;
    justify-content: center;
    align-items: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #ccc;
    margin-bottom: 6px;
    color: #fff;
    font-weight: bold;
}

.stepper-item.active .step-counter {
    background-color: #007bff;
}

.stepper-item.completed .step-counter {
    background-color: #28a745;
}

.step-name {
    font-size: 14px;
    text-align: center;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 추첨 실행 버튼 클릭 이벤트
    document.getElementById('executeDrawBtn').addEventListener('click', function() {
        // 버튼 비활성화 및 로딩 상태로 변경
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> 추첨 진행 중...';
        
        // 시뮬레이션: 3초 후 추첨 결과 표시
        setTimeout(function() {
            // 추첨 결과 섹션 표시
            document.getElementById('resultSection').style.display = 'block';
            
            // 버튼 상태 변경
            document.getElementById('executeDrawBtn').classList.remove('btn-success');
            document.getElementById('executeDrawBtn').classList.add('btn-secondary');
            document.getElementById('executeDrawBtn').innerHTML = '<i class="fas fa-check mr-2"></i> 추첨 완료됨';
            
            // 콘솔에 로그 남기기
            console.log('추첨 ID ' + <?php echo $draw_id; ?> + ' 에 대한 추첨이 완료되었습니다.');
        }, 3000);
    });
});
</script>

<?php
// 템플릿 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';
?>