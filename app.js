// 1. データの初期状態（空の配列）
let tasks = [
    { title: "企画書の作成", isCompleted: true }
];

// 2. 「タスクを追加する」という関数（マシン）を作る
function addTask(newTitle) {
    // 新しいタスクオブジェクトを作成
    let newTask = {
        title: newTitle,
        isCompleted: false // 追加直後は必ず未完了
    };
    
    // 配列の最後にガチャンと合体させる
    tasks.push(newTask);
    
    console.log("➕ タスクを追加しました: " + newTitle);
}

// 3. 「現在のタスクを一覧表示する」という関数を作る
function showTasks() {
    console.log("\n--- 現在のタスクボード ---");
    for (let item of tasks) {
        let status = item.isCompleted ? "✅" : "⏳";
        console.log(status + " " + item.title);
    }
    console.log("------------------------\n");
}

// --- 実際に動かしてみる ---

showTasks();           // 最初に今の状態を見る
addTask("エンジニアに相談"); // タスクを追加
addTask("デザインの修正");   // もう一つ追加
showTasks();           // 増えたか確認