document.addEventListener('DOMContentLoaded', () => {
    // Get preselected employees from sessionStorage
    const preselectedEmployees = JSON.parse(sessionStorage.getItem('preselectedEmployees') || '[]');
    
    // DOM Elements
    const employeeDisplay = document.getElementById('selected-employees-display');
    const employeeCountSpan = document.getElementById('employee-count');
    const form = document.getElementById('assign-task-form');
    
    // Modal Elements
    const modal = document.getElementById('confirmation-modal');
    const closeModalBtn = document.getElementById('close-modal-btn');
    const cancelConfirmBtn = document.getElementById('cancel-confirm-btn');
    const confirmSubmitBtn = document.getElementById('confirm-submit-btn');
    const confirmationMessage = document.getElementById('confirmation-message');
    const notMembersSection = document.getElementById('not-members-section');
    const notMembersList = document.getElementById('not-members-list');
    const taskDetailsList = document.getElementById('task-details-list');
    
    let selectedEmployeeData = [];
    
    /* ========================================
       VALIDATE EMPLOYEES SELECTED
    ======================================== */
    function validateEmployees() {
        if (preselectedEmployees.length === 0) {
            alert('No employees selected. Please go back to the Employee Directory and select employees.');
            setTimeout(() => {
                window.location.href = 'employee-directory.php';
            }, 100);
            return false;
        }
        return true;
    }
    
    // Check on load
    if (!validateEmployees()) {
        return; 
    }
    
    /* ========================================
       LOAD PRESELECTED EMPLOYEES
    ======================================== */
    async function loadPreselectedEmployees() {
        if (preselectedEmployees.length === 0) {
            employeeDisplay.innerHTML = '<p class="empty-message">No employees selected. Please go back to the Employee Directory and select employees.</p>';
            employeeCountSpan.textContent = '0';
            return;
        }
        
        // Update count
        employeeCountSpan.textContent = preselectedEmployees.length;
        
        // Fetch employee details from server (with colors)
        try {
            const response = await fetch(`assign-task.php?ajax=get_employees&ids=${preselectedEmployees.join(',')}`);
            const employees = await response.json();
            
            if (employees && employees.length > 0) {
                selectedEmployeeData = employees;
                displaySelectedEmployees();
            } else {
                // Fallback if fetch fails
                selectedEmployeeData = preselectedEmployees.map(id => ({
                    id: id,
                    name: `Employee ${id}`,
                    color: '#E5E7EB'
                }));
                displaySelectedEmployees();
            }
        } catch (error) {
            console.error('Error fetching employee details:', error);
            // Fallback
            selectedEmployeeData = preselectedEmployees.map(id => ({
                id: id,
                name: `Employee ${id}`,
                color: '#E5E7EB'
            }));
            displaySelectedEmployees();
        }
    }
    
    function displaySelectedEmployees() {
        if (selectedEmployeeData.length === 0) {
            employeeDisplay.innerHTML = '<p class="empty-message">No employees selected</p>';
            return;
        }
        
        // Display with colors from session (matching employee cards)
        employeeDisplay.innerHTML = selectedEmployeeData.map(emp => `
            <span class="employee-pill" style="background-color: ${emp.color || '#E5E7EB'}; color: #fff;">
                <i data-feather="user"></i>
                ${emp.name}
            </span>
        `).join('');
        
        feather.replace();
    }
    
    /* ========================================
       CHECK PROJECT MEMBERSHIP & SHOW MODAL
    ======================================== */
    async function showConfirmationModal(formData) {
        const projectId = formData.get('project_id');
        const taskName = formData.get('task_name');
        const priority = formData.get('priority');
        const deadline = formData.get('deadline');
        
        // Get project name from dropdown
        const projectSelect = document.getElementById('task-project');
        const projectName = projectSelect.options[projectSelect.selectedIndex].text;
        
        // Check which employees are NOT in the project
        try {
            const response = await fetch(`assign-task.php?ajax=check_membership&project_id=${projectId}&employee_ids=${preselectedEmployees.join(',')}`);
            const result = await response.json();
            const notMembers = result.not_members || [];
            
            // Build confirmation message
            if (notMembers.length > 0) {
                if (notMembers.length === 1) {
                    confirmationMessage.textContent = `${notMembers[0].name} is not a part of ${projectName}. If you assign them this task then they will be added as a Team Member on to ${projectName}. Are you sure you want to assign this task?`;
                } else {
                    confirmationMessage.textContent = `Some employees are not part of ${projectName}. If you assign them this task then they will be added as Team Members on to ${projectName}. Are you sure you want to assign this task?`;
                }
                
                // Show not members section
                notMembersSection.style.display = 'block';
                notMembersList.innerHTML = notMembers.map(emp => 
                    `<li>${emp.name}</li>`
                ).join('');
            } else {
                // All employees are already members
                confirmationMessage.textContent = `Are you sure you want to assign this task to the selected employees?`;
                notMembersSection.style.display = 'none';
            }
            
            // Show task details
            taskDetailsList.innerHTML = `
                <li><strong>Task:</strong> ${taskName}</li>
                <li><strong>Project:</strong> ${projectName}</li>
                <li><strong>Priority:</strong> ${priority.charAt(0).toUpperCase() + priority.slice(1)}</li>
                <li><strong>Deadline:</strong> ${deadline}</li>
            `;
            
            // Show modal
            modal.classList.add('show');
            feather.replace();
            
        } catch (error) {
            console.error('Error checking membership:', error);
            alert('Error checking project membership. Please try again.');
        }
    }
    
    function hideConfirmationModal() {
        modal.classList.remove('show');
    }
    
    closeModalBtn.addEventListener('click', hideConfirmationModal);
    cancelConfirmBtn.addEventListener('click', hideConfirmationModal);
    
    // Close modal on background click
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            hideConfirmationModal();
        }
    });
    
    /* ========================================
       FORM SUBMISSION
    ======================================== */
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Validate employees
            if (preselectedEmployees.length === 0) {
                alert('No employees selected. Please go back to the Employee Directory.');
                return;
            }
            
            // Validate project selection
            const projectSelect = document.getElementById('task-project');
            if (!projectSelect.value) {
                alert('Please select a project.');
                return;
            }
            
            // Prepare form data
            const formData = new FormData(form);
            
            await showConfirmationModal(formData);
        });
    }
    
    /* ========================================
       CONFIRM SUBMISSION
    ======================================== */
    confirmSubmitBtn.addEventListener('click', async () => {
        const formData = new FormData(form);
        
        // Add employee IDs
        preselectedEmployees.forEach(id => {
            formData.append('employee_ids[]', id);
        });
        
        // Disable button
        confirmSubmitBtn.disabled = true;
        confirmSubmitBtn.textContent = 'Assigning...';
        
        try {
            const response = await fetch('assign-task.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Clear sessionStorage
                sessionStorage.removeItem('preselectedEmployees');
                
                // Hide modal
                hideConfirmationModal();
                
                // Show success message
                alert('Task assigned successfully!');
                
                // Redirect back to employee directory
                window.location.href = 'employee-directory.php';
            } else {
                alert('Error: ' + result.message);
                confirmSubmitBtn.disabled = false;
                confirmSubmitBtn.innerHTML = '<i data-feather="check"></i> Confirm';
                feather.replace();
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
            confirmSubmitBtn.disabled = false;
            confirmSubmitBtn.innerHTML = '<i data-feather="check"></i> Confirm';
            feather.replace();
        }
    });
    
    loadPreselectedEmployees();
});