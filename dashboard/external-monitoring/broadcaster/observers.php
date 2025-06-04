<?php
/**
 * 추첨 참관인 관리 페이지
 */

// 공통 파일 포함
require_once '../../../includes/config.php';
require_once '../../../includes/db.php';
require_once '../../../includes/functions.php';

// 페이지 변수 설정
$pageTitle = "추첨 참관인 관리";
$currentSection = "broadcaster";
$currentPage = basename($_SERVER['PHP_SELF']);

// 데이터베이스 연결
$db = getDBConnection();

// 참관인 상태 변경 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    try {
        $observerId = intval($_POST['observer_id']);
        $status = $_POST['status'];
        $remarks = htmlspecialchars($_POST['remarks']);
        
        // 상태 업데이트
        $stmt = $db->prepare("
            UPDATE draw_observers 
            SET status = ?, remarks = ?, approval_date = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        $approvalDate = ($status === 'approved') ? date('Y-m-d H:i:s') : NULL;
        $stmt->execute([$status, $remarks, $approvalDate, $observerId]);
        
        $successMessage = "참관인 상태가 변경되었습니다.";
    } catch (Exception $e) {
        $errorMessage = "상태 변경 중 오류가 발생했습니다: " . $e->getMessage();
    }
}

// 참관인 추가 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_observer'])) {
    try {
        $observerName = htmlspecialchars($_POST['observer_name']);
        $organization = htmlspecialchars($_POST['organization']);
        $position = htmlspecialchars($_POST['position']);
        $contactPhone = htmlspecialchars($_POST['contact_phone']);
        $contactEmail = htmlspecialchars($_POST['contact_email']);
        $remarks = htmlspecialchars($_POST['remarks']);
        $drawId = intval($_POST['draw_id']);
        
        // 새 참관인 추가
        $stmt = $db->prepare("
            INSERT INTO draw_observers (draw_id, observer_name, organization, position, 
                                      contact_phone, contact_email, status, remarks)
            VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)
        ");
        $stmt->execute([
            $drawId, $observerName, $organization, $position, 
            $contactPhone, $contactEmail, $remarks
        ]);
        
        $successMessage = "참관인이 성공적으로 추가되었습니다.";
    } catch (Exception $e) {
        $errorMessage = "참관인 추가 중 오류가 발생했습니다: " . $e->getMessage();
    }
}

// 현재 진행 중인 추첨 회차 정보 - 샘플 데이터
$currentDraws = [
    ['id' => 125, 'name' => '제125회 추첨'],
    ['id' => 126, 'name' => '제126회 추첨'],
    ['id' => 127, 'name' => '제127회 추첨'],
];

// 참관인 목록 - 샘플 데이터
$observers = [
    [
        'id' => 1,
        'draw_id' => 126,
        'draw_name' => '제126회 추첨',
        'name' => '최재현',
        'organization' => '시민단체',
        'position' => '감사위원',
        'contact_phone' => '010-5555-6666',
        'contact_email' => 'choi@example.com',
        'registration_date' => '2025-05-10',
        'status' => 'approved',
        'approval_date' => '2025-05-12',
        'remarks' => '이전 참관 경험 있음'
    ],
    [
        'id' => 2,
        'draw_id' => 126,
        'draw_name' => '제126회 추첨',
        'name' => '장현석',
        'organization' => '로또 애호가 협회',
        'position' => '회원',
        'contact_phone' => '010-7777-8888',
        'contact_email' => 'jang@example.com',
        'registration_date' => '2025-05-12',
        'status' => 'pending',
        'approval_date' => null,
        'remarks' => '추첨 과정 이해도 높음'
    ],
    [
        'id' => 3,
        'draw_id' => 126,
        'draw_name' => '제126회 추첨',
        'name' => '정수미',
        'organization' => '방송심의위원회',
        'position' => '위원',
        'contact_phone' => '010-3333-4444',
        'contact_email' => 'jung@example.com',
        'registration_date' => '2025-05-15',
        'status' => 'rejected',
        'approval_date' => '2025-05-16',
        'remarks' => '내부 일정 충돌로 인한 거절'
    ],
    [
        'id' => 4,
        'draw_id' => 127,
        'draw_name' => '제127회 추첨',
        'name' => '김민수',
        'organization' => '회계법인',
        'position' => '회계사',
        'contact_phone' => '010-1111-2222',
        'contact_email' => 'kim@example.com',
        'registration_date' => '2025-05-15',
        'status' => 'approved',
        'approval_date' => '2025-05-17',
        'remarks' => '공정성 검증 목적'
    ],
    [
        'id' => 5,
        'draw_id' => 127,
        'draw_name' => '제127회 추첨',
        'name' => '박지영',
        'organization' => '법무법인',
        'position' => '변호사',
        'contact_phone' => '010-9999-8888',
        'contact_email' => 'park@example.com',
        'registration_date' => '2025-05-16',
        'status' => 'pending',
        'approval_date' => null,
        'remarks' => '법적 절차 검토 목적'
    ]
];

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
                    <li class="breadcrumb-item"><a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/index.php">외부접속감시</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/broadcaster/index.php">추첨 방송국</a></li>
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
        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas fa-check"></i> 성공!</h5>
                <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($errorMessage)): ?>
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas fa-ban"></i> 오류!</h5>
                <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>
        
        <!-- 통계 상자 -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>
                            <?php
                            // 전체 참관인 수
                            echo count($observers);
                            ?>
                        </h3>
                        <p>전체 참관인</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
            <!-- ./col -->
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>
                            <?php
                            // 승인된 참관인 수
                            $approvedCount = count(array_filter($observers, function($observer) {
                                return $observer['status'] === 'approved';
                            }));
                            echo $approvedCount;
                            ?>
                        </h3>
                        <p>승인된 참관인</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
            <!-- ./col -->
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>
                            <?php
                            // 대기 중인 참관인 수
                            $pendingCount = count(array_filter($observers, function($observer) {
                                return $observer['status'] === 'pending';
                            }));
                            echo $pendingCount;
                            ?>
                        </h3>
                        <p>대기 중인 참관인</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
            <!-- ./col -->
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>
                            <?php
                            // 반려된 참관인 수
                            $rejectedCount = count(array_filter($observers, function($observer) {
                                return $observer['status'] === 'rejected';
                            }));
                            echo $rejectedCount;
                            ?>
                        </h3>
                        <p>반려된 참관인</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
            </div>
            <!-- ./col -->
        </div>
        <!-- /.row -->
        
        <!-- 필터 및 검색 -->
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">참관인 필터</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form method="get" action="" class="mb-0">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="filter_draw">추첨 회차</label>
                                <select class="form-control" id="filter_draw" name="draw_id">
                                    <option value="">전체 회차</option>
                                    <?php foreach ($currentDraws as $draw): ?>
                                        <option value="<?php echo $draw['id']; ?>"><?php echo htmlspecialchars($draw['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="filter_status">상태</label>
                                <select class="form-control" id="filter_status" name="status">
                                    <option value="">전체 상태</option>
                                    <option value="pending">대기중</option>
                                    <option value="approved">승인됨</option>
                                    <option value="rejected">반려됨</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="filter_organization">소속</label>
                                <select class="form-control" id="filter_organization" name="organization">
                                    <option value="">전체 소속</option>
                                    <option value="시민단체">시민단체</option>
                                    <option value="로또 애호가 협회">로또 애호가 협회</option>
                                    <option value="방송심의위원회">방송심의위원회</option>
                                    <option value="회계법인">회계법인</option>
                                    <option value="법무법인">법무법인</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="search">검색</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="search" name="search" placeholder="이름, 직책, 연락처 검색">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i> 검색
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- 참관인 목록 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">추첨 참관인 목록</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#modal-add-observer">
                        <i class="fas fa-plus"></i> 참관인 추가
                    </button>
                </div>
            </div>
            <!-- /.card-header -->
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th style="width: 50px">ID</th>
                            <th>추첨 회차</th>
                            <th>참관인명</th>
                            <th>소속</th>
                            <th>직책</th>
                            <th>연락처</th>
                            <th>이메일</th>
                            <th>신청일</th>
                            <th>상태</th>
                            <th style="width: 120px">관리</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($observers as $observer): ?>
                        <tr>
                            <td><?php echo $observer['id']; ?></td>
                            <td><?php echo htmlspecialchars($observer['draw_name']); ?></td>
                            <td><?php echo htmlspecialchars($observer['name']); ?></td>
                            <td><?php echo htmlspecialchars($observer['organization']); ?></td>
                            <td><?php echo htmlspecialchars($observer['position']); ?></td>
                            <td><?php echo htmlspecialchars($observer['contact_phone']); ?></td>
                            <td><?php echo htmlspecialchars($observer['contact_email']); ?></td>
                            <td><?php echo $observer['registration_date']; ?></td>
                            <td>
                                <?php
                                $statusBadge = '';
                                switch ($observer['status']) {
                                    case 'pending':
                                        $statusBadge = '<span class="badge bg-warning">대기중</span>';
                                        break;
                                    case 'approved':
                                        $statusBadge = '<span class="badge bg-success">승인됨</span>';
                                        break;
                                    case 'rejected':
                                        $statusBadge = '<span class="badge bg-danger">반려됨</span>';
                                        break;
                                    default:
                                        $statusBadge = '<span class="badge bg-secondary">알 수 없음</span>';
                                }
                                echo $statusBadge;
                                ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-info" 
                                        data-toggle="modal" 
                                        data-target="#modal-view-observer" 
                                        data-id="<?php echo $observer['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($observer['name']); ?>"
                                        data-organization="<?php echo htmlspecialchars($observer['organization']); ?>"
                                        data-position="<?php echo htmlspecialchars($observer['position']); ?>"
                                        data-phone="<?php echo htmlspecialchars($observer['contact_phone']); ?>"
                                        data-email="<?php echo htmlspecialchars($observer['contact_email']); ?>"
                                        data-registration="<?php echo $observer['registration_date']; ?>"
                                        data-status="<?php echo $observer['status']; ?>"
                                        data-approval="<?php echo $observer['approval_date']; ?>"
                                        data-remarks="<?php echo htmlspecialchars($observer['remarks']); ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-warning" 
                                        data-toggle="modal" 
                                        data-target="#modal-change-status" 
                                        data-id="<?php echo $observer['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($observer['name']); ?>"
                                        data-status="<?php echo $observer['status']; ?>">
                                        <i class="fas fa-exchange-alt"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <!-- /.card-body -->
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
        <!-- /.card -->
        
        <!-- 회차별 참관인 현황 -->
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title">회차별 참관인 현황</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="chart">
                    <canvas id="observerChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                </div>
            </div>
            <!-- /.card-body -->
        </div>
        <!-- /.card -->
    </div><!-- /.container-fluid -->
</section>
<!-- /.content -->

<!-- 참관인 추가 모달 -->
<div class="modal fade" id="modal-add-observer">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">추첨 참관인 추가</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="draw_id">추첨 회차</label>
                        <select class="form-control" id="draw_id" name="draw_id" required>
                            <?php foreach ($currentDraws as $draw): ?>
                                <option value="<?php echo $draw['id']; ?>"><?php echo htmlspecialchars($draw['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="observer_name">참관인명</label>
                        <input type="text" class="form-control" id="observer_name" name="observer_name" required>
                    </div>
                    <div class="form-group">
                        <label for="organization">소속</label>
                        <input type="text" class="form-control" id="organization" name="organization" required>
                    </div>
                    <div class="form-group">
                        <label for="position">직책</label>
                        <input type="text" class="form-control" id="position" name="position" required>
                    </div>
                    <div class="form-group">
                        <label for="contact_phone">연락처</label>
                        <input type="tel" class="form-control" id="contact_phone" name="contact_phone" required>
                    </div>
                    <div class="form-group">
                        <label for="contact_email">이메일</label>
                        <input type="email" class="form-control" id="contact_email" name="contact_email">
                    </div>
                    <div class="form-group">
                        <label for="remarks">비고</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">닫기</button>
                    <button type="submit" name="add_observer" class="btn btn-primary">참관인 추가</button>
                </div>
            </form>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
<!-- /.modal -->

<!-- 참관인 상세 보기 모달 -->
<div class="modal fade" id="modal-view-observer">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">참관인 상세 정보</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <dl class="row">
                    <dt class="col-sm-4">참관인명:</dt>
                    <dd class="col-sm-8" id="view-name"></dd>
                    
                    <dt class="col-sm-4">소속:</dt>
                    <dd class="col-sm-8" id="view-organization"></dd>
                    
                    <dt class="col-sm-4">직책:</dt>
                    <dd class="col-sm-8" id="view-position"></dd>
                    
                    <dt class="col-sm-4">연락처:</dt>
                    <dd class="col-sm-8" id="view-phone"></dd>
                    
                    <dt class="col-sm-4">이메일:</dt>
                    <dd class="col-sm-8" id="view-email"></dd>
                    
                    <dt class="col-sm-4">신청일:</dt>
                    <dd class="col-sm-8" id="view-registration"></dd>
                    
                    <dt class="col-sm-4">상태:</dt>
                    <dd class="col-sm-8" id="view-status"></dd>
                    
                    <dt class="col-sm-4">승인일:</dt>
                    <dd class="col-sm-8" id="view-approval"></dd>
                    
                    <dt class="col-sm-4">비고:</dt>
                    <dd class="col-sm-8" id="view-remarks"></dd>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">닫기</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
<!-- /.modal -->

<!-- 상태 변경 모달 -->
<div class="modal fade" id="modal-change-status">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">참관인 상태 변경</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <input type="hidden" id="status-observer-id" name="observer_id">
                    <p>다음 참관인의 상태를 변경합니다: <strong id="status-observer-name"></strong></p>
                    <div class="form-group">
                        <label for="status">새 상태</label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="pending">대기중</option>
                            <option value="approved">승인</option>
                            <option value="rejected">반려</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status-remarks">비고</label>
                        <textarea class="form-control" id="status-remarks" name="remarks" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">취소</button>
                    <button type="submit" name="update_status" class="btn btn-primary">상태 변경</button>
                </div>
            </form>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
<!-- /.modal -->

<!-- 추가 스크립트 -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 참관인 상세 보기 모달
    $('#modal-view-observer').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var name = button.data('name');
        var organization = button.data('organization');
        var position = button.data('position');
        var phone = button.data('phone');
        var email = button.data('email');
        var registration = button.data('registration');
        var status = button.data('status');
        var approval = button.data('approval');
        var remarks = button.data('remarks');
        
        var statusText = '';
        switch (status) {
            case 'pending': statusText = '<span class="badge bg-warning">대기중</span>'; break;
            case 'approved': statusText = '<span class="badge bg-success">승인됨</span>'; break;
            case 'rejected': statusText = '<span class="badge bg-danger">반려됨</span>'; break;
            default: statusText = '<span class="badge bg-secondary">알 수 없음</span>';
        }
        
        var modal = $(this);
        modal.find('#view-name').text(name);
        modal.find('#view-organization').text(organization);
        modal.find('#view-position').text(position);
        modal.find('#view-phone').text(phone);
        modal.find('#view-email').text(email);
        modal.find('#view-registration').text(registration);
        modal.find('#view-status').html(statusText);
        modal.find('#view-approval').text(approval ? approval : '-');
        modal.find('#view-remarks').text(remarks);
    });
    
    // 상태 변경 모달
    $('#modal-change-status').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var id = button.data('id');
        var name = button.data('name');
        var status = button.data('status');
        
        var modal = $(this);
        modal.find('#status-observer-id').val(id);
        modal.find('#status-observer-name').text(name);
        modal.find('#status').val(status);
    });
    
    // 회차별 참관인 현황 차트
    var ctx = document.getElementById('observerChart').getContext('2d');
    var myChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['제125회', '제126회', '제127회'],
            datasets: [
                {
                    label: '승인됨',
                    backgroundColor: '#28a745',
                    data: [2, 1, 1]
                },
                {
                    label: '대기중',
                    backgroundColor: '#ffc107',
                    data: [0, 1, 1]
                },
                {
                    label: '반려됨',
                    backgroundColor: '#dc3545',
                    data: [1, 1, 0]
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                xAxes: [{
                    stacked: true,
                }],
                yAxes: [{
                    stacked: true,
                    ticks: {
                        beginAtZero: true
                    }
                }]
            }
        }
    });
});
</script>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
