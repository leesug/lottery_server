<!-- 고객 관리 대시보드 콘텐츠 -->
<div class="row">
    <div class="col-md-12">
        <!-- 고객 현황 카드 -->
        <div class="row">
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box">
                    <span class="info-box-icon bg-info elevation-1"><i class="fas fa-users"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">총 고객 수</span>
                        <span class="info-box-number">8,742명</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box">
                    <span class="info-box-icon bg-success elevation-1"><i class="fas fa-user-check"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">활성 고객</span>
                        <span class="info-box-number">6,531명</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box">
                    <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-user-plus"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">최근 30일 신규</span>
                        <span class="info-box-number">412명</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box">
                    <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-crown"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">VIP 고객</span>
                        <span class="info-box-number">658명</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <!-- 고객 등급별 분포 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">고객 등급별 분포</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="customerTierChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <!-- 고객 활동 현황 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">고객 활동 현황</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="customerActivityChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- 최근 고객 활동 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">최근 고객 활동</h3>
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
                                <th>고객명</th>
                                <th>활동</th>
                                <th>시간</th>
                                <th>상품</th>
                                <th>금액</th>
                                <th>상태</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>김철수</td>
                                <td>복권 구매</td>
                                <td>2024-05-18 09:45</td>
                                <td>KHUSHI Weekly</td>
                                <td>₹ 200</td>
                                <td><span class="badge bg-success">완료</span></td>
                            </tr>
                            <tr>
                                <td>이영희</td>
                                <td>복권 구매</td>
                                <td>2024-05-18 09:30</td>
                                <td>KHUSHI Bumper</td>
                                <td>₹ 500</td>
                                <td><span class="badge bg-success">완료</span></td>
                            </tr>
                            <tr>
                                <td>박지성</td>
                                <td>당첨금 신청</td>
                                <td>2024-05-18 09:15</td>
                                <td>KHUSHI Daily</td>
                                <td>₹ 1,000</td>
                                <td><span class="badge bg-warning">처리중</span></td>
                            </tr>
                            <tr>
                                <td>최민수</td>
                                <td>계정 등록</td>
                                <td>2024-05-18 09:00</td>
                                <td>-</td>
                                <td>-</td>
                                <td><span class="badge bg-info">신규</span></td>
                            </tr>
                            <tr>
                                <td>정성훈</td>
                                <td>개인정보 수정</td>
                                <td>2024-05-18 08:45</td>
                                <td>-</td>
                                <td>-</td>
                                <td><span class="badge bg-success">완료</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <a href="<?php echo SERVER_URL; ?>/dashboard/customer/customer-activity-report.php" class="btn btn-sm btn-primary">
                    전체 활동 보기
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <!-- 지역별 고객 분포 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">지역별 고객 분포</h3>
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
                            <th>지역</th>
                            <th>고객 수</th>
                            <th>비율</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>서울</td>
                            <td>2,340</td>
                            <td>
                                <div class="progress progress-xs">
                                    <div class="progress-bar bg-primary" style="width: 26.8%"></div>
                                </div>
                                <span class="badge bg-primary">26.8%</span>
                            </td>
                        </tr>
                        <tr>
                            <td>경기</td>
                            <td>1,984</td>
                            <td>
                                <div class="progress progress-xs">
                                    <div class="progress-bar bg-success" style="width: 22.7%"></div>
                                </div>
                                <span class="badge bg-success">22.7%</span>
                            </td>
                        </tr>
                        <tr>
                            <td>인천</td>
                            <td>823</td>
                            <td>
                                <div class="progress progress-xs">
                                    <div class="progress-bar bg-warning" style="width: 9.4%"></div>
                                </div>
                                <span class="badge bg-warning">9.4%</span>
                            </td>
                        </tr>
                        <tr>
                            <td>부산</td>
                            <td>712</td>
                            <td>
                                <div class="progress progress-xs">
                                    <div class="progress-bar bg-info" style="width: 8.1%"></div>
                                </div>
                                <span class="badge bg-info">8.1%</span>
                            </td>
                        </tr>
                        <tr>
                            <td>기타</td>
                            <td>2,883</td>
                            <td>
                                <div class="progress progress-xs">
                                    <div class="progress-bar bg-secondary" style="width: 33.0%"></div>
                                </div>
                                <span class="badge bg-secondary">33.0%</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
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
                        <a href="<?php echo SERVER_URL; ?>/dashboard/customer/customer-add.php" class="btn btn-block btn-primary mb-3">
                            <i class="fas fa-user-plus mr-2"></i> 고객 추가
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="<?php echo SERVER_URL; ?>/dashboard/customer/customer-list.php" class="btn btn-block btn-success mb-3">
                            <i class="fas fa-users mr-2"></i> 고객 목록
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="<?php echo SERVER_URL; ?>/dashboard/customer/verification.php" class="btn btn-block btn-info mb-3">
                            <i class="fas fa-check-circle mr-2"></i> 고객 인증
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="<?php echo SERVER_URL; ?>/dashboard/customer/customer-activity-report.php" class="btn btn-block btn-warning mb-3">
                            <i class="fas fa-chart-line mr-2"></i> 활동 보고서
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
