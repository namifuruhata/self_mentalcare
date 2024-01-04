<?php

ini_set('display_errors', 'On');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$message = ''; // メッセージを格納する変数を初期化
$userPoints = 0; // ユーザーのポイント残高を格納する変数

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 登録アクションの処理
    if (isset($_POST['action']) && $_POST['action'] == 'register') {
        $name = $_POST['name'];
        $email = $_POST['email'];

        // CSVファイルを開く
        $file = fopen('data/users.csv', 'a');
        fputcsv($file, [$name, $email, 0]); // 初期ポイントは0
        fclose($file);

        $message = "登録が完了しました。<br>";
    }

    // 残高確認アクションの処理
if (isset($_POST['action']) && $_POST['action'] == 'check_balance') {
    $name = $_POST['name'];

    $file = fopen('data/users.csv', 'r');
    $found = false;
    while ($row = fgetcsv($file)) {
        if ($row[0] == $name) {
            $message = "ポイント残高: " . $row[2] . "<br>";
            $found = true;
            break;
        }
    }
    if (!$found) {
        $message = "指定された名前のユーザーは見つかりませんでした。<br>";
    }
    fclose($file);
}


    // ポイント更新アクションの処理
    if (isset($_POST['action']) && $_POST['action'] == 'update_points') {
        $name = $_POST['name'];
        $points_to_add = $_POST['points'];

        $updated_data = [];
        $file = fopen('data/users.csv', 'r');
        while ($row = fgetcsv($file)) {
            if ($row[0] == $name) {
                $row[2] += $points_to_add;
            }
            $updated_data[] = $row;
        }
        fclose($file);

        // CSVファイルを更新
        $file = fopen('data/users.csv', 'w');
        foreach ($updated_data as $row) {
            fputcsv($file, $row);
        }
        fclose($file);

        $message = "ポイントが更新されました。<br>";
    }
}


    
?>

<div class="card">
    <h2>ポイントカード登録</h2>
    <form action="main.php" method="post">
        <input type="hidden" name="action" value="register">
        名前: <input type="text" name="name"><br>
        メールアドレス: <input type="email" name="email"><br>
        <input type="submit" value="登録">
    </form>
    <?php if (isset($_POST['action']) && $_POST['action'] == 'register') echo $message; ?>
</div>

<div class="card">
    <h2>ポイント残高照会</h2>
    <form action="main.php" method="post">
        <input type="hidden" name="action" value="check_balance">
        名前: <input type="text" name="name"><br>
        <input type="submit" value="残高確認">
    </form>
    <?php if (isset($_POST['action']) && $_POST['action'] == 'check_balance') echo $message; ?>
</div>

<div class="card">
    <h2>ポイント更新</h2>
    <form action="main.php" method="post">
        <input type="hidden" name="action" value="update_points">
        名前: <input type="text" name="name"><br>
        ポイント変更: <input type="number" name="points"><br>
        <input type="submit" value="更新">
    </form>
    <?php if (isset($_POST['action']) && $_POST['action'] == 'update_points') echo $message; ?>
</div>


<style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f4;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
    }

    .card {
        background-color: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        width: 300px;
        margin-bottom: 20px;
    }

    .card h2 {
        color: #333;
        margin-top: 0;
    }

    input[type="text"], input[type="email"], input[type="number"] {
        width: 100%;
        padding: 10px;
        margin: 10px 0;
        border-radius: 4px;
        border: 1px solid #ddd;
    }

    input[type="submit"] {
        width: 100%;
        padding: 10px;
        border: none;
        border-radius: 4px;
        background-color: #007bff;
        color: white;
        cursor: pointer;
    }

    input[type="submit"]:hover {
        background-color: #0056b3;
    }
</style>


