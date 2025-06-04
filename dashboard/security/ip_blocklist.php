<?php
// 공통 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 세션 체크 및 권한 검증
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: /pages/access-denied.php');
    exit;
}

// 페이지 변수 설정
$pageTitle = "IP 차단 목록 관리";
$currentSection = "security";
$currentPage = basename($_SERVER['PHP_SELF']);

// 데이터베이스 연결
$db = getDBConnection();

// IP 차단 테이블이 없으면 생성
$createTableSql = "CREATE TABLE IF NOT EXISTS `ip_blocklist` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `ip_address` varchar(45) NOT NULL COMMENT 'IP 주소',
    `reason` text DEFAULT NULL COMMENT '차단 이유',
    `blocked_by` int(11) DEFAULT NULL COMMENT '차단한 사용자 ID',
    `source_log_id` int(11) DEFAULT NULL COMMENT '관련 로그 ID',
    `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '활성 상태',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정 시간',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_ip_address` (`ip_address`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='IP 차단 목록';";

try {
    $db->exec($createTableSql);
} catch (PDOException $e) {
    error_log("IP 차단 테이블 생성 오류: " . $e->getMessage());
}

// 필터링 설정
$status = isset($_GET['status']) ? $_GET['status'] : 'active';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// 쿼리 빌드
$sql = "SELECT b.*, u.username as blocked_by_username
        FROM ip_blocklist b
        LEFT JOIN users u ON b.blocked_by = u.id
        WHERE 1=1";

// 필터 조건 추가
if ($status === 'active') {
    $sql .= " AND b.is_active = 1";
} else if ($status === 'inactive') {
    $sql .= " AND b.is_active = 0";
}

if (!empty($search)) {
    $sql .= " AND (b.ip_address LIKE :search OR b.reason LIKE :search)";
}

// 정렬
$sql .= " ORDER BY b.created_at DESC";

// 페이지네이션
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$countSql = str_replace("b.*, u.username as blocked_by_username", "COUNT(*) as total", $sql);
$countSql = preg_replace('/ORDER BY.*$/i', '', $countSql);

$stmt = $db->prepare($countSql);

// 파라미터 바인딩
if (!empty($search)) {
    $searchParam = '%' . $search . '%';
    $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
}

$stmt->execute();
$totalRows = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalRows / $perPage);

// 데이터 조회
$sql .= " LIMIT :offset, :per_page";
$stmt = $db->prepare($sql);

// 파라미터 바인딩
if (!empty($search)) {
    $searchParam = '%' . $search . '%';
    $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
}

$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':per_page', $perPage, PDO::PARAM_INT);

$stmt->execute();
$ipList = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                    <li class="breadcrumb-item">보안 관리</li>
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
        <!-- 필터 카드 -->
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">IP 차단 목록 필터링</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form id="filterForm" method="GET" action="">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>상태</label>
                                <select class="form-control" name="status">
                                    <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>활성</option>
                                    <option value="inactive" <?php echo $status == 'inactive' ? 'selected' : ''; ?>>비활성</option>
                                    <option value="all" <?php echo $status == 'all' ? 'selected' : ''; ?>>전체</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>검색</label>
                                <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="IP 주소 또는 차단 이유">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-search"></i> 검색
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <!-- /필터 카드 -->

        <!-- 작업 버튼 -->
        <div class="row mb-3">
            <div class="col-md-12">
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addIpModal">
                    <i class="fas fa-plus"></i> 새 IP 차단 추가
                </button>
                <a href="<?php echo SERVER_URL; ?>/dashboard/security/external_monitoring.php" class="btn btn-info">
                    <i class="fas fa-chart-bar"></i> 외부 접속 감시
                </a>
                <a href="<?php echo SERVER_URL; ?>/dashboard/security/external_monitoring_stats.php" class="btn btn-success">
                    <i class="fas fa-chart-line"></i> 접속 통계
                </a>
            </div>
        </div>
        <!-- /작업 버튼 -->

        <!-- IP 차단 목록 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">IP 차단 목록</h3>
                <div class="card-tools">
                    <span class="badge badge-info">총 <?php echo $totalRows; ?>개의 IP</span>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>IP 주소</th>
                            <th>차단 이유</th>
                            <th>차단 사용자</th>
                            <th>상태</th>
                            <th>생성일</th>
                            <th>수정일</th>
                            <th>작업</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ipList)): ?>
                        <tr>
                            <td colspan="8" class="text-center">차단된 IP가 없습니다.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($ipList as $ip): ?>
                            <tr>
                                <td><?php echo $ip['id']; ?></td>
                                <td><?php echo htmlspecialchars($ip['ip_address']); ?></td>
                                <td><?php echo htmlspecialchars($ip['reason']); ?></td>
                                <td><?php echo htmlspecialchars($ip['blocked_by_username'] ?? '시스템'); ?></td>
                                <td>
                                    <?php if ($ip['is_active'] == 1): ?>
                                    <span class="badge badge-danger">차단됨</span>
                                    <?php else: ?>
                                    <span class="badge badge-success">차단 해제됨</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('Y-m-d H:i:s', strtotime($ip['created_at'])); ?></td>
                                <td><?php echo date('Y-m-d H:i:s', strtotime($ip['updated_at'])); ?></td>
                                <td>
                                    <?php if ($ip['is_active'] == 1): ?>
                                    <button type="button" class="btn btn-xs btn-success toggle-status" data-id="<?php echo $ip['id']; ?>" data-status="0">
                                        <i class="fas fa-check"></i> 차단 해제
                                    </button>
                                    <?php else: ?>
                                    <button type="button" class="btn btn-xs btn-danger toggle-status" data-id="<?php echo $ip['id']; ?>" data-status="1">
                                        <i class="fas fa-ban"></i> 차단
                                    </button>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-xs btn-info view-details" data-toggle="modal" data-target="#viewDetailsModal" data-id="<?php echo $ip['id']; ?>">
                                        <i class="fas fa-eye"></i> 상세
                                    </button>
                                    <button type="button" class="btn btn-xs btn-warning edit-ip" data-toggle="modal" data-target="#editIpModal" data-id="<?php echo $ip['id']; ?>" data-ip="<?php echo htmlspecialchars($ip['ip_address']); ?>" data-reason="<?php echo htmlspecialchars($ip['reason']); ?>">
                                        <i class="fas fa-edit"></i> 수정
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer clearfix">
                <ul class="pagination pagination-sm m-0 float-right">
                    <?php if ($page > 1): ?>
                    <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">&laquo;</a></li>
                    <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">&lsaquo;</a></li>
                    <?php endif; ?>
                    
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                    <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">&rsaquo;</a></li>
                    <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $totalPages])); ?>">&raquo;</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <!-- /IP 차단 목록 -->
    </div>
</section>
<!-- /.content -->

<!-- 새 IP 차단 추가 모달 -->
<div class="modal fade" id="addIpModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">새 IP 차단 추가</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="addIpForm">
                    <div class="form-group">
                        <label for="newIpAddress">IP 주소</label>
                        <input type="text" class="form-control" id="newIpAddress" name="ip_address" placeholder="예: 192.168.1.1">
                    </div>
                    <div class="form-group">
                        <label for="newReason">차단 이유</label>
                        <textarea class="form-control" id="newReason" name="reason" rows="3" placeholder="차단 이유를 입력하세요."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="confirmAddIp">추가</button>
            </div>
        </div>
    </div>
</div>
<!-- /새 IP 차단 추가 모달 -->

<!-- IP 차단 상세 정보 모달 -->
<div class="modal fade" id="viewDetailsModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">IP 차단 상세 정보</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="ipDetailsContent">
                    <p>로딩 중...</p>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">닫기</button>
            </div>
        </div>
    </div>
</div>
<!-- /IP 차단 상세 정보 모달 -->

<!-- IP 차단 수정 모달 -->
<div class="modal fade" id="editIpModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">IP 차단 정보 수정</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editIpForm">
                    <input type="hidden" id="editIpId" name="id">
                    <div class="form-group">
                        <label for="editIpAddress">IP 주소</label>
                        <input type="text" class="form-control" id="editIpAddress" name="ip_address" readonly>
                    </div>
                    <div class="form-group">
                        <label for="editReason">차단 이유</label>
                        <textarea class="form-control" id="editReason" name="reason" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="confirmEditIp">저장</button>
            </div>
        </div>
    </div>
</div>
<!-- /IP 차단 수정 모달 -->

<!-- 상태 변경 확인 모달 -->
<div class="modal fade" id="toggleStatusModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="toggleStatusTitle">IP 차단 상태 변경</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="toggleStatusMessage">IP 주소의 차단 상태를 변경하시겠습니까?</p>
                <input type="hidden" id="toggleStatusId">
                <input type="hidden" id="toggleStatusValue">
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="confirmToggleStatus">확인</button>
            </div>
        </div>
    </div>
</div>
<!-- /상태 변경 확인 모달 -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 새 IP 차단 추가
    var confirmAddIpButton = document.getElementById('confirmAddIp');
    var newIpAddressInput = document.getElementById('newIpAddress');
    var newReasonInput = document.getElementById('newReason');

    confirmAddIpButton.addEventListener('click', function() {
        var ipAddress = newIpAddressInput.value.trim();
        var reason = newReasonInput.value.trim();

        if (!ipAddress) {
            alert('IP 주소를 입력해주세요.');
            return;
        }

        if (!reason) {
            alert('차단 이유를 입력해주세요.');
            return;
        }

        // IP 형식 검증
        var ipRegex = /^((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
        if (!ipRegex.test(ipAddress)) {
            alert('올바른 IPv4 주소 형식이 아닙니다.');
            return;
        }

        // IP 차단 API 호출
        fetch('/api/ip_blocklist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'block_ip',
                ip_address: ipAddress,
                reason: reason
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('IP 주소가 성공적으로 차단되었습니다.');
                location.reload();
            } else {
                alert('오류: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('IP 차단 중 오류가 발생했습니다.');
        });

        $('#addIpModal').modal('hide');
    });

    // IP 차단 상세 정보 조회
    var viewDetailsButtons = document.querySelectorAll('.view-details');
    var ipDetailsContent = document.getElementById('ipDetailsContent');

    viewDetailsButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            
            // IP 차단 상세 정보 Ajax 요청
            ipDetailsContent.innerHTML = '<p class="text-center"><i class="fas fa-spinner fa-spin"></i> 로딩 중...</p>';
            
            // 여기서는 IP 차단 상세 정보를 가져오는 API를 호출해야 합니다.
            // 예시로 간단한 내용을 표시하겠습니다.
            <?php 
            // 예시 데이터를 생성하여 JavaScript 변수로 전달
            $ipListJson = json_encode($ipList);
            echo "var ipList = " . $ipListJson . ";\n";
            ?>
            
            // ID로 IP 정보 찾기
            var ipInfo = ipList.find(function(ip) {
                return ip.id == id;
            });
            
            if (ipInfo) {
                // 상세 정보 표시
                var html = `
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <tr>
                                <th style="width: 30%">ID</th>
                                <td>${ipInfo.id}</td>
                            </tr>
                            <tr>
                                <th>IP 주소</th>
                                <td>${ipInfo.ip_address}</td>
                            </tr>
                            <tr>
                                <th>차단 이유</th>
                                <td>${ipInfo.reason || '-'}</td>
                            </tr>
                            <tr>
                                <th>차단 사용자</th>
                                <td>${ipInfo.blocked_by_username || '시스템'}</td>
                            </tr>
                            <tr>
                                <th>상태</th>
                                <td>${ipInfo.is_active == 1 ? '<span class="badge badge-danger">차단됨</span>' : '<span class="badge badge-success">차단 해제됨</span>'}</td>
                            </tr>
                            <tr>
                                <th>생성일</th>
                                <td>${new Date(ipInfo.created_at).toLocaleString()}</td>
                            </tr>
                            <tr>
                                <th>수정일</th>
                                <td>${new Date(ipInfo.updated_at).toLocaleString()}</td>
                            </tr>
                        </table>
                    </div>
                    
                    <h5 class="mt-4">최근 관련 활동</h5>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 이 IP 주소에 대한 최근 활동을 확인하려면 <a href="/dashboard/security/external_monitoring.php?ip_address=${encodeURIComponent(ipInfo.ip_address)}" class="alert-link">외부 접속 감시</a> 페이지를 확인하세요.
                    </div>
                `;
                
                ipDetailsContent.innerHTML = html;
            } else {
                ipDetailsContent.innerHTML = '<div class="alert alert-danger">IP 차단 정보를 찾을 수 없습니다.</div>';
            }
        });
    });

    // IP 차단 수정
    var editIpButtons = document.querySelectorAll('.edit-ip');
    var editIpIdInput = document.getElementById('editIpId');
    var editIpAddressInput = document.getElementById('editIpAddress');
    var editReasonInput = document.getElementById('editReason');
    var confirmEditIpButton = document.getElementById('confirmEditIp');

    editIpButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            var ip = this.getAttribute('data-ip');
            var reason = this.getAttribute('data-reason');

            editIpIdInput.value = id;
            editIpAddressInput.value = ip;
            editReasonInput.value = reason;
        });
    });

    confirmEditIpButton.addEventListener('click', function() {
        var id = editIpIdInput.value;
        var reason = editReasonInput.value.trim();

        if (!reason) {
            alert('차단 이유를 입력해주세요.');
            return;
        }

        // IP 차단 정보 수정 API 호출
        fetch('/api/ip_blocklist.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id: id,
                reason: reason
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('IP 차단 정보가 성공적으로 수정되었습니다.');
                location.reload();
            } else {
                alert('오류: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('IP 차단 정보 수정 중 오류가 발생했습니다.');
        });

        $('#editIpModal').modal('hide');
    });

    // 상태 변경
    var toggleStatusButtons = document.querySelectorAll('.toggle-status');
    var toggleStatusIdInput = document.getElementById('toggleStatusId');
    var toggleStatusValueInput = document.getElementById('toggleStatusValue');
    var toggleStatusTitle = document.getElementById('toggleStatusTitle');
    var toggleStatusMessage = document.getElementById('toggleStatusMessage');
    var confirmToggleStatusButton = document.getElementById('confirmToggleStatus');

    toggleStatusButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            var status = this.getAttribute('data-status');
            var action = status == 1 ? '차단' : '차단 해제';

            toggleStatusIdInput.value = id;
            toggleStatusValueInput.value = status;
            toggleStatusTitle.textContent = `IP ${action} 확인`;
            toggleStatusMessage.textContent = `정말로 이 IP 주소를 ${action}하시겠습니까?`;

            $('#toggleStatusModal').modal('show');
        });
    });

    confirmToggleStatusButton.addEventListener('click', function() {
        var id = toggleStatusIdInput.value;
        var status = toggleStatusValueInput.value;

        // IP 차단 상태 변경 API 호출
        fetch('/api/ip_blocklist.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id: id,
                is_active: status
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('IP 차단 상태가 성공적으로 변경되었습니다.');
                location.reload();
            } else {
                alert('오류: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('IP 차단 상태 변경 중 오류가 발생했습니다.');
        });

        $('#toggleStatusModal').modal('hide');
    });

    // 상태 변경 시 자동 폼 제출
    document.querySelector('select[name="status"]').addEventListener('change', function() {
        document.getElementById('filterForm').submit();
    });
    
    // 콘솔에 로딩 완료 로그
    console.log("[IP 차단 목록 관리] 페이지 로딩 완료");
});
</script>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
