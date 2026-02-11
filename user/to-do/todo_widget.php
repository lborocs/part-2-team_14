<style>
/* Smooth transition for the panel */
.todo-panel[hidden] {
  display: none;
  opacity: 0;
  transform: translateY(10px) scale(0.95);
}

/* Custom styling to ensure the delete button is visible on hover */
.floating-todo-item {
    position: relative;
    padding-right: 40px !important;
    cursor: pointer;
}

.todo-delete-btn {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #D93025;
    cursor: pointer;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    opacity: 0.3;
    transition: opacity 0.2s;
    z-index: 2;
}

.floating-todo-item:hover .todo-delete-btn {
    opacity: 1;
}

.todo-delete-btn:hover {
    background: #FFF1F0;
}

/* Make checkbox clickable */
.floating-todo-checkbox {
    cursor: pointer;
}
</style>

<div class="floating-todo-widget" id="floating-todo-widget">
  <button class="todo-fab" id="todo-fab" aria-label="Toggle personal todos">
    <i data-feather="check-square"></i>
    <span class="todo-badge hidden" id="todo-badge">0</span>
  </button>

  <div class="todo-panel" id="todo-panel" hidden>
    <div class="todo-panel-header">
      <h3>My To-Dos</h3>
      <button class="todo-close-btn" id="todo-close-btn">
        <i data-feather="x"></i>
      </button>
    </div>

    <div class="todo-panel-content">
      <div style="padding: 15px; border-bottom: 1px solid #E8E4D9; background: #FEFBF4;">
          <form id="quick-add-todo-form" style="display: flex; flex-direction: column; gap: 8px;">
              <input type="text" id="new-todo-name" placeholder="Quick add task..." required 
                     style="padding: 10px; border: 1px solid #D4CDB8; border-radius: 8px; font-size: 13px; font-family: inherit; width: 100%;">
              <div style="display: flex; gap: 8px;">
                  <input type="datetime-local" id="new-todo-date" 
                         style="padding: 8px; border: 1px solid #D4CDB8; border-radius: 8px; font-size: 11px; flex-grow: 1; font-family: inherit;">
                  <button type="submit" class="todo-add-btn" style="width: auto; padding: 0 15px; height: 35px; margin: 0;">Add</button>
              </div>
          </form>
      </div>

      <div class="todo-panel-list" id="floating-todo-list">
        <div class="floating-todo-empty">
            <i data-feather="loader"></i>
            <p>Loading...</p>
        </div>
      </div>
      
      <div class="todo-panel-footer" style="text-align: center;">
        <small style="color: #8C8C8C; font-size: 11px;" id="todo-status-text">Ready to work!</small>
      </div>
    </div>
  </div>
</div>

<?php
// Compute relative path from the including page to this to-do directory
$includerDir = dirname($_SERVER['SCRIPT_FILENAME']);
$todoDir = __DIR__;
if (realpath($includerDir) === realpath(dirname($todoDir))) {
    $todoFetchBase = 'to-do/';
} else {
    $todoFetchBase = '../to-do/';
}
?>
<script>
(function() {
    const TODO_BASE = '<?= $todoFetchBase ?>';
    const fab = document.getElementById('todo-fab');
    const panel = document.getElementById('todo-panel');
    const closeBtn = document.getElementById('todo-close-btn');
    const todoList = document.getElementById('floating-todo-list');
    const badge = document.getElementById('todo-badge');
    const addForm = document.getElementById('quick-add-todo-form');

    // Handle checkbox clicks
    function handleCheckboxClick(event, todoId, currentStatus) {
        event.stopPropagation();
        toggleTaskStatus(todoId, currentStatus);
    }

    // Handle delete button clicks
    function handleDeleteClick(event, todoId) {
        event.stopPropagation();
        deleteTask(todoId);
    }

    // Toggle task completion status
    async function toggleTaskStatus(todoId, currentStatus) {
        try {
            const response = await fetch(TODO_BASE + 'update_todo_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    todo_id: todoId,
                    is_completed: currentStatus == 1 ? 0 : 1
                })
            });
            const result = await response.json();
            if (result.success) {
                await loadTodos();
                await updateBadge();
            } else {
                alert(result.message || 'Failed to update task');
            }
        } catch (err) { 
            console.error('Toggle error:', err);
            alert('Error updating task');
        }
    }

    // Delete a task
    async function deleteTask(todoId) {
        if(!confirm("Delete this task?")) return;

        try {
            const response = await fetch(TODO_BASE + 'delete_todo.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    todo_id: todoId
                })
            });
            const result = await response.json();
            if (result.success) {
                await loadTodos();
                await updateBadge();
            } else {
                alert(result.message || 'Failed to delete task');
            }
        } catch (err) { 
            console.error('Delete error:', err);
            alert('Error deleting task');
        }
    }

    async function loadTodos() {
        try {
            const response = await fetch(TODO_BASE + 'get_personal_todos.php');
            const data = await response.json();
            
            // Check if it's an error object
            if (data.error) {
                todoList.innerHTML = `<div class="floating-todo-empty"><i data-feather="alert-triangle"></i><p>${data.error}</p></div>`;
                feather.replace();
                return;
            }
            
            if (!data || data.length === 0) {
                todoList.innerHTML = '<div class="floating-todo-empty"><i data-feather="clipboard"></i><p>All caught up!</p></div>';
                feather.replace();
                return;
            }

            todoList.innerHTML = data.map(todo => {
                const isDone = todo.is_completed == 1;
                const deadline = todo.deadline ? new Date(todo.deadline) : null;
                const formattedDate = deadline ? deadline.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + ' ' + deadline.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }) : 'No deadline';

                // Check if overdue
                const today = new Date();
                const isOverdue = deadline && deadline < today && !isDone;
                
                return `
                <div class="floating-todo-item ${isDone ? 'completed' : ''}">
                    <div class="floating-todo-checkbox ${isDone ? 'checked' : ''}" 
                         onclick="event.stopPropagation(); window.todoApp.toggleTaskStatus(${todo.personal_task_id}, ${todo.is_completed})">
                        ${isDone ? '<i data-feather="check" style="width:12px; height:12px;"></i>' : ''}
                    </div>
                    <div class="floating-todo-content">
                        <div class="floating-todo-text">${todo.task_name}</div>
                        <div class="floating-todo-meta">
                            <span class="${isOverdue ? 'overdue' : ''}">${formattedDate}</span>
                        </div>
                    </div>
                    <button class="todo-delete-btn" 
                            onclick="event.stopPropagation(); window.todoApp.deleteTask(${todo.personal_task_id})">
                        <i data-feather="trash-2" style="width:14px; height:14px;"></i>
                    </button>
                </div>
                `;
            }).join('');
            
            feather.replace();
        } catch (err) {
            console.error('Load error:', err);
            todoList.innerHTML = '<div class="floating-todo-empty"><i data-feather="alert-triangle"></i><p>Error loading tasks</p></div>';
            feather.replace();
        }
    }

    async function updateBadge() {
        try {
            const response = await fetch(TODO_BASE + 'count_incomplete_todos.php');
            const data = await response.json();
            
            if (data.error) {
                console.error('Badge error:', data.error);
                return;
            }
            
            const count = data.count || 0;
            if (count > 0) {
                badge.textContent = count;
                badge.classList.remove('hidden');
                document.getElementById('todo-status-text').textContent = `You have ${count} pending tasks`;
            } else {
                badge.classList.add('hidden');
                document.getElementById('todo-status-text').textContent = `All tasks completed!`;
            }
        } catch (e) {
            console.error('Update badge error:', e);
        }
    }

    // Toggle panel
    fab.addEventListener('click', () => {
        const isHidden = panel.hasAttribute('hidden');
        if (isHidden) {
            panel.removeAttribute('hidden');
            loadTodos();
        } else {
            panel.setAttribute('hidden', '');
        }
    });

    // Close button
    closeBtn.addEventListener('click', () => panel.setAttribute('hidden', ''));

    // Add new todo
    addForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const nameInput = document.getElementById('new-todo-name');
        const dateInput = document.getElementById('new-todo-date');
        
        if (!nameInput.value.trim()) {
            alert('Please enter a task name');
            return;
        }
        
        try {
            const response = await fetch(TODO_BASE + 'create_personal_todo.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    task_name: nameInput.value,
                    deadline: dateInput.value || null
                })
            });
            
            const result = await response.json();
            if (result.success) {
                nameInput.value = '';
                dateInput.value = '';
                await loadTodos();
                await updateBadge();
            } else {
                alert(result.message || 'Failed to create task');
            }
        } catch (error) {
            console.error('Create error:', error);
            alert('Error creating task');
        }
    });

    // Make functions available globally
    window.todoApp = {
        toggleTaskStatus,
        deleteTask,
        loadTodos,
        updateBadge
    };

    // Initial load
    updateBadge();
    if (typeof feather !== 'undefined') feather.replace();
})();
</script>