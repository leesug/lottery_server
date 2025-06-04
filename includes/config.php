<?php
/**
 * 로또 서버 설정 파일
 * 
 * 이 파일은 로또 서버 애플리케이션에 필요한 모든 설정을 포함합니다.
 */

// 오류 보고 설정
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 시간대 설정
date_default_timezone_set('Asia/Seoul');

// 데이터베이스 연결 설정
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'lotto_server');

// 애플리케이션 경로 설정
define('BASE_PATH', dirname(__DIR__));
define('API_PATH', BASE_PATH . '/api');
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('TEMPLATES_PATH', BASE_PATH . '/templates');
define('LOGS_PATH', BASE_PATH . '/logs');

// 세션 설정 (비활성화됨)
define('SESSION_TIMEOUT', 1800); // 세션 유효 시간 (초 단위, 기본 30분)
define('SESSION_NAME', 'LOTTO_SESSION'); // 세션 쿠키 이름

// 세션 관리 클래스 로드 (세션 관리 기능은 비활성화됨)
require_once __DIR__ . '/SessionManager.php';

// 통화 설정 로드
require_once __DIR__ . '/currency.php';

// 환경 설정
define('DEVELOPMENT_MODE', true); // 개발 모드 (true) 또는 프로덕션 모드 (false)

// 서버 URL을 동적으로 감지 (외부 접속 지원)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
define('SERVER_URL', $protocol . $host . '/server'); // 서버 URL

// API 응답 코드
define('API_SUCCESS', 200);
define('API_CREATED', 201);
define('API_BAD_REQUEST', 400);
define('API_UNAUTHORIZED', 401);
define('API_FORBIDDEN', 403);
define('API_NOT_FOUND', 404);
define('API_SERVER_ERROR', 500);

// 보안 설정
define('HASH_COST', 12); // bcrypt 해시 비용
define('APP_SECRET', 'your-secret-key-here'); // 애플리케이션 비밀 키 (보안을 위해 변경 필요)

// 로깅 설정
define('LOG_LEVEL', 'info'); // 로그 레벨 (debug, info, warning, error, critical)

// 페이지네이션 설정
define('ITEMS_PER_PAGE', 10);

// 파일 업로드 설정
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf']);
