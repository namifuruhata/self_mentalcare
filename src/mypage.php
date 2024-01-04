<?php
// セッションがすでに開始していない場合のみ、session_start()を呼び出す
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$dbn ='mysql:dbname=thanks_card;charset=utf8mb4;port=3306;host=localhost';
$user = 'root';
$pwd = '';


try {
    $pdo = new PDO($dbn, $user, $pwd);
    // エラーモードを例外モードに設定
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    ob_clean(); // 既存の出力をクリア
    echo json_encode(["error" => $e->getMessage()]);
    exit;
}



// セッション変数からメールアドレスを取得
$email = isset($_SESSION['user_mail']) ? $_SESSION['user_mail'] : '';

if ($email) {
    $sql = 'SELECT * FROM user WHERE mail = :mail';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':mail', $email, PDO::PARAM_STR);

  try {
    $status = $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    // ユーザーが見つかった場合、名前をセッションに保存
    if ($user) {
      $_SESSION['user_name'] = $user['name'];
      $name = $user['name'];
    }
  } catch (PDOException $e) {
    echo json_encode(["sql error" => "{$e->getMessage()}"]);
    exit();
  }
} else {
  // セッション変数から名前を取得
  $name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
}

$output = ""; // これで変数を初期化します

// ユーザー名の更新処理
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_name') {
    $newName = $_POST['new_name']; // フォームから新しい名前を取得

    // データベースに接続し、名前を更新
    $updateSql = 'UPDATE user SET name = :new_name WHERE mail = :mail';
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->bindValue(':new_name', $newName, PDO::PARAM_STR);
    $updateStmt->bindValue(':mail', $email, PDO::PARAM_STR);

    try {
        $updateStmt->execute();

        // セッション変数も更新
        $_SESSION['user_name'] = $newName;

        // 更新した後にページをリダイレクトする
        header('Location: mypage.php');
        exit;
    } catch (PDOException $e) {
        $message = "データベースエラー: " . $e->getMessage();
    }
}

// データベースから名前を取得する処理を再度実行
if ($email) {
  $sql = 'SELECT * FROM user WHERE mail = :mail';
  $stmt = $pdo->prepare($sql);
  $stmt->bindValue(':mail', $email, PDO::PARAM_STR);
  $stmt->execute();
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($user) {
    $_SESSION['user_name'] = $user['name'];
    $name = $user['name'];
  }
}

// 退会処理
if (isset($_POST['action']) && $_POST['action'] == 'withdraw') {
    // ユーザーのIDを取得
    $stmt = $pdo->prepare("SELECT id FROM user WHERE mail = :email");
    $stmt->execute([':email' => $email]);
    $userId = $stmt->fetch(PDO::FETCH_ASSOC)['id'];

    if ($userId) {
        // card_idのリストを取得
        $stmt = $pdo->prepare("SELECT id FROM card WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        $cardIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // 各card_idについてexchange_historyとpoint_historyから削除
        foreach ($cardIds as $cardId) {
            $pdo->prepare("DELETE FROM exchange_history WHERE card_id = :card_id")->execute([':card_id' => $cardId]);
            $pdo->prepare("DELETE FROM point_history WHERE card_id = :card_id")->execute([':card_id' => $cardId]);
        }

        // cardテーブルからユーザーに関連するカードを削除
        $pdo->prepare("DELETE FROM card WHERE user_id = :user_id")->execute([':user_id' => $userId]);

        // 最後にユーザー自体を削除
        $pdo->prepare("DELETE FROM user WHERE id = :user_id")->execute([':user_id' => $userId]);
    }

    session_destroy();
    header('Location: top.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ポイントカード</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
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

    <?= $output ?>

    <div class="card">
        <h2>あなたの情報</h2>
        <form action="mypage.php" method="POST">
            <input type="hidden" name="action" value="update_name">
            名前: <input type="text" name="new_name" value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>"><br>
            メールアドレス: <input type="email" name="email" id="email-field" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" readonly><br>
            <input type="submit" value="更新">
        </form>
        <!-- パスワード変更へのリンクボタン -->
<form action="pass.php" method="get">
    <input type="submit" value="パスワード変更">
</form>


        <!-- 退会ボタン（モーダルを表示するためのボタン） -->
        <button id="withdraw-btn">退会する</button>

        <!-- モーダルの背景 -->
        <div id="modal-background"></div>

        <!-- モーダルウィンドウ -->
        <div id="modal" style="display:none;">
            <p>退会するとすべてのデータが削除されます。本当に退会しますか？</p>
            <button id="confirm-yes">はい</button>
            <button id="confirm-no">いいえ</button>

            <!-- 非表示の退会フォーム -->
            <form id="withdraw-form" action="mypage.php" method="POST" style="display: none;">
                <input type="hidden" name="action" value="withdraw">
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var withdrawBtn = document.getElementById('withdraw-btn');
            var modal = document.getElementById('modal');
            var modalBackground = document.getElementById('modal-background');
            var confirmYes = document.getElementById('confirm-yes');
            var confirmNo = document.getElementById('confirm-no');
            var withdrawForm = document.getElementById('withdraw-form');

            // 退会ボタンをクリックした時の動作
            withdrawBtn.addEventListener('click', function() {
                modal.style.display = 'block';
                modalBackground.style.display = 'block';
            });

            // 「はい」をクリックした時の動作
            confirmYes.addEventListener('click', function() {
                withdrawForm.submit();
            });

            // 「いいえ」または背景をクリックした時の動作
            confirmNo.addEventListener('click', closeModal);
            modalBackground.addEventListener('click', closeModal);

            function closeModal() {
                modal.style.display = 'none';
                modalBackground.style.display = 'none';
            }
        });
    </script>
</body>
</html>
