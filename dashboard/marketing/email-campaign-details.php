<?php
/**
 * 이메일 캠페인 상세 페이지
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// ID 파라미터 확인
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    // 유효하지 않은 ID일 경우 이메일 마케팅 페이지로 리디렉션
    header("Location: email.php");
    exit;
}

// 현재 페이지 정보
$pageTitle = "이메일 캠페인 상세 정보";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 데이터베이스 연결
$db = getDbConnection();

// 샘플 이메일 캠페인 데이터 - 실제로는 데이터베이스에서 가져옴
$emailCampaigns = [
    1 => [
        'id' => 1,
        'name' => '5월 추첨 뉴스레터',
        'subject' => 'KHUSHI LOTTERY: 5월 대형 추첨 소식',
        'target_group' => '모든 고객',
        'send_date' => '2025-05-10 09:30:00',
        'total_sent' => 12345,
        'open_rate' => 37.6,
        'click_rate' => 18.2,
        'status' => '완료됨',
        'content' => '<h1>5월 대형 추첨 소식</h1><p>안녕하세요, KHUSHI LOTTERY 회원님!</p><p>5월 대형 추첨이 다가오고 있습니다. 이번 달 추첨에는 역대 최대 규모의 당첨금이 준비되어 있습니다.</p><p>...</p>',
        'statistics' => [
            'delivered' => 12345,
            'opened' => 4642,
            'clicked' => 844,
            'bounced' => 123,
            'unsubscribed' => 52
        ],
        'links' => [
            [
                'url' => 'https://khushilottery.example/may-draw',
                'clicks' => 423,
                'ctr' => 9.1
            ],
            [
                'url' => 'https://khushilottery.example/buy-tickets',
                'clicks' => 315,
                'ctr' => 6.8
            ],
            [
                'url' => 'https://khushilottery.example/past-winners',
                'clicks' => 106,
                'ctr' => 2.3
            ]
        ]
    ],
    2 => [
        'id' => 2,
        'name' => '당첨자 안내 이메일',
        'subject' => '축하합니다! KHUSHI LOTTERY 당첨 안내',
        'target_group' => '최근 당첨자',
        'send_date' => '2025-05-12 10:15:00',
        'total_sent' => 246,
        'open_rate' => 92.3,
        'click_rate' => 85.4,
        'status' => '완료됨',
        'content' => '<h1>축하합니다!</h1><p>안녕하세요, [고객명]님!</p><p>KHUSHI LOTTERY 추첨에 당첨되신 것을 진심으로 축하드립니다. 귀하의 당첨 정보는 다음과 같습니다:</p><p>...</p>',
        'statistics' => [
            'delivered' => 246,
            'opened' => 227,
            'clicked' => 210,
            'bounced' => 0,
            'unsubscribed' => 0
        ],
        'links' => [
            [
                'url' => 'https://khushilottery.example/claim-prize',
                'clicks' => 189,
                'ctr' => 83.3
            ],
            [
                'url' => 'https://khushilottery.example/winner-guide',
                'clicks' => 152,
                'ctr' => 67.0
            ],
            [
                'url' => 'https://khushilottery.example/tax-info',
                'clicks' => 95,
                'ctr' => 41.9
            ]
        ]
    ],
    3 => [
        'id' => 3,
        'name' => '신규 복권 출시 안내',
        'subject' => 'KHUSHI LOTTERY 신규 복권 출시 및 대형 당첨금 안내',
        'target_group' => '활성 고객',
        'send_date' => '2025-05-20 08:00:00',
        'total_sent' => 0,
        'open_rate' => 0,
        'click_rate' => 0,
        'status' => '예약됨',
        'content' => '<h1>새로운 복권이 출시되었습니다!</h1><p>안녕하세요, [고객명]님!</p><p>KHUSHI LOTTERY가 새로운 복권을 출시했습니다. 더 많은 당첨 기회와 더 큰 당첨금이 기다리고 있습니다.</p><p>...</p>',
        'statistics' => [
            'delivered' => 0,
            'opened' => 0,
            'clicked' => 0,
            'bounced' => 0,
            'unsubscribed' => 0
        ],
        'links' => []
    ],
    4 => [
        'id' => 4,
        'name' => '비활성 고객 재활성화',
        'subject' => 'KHUSHI LOTTERY가 그리워요! 특별 프로모션 코드를 확인하세요',
        'target_group' => '휴면 고객',
        'send_date' => '2025-05-05 15:45:00',
        'total_sent' => 3578,
        'open_rate' => 22.5,
        'click_rate' => 8.7,
        'status' => '완료됨',
        'content' => '<h1>돌아오세요!</h1><p>안녕하세요, [고객명]님!</p><p>오랫동안 KHUSHI LOTTERY를 이용하지 않으셨네요. 귀하를 위한 특별 프로모션 코드를 준비했습니다.</p><p>...</p>',
        'statistics' => [
            'delivered' => 3578,
            'opened' => 805,
            'clicked' => 311,
            'bounced' => 95,
            'unsubscribed' => 42
        ],
        'links' => [
            [
                'url' => 'https://khushilottery.example/special-promo',
                'clicks' => 235,
                'ctr' => 29.2
            ],
            [
                'url' => 'https://khushilottery.example/buy-tickets',
                'clicks' => 76,
                'ctr' => 9.4
            ]
        ]
    ],
    5 => [
        'id' => 5,
        'name' => '월간 복권 소식지',
        'subject' => 'KHUSHI LOTTERY 5월 소식: 당첨자 인터뷰 및 새로운 복권 정보',
        'target_group' => '구독자',
        'send_date' => '2025-05-25 09:00:00',
        'total_sent' => 0,
        'open_rate' => 0,
        'click_rate' => 0,
        'status' => '예약됨',
        'content' => '<h1>KHUSHI LOTTERY 5월 소식</h1><p>안녕하세요, [고객명]님!</p><p>KHUSHI LOTTERY의 5월 소식을 알려드립니다. 이번 달에는 최근 당첨자 인터뷰와 새로운 복권 정보를 준비했습니다.</p><p>...</p>',
        'statistics' => [
            'delivered' => 0,
            'opened' => 0,
            'clicked' => 0,
            'bounced' => 0,
            'unsubscribed' => 0
        ],
        'links' => []
    ]
];

// ID에 해당하는 이메일 캠페인 정보 가져오기
$campaignInfo = isset($emailCampaigns[$id]) ? $emailCampaigns[$id] : null;

// 이메일 캠페인 정보가 없을 경우 이메일 마케팅 페이지로 리디렉션
if (!$campaignInfo) {
    header("Location: email.php");
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
                    <li class="breadcrumb-item"><a href="<?php echo SERVER_URL; ?>/dashboard/marketing/email.php">이메일 마케팅</a></li>
                    <li class="breadcrumb-item active">이메일 캠페인 상세 정보</li>
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
                <a href="email.php" class="btn btn-default mr-2">
                    <i class="fas fa-arrow-left"></i> 목록으로
                </a>
                <?php if ($campaignInfo['status'] === '예약됨'): ?>
                <a href="email-campaign-edit.php?id=<?php echo $id; ?>" class="btn btn-primary mr-2">
                    <i class="fas fa-edit"></i> 편집
                </a>
                <?php endif; ?>
                <a href="email-campaign-duplicate.php?id=<?php echo $id; ?>" class="btn btn-warning mr-2">
                    <i class="fas fa-copy"></i> 복제
                </a>
                <button type="button" class="btn btn-danger mr-2" data-toggle="modal" data-target="#deleteModal">
                    <i class="fas fa-trash"></i> 삭제
                </button>
                <?php if ($campaignInfo['status'] === '완료됨'): ?>
                <a href="email-campaign-resend.php?id=<?php echo $id; ?>" class="btn btn-info">
                    <i class="fas fa-redo"></i> 재발송
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 이메일 캠페인 정보 카드 -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">캠페인 정보</h3>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <tr>
                                <th style="width: 150px">캠페인 ID</th>
                                <td><?php echo $campaignInfo['id']; ?></td>
                            </tr>
                            <tr>
                                <th>캠페인명</th>
                                <td><?php echo $campaignInfo['name']; ?></td>
                            </tr>
                            <tr>
                                <th>이메일 제목</th>
                                <td><?php echo $campaignInfo['subject']; ?></td>
                            </tr>
                            <tr>
                                <th>대상 그룹</th>
                                <td><?php echo $campaignInfo['target_group']; ?></td>
                            </tr>
                            <tr>
                                <th>발송일</th>
                                <td><?php echo date('Y년 m월 d일 H:i', strtotime($campaignInfo['send_date'])); ?></td>
                            </tr>
                            <tr>
                                <th>발송 수</th>
                                <td><?php echo number_format($campaignInfo['total_sent']); ?></td>
                            </tr>
                            <tr>
                                <th>상태</th>
                                <td>
                                    <?php 
                                    $statusClass = '';
                                    switch($campaignInfo['status']) {
                                        case '완료됨':
                                            $statusClass = 'badge-success';
                                            break;
                                        case '예약됨':
                                            $statusClass = 'badge-warning';
                                            break;
                                        default:
                                            $statusClass = 'badge-info';
                                    }
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>"><?php echo $campaignInfo['status']; ?></span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">성과 요약</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($campaignInfo['status'] === '예약됨'): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 이 캠페인은 아직 발송되지 않았습니다. 캠페인이 발송되면 성과 데이터가 표시됩니다.
                        </div>
                        <?php else: ?>
                        <div class="row">
                            <div class="col-md-6 col-sm-6 col-12">
                                <div class="info-box">
                                    <span class="info-box-icon bg-info"><i class="fas fa-envelope-open"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">오픈율</span>
                                        <span class="info-box-number"><?php echo number_format($campaignInfo['open_rate'], 1); ?>%</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-sm-6 col-12">
                                <div class="info-box">
                                    <span class="info-box-icon bg-success"><i class="fas fa-mouse-pointer"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">클릭률</span>
                                        <span class="info-box-number"><?php echo number_format($campaignInfo['click_rate'], 1); ?>%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="progress-group">
                            <span class="progress-text">발송됨</span>
                            <span class="float-right"><?php echo number_format($campaignInfo['statistics']['delivered']); ?></span>
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-primary" style="width: 100%"></div>
                            </div>
                        </div>
                        <div class="progress-group">
                            <span class="progress-text">오픈됨</span>
                            <span class="float-right"><?php echo number_format($campaignInfo['statistics']['opened']); ?></span>
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-info" style="width: <?php echo ($campaignInfo['statistics']['opened'] / $campaignInfo['statistics']['delivered'] * 100); ?>%"></div>
                            </div>
                        </div>
                        <div class="progress-group">
                            <span class="progress-text">클릭됨</span>
                            <span class="float-right"><?php echo number_format($campaignInfo['statistics']['clicked']); ?></span>
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-success" style="width: <?php echo ($campaignInfo['statistics']['clicked'] / $campaignInfo['statistics']['delivered'] * 100); ?>%"></div>
                            </div>
                        </div>
                        <div class="progress-group">
                            <span class="progress-text">반송됨</span>
                            <span class="float-right"><?php echo number_format($campaignInfo['statistics']['bounced']); ?></span>
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-danger" style="width: <?php echo ($campaignInfo['statistics']['bounced'] / $campaignInfo['statistics']['delivered'] * 100); ?>%"></div>
                            </div>
                        </div>
                        <div class="progress-group">
                            <span class="progress-text">구독 취소</span>
                            <span class="float-right"><?php echo number_format($campaignInfo['statistics']['unsubscribed']); ?></span>
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-warning" style="width: <?php echo ($campaignInfo['statistics']['unsubscribed'] / $campaignInfo['statistics']['delivered'] * 100); ?>%"></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 이메일 컨텐츠 미리보기 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">이메일 컨텐츠 미리보기</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>이메일 제목</label>
                            <input type="text" class="form-control" value="<?php echo $campaignInfo['subject']; ?>" readonly>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>이메일 내용</label>
                            <div class="email-preview" style="border: 1px solid #ddd; padding: 15px; min-height: 200px; background-color: #fff;">
                                <?php echo $campaignInfo['content']; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 text-center">
                        <button type="button" class="btn btn-default mr-2">
                            <i class="fas fa-desktop"></i> 데스크톱 보기
                        </button>
                        <button type="button" class="btn btn-default mr-2">
                            <i class="fas fa-mobile-alt"></i> 모바일 보기
                        </button>
                        <button type="button" class="btn btn-default">
                            <i class="fas fa-envelope"></i> 테스트 발송
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($campaignInfo['status'] === '완료됨' && !empty($campaignInfo['links'])): ?>
        <!-- 링크 성과 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">링크 성과</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>링크 URL</th>
                                <th>클릭 수</th>
                                <th>클릭률</th>
                                <th>차트</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($campaignInfo['links'] as $link): ?>
                            <tr>
                                <td><?php echo $link['url']; ?></td>
                                <td><?php echo number_format($link['clicks']); ?></td>
                                <td><?php echo number_format($link['ctr'], 1); ?>%</td>
                                <td>
                                    <div class="progress progress-xs">
                                        <div class="progress-bar bg-success" style="width: <?php echo $link['ctr']; ?>%"></div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>
<!-- /.content -->

<!-- 삭제 확인 모달 -->
<div class="modal fade" id="deleteModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">이메일 캠페인 삭제</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>정말로 이 이메일 캠페인을 삭제하시겠습니까?</p>
                <p><strong><?php echo $campaignInfo['name']; ?></strong></p>
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
    console.log('이메일 캠페인 상세 페이지가 로드되었습니다. ID: <?php echo $id; ?>');
});
</script>

<?php
// 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';
?>
