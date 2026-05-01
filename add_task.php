<?php
session_start(); // メモ帳を開く
// 1. 「これはJSONデータですよ」とブラウザに伝える宣言
header('Content-Type: application/json');

// データベース接続設定
$host = 'localhost';
$dbname = 'my_app';
$user = 'root';
$password = '';

// --- 🛡 CSRF検証（ここが追加ポイント！） ---
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode([
        'success' => false, 
        'message' => '不正なリクエスト（CSRFトークン不一致）です。'
    ]);
    exit; // 一致しなければ、ここで即終了！
}

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

    // 2. POSTデータを受け取る
// add_task.php の中身を一部書き換え

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['title'])) {
        $title = trim($_POST['title']); // 前後の空白を消す
        $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null; // ✅ ここで定義！
        $category = !empty($_POST['category']) ? $_POST['category'] : null;

        // 💡 バリデーション（検閲）
        if (mb_strlen($title) > 50) {
            echo json_encode(['success' => false, 'message' => 'タスクは50文字以内で入力してください']);
            exit;
        }
        
        if ($title === "") {
            echo json_encode(['success' => false, 'message' => 'タスク名を入力してください']);
            exit;
        }

        // データベースに保存
        $stmt = $pdo->prepare("INSERT INTO tasks (title, due_date, category) VALUES (:title, :due_date, :category)");
        $stmt->bindValue(':title', $title, PDO::PARAM_STR);
        $stmt->bindValue(':due_date', $due_date, PDO::PARAM_STR); // 文字列としてバインド
        $stmt->bindValue(':category', $category, PDO::PARAM_STR); // ✅ これを追加！
        $stmt->execute();

        // 3. 成功の返事（JSON）を準備する
        echo json_encode([
            'success' => true, 
            'title' => htmlspecialchars($title), 
            'due_date' => $due_date // 追加
        ]);
    } else {
        // 失敗時の返事
        echo json_encode(['success' => false, 'message' => 'タイトルが空です']);
    }

} catch (PDOException $e) {
    // 接続エラー時もJSONで返す
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}