<!-- 당첨금 관리 대시보드 콘텐츠 -->
<?php
// 당첨금 정보 (Mock 데이터 사용)
$prizeInfo = [
    'total_prize_pool' => 45000000,
    'claimed_amount' => 12850000,
    'unclaimed_amount' => 7150000,
    'carryover_amount' => 25000000
];

// 최근 당첨금 지급 내역 (Mock 데이터 사용)
$recentPrizes = [
    [
        'id' => 'P202405180001', 
        'draw_id' => 125, 
        'product' => 'KHUSHI Weekly', 
        'ticket' => 'TK24W17505', 
        'amount' => 5000000, 
        'rank' => 1, 
        'date' => '2024-05-18 14:30:25', 
        'status' => 'paid'
    ],
    [
        'id' => 'P202405180002', 
        'draw_id' => 125, 
        'product' => 'KHUSHI Weekly', 
        'ticket' => 'TK24W18720', 
        'amount' => 2000000, 
        'rank' => 2, 
        'date' => '2024-05-18 15:10:45', 
        'status' => 'paid'
    ],
    [
        'id' => 'P202405180003', 
        'draw_id' => 125, 
        'product' => 'KHUSHI Weekly', 
        'ticket' => 'TK24W20145', 
        'amount' => 1000000, 
        'rank' => 3, 
        'date' => '2024-05-18 15:45:12', 
        'status' => 'processing'
    ],
    [
        'id' => 'P202405170001', 
        'draw_id' => 124, 
        'product' => 'KHUSHI Daily', 
        'ticket' => 'TK24D08925', 
        'amount' => 2500000, 
        'rank' => 1, 
        'date' => '2024-05-17 14:15:30', 
        'status' => 'paid'
    ]
];
?>
<div class="row">
    <!-- 당첨금 통계 요약 -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>₹ <?php echo number_format($prizeInfo['total_prize_pool']); ?></h3>
                <p>총 당첨금 풀</p>
            </div>
            <div class="icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <a href="<?php echo SERVER_URL; ?>/dashboard/prize/settings.php" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>₹ <?php echo number_format($prizeInfo['claimed_amount']); ?></h3>
                <p>지급된 당첨금</p>
            </div>
            <div class="icon">
                <i class="fas fa-hand-holding-usd"></i>
            </div>
            <a href="<?php echo SERVER_URL; ?>/dashboard/prize/payment.php" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>₹ <?php echo number_format($prizeInfo['unclaimed_amount']); ?></h3>
                <p>미청구 당첨금</p>
            </div>
            <div class="icon">
                <i class="fas fa-clock"></i>
            </div>
            <a href="<?php echo SERVER_URL; ?>/dashboard/prize/statistics.php?filter=unclaimed" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>₹ <?php echo number_format($prizeInfo['carryover_amount']); ?></h3>
                <p>이월금</p>
            </div>
            <div class="icon">
                <i class="fas fa-exchange-alt"></i>
            </div>
            <a href="<?php echo SERVER_URL; ?>/dashboard/prize/carryover.php" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<div class="row">
    <!-- 당첨금 분배 차트 -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">당첨금 분배 현황</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="chart">
                    <canvas id="prizeDistributionChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 당첨금 지급 상태 -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">당첨금 지급 상태</h3>
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
                            <th>상태</th>
                            <th>금액</th>
                            <th>비율</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>지급 완료</td>
                            <td>₹ <?php echo number_format($prizeInfo['claimed_amount']); ?></td>
                            <td>
                                <div class="progress progress-xs">
                                    <div class="progress-bar bg-success" style="width: <?php echo round($prizeInfo['claimed_amount'] / ($prizeInfo['claimed_amount'] + $prizeInfo['unclaimed_amount']) * 100); ?>%"></div>
                                </div>
                                <span class="badge bg-success"><?php echo round($prizeInfo['claimed_amount'] / ($prizeInfo['claimed_amount'] + $prizeInfo['unclaimed_amount']) * 100); ?>%</span>
                            </td>
                        </tr>
                        <tr>
                            <td>미청구</td>
                            <td>₹ <?php echo number_format($prizeInfo['unclaimed_amount']); ?></td>
                            <td>
                                <div class="progress progress-xs">
                                    <div class="progress-bar bg-warning" style="width: <?php echo round($prizeInfo['unclaimed_amount'] / ($prizeInfo['claimed_amount'] + $prizeInfo['unclaimed_amount']) * 100); ?>%"></div>
                                </div>
                                <span class="badge bg-warning"><?php echo round($prizeInfo['unclaimed_amount'] / ($prizeInfo['claimed_amount'] + $prizeInfo['unclaimed_amount']) * 100); ?>%</span>
                            </td>
                        </tr>
                        <tr>
                            <td>이월금</td>
                            <td>₹ <?php echo number_format($prizeInfo['carryover_amount']); ?></td>
                            <td>
                                <div class="progress progress-xs">
                                    <div class="progress-bar bg-danger" style="width: <?php echo round($prizeInfo['carryover_amount'] / $prizeInfo['total_prize_pool'] * 100); ?>%"></div>
                                </div>
                                <span class="badge bg-danger"><?php echo round($prizeInfo['carryover_amount'] / $prizeInfo['total_prize_pool'] * 100); ?>%</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- 최근 당첨금 지급 내역 -->
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">최근 당첨금 지급 내역</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>추첨 ID</th>
                                <th>복권 종류</th>
                                <th>티켓 번호</th>
                                <th>당첨금액</th>
                                <th>당첨 등수</th>
                                <th>지급 일시</th>
                                <th>상태</th>
                                <th>액션</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentPrizes as $prize): ?>
                            <tr>
                                <td><?php echo $prize['id']; ?></td>
                                <td><?php echo $prize['draw_id']; ?></td>
                                <td><?php echo $prize['product']; ?></td>
                                <td><?php echo $prize['ticket']; ?></td>
                                <td>₹ <?php echo number_format($prize['amount']); ?></td>
                                <td><?php echo $prize['rank']; ?>등</td>
                                <td><?php echo $prize['date']; ?></td>
                                <td>
                                    <?php if ($prize['status'] == 'paid'): ?>
                                    <span class="badge bg-success">지급 완료</span>
                                    <?php elseif ($prize['status'] == 'processing'): ?>
                                    <span class="badge bg-warning">처리 중</span>
                                    <?php else: ?>
                                    <span class="badge bg-danger">지급 실패</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <a href="<?php echo SERVER_URL; ?>/dashboard/prize/payment.php" class="btn btn-sm btn-primary float-right">
                    모든 당첨금 지급 내역 보기
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// 당첨금 분배 차트
$(function() {
    var prizeDistributionChartCanvas = document.getElementById('prizeDistributionChart').getContext('2d');
    
    var prizeDistributionData = {
        labels: ['1등', '2등', '3등', '4등', '5등', '이월금'],
        datasets: [
            {
                label: '금액(천만 루피)',
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)',
                    'rgba(255, 159, 64, 0.8)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)'
                ],
                borderWidth: 1,
                data: [1000, 500, 250, 125, 75, 2500]
            }
        ]
    };

    var prizeDistributionOptions = {
        maintainAspectRatio: false,
        responsive: true,
        legend: {
            position: 'right',
            display: true
        }
    };

    var prizeDistributionChart = new Chart(prizeDistributionChartCanvas, {
        type: 'pie',
        data: prizeDistributionData,
        options: prizeDistributionOptions
    });
});
</script>