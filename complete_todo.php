<?php
// Connect to SQLite database
$db = new SQLite3('todos.db');

$id = $_GET['id'] ?? null;

if ($id) {
  $stmt = $db->prepare('UPDATE todos SET completed = NOT completed, date_completed = CASE WHEN completed = 0 THEN CURRENT_TIMESTAMP ELSE NULL END WHERE id = :id');
  $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
  $result = $stmt->execute();

  if ($result) {
    $todo = $db->query("SELECT * FROM todos WHERE id = $id")->fetchArray(SQLITE3_ASSOC);
?>
    <li id="todo-<?= $todo['id'] ?>" class="flex items-center justify-between bg-gray-50 p-4 rounded-lg">
      <div class="flex items-center">
        <input type="checkbox" id="complete-<?= $todo['id'] ?>"
          hx-post="complete_todo.php?id=<?= $todo['id'] ?>"
          hx-target="#todo-<?= $todo['id'] ?>"
          hx-swap="outerHTML"
          <?= $todo['completed'] ? 'checked' : '' ?>
          class="mr-2">
        <span class="<?= $todo['completed'] ? 'line-through text-gray-500' : '' ?>">
          <?= htmlspecialchars($todo['title']) ?>
        </span>
      </div>
      <div class="flex items-center">
        <span class="text-sm text-gray-500 mr-2">
          <?= $todo['date_added'] ?>
        </span>
        <button hx-get="edit_todo.php?id=<?= $todo['id'] ?>"
          hx-target="#todo-<?= $todo['id'] ?>"
          class="text-blue-500 hover:text-blue-700 mr-2">
          Edit
        </button>
        <button hx-delete="delete_todo.php?id=<?= $todo['id'] ?>"
          hx-target="#todo-<?= $todo['id'] ?>"
          hx-swap="outerHTML"
          class="text-red-500 hover:text-red-700">
          Delete
        </button>
      </div>
    </li>
<?php
  } else {
    http_response_code(500);
    echo "Error updating todo";
  }
}
?>