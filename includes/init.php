<?php
/**
 * 통합 인클루드 파일
 * 
 * 이 파일은 모든 페이지에서 필요한 공통 파일들을 한 번에 인클루드합니다.
 * 순서가 중요: 설정 -> 세션 -> DB -> 함수
 */

// 설정 파일 포함
require_once __DIR__ . '/config.php';

// 세션 관리 파일 포함
require_once __DIR__ . '/session.php';

// 데이터베이스 연결 파일 포함
require_once __DIR__ . '/db.php';

// 공통 함수 파일 포함
require_once __DIR__ . '/functions.php';
