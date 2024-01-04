<?php
session_start(); // セッションを開始

ini_set('display_errors', 1);
error_reporting(E_ALL);


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $mail = $_POST['mail'];
    $pass = $_POST['pass'];

    // DB接続
    $dbn ='mysql:dbname=thanks_card;charset=utf8mb4;port=3306;host=localhost';
    $user = 'root';
    $pwd = '';

    try {
        $pdo = new PDO($dbn, $user, $pwd);
        $sql = "SELECT * FROM user WHERE mail = :mail";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':mail', $mail, PDO::PARAM_STR);
        $stmt->execute();

        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
     if (password_verify($pass, $row['pass'])) {
    $_SESSION['user_id'] = $row['id']; // ユーザーIDをセッションに保存
    $_SESSION['user_name'] = $row['name']; // ユーザー名をセッションに保存
    $_SESSION['user_mail'] = $mail; // メールアドレスをセッションに保存
    header('Location: main2.php'); // main2.phpにリダイレクト
    exit;

                exit;
            } else {
                $login_error = "パスワードが間違っています。";
            }
        } else {
            $login_error = "メールアドレスが見つかりません。";
        }
    } catch (PDOException $e) {
        $login_error = "データベースエラー: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
</head>
<body>
    <?php if (!empty($login_error)): ?>
        <p><?= htmlspecialchars($login_error, ENT_QUOTES); ?></p>
    <?php endif; ?>
    <div class="login_group">
    <form action="login.php" method="POST">
        <label for="mail">メールアドレス:</label>
        <input type="email" id="mail" name="mail" required><br>
        <div class="password-field">
            <label for="pass">パスワード:</label>
            <input type="password" id="pass" name="pass" required>
            <i class="fas fa-eye" onclick="togglePasswordVisibility('pass')"></i>
        </div>
        <input type="submit" value="ログイン">
    </form>
    </div>

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
</body>
</html>
