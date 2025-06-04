/**
 * 로그인 페이지 자바스크립트
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Login page loaded');
    
    // 로그인 폼 유효성 검사
    const loginForm = document.querySelector('.login-form');
    
    if (loginForm) {
        loginForm.addEventListener('submit', function(event) {
            console.log('Login form submitted');
            
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value.trim();
            
            let isValid = true;
            let errorMessage = '';
            
            // 이메일 유효성 검사
            if (!email) {
                isValid = false;
                errorMessage = '이메일을 입력해주세요.';
            } else if (!isValidEmail(email)) {
                isValid = false;
                errorMessage = '유효한 이메일 형식이 아닙니다.';
            }
            
            // 비밀번호 유효성 검사
            if (!password) {
                isValid = false;
                errorMessage = '비밀번호를 입력해주세요.';
            }
            
            if (!isValid) {
                event.preventDefault();
                
                // 에러 메시지 표시
                const existingAlert = document.querySelector('.alert');
                if (existingAlert) {
                    existingAlert.textContent = errorMessage;
                    existingAlert.style.display = 'block';
                } else {
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-danger';
                    alertDiv.textContent = errorMessage;
                    
                    const loginContainer = document.querySelector('.login-container');
                    const loginHeader = document.querySelector('.login-header');
                    loginContainer.insertBefore(alertDiv, loginHeader.nextSibling);
                }
            }
        });
    }
    
    // 에러 메시지 자동 닫기
    const alertMessages = document.querySelectorAll('.alert');
    
    if (alertMessages.length > 0) {
        alertMessages.forEach(function(alert) {
            setTimeout(function() {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 500);
            }, 5000);
        });
    }
    
    // 세션 만료 메시지가 있으면 5초 후 숨기기
    const expiredMessage = document.querySelector('.alert-warning');
    
    if (expiredMessage) {
        setTimeout(function() {
            expiredMessage.style.opacity = '0';
            setTimeout(function() {
                expiredMessage.style.display = 'none';
            }, 500);
        }, 5000);
    }
});

/**
 * 이메일 형식 검사
 * 
 * @param {string} email 검사할 이메일
 * @return {boolean} 유효성 여부
 */
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}
