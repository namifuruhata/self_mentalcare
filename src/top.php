<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>login</title>
     <link rel="stylesheet" href="style.css">
     <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Firebase SDK の読み込み -->
    <script src="https://www.gstatic.com/firebasejs/8.6.1/firebase-app.js"></script>
    <script src="https://www.gstatic.com/firebasejs/8.6.1/firebase-auth.js"></script>

</head>
<body>
  <header>
  <nav>
    <ul>
      <li><a href="#">機能紹介</a></li>
      <li><a href="#">お客様の声</a></li>
      <li><a href="#">料金</a></li>
      <li><a href="#">よくある質問</a></li>
      <li><a href="#">お問い合わせ</a></li>
      <div class="button_group">
             <input type="button" onclick="location.href='login.php'" value="ログイン">
              <input type="button" onclick="location.href='registration.html'" value="新規登録">
              </div>
  </nav>
</header>

</body>
</html>
