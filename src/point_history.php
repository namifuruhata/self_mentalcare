<?php

error_reporting(E_ALL); // すべてのエラーを表示
ini_set('display_errors', 1);

session_start();

// データベース接続
$dbn ='mysql:dbname=thanks_card;charset=utf8mb4;port=3306;host=localhost';
$user = 'root';
$pwd = '';

try {
    $pdo = new PDO($dbn, $user, $pwd);
} catch (PDOException $e) {
    echo "データベースエラー: " . $e->getMessage();
    exit();
}

// セッションからユーザーIDを取得
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// 18ポイントに達したカードを取得する処理（統合）
$sql = "SELECT c.id, c.card_name, c.total_point, eh.exchange_date, eh.created_at 
        FROM card c
        LEFT JOIN exchange_history eh ON c.id = eh.card_id
        WHERE c.user_id = :userId AND c.total_point = 18
        AND (eh.exchange_date IS NULL OR eh.exchange_date = eh.created_at)";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
$stmt->execute();
$completedCards = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt->execute();
$completedCards = $stmt->fetchAll(PDO::FETCH_ASSOC);

// カード履歴の検索処理
$cardHistory = [];
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'search_card_history') {
    $cardName = $_POST['card_name'];
    $sql = "SELECT ph.point, ph.updated_at FROM point_history ph
            INNER JOIN card c ON ph.card_id = c.id
            WHERE c.user_id = :userId AND c.card_name = :cardName";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':cardName', $cardName, PDO::PARAM_STR);
    $stmt->execute();
    $cardHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ユーザーカード一覧を表示するためのクエリ
$sql = "SELECT c.card_name, c.total_point, u.name AS name
        FROM card c
        INNER JOIN user u ON c.user_id = u.id
        WHERE c.partner_mail = :userMail";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':userMail', $_SESSION['user_mail'], PDO::PARAM_STR);
$stmt->execute();
$userCards = $stmt->fetchAll(PDO::FETCH_ASSOC);

// exchange_historyテーブルに新しいレコードを挿入
foreach ($completedCards as $card) {
    $cardId = $card['id'];
    $sql = "SELECT COUNT(*) AS count FROM exchange_history WHERE card_id = :cardId";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':cardId', $cardId, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['count'] == 0) {
        $created_at = date('Y-m-d H:i:s');
        $deadline = date('Y-m-d H:i:s', strtotime($created_at . '+1 week'));
        $exchange_date = date('Y-m-d H:i:s');

        // デバッグ情報を出力
        var_dump($cardId, $created_at, $deadline, $exchange_date);

        $sql = "INSERT INTO exchange_history (card_id, created_at, deadline, exchange_date) 
                VALUES (:cardId, :created_at, :deadline, :exchange_date)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':cardId', $cardId, PDO::PARAM_INT);
        $stmt->bindValue(':created_at', $created_at, PDO::PARAM_STR);
        $stmt->bindValue(':deadline', $deadline, PDO::PARAM_STR);
        $stmt->bindValue(':exchange_date', $exchange_date, PDO::PARAM_STR);
        $stmt->execute();
    }
}


// カードの交換処理
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'exchange_card') {
    $cardId = $_POST['card_id'];
    $sql = "UPDATE exchange_history SET exchange_date = NOW() WHERE card_id = :cardId";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':cardId', $cardId, PDO::PARAM_INT);
    $stmt->execute();
    header("Location: point_history.php");
    exit();
}

?>


<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ポイント履歴の表示</title>
     <link rel="stylesheet" href="style.css">
     <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<script>
function hideCard(cardId) {
    var cardElement = document.getElementById('card-' + cardId);
    if (cardElement) {
        cardElement.style.display = 'none'; // カードを非表示にする
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
  </nav>
</header>

     <!-- 検索結果の表示 -->
<?php if (!empty($cardHistory)): ?>
    <div class="card">
        <h3>カード履歴</h3>
        <ul>
            <?php foreach ($cardHistory as $history): ?>
                <li>ポイント: <?php echo htmlspecialchars($history['point']); ?>, 更新日時: <?php echo htmlspecialchars($history['updated_at']); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
<div class="card">
    <h2>カード履歴検索</h2>
    <form action="point_history.php" method="post">
        <input type="hidden" name="action" value="search_card_history">
        カードの名前: <input type="text" name="card_name" required><br>
        <input type="submit" value="検索">
    </form>
</div>
<!-- 18ポイントに達したカードの表示 -->
<div class="card">
    <h2>18ポイント貯まったカード</h2>
    <ul id="completed-cards-list">
        <?php if (!empty($completedCards)): ?>
            <?php foreach ($completedCards as $card): ?>
                <li id="card-<?php echo htmlspecialchars($card['id']); ?>">
                    カード名: <?php echo htmlspecialchars($card['card_name']); ?>
                    <form action="point_history.php" method="post">
                        <input type="hidden" name="action" value="exchange_card">
                        <input type="hidden" name="card_id" value="<?php echo htmlspecialchars($card['id']); ?>">
                        <input type="submit" value="交換">
                    </form>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <li>18ポイントに達したカードはありません。</li>
        <?php endif; ?>
    </ul>
</div>


<div class="card">
    <h2>ユーザーカード一覧</h2>
    <ul id="user-cards-list">
        <?php foreach ($userCards as $card): ?>
 <li>
                カード名: <?php echo htmlspecialchars($card['card_name']); ?>,
                トータルポイント: <?php echo htmlspecialchars($card['total_point']); ?>,
                発行者の名前: <?php echo htmlspecialchars($card['name']); ?>
            </li>
        <?php endforeach; ?>
    </ul>

</div>



</body>
</html>
