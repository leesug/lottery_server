<?php
/**
 * 페이지 헤더 템플릿 파일
 * 각 페이지의 상단 타이틀과 브레드크럼을 표시합니다.
 */
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
                    <?php if (isset($currentSection) && !empty($currentSection)): ?>
                        <?php
                        $sectionTitle = '';
                        switch ($currentSection) {
                            case 'lottery':
                                $sectionTitle = '복권 관리';
                                break;
                            case 'sales':
                                $sectionTitle = '판매 관리';
                                break;
                            case 'draw':
                                $sectionTitle = '추첨 관리';
                                break;
                            case 'prize':
                                $sectionTitle = '당첨금 관리';
                                break;
                            case 'customer':
                                $sectionTitle = '고객 관리';
                                break;
                            case 'store':
                                $sectionTitle = '판매점 관리';
                                break;
                            case 'finance':
                                $sectionTitle = '재무 관리';
                                break;
                            case 'marketing':
                                $sectionTitle = '마케팅 관리';
                                break;
                            case 'reports':
                                $sectionTitle = '통계 및 보고서';
                                break;
                            case 'system':
                                $sectionTitle = '시스템 관리';
                                break;
                            case 'security':
                                $sectionTitle = '보안 관리';
                                break;
                            case 'logs':
                                $sectionTitle = '로그/감사';
                                break;
                            case 'external-monitoring':
                                $sectionTitle = '외부관련접속';
                                break;
                            default:
                                $sectionTitle = $currentSection;
                        }
                        ?>
                        <li class="breadcrumb-item"><?php echo $sectionTitle; ?></li>
                    <?php endif; ?>
                    <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- /.content-header -->

<?php if (!empty($message)): ?>
    <div class="container-fluid">
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    </div>
<?php endif; ?>
