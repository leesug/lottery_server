<?php
/**
 * 광고 상세 페이지
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// ID 파라미터 확인
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    // 유효하지 않은 ID일 경우 광고 목록 페이지로 리디렉션
    header("Location: advertisements.php");
    exit;
}

// 현재 페이지 정보
$pageTitle = "광고 상세 정보";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 데이터베이스 연결
$db = getDbConnection();

// 샘플 광고 데이터 - 실제로는 데이터베이스에서 가져옴
$advertisements = [
    1 => [
        'id' => 1,
        'name' => '페이스북 - 복권 홍보',
        'type' => '소셜 미디어',
        'start_date' => '2025-05-01',
        'end_date' => '2025-06-30',
        'budget' => 250000,
        'status' => '진행 중',
        'description' => '페이스북을 통한 KHUSHI LOTTERY 복권 홍보 캠페인입니다. 주요 타겟층은 25-45세 사이의 남녀입니다.',
        'creative_url' => 'assets/img/ad-creative-1.jpg',
        'target_audience' => '25-45세 남녀',
        'placement' => '페이스북 뉴스피드, 인스타그램 스토리',
        'impressions' => 120500,
        'clicks' => 15850,
        'ctr' => 13.15,
        'conversions' => 2340,
        'conversion_rate' => 14.76,
        'cost_per_click' => 15.77,
        'cost_per_conversion' => 106.84
    ],
    2 => [
        'id' => 2,
        'name' => 'TV 광고 - 대형 당첨금',
        'type' => 'TV',
        'start_date' => '2025-05-01',
        'end_date' => '2025-05-31',
        'budget' => 500000,
        'status' => '진행 중',
        'description' => '주요 TV 채널에서 방영되는 KHUSHI LOTTERY 대형 당첨금 홍보 광고입니다. 프라임 타임에 방송됩니다.',
        'creative_url' => 'assets/img/ad-creative-2.jpg',
        'target_audience' => '전국 TV 시청자',
        'placement' => '주요 TV 채널 프라임 타임',
        'impressions' => 2500000,
        'views' => 1800000,
        'completion_rate' => 72,
        'estimated_reach' => 3500000,
        'frequency' => 3.5,
        'cost_per_view' => 0.28
    ],
    3 => [
        'id' => 3,
        'name' => '라디오 광고 - 주간 추첨',
        'type' => '라디오',
        'start_date' => '2025-05-10',
        'end_date' => '2025-06-10',
        'budget' => 150000,
        'status' => '진행 중',
        'description' => '주요 라디오 방송국에서 방송되는 KHUSHI LOTTERY 주간 추첨 홍보 광고입니다. 출퇴근 시간대에 방송됩니다.',
        'creative_url' => 'assets/img/ad-creative-3.jpg',
        'target_audience' => '출퇴근 시간대 라디오 청취자',
        'placement' => '주요 라디오 방송국 출퇴근 시간대',
        'impressions' => 1200000,
        'estimated_reach' => 800000,
        'frequency' => 4.2,
        'cost_per_impression' => 0.13
    ],
    4 => [
        'id' => 4,
        'name' => '구글 광고 - 검색 키워드',
        'type' => '온라인',
        'start_date' => '2025-06-01',
        'end_date' => '2025-06-30',
        'budget' => 180000,
        'status' => '예정됨',
        'description' => '구글 검색 엔진에 노출되는 KHUSHI LOTTERY 검색 키워드 광고입니다. 주요 키워드는 "복권", "당첨금", "로또" 등입니다.',
        'creative_url' => 'assets/img/ad-creative-4.jpg',
        'target_audience' => '25-60세 남녀',
        'placement' => '구글 검색 엔진',
        'keywords' => '복권, 당첨금, 로또, 추첨, 대박, 당첨, KHUSHI LOTTERY',
        'impressions' => 0,
        'clicks' => 0,
        'ctr' => 0,
        'conversions' => 0,
        'conversion_rate' => 0,
        'cost_per_click' => 0,
        'cost_per_conversion' => 0
    ],
    5 => [
        'id' => 5,
        'name' => '신문 광고 - 전면',
        'type' => '인쇄 매체',
        'start_date' => '2025-04-01',
        'end_date' => '2025-04-30',
        'budget' => 320000,
        'status' => '종료됨',
        'description' => '주요 일간지 전면에 게재된 KHUSHI LOTTERY 홍보 광고입니다. 대형 당첨금과 당첨자 인터뷰를 중점적으로 다룹니다.',
        'creative_url' => 'assets/img/ad-creative-5.jpg',
        'target_audience' => '신문 구독자',
        'placement' => '주요 일간지 전면',
        'impressions' => 750000,
        'estimated_reach' => 950000,
        'frequency' => 1.2,
        'cost_per_impression' => 0.43
    ]
];

// ID에 해당하는 광고 정보 가져오기
$adInfo = isset($advertisements[$id]) ? $advertisements[$id] : null;

// 광고 정보가 없을 경우 광고 목록 페이지로 리디렉션
if (!$adInfo) {
    header("Location: advertisements.php");
    exit;
}

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
                    <li class="breadcrumb-item"><a href="<?php echo SERVER_URL; ?>/dashboard/">홈</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo SERVER_URL; ?>/dashboard/marketing/">마케팅 관리</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo SERVER_URL; ?>/dashboard/marketing/advertisements.php">광고 관리</a></li>
                    <li class="breadcrumb-item active">광고 상세 정보</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- /.content-header -->

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- 상단 액션 버튼 -->
        <div class="row mb-3">
            <div class="col-md-12">
                <a href="advertisements.php" class="btn btn-default mr-2">
                    <i class="fas fa-arrow-left"></i> 목록으로
                </a>
                <a href="advertisement-edit.php?id=<?php echo $id; ?>" class="btn btn-primary mr-2">
                    <i class="fas fa-edit"></i> 편집
                </a>
                <?php if ($adInfo['status'] === '예정됨' || $adInfo['status'] === '진행 중'): ?>
                <button type="button" class="btn btn-warning mr-2" data-toggle="modal" data-target="#pauseModal">
                    <i class="fas fa-pause"></i> 일시 중지
                </button>
                <?php endif; ?>
                <button type="button" class="btn btn-danger mr-2" data-toggle="modal" data-target="#deleteModal">
                    <i class="fas fa-trash"></i> 삭제
                </button>
                <a href="advertisement-duplicate.php?id=<?php echo $id; ?>" class="btn btn-info">
                    <i class="fas fa-copy"></i> 복제
                </a>
            </div>
        </div>
        
        <!-- 광고 정보 카드 -->
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">광고 정보</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <table class="table table-bordered">
                                    <tr>
                                        <th style="width: 150px">광고 ID</th>
                                        <td><?php echo $adInfo['id']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>광고명</th>
                                        <td><?php echo $adInfo['name']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>유형</th>
                                        <td><span class="badge badge-primary"><?php echo $adInfo['type']; ?></span></td>
                                    </tr>
                                    <tr>
                                        <th>설명</th>
                                        <td><?php echo $adInfo['description']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>기간</th>
                                        <td><?php echo $adInfo['start_date']; ?> ~ <?php echo $adInfo['end_date']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>예산</th>
                                        <td>₹ <?php echo number_format($adInfo['budget']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>타겟 고객</th>
                                        <td><?php echo $adInfo['target_audience']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>광고 게재 위치</th>
                                        <td><?php echo $adInfo['placement']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>상태</th>
                                        <td>
                                            <?php 
                                            $statusClass = '';
                                            switch($adInfo['status']) {
                                                case '진행 중':
                                                    $statusClass = 'badge-success';
                                                    break;
                                                case '예정됨':
                                                    $statusClass = 'badge-warning';
                                                    break;
                                                case '종료됨':
                                                    $statusClass = 'badge-secondary';
                                                    break;
                                                default:
                                                    $statusClass = 'badge-info';
                                            }
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?>"><?php echo $adInfo['status']; ?></span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">광고 소재</h3>
                    </div>
                    <div class="card-body text-center">
                        <img src="<?php echo SERVER_URL; ?>/assets/img/ad-sample.jpg" alt="광고 소재" class="img-fluid mb-3">
                        <div class="mt-3">
                            <a href="#" class="btn btn-sm btn-default mr-2">
                                <i class="fas fa-download"></i> 다운로드
                            </a>
                            <a href="#" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> 크게 보기
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 광고 성과 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">광고 성과</h3>
            </div>
            <div class="card-body">
                <?php if ($adInfo['status'] === '예정됨'): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 이 광고는 아직 시작되지 않았습니다. 광고가 시작되면 성과 데이터가 표시됩니다.
                </div>
                <?php else: ?>
                <div class="row">
                    <?php if (isset($adInfo['impressions'])): ?>
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-info"><i class="fas fa-eye"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">노출 수</span>
                                <span class="info-box-number"><?php echo number_format($adInfo['impressions']); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($adInfo['clicks'])): ?>
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-success"><i class="fas fa-mouse-pointer"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">클릭 수</span>
                                <span class="info-box-number"><?php echo number_format($adInfo['clicks']); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($adInfo['ctr'])): ?>
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-warning"><i class="fas fa-percentage"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">클릭률(CTR)</span>
                                <span class="info-box-number"><?php echo number_format($adInfo['ctr'], 2); ?>%</span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($adInfo['conversions'])): ?>
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-danger"><i class="fas fa-shopping-cart"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">전환 수</span>
                                <span class="info-box-number"><?php echo number_format($adInfo['conversions']); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($adInfo['conversion_rate'])): ?>
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-primary"><i class="fas fa-chart-line"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">전환율</span>
                                <span class="info-box-number"><?php echo number_format($adInfo['conversion_rate'], 2); ?>%</span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($adInfo['cost_per_click'])): ?>
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-info"><i class="fas fa-hand-pointer"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">클릭당 비용(CPC)</span>
                                <span class="info-box-number">₹ <?php echo number_format($adInfo['cost_per_click'], 2); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($adInfo['cost_per_conversion'])): ?>
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-success"><i class="fas fa-coins"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">전환당 비용(CPA)</span>
                                <span class="info-box-number">₹ <?php echo number_format($adInfo['cost_per_conversion'], 2); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($adInfo['views'])): ?>
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-info"><i class="fas fa-play-circle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">재생 수</span>
                                <span class="info-box-number"><?php echo number_format($adInfo['views']); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($adInfo['completion_rate'])): ?>
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-warning"><i class="fas fa-video"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">완료율</span>
                                <span class="info-box-number"><?php echo number_format($adInfo['completion_rate'], 2); ?>%</span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->

<!-- 일시 중지 확인 모달 -->
<div class="modal fade" id="pauseModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">광고 일시 중지</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>이 광고를 일시 중지하시겠습니까?</p>
                <p>일시 중지된 광고는 다시 활성화할 수 있습니다.</p>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">취소</button>
                <button type="button" class="btn btn-warning">일시 중지</button>
            </div>
        </div>
    </div>
</div>

<!-- 삭제 확인 모달 -->
<div class="modal fade" id="deleteModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">광고 삭제</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>정말로 이 광고를 삭제하시겠습니까?</p>
                <p><strong><?php echo $adInfo['name']; ?></strong></p>
                <p class="text-danger">이 작업은 되돌릴 수 없습니다.</p>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">취소</button>
                <button type="button" class="btn btn-danger">삭제</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('광고 상세 페이지가 로드되었습니다. ID: <?php echo $id; ?>');
});
</script>

<?php
// 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';
?>
