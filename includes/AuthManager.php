<?php
/**
 * 인증 관리 기능이 비활성화되었습니다.
 * 로그인 세션 관련 문제로 인해 모든 인증 관리 기능이 제거되었습니다.
 */

class AuthManager {
    // 모든 메서드는 더미 응답을 반환합니다.
    
    public static function login($email, $password) {
        // 항상 로그인 성공으로 간주
        return [
            'id' => 1,
            'username' => 'admin',
            'email' => 'admin@example.com',
            'role' => 'admin'
        ];
    }
    
    public static function logLoginAttempt($email, $status, $userId = null) {
        return true;
    }
    
    public static function logout() {
        return true;
    }
    
    public static function registerUser($username, $email, $password, $role = 'user') {
        return 1; // 항상 ID 1 반환
    }
    
    public static function resetPassword($userId, $newPassword) {
        return true;
    }
    
    public static function isLoggedIn() {
        return true; // 항상 로그인됨으로 간주
    }
    
    public static function isAdmin() {
        return true; // 항상 관리자로 간주
    }
    
    public static function isAgent() {
        return true; // 항상 에이전트로 간주
    }
    
    public static function hasPermission($requiredRole) {
        return true; // 항상 권한 있음으로 간주
    }
    
    public static function checkAuth($requiredRole = null) {
        return true; // 항상 인증됨으로 간주
    }
    
    public static function generatePasswordResetToken($email) {
        return md5(uniqid(mt_rand(), true));
    }
    
    public static function validatePasswordResetToken($token) {
        return 1; // 항상 사용자 ID 1 반환
    }
}
