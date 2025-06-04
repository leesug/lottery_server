<?php
/**
 * 테스트용 간단한 헤더 파일
 */
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? '로또 서버 관리 시스템'; ?></title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { padding-top: 20px; }
        .content-wrapper { padding: 20px; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
            <a class="navbar-brand" href="#">KHUSHI LOTTERY</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item <?php echo ($activeMenu === 'dashboard') ? 'active' : ''; ?>">
                        <a class="nav-link" href="/server/dashboard/">대시보드</a>
                    </li>
                    <li class="nav-item <?php echo ($activeMenu === 'lottery') ? 'active' : ''; ?>">
                        <a class="nav-link" href="/server/dashboard/lottery/">복권 관리</a>
                    </li>
                    <li class="nav-item <?php echo ($activeMenu === 'sales') ? 'active' : ''; ?>">
                        <a class="nav-link" href="/server/dashboard/sales/">판매 관리</a>
                    </li>
                    <li class="nav-item <?php echo ($activeMenu === 'draw') ? 'active' : ''; ?>">
                        <a class="nav-link" href="/server/dashboard/draw/">추첨 관리</a>
                    </li>
                    <li class="nav-item <?php echo ($activeMenu === 'prize') ? 'active' : ''; ?>">
                        <a class="nav-link" href="/server/dashboard/prize/">당첨금 관리</a>
                    </li>
                    <li class="nav-item <?php echo ($activeMenu === 'customer') ? 'active' : ''; ?>">
                        <a class="nav-link" href="/server/dashboard/customer/">고객 관리</a>
                    </li>
                    <li class="nav-item <?php echo ($activeMenu === 'store') ? 'active' : ''; ?>">
                        <a class="nav-link" href="/server/dashboard/store/">판매점 관리</a>
                    </li>
                </ul>
                <span class="navbar-text">
                    관리자 (admin) | 
                    <a href="/server/pages/logout.php" class="text-white">로그아웃</a>
                </span>
            </div>
        </nav>
    </div>