<!-- 시스템 관리 대시보드 콘텐츠 -->
<div class="row">
    <div class="col-md-12">
        <!-- 빠른 액션 버튼 -->
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="btn-group">
                    <a href="users.php" class="btn btn-primary"><i class="fas fa-users-cog"></i> 사용자 관리</a>
                    <a href="roles.php" class="btn btn-success"><i class="fas fa-user-shield"></i> 권한 관리</a>
                    <a href="settings.php" class="btn btn-info"><i class="fas fa-cogs"></i> 시스템 설정</a>
                    <a href="backup.php" class="btn btn-warning"><i class="fas fa-database"></i> 백업 및 복원</a>
                    <a href="logs.php" class="btn btn-secondary"><i class="fas fa-list"></i> 로그 관리</a>
                </div>
            </div>
        </div>

        <!-- 시스템 통계 요약 카드 -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>85</h3>
                        <p>총 사용자 수</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <a href="users.php" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>14</h3>
                        <p>보관 중인 백업</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-database"></i>
                    </div>
                    <a href="backup.php" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>45</h3>
                        <p>오늘 로그인 수</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                    <a href="logs.php?filter=login" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>4</h3>
                        <p>보안 경고</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <a href="logs.php?filter=security" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- 시스템 자원 사용량 -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">시스템 자원 사용량</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <!-- CPU 사용량 -->
                        <div class="progress-group">
                            <span class="progress-text">CPU 사용량</span>
                            <span class="float-right">28%</span>
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-primary" style="width: 28%"></div>
                            </div>
                        </div>
                        <!-- 메모리 사용량 -->
                        <div class="progress-group">
                            <span class="progress-text">메모리 사용량</span>
                            <span class="float-right">42%</span>
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-success" style="width: 42%"></div>
                            </div>
                        </div>
                        <!-- 디스크 사용량 -->
                        <div class="progress-group">
                            <span class="progress-text">디스크 사용량</span>
                            <span class="float-right">65%</span>
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-warning" style="width: 65%"></div>
                            </div>
                        </div>
                        <!-- 네트워크 사용량 -->
                        <div class="progress-group">
                            <span class="progress-text">네트워크 사용량</span>
                            <span class="float-right">35%</span>
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-info" style="width: 35%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <!-- 데이터베이스 크기 -->
                        <div class="info-box bg-light">
                            <div class="info-box-content">
                                <span class="info-box-text text-center text-muted">데이터베이스 크기</span>
                                <span class="info-box-number text-center text-muted mb-0">12.75 GB</span>
                            </div>
                        </div>
                        <!-- 총 디스크 공간 -->
                        <div class="info-box bg-light">
                            <div class="info-box-content">
                                <span class="info-box-text text-center text-muted">총 디스크 공간</span>
                                <span class="info-box-number text-center text-muted mb-0">500 GB</span>
                            </div>
                        </div>
                        <!-- 여유 디스크 공간 -->
                        <div class="info-box bg-light">
                            <div class="info-box-content">
                                <span class="info-box-text text-center text-muted">여유 디스크 공간</span>
                                <span class="info-box-number text-center text-muted mb-0">175 GB</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 서버 정보 -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">서버 정보</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table">
                    <tr>
                        <td><i class="fas fa-server mr-2"></i> 운영 체제</td>
                        <td>CentOS Linux 8.4</td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-globe mr-2"></i> 웹 서버</td>
                        <td>Apache 2.4.51</td>
                    </tr>
                    <tr>
                        <td><i class="fab fa-php mr-2"></i> PHP 버전</td>
                        <td>PHP 8.1.12</td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-database mr-2"></i> 데이터베이스</td>
                        <td>MySQL 8.0.27</td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-network-wired mr-2"></i> 서버 IP</td>
                        <td>192.168.1.100</td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-laptop-code mr-2"></i> 호스트명</td>
                        <td>lottery-prod-01</td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-clock mr-2"></i> 가동 시간</td>
                        <td>45일 12시간 28분</td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-power-off mr-2"></i> 마지막 재부팅</td>
                        <td>2025-04-03 01:35:22</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- 최근 사용자 활동 -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">최근 사용자 활동</h3>
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
                                <th>사용자</th>
                                <th>액션</th>
                                <th>IP 주소</th>
                                <th>시간</th>
                                <th>상태</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>admin</td>
                                <td>로그인 성공</td>
                                <td>192.168.1.101</td>
                                <td>2025-05-18 14:32:15</td>
                                <td><span class="badge bg-success">성공</span></td>
                            </tr>
                            <tr>
                                <td>finance_manager</td>
                                <td>재무 보고서 생성</td>
                                <td>192.168.1.105</td>
                                <td>2025-05-18 14:25:43</td>
                                <td><span class="badge bg-success">성공</span></td>
                            </tr>
                            <tr>
                                <td>store_manager</td>
                                <td>판매점 추가</td>
                                <td>192.168.1.110</td>
                                <td>2025-05-18 14:15:22</td>
                                <td><span class="badge bg-success">성공</span></td>
                            </tr>
                            <tr>
                                <td>marketing_user</td>
                                <td>로그인 실패</td>
                                <td>192.168.1.115</td>
                                <td>2025-05-18 14:10:18</td>
                                <td><span class="badge bg-danger">실패</span></td>
                            </tr>
                            <tr>
                                <td>system_admin</td>
                                <td>백업 실행</td>
                                <td>192.168.1.100</td>
                                <td>2025-05-18 14:05:32</td>
                                <td><span class="badge bg-info">정보</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <a href="activity-logs.php" class="btn btn-sm btn-primary">
                    모든 활동 보기
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- 역할별 사용자 분포 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">역할별 사용자 분포</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="userRoleDistribution" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
            </div>
        </div>

        <!-- 보안 알림 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">보안 알림</h3>
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
                            <a href="logs.php?filter=security&id=1" class="product-title">잠재적 무단 접근 시도
                                <span class="badge badge-danger float-right">높음</span>
                            </a>
                            <span class="product-description">
                                IP: 203.0.113.15 | 시도 횟수: 8 | 2025-05-18 13:45:22
                            </span>
                        </div>
                    </li>
                    <li class="item">
                        <div class="product-info">
                            <a href="logs.php?filter=security&id=2" class="product-title">실패한 로그인 시도
                                <span class="badge badge-warning float-right">중간</span>
                            </a>
                            <span class="product-description">
                                IP: 192.168.1.115 | 시도 횟수: 3 | 2025-05-18 12:32:10
                            </span>
                        </div>
                    </li>
                    <li class="item">
                        <div class="product-info">
                            <a href="logs.php?filter=security&id=3" class="product-title">비정상적인 행동 패턴
                                <span class="badge badge-info float-right">낮음</span>
                            </a>
                            <span class="product-description">
                                IP: 192.168.1.130 | 시도 횟수: 1 | 2025-05-18 11:15:40
                            </span>
                        </div>
                    </li>
                    <li class="item">
                        <div class="product-info">
                            <a href="logs.php?filter=security&id=4" class="product-title">권한 승격 시도
                                <span class="badge badge-warning float-right">중간</span>
                            </a>
                            <span class="product-description">
                                IP: 203.0.113.25 | 시도 횟수: 2 | 2025-05-18 10:22:15
                            </span>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="card-footer text-center">
                <a href="logs.php?filter=security" class="uppercase">모든 보안 알림 보기</a>
            </div>
        </div>
    </div>
</div>
