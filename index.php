<?php
// Connect to SQLite database
$db = new SQLite3('todos.db');

// Create todos table if it doesn't exist
$db->exec('CREATE TABLE IF NOT EXISTS todos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    completed BOOLEAN DEFAULT 0,
    date_added DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_completed DATETIME
)');

// Add deadline column if it doesn't exist
$result = $db->query("PRAGMA table_info(todos)");
$columns = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
  $columns[] = $row['name'];
}

if (!in_array('deadline', $columns)) {
  $db->exec('ALTER TABLE todos ADD COLUMN deadline DATE');
}

// Add order column if it doesn't exist
if (!in_array('order', $columns)) {
  $db->exec('ALTER TABLE todos ADD COLUMN `order` INTEGER');
  // Set initial order based on id
  $db->exec('UPDATE todos SET `order` = id WHERE `order` IS NULL');
}

// Fetch all todos
$todos = $db->query('SELECT * FROM todos ORDER BY 
    CASE WHEN deadline IS NOT NULL AND deadline >= date("now") THEN 0 ELSE 1 END, 
    deadline ASC NULLS LAST, 
    `order` ASC, 
    date_added DESC');

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Todo App - Windows 95 Style</title>
  <script src="https://unpkg.com/htmx.org@1.9.10"></script>
  <style>
    body {
      font-family: 'Courier New', Courier, monospace;
      background-color: #008080;
      color: #000;
      margin: 0;
      padding: 20px;
    }

    .window {
      background-color: #c0c0c0;
      border: 2px outset #fff;
      box-shadow: 2px 2px 0 #000;
      padding: 2px;
      max-width: 600px;
      margin: 0 auto;
      position: absolute;
      top: 50px;
      left: 50px;
    }

    .title-bar {
      background-color: #000080;
      color: #fff;
      padding: 2px 4px;
      font-weight: bold;
      cursor: move;
    }

    .content {
      padding: 10px;
    }

    input[type="text"],
    input[type="date"],
    button {
      font-family: 'Courier New', Courier, monospace;
      border: 2px inset #fff;
      background-color: #fff;
      padding: 4px 6px;
      margin: 2px 0;
    }

    input[type="text"] {
      width: 60%;
      font-size: 16px;
    }

    input[type="date"] {
      width: 25%;
    }

    button {
      border: 2px outset #fff;
      background-color: #c0c0c0;
      cursor: pointer;
      padding: 4px 8px;
    }

    button:active {
      border-style: inset;
    }

    ul {
      list-style-type: none;
      padding: 0;
    }

    li {
      background-color: #fff;
      border: 1px solid #000;
      margin-bottom: 4px;
      padding: 4px;
      cursor: move;
      display: flex;
      align-items: center;
    }

    .todo-content {
      flex-grow: 1;
      display: flex;
      align-items: center;
    }

    .todo-text {
      margin-left: 4px;
      font-weight: bold;
    }

    .todo-date {
      font-size: 0.8em;
      margin-left: 8px;
    }

    .todo-actions {
      display: flex;
      align-items: center;
    }

    .todo-actions button {
      margin-left: 4px;
    }

    .due-todo {
      background-color: #ffffa0;
      /* Light yellow shade */
    }
  </style>
</head>

<body>
  <div id="todo-window" class="window">
    <div id="title-bar" class="title-bar">Todo App <small>(Drag me around)</small></div>
    <div class="content">
      <form hx-post="add_todo.php" hx-target="#todo-list" hx-swap="afterbegin" hx-on::after-request="this.reset()">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
          <input type="text" name="title" placeholder="Enter new todo" required>
          <input type="date" name="deadline">
          <button type="submit">Add</button>
        </div>
      </form>

      <ul id="todo-list">
        <?php while ($todo = $todos->fetchArray(SQLITE3_ASSOC)):
          $isDue = $todo['deadline'] && strtotime($todo['deadline']) >= strtotime('today');
        ?>
          <li id="todo-<?= $todo['id'] ?>" draggable="true" data-id="<?= $todo['id'] ?>" class="<?= $isDue ? 'due-todo' : '' ?>">
            <div class="todo-content">
              <input type="checkbox" class="checkbox" id="complete-<?= $todo['id'] ?>"
                hx-post="complete_todo.php?id=<?= $todo['id'] ?>"
                hx-target="#todo-<?= $todo['id'] ?>"
                hx-swap="outerHTML"
                <?= $todo['completed'] ? 'checked' : '' ?>>
              <span class="todo-text" style="<?= $todo['completed'] ? 'text-decoration: line-through;' : '' ?>">
                <?= htmlspecialchars($todo['title']) ?>
              </span>
              <?php if ($todo['deadline']): ?>
                <span class="todo-date">
                  Due: <?= $todo['deadline'] ?>
                </span>
              <?php endif; ?>
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
          </li>
        <?php endwhile; ?>
      </ul>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', (event) => {
      const todoList = document.getElementById('todo-list');
      const todoWindow = document.getElementById('todo-window');
      const titleBar = document.getElementById('title-bar');
      let draggedItem = null;
      let isDragging = false;
      let currentX;
      let currentY;
      let initialX;
      let initialY;
      let xOffset = 0;
      let yOffset = 0;

      // Load saved position from localStorage
      const savedPosition = JSON.parse(localStorage.getItem('todoWindowPosition'));
      if (savedPosition) {
        todoWindow.style.left = savedPosition.x + 'px';
        todoWindow.style.top = savedPosition.y + 'px';
        xOffset = savedPosition.x;
        yOffset = savedPosition.y;
      }

      titleBar.addEventListener('mousedown', dragStart);
      document.addEventListener('mousemove', drag);
      document.addEventListener('mouseup', dragEnd);

      function dragStart(e) {
        initialX = e.clientX - xOffset;
        initialY = e.clientY - yOffset;
        isDragging = true;
      }

      function drag(e) {
        if (isDragging) {
          e.preventDefault();
          currentX = e.clientX - initialX;
          currentY = e.clientY - initialY;
          xOffset = currentX;
          yOffset = currentY;
          setTranslate(currentX, currentY, todoWindow);
        }
      }

      function dragEnd(e) {
        initialX = currentX;
        initialY = currentY;
        isDragging = false;
        // Save position to localStorage
        localStorage.setItem('todoWindowPosition', JSON.stringify({
          x: xOffset,
          y: yOffset
        }));
      }

      function setTranslate(xPos, yPos, el) {
        el.style.left = xPos + 'px';
        el.style.top = yPos + 'px';
      }

      todoList.addEventListener('dragstart', (e) => {
        draggedItem = e.target;
        e.target.classList.add('dragging');
      });

      todoList.addEventListener('dragend', (e) => {
        e.target.classList.remove('dragging');
      });

      todoList.addEventListener('dragover', (e) => {
        e.preventDefault();
        const afterElement = getDragAfterElement(todoList, e.clientY);
        const currentElement = draggedItem;
        if (afterElement == null) {
          todoList.appendChild(draggedItem);
        } else {
          todoList.insertBefore(draggedItem, afterElement);
        }
      });

      todoList.addEventListener('dragend', (e) => {
        e.target.classList.remove('dragging');
        updateOrder();
      });

      function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('li:not(.dragging)')];

        return draggableElements.reduce((closest, child) => {
          const box = child.getBoundingClientRect();
          const offset = y - box.top - box.height / 2;
          if (offset < 0 && offset > closest.offset) {
            return {
              offset: offset,
              element: child
            };
          } else {
            return closest;
          }
        }, {
          offset: Number.NEGATIVE_INFINITY
        }).element;
      }

      function updateOrder() {
        const items = todoList.querySelectorAll('li');
        const ids = Array.from(items).map(item => item.dataset.id);

        fetch('update_order.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            ids: ids
          }),
        });
      }
    });
  </script>
</body>

</html>