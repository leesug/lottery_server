<?php
/**
 * 추첨 대시보드 페이지
 */

// 세션 시작 및 인증 체크
session_start();

// 설정 및 공통 함수
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

// 인증 확인
check_auth();

// 데이터베이스 연결
$db = get_db_connection();

// 오늘 날짜
$today = date('Y-m-d');

// 통계 데이터 조회
$nextDraws = [];
$recentDraws = [];
$prizeSummary = [];
$drawStatistics = [];
$winningNumberFrequency = [];

try {
    // 다음 예정된 추첨 조회
    $stmt = $db->prepare("
        SELECT 
            ld.id,
            ld.draw_number, 
            lp.name AS product_name, 
            lp.id AS product_id,
            ld.draw_date,
            lp.price AS ticket_price
        FROM 
            lottery_draws ld
        JOIN 
            lottery_products lp ON ld.product_id = lp.id
        WHERE 
            ld.draw_status = 'scheduled' AND
            ld.draw_date > NOW()
        ORDER BY 
            ld.draw_date ASC
        LIMIT 5
    ");
    $stmt->execute();
    $nextDraws = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 최근 완료된 추첨 조회
    $stmt = $db->prepare("
        SELECT 
            ld.id,
            ld.draw_number, 
            lp.name AS product_name, 
            ld.draw_date,
            ld.winning_numbers,
            COUNT(DISTINCT w.id) AS winner_count,
            SUM(w.prize_amount) AS total_prize_amount
        FROM 
            lottery_draws ld
        JOIN 
            lottery_products lp ON ld.product_id = lp.id
        LEFT JOIN 
            winnings w ON ld.id = w.draw_id
        WHERE 
            ld.draw_status = 'completed'
        GROUP BY
            ld.id
        ORDER BY 
            ld.draw_date DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recentDraws = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 등급별 당첨 통계 조회
    $stmt = $db->prepare("
        SELECT 
            w.prize_tier,
            COUNT(w.id) AS winner_count,
            SUM(w.prize_amount) AS total_prize_amount
        FROM 
            winnings w
        JOIN 
            lottery_draws ld ON w.draw_id = ld.id
        WHERE 
            ld.draw_status = 'completed' AND
            DATE(ld.draw_date) >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
        GROUP BY 
            w.prize_tier
        ORDER BY 
            w.prize_tier ASC
    ");
    $stmt->execute();
    $prizeSummary = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 복권 상품별 당첨 통계 조회
    $stmt = $db->prepare("
        SELECT 
            lp.id AS product_id,
            lp.name AS product_name,
            COUNT(DISTINCT ld.id) AS draw_count,
            COUNT(DISTINCT w.id) AS winner_count,
            SUM(w.prize_amount) AS total_prize_amount
        FROM 
            lottery_products lp
        LEFT JOIN 
            lottery_draws ld ON lp.id = ld.product_id AND ld.draw_status = 'completed'
        LEFT JOIN 
            winnings w ON ld.id = w.draw_id
        WHERE 
            ld.draw_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
        GROUP BY 
            lp.id
        ORDER BY 
            total_prize_amount DESC
    ");
    $stmt->execute();
    $drawStatistics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 가장 많이 당첨된 번호 조회 (예시: 로또 6/45)
    $stmt = $db->prepare("
        SELECT 
            ld.winning_numbers
        FROM 
            lottery_draws ld
        JOIN 
            lottery_products lp ON ld.product_id = lp.id
        WHERE 
            ld.draw_status = 'completed' AND
            lp.name = '로또 6/45' AND
            ld.draw_date >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)
        ORDER BY 
            ld.draw_date DESC
        LIMIT 100
    ");
    $stmt->execute();
    $winningDraws = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 번호 빈도 계산 (예시 - 실제 구현은 번호 형식에 따라 달라질 수 있음)
    $numberFrequency = [];
    foreach ($winningDraws as $draw) {
        $numbers = explode(',', $draw['winning_numbers']);
        foreach ($numbers as $number) {
            $number = trim($number);
            if (!isset($numberFrequency[$number])) {
                $numberFrequency[$number] = 0;
            }
            $numberFrequency[$number]++;
        }
    }
    
    // 빈도순으로 정렬
    arsort($numberFrequency);
    
    // 상위 10개 추출
    $winningNumberFrequency = array_slice($numberFrequency, 0, 10, true);
    
} catch (PDOException $e) {
    // 에러 로깅
    error_log("Database error: " . $e->getMessage());
    // 에러 발생 시 기본값 유지
}

// 헤더 포함
$pageTitle = "추첨 대시보드";
include '../templates/header.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">추첨 대시보드</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">홈</a></li>
                        <li class="breadcrumb-item active">추첨 대시보드</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <!-- 다음 예정 추첨 및 최근 당첨 결과 카드 -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">다음 예정된 추첨</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>복권 상품</th>
                                        <th>회차</th>
                                        <th>추첨일</th>
                                        <th>잔여시간</th>
                                        <th>예상 당첨금</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach ($nextDraws as $draw) {
                                        $drawDate = new DateTime($draw['draw_date']);
                                        $now = new DateTime();
                                        $interval = $now->diff($drawDate);
                                        
                                        // 간단한 형식으로 남은 시간 표시
                                        $remainingTime = '';
                                        if ($interval->days > 0) {
                                            $remainingTime = $interval->format('%a일 %h시간');
                                        } else {
                                            $remainingTime = $interval->format('%h시간 %i분');
                                        }
                                        
                                        // 판매량 조회 (예시)
                                        $stmt = $db->prepare("
                                            SELECT COUNT(*) AS ticket_count
                                            FROM tickets
                                            WHERE draw_id = ?
                                        ");
                                        $stmt->execute([$draw['id']]);
                                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                        $ticketCount = $result['ticket_count'] ?? 0;
                                        
                                        // 간단한 예상 당첨금 계산 (예시 - 실제 로직은 더 복잡할 수 있음)
                                        $estPrizePool = $ticketCount * $draw['ticket_price'] * 0.5; // 티켓 판매액의 50%가 당첨금으로 가정
                                        
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($draw['product_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($draw['draw_number']) . "</td>";
                                        echo "<td>" . htmlspecialchars($drawDate->format('Y-m-d H:i')) . "</td>";
                                        echo "<td>" . htmlspecialchars($remainingTime) . "</td>";
                                        echo "<td>" . number_format($estPrizePool) . " NPR</td>";
                                        echo "</tr>";
                                    }
                                    
                                    if (empty($nextDraws)) {
                                        echo "<tr><td colspan='5' class='text-center'>예정된 추첨이 없습니다.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer text-center">
                            <a href="/server/dashboard/draw/plan.php" class="btn btn-sm btn-primary">추첨 일정 관리</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card card-success">
                        <div class="card-header">
                            <h3 class="card-title">최근 추첨 결과</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>복권 상품</th>
                                        <th>회차</th>
                                        <th>추첨일</th>
                                        <th>당첨번호</th>
                                        <th>당첨자 수</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach ($recentDraws as $draw) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($draw['product_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($draw['draw_number']) . "</td>";
                                        echo "<td>" . date('Y-m-d', strtotime($draw['draw_date'])) . "</td>";
                                        echo "<td class='text-center'><span class='badge bg-success'>" 
                                            . htmlspecialchars($draw['winning_numbers']) . "</span></td>";
                                        echo "<td>" . number_format($draw['winner_count']) . "</td>";
                                        echo "</tr>";
                                    }
                                    
                                    if (empty($recentDraws)) {
                                        echo "<tr><td colspan='5' class='text-center'>최근 추첨 결과가 없습니다.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer text-center">
                            <a href="/server/dashboard/draw/results.php" class="btn btn-sm btn-success">모든 추첨 결과 보기</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 당첨 통계 및 번호 빈도 차트 -->
            <div class="row">
                <div class="col-md-6">
                    <!-- 등급별 당첨 통계 -->
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">등급별 당첨 통계 (최근 90일)</h3>
                        </div>
                        <div class="card-body">
                            <div class="chart">
                                <canvas id="prizeTierChart" style="min-height: 300px; height: 300px; max-height: 300px; max-width: 100%;"></canvas>
                            </div>
                            <div class="table-responsive mt-3">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>등급</th>
                                            <th>당첨자 수</th>
                                            <th>총 당첨금액 (NPR)</th>
                                            <th>평균 당첨금액 (NPR)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        foreach ($prizeSummary as $prize) {
                                            $avgAmount = $prize['winner_count'] > 0 ? $prize['total_prize_amount'] / $prize['winner_count'] : 0;
                                            echo "<tr>";
                                            echo "<td>" . htmlspecialchars($prize['prize_tier']) . "등</td>";
                                            echo "<td>" . number_format($prize['winner_count']) . "</td>";
                                            echo "<td>" . number_format($prize['total_prize_amount']) . "</td>";
                                            echo "<td>" . number_format($avgAmount) . "</td>";
                                            echo "</tr>";
                                        }
                                        
                                        if (empty($prizeSummary)) {
                                            echo "<tr><td colspan='4' class='text-center'>당첨 데이터가 없습니다.</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <!-- 번호 빈도 분석 -->
                    <div class="card card-danger">
                        <div class="card-header">
                            <h3 class="card-title">자주 당첨되는 번호 (로또 6/45, 최근 1년)</h3>
                        </div>
                        <div class="card-body">
                            <div class="chart">
                                <canvas id="numberFrequencyChart" style="min-height: 300px; height: 300px; max-height: 300px; max-width: 100%;"></canvas>
                            </div>
                        </div>
                        <div class="card-footer text-center">
                            <p class="text-muted mb-0">* 이 정보는 참고용이며, 각 추첨의 결과는 무작위로 결정됩니다.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 상품별 추첨 통계 및 당첨금 지급 현황 -->
            <div class="row">
                <div class="col-md-12">
                    <!-- 상품별 추첨 통계 -->
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">상품별 추첨 통계 (최근 90일)</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead>
                                        <tr>
                                            <th>복권 상품</th>
                                            <th>진행 회차 수</th>
                                            <th>당첨자 수</th>
                                            <th>총 당첨금액 (NPR)</th>
                                            <th>회차당 평균 당첨금액 (NPR)</th>
                                            <th>당첨자당 평균 당첨금액 (NPR)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        foreach ($drawStatistics as $stat) {
                                            $avgPerDraw = $stat['draw_count'] > 0 ? $stat['total_prize_amount'] / $stat['draw_count'] : 0;
                                            $avgPerWinner = $stat['winner_count'] > 0 ? $stat['total_prize_amount'] / $stat['winner_count'] : 0;
                                            
                                            echo "<tr>";
                                            echo "<td>" . htmlspecialchars($stat['product_name']) . "</td>";
                                            echo "<td>" . number_format($stat['draw_count']) . "</td>";
                                            echo "<td>" . number_format($stat['winner_count']) . "</td>";
                                            echo "<td>" . number_format($stat['total_prize_amount']) . "</td>";
                                            echo "<td>" . number_format($avgPerDraw) . "</td>";
                                            echo "<td>" . number_format($avgPerWinner) . "</td>";
                                            echo "</tr>";
                                        }
                                        
                                        if (empty($drawStatistics)) {
                                            echo "<tr><td colspan='6' class='text-center'>통계 데이터가 없습니다.</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 당첨금 지급 현황 -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-warning">
                        <div class="card-header">
                            <h3 class="card-title">당첨금 지급 현황</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php
                                // 당첨금 지급 현황 조회 (예시)
                                try {
                                    // 미지급 당첨금 총액
                                    $stmt = $db->prepare("
                                        SELECT COALESCE(SUM(prize_amount), 0) AS total_pending
                                        FROM winnings
                                        WHERE status = 'pending'
                                    ");
                                    $stmt->execute();
                                    $pendingResult = $stmt->fetch(PDO::FETCH_ASSOC);
                                    $totalPending = $pendingResult['total_pending'] ?? 0;
                                    
                                    // 청구된 당첨금 총액
                                    $stmt = $db->prepare("
                                        SELECT COALESCE(SUM(prize_amount), 0) AS total_claimed
                                        FROM winnings
                                        WHERE status = 'claimed'
                                    ");
                                    $stmt->execute();
                                    $claimedResult = $stmt->fetch(PDO::FETCH_ASSOC);
                                    $totalClaimed = $claimedResult['total_claimed'] ?? 0;
                                    
                                    // 지급된 당첨금 총액
                                    $stmt = $db->prepare("
                                        SELECT COALESCE(SUM(prize_amount), 0) AS total_paid
                                        FROM winnings
                                        WHERE status = 'paid'
                                    ");
                                    $stmt->execute();
                                    $paidResult = $stmt->fetch(PDO::FETCH_ASSOC);
                                    $totalPaid = $paidResult['total_paid'] ?? 0;
                                    
                                    // 금일 지급된 당첨금 총액
                                    $stmt = $db->prepare("
                                        SELECT COALESCE(SUM(prize_amount), 0) AS today_paid
                                        FROM winnings
                                        WHERE status = 'paid' AND DATE(paid_at) = ?
                                    ");
                                    $stmt->execute([$today]);
                                    $todayPaidResult = $stmt->fetch(PDO::FETCH_ASSOC);
                                    $todayPaid = $todayPaidResult['today_paid'] ?? 0;
                                    
                                    echo '<div class="col-md-3 col-sm-6 col-12">';
                                    echo '<div class="info-box bg-warning">';
                                    echo '<span class="info-box-icon"><i class="fas fa-clock"></i></span>';
                                    echo '<div class="info-box-content">';
                                    echo '<span class="info-box-text">미지급 당첨금</span>';
                                    echo '<span class="info-box-number">' . number_format($totalPending) . ' NPR</span>';
                                    echo '</div>';
                                    echo '</div>';
                                    echo '</div>';
                                    
                                    echo '<div class="col-md-3 col-sm-6 col-12">';
                                    echo '<div class="info-box bg-info">';
                                    echo '<span class="info-box-icon"><i class="fas fa-file-invoice"></i></span>';
                                    echo '<div class="info-box-content">';
                                    echo '<span class="info-box-text">청구된 당첨금</span>';
                                    echo '<span class="info-box-number">' . number_format($totalClaimed) . ' NPR</span>';
                                    echo '</div>';
                                    echo '</div>';
                                    echo '</div>';
                                    
                                    echo '<div class="col-md-3 col-sm-6 col-12">';
                                    echo '<div class="info-box bg-success">';
                                    echo '<span class="info-box-icon"><i class="fas fa-check-circle"></i></span>';
                                    echo '<div class="info-box-content">';
                                    echo '<span class="info-box-text">지급 완료 당첨금</span>';
                                    echo '<span class="info-box-number">' . number_format($totalPaid) . ' NPR</span>';
                                    echo '</div>';
                                    echo '</div>';
                                    echo '</div>';
                                    
                                    echo '<div class="col-md-3 col-sm-6 col-12">';
                                    echo '<div class="info-box bg-danger">';
                                    echo '<span class="info-box-icon"><i class="fas fa-calendar-day"></i></span>';
                                    echo '<div class="info-box-content">';
                                    echo '<span class="info-box-text">금일 지급 당첨금</span>';
                                    echo '<span class="info-box-number">' . number_format($todayPaid) . ' NPR</span>';
                                    echo '</div>';
                                    echo '</div>';
                                    echo '</div>';
                                    
                                } catch (PDOException $e) {
                                    // 에러 로깅
                                    error_log("Database error: " . $e->getMessage());
                                    echo '<div class="col-12"><div class="alert alert-danger">당첨금 지급 데이터를 불러오는 중 오류가 발생했습니다.</div></div>';
                                }
                                ?>
                            </div>
                            
                            <div class="progress-group mt-4">
                                <span class="progress-text">당첨금 지급률</span>
                                <?php
                                $totalPrize = $totalPending + $totalClaimed + $totalPaid;
                                $paidPercentage = $totalPrize > 0 ? ($totalPaid / $totalPrize * 100) : 0;
                                ?>
                                <span class="float-right"><b><?php echo number_format($paidPercentage, 1); ?></b>%</span>
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-success" style="width: <?php echo $paidPercentage; ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-center">
                            <a href="/server/dashboard/prize/payment.php" class="btn btn-sm btn-warning">당첨금 지급 관리</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../templates/footer.php'; ?>

<!-- Chart.js 스크립트 -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 등급별 당첨 통계 차트
    var prizeTierData = <?php echo json_encode($prizeSummary); ?>;
    var prizeTierLabels = prizeTierData.map(function(item) {
        return item.prize_tier + '등';
    });
    var prizeTierCounts = prizeTierData.map(function(item) {
        return item.winner_count;
    });
    var prizeTierAmounts = prizeTierData.map(function(item) {
        return item.total_prize_amount;
    });
    
    var prizeTierChartCanvas = document.getElementById('prizeTierChart').getContext('2d');
    var prizeTierChartData = {
        labels: prizeTierLabels,
        datasets: [
            {
                label: '당첨자 수',
                backgroundColor: 'rgba(60,141,188,0.8)',
                borderColor: 'rgba(60,141,188,1)',
                pointRadius: false,
                pointColor: '#3b8bba',
                pointStrokeColor: 'rgba(60,141,188,1)',
                pointHighlightFill: '#fff',
                pointHighlightStroke: 'rgba(60,141,188,1)',
                data: prizeTierCounts,
                type: 'bar',
                yAxisID: 'y-axis-1'
            },
            {
                label: '당첨금액 (백만 NPR)',
                backgroundColor: 'rgba(210, 214, 222, 0.2)',
                borderColor: 'rgba(210, 214, 222, 1)',
                pointRadius: 3,
                pointColor: 'rgba(210, 214, 222, 1)',
                pointStrokeColor: '#c1c7d1',
                pointHighlightFill: '#fff',
                pointHighlightStroke: 'rgba(220,220,220,1)',
                data: prizeTierAmounts.map(function(val) { return val / 1000000; }),
                type: 'line',
                yAxisID: 'y-axis-2'
            }
        ]
    };
    
    var prizeTierChartOptions = {
        maintainAspectRatio: false,
        responsive: true,
        tooltips: {
            mode: 'index',
            intersect: false,
            callbacks: {
                label: function(tooltipItem, data) {
                    var dataset = data.datasets[tooltipItem.datasetIndex];
                    var index = tooltipItem.index;
                    if (tooltipItem.datasetIndex === 0) {
                        return dataset.label + ': ' + new Intl.NumberFormat().format(dataset.data[index]);
                    } else {
                        return dataset.label + ': ' + new Intl.NumberFormat().format(dataset.data[index] * 1000000) + ' NPR';
                    }
                }
            }
        },
        scales: {
            yAxes: [
                {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    id: 'y-axis-1',
                    ticks: {
                        beginAtZero: true,
                        callback: function(value) {
                            return new Intl.NumberFormat().format(value);
                        }
                    }
                },
                {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    id: 'y-axis-2',
                    gridLines: {
                        drawOnChartArea: false
                    },
                    ticks: {
                        beginAtZero: true,
                        callback: function(value) {
                            return new Intl.NumberFormat().format(value) + 'M';
                        }
                    }
                }
            ]
        }
    };
    
    new Chart(prizeTierChartCanvas, {
        type: 'bar',
        data: prizeTierChartData,
        options: prizeTierChartOptions
    });
    
    // 번호 빈도 차트
    var numberFrequencyData = <?php echo json_encode($winningNumberFrequency); ?>;
    var numberLabels = Object.keys(numberFrequencyData);
    var frequencyValues = Object.values(numberFrequencyData);
    
    var numberFrequencyChartCanvas = document.getElementById('numberFrequencyChart').getContext('2d');
    var numberFrequencyChartData = {
        labels: numberLabels,
        datasets: [
            {
                label: '출현 빈도',
                backgroundColor: 'rgba(255, 99, 132, 0.8)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1,
                data: frequencyValues
            }
        ]
    };
    
    var numberFrequencyChartOptions = {
        maintainAspectRatio: false,
        responsive: true,
        legend: {
            display: false
        },
        scales: {
            yAxes: [{
                ticks: {
                    beginAtZero: true,
                    precision: 0
                }
            }]
        }
    };
    
    new Chart(numberFrequencyChartCanvas, {
        type: 'bar',
        data: numberFrequencyChartData,
        options: numberFrequencyChartOptions
    });
});
</script>
