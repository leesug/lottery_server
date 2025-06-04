<!-- 마케팅 관리 대시보드 콘텐츠 -->
<div class="row">
    <div class="col-md-12">
        <!-- 빠른 액션 버튼 -->
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="btn-group">
                    <a href="campaigns.php" class="btn btn-primary"><i class="fas fa-bullhorn"></i> 캠페인 관리</a>
                    <a href="email.php" class="btn btn-success"><i class="fas fa-envelope"></i> 이메일 마케팅</a>
                    <a href="sms.php" class="btn btn-info"><i class="fas fa-sms"></i> SMS 마케팅</a>
                    <a href="advertisements.php" class="btn btn-warning"><i class="fas fa-ad"></i> 광고 관리</a>
                    <a href="promotions.php" class="btn btn-secondary"><i class="fas fa-percent"></i> 프로모션 관리</a>
                </div>
            </div>
        </div>

        <!-- 마케팅 통계 요약 카드 -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>8</h3>
                        <p>진행 중인 캠페인</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <a href="campaigns.php?filter=active" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>12</h3>
                        <p>진행 중인 프로모션</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-percent"></i>
                    </div>
                    <a href="promotions.php?filter=active" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>5</h3>
                        <p>예정된 캠페인</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <a href="campaigns.php?filter=scheduled" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>237,550,000</h3>
                        <p>남은 예산</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-coins"></i>
                    </div>
                    <a href="budget-allocation-manage.php" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- 월별 마케팅 성과 추이 차트 -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">월별 마케팅 성과 추이</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="chart">
                    <canvas id="marketingPerformanceChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- 예산 사용 현황 -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">예산 사용 현황</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="progress-group">
                    <span class="progress-text">총 예산 사용률</span>
                    <span class="float-right">72%</span>
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-primary" style="width: 72%"></div>
                    </div>
                </div>
                <div class="progress-group mt-3">
                    <span class="progress-text">이메일 마케팅</span>
                    <span class="float-right">82%</span>
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-success" style="width: 82%"></div>
                    </div>
                </div>
                <div class="progress-group mt-3">
                    <span class="progress-text">온라인 광고</span>
                    <span class="float-right">84%</span>
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-warning" style="width: 84%"></div>
                    </div>
                </div>
                <div class="progress-group mt-3">
                    <span class="progress-text">소셜 미디어</span>
                    <span class="float-right">80%</span>
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-info" style="width: 80%"></div>
                    </div>
                </div>
                <div class="progress-group mt-3">
                    <span class="progress-text">오프라인 광고</span>
                    <span class="float-right">68%</span>
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-danger" style="width: 68%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- 캠페인 성과 현황 -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">캠페인 성과 현황</h3>
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
                                <th>캠페인명</th>
                                <th>시작일</th>
                                <th>종료일</th>
                                <th>예산</th>
                                <th>사용액</th>
                                <th>전환율</th>
                                <th>ROI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>여름 특별 이벤트</td>
                                <td>2025-05-01</td>
                                <td>2025-06-30</td>
                                <td>₹ 120,000,000</td>
                                <td>₹ 45,250,000</td>
                                <td>18.8%</td>
                                <td><span class="text-success">18.5%</span></td>
                            </tr>
                            <tr>
                                <td>명절 특별 프로모션</td>
                                <td>2025-04-15</td>
                                <td>2025-05-15</td>
                                <td>₹ 180,000,000</td>
                                <td>₹ 158,750,000</td>
                                <td>19.1%</td>
                                <td><span class="text-success">22.1%</span></td>
                            </tr>
                            <tr>
                                <td>신규 회원 유치</td>
                                <td>2025-03-01</td>
                                <td>2025-05-31</td>
                                <td>₹ 250,000,000</td>
                                <td>₹ 178,500,000</td>
                                <td>17.0%</td>
                                <td><span class="text-success">16.8%</span></td>
                            </tr>
                            <tr>
                                <td>당첨자 스토리</td>
                                <td>2025-04-01</td>
                                <td>2025-06-30</td>
                                <td>₹ 85,000,000</td>
                                <td>₹ 42,500,000</td>
                                <td>17.0%</td>
                                <td><span class="text-success">27.5%</span></td>
                            </tr>
                            <tr>
                                <td>모바일 앱 다운로드</td>
                                <td>2025-05-10</td>
                                <td>2025-06-10</td>
                                <td>₹ 70,000,000</td>
                                <td>₹ 21,680,000</td>
                                <td>40.1%</td>
                                <td><span class="text-success">31.2%</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <a href="campaigns.php" class="btn btn-sm btn-primary">
                    모든 캠페인 보기
                </a>
            </div>
        </div>
    </div>

    <!-- 마케팅 채널별 성과 -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">마케팅 채널별 성과</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="channelPerformanceChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
            </div>
        </div>

        <!-- 최근 활동 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">최근 마케팅 활동</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <ul class="products-list product-list-in-card pl-2 pr-2">
                    <li class="item">
                        <div class="product-info">
                            <a href="email-campaign-details.php?id=1" class="product-title">5월 신규 고객 웰컴 이메일
                                <span class="badge badge-info float-right">발송됨</span>
                            </a>
                            <span class="product-description">
                                15,420명 발송, 8,754명 열람 (56.8%)
                            </span>
                        </div>
                    </li>
                    <li class="item">
                        <div class="product-info">
                            <a href="advertisement-details.php?id=2" class="product-title">여름 시즌 온라인 광고
                                <span class="badge badge-success float-right">활성</span>
                            </a>
                            <span class="product-description">
                                노출 512,450회, 클릭 18,540회 (3.6% CTR)
                            </span>
                        </div>
                    </li>
                    <li class="item">
                        <div class="product-info">
                            <a href="sms.php?campaign=3" class="product-title">명절 특별 SMS 프로모션
                                <span class="badge badge-warning float-right">준비중</span>
                            </a>
                            <span class="product-description">
                                50,000명 타겟, 예상 전환율 15%
                            </span>
                        </div>
                    </li>
                    <li class="item">
                        <div class="product-info">
                            <a href="promotions.php?id=4" class="product-title">모바일 앱 다운로드 이벤트
                                <span class="badge badge-success float-right">활성</span>
                            </a>
                            <span class="product-description">
                                신규 다운로드 8,754건, 목표 달성률 87.5%
                            </span>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="card-footer text-center">
                <a href="campaigns.php" class="uppercase">모든 마케팅 활동 보기</a>
            </div>
        </div>
    </div>
</div>
