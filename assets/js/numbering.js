/**
 * 넘버링 관리 JavaScript
 * 
 * 넘버링 관리 페이지의 기능을 구현하는 JavaScript 코드
 */

$(document).ready(function() {
    console.log('넘버링 관리 스크립트 로드됨');

    // 새 번호 체계 추가 폼 제출
    $('#addFormatForm').submit(function(e) {
        e.preventDefault();
        console.log('새 번호 체계 추가 폼 제출');
        
        // 폼 데이터 수집
        var formData = $(this).serialize();
        
        // 폼 유효성 검증 (간단화)
        if ($('#name').val() === '' || $('#pattern').val() === '') {
            alert('이름과 패턴은 필수입니다.');
            return false;
        }
        
        // AJAX 요청 보내기
        $.ajax({
            url: '/server/api/lottery/manage_number_format.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                console.log('번호 체계 추가 응답:', response);
                
                if (response.status === 'success') {
                    // 성공 메시지 표시
                    alert(response.message);
                    // 모달 닫기
                    $('#addFormatModal').modal('hide');
                    // 페이지 새로고침
                    location.reload();
                } else {
                    // 오류 메시지 표시
                    alert('오류: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('번호 체계 추가 AJAX 오류:', error);
                console.error('응답 텍스트:', xhr.responseText);
                alert('서버 통신 오류가 발생했습니다. 다시 시도해주세요.');
            }
        });
    });
    
    // 번호 체계 수정 폼 제출
    $('#editFormatForm').on('submit', function(e) {
        e.preventDefault();
        console.log('번호 체계 수정 폼 제출');
        
        // 폼 데이터 수집
        var formData = $(this).serialize();
        
        // 폼 유효성 검증
        if (!validateFormatForm('#editFormatForm')) {
            return false;
        }
        
        // AJAX 요청 보내기
        $.ajax({
            url: '/server/api/lottery/manage_number_format.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                console.log('번호 체계 수정 응답:', response);
                
                if (response.status === 'success') {
                    // 성공 메시지 표시
                    showAlert('success', response.message);
                    // 모달 닫기
                    $('#editFormatModal').modal('hide');
                    // 페이지 새로고침
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    // 오류 메시지 표시
                    showAlert('danger', '오류: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('번호 체계 수정 AJAX 오류:', error);
                showAlert('danger', '서버 통신 오류가 발생했습니다. 다시 시도해주세요.');
            }
        });
    });
    
    // 번호 할당 폼 제출
    $('#assignNumbersForm').on('submit', function(e) {
        e.preventDefault();
        console.log('번호 할당 폼 제출');
        
        // 폼 데이터 수집
        var formData = $(this).serialize();
        
        // 폼 유효성 검증
        if (!validateAssignmentForm()) {
            return false;
        }
        
        // AJAX 요청 보내기
        $.ajax({
            url: '/server/api/lottery/assign_numbers.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                console.log('번호 할당 응답:', response);
                
                if (response.status === 'success') {
                    // 성공 메시지 표시
                    showAlert('success', response.message);
                    // 모달 닫기
                    $('#assignNumbersModal').modal('hide');
                    // 페이지 새로고침
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    // 오류 메시지 표시
                    showAlert('danger', '오류: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('번호 할당 AJAX 오류:', error);
                showAlert('danger', '서버 통신 오류가 발생했습니다. 다시 시도해주세요.');
            }
        });
    });
    
    // 번호 예약 폼 제출
    $('#reserveNumbersForm').on('submit', function(e) {
        e.preventDefault();
        console.log('번호 예약 폼 제출');
        
        // 폼 데이터 수집
        var formData = $(this).serialize();
        
        // 폼 유효성 검증
        if (!validateReservationForm()) {
            return false;
        }
        
        // AJAX 요청 보내기
        $.ajax({
            url: '/server/api/lottery/reserve_numbers.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                console.log('번호 예약 응답:', response);
                
                if (response.status === 'success') {
                    // 성공 메시지 표시
                    showAlert('success', response.message);
                    // 모달 닫기
                    $('#reserveNumbersModal').modal('hide');
                    // 페이지 새로고침
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    // 오류 메시지 표시
                    showAlert('danger', '오류: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('번호 예약 AJAX 오류:', error);
                showAlert('danger', '서버 통신 오류가 발생했습니다. 다시 시도해주세요.');
            }
        });
    });
    
    // 번호 체계 상세 보기 버튼 클릭
    $('.btn-view-format').click(function() {
        var id = $(this).data('id');
        console.log('번호 체계 상세 보기:', id);
        
        // AJAX로 번호 체계 상세 정보 조회
        $.ajax({
            url: '/server/api/lottery/get_number_format.php',
            type: 'GET',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                console.log('번호 체계 상세 정보 응답:', response);
                
                if (response.status === 'success') {
                    var format = response.data;
                    
                    // 모달에 데이터 채우기
                    $('#view_name').text(format.name);
                    $('#view_format_type').text(format.pattern || '-');
                    $('#view_description').text(format.description || '-');
                    $('#view_number_count').text('최소: ' + format.min_length + ', 최대: ' + format.max_length);
                    $('#view_number_range').text(format.allowed_characters || '-');
                    $('#view_prefix').text(format.prefix || '-');
                    $('#view_suffix').text(format.suffix || '-');
                    $('#view_example code').text(format.pattern || '-');
                    
                    var statusText = '알 수 없음';
                    var statusClass = 'secondary';
                    if (format.is_active == 1) {
                        statusText = '활성';
                        statusClass = 'success';
                    } else {
                        statusText = '비활성';
                        statusClass = 'warning';
                    }
                    
                    $('#view_status').html('<span class="badge badge-' + statusClass + '">' + statusText + '</span>');
                    $('#view_created_at').text(new Date(format.created_at).toLocaleString());
                    $('#view_updated_at').text(format.updated_at ? new Date(format.updated_at).toLocaleString() : '-');
                    
                    // 모달 표시
                    $('#viewFormatModal').modal('show');
                } else {
                    showAlert('danger', '오류: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('번호 체계 상세 조회 AJAX 오류:', error);
                showAlert('danger', '서버 통신 오류가 발생했습니다. 다시 시도해주세요.');
            }
        });
    });
    
    // 번호 체계 수정 버튼 클릭
    $('.btn-edit-format').click(function() {
        var id = $(this).data('id');
        console.log('번호 체계 수정:', id);
        
        // AJAX로 번호 체계 상세 정보 조회
        $.ajax({
            url: '/server/api/lottery/get_number_format.php',
            type: 'GET',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                console.log('번호 체계 상세 정보 응답:', response);
                
                if (response.status === 'success') {
                    var format = response.data;
                    
                    // 모달에 데이터 채우기
                    $('#edit_format_id').val(format.id);
                    $('#edit_name').val(format.name);
                    $('#edit_pattern').val(format.pattern);
                    $('#edit_description').val(format.description);
                    $('#edit_min_length').val(format.min_length);
                    $('#edit_max_length').val(format.max_length);
                    $('#edit_prefix').val(format.prefix);
                    $('#edit_suffix').val(format.suffix);
                    $('#edit_allowed_characters').val(format.allowed_characters);
                    
                    // 체크박스 설정
                    $('#edit_is_alphanumeric').prop('checked', format.is_alphanumeric == 1);
                    $('#edit_is_active').prop('checked', format.is_active == 1);
                    
                    // 모달 표시
                    $('#editFormatModal').modal('show');
                } else {
                    showAlert('danger', '오류: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('번호 체계 상세 조회 AJAX 오류:', error);
                showAlert('danger', '서버 통신 오류가 발생했습니다. 다시 시도해주세요.');
            }
        });
    });
    
    // 번호 예약 상세 보기 버튼 클릭
    $(document).on('click', '.btn-view-reservation', function() {
        var id = $(this).data('id');
        console.log('번호 예약 상세 보기:', id);
        
        // AJAX로 번호 예약 상세 정보 조회
        $.ajax({
            url: '/server/api/lottery/get_reservation.php',
            type: 'GET',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                console.log('번호 예약 상세 정보 응답:', response);
                
                if (response.status === 'success') {
                    var reservation = response.data;
                    
                    // 모달에 데이터 채우기
                    $('#view_reservation_format_name').text(reservation.format_name || '-');
                    
                    // 예약 번호 표시 - reserved_number 또는 numbers 필드 사용
                    var reservedNumber = reservation.reserved_number || reservation.numbers || '-';
                    $('#view_reservation_number code').text(reservedNumber);
                    
                    $('#view_reservation_reason').text(reservation.reason || '-');
                    
                    var statusText = '알 수 없음';
                    var statusClass = 'secondary';
                    switch (reservation.status) {
                        case 'active':
                            statusText = '활성';
                            statusClass = 'success';
                            break;
                        case 'expired':
                            statusText = '만료됨';
                            statusClass = 'warning';
                            break;
                        case 'cancelled':
                            statusText = '취소됨';
                            statusClass = 'danger';
                            break;
                    }
                    
                    $('#view_reservation_status').html('<span class="badge badge-' + statusClass + '">' + statusText + '</span>');
                    $('#view_reservation_by').text(reservation.reserved_by_name || '-');
                    $('#view_reservation_created_at').text(new Date(reservation.created_at).toLocaleString());
                    $('#view_reservation_updated_at').text(reservation.updated_at ? new Date(reservation.updated_at).toLocaleString() : '-');
                    
                    // 모달 표시
                    $('#viewReservationModal').modal('show');
                } else {
                    showAlert('danger', '오류: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('번호 예약 상세 조회 AJAX 오류:', error);
                showAlert('danger', '서버 통신 오류가 발생했습니다. 다시 시도해주세요.');
            }
        });
    });
    
    // 할당 취소 버튼 클릭
    $(document).on('click', '.btn-cancel-assignment', function(e) {
        e.preventDefault();
        
        var id = $(this).data('id');
        console.log('번호 할당 취소:', id);
        
        if (confirm('이 번호 할당을 취소하시겠습니까? 이 작업은 취소할 수 없습니다.')) {
            // AJAX로 할당 취소 처리
            $.ajax({
                url: '/server/api/lottery/cancel_issue.php',
                type: 'POST',
                data: {
                    assignment_id: id
                },
                dataType: 'json',
                success: function(response) {
                    console.log('할당 취소 응답:', response);
                    
                    if (response.status === 'success') {
                        showAlert('success', response.message);
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showAlert('danger', '오류: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('할당 취소 AJAX 오류:', error);
                    showAlert('danger', '서버 통신 오류가 발생했습니다. 다시 시도해주세요.');
                }
            });
        }
    });
    
    // 예약 취소 버튼 클릭
    $(document).on('click', '.btn-cancel-reservation', function(e) {
        e.preventDefault();
        
        var id = $(this).data('id');
        console.log('번호 예약 취소:', id);
        
        if (confirm('이 번호 예약을 취소하시겠습니까? 이 작업은 취소할 수 없습니다.')) {
            // AJAX로 예약 취소 처리
            $.ajax({
                url: '/server/api/lottery/cancel_reservation.php',
                type: 'POST',
                data: {
                    reservation_id: id
                },
                dataType: 'json',
                success: function(response) {
                    console.log('예약 취소 응답:', response);
                    
                    if (response.status === 'success') {
                        showAlert('success', response.message);
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showAlert('danger', '오류: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('예약 취소 AJAX 오류:', error);
                    showAlert('danger', '서버 통신 오류가 발생했습니다. 다시 시도해주세요.');
                }
            });
        }
    });
    
    // 유틸리티 함수
    
    // 알림 메시지 표시 함수
    function showAlert(type, message) {
        var alertHtml = '<div class="alert alert-' + type + ' alert-dismissible fade show">' +
            message +
            '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
            '<span aria-hidden="true">&times;</span>' +
            '</button>' +
            '</div>';
        
        // 기존 알림 제거
        $('.alert').remove();
        
        // 알림 표시
        $('.content-header').after(alertHtml);
        
        // 3초 후 자동으로 사라지게 설정
        setTimeout(function() {
            $('.alert').alert('close');
        }, 3000);
    }
    
    // 번호 체계 폼 유효성 검증
    function validateFormatForm(formSelector) {
        var formValid = true;
        
        // 필수 필드 검증
        $(formSelector + ' [required]').each(function() {
            if (!$(this).val()) {
                $(this).addClass('is-invalid');
                formValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        // 길이 필드 검증
        var minLength = parseInt($(formSelector + ' [name="min_length"]').val());
        var maxLength = parseInt($(formSelector + ' [name="max_length"]').val());
        
        if (minLength > maxLength) {
            $(formSelector + ' [name="min_length"]').addClass('is-invalid');
            $(formSelector + ' [name="max_length"]').addClass('is-invalid');
            showAlert('danger', '최소 길이는 최대 길이보다 클 수 없습니다.');
            formValid = false;
        }
        
        return formValid;
    }
    
    // 번호 할당 폼 유효성 검증
    function validateAssignmentForm() {
        var formValid = true;
        
        // 필수 필드 검증
        $('#assignNumbersForm [required]').each(function() {
            if (!$(this).val()) {
                $(this).addClass('is-invalid');
                formValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        // 수량 필드 검증
        var quantity = parseInt($('#quantity').val());
        
        if (isNaN(quantity) || quantity <= 0) {
            $('#quantity').addClass('is-invalid');
            showAlert('danger', '수량은 1 이상의 숫자여야 합니다.');
            formValid = false;
        }
        
        return formValid;
    }
    
    // 번호 예약 폼 유효성 검증
    function validateReservationForm() {
        var formValid = true;
        
        // 필수 필드 검증
        $('#reserveNumbersForm [required]').each(function() {
            if (!$(this).val()) {
                $(this).addClass('is-invalid');
                formValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        return formValid;
    }
});
