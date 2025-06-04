<?php
/**
 * 고객 세부 정보 페이지
 * 
 * 이 페이지는 특정 고객의 세부 정보를 표시합니다.
 * 기본 정보, 주소, 상태 등을 확인할 수 있습니다.
 */

// 필요한 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// 페이지 접근 권한 확인
checkPageAccess('customer_management');

// 고객 ID 유효성 검사
$customerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($customerId <= 0) {
    // 유효하지 않은 ID인 경우 고객 목록 페이지로 리다이렉트
    header('Location: customer-list.php');
    exit;
}

// 데이터베이스 연결
$db = getDbConnection();

// 고객 정보 조회
$sql = "SELECT * FROM customers WHERE id = ?";
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

// 고객 설정 정보 조회
$sql = "SELECT * FROM customer_preferences WHERE customer_id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param('i', $customerId);
$stmt->execute();
$preferencesResult = $stmt->get_result();
$preferences = $preferencesResult->num_rows > 0 ? $preferencesResult->fetch_assoc() : null;
$stmt->close();

// 고객 거래 내역 수 조회
$sql = "SELECT COUNT(*) as total FROM customer_transactions WHERE customer_id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param('i', $customerId);
$stmt->execute();
$transactionsResult = $stmt->get_result();
$transactionsCount = $transactionsResult->fetch_assoc()['total'];
$stmt->close();

// 고객 문서 수 조회
$sql = "SELECT COUNT(*) as total, 
               SUM(CASE WHEN verification_status = 'verified' THEN 1 ELSE 0 END) as verified,
               SUM(CASE WHEN verification_status = 'pending' THEN 1 ELSE 0 END) as pending,
               SUM(CASE WHEN verification_status = 'rejected' THEN 1 ELSE 0 END) as rejected
        FROM customer_documents 
        WHERE customer_id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param('i', $customerId);
$stmt->execute();
$documentsResult = $stmt->get_result();
$documentsStats = $documentsResult->fetch_assoc();
$stmt->close();

// 페이지 제목 및 기타 메타 정보
$pageTitle = "고객 세부 정보: " . $customer['first_name'] . ' ' . $customer['last_name'];
$pageDescription = "고객 ID: " . $customer['customer_code'] . "의 세부 정보입니다.";
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
                        <li class="breadcrumb-item active">고객 세부 정보</li>
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
                    <a href="customer-list.php" class="btn btn-default">
                        <i class="fas fa-arrow-left"></i> 목록으로 돌아가기
                    </a>
                    <a href="customer-edit.php?id=<?php echo $customerId; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> 정보 수정
                    </a>
                    <a href="customer-transactions.php?customer_id=<?php echo $customerId; ?>" class="btn btn-success">
                        <i class="fas fa-money-bill"></i> 거래 내역
                    </a>
                    <a href="customer-documents.php?customer_id=<?php echo $customerId; ?>" class="btn btn-warning">
                        <i class="fas fa-file-alt"></i> 문서
                    </a>
                    <a href="customer-preferences.php?customer_id=<?php echo $customerId; ?>" class="btn btn-info">
                        <i class="fas fa-cog"></i> 설정
                    </a>
                </div>
            </div>

            <div class="row">
                <!-- 고객 기본 정보 -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">기본 정보</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 30%">고객 ID</th>
                                    <td><?php echo $customer['id']; ?></td>
                                </tr>
                                <tr>
                                    <th>고객 코드</th>
                                    <td><?php echo htmlspecialchars($customer['customer_code']); ?></td>
                                </tr>
                                <tr>
                                    <th>이름</th>
                                    <td><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></td>
                                </tr>
                                <tr>
                                    <th>이메일</th>
                                    <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                </tr>
                                <tr>
                                    <th>전화번호</th>
                                    <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                </tr>
                                <tr>
                                    <th>등록일</th>
                                    <td><?php echo date('Y-m-d H:i', strtotime($customer['registration_date'])); ?></td>
                                </tr>
                                <tr>
                                    <th>계정 상태</th>
                                    <td>
                                        <?php
                                        $statusClass = '';
                                        $statusText = '';
                                        
                                        switch ($customer['status']) {
                                            case 'active':
                                                $statusClass = 'success';
                                                $statusText = '활성';
                                                break;
                                            case 'inactive':
                                                $statusClass = 'warning';
                                                $statusText = '비활성';
                                                break;
                                            case 'blocked':
                                                $statusClass = 'danger';
                                                $statusText = '차단됨';
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
                                </tr>
                                <tr>
                                    <th>인증 상태</th>
                                    <td>
                                        <?php
                                        $verificationClass = '';
                                        $verificationText = '';
                                        
                                        switch ($customer['verification_status']) {
                                            case 'verified':
                                                $verificationClass = 'success';
                                                $verificationText = '인증됨';
                                                break;
                                            case 'unverified':
                                                $verificationClass = 'warning';
                                                $verificationText = '미인증';
                                                break;
                                            default:
                                                $verificationClass = 'secondary';
                                                $verificationText = '알 수 없음';
                                        }
                                        ?>
                                        <span class="badge badge-<?php echo $verificationClass; ?>">
                                            <?php echo $verificationText; ?>
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 고객 주소 정보 -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">주소 정보</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 30%">주소</th>
                                    <td><?php echo !empty($customer['address']) ? htmlspecialchars($customer['address']) : '-'; ?></td>
                                </tr>
                                <tr>
                                    <th>도시</th>
                                    <td><?php echo !empty($customer['city']) ? htmlspecialchars($customer['city']) : '-'; ?></td>
                                </tr>
                                <tr>
                                    <th>주/도</th>
                                    <td><?php echo !empty($customer['state']) ? htmlspecialchars($customer['state']) : '-'; ?></td>
                                </tr>
                                <tr>
                                    <th>우편번호</th>
                                    <td><?php echo !empty($customer['postal_code']) ? htmlspecialchars($customer['postal_code']) : '-'; ?></td>
                                </tr>
                                <tr>
                                    <th>국가</th>
                                    <td><?php echo !empty($customer['country']) ? htmlspecialchars($customer['country']) : '-'; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- 고객 설정 정보 -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">설정 정보</h3>
                        </div>
                        <div class="card-body">
                            <?php if ($preferences) : ?>
                                <table class="table table-bordered">
                                    <tr>
                                        <th style="width: 30%">언어</th>
                                        <td><?php echo htmlspecialchars($preferences['language']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>이메일 알림</th>
                                        <td>
                                            <span class="badge badge-<?php echo $preferences['notification_email'] ? 'success' : 'danger'; ?>">
                                                <?php echo $preferences['notification_email'] ? '활성화' : '비활성화'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>SMS 알림</th>
                                        <td>
                                            <span class="badge badge-<?php echo $preferences['notification_sms'] ? 'success' : 'danger'; ?>">
                                                <?php echo $preferences['notification_sms'] ? '활성화' : '비활성화'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>푸시 알림</th>
                                        <td>
                                            <span class="badge badge-<?php echo $preferences['notification_push'] ? 'success' : 'danger'; ?>">
                                                <?php echo $preferences['notification_push'] ? '활성화' : '비활성화'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>마케팅 동의</th>
                                        <td>
                                            <span class="badge badge-<?php echo $preferences['marketing_consent'] ? 'success' : 'danger'; ?>">
                                                <?php echo $preferences['marketing_consent'] ? '동의함' : '동의하지 않음'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            <?php else : ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> 설정 정보가 없습니다.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- 고객 메모 -->
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">메모</h3>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($customer['notes'])) : ?>
                                <div class="callout callout-info">
                                    <?php echo nl2br(htmlspecialchars($customer['notes'])); ?>
                                </div>
                            <?php else : ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> 메모가 없습니다.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- 거래 요약 카드 -->
                <div class="col-md-4">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?php echo $transactionsCount; ?></h3>
                            <p>총 거래 건수</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-money-bill"></i>
                        </div>
                        <a href="customer-transactions.php?customer_id=<?php echo $customerId; ?>" class="small-box-footer">
                            자세히 보기 <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>

                <!-- 문서 요약 카드 -->
                <div class="col-md-4">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?php echo $documentsStats['total']; ?></h3>
                            <p>문서 (인증: <?php echo $documentsStats['verified']; ?>, 대기: <?php echo $documentsStats['pending']; ?>, 거부: <?php echo $documentsStats['rejected']; ?>)</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <a href="customer-documents.php?customer_id=<?php echo $customerId; ?>" class="small-box-footer">
                            자세히 보기 <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>

                <!-- 설정 요약 카드 -->
                <div class="col-md-4">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?php echo $preferences ? $preferences['language'] : 'N/A'; ?></h3>
                            <p>설정</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-cog"></i>
                        </div>
                        <a href="customer-preferences.php?customer_id=<?php echo $customerId; ?>" class="small-box-footer">
                            설정 관리 <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
// 브라우저 콘솔에 디버깅 정보 출력
console.log('고객 세부 정보 페이지 로드됨');
console.log('고객 ID:', <?php echo json_encode($customerId); ?>);
console.log('고객 정보:', <?php echo json_encode($customer); ?>);

// 페이지 로드 시 이벤트 처리
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM 완전히 로드됨');
});
</script>

<?php
// 푸터 포함
include '../../templates/footer.php';
?>
