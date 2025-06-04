<!-- 추첨 관리 대시보드 콘텐츠 -->
<?php
// 추첨 정보 (Mock 데이터 사용)
$drawInfo = [
    'scheduled_draws' => 3,
    'completed_draws' => 124,
    'total_winners' => 85642,
    'next_draw_date' => '2024-05-22'
];

// 최근 추첨 결과 (Mock 데이터 사용)
$recentDraws = [
    [
        'id' => 125, 
        'product' => 'KHUSHI Weekly', 
        'date' => '2024-05-18', 
        'numbers' => '08, 15, 22, 30, 37, 42', 
        'winners' => 6820, 
        'prize_pool' => 10000000
    ],
    [
        'id' => 124, 
        'product' => 'KHUSHI Daily', 
        'date' => '2024-05-17', 
        'numbers' => '03, 11, 19, 25, 32, 40', 
        'winners' => 5240, 
        'prize_pool' => 5000000
    ],
    [
        'id' => 123, 
        'product' => 'KHUSHI Weekly', 
        'date' => '2024-05-11', 
        'numbers' => '05, 13, 20, 28, 35, 41', 
        'winners' => 7152, 
        'prize_pool' => 10000000
    ],
    [
        'id' => 122, 
        'product' => 'KHUSHI Bumper', 
        'date' => '2024-05-05', 
        'numbers' => '07, 12, 24, 31, 39, 45', 
        'winners' => 12450, 
        'prize_pool' => 25000000
    ]
];
?>
<div class="row">
    <!-- 추첨 통계 요약 -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?php echo number_format($drawInfo['scheduled_draws']); ?></h3>
                <p>예정된 추첨</p>
            </div>
            <div class="icon">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <a href="<?php echo SERVER_URL; ?>/dashboard/draw/plan.php" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?php echo number_format($drawInfo['completed_draws']); ?></h3>
                <p>완료된 추첨</p>
            </div>
            <div class="icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <a href="<?php echo SERVER_URL; ?>/dashboard/draw/history.php" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?php echo number_format($drawInfo['total_winners']); ?></h3>
                <p>총 당첨자 수</p>
            </div>
            <div class="icon">
                <i class="fas fa-trophy"></i>
            </div>
            <a href="<?php echo SERVER_URL; ?>/dashboard/prize/statistics.php" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3><?php echo $drawInfo['next_draw_date']; ?></h3>
                <p>다음 추첨일</p>
            </div>
            <div class="icon">
                <i class="fas fa-clock"></i>
            </div>
            <a href="<?php echo SERVER_URL; ?>/dashboard/draw/execution.php?draw_id=126" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<div class="row">
    <!-- 최근 추첨 결과 표 -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">최근 추첨 결과</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>회차</th>
                            <th>복권 종류</th>
                            <th>추첨일</th>
                            <th>당첨 번호</th>
                            <th>당첨자 수</th>
                            <th>당첨금 풀</th>
                            <th>액션</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentDraws as $draw): ?>
                        <tr>
                            <td><?php echo $draw['id']; ?></td>
                            <td><?php echo $draw['product']; ?></td>
                            <td><?php echo $draw['date']; ?></td>
                            <td>
                                <span class="badge bg-primary"><?php echo $draw['numbers']; ?></span>
                            </td>
                            <td><?php echo number_format($draw['winners']); ?></td>
                            <td>₹ <?php echo number_format($draw['prize_pool']); ?></td>
                            <td>
                                <a href="<?php echo SERVER_URL; ?>/dashboard/draw/results.php?draw_id=<?php echo $draw['id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <a href="<?php echo SERVER_URL; ?>/dashboard/draw/history.php" class="btn btn-sm btn-primary float-right">
                    모든 추첨 결과 보기
                </a>
            </div>
        </div>
    </div>
    
    <!-- 다가오는 추첨 일정 -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">예정된 추첨</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>KHUSHI Weekly</strong><br>
                                <small>회차: #126</small>
                            </div>
                            <div class="text-right">
                                <span class="badge bg-primary">2024-05-22</span><br>
                                <small>18:00 IST</small>
                            </div>
                        </div>
                    </li>
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>KHUSHI Daily</strong><br>
                                <small>회차: #127</small>
                            </div>
                            <div class="text-right">
                                <span class="badge bg-primary">2024-05-23</span><br>
                                <small>18:00 IST</small>
                            </div>
                        </div>
                    </li>
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>KHUSHI Weekly</strong><br>
                                <small>회차: #128</small>
                            </div>
                            <div class="text-right">
                                <span class="badge bg-primary">2024-05-29</span><br>
                                <small>18:00 IST</small>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="card-footer">
                <a href="<?php echo SERVER_URL; ?>/dashboard/draw/plan.php" class="btn btn-sm btn-primary float-right">
                    모든 추첨 일정 보기
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- 당첨 통계 그래프 -->
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">당첨 통계 추이</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="chart">
                    <canvas id="drawStatsChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// 당첨 통계 추이 차트
$(function() {
    var drawStatsChartCanvas = document.getElementById('drawStatsChart').getContext('2d');
    
    var drawStatsChartData = {
        labels: ['#119', '#120', '#121', '#122', '#123', '#124', '#125'],
        datasets: [
            {
                label: '당첨자 수',
                backgroundColor: 'rgba(60,141,188,0.9)',
                borderColor: 'rgba(60,141,188,0.8)',
                pointRadius: 3,
                pointColor: '#3b8bba',
                pointStrokeColor: 'rgba(60,141,188,1)',
                pointHighlightFill: '#fff',
                pointHighlightStroke: 'rgba(60,141,188,1)',
                data: [5150, 6230, 6410, 12450, 7152, 5240, 6820]
            },
            {
                label: '당첨금 풀(억 루피)',
                backgroundColor: 'rgba(210, 214, 222, 0.9)',
                borderColor: 'rgba(210, 214, 222, 0.8)',
                pointRadius: 3,
                pointColor: 'rgba(210, 214, 222, 1)',
                pointStrokeColor: '#c1c7d1',
                pointHighlightFill: '#fff',
                pointHighlightStroke: 'rgba(220,220,220,1)',
                data: [5, 5, 5, 25, 10, 5, 10]
            }
        ]
    };

    var drawStatsChartOptions = {
        maintainAspectRatio: false,
        responsive: true,
        legend: {
            display: true
        },
        scales: {
            xAxes: [{
                gridLines: {
                    display: false
                }
            }],
            yAxes: [{
                gridLines: {
                    display: false
                }
            }]
        }
    };

    var drawStatsChart = new Chart(drawStatsChartCanvas, {
        type: 'bar',
        data: drawStatsChartData,
        options: drawStatsChartOptions
    });
});
</script>