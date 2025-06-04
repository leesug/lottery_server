<!-- 통계 및 보고서 대시보드 콘텐츠 -->
<div class="row">
    <div class="col-md-12">
        <!-- 빠른 액션 버튼 -->
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="btn-group">
                    <a href="sales-report.php" class="btn btn-primary"><i class="fas fa-chart-line"></i> 판매 보고서</a>
                    <a href="financial-report.php" class="btn btn-success"><i class="fas fa-money-bill-wave"></i> 재무 보고서</a>
                    <a href="draw-report.php" class="btn btn-info"><i class="fas fa-random"></i> 추첨 보고서</a>
                    <a href="store-report.php" class="btn btn-warning"><i class="fas fa-store"></i> 판매점 보고서</a>
                    <a href="customer-report.php" class="btn btn-secondary"><i class="fas fa-users"></i> 고객 보고서</a>
                </div>
            </div>
        </div>

        <!-- 주요 통계 요약 카드 -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>234.58B</h3>
                        <p>누적 판매액</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <a href="sales-report.php" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>125.68B</h3>
                        <p>누적 당첨금</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <a href="draw-report.php" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>245만</h3>
                        <p>누적 고객 수</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <a href="customer-report.php" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>58</h3>
                        <p>1등 당첨자 수</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-medal"></i>
                    </div>
                    <a href="draw-report.php?filter=jackpot" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- 연간 판매 추이 차트 -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">연간 판매 추이</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="chart">
                    <canvas id="yearlySalesChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- 복권 종류별 판매 비율 -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">복권 종류별 판매 비율</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="lotteryTypeSalesChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- 최근 보고서 목록 -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">최근 보고서 목록</h3>
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
                                <th>보고서 ID</th>
                                <th>제목</th>
                                <th>유형</th>
                                <th>생성일</th>
                                <th>생성자</th>
                                <th>형식</th>
                                <th>액션</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>R2505001</td>
                                <td>2025년 5월 판매 현황 보고서</td>
                                <td><span class="badge bg-primary">판매</span></td>
                                <td>2025-05-18 09:30</td>
                                <td>김재원</td>
                                <td><span class="badge bg-danger">PDF</span></td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="#" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td>R2505002</td>
                                <td>2025년 5월 당첨자 통계 분석</td>
                                <td><span class="badge bg-success">당첨자</span></td>
                                <td>2025-05-17 14:25</td>
                                <td>이현우</td>
                                <td><span class="badge bg-success">Excel</span></td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="#" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td>R2505003</td>
                                <td>2025년 1분기 재무 보고서</td>
                                <td><span class="badge bg-warning">재무</span></td>
                                <td>2025-05-15 10:15</td>
                                <td>박소연</td>
                                <td><span class="badge bg-danger">PDF</span></td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="#" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td>R2504001</td>
                                <td>판매점 성과 분석 (2025년 4월)</td>
                                <td><span class="badge bg-info">판매점</span></td>
                                <td>2025-04-30 16:40</td>
                                <td>정민준</td>
                                <td><span class="badge bg-success">Excel</span></td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="#" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td>R2504002</td>
                                <td>로또 6/45 추첨 결과 분석 (2025년 4월)</td>
                                <td><span class="badge bg-secondary">추첨</span></td>
                                <td>2025-04-28 11:20</td>
                                <td>최지원</td>
                                <td><span class="badge bg-danger">PDF</span></td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="#" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <a href="sales-report.php" class="btn btn-sm btn-primary">
                    모든 보고서 보기
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- 지역별 판매 현황 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">지역별 판매 현황</h3>
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
                            <th>판매액</th>
                            <th>비율</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>서울</td>
                            <td>₹ 58.45B</td>
                            <td>
                                <div class="progress progress-xs">
                                    <div class="progress-bar bg-primary" style="width: 24.9%"></div>
                                </div>
                                <span class="badge bg-primary">24.9%</span>
                            </td>
                        </tr>
                        <tr>
                            <td>경기</td>
                            <td>₹ 45.78B</td>
                            <td>
                                <div class="progress progress-xs">
                                    <div class="progress-bar bg-success" style="width: 19.5%"></div>
                                </div>
                                <span class="badge bg-success">19.5%</span>
                            </td>
                        </tr>
                        <tr>
                            <td>인천</td>
                            <td>₹ 18.45B</td>
                            <td>
                                <div class="progress progress-xs">
                                    <div class="progress-bar bg-warning" style="width: 7.8%"></div>
                                </div>
                                <span class="badge bg-warning">7.8%</span>
                            </td>
                        </tr>
                        <tr>
                            <td>부산</td>
                            <td>₹ 15.86B</td>
                            <td>
                                <div class="progress progress-xs">
                                    <div class="progress-bar bg-info" style="width: 6.7%"></div>
                                </div>
                                <span class="badge bg-info">6.7%</span>
                            </td>
                        </tr>
                        <tr>
                            <td>기타</td>
                            <td>₹ 96.04B</td>
                            <td>
                                <div class="progress progress-xs">
                                    <div class="progress-bar bg-secondary" style="width: 41.1%"></div>
                                </div>
                                <span class="badge bg-secondary">41.1%</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 자주 조회되는 보고서 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">자주 조회되는 보고서</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a href="sales-report.php?template=T001" class="nav-link">
                            <i class="fas fa-chart-line mr-2 text-primary"></i> 판매 현황 일일 보고서
                            <span class="float-right text-muted">일별</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="draw-report.php?template=T002" class="nav-link">
                            <i class="fas fa-trophy mr-2 text-success"></i> 당첨금 지급 현황
                            <span class="float-right text-muted">주간</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="financial-report.php?template=T003" class="nav-link">
                            <i class="fas fa-money-bill-wave mr-2 text-warning"></i> 재무 요약 보고서
                            <span class="float-right text-muted">월간</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="store-report.php?template=T004" class="nav-link">
                            <i class="fas fa-store mr-2 text-info"></i> 판매점 성과 분석
                            <span class="float-right text-muted">월간</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="campaign-report.php?template=T005" class="nav-link">
                            <i class="fas fa-bullhorn mr-2 text-danger"></i> 마케팅 캠페인 효과 분석
                            <span class="float-right text-muted">분기별</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
