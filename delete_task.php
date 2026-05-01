<?php
session_start(); // 💡 これを絶対に忘れない！
header('Content-Type: application/json');
$host = 'localhost'; $dbname = 'my_app'; $user = 'root'; $password = '';

// --- 🛡️ CSRF検問：ここをコピー！ ---
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => '不正なリクエストです']);
    exit;
}

try {
    require 'db_config.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
        $id = $_POST['id'];

        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode(['success' => true]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}