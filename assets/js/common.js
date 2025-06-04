/**
 * 공통 JavaScript 기능
 */

// 전역 변수 정의
var SERVER_URL = '';

// 문서 준비 완료시 실행
$(document).ready(function() {
    console.log('공통 기능 초기화 중...');
    
    // 서버 URL 가져오기
    SERVER_URL = $('meta[name="server-url"]').attr('content') || '';
    console.log('서버 URL:', SERVER_URL);
    
    // DataTables 초기화
    initDataTables();
    
    // 사이드바 활성화
    activateSidebar();
    
    // 툴팁 활성화
    $('[data-toggle="tooltip"]').tooltip();
    
    // 모달 설정
    setupModals();
    
    // 폼 유효성 검사 설정
    setupFormValidation();
});

/**
 * 사이드바 활성 메뉴 설정
 */
function activateSidebar() {
    // 현재 페이지 경로 가져오기
    var currentPath = window.location.pathname;
    
    // 사이드바 메뉴 활성화
    $('.nav-sidebar a').each(function() {
        var link = $(this).attr('href');
        if (link && currentPath.indexOf(link) !== -1) {
            $(this).addClass('active');
            $(this).parents('.nav-item').addClass('menu-open');
            $(this).parents('.nav-treeview').prev().addClass('active');
        }
    });
}

/**
 * DataTables 초기화
 */
function initDataTables() {
    if ($.fn.DataTable) {
        $('.datatable').each(function() {
            if (!$.fn.DataTable.isDataTable(this)) {
                $(this).DataTable({
                    "responsive": true,
                    "autoWidth": false,
                    "language": {
                        "url": SERVER_URL + "/assets/plugins/datatables/ko.json"
                    }
                });
            }
        });
    }
}

/**
 * 모달 설정
 */
function setupModals() {
    // 모달 표시 이벤트
    $('.modal').on('show.bs.modal', function(e) {
        console.log('모달 표시:', $(this).attr('id'));
    });
    
    // 모달 숨김 이벤트
    $('.modal').on('hidden.bs.modal', function(e) {
        console.log('모달 숨김:', $(this).attr('id'));
        // 폼 초기화
        $(this).find('form').trigger('reset');
    });
}

/**
 * 폼 유효성 검사 설정
 */
function setupFormValidation() {
    $('form.needs-validation').on('submit', function(event) {
        if (this.checkValidity() === false) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        $(this).addClass('was-validated');
    });
}

/**
 * 차트 생성 함수 (모든 대시보드에서 사용)
 * @param {string} canvasId - 차트 캔버스 ID
 * @param {string} type - 차트 유형 ('line', 'bar', 'pie', 'doughnut', 'radar')
 * @param {Object} data - 차트 데이터
 * @param {Object} options - 차트 옵션 (선택적)
 * @returns {Chart|null} 생성된 차트 객체 또는 null
 */
function createChart(canvasId, type, data, options) {
    try {
        // Chart 라이브러리 존재 확인
        if (typeof Chart === 'undefined') {
            console.error('Chart.js 라이브러리가 로드되지 않았습니다.');
            return null;
        }
        
        // 캔버스 요소 확인
        var canvas = document.getElementById(canvasId);
        if (!canvas) {
            console.error('캔버스 요소를 찾을 수 없습니다:', canvasId);
            return null;
        }
        
        // 기본 옵션 설정
        var defaultOptions = {
            maintainAspectRatio: false,
            responsive: true
        };
        
        // 사용자 옵션과 기본 옵션 병합
        var chartOptions = $.extend(true, {}, defaultOptions, options || {});
        
        // 차트 생성
        var ctx = canvas.getContext('2d');
        return new Chart(ctx, {
            type: type,
            data: data,
            options: chartOptions
        });
    } catch (error) {
        console.error('차트 생성 중 오류 발생:', error);
        return null;
    }
}

/**
 * 포맷팅 함수 - 통화
 * @param {number} value - 포맷팅할 값
 * @param {string} currency - 통화 기호 (기본: '₹')
 * @returns {string} 포맷팅된 통화 문자열
 */
function formatCurrency(value, currency) {
    var symbol = currency || '₹';
    return symbol + ' ' + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

/**
 * 포맷팅 함수 - 숫자
 * @param {number} value - 포맷팅할 값
 * @returns {string} 포맷팅된 숫자 문자열
 */
function formatNumber(value) {
    return value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

/**
 * AJAX 요청 함수
 * @param {string} url - 요청 URL
 * @param {string} method - HTTP 메서드 (GET, POST, PUT, DELETE)
 * @param {Object} data - 요청 데이터 (선택적)
 * @param {function} successCallback - 성공 콜백 함수
 * @param {function} errorCallback - 오류 콜백 함수 (선택적)
 */
function ajaxRequest(url, method, data, successCallback, errorCallback) {
    $.ajax({
        url: url,
        type: method,
        data: data,
        dataType: 'json',
        success: function(response) {
            if (typeof successCallback === 'function') {
                successCallback(response);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX 요청 오류:', error);
            
            if (typeof errorCallback === 'function') {
                errorCallback(xhr, status, error);
            } else {
                // 기본 오류 처리
                alert('요청 처리 중 오류가 발생했습니다: ' + error);
            }
        }
    });
}
