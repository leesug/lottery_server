<?php
/**
 * 대시보드 헤더 템플릿 파일
 */

// 설정 및 공통 함수가 이미 로드되어 있지 않으면 로드
if (!defined('SERVER_URL')) {
    require_once __DIR__ . '/../includes/config.php';
}
if (!function_exists('check_auth')) {
    require_once __DIR__ . '/../includes/functions.php';
    require_once __DIR__ . '/../includes/auth.php';
}

// 로그인 확인 (비활성화됨)
// check_auth();

// 고정된 사용자 정보 (세션 관리 기능이 비활성화됨)
$username = "관리자";
$userRole = "admin";
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="server-url" content="<?php echo SERVER_URL; ?>">
    <title><?php echo $pageTitle ?? 'KHUSHI LOTTERY'; ?></title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.1.0/dist/css/adminlte.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SERVER_URL; ?>/assets/css/style.css">
    <?php if (isset($extraCss)): ?>
        <link rel="stylesheet" href="<?php echo $extraCss; ?>">
    <?php endif; ?>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="<?php echo SERVER_URL; ?>/dashboard/index.php" class="nav-link">홈</a>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <!-- Notifications -->
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-bell"></i>
                    <span class="badge badge-warning navbar-badge">0</span>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <span class="dropdown-item dropdown-header">0개의 알림</span>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item">
                        <i class="fas fa-info mr-2"></i> 새로운 알림이 없습니다
                    </a>
                </div>
            </li>
            <!-- User Menu -->
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-user"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <span class="dropdown-item dropdown-header"><?php echo htmlspecialchars($username); ?></span>
                    <div class="dropdown-divider"></div>
                    <a href="<?php echo SERVER_URL; ?>/pages/profile.php" class="dropdown-item">
                        <i class="fas fa-user-cog mr-2"></i> 내 정보
                    </a>
                    <div class="dropdown-divider"></div>
                    <form action="<?php echo SERVER_URL; ?>/pages/logout.php" method="post" class="dropdown-item p-0">
                        <input type="hidden" name="csrf_token" value="<?php echo md5(uniqid()); ?>">
                        <button type="submit" class="btn btn-link text-left w-100 px-3">
                            <i class="fas fa-sign-out-alt mr-2"></i> 로그아웃
                        </button>
                    </form>
                </div>
            </li>
        </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="<?php echo SERVER_URL; ?>/dashboard/index.php" class="brand-link">
            <span class="brand-text font-weight-light">KHUSHI LOTTERY</span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="true">
                    <!-- Dashboard -->
                    <li class="nav-item">
                        <a href="<?php echo SERVER_URL; ?>/dashboard/index.php" class="nav-link <?php echo ($currentPage === 'index.php' && $currentSection === 'dashboard') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>종합 대시보드</p>
                        </a>
                    </li>
                    
                    <!-- 복권 관리 -->
                    <li class="nav-item <?php echo ($currentSection === 'lottery') ? 'menu-open' : ''; ?>">
                        <a href="<?php echo SERVER_URL; ?>/dashboard/lottery/dashboard.php" class="nav-link <?php echo ($currentSection === 'lottery') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-ticket-alt"></i>
                            <p>
                                복권 관리
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/lottery/dashboard.php" class="nav-link <?php echo ($currentPage === 'dashboard.php' && $currentSection === 'lottery') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>복권 대시보드</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/lottery/products.php" class="nav-link <?php echo ($currentPage === 'products.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>복권 상품 관리</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/lottery/issues.php" class="nav-link <?php echo ($currentPage === 'issues.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>복권 발행 관리</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/lottery/numbering.php" class="nav-link <?php echo ($currentPage === 'numbering.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>넘버링 관리</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/lottery/status.php" class="nav-link <?php echo ($currentPage === 'status.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>복권 상태 관리</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/lottery/lottery-templates.php" class="nav-link <?php echo ($currentPage === 'lottery-templates.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>복권 템플릿 관리</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/lottery/batch-management.php" class="nav-link <?php echo ($currentPage === 'batch-management.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>배치 관리</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/lottery/printing-settings.php" class="nav-link <?php echo ($currentPage === 'printing-settings.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>인쇄 설정</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/lottery/barcode-management.php" class="nav-link <?php echo ($currentPage === 'barcode-management.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>QR코드 관리</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/lottery/validation-settings.php" class="nav-link <?php echo ($currentPage === 'validation-settings.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>유효성 검증 설정</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- 판매 관리 -->
                    <li class="nav-item <?php echo ($currentSection === 'sales') ? 'menu-open' : ''; ?>">
                        <a href="<?php echo SERVER_URL; ?>/dashboard/sales/dashboard.php" class="nav-link <?php echo ($currentSection === 'sales') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-shopping-cart"></i>
                            <p>
                                판매 관리
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/sales/dashboard.php" class="nav-link <?php echo ($currentPage === 'dashboard.php' && $currentSection === 'sales') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>판매 대시보드</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/sales/status.php" class="nav-link <?php echo ($currentPage === 'status.php' && $currentSection === 'sales') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>판매 현황</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/sales/history.php" class="nav-link <?php echo ($currentPage === 'history.php' && $currentSection === 'sales') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>판매 이력</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/sales/policy.php" class="nav-link <?php echo ($currentPage === 'policy.php' && $currentSection === 'sales') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>판매 정책</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/sales/refund.php" class="nav-link <?php echo ($currentPage === 'refund.php' && $currentSection === 'sales') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>판매 취소/환불</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- 추첨 관리 -->
                    <li class="nav-item <?php echo ($currentSection === 'draw') ? 'menu-open' : ''; ?>">
                        <a href="<?php echo SERVER_URL; ?>/dashboard/draw/dashboard.php" class="nav-link <?php echo ($currentSection === 'draw') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-random"></i>
                            <p>
                                추첨 관리
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/draw/dashboard.php" class="nav-link <?php echo ($currentPage === 'dashboard.php' && $currentSection === 'draw') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>추첨 대시보드</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/draw/plan.php" class="nav-link <?php echo ($currentPage === 'plan.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>추첨 계획</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/draw/execution.php?draw_id=125" class="nav-link <?php echo ($currentPage === 'execution.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>추첨 실행</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/draw/results.php?draw_id=125" class="nav-link <?php echo ($currentPage === 'results.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>추첨 결과</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/draw/history.php" class="nav-link <?php echo ($currentPage === 'history.php' && $currentSection === 'draw') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>추첨 이력</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- 당첨금 관리 -->
                    <li class="nav-item <?php echo ($currentSection === 'prize') ? 'menu-open' : ''; ?>">
                        <a href="<?php echo SERVER_URL; ?>/dashboard/prize/dashboard.php" class="nav-link <?php echo ($currentSection === 'prize') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-money-bill-wave"></i>
                            <p>
                                당첨금 관리
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/prize/dashboard.php" class="nav-link <?php echo ($currentPage === 'dashboard.php' && $currentSection === 'prize') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>당첨금 대시보드</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/prize/settings.php" class="nav-link <?php echo ($currentPage === 'settings.php' && $currentSection === 'prize') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>당첨금 설정</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/prize/payment.php" class="nav-link <?php echo ($currentPage === 'payment.php' && $currentSection === 'prize') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>당첨금 지급</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/prize/statistics.php" class="nav-link <?php echo ($currentPage === 'statistics.php' && $currentSection === 'prize') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>당첨금 통계</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/prize/carryover.php" class="nav-link <?php echo ($currentPage === 'carryover.php' && $currentSection === 'prize') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>당첨금 이월</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- 고객 관리 -->
                    <li class="nav-item <?php echo ($currentSection === 'customer') ? 'menu-open' : ''; ?>">
                        <a href="#" class="nav-link <?php echo ($currentSection === 'customer') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-users"></i>
                            <p>
                                고객 관리
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/customer/customer-list.php" class="nav-link <?php echo ($currentPage === 'customer-list.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>고객 목록</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/customer/customer-add.php" class="nav-link <?php echo ($currentPage === 'customer-add.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>고객 추가</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/customer/customer-transactions.php" class="nav-link <?php echo ($currentPage === 'customer-transactions.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>고객 거래 내역</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/customer/verification.php" class="nav-link <?php echo ($currentPage === 'verification.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>고객 인증 관리</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/customer/customer-dashboard.php" class="nav-link <?php echo ($currentPage === 'customer-dashboard.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>고객 대시보드</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- 판매점 관리 -->
                    <li class="nav-item <?php echo ($currentSection === 'store') ? 'menu-open' : ''; ?>">
                        <a href="#" class="nav-link <?php echo ($currentSection === 'store') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-store"></i>
                            <p>
                                판매점 관리
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/store/store-list.php" class="nav-link <?php echo ($currentPage === 'store-list.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>판매점 목록</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/store/store-add.php" class="nav-link <?php echo ($currentPage === 'store-add.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>판매점 추가</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/store/store-sales.php" class="nav-link <?php echo ($currentPage === 'store-sales.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>판매점 판매 현황</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/store/equipment-list.php" class="nav-link <?php echo ($currentPage === 'equipment-list.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>장비 관리</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/store/equipment-maintenance.php" class="nav-link <?php echo ($currentPage === 'equipment-maintenance.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>장비 유지보수</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/store/contract-list.php" class="nav-link <?php echo ($currentPage === 'contract-list.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>계약 관리</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/store/store-dashboard.php" class="nav-link <?php echo ($currentPage === 'store-dashboard.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>판매점 대시보드</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- 재무 관리 -->
                    <li class="nav-item <?php echo ($currentSection === 'finance') ? 'menu-open' : ''; ?>">
                        <a href="#" class="nav-link <?php echo ($currentSection === 'finance') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-chart-line"></i>
                            <p>
                                재무 관리
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/finance/transactions.php" class="nav-link <?php echo ($currentPage === 'transactions.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>거래 내역</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/finance/balance.php" class="nav-link <?php echo ($currentPage === 'balance.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>자금 잔액 현황</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/finance/funds.php" class="nav-link <?php echo ($currentPage === 'funds.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>기금 관리</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/finance/budget.php" class="nav-link <?php echo ($currentPage === 'budget.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>예산 관리</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/finance/settlements.php" class="nav-link <?php echo ($currentPage === 'settlements.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>정산 관리</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/finance/reports.php" class="nav-link <?php echo ($currentPage === 'reports.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>재무 보고서</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/finance/finance-dashboard.php" class="nav-link <?php echo ($currentPage === 'finance-dashboard.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>재무 대시보드</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- 마케팅 관리 -->
                    <li class="nav-item <?php echo ($currentSection === 'marketing') ? 'menu-open' : ''; ?>">
                        <a href="#" class="nav-link <?php echo ($currentSection === 'marketing') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-bullhorn"></i>
                            <p>
                                마케팅 관리
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/marketing/campaigns.php" class="nav-link <?php echo ($currentPage === 'campaigns.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>캠페인 관리</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/marketing/promotions.php" class="nav-link <?php echo ($currentPage === 'promotions.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>프로모션 관리</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/marketing/advertisements.php" class="nav-link <?php echo ($currentPage === 'advertisements.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>광고 관리</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/marketing/sms.php" class="nav-link <?php echo ($currentPage === 'sms.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>SMS 마케팅</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/marketing/email.php" class="nav-link <?php echo ($currentPage === 'email.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>이메일 마케팅</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/marketing/marketing-dashboard.php" class="nav-link <?php echo ($currentPage === 'marketing-dashboard.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>마케팅 대시보드</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- 통계 및 보고서 -->
                    <li class="nav-item <?php echo ($currentSection === 'reports') ? 'menu-open' : ''; ?>">
                        <a href="#" class="nav-link <?php echo ($currentSection === 'reports') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-chart-bar"></i>
                            <p>
                                통계 및 보고서
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/reports/sales-report.php" class="nav-link <?php echo ($currentPage === 'sales-report.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>판매 보고서</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/reports/draw-report.php" class="nav-link <?php echo ($currentPage === 'draw-report.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>추첨 보고서</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/reports/store-report.php" class="nav-link <?php echo ($currentPage === 'store-report.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>판매점 보고서</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/reports/customer-report.php" class="nav-link <?php echo ($currentPage === 'customer-report.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>고객 보고서</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/reports/financial-report.php" class="nav-link <?php echo ($currentPage === 'financial-report.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>재무 보고서</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/reports/reports-dashboard.php" class="nav-link <?php echo ($currentPage === 'reports-dashboard.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>보고서 대시보드</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- 시스템 관리 -->
                    <li class="nav-item <?php echo ($currentSection === 'system') ? 'menu-open' : ''; ?>">
                        <a href="#" class="nav-link <?php echo ($currentSection === 'system') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-cogs"></i>
                            <p>
                                시스템 관리
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/system/users.php" class="nav-link <?php echo ($currentPage === 'users.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>사용자 관리</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/system/roles.php" class="nav-link <?php echo ($currentPage === 'roles.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>역할 및 권한 관리</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/system/settings.php" class="nav-link <?php echo ($currentPage === 'settings.php' && $currentSection === 'system') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>시스템 설정</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/system/backup.php" class="nav-link <?php echo ($currentPage === 'backup.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>백업 및 복원</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/system/maintenance.php" class="nav-link <?php echo ($currentPage === 'maintenance.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>시스템 유지보수</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/system/logs.php" class="nav-link <?php echo ($currentPage === 'logs.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>로그 관리</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/system/system-info.php" class="nav-link <?php echo ($currentPage === 'system-info.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>시스템 정보</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/system/ip-access.php" class="nav-link <?php echo ($currentPage === 'ip-access.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>IP 접근 관리</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/system/menus.php" class="nav-link <?php echo ($currentPage === 'menus.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>메뉴 관리</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/system/system-dashboard.php" class="nav-link <?php echo ($currentPage === 'system-dashboard.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>시스템 대시보드</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- 보안 관리 -->
                    <li class="nav-item <?php echo ($currentSection === 'security') ? 'menu-open' : ''; ?>">
                        <a href="#" class="nav-link <?php echo ($currentSection === 'security') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-shield-alt"></i>
                            <p>
                                보안 관리
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/security/external_monitoring.php" class="nav-link <?php echo ($currentPage === 'external_monitoring.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>외부 접속 감시</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/security/external_monitoring_stats.php" class="nav-link <?php echo ($currentPage === 'external_monitoring_stats.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>접속 통계</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/security/ip_blocklist.php" class="nav-link <?php echo ($currentPage === 'ip_blocklist.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>IP 차단 관리</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/security/auth_logs.php" class="nav-link <?php echo ($currentPage === 'auth_logs.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>인증 로그</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/security/security_settings.php" class="nav-link <?php echo ($currentPage === 'security_settings.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>보안 설정</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/security/security_audit.php" class="nav-link <?php echo ($currentPage === 'security_audit.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>보안 감사</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- 로그/감사 -->
                    <li class="nav-item <?php echo ($currentSection === 'logs') ? 'menu-open' : ''; ?>">
                        <a href="<?php echo SERVER_URL; ?>/dashboard/logs/logs-dashboard.php" class="nav-link <?php echo ($currentSection === 'logs') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-clipboard-list"></i>
                            <p>
                                로그/감사
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/logs/logs-dashboard.php" class="nav-link <?php echo ($currentPage === 'logs-dashboard.php' && $currentSection === 'logs') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>로그 대시보드</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/logs/access-logs.php" class="nav-link <?php echo ($currentPage === 'access-logs.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>접근 로그</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/logs/activity-logs.php" class="nav-link <?php echo ($currentPage === 'activity-logs.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>활동 로그</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/logs/system-logs.php" class="nav-link <?php echo ($currentPage === 'system-logs.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>시스템 로그</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/logs/audit-logs.php" class="nav-link <?php echo ($currentPage === 'audit-logs.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>감사 로그</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/logs/security-logs.php" class="nav-link <?php echo ($currentPage === 'security-logs.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>보안 로그</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- 외부관련접속 -->
                    <li class="nav-item <?php echo ($currentSection === 'external-monitoring') ? 'menu-open' : ''; ?>">
                        <a href="#" class="nav-link <?php echo ($currentSection === 'external-monitoring') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-globe"></i>
                            <p>
                                외부관련접속
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/broadcaster/index.php" class="nav-link <?php echo ($currentPage === 'index.php' && $currentSection === 'broadcaster') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>방송국</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/bank/index.php" class="nav-link <?php echo ($currentPage === 'index.php' && $currentSection === 'bank') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>은행</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/government/index.php" class="nav-link <?php echo ($currentPage === 'index.php' && $currentSection === 'government') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>정부</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SERVER_URL; ?>/dashboard/external-monitoring/fund/index.php" class="nav-link <?php echo ($currentPage === 'index.php' && $currentSection === 'fund') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>기금처</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </nav>
            <!-- /.sidebar-menu -->
        </div>
        <!-- /.sidebar -->
    </aside>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
