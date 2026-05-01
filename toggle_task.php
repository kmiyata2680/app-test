<?php
session_start(); // 💡 これを絶対に忘れない！
header('Content-Type: application/json');

// --- 🛡️ CSRF検問：ここをコピー！ ---
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => '不正なリクエストです']);
    exit;
}

// データベース接続（ここはお使いの環境に合わせてください）
$host = 'localhost'; $dbname = 'my_app'; $user = 'root'; $password = '';

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

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
        $id = $_POST['id'];

        // 現在の状態を反転させるUPDATE
        $stmt = $pdo->prepare("UPDATE tasks SET is_completed = NOT is_completed WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        // 最新の状態を取得して返す
        $stmt = $pdo->prepare("SELECT is_completed FROM tasks WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $new_status = $stmt->fetchColumn();

        echo json_encode(['success' => true, 'is_completed' => (int)$new_status]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}