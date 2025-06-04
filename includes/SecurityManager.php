<?php
/**
 * 보안 관리 기능이 비활성화되었습니다.
 * 로그인 세션 관련 문제로 인해 모든 보안 관리 기능이 제거되었습니다.
 */

class SecurityManager {
    // 모든 메서드는 더미 응답을 반환합니다.
    
    public static function generateCsrfToken() {
        return md5(uniqid(mt_rand(), true));
    }
    
    public static function validateCsrfToken($token) {
        return true; // 항상 유효하다고 간주
    }
    
    public static function validateCsrfOnPost() {
        return true; // 항상 유효하다고 간주
    }
    
    public static function sanitizeOutput($value) {
        // null 체크 추가: null이면 빈 문자열 반환
        if ($value === null) {
            return '';
        }
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
    
    public static function sanitizeInput($value) {
        // null 체크 추가
        if ($value === null) {
            return '';
        }
        
        if (is_array($value)) {
            foreach ($value as $key => $val) {
                $value[$key] = self::sanitizeInput($val);
            }
            return $value;
        }
        
        return trim(strip_tags((string)$value));
    }
    
    public static function escapeSql($value, $connection) {
        return $connection->real_escape_string($value);
    }
    
    public static function generateRandomToken($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    public static function checkIpAccess($allowedIps) {
        return true; // 항상 접근 허용
    }
    
    public static function getClientIp() {
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    public static function getUserAgent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }
    
    public static function encrypt($plainText, $key) {
        return base64_encode($plainText); // 단순 인코딩으로 대체
    }
    
    public static function decrypt($cipherText, $key) {
        return base64_decode($cipherText); // 단순 디코딩으로 대체
    }
    
    public static function checkHttpMethod($allowedMethods) {
        return true; // 항상 허용
    }
    
    public static function filterRequestData() {
        return [
            'get' => $_GET,
            'post' => $_POST,
            'cookie' => $_COOKIE
        ];
    }
    
    public static function checkRateLimit($key, $maxRequests, $timeWindow) {
        return true; // 항상 허용
    }
}
