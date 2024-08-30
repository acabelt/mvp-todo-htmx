<?php
// Connect to SQLite database
$db = new SQLite3('todos.db');

// Get the todo title and deadline from the POST data
$title = trim($_POST['title']);
$deadline = $_POST['deadline'] ?? null;

if (!empty($title)) {
  // Get current timestamp
  $currentTimestamp = date('Y-m-d H:i:s');

  // Prepare the INSERT statement
  $stmt = $db->prepare('INSERT INTO todos (title, date_added, deadline) VALUES (:title, :date_added, :deadline)');
  $stmt->bindValue(':title', $title, SQLITE3_TEXT);
  $stmt->bindValue(':date_added', $currentTimestamp, SQLITE3_TEXT);
  $stmt->bindValue(':deadline', $deadline, SQLITE3_TEXT);

  // Execute the statement
  $result = $stmt->execute();

  if ($result) {
    // Fetch the newly inserted todo
    $id = $db->lastInsertRowID();
    $newTodo = $db->query("SELECT * FROM todos WHERE id = $id")->fetchArray(SQLITE3_ASSOC);

    $isDue = $newTodo['deadline'] && strtotime($newTodo['deadline']) >= strtotime('today');

    // Output the new todo item HTML
?>
    <li id="todo-<?= $newTodo['id'] ?>" draggable="true" data-id="<?= $newTodo['id'] ?>" class="<?= $isDue ? 'due-todo' : '' ?>">
      <div class="todo-content">
        <input type="checkbox" class="checkbox" id="complete-<?= $newTodo['id'] ?>"
          hx-post="complete_todo.php?id=<?= $newTodo['id'] ?>"
          hx-target="#todo-<?= $newTodo['id'] ?>"
          hx-swap="outerHTML">
        <span class="todo-text">
          <?= htmlspecialchars($newTodo['title']) ?>
        </span>
        <?php if ($newTodo['deadline']): ?>
          <span class="todo-date">
            Due: <?= $newTodo['deadline'] ?>
          </span>
        <?php endif; ?>
      </div>
      <div class="todo-actions">
        <button hx-get="edit_todo.php?id=<?= $newTodo['id'] ?>"
          hx-target="#todo-<?= $newTodo['id'] ?>">
          &#9998;
        </button>
        <button hx-delete="delete_todo.php?id=<?= $newTodo['id'] ?>"
          hx-target="#todo-<?= $newTodo['id'] ?>"
          hx-swap="outerHTML">
          &#10060;
        </button>
      </div>
    </li>
<?php
  }
}
?>