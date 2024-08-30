<?php
// Connect to SQLite database
$db = new SQLite3('todos.db');

$id = $_GET['id'] ?? null;

if ($id) {
  $stmt = $db->prepare('SELECT * FROM todos WHERE id = :id');
  $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
  $result = $stmt->execute();
  $todo = $result->fetchArray(SQLITE3_ASSOC);

  if ($todo) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $newTitle = trim($_POST['title']);
      $newDeadline = $_POST['deadline'] ?? null;
      if (!empty($newTitle)) {
        $updateStmt = $db->prepare('UPDATE todos SET title = :title, deadline = :deadline WHERE id = :id');
        $updateStmt->bindValue(':title', $newTitle, SQLITE3_TEXT);
        $updateStmt->bindValue(':deadline', $newDeadline, SQLITE3_TEXT);
        $updateStmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $updateStmt->execute();

        // Fetch the updated todo
        $stmt->reset();
        $result = $stmt->execute();
        $todo = $result->fetchArray(SQLITE3_ASSOC);
      }
    }
    $isDue = $todo['deadline'] && strtotime($todo['deadline']) >= strtotime('today');
?>
    <li id="todo-<?= $todo['id'] ?>" draggable="true" data-id="<?= $todo['id'] ?>" class="<?= $isDue ? 'due-todo' : '' ?>">
      <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <div class="todo-content">
          <input type="checkbox" class="checkbox" id="complete-<?= $todo['id'] ?>"
            hx-post="complete_todo.php?id=<?= $todo['id'] ?>"
            hx-target="#todo-<?= $todo['id'] ?>"
            hx-swap="outerHTML"
            <?= $todo['completed'] ? 'checked' : '' ?>>
          <span class="todo-text" style="<?= $todo['completed'] ? 'text-decoration: line-through;' : '' ?>">
            <?= htmlspecialchars($todo['title']) ?>
          </span>
          <span class="todo-date">
            <?= $todo['date_added'] ?>
            <?= $todo['deadline'] ? " (Due: {$todo['deadline']})" : '' ?>
          </span>
        </div>
        <div class="todo-actions">
          <button hx-get="edit_todo.php?id=<?= $todo['id'] ?>"
            hx-target="#todo-<?= $todo['id'] ?>">
            &#9998;
          </button>
          <button hx-delete="delete_todo.php?id=<?= $todo['id'] ?>"
            hx-target="#todo-<?= $todo['id'] ?>"
            hx-swap="outerHTML">
            &#10060;
          </button>
        </div>
      <?php else: ?>
        <form hx-post="edit_todo.php?id=<?= $todo['id'] ?>" hx-target="#todo-<?= $todo['id'] ?>" style="display: flex; align-items: center; width: 100%;">
          <input type="text" name="title" value="<?= htmlspecialchars($todo['title']) ?>" required style="flex-grow: 1; margin-right: 4px;">
          <input type="date" name="deadline" value="<?= $todo['deadline'] ?>" style="margin-right: 4px;">
          <button type="submit">&#10004;</button>
        </form>
      <?php endif; ?>
    </li>
<?php
  }
}
?>