<?php
// Connect to SQLite database
$db = new SQLite3('todos.db');

$id = $_GET['id'] ?? null;

if ($id) {
  $stmt = $db->prepare('DELETE FROM todos WHERE id = :id');
  $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
  $result = $stmt->execute();

  if ($result) {
    // Return an empty response with 200 status code
    http_response_code(200);
    exit;
  } else {
    http_response_code(500);
    echo "Error deleting todo";
  }
}
