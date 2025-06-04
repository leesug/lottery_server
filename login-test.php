<?php
// 임시 로그인 페이지 - 직접 세션을 설정하여 로그인을 시뮬레이션합니다
session_start();

// 로그인 처리
if (isset($_POST['login'])) {
    // 세션에 사용자 정보 저장 - 실제 DB 검증 없이 직접 관리자로 설정
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = '관리자';
    $_SESSION['email'] = 'admin@lotto.com';
    $_SESSION['user_role'] = 'admin';
    $_SESSION['last_activity'] = time();
    
    // 로그인 성공 메시지
    $success = "로그인 성공! 세션이 설정되었습니다.";
    
    // 대시보드로 리디렉션 (선택 사항)
    // header('Location: /server/dashboard/index.php');
    // exit;
}

// 대시보드로 이동
if (isset($_POST['goto_dashboard'])) {
    header('Location: /server/dashboard/index.php');
    exit;
}

// 세션 상태 확인
$loggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>테스트 로그인</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f0f0f0;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1, h2 {
            color: #333;
        }
        .info {
            background-color: #e7f3fe;
            border-left: 4px solid #2196F3;
            padding: 12px;
            margin: 15px 0;
        }
        .success {
            background-color: #ddffdd;
            border-left: 4px solid #4CAF50;
            padding: 12px;
            margin: 15px 0;
        }
        .warning {
            background-color: #ffffcc;
            border-left: 4px solid #ffeb3b;
            padding: 12px;
            margin: 15px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px 0;
        }
        button:hover {
            background-color: #45a049;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>로또 서버 테스트 로그인</h1>
        
        <?php if (isset($success)): ?>
            <div class="success">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <div class="info">
            <p><strong>이 페이지는 데이터베이스 연결 없이도 관리자 권한으로 로그인하는 테스트 도구입니다.</strong></p>
            <p>로그인 버튼을 누르면 세션에 관리자 정보가 직접 설정됩니다. 그 후 대시보드로 이동할 수 있습니다.</p>
        </div>
        
        <h2>현재 세션 상태</h2>
        <p><strong>로그인 상태:</strong> <?php echo $loggedIn ? '로그인됨' : '로그인되지 않음'; ?></p>
        
        <?php if ($loggedIn): ?>
            <table>
                <tr>
                    <th>세션 키</th>
                    <th>값</th>
                </tr>
                <?php foreach ($_SESSION as $key => $value): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($key); ?></td>
                        <td><?php echo htmlspecialchars(is_string($value) ? $value : var_export($value, true)); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
            
            <form method="post">
                <button type="submit" name="goto_dashboard">대시보드로 이동</button>
            </form>
        <?php else: ?>
            <form method="post">
                <button type="submit" name="login">관리자로 로그인</button>
            </form>
        <?php endif; ?>
        
        <h2>로그인 문제 해결을 위한 도움말</h2>
        <div class="warning">
            <p><strong>원래 로그인이 작동하지 않는 이유:</strong></p>
            <ol>
                <li>데이터베이스가 생성되지 않았거나 테이블이 없을 수 있습니다</li>
                <li>데이터베이스에 사용자 계정이 생성되지 않았을 수 있습니다</li>
                <li>config.php의 데이터베이스 연결 설정이 올바르지 않을 수 있습니다</li>
            </ol>
        </div>
        
        <h3>데이터베이스 초기화 방법</h3>
        <p>PhpMyAdmin(http://localhost/phpmyadmin)에서 다음 SQL을 실행하여 문제를 해결할 수 있습니다:</p>
        <pre>
CREATE DATABASE IF NOT EXISTS lotto_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lotto_system;

-- 사용자 테이블 생성
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'agent', 'user') NOT NULL DEFAULT 'user',
    status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 기본 관리자 계정 생성 (비밀번호: admin123)
INSERT INTO users (username, email, password, role) VALUES 
('관리자', 'admin@lotto.com', '$2y$12$QTJbh.3IMgXH4dX1EJJMeuOiSrKYQnMFiV9YqXwv59.lngEsZoJ6W', 'admin');
        </pre>
    </div>
</body>
</html>
