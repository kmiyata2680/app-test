<?php
session_start();

// --- データベース接続設定 ---
// 環境変数（Railway）から取得、なければローカル（localhost）の設定を使う
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'todo_app';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: 'root'; // あなたのローカル環境のパスワードに合わせてください

try {
    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    // 接続失敗時にエラーを表示して止める
    exit('DB接続エラーが発生しました: ' . $e->getMessage());
}



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 🛡️ CSRFチェック（いつもの検問）
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        exit('不正なアクセスです');
    }

    // 🧹 削除命令：is_completed が 1 (完了) のものを全て削除
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE is_completed = 1");
    $stmt->execute();

    // 削除後はトップページに戻る（今回はAjaxなしのシンプル版）
    header('Location: index.php');
    exit;
}