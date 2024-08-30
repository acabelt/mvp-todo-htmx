<?php
// Connect to SQLite database
$db = new SQLite3('todos.db');

// Get the JSON data from the request body
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (isset($data['ids']) && is_array($data['ids'])) {
  // Start a transaction
  $db->exec('BEGIN');

  try {
    foreach ($data['ids'] as $index => $id) {
      $stmt = $db->prepare('UPDATE todos SET `order` = :order WHERE id = :id');
      $stmt->bindValue(':order', $index, SQLITE3_INTEGER);
      $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
      $stmt->execute();
    }

    // Commit the transaction
    $db->exec('COMMIT');
    echo json_encode(['success' => true]);
  } catch (Exception $e) {
    // Rollback the transaction on error
    $db->exec('ROLLBACK');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
  }
} else {
  echo json_encode(['success' => false, 'error' => 'Invalid data']);
}
