<?php
session_start();

// データベース接続設定
$dbn = 'mysql:dbname=thanks_card;charset=utf8mb4;port=3306;host=localhost';
$dbUser = 'root'; 
$dbPassword = '';

try {
    $pdo = new PDO($dbn, $dbUser, $dbPassword);
} catch (PDOException $e) {
    echo 'データベースエラー: ' . $e->getMessage();
    exit;
}

// パスワード変更処理
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    $userEmail = $_SESSION['user_mail']; // ユーザーのメールアドレスをセッションから取得

    $stmt = $pdo->prepare("SELECT pass FROM user WHERE mail = :email");
    $stmt->bindValue(':email', $userEmail);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($currentPassword, $user['pass'])) {
        if ($newPassword === $confirmPassword) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare("UPDATE user SET pass = :new_password WHERE mail = :email");
            $updateStmt->bindValue(':new_password', $hashedPassword);
            $updateStmt->bindValue(':email', $userEmail);
            $updateStmt->execute();

            echo "パスワードが変更されました。";
        } else {
            echo "新しいパスワードと再入力パスワードが一致しません。";
        }
    } else {
        echo "現在のパスワードが正しくありません。";
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>パスワード変更</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">

    <script>
function togglePasswordVisibility(fieldId) {
    var passwordField = document.getElementById(fieldId);
    var toggleIcon = passwordField.nextElementSibling;

    if (passwordField.type === "password") {
        passwordField.type = "text";
        toggleIcon.classList.add("fa-eye-slash");
        toggleIcon.classList.remove("fa-eye");
    } else {
        passwordField.type = "password";
        toggleIcon.classList.add("fa-eye");
        toggleIcon.classList.remove("fa-eye-slash");
    }
}
</script>

</head>

<body>
        <header>
        <nav>
            <ul>
                <li><a href="mypage.php">マイページ</a></li>
                <li><a href="main2.php">ポイント管理</a></li>
                <li><a href="point_history.php">履歴</a></li>
                <li><a href="#">よくある質問</a></li>
                <li><a href="#">お問い合わせ</a></li>
                <div class="button_group">
                    <input type="button" onclick="location.href='top.php'" value="ログアウト">
                </div>
            </ul>
        </nav>
    </header>


    <div class="card">
        <h2>あなたの情報</h2>
    
    <form action="pass.php" method="POST">
<div class="password-field">
    <label for="current_password">現在のパスワード:</label>
    <input type="password" id="current_password" name="current_password" required>
    <i class="fas fa-eye" onclick="togglePasswordVisibility('current_password')"></i>
</div>

<div class="password-field">
    <label for="new_password">新しいパスワード:</label>
    <input type="password" id="new_password" name="new_password" required>
    <i class="fas fa-eye" onclick="togglePasswordVisibility('new_password')"></i>
</div>

<div class="password-field">
    <label for="confirm_password">新しいパスワード（再入力）:</label>
    <input type="password" id="confirm_password" name="confirm_password" required>
    <i class="fas fa-eye" onclick="togglePasswordVisibility('confirm_password')"></i>
</div>

        <input type="submit" value="変更">
    </form>
</div>
</body>
</html>
