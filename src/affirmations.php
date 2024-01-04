<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    // ユーザーがログインしていない場合はログインページにリダイレクト
    header('Location: login.php');
    exit;
}

$dbn ='mysql:dbname=thanks_card;charset=utf8mb4;port=3306;host=localhost';
$user = 'root';
$pwd = '';


try {
    $pdo = new PDO($dbn, $user, $pwd);
} catch (PDOException $e) {
    exit("データベース接続エラー: " . $e->getMessage());
}

// アファメーションの投稿
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $message = $_POST['message'];
    $userId = $_SESSION['user_id']; // セッションからユーザーIDを取得

    $stmt = $pdo->prepare("INSERT INTO affirmations (user_id, message) VALUES (:user_id, :message)");
    $stmt->execute([':user_id' => $userId, ':message' => $message]);


}

// アファメーションをデータベースから取得
$stmt = $pdo->query("SELECT * FROM affirmations ORDER BY created_at DESC");
$affirmations = $stmt->fetchAll(PDO::FETCH_ASSOC);


// アファメーションとユーザー情報をデータベースから取得
$stmt = $pdo->query("
    SELECT affirmations.*, user.name AS user_name
    FROM affirmations
    JOIN user ON affirmations.user_id = user.id
    ORDER BY created_at DESC
");
$affirmations = $stmt->fetchAll(PDO::FETCH_ASSOC);


foreach ($affirmations as $affirmation) {
    echo "<div>";
    echo "<p>" . htmlspecialchars($affirmation['message'], ENT_QUOTES) . "</p>";
    echo "<p>投稿者: " . htmlspecialchars($affirmation['user_name'], ENT_QUOTES) . "</p>";
    echo "<p>投稿日時: " . $affirmation['created_at'] . "</p>";
    echo "<p>いいね: " . $affirmation['likes'] . "</p>";
    // ここに「いいね」ボタンを追加
    echo "</div>";
}


?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>アファメーションカード</title>
</head>
<body>
    <form action="affirmations.php" method="post">
        <label for="message">アファメーション:</label>
        <textarea id="message" name="message" required></textarea><br>
        <input type="submit" value="投稿">
    </form>
</body>
</html>
