<?php
/**
 * 추첨 담당자 관리 페이지
 */

// 공통 파일 포함
require_once '../../../includes/config.php';
require_once '../../../includes/db.php';
require_once '../../../includes/functions.php';

// 페이지 변수 설정
$pageTitle = "추첨 담당자 관리";
$currentSection = "broadcaster";
$currentPage = basename($_SERVER['PHP_SELF']);

// 데이터베이스 연결
$db = getDBConnection();

// 추첨 담당자 추가 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_manager'])) {
    try {
        $managerName = htmlspecialchars($_POST['manager_name']);
        $position = htmlspecialchars($_POST['position']);
        $organization = htmlspecialchars($_POST['organization']);
        $contactPhone = htmlspecialchars($_POST['contact_phone']);
        $contactEmail = htmlspecialchars($_POST['contact_email']);
        $roleDescription = htmlspecialchars($_POST['role_description']);
        $isPrimary = isset($_POST['is_primary']) ? 1 : 0;
        $drawId = intval($_POST['draw_id']);
        
        // 주 담당자 설정 시 기존 주 담당자 해제
        if ($isPrimary) {
            $stmt = $db->prepare("
                UPDATE draw_managers 
                SET is_primary = 0 
                WHERE draw_id = ? AND is_primary = 1
            ");
            $stmt->execute([$drawId]);
        }
        
        // 새 담당자 추가
        $stmt = $db->prepare("
            INSERT INTO draw_managers (draw_id, manager_name, position, organization, 
                                      contact_phone, contact_email, role_description, is_primary)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $drawId, $managerName, $position, $organization, 
            $contactPhone, $contactEmail, $roleDescription, $isPrimary
        ]);
        
        $successMessage = "추첨 담당자가 성공적으로 추가되었습니다.";
    } catch (Exception $e) {
        $errorMessage = "담당자 추가 중 오류가 발생했습니다: " . $e->getMessage();
    }
}

// 담당자 삭제 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_manager'])) {
    try {
        $managerId = intval($_POST['manager_id']);
        
        // 담당자 삭제
        $stmt = $db->prepare("
            DELETE FROM draw_managers 
            WHERE id = ?
        ");
        $stmt->execute([$managerId]);
        
        $successMessage = "담당자가 삭제되었습니다.";
    } catch (Exception $e) {
        $errorMessage = "담당자 삭제 중 오류가 발생했습니다: " . $e->getMessage();
    }
}

// 현재 진행 중인 추첨 회차 정보 - 샘플 데이터
$currentDraws = [
    ['id' => 125, 'name' => '제125회 추첨'],
    ['id' => 126, 'name' => '제126회 추첨'],
    ['id' => 127, 'name' => '제127회 추첨'],
];

// 현재 등록된 담당자 목록 - 샘플 데이터
$managers = [
    [
        'id' => 1,
        'draw_id' => 126,
        'draw_name' => '제126회 추첨',
        'name' => '김상혁',
        'position' => '제작 책임자',
        'organization' => 'KBS',
        'contact_phone' => '010-1234-5678',
        'contact_email' => 'kim@example.com',
        'role_description' => '추첨 행사 전체 총괄 및 진행 감독',
        'is_primary' => true
    ],
    [
        'id' => 2,
        'draw_id' => 126,
        'draw_name' => '제126회 추첨',
        'name' => '이민우',
        'position' => '추첨 감독관',
        'organization' => '로또 위원회',
        'contact_phone' => '010-9876-5432',
        'contact_email' => 'lee@example.com',
        'role_description' => '추첨 과정 관리 및 검증, 공정성 확보',
        'is_primary' => false
    ],
    [
        'id' => 3,
        'draw_id' => 126,
        'draw_name' => '제126회 추첨',
        'name' => '박지은',
        'position' => '방송 PD',
        'organization' => 'KBS',
        'contact_phone' => '010-2222-3333',
        'contact_email' => 'park@example.com',
        'role_description' => '방송 연출 및 카메라 감독',
        'is_primary' => false
    ],
    [
        'id' => 4,
        'draw_id' => 126,
        'draw_name' => '제126회 추첨',
        'name' => '최준호',
        'position' => '기술 감독',
        'organization' => 'KBS',
        'contact_phone' => '010-4444-5555',
        'contact_email' => 'choi@example.com',
        'role_description' => '추첨기 관리 및 오작동 대비 기술 지원',
        'is_primary' => false
    ],
    [
        'id' => 5,
        'draw_id' => 127,
        'draw_name' => '제127회 추첨',
        'name' => '정수미',
        'position' => '진행자',
        'organization' => 'KBS',
        'contact_phone' => '010-7777-8888',
        'contact_email' => 'jung@example.com',
        'role_description' => '추첨 방송 메인 진행자',
        'is_primary' => true
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
        
        <!-- 필터 및 검색 -->
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">담당자 필터</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form method="get" action="" class="mb-0">
                    <div class="row">
                        <div class="col-md-4">
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
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="filter_organization">소속</label>
                                <select class="form-control" id="filter_organization" name="organization">
                                    <option value="">전체 소속</option>
                                    <option value="KBS">KBS</option>
                                    <option value="로또 위원회">로또 위원회</option>
                                    <option value="기타">기타</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
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
        
        <!-- 담당자 목록 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">추첨 담당자 목록</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#modal-add-manager">
                        <i class="fas fa-plus"></i> 담당자 추가
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
                            <th>담당자명</th>
                            <th>직책</th>
                            <th>소속</th>
                            <th>연락처</th>
                            <th>이메일</th>
                            <th>주 담당자</th>
                            <th style="width: 120px">관리</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($managers as $manager): ?>
                        <tr>
                            <td><?php echo $manager['id']; ?></td>
                            <td><?php echo htmlspecialchars($manager['draw_name']); ?></td>
                            <td><?php echo htmlspecialchars($manager['name']); ?></td>
                            <td><?php echo htmlspecialchars($manager['position']); ?></td>
                            <td><?php echo htmlspecialchars($manager['organization']); ?></td>
                            <td><?php echo htmlspecialchars($manager['contact_phone']); ?></td>
                            <td><?php echo htmlspecialchars($manager['contact_email']); ?></td>
                            <td>
                                <?php if ($manager['is_primary']): ?>
                                    <span class="badge bg-success">주 담당자</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">부 담당자</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-info" 
                                        data-toggle="modal" 
                                        data-target="#modal-view-manager" 
                                        data-id="<?php echo $manager['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($manager['name']); ?>"
                                        data-position="<?php echo htmlspecialchars($manager['position']); ?>"
                                        data-organization="<?php echo htmlspecialchars($manager['organization']); ?>"
                                        data-phone="<?php echo htmlspecialchars($manager['contact_phone']); ?>"
                                        data-email="<?php echo htmlspecialchars($manager['contact_email']); ?>"
                                        data-role="<?php echo htmlspecialchars($manager['role_description']); ?>"
                                        data-primary="<?php echo $manager['is_primary'] ? '1' : '0'; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" 
                                        data-toggle="modal" 
                                        data-target="#modal-delete-manager" 
                                        data-id="<?php echo $manager['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($manager['name']); ?>">
                                        <i class="fas fa-trash"></i>
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
        
        <!-- 담당자 연락망 -->
        <div class="card card-success card-outline">
            <div class="card-header">
                <h3 class="card-title">주요 담당자 연락망</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php 
                    // 주 담당자들만 필터링
                    $primaryManagers = array_filter($managers, function($manager) {
                        return $manager['is_primary'];
                    });
                    
                    foreach ($primaryManagers as $manager): 
                    ?>
                    <div class="col-md-4">
                        <div class="card card-widget widget-user-2">
                            <div class="widget-user-header bg-primary">
                                <div class="widget-user-image">
                                    <img class="img-circle elevation-2" src="<?php echo SERVER_URL; ?>/assets/img/avatar-default.png" alt="User Avatar">
                                </div>
                                <h3 class="widget-user-username"><?php echo htmlspecialchars($manager['name']); ?></h3>
                                <h5 class="widget-user-desc"><?php echo htmlspecialchars($manager['position']); ?></h5>
                            </div>
                            <div class="card-footer p-0">
                                <ul class="nav flex-column">
                                    <li class="nav-item">
                                        <a href="#" class="nav-link">
                                            소속 <span class="float-right badge bg-info"><?php echo htmlspecialchars($manager['organization']); ?></span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#" class="nav-link">
                                            연락처 <span class="float-right"><?php echo htmlspecialchars($manager['contact_phone']); ?></span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#" class="nav-link">
                                            이메일 <span class="float-right"><?php echo htmlspecialchars($manager['contact_email']); ?></span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#" class="nav-link">
                                            담당 회차 <span class="float-right badge bg-success"><?php echo htmlspecialchars($manager['draw_name']); ?></span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <!-- /.card -->
    </div><!-- /.container-fluid -->
</section>
<!-- /.content -->

<!-- 담당자 추가 모달 -->
<div class="modal fade" id="modal-add-manager">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">추첨 담당자 추가</h4>
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
                        <label for="manager_name">담당자명</label>
                        <input type="text" class="form-control" id="manager_name" name="manager_name" required>
                    </div>
                    <div class="form-group">
                        <label for="position">직책</label>
                        <input type="text" class="form-control" id="position" name="position" required>
                    </div>
                    <div class="form-group">
                        <label for="organization">소속</label>
                        <input type="text" class="form-control" id="organization" name="organization" required>
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
                        <label for="role_description">역할 설명</label>
                        <textarea class="form-control" id="role_description" name="role_description" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input class="custom-control-input" type="checkbox" id="is_primary" name="is_primary">
                            <label for="is_primary" class="custom-control-label">주 담당자로 지정</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">닫기</button>
                    <button type="submit" name="add_manager" class="btn btn-primary">담당자 추가</button>
                </div>
            </form>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
<!-- /.modal -->

<!-- 담당자 상세 보기 모달 -->
<div class="modal fade" id="modal-view-manager">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">담당자 상세 정보</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <dl class="row">
                    <dt class="col-sm-4">담당자명:</dt>
                    <dd class="col-sm-8" id="view-name"></dd>
                    
                    <dt class="col-sm-4">직책:</dt>
                    <dd class="col-sm-8" id="view-position"></dd>
                    
                    <dt class="col-sm-4">소속:</dt>
                    <dd class="col-sm-8" id="view-organization"></dd>
                    
                    <dt class="col-sm-4">연락처:</dt>
                    <dd class="col-sm-8" id="view-phone"></dd>
                    
                    <dt class="col-sm-4">이메일:</dt>
                    <dd class="col-sm-8" id="view-email"></dd>
                    
                    <dt class="col-sm-4">주 담당자:</dt>
                    <dd class="col-sm-8" id="view-primary"></dd>
                    
                    <dt class="col-sm-4">역할 설명:</dt>
                    <dd class="col-sm-8" id="view-role"></dd>
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

<!-- 담당자 삭제 모달 -->
<div class="modal fade" id="modal-delete-manager">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">담당자 삭제</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <input type="hidden" id="delete-manager-id" name="manager_id">
                    <p>다음 담당자를 정말로 삭제하시겠습니까? <strong id="delete-manager-name"></strong></p>
                    <p class="text-danger">이 작업은 되돌릴 수 없습니다.</p>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">취소</button>
                    <button type="submit" name="delete_manager" class="btn btn-danger">삭제</button>
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
    // 담당자 상세 보기 모달
    $('#modal-view-manager').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var name = button.data('name');
        var position = button.data('position');
        var organization = button.data('organization');
        var phone = button.data('phone');
        var email = button.data('email');
        var role = button.data('role');
        var isPrimary = button.data('primary') == '1';
        
        var modal = $(this);
        modal.find('#view-name').text(name);
        modal.find('#view-position').text(position);
        modal.find('#view-organization').text(organization);
        modal.find('#view-phone').text(phone);
        modal.find('#view-email').text(email);
        modal.find('#view-role').text(role);
        
        if (isPrimary) {
            modal.find('#view-primary').html('<span class="badge bg-success">주 담당자</span>');
        } else {
            modal.find('#view-primary').html('<span class="badge bg-secondary">부 담당자</span>');
        }
    });
    
    // 담당자 삭제 모달
    $('#modal-delete-manager').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var id = button.data('id');
        var name = button.data('name');
        
        var modal = $(this);
        modal.find('#delete-manager-id').val(id);
        modal.find('#delete-manager-name').text(name);
    });
});
</script>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
