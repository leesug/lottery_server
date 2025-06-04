<?php
/**
 * 역할 및 권한 관리 페이지
 */

// 오류 표시 설정
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 헤더 설정
header('Content-Type: text/html; charset=utf-8');

// 출력 버퍼링 시작
ob_start();

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 현재 페이지 정보
$pageTitle = "역할 및 권한 관리";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 데이터베이스 연결
$db = getDbConnection();

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
                    <li class="breadcrumb-item active">시스템 관리</li>
                    <li class="breadcrumb-item active">역할 및 권한 관리</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- /.content-header -->

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- 역할 및 권한 개요 -->
        <div class="row">
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box">
                    <span class="info-box-icon bg-info"><i class="fas fa-user-shield"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">관리자</span>
                        <span class="info-box-number">5</span>
                        <div class="progress">
                            <div class="progress-bar bg-info" style="width: 6%"></div>
                        </div>
                        <span class="progress-description">
                            전체 사용자의 6%
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box">
                    <span class="info-box-icon bg-warning"><i class="fas fa-user-tie"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">매니저</span>
                        <span class="info-box-number">15</span>
                        <div class="progress">
                            <div class="progress-bar bg-warning" style="width: 18%"></div>
                        </div>
                        <span class="progress-description">
                            전체 사용자의 18%
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box">
                    <span class="info-box-icon bg-primary"><i class="fas fa-user-cog"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">운영자</span>
                        <span class="info-box-number">22</span>
                        <div class="progress">
                            <div class="progress-bar bg-primary" style="width: 27%"></div>
                        </div>
                        <span class="progress-description">
                            전체 사용자의 27%
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box">
                    <span class="info-box-icon bg-secondary"><i class="fas fa-user"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">일반 직원</span>
                        <span class="info-box-number">40</span>
                        <div class="progress">
                            <div class="progress-bar bg-secondary" style="width: 49%"></div>
                        </div>
                        <span class="progress-description">
                            전체 사용자의 49%
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.row -->
        
        <!-- 역할 관리 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">역할 관리</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addRoleModal">
                        <i class="fas fa-plus"></i> 역할 추가
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th style="width: 50px">ID</th>
                                <th>역할명</th>
                                <th>설명</th>
                                <th>사용자 수</th>
                                <th>생성일</th>
                                <th>상태</th>
                                <th style="width: 150px">액션</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- 샘플 데이터 -->
                            <tr>
                                <td>1</td>
                                <td>관리자</td>
                                <td>시스템 관리자 역할. 모든 기능에 접근 가능</td>
                                <td>5</td>
                                <td>2024-01-01</td>
                                <td><span class="badge badge-success">활성</span></td>
                                <td>
                                    <a href="#" class="btn btn-info btn-xs" data-toggle="modal" data-target="#viewRoleModal1">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="#" class="btn btn-primary btn-xs" data-toggle="modal" data-target="#editRoleModal1">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-xs" data-toggle="modal" data-target="#deleteRoleModal1">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>매니저</td>
                                <td>부서 매니저 역할. 대부분의 기능에 접근 가능하나 시스템 설정은 제한됨</td>
                                <td>15</td>
                                <td>2024-01-01</td>
                                <td><span class="badge badge-success">활성</span></td>
                                <td>
                                    <a href="#" class="btn btn-info btn-xs" data-toggle="modal" data-target="#viewRoleModal2">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="#" class="btn btn-primary btn-xs" data-toggle="modal" data-target="#editRoleModal2">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-xs" data-toggle="modal" data-target="#deleteRoleModal2">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>운영자</td>
                                <td>부서 운영 담당자. 일부 관리 기능 및 보고서 접근 가능</td>
                                <td>22</td>
                                <td>2024-01-01</td>
                                <td><span class="badge badge-success">활성</span></td>
                                <td>
                                    <a href="#" class="btn btn-info btn-xs" data-toggle="modal" data-target="#viewRoleModal3">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="#" class="btn btn-primary btn-xs" data-toggle="modal" data-target="#editRoleModal3">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-xs" data-toggle="modal" data-target="#deleteRoleModal3">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>일반 직원</td>
                                <td>기본 사용자 역할. 제한된 기능에만 접근 가능</td>
                                <td>40</td>
                                <td>2024-01-01</td>
                                <td><span class="badge badge-success">활성</span></td>
                                <td>
                                    <a href="#" class="btn btn-info btn-xs" data-toggle="modal" data-target="#viewRoleModal4">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="#" class="btn btn-primary btn-xs" data-toggle="modal" data-target="#editRoleModal4">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-xs" data-toggle="modal" data-target="#deleteRoleModal4">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>고객 지원</td>
                                <td>고객 지원 담당자. 고객 데이터 및 지원 티켓 관리 기능에 접근 가능</td>
                                <td>18</td>
                                <td>2024-02-15</td>
                                <td><span class="badge badge-success">활성</span></td>
                                <td>
                                    <a href="#" class="btn btn-info btn-xs" data-toggle="modal" data-target="#viewRoleModal5">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="#" class="btn btn-primary btn-xs" data-toggle="modal" data-target="#editRoleModal5">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-xs" data-toggle="modal" data-target="#deleteRoleModal5">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- /.card -->
        
        <!-- 권한 관리 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">역할별 권한 관리</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="roleSelect">역할 선택</label>
                            <select class="form-control" id="roleSelect">
                                <option value="1">관리자</option>
                                <option value="2">매니저</option>
                                <option value="3">운영자</option>
                                <option value="4">일반 직원</option>
                                <option value="5">고객 지원</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-8 d-flex align-items-center">
                        <button type="button" id="savePermissions" class="btn btn-primary ml-auto">
                            <i class="fas fa-save"></i> 권한 변경사항 저장
                        </button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th style="width: 200px">모듈</th>
                                <th>조회 권한</th>
                                <th>생성 권한</th>
                                <th>수정 권한</th>
                                <th>삭제 권한</th>
                                <th>승인 권한</th>
                                <th>내보내기 권한</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- 복권 관리 모듈 -->
                            <tr>
                                <td><strong>복권 관리</strong></td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="lottery_view" checked>
                                        <label class="custom-control-label" for="lottery_view"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="lottery_create" checked>
                                        <label class="custom-control-label" for="lottery_create"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="lottery_edit" checked>
                                        <label class="custom-control-label" for="lottery_edit"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="lottery_delete" checked>
                                        <label class="custom-control-label" for="lottery_delete"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="lottery_approve" checked>
                                        <label class="custom-control-label" for="lottery_approve"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="lottery_export" checked>
                                        <label class="custom-control-label" for="lottery_export"></label>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- 판매 관리 모듈 -->
                            <tr>
                                <td><strong>판매 관리</strong></td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="sales_view" checked>
                                        <label class="custom-control-label" for="sales_view"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="sales_create" checked>
                                        <label class="custom-control-label" for="sales_create"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="sales_edit" checked>
                                        <label class="custom-control-label" for="sales_edit"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="sales_delete" checked>
                                        <label class="custom-control-label" for="sales_delete"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="sales_approve" checked>
                                        <label class="custom-control-label" for="sales_approve"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="sales_export" checked>
                                        <label class="custom-control-label" for="sales_export"></label>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- 추첨 관리 모듈 -->
                            <tr>
                                <td><strong>추첨 관리</strong></td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="draw_view" checked>
                                        <label class="custom-control-label" for="draw_view"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="draw_create" checked>
                                        <label class="custom-control-label" for="draw_create"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="draw_edit" checked>
                                        <label class="custom-control-label" for="draw_edit"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="draw_delete" checked>
                                        <label class="custom-control-label" for="draw_delete"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="draw_approve" checked>
                                        <label class="custom-control-label" for="draw_approve"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="draw_export" checked>
                                        <label class="custom-control-label" for="draw_export"></label>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- 당첨금 관리 모듈 -->
                            <tr>
                                <td><strong>당첨금 관리</strong></td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="prize_view" checked>
                                        <label class="custom-control-label" for="prize_view"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="prize_create" checked>
                                        <label class="custom-control-label" for="prize_create"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="prize_edit" checked>
                                        <label class="custom-control-label" for="prize_edit"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="prize_delete" checked>
                                        <label class="custom-control-label" for="prize_delete"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="prize_approve" checked>
                                        <label class="custom-control-label" for="prize_approve"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="prize_export" checked>
                                        <label class="custom-control-label" for="prize_export"></label>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- 고객 관리 모듈 -->
                            <tr>
                                <td><strong>고객 관리</strong></td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="customer_view" checked>
                                        <label class="custom-control-label" for="customer_view"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="customer_create" checked>
                                        <label class="custom-control-label" for="customer_create"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="customer_edit" checked>
                                        <label class="custom-control-label" for="customer_edit"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="customer_delete" checked>
                                        <label class="custom-control-label" for="customer_delete"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="customer_approve" checked>
                                        <label class="custom-control-label" for="customer_approve"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="customer_export" checked>
                                        <label class="custom-control-label" for="customer_export"></label>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- 판매점 관리 모듈 -->
                            <tr>
                                <td><strong>판매점 관리</strong></td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="store_view" checked>
                                        <label class="custom-control-label" for="store_view"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="store_create" checked>
                                        <label class="custom-control-label" for="store_create"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="store_edit" checked>
                                        <label class="custom-control-label" for="store_edit"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="store_delete" checked>
                                        <label class="custom-control-label" for="store_delete"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="store_approve" checked>
                                        <label class="custom-control-label" for="store_approve"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="store_export" checked>
                                        <label class="custom-control-label" for="store_export"></label>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- 재무 관리 모듈 -->
                            <tr>
                                <td><strong>재무 관리</strong></td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="finance_view" checked>
                                        <label class="custom-control-label" for="finance_view"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="finance_create" checked>
                                        <label class="custom-control-label" for="finance_create"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="finance_edit" checked>
                                        <label class="custom-control-label" for="finance_edit"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="finance_delete" checked>
                                        <label class="custom-control-label" for="finance_delete"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="finance_approve" checked>
                                        <label class="custom-control-label" for="finance_approve"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="finance_export" checked>
                                        <label class="custom-control-label" for="finance_export"></label>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- 마케팅 관리 모듈 -->
                            <tr>
                                <td><strong>마케팅 관리</strong></td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="marketing_view" checked>
                                        <label class="custom-control-label" for="marketing_view"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="marketing_create" checked>
                                        <label class="custom-control-label" for="marketing_create"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="marketing_edit" checked>
                                        <label class="custom-control-label" for="marketing_edit"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="marketing_delete" checked>
                                        <label class="custom-control-label" for="marketing_delete"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="marketing_approve" checked>
                                        <label class="custom-control-label" for="marketing_approve"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="marketing_export" checked>
                                        <label class="custom-control-label" for="marketing_export"></label>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- 보고서 모듈 -->
                            <tr>
                                <td><strong>통계 및 보고서</strong></td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="reports_view" checked>
                                        <label class="custom-control-label" for="reports_view"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="reports_create" checked>
                                        <label class="custom-control-label" for="reports_create"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="reports_edit" checked>
                                        <label class="custom-control-label" for="reports_edit"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="reports_delete" checked>
                                        <label class="custom-control-label" for="reports_delete"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="reports_approve" checked>
                                        <label class="custom-control-label" for="reports_approve"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="reports_export" checked>
                                        <label class="custom-control-label" for="reports_export"></label>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- 시스템 관리 모듈 -->
                            <tr>
                                <td><strong>시스템 관리</strong></td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="system_view" checked>
                                        <label class="custom-control-label" for="system_view"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="system_create" checked>
                                        <label class="custom-control-label" for="system_create"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="system_edit" checked>
                                        <label class="custom-control-label" for="system_edit"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="system_delete" checked>
                                        <label class="custom-control-label" for="system_delete"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="system_approve" checked>
                                        <label class="custom-control-label" for="system_approve"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="system_export" checked>
                                        <label class="custom-control-label" for="system_export"></label>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- 로그/감사 모듈 -->
                            <tr>
                                <td><strong>로그/감사</strong></td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="logs_view" checked>
                                        <label class="custom-control-label" for="logs_view"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="logs_create" checked>
                                        <label class="custom-control-label" for="logs_create"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="logs_edit" checked>
                                        <label class="custom-control-label" for="logs_edit"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="logs_delete" checked>
                                        <label class="custom-control-label" for="logs_delete"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="logs_approve" checked>
                                        <label class="custom-control-label" for="logs_approve"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="logs_export" checked>
                                        <label class="custom-control-label" for="logs_export"></label>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-center">
                <button type="button" id="savePermissionsBottom" class="btn btn-primary">
                    <i class="fas fa-save"></i> 권한 변경사항 저장
                </button>
            </div>
        </div>
        <!-- /.card -->
    </div>
</section>
<!-- /.content -->

<!-- 역할 조회 모달 -->
<div class="modal fade" id="viewRoleModal1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">역할 상세 정보</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <dl class="row">
                    <dt class="col-sm-4">역할 ID:</dt>
                    <dd class="col-sm-8">1</dd>
                    
                    <dt class="col-sm-4">역할명:</dt>
                    <dd class="col-sm-8">관리자</dd>
                    
                    <dt class="col-sm-4">설명:</dt>
                    <dd class="col-sm-8">시스템 관리자 역할. 모든 기능에 접근 가능</dd>
                    
                    <dt class="col-sm-4">생성일:</dt>
                    <dd class="col-sm-8">2024-01-01</dd>
                    
                    <dt class="col-sm-4">상태:</dt>
                    <dd class="col-sm-8"><span class="badge badge-success">활성</span></dd>
                    
                    <dt class="col-sm-4">사용자 수:</dt>
                    <dd class="col-sm-8">5</dd>
                </dl>
                <h5>사용자 목록:</h5>
                <ul>
                    <li>라젠드라 프라사드 (rajendra.prasad@khushilottery.com)</li>
                    <li>수닐 바타차르야 (sunil.bhattacharya@khushilottery.com)</li>
                    <li>비나야크 카르키 (binayak.karki@khushilottery.com)</li>
                    <li>아니타 가우탐 (anita.gautam@khushilottery.com)</li>
                    <li>디페시 실발 (dipesh.silwal@khushilottery.com)</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">닫기</button>
            </div>
        </div>
    </div>
</div>

<!-- 역할 추가 모달 -->
<div class="modal fade" id="addRoleModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">역할 추가</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="addRoleForm">
                    <div class="form-group">
                        <label for="role_name">역할명 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="role_name" name="role_name" required>
                    </div>
                    <div class="form-group">
                        <label for="role_description">설명</label>
                        <textarea class="form-control" id="role_description" name="role_description" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>상태</label>
                        <div class="custom-control custom-radio">
                            <input type="radio" id="role_status_active" name="role_status" class="custom-control-input" value="active" checked>
                            <label class="custom-control-label" for="role_status_active">활성</label>
                        </div>
                        <div class="custom-control custom-radio">
                            <input type="radio" id="role_status_inactive" name="role_status" class="custom-control-input" value="inactive">
                            <label class="custom-control-label" for="role_status_inactive">비활성</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>권한 복사 대상</label>
                        <select class="form-control" id="copy_permissions_from">
                            <option value="">권한 템플릿 선택 (선택사항)</option>
                            <option value="1">관리자</option>
                            <option value="2">매니저</option>
                            <option value="3">운영자</option>
                            <option value="4">일반 직원</option>
                            <option value="5">고객 지원</option>
                        </select>
                        <small class="form-text text-muted">선택한 역할의 권한 설정을 복사합니다.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="saveNewRole">저장</button>
            </div>
        </div>
    </div>
</div>

<script>
// 페이지 로드 시 실행
document.addEventListener('DOMContentLoaded', function() {
    console.log('역할 및 권한 관리 페이지가 로드되었습니다.');
    
    // 역할 선택 시 권한 로드
    initRoleSelect();
    
    // 권한 저장 버튼 이벤트
    setupSavePermissions();
    
    // 새 역할 저장 버튼 이벤트
    setupSaveNewRole();
});

// 역할 선택 시 권한 로드
function initRoleSelect() {
    const roleSelect = document.getElementById('roleSelect');
    
    if (roleSelect) {
        roleSelect.addEventListener('change', function() {
            loadPermissions(this.value);
        });
        
        // 초기 권한 로드
        loadPermissions(roleSelect.value);
    }
}

// 권한 로드 함수
function loadPermissions(roleId) {
    console.log(`역할 ID ${roleId}의 권한을 로드합니다.`);
    
    // 역할 ID에 따라 권한 설정 변경 (데모를 위한 간단한 예시)
    switch (roleId) {
        case '1': // 관리자
            setAllPermissions(true);
            break;
        case '2': // 매니저
            setManagerPermissions();
            break;
        case '3': // 운영자
            setOperatorPermissions();
            break;
        case '4': // 일반 직원
            setStaffPermissions();
            break;
        case '5': // 고객 지원
            setSupportPermissions();
            break;
    }
}

// 모든 권한 설정 함수
function setAllPermissions(value) {
    const checkboxes = document.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(function(checkbox) {
        checkbox.checked = value;
    });
}

// 매니저 권한 설정 함수
function setManagerPermissions() {
    // 일단 모든 권한 비활성화
    setAllPermissions(false);
    
    // 매니저 권한 설정
    const viewCheckboxes = document.querySelectorAll('input[id$="_view"]');
    viewCheckboxes.forEach(function(checkbox) {
        checkbox.checked = true;
    });
    
    const createCheckboxes = document.querySelectorAll('input[id$="_create"]');
    createCheckboxes.forEach(function(checkbox) {
        checkbox.checked = true;
    });
    
    const editCheckboxes = document.querySelectorAll('input[id$="_edit"]');
    editCheckboxes.forEach(function(checkbox) {
        checkbox.checked = true;
    });
    
    const exportCheckboxes = document.querySelectorAll('input[id$="_export"]');
    exportCheckboxes.forEach(function(checkbox) {
        checkbox.checked = true;
    });
    
    // 시스템 관리 모듈 권한 제한
    document.getElementById('system_create').checked = false;
    document.getElementById('system_edit').checked = false;
    document.getElementById('system_delete').checked = false;
    document.getElementById('system_approve').checked = false;
    
    // 일부 모듈 삭제 권한 제한
    document.getElementById('lottery_delete').checked = false;
    document.getElementById('draw_delete').checked = false;
    document.getElementById('finance_delete').checked = false;
}

// 운영자 권한 설정 함수
function setOperatorPermissions() {
    // 일단 모든 권한 비활성화
    setAllPermissions(false);
    
    // 운영자 권한 설정
    const viewCheckboxes = document.querySelectorAll('input[id$="_view"]');
    viewCheckboxes.forEach(function(checkbox) {
        checkbox.checked = true;
    });
    
    // 특정 모듈에 대한 추가 권한
    document.getElementById('lottery_create').checked = true;
    document.getElementById('lottery_edit').checked = true;
    document.getElementById('lottery_export').checked = true;
    
    document.getElementById('sales_create').checked = true;
    document.getElementById('sales_edit').checked = true;
    document.getElementById('sales_export').checked = true;
    
    document.getElementById('customer_create').checked = true;
    document.getElementById('customer_edit').checked = true;
    document.getElementById('customer_export').checked = true;
    
    document.getElementById('reports_export').checked = true;
}

// 일반 직원 권한 설정 함수
function setStaffPermissions() {
    // 일단 모든 권한 비활성화
    setAllPermissions(false);
    
    // 일반 직원 권한 설정 - 제한된 조회 권한만
    document.getElementById('lottery_view').checked = true;
    document.getElementById('sales_view').checked = true;
    document.getElementById('draw_view').checked = true;
    document.getElementById('prize_view').checked = true;
    document.getElementById('customer_view').checked = true;
    document.getElementById('reports_view').checked = true;
}

// 고객 지원 권한 설정 함수
function setSupportPermissions() {
    // 일단 모든 권한 비활성화
    setAllPermissions(false);
    
    // 고객 지원 권한 설정
    document.getElementById('customer_view').checked = true;
    document.getElementById('customer_create').checked = true;
    document.getElementById('customer_edit').checked = true;
    document.getElementById('customer_export').checked = true;
    
    document.getElementById('sales_view').checked = true;
    document.getElementById('prize_view').checked = true;
    document.getElementById('reports_view').checked = true;
}

// 권한 저장 버튼 이벤트 설정
function setupSavePermissions() {
    const saveButtons = document.querySelectorAll('#savePermissions, #savePermissionsBottom');
    
    saveButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const roleId = document.getElementById('roleSelect').value;
            const roleName = document.getElementById('roleSelect').options[document.getElementById('roleSelect').selectedIndex].text;
            
            // 권한 저장 처리 (실제로는 서버에 저장해야 함)
            alert(`${roleName} 역할의 권한 변경사항이 저장되었습니다.`);
        });
    });
}

// 새 역할 저장 버튼 이벤트 설정
function setupSaveNewRole() {
    const saveButton = document.getElementById('saveNewRole');
    
    if (saveButton) {
        saveButton.addEventListener('click', function() {
            const form = document.getElementById('addRoleForm');
            
            // 간단한 유효성 검사
            const roleName = document.getElementById('role_name').value;
            
            if (!roleName) {
                alert('역할명을 입력해주세요.');
                return;
            }
            
            // 역할 추가 처리 (실제로는 서버에 저장해야 함)
            alert('새 역할이 추가되었습니다.');
            
            // 모달 닫기
            $('#addRoleModal').modal('hide');
            
            // 폼 초기화
            form.reset();
        });
    }
}
</script>

<?php
// 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';

// 출력 버퍼 플러시
ob_end_flush();
?>
