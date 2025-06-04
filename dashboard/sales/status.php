<?php
/**
 * 판매 현황 페이지
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 데이터베이스 연결
$db = get_db_connection();

// 현재 페이지 정보
$pageTitle = "판매 현황";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 메시지 초기화
$message = '';
$message_type = '';

// 필터 값 설정
$filter_date = $_GET['date'] ?? date('Y-m-d');
$filter_product = $_GET['product'] ?? 'all';
$filter_region = $_GET['region'] ?? 'all';

// 템플릿 헤더 포함
include_once TEMPLATES_PATH . '/dashboard_header.php';
?>

<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><?php echo $pageTitle; ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?php echo SERVER_URL; ?>/dashboard/index.php">홈</a></li>
                    <li class="breadcrumb-item">판매 관리</li>
                    <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- /.content-header -->

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- 필터 -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">검색 필터</h3>
            </div>
            <div class="card-body">
                <form action="" method="get" class="form-row">
                    <div class="form-group col-md-4">
                        <label for="date">날짜</label>
                        <input type="date" class="form-control" id="date" name="date" value="<?php echo $filter_date; ?>">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="product">복권 상품</label>
                        <select class="form-control" id="product" name="product">
                            <option value="all" <?php echo ($filter_product === 'all') ? 'selected' : ''; ?>>전체</option>
                            <option value="DAILY001" <?php echo ($filter_product === 'DAILY001') ? 'selected' : ''; ?>>일일 복권</option>
                            <option value="WEEKLY001" <?php echo ($filter_product === 'WEEKLY001') ? 'selected' : ''; ?>>주간 복권</option>
                            <option value="MONTHLY001" <?php echo ($filter_product === 'MONTHLY001') ? 'selected' : ''; ?>>월간 특별 복권</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="region">지역</label>
                        <select class="form-control" id="region" name="region">
                            <option value="all" <?php echo ($filter_region === 'all') ? 'selected' : ''; ?>>전체</option>
                            <option value="kathmandu" <?php echo ($filter_region === 'kathmandu') ? 'selected' : ''; ?>>카트만두</option>
                            <option value="pokhara" <?php echo ($filter_region === 'pokhara') ? 'selected' : ''; ?>>포카라</option>
                            <option value="lalitpur" <?php echo ($filter_region === 'lalitpur') ? 'selected' : ''; ?>>랄리트푸르</option>
                            <option value="bhaktapur" <?php echo ($filter_region === 'bhaktapur') ? 'selected' : ''; ?>>박타푸르</option>
                        </select>
                    </div>
                    <div class="form-group col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-block">적용</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- 판매 요약 -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>2,450</h3>
                        <p>판매 수량</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>₹ 6,850,000</h3>
                        <p>판매 금액</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>68</h3>
                        <p>판매점 수</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-store"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>85%</h3>
                        <p>목표 달성률</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 판매 차트 -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">상품별 판매 현황</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 300px; display: flex; align-items: center; justify-content: center; background-color: #f8f9fa;">
                            <span>상품별 판매 현황 차트 (개발 중)</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">시간대별 판매 현황</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 300px; display: flex; align-items: center; justify-content: center; background-color: #f8f9fa;">
                            <span>시간대별 판매 현황 차트 (개발 중)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 판매 목록 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">판매 상세 목록</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                    <div class="btn-group">
                        <button type="button" class="btn btn-tool dropdown-toggle" data-toggle="dropdown">
                            <i class="fas fa-download"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right" role="menu">
                            <a href="#" class="dropdown-item">Excel로 내보내기</a>
                            <a href="#" class="dropdown-item">CSV로 내보내기</a>
                            <a href="#" class="dropdown-item">PDF로 내보내기</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>거래번호</th>
                            <th>판매점</th>
                            <th>지역</th>
                            <th>복권 상품</th>
                            <th>수량</th>
                            <th>금액</th>
                            <th>판매 시간</th>
                            <th>상태</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>TX0001254</td>
                            <td>네팔 마트 #23</td>
                            <td>카트만두</td>
                            <td>일일 복권</td>
                            <td>12</td>
                            <td>₹ 24,000</td>
                            <td>2025-05-16 12:45</td>
                            <td><span class="badge badge-success">완료</span></td>
                        </tr>
                        <tr>
                            <td>TX0001253</td>
                            <td>카트만두 센터 #05</td>
                            <td>카트만두</td>
                            <td>주간 복권</td>
                            <td>8</td>
                            <td>₹ 40,000</td>
                            <td>2025-05-16 12:30</td>
                            <td><span class="badge badge-success">완료</span></td>
                        </tr>
                        <tr>
                            <td>TX0001252</td>
                            <td>포카라 샵 #18</td>
                            <td>포카라</td>
                            <td>일일 복권</td>
                            <td>15</td>
                            <td>₹ 30,000</td>
                            <td>2025-05-16 12:15</td>
                            <td><span class="badge badge-success">완료</span></td>
                        </tr>
                        <tr>
                            <td>TX0001251</td>
                            <td>랄리트푸르 #11</td>
                            <td>랄리트푸르</td>
                            <td>월간 특별 복권</td>
                            <td>5</td>
                            <td>₹ 50,000</td>
                            <td>2025-05-16 11:55</td>
                            <td><span class="badge badge-success">완료</span></td>
                        </tr>
                        <tr>
                            <td>TX0001250</td>
                            <td>비랏나가르 #07</td>
                            <td>비랏나가르</td>
                            <td>일일 복권</td>
                            <td>10</td>
                            <td>₹ 20,000</td>
                            <td>2025-05-16 11:45</td>
                            <td><span class="badge badge-success">완료</span></td>
                        </tr>
                        <tr>
                            <td>TX0001249</td>
                            <td>네팔 마트 #23</td>
                            <td>카트만두</td>
                            <td>일일 복권</td>
                            <td>8</td>
                            <td>₹ 16,000</td>
                            <td>2025-05-16 11:30</td>
                            <td><span class="badge badge-success">완료</span></td>
                        </tr>
                        <tr>
                            <td>TX0001248</td>
                            <td>포카라 샵 #18</td>
                            <td>포카라</td>
                            <td>주간 복권</td>
                            <td>6</td>
                            <td>₹ 30,000</td>
                            <td>2025-05-16 11:15</td>
                            <td><span class="badge badge-success">완료</span></td>
                        </tr>
                        <tr>
                            <td>TX0001247</td>
                            <td>카트만두 센터 #05</td>
                            <td>카트만두</td>
                            <td>일일 복권</td>
                            <td>5</td>
                            <td>₹ 10,000</td>
                            <td>2025-05-16 11:00</td>
                            <td><span class="badge badge-warning">검증중</span></td>
                        </tr>
                        <tr>
                            <td>TX0001246</td>
                            <td>랄리트푸르 #11</td>
                            <td>랄리트푸르</td>
                            <td>일일 복권</td>
                            <td>7</td>
                            <td>₹ 14,000</td>
                            <td>2025-05-16 10:45</td>
                            <td><span class="badge badge-danger">취소됨</span></td>
                        </tr>
                        <tr>
                            <td>TX0001245</td>
                            <td>비랏나가르 #07</td>
                            <td>비랏나가르</td>
                            <td>주간 복권</td>
                            <td>4</td>
                            <td>₹ 20,000</td>
                            <td>2025-05-16 10:30</td>
                            <td><span class="badge badge-success">완료</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer clearfix">
                <ul class="pagination pagination-sm m-0 float-right">
                    <li class="page-item"><a class="page-link" href="#">&laquo;</a></li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item"><a class="page-link" href="#">&raquo;</a></li>
                </ul>
            </div>
        </div>
    </div>
</section>

<?php
// 템플릿 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';
?>