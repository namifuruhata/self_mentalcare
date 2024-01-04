<?php

if (
  !isset($_POST['name']) || $_POST['name'] === '' ||
  !isset($_POST['mail']) || $_POST['mail'] === '' ||
 !isset($_POST['pass']) || $_POST['pass'] === ''
) {
  exit('ParamError');
}

$name = $_POST['name'];
$mail = $_POST['mail'];
$pass = $_POST['pass'];

// パスワードのハッシュ化
$hashed_pass = password_hash($pass, PASSWORD_DEFAULT);

// DB接続
$dbn ='mysql:dbname=thanks_card;charset=utf8mb4;port=3306;host=localhost';
$user = 'root';
$pwd = '';


try {
  $pdo = new PDO($dbn, $user, $pwd);
} catch (PDOException $e) {
  echo json_encode(["db error" => "{$e->getMessage()}"]);
  exit();
}

// ここから追加: メールアドレスの存在チェック
$checkMailSql = 'SELECT * FROM user WHERE mail = :mail';
$checkMailStmt = $pdo->prepare($checkMailSql);
$checkMailStmt->bindValue(':mail', $mail, PDO::PARAM_STR);
$checkMailStmt->execute();

if ($checkMailStmt->fetch()) {
  echo 'このメールアドレスは既に登録されています。';
  exit();
}


$sql = 'INSERT INTO user (id, name, mail, pass, created_at, updated_at) VALUES (NULL, :name, :mail, :hashed_pass, now(), now())';

$stmt = $pdo->prepare($sql);

$stmt->bindValue(':name', $name, PDO::PARAM_STR);
$stmt->bindValue(':mail', $mail, PDO::PARAM_STR);
$stmt->bindValue(':hashed_pass', $hashed_pass, PDO::PARAM_STR); // 修正

try {
  $status = $stmt->execute();
} catch (PDOException $e) {
  echo json_encode(["sql error" => "{$e->getMessage()}"]);
  exit();
}

header('Location: kanryou.html');
exit();

?>
