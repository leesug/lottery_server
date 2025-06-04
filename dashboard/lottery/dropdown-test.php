<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>드롭다운 메뉴 테스트</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
  <style>
    body {
      padding: 50px;
    }
    .container {
      max-width: 600px;
    }
    .btn-group {
      margin-bottom: 20px;
    }
    #result {
      margin-top: 20px;
      padding: 10px;
      border: 1px solid #ddd;
      background-color: #f9f9f9;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>드롭다운 메뉴 테스트</h1>
    
    <div class="btn-group">
      <button type="button" class="btn btn-warning dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="fas fa-cog"></i> 설정
      </button>
      <div class="dropdown-menu">
        <a class="dropdown-item btn-change-status" data-id="1" data-status="ready" href="#">
          <i class="fas fa-check text-info"></i> 준비 상태로 변경
        </a>
        <a class="dropdown-item btn-delete-plan" data-id="1" href="#">
          <i class="fas fa-trash text-danger"></i> 계획 삭제
        </a>
      </div>
    </div>
    
    <div id="result">
      결과가 여기에 표시됩니다.
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
  <script>
    $(document).ready(function() {
      // 상태 변경 버튼 이벤트
      $('body').on('click', '.btn-change-status', function(e) {
        e.preventDefault();
        console.log('준비 상태로 변경 버튼 클릭!');
        var id = $(this).data('id');
        var status = $(this).data('status');
        $('#result').html('<div class="alert alert-info">계획 #' + id + '의 상태를 "' + status + '"(으)로 변경합니다.</div>');
      });
      
      // 계획 삭제 버튼 이벤트
      $('body').on('click', '.btn-delete-plan', function(e) {
        e.preventDefault();
        console.log('계획 삭제 버튼 클릭!');
        var id = $(this).data('id');
        $('#result').html('<div class="alert alert-danger">계획 #' + id + '를 삭제합니다.</div>');
      });
    });
  </script>
</body>
</html>
