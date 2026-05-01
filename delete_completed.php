<?php
session_start();
require 'db_config.php'; // DB接続設定を共通化している場合

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