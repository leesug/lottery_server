<!-- 재무 관리 대시보드 콘텐츠 -->
<div class="row">
    <div class="col-md-12">
        <!-- 재무 현황 카드 -->
        <div class="row">
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box">
                    <span class="info-box-icon bg-info elevation-1"><i class="fas fa-dollar-sign"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">총 매출 (금월)</span>
                        <span class="info-box-number">₹ 85,245,300</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box">
                    <span class="info-box-icon bg-success elevation-1"><i class="fas fa-chart-line"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">순이익 (금월)</span>
                        <span class="info-box-number">₹ 24,580,000</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box">
                    <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-credit-card"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">지출 (금월)</span>
                        <span class="info-box-number">₹ 12,450,800</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box">
                    <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-file-invoice-dollar"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">미결제 청구서</span>
                        <span class="info-box-number">₹ 3,845,200</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <!-- 매출 추이 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">월별 매출 추이</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="revenueTrendChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <!-- 예산 대비 실적 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">예산 대비 실적</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="budgetVsActualChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- 최근 금융 거래 내역 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">최근 금융 거래 내역</h3>
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
                                <th>거래 ID</th>
                                <th>날짜</th>
                                <th>종류</th>
                                <th>내역</th>
                                <th>금액</th>
                                <th>상태</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>TRX-24051801</td>
                                <td>2024-05-18</td>
                                <td><span class="badge bg-success">수입</span></td>
                                <td>판매 수익 - 명동점</td>
                                <td>₹ 1,245,800</td>
                                <td><span class="badge bg-success">완료</span></td>
                            </tr>
                            <tr>
                                <td>TRX-24051752</td>
                                <td>2024-05-17</td>
                                <td><span class="badge bg-danger">지출</span></td>
                                <td>시스템 유지 보수비</td>
                                <td>₹ 450,000</td>
                                <td><span class="badge bg-success">완료</span></td>
                            </tr>
                            <tr>
                                <td>TRX-24051684</td>
                                <td>2024-05-16</td>
                                <td><span class="badge bg-success">수입</span></td>
                                <td>판매 수익 - 강남점</td>
                                <td>₹ 987,500</td>
                                <td><span class="badge bg-success">완료</span></td>
                            </tr>
                            <tr>
                                <td>TRX-24051642</td>
                                <td>2024-05-16</td>
                                <td><span class="badge bg-warning">이체</span></td>
                                <td>기금 이체 - 당첨금 계정</td>
                                <td>₹ 5,000,000</td>
                                <td><span class="badge bg-success">완료</span></td>
                            </tr>
                            <tr>
                                <td>TRX-24051521</td>
                                <td>2024-05-15</td>
                                <td><span class="badge bg-danger">지출</span></td>
                                <td>판매점 수수료 지급</td>
                                <td>₹ 1,845,200</td>
                                <td><span class="badge bg-warning">처리중</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <a href="<?php echo SERVER_URL; ?>/dashboard/finance/transactions.php" class="btn btn-sm btn-primary">
                    전체 거래 내역 보기
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <!-- 수입/지출 분포 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">수입/지출 분포</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="incomeExpenseChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
            </div>
        </div>
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
                        <a href="<?php echo SERVER_URL; ?>/dashboard/finance/transaction-add.php" class="btn btn-block btn-primary mb-3">
                            <i class="fas fa-plus-circle mr-2"></i> 거래 추가
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="<?php echo SERVER_URL; ?>/dashboard/finance/reports.php" class="btn btn-block btn-success mb-3">
                            <i class="fas fa-chart-bar mr-2"></i> 재무 보고서
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="<?php echo SERVER_URL; ?>/dashboard/finance/budget.php" class="btn btn-block btn-info mb-3">
                            <i class="fas fa-money-bill-alt mr-2"></i> 예산 관리
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="<?php echo SERVER_URL; ?>/dashboard/finance/settlements.php" class="btn btn-block btn-warning mb-3">
                            <i class="fas fa-file-invoice mr-2"></i> 정산 관리
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
