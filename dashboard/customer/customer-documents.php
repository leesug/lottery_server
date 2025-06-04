<?php
/**
 * 고객 문서 목록 페이지
 * 
 * 이 페이지는 특정 고객의 모든 문서를 표시합니다.
 * 문서 유형, 인증 상태 등으로 필터링할 수 있습니다.
 */

// 필요한 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// 페이지 접근 권한 확인
checkPageAccess('customer_management');

// 고객 ID 유효성 검사
$customerId = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;

if ($customerId <= 0) {
    // 유효하지 않은 ID인 경우 고객 목록 페이지로 리다이렉트
    header('Location: customer-list.php');
    exit;
}

// 데이터베이스 연결
$db = getDbConnection();

// 고객 정보 조회
$sql = "SELECT id, customer_code, first_name, last_name, email, phone FROM customers WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param('i', $customerId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // 고객 정보가 없는 경우 고객 목록 페이지로 리다이렉트
    $stmt->close();
    header('Location: customer-list.php');
    exit;
}

$customer = $result->fetch_assoc();
$stmt->close();

// 페이지네이션 설정
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = ($page - 1) * $limit;

// 검색 및 필터링 파라미터
$documentType = isset($_GET['document_type']) ? sanitizeInput($_GET['document_type']) : '';
$verificationStatus = isset($_GET['verification_status']) ? sanitizeInput($_GET['verification_status']) : '';
$sortBy = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'uploaded_date';
$sortOrder = isset($_GET['order']) ? sanitizeInput($_GET['order']) : 'DESC';

// 데이터 조회를 위한 WHERE 절 구성
$whereClause = ["customer_id = ?"];
$params = [$customerId];
$paramTypes = 'i';

if (!empty($documentType)) {
    $whereClause[] = "document_type = ?";
    $params[] = $documentType;
    $paramTypes .= 's';
}

if (!empty($verificationStatus)) {
    $whereClause[] = "verification_status = ?";
    $params[] = $verificationStatus;
    $paramTypes .= 's';
}

// WHERE 절 완성
$whereStr = implode(' AND ', $whereClause);

// 정렬 설정
$orderByClause = "ORDER BY {$sortBy} {$sortOrder}";

// 전체 레코드 수 조회
$countSql = "SELECT COUNT(*) as total FROM customer_documents WHERE {$whereStr}";
$countStmt = $db->prepare($countSql);
$countStmt->bind_param($paramTypes, ...$params);
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit);
$countStmt->close();

// 문서 목록 조회
$sql = "SELECT id, document_type, document_number, document_path, uploaded_date, 
               verification_status, verification_date, verified_by, rejection_reason
        FROM customer_documents 
        WHERE {$whereStr} 
        {$orderByClause} 
        LIMIT {$offset}, {$limit}";

$stmt = $db->prepare($sql);
$stmt->bind_param($paramTypes, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$documents = [];

while ($row = $result->fetch_assoc()) {
    $documents[] = $row;
}

$stmt->close();

// 문서 유형별 통계 조회
$statsSql = "SELECT 
             document_type,
             COUNT(*) as count,
             SUM(CASE WHEN verification_status = 'verified' THEN 1 ELSE 0 END) as verified,
             SUM(CASE WHEN verification_status = 'pending' THEN 1 ELSE 0 END) as pending,
             SUM(CASE WHEN verification_status = 'rejected' THEN 1 ELSE 0 END) as rejected
             FROM customer_documents 
             WHERE customer_id = ?
             GROUP BY document_type";

$statsStmt = $db->prepare($statsSql);
$statsStmt->bind_param('i', $customerId);
$statsStmt->execute();
$statsResult = $statsStmt->get_result();
$stats = [];

while ($row = $statsResult->fetch_assoc()) {
    $stats[$row['document_type']] = $row;
}

$statsStmt->close();

// 페이지 제목 및 기타 메타 정보
$pageTitle = "고객 문서: " . $customer['first_name'] . ' ' . $customer['last_name'];
$pageDescription = "고객 코드: " . $customer['customer_code'] . "의 문서 목록입니다.";
$activeMenu = "customer";
$activeSubMenu = "customer-list";

// 헤더 포함
include '../../templates/header.php';
?>

<div class="content-wrapper">
    <!-- 콘텐츠 헤더 -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><?php echo $pageTitle; ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="../../dashboard/">대시보드</a></li>
                        <li class="breadcrumb-item">고객 관리</li>
                        <li class="breadcrumb-item"><a href="customer-list.php">고객 목록</a></li>
                        <li class="breadcrumb-item"><a href="customer-details.php?id=<?php echo $customerId; ?>">고객 세부 정보</a></li>
                        <li class="breadcrumb-item active">문서</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- 메인 콘텐츠 -->
    <section class="content">
        <div class="container-fluid">
            <!-- 버튼 행 -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <a href="customer-details.php?id=<?php echo $customerId; ?>" class="btn btn-default">
                        <i class="fas fa-arrow-left"></i> 고객 세부 정보로 돌아가기
                    </a>
                    <a href="customer-list.php" class="btn btn-default">
                        <i class="fas fa-list"></i> 고객 목록
                    </a>
                    <a href="document-upload.php?customer_id=<?php echo $customerId; ?>" class="btn btn-success">
                        <i class="fas fa-upload"></i> 새 문서 업로드
                    </a>
                </div>
            </div>

            <!-- 고객 정보 요약 -->
            <div class="row">
                <div class="col-md-12">
                    <div class="info-box">
                        <span class="info-box-icon bg-info"><i class="fas fa-user"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">고객 정보</span>
                            <span class="info-box-number"><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></span>
                            <span class="info-box-text">고객 코드: <?php echo htmlspecialchars($customer['customer_code']); ?></span>
                            <span class="info-box-text">이메일: <?php echo htmlspecialchars($customer['email']); ?></span>
                            <span class="info-box-text">전화번호: <?php echo htmlspecialchars($customer['phone']); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 문서 통계 -->
            <div class="row">
                <?php foreach (['id_proof', 'address_proof', 'bank_details', 'other'] as $docType) : ?>
                    <?php 
                    $typeInfo = isset($stats[$docType]) ? $stats[$docType] : ['count' => 0, 'verified' => 0, 'pending' => 0, 'rejected' => 0];
                    $typeName = '';
                    $typeIcon = '';
                    
                    switch ($docType) {
                        case 'id_proof':
                            $typeName = '신분증';
                            $typeIcon = 'fas fa-id-card';
                            break;
                        case 'address_proof':
                            $typeName = '주소 증명';
                            $typeIcon = 'fas fa-home';
                            break;
                        case 'bank_details':
                            $typeName = '은행 정보';
                            $typeIcon = 'fas fa-university';
                            break;
                        case 'other':
                            $typeName = '기타';
                            $typeIcon = 'fas fa-file-alt';
                            break;
                    }
                    ?>
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-warning"><i class="<?php echo $typeIcon; ?>"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text"><?php echo $typeName; ?></span>
                                <span class="info-box-number"><?php echo $typeInfo['count']; ?> 문서</span>
                                <span class="info-box-text">
                                    <span class="badge badge-success"><?php echo $typeInfo['verified']; ?> 인증</span>
                                    <span class="badge badge-warning"><?php echo $typeInfo['pending']; ?> 대기</span>
                                    <span class="badge badge-danger"><?php echo $typeInfo['rejected']; ?> 거부</span>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- 검색 및 필터 -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">문서 검색 및 필터</h3>
                </div>
                <div class="card-body">
                    <form action="" method="GET" class="form-horizontal">
                        <input type="hidden" name="customer_id" value="<?php echo $customerId; ?>">
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="document_type">문서 유형:</label>
                                    <select class="form-control" id="document_type" name="document_type">
                                        <option value="">모든 유형</option>
                                        <option value="id_proof" <?php echo ($documentType == 'id_proof') ? 'selected' : ''; ?>>신분증</option>
                                        <option value="address_proof" <?php echo ($documentType == 'address_proof') ? 'selected' : ''; ?>>주소 증명</option>
                                        <option value="bank_details" <?php echo ($documentType == 'bank_details') ? 'selected' : ''; ?>>은행 정보</option>
                                        <option value="other" <?php echo ($documentType == 'other') ? 'selected' : ''; ?>>기타</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="verification_status">인증 상태:</label>
                                    <select class="form-control" id="verification_status" name="verification_status">
                                        <option value="">모든 상태</option>
                                        <option value="pending" <?php echo ($verificationStatus == 'pending') ? 'selected' : ''; ?>>대기중</option>
                                        <option value="verified" <?php echo ($verificationStatus == 'verified') ? 'selected' : ''; ?>>인증됨</option>
                                        <option value="rejected" <?php echo ($verificationStatus == 'rejected') ? 'selected' : ''; ?>>거부됨</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="sort">정렬 기준:</label>
                                    <select class="form-control" id="sort" name="sort">
                                        <option value="uploaded_date" <?php echo ($sortBy == 'uploaded_date') ? 'selected' : ''; ?>>업로드 날짜</option>
                                        <option value="document_type" <?php echo ($sortBy == 'document_type') ? 'selected' : ''; ?>>문서 유형</option>
                                        <option value="verification_status" <?php echo ($sortBy == 'verification_status') ? 'selected' : ''; ?>>인증 상태</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="order">정렬 순서:</label>
                                    <select class="form-control" id="order" name="order">
                                        <option value="DESC" <?php echo ($sortOrder == 'DESC') ? 'selected' : ''; ?>>내림차순</option>
                                        <option value="ASC" <?php echo ($sortOrder == 'ASC') ? 'selected' : ''; ?>>오름차순</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="limit">표시 개수:</label>
                                    <select class="form-control" id="limit" name="limit">
                                        <option value="10" <?php echo ($limit == 10) ? 'selected' : ''; ?>>10</option>
                                        <option value="20" <?php echo ($limit == 20) ? 'selected' : ''; ?>>20</option>
                                        <option value="50" <?php echo ($limit == 50) ? 'selected' : ''; ?>>50</option>
                                        <option value="100" <?php echo ($limit == 100) ? 'selected' : ''; ?>>100</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i> 검색
                                        </button>
                                        <a href="customer-documents.php?customer_id=<?php echo $customerId; ?>" class="btn btn-default">
                                            <i class="fas fa-sync-alt"></i> 초기화
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 문서 목록 테이블 -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">문서 목록 (총 <?php echo $totalRecords; ?>건)</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th style="width: 5%">ID</th>
                                    <th style="width: 15%">문서 유형</th>
                                    <th style="width: 15%">문서 번호</th>
                                    <th style="width: 15%">업로드 날짜</th>
                                    <th style="width: 15%">인증 상태</th>
                                    <th style="width: 15%">인증 날짜</th>
                                    <th style="width: 20%">작업</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($documents)) : ?>
                                    <tr>
                                        <td colspan="7" class="text-center">문서가 없습니다.</td>
                                    </tr>
                                <?php else : ?>
                                    <?php foreach ($documents as $document) : ?>
                                        <tr>
                                            <td><?php echo $document['id']; ?></td>
                                            <td>
                                                <?php
                                                $typeText = '';
                                                $typeIcon = '';
                                                
                                                switch ($document['document_type']) {
                                                    case 'id_proof':
                                                        $typeText = '신분증';
                                                        $typeIcon = 'fas fa-id-card';
                                                        break;
                                                    case 'address_proof':
                                                        $typeText = '주소 증명';
                                                        $typeIcon = 'fas fa-home';
                                                        break;
                                                    case 'bank_details':
                                                        $typeText = '은행 정보';
                                                        $typeIcon = 'fas fa-university';
                                                        break;
                                                    default:
                                                        $typeText = '기타';
                                                        $typeIcon = 'fas fa-file-alt';
                                                }
                                                ?>
                                                <i class="<?php echo $typeIcon; ?>"></i> <?php echo $typeText; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($document['document_number']); ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($document['uploaded_date'])); ?></td>
                                            <td>
                                                <?php
                                                $statusClass = '';
                                                $statusText = '';
                                                
                                                switch ($document['verification_status']) {
                                                    case 'pending':
                                                        $statusClass = 'warning';
                                                        $statusText = '대기중';
                                                        break;
                                                    case 'verified':
                                                        $statusClass = 'success';
                                                        $statusText = '인증됨';
                                                        break;
                                                    case 'rejected':
                                                        $statusClass = 'danger';
                                                        $statusText = '거부됨';
                                                        break;
                                                    default:
                                                        $statusClass = 'secondary';
                                                        $statusText = '알 수 없음';
                                                }
                                                ?>
                                                <span class="badge badge-<?php echo $statusClass; ?>">
                                                    <?php echo $statusText; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($document['verification_date']) : ?>
                                                    <?php echo date('Y-m-d', strtotime($document['verification_date'])); ?>
                                                <?php else : ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="../../<?php echo $document['document_path']; ?>" target="_blank" 
                                                       class="btn btn-sm btn-info" title="문서 보기">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="document-verify.php?id=<?php echo $document['id']; ?>&customer_id=<?php echo $customerId; ?>" 
                                                       class="btn btn-sm btn-success" title="인증 관리">
                                                        <i class="fas fa-check-circle"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-danger" title="문서 삭제"
                                                           onclick="confirmDelete(<?php echo $document['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 페이지네이션 -->
                <div class="card-footer clearfix">
                    <ul class="pagination pagination-sm m-0 float-right">
                        <?php if ($page > 1) : ?>
                            <li class="page-item">
                                <a class="page-link" href="?customer_id=<?php echo $customerId; ?>&page=1&limit=<?php echo $limit; ?>&document_type=<?php echo $documentType; ?>&verification_status=<?php echo $verificationStatus; ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $sortOrder; ?>">
                                    &laquo;
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?customer_id=<?php echo $customerId; ?>&page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>&document_type=<?php echo $documentType; ?>&verification_status=<?php echo $verificationStatus; ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $sortOrder; ?>">
                                    &lt;
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);

                        for ($i = $startPage; $i <= $endPage; $i++) :
                        ?>
                            <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="?customer_id=<?php echo $customerId; ?>&page=<?php echo $i; ?>&limit=<?php echo $limit; ?>&document_type=<?php echo $documentType; ?>&verification_status=<?php echo $verificationStatus; ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $sortOrder; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages) : ?>
                            <li class="page-item">
                                <a class="page-link" href="?customer_id=<?php echo $customerId; ?>&page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>&document_type=<?php echo $documentType; ?>&verification_status=<?php echo $verificationStatus; ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $sortOrder; ?>">
                                    &gt;
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?customer_id=<?php echo $customerId; ?>&page=<?php echo $totalPages; ?>&limit=<?php echo $limit; ?>&document_type=<?php echo $documentType; ?>&verification_status=<?php echo $verificationStatus; ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $sortOrder; ?>">
                                    &raquo;
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- 삭제 확인 모달 -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">문서 삭제 확인</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                정말로 이 문서를 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                <form id="deleteForm" method="post" action="document-delete.php">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="document_id" id="documentIdToDelete">
                    <input type="hidden" name="customer_id" value="<?php echo $customerId; ?>">
                    <button type="submit" class="btn btn-danger">삭제</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// 브라우저 콘솔에 디버깅 정보 출력
console.log('고객 문서 목록 페이지 로드됨');
console.log('고객 ID:', <?php echo json_encode($customerId); ?>);
console.log('문서 유형 필터:', <?php echo json_encode($documentType); ?>);
console.log('인증 상태 필터:', <?php echo json_encode($verificationStatus); ?>);

// 삭제 확인 함수
function confirmDelete(documentId) {
    console.log('문서 삭제 확인 요청:', documentId);
    document.getElementById('documentIdToDelete').value = documentId;
    $('#deleteModal').modal('show');
}

// 페이지 로드 시 이벤트 처리
document.addEventListener('DOMContentLoaded', function() {
    // 검색 폼 서브밋 이벤트 처리
    document.querySelector('form').addEventListener('submit', function(e) {
        console.log('검색 폼 제출됨');
    });

    // 정렬 변경 이벤트 처리
    document.getElementById('sort').addEventListener('change', function() {
        console.log('정렬 기준 변경:', this.value);
    });

    document.getElementById('order').addEventListener('change', function() {
        console.log('정렬 순서 변경:', this.value);
    });

    // 표시 개수 변경 이벤트 처리
    document.getElementById('limit').addEventListener('change', function() {
        console.log('표시 개수 변경:', this.value);
        // 표시 개수 변경 시 페이지 리로드
        const url = new URL(window.location.href);
        url.searchParams.set('limit', this.value);
        url.searchParams.set('page', '1');
        window.location.href = url.toString();
    });
});
</script>

<?php
// 푸터 포함
include '../../templates/footer.php';
?>
