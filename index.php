<?php
session_start(); // セッション（メモ帳）を開始

// 合言葉がまだなければ、新しく作ってメモ帳（セッション）に保存
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<?php
try {

    require 'db_config.php';

// --- 1. 新しいタスクの保存処理（これ1つにまとめます） ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
        
        // CSRFチェック
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            exit('不正なアクセスです');
        }

        $title = trim($_POST['title']);
        $due_date = $_POST['due_date'] ?: null;
        $category = $_POST['category'] ?: null;

        // 💡 ここがバリデーション
        if ($title === '') {
            $_SESSION['error'] = 'タスク名を入力してください！！';
        } elseif (mb_strlen($title) > 100) {
            $_SESSION['error'] = 'タスク名は100文字以内で入力してください。';
        } else {
            // 💡 OKなら保存
            $stmt = $pdo->prepare("INSERT INTO tasks (title, due_date, category) VALUES (:title, :due_date, :category)");
            $stmt->bindValue(':title', $title, PDO::PARAM_STR);
            $stmt->bindValue(':due_date', $due_date, PDO::PARAM_STR);
            $stmt->bindValue(':category', $category, PDO::PARAM_STR);
            $stmt->execute();
            
            $_SESSION['success'] = 'タスクを追加しました！'; // ここで成功メッセージをセット
        }

        // 最後にリダイレクト（1回だけ！）
        header('Location: index.php');
        exit;
    }

    // --- 3. タスクの完了状態を切り替える処理 ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['toggle'])) {
    $id = $_POST['id'];

    // SQLの「NOT」を使って、0なら1に、1なら0に反転させます
    $stmt = $pdo->prepare("UPDATE tasks SET is_completed = NOT is_completed WHERE id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT); // IDは数値（INT）として紐付け
    $stmt->execute();

    header('Location: index.php');
    exit;
}

// --- 4. タスクを削除する処理 ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['delete'])) {
    $id = $_POST['id'];

    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    header('Location: index.php');
    exit;
}

// --- 2. タスク一覧の取得処理（URLパラメータに合わせて動く） ---
    
    // URLの ?category=◯◯ を受け取る（なければ空にする）
    $selected_category = $_GET['category'] ?? ''; 
    $params = [];

    // SQLの土台（ベース）
    $sql = "SELECT * FROM tasks";

    // カテゴリーが選択されている場合だけ、絞り込み（WHERE）を追加
    if (!empty($selected_category)) {
        $sql .= " WHERE category = :category";
        $params[':category'] = $selected_category;
    }

    // 最後に並び替え（完了分を下に、IDの新しい順に）を追加
    $sql .= " ORDER BY is_completed ASC, id DESC";

    // 準備して実行（プレースホルダを使うので prepare を使用）
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    exit('接続失敗！' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>PHP Task App</title>
    <style>
        /* 1. 全体の設定（フォントや背景色） */
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background-color: #f4f7f6;
            color: #333;
            display: flex;
            justify-content: center;
            padding: 50px;
        }
        .container {
            width: 100%;
            max-width: 400px;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }

        /* 2. 入力フォームのデザイン */
        form { display: flex; gap: 10px; margin-bottom: 20px; }
        input[type="text"] {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }
        button {
            padding: 10px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: 0.3s;
        }
        button[type="submit"]:hover { opacity: 0.8; }

        /* 3. タスクリストのデザイン */
        ul { list-style: none; padding: 0; }
        li {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        .task-title { flex: 1; margin: 0 15px; }
        .completed { text-decoration: line-through; color: #aaa; }

        /* 4. ボタンの色の使い分け */
        .btn-toggle { background-color: #e0e0e0; }
        .btn-delete { background-color: #ff4d4d; color: white; }

        .due-date {
            font-size: 0.8em;
            color: #666;
            margin-left: 10px;
        }

        .due-date.overdue {
            color: #ff4d4d; /* 期限切れは赤く！ */
            font-weight: bold;
        }
        .category-badge {
            background-color: #e0e0e0;
            font-size: 0.7em;
            padding: 2px 8px;
            border-radius: 12px;
            margin-left: 5px;
            color: #666;
        }
    </style>
</head>

<body>
    <h1>タスク一覧（DBから取得）</h1>

    <!-- <form action="index.php" method="POST" style="margin-bottom: 20px;">
        <input type="text" name="title" placeholder="新しいタスクを入力..." required>
        <button type="submit">追加</button>
    </form> -->

    <?php if (!empty($_SESSION['success'])): ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
            ✅ <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
            ⚠️ <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <form id="add-form" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="text" name="title" placeholder="新しいタスクを入力...">
        
        <select name="category">
            <option value="">カテゴリーなし</option>
            <option value="仕事">💼 仕事</option>
            <option value="プライベート">🏠 プライベート</option>
            <option value="その他">✨ その他</option>
        </select>

        <input type="date" name="due_date">
        <button type="submit" name="add_task">追加</button>
    </form>

    <div class="filter-container" style="margin: 20px 0; padding: 10px; background: #f9f9f9; border-radius: 8px;">
        <span style="font-size: 0.9em; color: #666; margin-right: 10px;">絞り込み:</span>
        
        <a href="index.php" 
        style="text-decoration: none; margin-right: 10px; <?php echo empty($selected_category) ? 'font-weight: bold; color: #000;' : 'color: #007bff;'; ?>">
        🏠 すべて
        </a>

        <?php 
        $categories = ['仕事', 'プライベート', 'その他'];
        foreach ($categories as $cat): 
        ?>
            <a href="index.php?category=<?php echo urlencode($cat); ?>" 
            style="text-decoration: none; margin-right: 10px; <?php echo $selected_category === $cat ? 'font-weight: bold; color: #000;' : 'color: #007bff;'; ?>">
            <?php echo $cat; ?>
            </a>
        <?php endforeach; ?>
    </div>

<ul id="task-list">

    <?php foreach ($tasks as $task): ?>
        <li style="margin-bottom: 10px;">
        <form action="delete_completed.php" method="POST" id="bulk-delete-form" style="margin-top: 20px;">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <!-- <button type="submit" style="background-color: #ff4d4d; color: white; border: none; padding: 10px; border-radius: 5px; cursor: pointer;">
                ✅ 完了済みのタスクを一括削除
            </button> -->
            <button type="submit" 
        onclick="return confirm('完了済みのタスクをすべて削除します。よろしいですか？');"
        style="background-color: #ff4d4d; color: white; ...">
    ✅ 完了済みのタスクを一括削除
</button>

        </form>
            <form action="index.php" method="POST" style="display: inline;">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="id" value="<?php echo $task['id']; ?>">
                <button type="submit" name="toggle" class="btn-toggle">
                    <?php echo $task['is_completed'] ? '✅ 完了' : '⏳ 未完了'; ?>
                </button>
            </form>

            <span><?php echo htmlspecialchars($task['title']); ?></span>
            <?php if (!empty($task['category'])): ?>
                <span class="category-badge">
                    <?php echo htmlspecialchars($task['category']); ?>
                </span>
            <?php endif; ?>

            <?php if ($task['due_date']): ?>
                <?php 
                    // 今日より前の日付なら 'overdue' クラスを付ける
                    $is_overdue = (!$task['is_completed'] && $task['due_date'] < date('Y-m-d'));
                ?>
                <span class="due-date <?php echo $is_overdue ? 'overdue' : ''; ?>">
                    (期限: <?php echo htmlspecialchars($task['due_date']); ?>)
                </span>
            <?php endif; ?>

            <form action="index.php" method="POST" style="display: inline; margin-left: 10px;">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="id" value="<?php echo $task['id']; ?>">
                <button type="submit" name="delete" class="btn-delete">
                    🗑️ 削除
                </button>
            </form>
        </li> 
    <?php endforeach; ?>
</ul>


<script>
    // document.querySelector('#bulk-delete-form').addEventListener('submit', function(e) {
    //     if (!confirm('完了済みのタスクをすべて削除します。よろしいですか？')) {
    //         e.preventDefault(); // 「キャンセル」なら送信を止める
    //     }
    // });

    // 💡 ボタンのクリックを直接監視する
const bulkDeleteBtn = document.querySelector('#bulk-delete-form button[type="submit"]');

if (bulkDeleteBtn) {
    bulkDeleteBtn.addEventListener('click', function(e) {
        // 1. まず送信を強制停止
        // 2. 確認ダイアログを出す
        if (!confirm('完了済みのタスクをすべて削除します。よろしいですか？')) {
            e.preventDefault(); // キャンセルなら止めたまま
            console.log("🛑 ユーザーがキャンセルしました");
        } else {
            console.log("🆗 ユーザーが承認しました。送信を開始します。");
            // OKならそのまま送信（preventDefaultしない）
        }
    });
}


    document.querySelector('#add-form').addEventListener('submit', function(e) {
        e.preventDefault(); // リロードを止める

        // 1. 入力値を取得してチェック
        const titleInput = this.querySelector('input[name="title"]');
        const title = titleInput.value.trim();

        // 💡 水際対策：空っぽ、または50文字超えならここで終了！
        if (title === "") {
            alert('タスク名を入力してください〜〜〜');
            return; 
        }
        if (title.length > 50) {
            alert('タスクは50文字以内で入力してください');
            return;
        }

        // 2. チェックを通過したら送信処理へ
        const formData = new FormData(this);

        fetch('add_task.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // リストに追加する処理（前回作った ul.prepend など）
                const ul = document.querySelector('#task-list');
                const li = document.createElement('li');
                li.innerHTML = `⏳ 未完了 ${data.title} (リロードで反映)`;
                ul.prepend(li);
                
                titleInput.value = ''; // 入力欄を空にする
            } else {
                // PHP側のバリデーションで弾かれた場合のエラー表示
                alert(data.message);
            }
        });
    });


    // リスト全体（ul）でクリックを見守る
    document.querySelector('#task-list').addEventListener('click', function(e) {
        // もしクリックされたのが「btn-toggle」クラスを持つボタンなら
        if (e.target.classList.contains('btn-toggle')) {
            e.preventDefault(); // リロードを阻止

            const button = e.target;
            const form = button.closest('form'); // 親のフォームを探す
            const formData = new FormData(form);

            fetch('toggle_task.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 完了なら ✅、未完了なら ⏳ にボタンの文字を書き換える
                    button.innerText = data.is_completed ? '✅ 完了' : '⏳ 未完了';
                    
                    // タスク名の見た目も変える（打ち消し線のクラスを付け外し）
                    const titleSpan = button.parentElement.nextElementSibling;

                    // --- ここに追加！ ---
                    console.log('ターゲット要素:', titleSpan); 
                    // ------------------

                    titleSpan.classList.toggle('completed', data.is_completed);
                }
            });
        }


        if (e.target.classList.contains('btn-delete')) {
            if (!confirm('本当に削除しますか？')) return; // ディレクター視点の「確認ダイアログ」
            
            e.preventDefault();
            const button = e.target;
            const form = button.closest('form');
            const formData = new FormData(form);

            fetch('delete_task.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 削除されたタスクの行（li）を特定
                    const li = button.closest('li');
                    
                    // 💡 UXのこだわり：いきなり消さずに透明度を下げてから消す
                    li.style.transition = '0.3s';
                    li.style.opacity = '0';
                    
                    setTimeout(() => {
                        li.remove(); // 0.3秒後に画面から完全に削除
                    }, 300);
                }
            });
        }



    });


</script>




</body>
</html>