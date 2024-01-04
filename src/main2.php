<?php


session_start(); 

$dbn ='mysql:dbname=thanks_card;charset=utf8mb4;port=3306;host=localhost';
$user = 'root';
$pwd = '';

try {
    $pdo = new PDO($dbn, $user, $pwd);
   
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

// 新しいカードの登録処理
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'create_card') {
    $cardName = $_POST['card_name'];
    $partnerName = $_POST['partner_name'];
    $partnerEmail = $_POST['partner_email'];
    $userId = $user['id']; // ログインしているユーザーのID

    // 同じ名前のカードがログイン中のユーザーによって既に登録されているかチェック
    $checkCardSql = 'SELECT * FROM card WHERE user_id = :user_id AND card_name = :card_name';
    $checkCardStmt = $pdo->prepare($checkCardSql);
    $checkCardStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $checkCardStmt->bindValue(':card_name', $cardName, PDO::PARAM_STR);
    $checkCardStmt->execute();

    if ($checkCardStmt->fetch()) {
        // 同じ名前のカードが存在する場合
        echo '同じ名前のカードは既に存在します。';
        exit();
    }
    // データベースに新しいカードを登録
    $insertSql = 'INSERT INTO card (user_id, card_name, total_point, partner_name, partner_mail, created_at) VALUES (:user_id, :card_name, 0, :partner_name, :partner_email, NOW())';
    $insertStmt = $pdo->prepare($insertSql);
    $insertStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $insertStmt->bindValue(':card_name', $cardName, PDO::PARAM_STR);
    $insertStmt->bindValue(':partner_name', $partnerName, PDO::PARAM_STR);
    $insertStmt->bindValue(':partner_email', $partnerEmail, PDO::PARAM_STR);

    $insertStmt->execute();
    $error = $insertStmt->errorInfo();
    if ($error[0] == "00000") {
        // カード作成に成功した場合、ページをリダイレクト
        header('Location: main2.php');
        exit;
    
    } else {
        $registerMessage = "新しいカードが登録されました。";
    }
} 


ini_set('display_errors', 'On');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$message = ''; // メッセージを格納する変数
$userPoints = 0; // ユーザーのポイント
$found = false; // ユーザーが見つかったかどうか
$registerMessage = ''; // ユーザー登録時のメッセージ


// セッション変数からユーザーIDを取得
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

if ($userId) {
// データベースからユーザーのカード情報を取得（18ポイント未満のみ）
$sql = 'SELECT id, card_name FROM card WHERE user_id = :user_id AND total_point < 18';
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
$stmt->execute();
$cards = $stmt->fetchAll(PDO::FETCH_ASSOC);



}

// ユーザーカード一覧取得
$userCards = [];
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'register') {
        // ユーザー登録の処理
        // $name = $_POST['name'];
        // $email = $_POST['email'];
         $notificationEmail = $_POST['notification_email']; // 通知用メールアドレスを取得

        // 重複チェック
        $isDuplicate = false;
        $file = fopen('data/users.csv', 'r');
        while ($row = fgetcsv($file)) {
            if ($row[0] == $name && $row[1] == $email) {
                $isDuplicate = true;
                break;
            }
        }
        fclose($file);

        if ($isDuplicate) {
            $registerMessage = "同じ名前のユーザーがこのメールアドレスで既に存在します。<br>";
        } else {
            // CSVファイルに追加
            $file = fopen('data/users.csv', 'a');
            fputcsv($file, [$name, $email, 0, $notificationEmail]);  // 初期ポイントは0
            fclose($file);

            $registerMessage = "登録が完了しました。<br>";
        }
    }

}
// ユーザーカード一覧を取得
if (isset($_POST['action']) && $_POST['action'] == 'fetch_cards') {
    $userId = $user['id']; // ログインしているユーザーのID
    $cardSql = 'SELECT * FROM card WHERE user_id = :user_id AND total_point <= 18';
    $cardStmt = $pdo->prepare($cardSql);
    $cardStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $cardStmt->execute();
    $cards = $cardStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($cards);
    exit;

    
}


// ポイント情報を取得する処理を追加
if (isset($_POST['action']) && $_POST['action'] == 'check_balance') {
    $name = $_POST['name'];
    $email = $_POST['email'];

    $file = fopen('data/users.csv', 'r');
    while ($row = fgetcsv($file)) {
        if ($row[0] == $name && $row[1] == $email) {
            $userPoints = $row[2];
            $found = true;
            break;
        }
    }
    fclose($file);

    if ($found) {
        // ユーザーのポイント情報を返す
        echo json_encode(['points' => $userPoints]);
        exit;
    }
}


// カードの詳細情報を取得する処理
if (isset($_POST['action']) && $_POST['action'] == 'get_card_details') {
    $cardId = $_POST['card_id'];

    $cardSql = 'SELECT * FROM card WHERE id = :card_id';
    $cardStmt = $pdo->prepare($cardSql);
    $cardStmt->bindValue(':card_id', $cardId, PDO::PARAM_INT);
    $cardStmt->execute();
    $cardDetails = $cardStmt->fetch(PDO::FETCH_ASSOC);

    if ($cardDetails) {
        echo json_encode($cardDetails);
    } else {
        echo json_encode(['error' => 'Card not found']);
    }
    exit;
}
// ポイントを加算する処理
if (isset($_POST['action']) && $_POST['action'] == 'add_points') {
    $cardId = $_POST['card_id'];
    $pointsToAdd = $_POST['points'];

    // 変数の値を確認
    var_dump($cardId);
    var_dump($pointsToAdd);

  $updateSql = 'UPDATE card SET total_point = total_point + :points WHERE id = :card_id';
$updateStmt = $pdo->prepare($updateSql);
$updateStmt->bindValue(':points', $pointsToAdd, PDO::PARAM_INT);
$updateStmt->bindValue(':card_id', $cardId, PDO::PARAM_INT);
    $updateStmt->execute();

    // point_history テーブルに記録を追加
    $historySql = 'INSERT INTO point_history (card_id, point, updated_at) VALUES (:card_id, :point, NOW())';
    $historyStmt = $pdo->prepare($historySql);
    $historyStmt->bindValue(':card_id', $cardId, PDO::PARAM_INT);
    $historyStmt->bindValue(':point', $pointsToAdd, PDO::PARAM_INT);

    try {
        $historyStmt->execute();
        echo json_encode(['message' => 'Points and history added successfully']);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'History update failed: ' . $e->getMessage()]);
        exit;
    }
}

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
        header('Location: main2.php');
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
  <script>

    

document.addEventListener('DOMContentLoaded', function() {
    var email = '<?php echo $email; ?>';
    if (email) {
        fetchUserCards(email);
    }
});

function fetchUserCards(email) {
    fetch('main2.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=fetch_cards&email=' + encodeURIComponent(email)
    })
    .then(response => response.json())
    .then(cards => {
        var userCardsList = document.getElementById('user-cards-list');
        userCardsList.innerHTML = '';
        cards.forEach(function(card) {
            var cardItem = document.createElement('li');
            var cardLink = document.createElement('a');
            cardLink.href = 'javascript:void(0);';
            cardLink.className = 'check-card';
            cardLink.setAttribute('data-id', card.id);
            cardLink.textContent = card.card_name;
            cardLink.addEventListener('click', function() {
                fetchCardDetails(card.id);
            });
            cardItem.appendChild(cardLink);
            userCardsList.appendChild(cardItem);
        });
    })
    .catch(error => {
        console.error('Error:', error);
    });
}


        function fetchCardDetails(cardId) {
            fetch('main2.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_card_details&card_id=' + encodeURIComponent(cardId)
})
    .then(response => response.json())
    .then(cardDetails => {
        if (cardDetails.error) {
            console.error(cardDetails.error);
        } else {
            displayCardDetails(cardDetails);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

var pointCardContainer = document.querySelector('#point-card-container');
if (pointCardContainer) {
    var slots = pointCardContainer.querySelectorAll('.point-slot');
    slots.forEach(function(slot) {
            slot.addEventListener('click', function() {
                var currentPoints = pointCardContainer.querySelectorAll('.stamped').length;
                var slotNumber = parseInt(this.getAttribute('data-point-number'), 10);
                if (!this.classList.contains('stamped') && slotNumber === currentPoints + 1) {
                    addPoint(cardDetails.id, 1);
                }
            });
        });
}

        function displayCardDetails(cardDetails) {
            var pointCardHtml = '<div class="point-card">';
            pointCardHtml += '<div class="card-name">' + cardDetails.card_name + 'カード</div>';
            pointCardHtml += '<div class="point-grid">';
            for (var i = 1; i <= 18; i++) {
                pointCardHtml += '<div class="point-slot ' + (i <= cardDetails.total_point ? 'stamped' : '') + '" data-point-number="' + i + '" data-card-id="' + cardDetails.id + '">' + i + '</div>';
            }
            pointCardHtml += '</div></div>';

            var pointCardContainer = document.querySelector('#point-card-container');
            pointCardContainer.innerHTML = pointCardHtml;

    var slots = pointCardContainer.querySelectorAll('.point-slot');
    slots.forEach(function(slot) {
        slot.addEventListener('click', function() {
            if (!this.classList.contains('stamped')) {
                this.classList.add('stamped'); // スタンプの見た目を即時更新
                addPoint(cardDetails.id, 1); // 1ポイント加算のリクエスト
            }
        });
    });
}

   // 18ポイントに達した場合、モーダルを表示
    if (cardDetails.total_point >= 18) {
        showModal();
    }


// モーダルポップアップを表示する関数
function showModal() {
    var modal = document.getElementById('modal');
    modal.style.display = 'block';
}

// モーダルポップアップを閉じる関数
function closeModal() {
    var modal = document.getElementById('modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// 画面外のクリックでモーダルを閉じる処理
window.onclick = function(event) {
    var modal = document.getElementById('modal');
    if (event.target == modal) {
        closeModal();
    }
}

function addPoint(cardId, pointsToAdd) {
    fetch('main2.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=add_points&card_id=' + encodeURIComponent(cardId) + '&points=' + pointsToAdd
    })
    .then(response => response.json())
      .then(data => {
        if (data.message === 'Points added successfully') {
            var pointCardContainer = document.querySelector('#point-card-container');
            var currentPoints = pointCardContainer.querySelectorAll('.stamped').length;
            var nextSlot = pointCardContainer.querySelector('.point-slot[data-point-number="' + (currentPoints + 1) + '"]');
            if (nextSlot) {
                nextSlot.classList.add('stamped');
                nextSlot.innerHTML = '<img src="img/point.png">'; // スタンプ画像のパスに注意
            }
        } else {
            console.error('Error: Failed to add points');
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// スロットクリック時の処理を更新
var slots = pointCardContainer.querySelectorAll('.point-slot');
slots.forEach(function(slot) {
    slot.addEventListener('click', function() {
        var currentPoints = pointCardContainer.querySelectorAll('.stamped').length;
        var slotNumber = parseInt(this.getAttribute('data-point-number'), 10);
        if (!this.classList.contains('stamped') && slotNumber === currentPoints + 1) {
            addPoint(cardDetails.id, 1);
        }
    });
});



// スタンプの見た目を更新する関数
function updatePointSlotVisual(cardId, pointsToAdd) {
    var cardContainer = document.querySelector(`[data-card-id='${cardId}']`).parentNode;
    var slots = cardContainer.querySelectorAll('.point-slot:not(.stamped)');
    
    for (var i = 0; i < pointsToAdd && i < slots.length; i++) {
        slots[i].classList.add('stamped');
    }
}




        document.addEventListener('DOMContentLoaded', function() {
            var email = '<?php echo $email; ?>';
            fetchUserCards(email);
        });
    </script>
    
</head>

<body>
      <header>
  <nav>
    <ul>
      <li><a href="mypage.php">マイページ</a></li>
            <li><a href="affirmations.php">アファメーション</a></li>
      <li><a href="main2.php">ポイント管理</a></li>
      <li><a href="point_history.php">履歴</a></li>
      <li><a href="#">よくある質問</a></li>
      <li><a href="#">お問い合わせ</a></li>
      <div class="button_group">
              <input type="button" onclick="location.href='top.php'" value="ログアウト">
 </div>
  </nav>
</header>

      <?= $output ?>



    <div class="card">
        <h2>ポイントカード登録</h2>
        <form action="main2.php" method="POST">
        <input type="hidden" name="action" value="create_card">
        カードの名前: <input type="text" name="card_name" required><br>
        相手の名前: <input type="text" name="partner_name" required><br>
        相手のメールアドレス: <input type="email" name="partner_email" required><br>
        <input type="submit" value="カードを作成">
 </form>
    </div>



    <div class="card">
        <h2>ポイントカード一覧</h2>
        <ul id="user-cards-list">
            <!-- ここにユーザーカード一覧が動的に追加されます -->
        </ul>
    </div>
    <div id="point-card-container">
    <!-- ここに動的にポイントカードが表示されます -->
    </div>

    
<!-- モーダルポップアップ -->
<div id="modal" class="modal" onclick="closeModal()">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <p>おめでとう！次のカードを作成してね。<br>
        （同じ名前のカードは作れないので、カード名の後に何枚目か番号をつけてね。）
        </p>
    </div>
</div>

</body>
</html>