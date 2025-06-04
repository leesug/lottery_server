<?php
/**
 * Contract Print Page
 * 
 * This page displays a printable version of a contract.
 */

// Include required files
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// Check if user has access to this page
checkPageAccess('store_management');

// Get contract ID from URL parameter
$contractId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($contractId <= 0) {
    // Redirect to store list if no valid ID provided
    header('Location: store-list.php');
    exit;
}

// Initialize variables
$contract = null;
$storeInfo = null;

// Database connection
$db = getDbConnection();

// Get contract information with store details
$stmt = $db->prepare("
    SELECT c.*, 
           s.id as store_id, 
           s.store_name, 
           s.store_code, 
           s.owner_name,
           s.email as store_email,
           s.phone as store_phone,
           s.address, 
           s.city, 
           s.state, 
           s.postal_code,
           s.country,
           s.business_license_number,
           s.tax_id,
           CONCAT(u1.first_name, ' ', u1.last_name) as created_by_name,
           CONCAT(u2.first_name, ' ', u2.last_name) as approved_by_name
    FROM store_contracts c
    JOIN stores s ON c.store_id = s.id
    LEFT JOIN users u1 ON c.created_by = u1.id
    LEFT JOIN users u2 ON c.approved_by = u2.id
    WHERE c.id = ?
");
$stmt->bind_param("i", $contractId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Contract not found, redirect to list
    header('Location: store-list.php');
    exit;
}

$contract = $result->fetch_assoc();
$storeId = $contract['store_id'];
$stmt->close();

// Page title
$pageTitle = "계약서 인쇄: " . htmlspecialchars($contract['contract_number']);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
            background-color: #f9f9f9;
        }
        .print-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .print-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .print-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .print-subtitle {
            font-size: 16px;
            color: #666;
        }
        .print-section {
            margin-bottom: 30px;
        }
        .print-section-title {
            font-size: 18px;
            font-weight: bold;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        .print-row {
            display: flex;
            margin-bottom: 10px;
        }
        .print-label {
            flex: 0 0 200px;
            font-weight: bold;
        }
        .print-value {
            flex: 1;
        }
        .print-special-terms {
            margin-top: 20px;
            border: 1px solid #ddd;
            padding: 15px;
            background-color: #f9f9f9;
        }
        .print-footer {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        .print-signature {
            flex: 0 0 45%;
            border-top: 1px solid #333;
            padding-top: 5px;
            text-align: center;
        }
        .print-actions {
            margin-top: 20px;
            text-align: center;
        }
        .print-button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 4px;
        }
        .print-button:hover {
            background-color: #0056b3;
        }
        .logo {
            max-width: 200px;
            margin-bottom: 15px;
        }
        @media print {
            body {
                background-color: white;
                padding: 0;
            }
            .print-container {
                box-shadow: none;
                max-width: 100%;
                padding: 0;
            }
            .print-actions {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="print-container">
        <div class="print-header">
            <img src="../../assets/img/logo.png" alt="KHUSHI LOTTERY" class="logo">
            <div class="print-title">복권 판매점 계약서</div>
            <div class="print-subtitle">계약번호: <?php echo htmlspecialchars($contract['contract_number']); ?></div>
        </div>
        
        <div class="print-section">
            <div class="print-section-title">계약 당사자</div>
            <div class="print-row">
                <div class="print-label">갑 (회사):</div>
                <div class="print-value">KHUSHI LOTTERY</div>
            </div>
            <div class="print-row">
                <div class="print-label">주소:</div>
                <div class="print-value">123 Lottery Street, Kathmandu, Nepal</div>
            </div>
            <div class="print-row">
                <div class="print-label">대표자:</div>
                <div class="print-value">John Smith</div>
            </div>
            <div class="print-row">
                <div class="print-label">을 (판매점):</div>
                <div class="print-value"><?php echo htmlspecialchars($contract['store_name']); ?> (코드: <?php echo htmlspecialchars($contract['store_code']); ?>)</div>
            </div>
            <div class="print-row">
                <div class="print-label">주소:</div>
                <div class="print-value">
                    <?php 
                    $fullAddress = [];
                    if (!empty($contract['address'])) $fullAddress[] = htmlspecialchars($contract['address']);
                    if (!empty($contract['city'])) $fullAddress[] = htmlspecialchars($contract['city']);
                    if (!empty($contract['state'])) $fullAddress[] = htmlspecialchars($contract['state']);
                    if (!empty($contract['postal_code'])) $fullAddress[] = htmlspecialchars($contract['postal_code']);
                    if (!empty($contract['country'])) $fullAddress[] = htmlspecialchars($contract['country']);
                    echo implode(", ", $fullAddress);
                    ?>
                </div>
            </div>
            <div class="print-row">
                <div class="print-label">대표자:</div>
                <div class="print-value"><?php echo htmlspecialchars($contract['owner_name']); ?></div>
            </div>
            <div class="print-row">
                <div class="print-label">연락처:</div>
                <div class="print-value"><?php echo htmlspecialchars($contract['store_phone']); ?></div>
            </div>
            <div class="print-row">
                <div class="print-label">이메일:</div>
                <div class="print-value"><?php echo htmlspecialchars($contract['store_email']); ?></div>
            </div>
            <div class="print-row">
                <div class="print-label">사업자등록번호:</div>
                <div class="print-value"><?php echo htmlspecialchars($contract['business_license_number']); ?></div>
            </div>
            <div class="print-row">
                <div class="print-label">세금ID:</div>
                <div class="print-value"><?php echo htmlspecialchars($contract['tax_id']); ?></div>
            </div>
        </div>
        
        <div class="print-section">
            <div class="print-section-title">계약 내용</div>
            <div class="print-row">
                <div class="print-label">계약 유형:</div>
                <div class="print-value">
                    <?php 
                    $contractTypeLabels = [
                        'standard' => '표준 계약',
                        'premium' => '프리미엄 계약',
                        'seasonal' => '계절 계약',
                        'temporary' => '임시 계약',
                        'custom' => '맞춤 계약'
                    ];
                    echo isset($contractTypeLabels[$contract['contract_type']]) 
                        ? $contractTypeLabels[$contract['contract_type']] 
                        : htmlspecialchars($contract['contract_type']);
                    ?>
                </div>
            </div>
            <div class="print-row">
                <div class="print-label">계약 기간:</div>
                <div class="print-value">
                    <?php 
                    echo formatDate($contract['start_date']) . ' ~ ' . formatDate($contract['end_date']); 
                    
                    // Calculate contract duration
                    $startDate = new DateTime($contract['start_date']);
                    $endDate = new DateTime($contract['end_date']);
                    $interval = $startDate->diff($endDate);
                    $months = ($interval->y * 12) + $interval->m;
                    
                    echo ' (' . $months . '개월)';
                    ?>
                </div>
            </div>
            <div class="print-row">
                <div class="print-label">수수료율:</div>
                <div class="print-value"><?php echo number_format($contract['commission_rate'], 2); ?>%</div>
            </div>
            <div class="print-row">
                <div class="print-label">판매 목표:</div>
                <div class="print-value"><?php echo !empty($contract['sales_target']) ? '₩ ' . number_format($contract['sales_target']) : '-'; ?></div>
            </div>
            <div class="print-row">
                <div class="print-label">최소 보장액:</div>
                <div class="print-value"><?php echo !empty($contract['min_guarantee_amount']) ? '₩ ' . number_format($contract['min_guarantee_amount']) : '-'; ?></div>
            </div>
            <div class="print-row">
                <div class="print-label">보증금:</div>
                <div class="print-value"><?php echo !empty($contract['security_deposit']) ? '₩ ' . number_format($contract['security_deposit']) : '-'; ?></div>
            </div>
        </div>
        
        <div class="print-section">
            <div class="print-section-title">계약 조건</div>
            <ol>
                <li>판매점은 KHUSHI LOTTERY의 정책과 절차에 따라 복권을 판매해야 합니다.</li>
                <li>판매점은 복권 판매 시 적절한 신분증 확인을 통해 미성년자에게 판매하지 않도록 해야 합니다.</li>
                <li>판매점은 KHUSHI LOTTERY로부터 제공받은 장비를 적절하게 관리하고 보호해야 합니다.</li>
                <li>수수료는 매주 판매 실적에 따라 정산되며, 정산 주기는 월요일부터 일요일까지입니다.</li>
                <li>판매점은 복권 판매 관련 문제 발생 시 즉시 KHUSHI LOTTERY에 보고해야 합니다.</li>
                <li>KHUSHI LOTTERY는 필요시 판매점에 대한 교육 및 기술 지원을 제공합니다.</li>
                <li>계약 종료 30일 전까지 양측 중 어느 한쪽이 계약 종료 의사를 표시하지 않으면 동일한 조건으로 1년 연장됩니다.</li>
                <li>계약 위반 시 KHUSHI LOTTERY는 즉시 계약을 해지할 수 있습니다.</li>
                <li>판매점은 KHUSHI LOTTERY의 브랜드 및 마케팅 지침을 준수해야 합니다.</li>
                <li>판매점은 당첨자에게 지급 가능한 최대 금액 이하의 당첨금을 지급할 의무가 있습니다.</li>
            </ol>
            
            <?php if (!empty($contract['special_terms'])): ?>
            <div class="print-special-terms">
                <strong>특별 계약 조건:</strong><br>
                <?php echo nl2br(htmlspecialchars($contract['special_terms'])); ?>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="print-footer">
            <div class="print-signature">
                <p><strong>갑 (KHUSHI LOTTERY)</strong></p>
                <p>서명: _________________________</p>
                <p>이름: _________________________</p>
                <p>직위: _________________________</p>
            </div>
            <div class="print-signature">
                <p><strong>을 (<?php echo htmlspecialchars($contract['store_name']); ?>)</strong></p>
                <p>서명: _________________________</p>
                <p>이름: <?php echo htmlspecialchars($contract['owner_name']); ?></p>
                <p>직위: 대표</p>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 20px;">
            <p>계약 서명일: <?php echo formatDate($contract['signing_date']); ?></p>
        </div>
        
        <div class="print-actions">
            <button class="print-button" onclick="window.print();">인쇄하기</button>
            <button class="print-button" onclick="window.close();" style="background-color: #6c757d;">닫기</button>
        </div>
    </div>
</body>
</html>