<!-- 복권 관리 대시보드 콘텐츠 -->
<?php
// 복권 상품 정보 (Mock 데이터 사용)
$lotteryProducts = [
    ['name' => 'KHUSHI Bumper', 'status' => 'active', 'prize_pool' => 25000000, 'sales' => 145320],
    ['name' => 'KHUSHI Weekly', 'status' => 'active', 'prize_pool' => 10000000, 'sales' => 89745],
    ['name' => 'KHUSHI Daily', 'status' => 'active', 'prize_pool' => 5000000, 'sales' => 125680],
    ['name' => 'KHUSHI Special', 'status' => 'pending', 'prize_pool' => 30000000, 'sales' => 0]
];

// 최근 배치 정보 (Mock 데이터 사용)
$recentBatches = [
    ['id' => 'B2024051801', 'product' => 'KHUSHI Bumper', 'date' => '2024-05-18', 'quantity' => 50000, 'allocated' => 15000, 'status' => 'active'],
    ['id' => 'B2024051701', 'product' => 'KHUSHI Weekly', 'date' => '2024-05-17', 'quantity' => 30000, 'allocated' => 28500, 'status' => 'active'],
    ['id' => 'B2024051601', 'product' => 'KHUSHI Daily', 'date' => '2024-05-16', 'quantity' => 20000, 'allocated' => 20000, 'status' => 'closed'],
    ['id' => 'B2024051501', 'product' => 'KHUSHI Bumper', 'date' => '2024-05-15', 'quantity' => 50000, 'allocated' => 50000, 'status' => 'closed']
];
?>
<div class="row">
    <div class="col-md-6">
        <!-- 복권 상품 현황 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">복권 상품 현황</h3>
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
                            <th>상품명</th>
                            <th>상태</th>
                            <th>당첨금 풀</th>
                            <th>판매량</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>KHUSHI Bumper</td>
                            <td><span class="badge bg-success">활성</span></td>
                            <td>₹ 25,000,000</td>
                            <td>145,320</td>
                        </tr>
                        <tr>
                            <td>KHUSHI Weekly</td>
                            <td><span class="badge bg-success">활성</span></td>
                            <td>₹ 10,000,000</td>
                            <td>89,745</td>
                        </tr>
                        <tr>
                            <td>KHUSHI Daily</td>
                            <td><span class="badge bg-success">활성</span></td>
                            <td>₹ 5,000,000</td>
                            <td>125,680</td>
                        </tr>
                        <tr>
                            <td>KHUSHI Special</td>
                            <td><span class="badge bg-warning">준비 중</span></td>
                            <td>₹ 30,000,000</td>
                            <td>0</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <!-- 복권 발행 현황 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">복권 발행 현황</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="lotteryIssuanceChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- 최근 복권 배치 현황 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">최근 복권 배치 현황</h3>
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
                                <th>배치 ID</th>
                                <th>상품명</th>
                                <th>발행일</th>
                                <th>수량</th>
                                <th>할당됨</th>
                                <th>상태</th>
                                <th>액션</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>B2024051801</td>
                                <td>KHUSHI Bumper</td>
                                <td>2024-05-18</td>
                                <td>50,000</td>
                                <td>15,000</td>
                                <td><span class="badge bg-success">활성</span></td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td>B2024051701</td>
                                <td>KHUSHI Weekly</td>
                                <td>2024-05-17</td>
                                <td>30,000</td>
                                <td>28,500</td>
                                <td><span class="badge bg-success">활성</span></td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td>B2024051601</td>
                                <td>KHUSHI Daily</td>
                                <td>2024-05-16</td>
                                <td>20,000</td>
                                <td>20,000</td>
                                <td><span class="badge bg-warning">마감됨</span></td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td>B2024051501</td>
                                <td>KHUSHI Bumper</td>
                                <td>2024-05-15</td>
                                <td>50,000</td>
                                <td>50,000</td>
                                <td><span class="badge bg-warning">마감됨</span></td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <a href="<?php echo SERVER_URL; ?>/dashboard/lottery/batch-management.php" class="btn btn-sm btn-primary">
                    모든 배치 보기
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <!-- 빠른 액션 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">빠른 액션</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <a href="<?php echo SERVER_URL; ?>/dashboard/lottery/products.php" class="btn btn-block btn-primary mb-3">
                            <i class="fas fa-ticket-alt mr-2"></i> 복권 상품 관리
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="<?php echo SERVER_URL; ?>/dashboard/lottery/issues.php" class="btn btn-block btn-success mb-3">
                            <i class="fas fa-file-invoice mr-2"></i> 복권 발행
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="<?php echo SERVER_URL; ?>/dashboard/lottery/numbering.php" class="btn btn-block btn-info mb-3">
                            <i class="fas fa-hashtag mr-2"></i> 넘버링 관리
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="<?php echo SERVER_URL; ?>/dashboard/lottery/status.php" class="btn btn-block btn-warning mb-3">
                            <i class="fas fa-chart-line mr-2"></i> 상태 확인
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="<?php echo SERVER_URL; ?>/dashboard/lottery/batch-management.php" class="btn btn-block btn-secondary mb-3">
                            <i class="fas fa-cubes mr-2"></i> 배치 관리
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="<?php echo SERVER_URL; ?>/dashboard/lottery/printing-settings.php" class="btn btn-block btn-danger mb-3">
                            <i class="fas fa-print mr-2"></i> 인쇄 설정
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <!-- 알림 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">알림</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <i class="fas fa-exclamation-circle text-warning mr-2"></i>
                        KHUSHI Bumper 배치 #B2024051801 할당 필요
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-check-circle text-success mr-2"></i>
                        KHUSHI Weekly 배치 #B2024051701 종료 임박 (95% 할당됨)
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-info-circle text-info mr-2"></i>
                        KHUSHI Special 발행 승인 대기 중
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>