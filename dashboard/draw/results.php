<?php
/**
 * 추첨 결과 페이지
 * 
 * 이 페이지는 복권 추첨 결과를 조회하고 당첨자 통계를 제공하는 기능을 제공합니다.
 * - 추첨 결과 공개
 * - 당첨 번호 관리
 * - 당첨 통계
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 데이터베이스 연결
$db = get_db_connection();

// 현재 페이지 정보
$pageTitle = "추첨 결과";
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
                <!-- 추첨 결과 카드 -->
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">추첨 결과 - <?php echo $draw_id; ?>번 추첨</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-box bg-gradient-success">
                                    <span class="info-box-icon"><i class="fas fa-trophy"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">당첨 번호</span>
                                        <span class="info-box-number">13 - 24 - 32 - 39 - 45 - 48</span>
                                        <div class="progress">
                                            <div class="progress-bar" style="width: 100%"></div>
                                        </div>
                                        <span class="progress-description">
                                            보너스 번호: 7
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-box bg-gradient-info">
                                    <span class="info-box-icon"><i class="fas fa-money-bill-wave"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">당첨금 총액</span>
                                        <span class="info-box-number">₹ 12,500,000</span>
                                        <div class="progress">
                                            <div class="progress-bar" style="width: 100%"></div>
                                        </div>
                                        <span class="progress-description">
                                            총 15명 당첨
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">당첨 번호 상세</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-12 text-center mb-4">
                                                <div class="lottery-balls">
                                                    <span class="lottery-ball">13</span>
                                                    <span class="lottery-ball">24</span>
                                                    <span class="lottery-ball">32</span>
                                                    <span class="lottery-ball">39</span>
                                                    <span class="lottery-ball">45</span>
                                                    <span class="lottery-ball">48</span>
                                                    <span class="lottery-ball bonus">7</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>등수</th>
                                                        <th>맞춘 번호</th>
                                                        <th>당첨자 수</th>
                                                        <th>1인당 당첨금</th>
                                                        <th>총 당첨금</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>1등</td>
                                                        <td>6개 번호 일치</td>
                                                        <td>1명</td>
                                                        <td>₹ 5,000,000</td>
                                                        <td>₹ 5,000,000</td>
                                                    </tr>
                                                    <tr>
                                                        <td>2등</td>
                                                        <td>5개 번호 + 보너스 번호</td>
                                                        <td>2명</td>
                                                        <td>₹ 1,500,000</td>
                                                        <td>₹ 3,000,000</td>
                                                    </tr>
                                                    <tr>
                                                        <td>3등</td>
                                                        <td>5개 번호 일치</td>
                                                        <td>3명</td>
                                                        <td>₹ 500,000</td>
                                                        <td>₹ 1,500,000</td>
                                                    </tr>
                                                    <tr>
                                                        <td>4등</td>
                                                        <td>4개 번호 일치</td>
                                                        <td>9명</td>
                                                        <td>₹ 50,000</td>
                                                        <td>₹ 450,000</td>
                                                    </tr>
                                                    <tr>
                                                        <td>5등</td>
                                                        <td>3개 번호 일치</td>
                                                        <td>255명</td>
                                                        <td>₹ 10,000</td>
                                                        <td>₹ 2,550,000</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">통계</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container" style="height: 250px;">
                                            <canvas id="winnerChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">판매 정보</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container" style="height: 250px;">
                                            <canvas id="salesChart"></canvas>
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
                                <a href="execution.php?draw_id=<?php echo $draw_id; ?>" class="btn btn-warning btn-lg">
                                    <i class="fas fa-edit mr-2"></i> 추첨 실행 페이지
                                </a>
                                <button type="button" class="btn btn-success btn-lg" id="exportResultBtn">
                                    <i class="fas fa-file-export mr-2"></i> 결과 내보내기
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.lottery-balls {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.lottery-ball {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background-color: #007bff;
    color: white;
    font-weight: bold;
    font-size: 24px;
}

.lottery-ball.bonus {
    background-color: #dc3545;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 차트 데이터 및 설정
    const winnerCtx = document.getElementById('winnerChart').getContext('2d');
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    
    // 당첨자 분포 차트
    const winnerChart = new Chart(winnerCtx, {
        type: 'pie',
        data: {
            labels: ['1등', '2등', '3등', '4등', '5등'],
            datasets: [{
                data: [1, 2, 3, 9, 255],
                backgroundColor: [
                    '#dc3545',
                    '#fd7e14',
                    '#ffc107',
                    '#28a745',
                    '#17a2b8'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            title: {
                display: true,
                text: '당첨자 분포'
            }
        }
    });
    
    // 판매 현황 차트
    const salesChart = new Chart(salesCtx, {
        type: 'bar',
        data: {
            labels: ['온라인', '판매점', '앱'],
            datasets: [{
                label: '판매량',
                data: [1820, 2935, 245],
                backgroundColor: '#007bff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            title: {
                display: true,
                text: '판매 채널별 현황'
            },
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true
                    }
                }]
            }
        }
    });
    
    // 결과 내보내기 버튼 클릭 이벤트
    document.getElementById('exportResultBtn').addEventListener('click', function() {
        // 실제로는 서버에 요청하여 파일을 내보내는 기능이 구현되어야 함
        alert('추첨 결과가 CSV 파일로 내보내집니다.');
        console.log('추첨 ID ' + <?php echo $draw_id; ?> + ' 결과 내보내기 요청됨');
    });
});
</script>

<?php
// 템플릿 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';
?>