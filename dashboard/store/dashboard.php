<!-- 판매점 관리 대시보드 콘텐츠 -->
<div class="row">
    <div class="col-md-12">
        <!-- 판매점 현황 카드 -->
        <div class="row">
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box">
                    <span class="info-box-icon bg-info elevation-1"><i class="fas fa-store"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">총 판매점</span>
                        <span class="info-box-number">1,250개</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box">
                    <span class="info-box-icon bg-success elevation-1"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">활성 판매점</span>
                        <span class="info-box-number">1,082개</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box">
                    <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-exclamation-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">계약 만료 예정</span>
                        <span class="info-box-number">68개</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box">
                    <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-desktop"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">등록 단말기</span>
                        <span class="info-box-number">1,450대</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <!-- 판매점 등록 추이 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">판매점 등록 추이</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="storeRegistrationChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <!-- 장비 상태 분포 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">장비 상태 분포</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="equipmentStatusChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- 판매점 성과 현황 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">판매점 성과 현황</h3>
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
                                <th>판매점 ID</th>
                                <th>판매점명</th>
                                <th>지역</th>
                                <th>이번 달 판매량</th>
                                <th>전월 대비</th>
                                <th>등급</th>
                                <th>계약 상태</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>ST-2024-0345</td>
                                <td>서울 명동점</td>
                                <td>서울</td>
                                <td>8,456장</td>
                                <td><span class="text-success">+12.4%</span></td>
                                <td><span class="badge bg-primary">프리미엄</span></td>
                                <td><span class="badge bg-success">활성</span></td>
                            </tr>
                            <tr>
                                <td>ST-2024-0287</td>
                                <td>부산 해운대점</td>
                                <td>부산</td>
                                <td>7,234장</td>
                                <td><span class="text-success">+8.2%</span></td>
                                <td><span class="badge bg-primary">프리미엄</span></td>
                                <td><span class="badge bg-success">활성</span></td>
                            </tr>
                            <tr>
                                <td>ST-2024-0156</td>
                                <td>강남 역삼점</td>
                                <td>서울</td>
                                <td>6,912장</td>
                                <td><span class="text-danger">-2.5%</span></td>
                                <td><span class="badge bg-info">스탠다드</span></td>
                                <td><span class="badge bg-warning">만료 임박</span></td>
                            </tr>
                            <tr>
                                <td>ST-2024-0422</td>
                                <td>인천 송도점</td>
                                <td>인천</td>
                                <td>6,245장</td>
                                <td><span class="text-success">+15.7%</span></td>
                                <td><span class="badge bg-info">스탠다드</span></td>
                                <td><span class="badge bg-success">활성</span></td>
                            </tr>
                            <tr>
                                <td>ST-2024-0189</td>
                                <td>대구 동성로점</td>
                                <td>대구</td>
                                <td>5,876장</td>
                                <td><span class="text-success">+5.3%</span></td>
                                <td><span class="badge bg-info">스탠다드</span></td>
                                <td><span class="badge bg-success">활성</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <a href="<?php echo SERVER_URL; ?>/dashboard/store/store-performance.php" class="btn btn-sm btn-primary">
                    전체 판매점 성과 보기
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <!-- 지역별 판매점 분포 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">지역별 판매점 분포</h3>
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
                            <th>판매점 수</th>
                            <th>비율</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>서울</td>
                            <td>380</td>
                            <td>
                                <div class="progress progress-xs">
                                    <div class="progress-bar bg-primary" style="width: 30.4%"></div>
                                </div>
                                <span class="badge bg-primary">30.4%</span>
                            </td>
                        </tr>
                        <tr>
                            <td>경기</td>
                            <td>298</td>
                            <td>
                                <div class="progress progress-xs">
                                    <div class="progress-bar bg-success" style="width: 23.8%"></div>
                                </div>
                                <span class="badge bg-success">23.8%</span>
                            </td>
                        </tr>
                        <tr>
                            <td>인천</td>
                            <td>115</td>
                            <td>
                                <div class="progress progress-xs">
                                    <div class="progress-bar bg-warning" style="width: 9.2%"></div>
                                </div>
                                <span class="badge bg-warning">9.2%</span>
                            </td>
                        </tr>
                        <tr>
                            <td>부산</td>
                            <td>98</td>
                            <td>
                                <div class="progress progress-xs">
                                    <div class="progress-bar bg-info" style="width: 7.8%"></div>
                                </div>
                                <span class="badge bg-info">7.8%</span>
                            </td>
                        </tr>
                        <tr>
                            <td>기타</td>
                            <td>359</td>
                            <td>
                                <div class="progress progress-xs">
                                    <div class="progress-bar bg-secondary" style="width: 28.8%"></div>
                                </div>
                                <span class="badge bg-secondary">28.8%</span>
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
                        <a href="<?php echo SERVER_URL; ?>/dashboard/store/store-add.php" class="btn btn-block btn-primary mb-3">
                            <i class="fas fa-store-alt mr-2"></i> 판매점 추가
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="<?php echo SERVER_URL; ?>/dashboard/store/store-list.php" class="btn btn-block btn-success mb-3">
                            <i class="fas fa-list mr-2"></i> 판매점 목록
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="<?php echo SERVER_URL; ?>/dashboard/store/equipment-list.php" class="btn btn-block btn-info mb-3">
                            <i class="fas fa-desktop mr-2"></i> 장비 관리
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="<?php echo SERVER_URL; ?>/dashboard/store/contract-list.php" class="btn btn-block btn-warning mb-3">
                            <i class="fas fa-file-contract mr-2"></i> 계약 관리
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
