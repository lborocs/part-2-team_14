
document.addEventListener('DOMContentLoaded', () => {
    // Get preselected employees from sessionStorage
    const preselectedEmployees = JSON.parse(sessionStorage.getItem('preselectedEmployees') || '[]');
    
    // DOM Elements
    const employeeDisplay = document.getElementById('selected-employees-display');
    const projectToggle = document.getElementById('project-select-toggle');
    const projectDropdown = document.getElementById('project-select-dropdown');
    const projectLabel = document.getElementById('project-select-label');
    const projectSearch = document.getElementById('project-search-input');
    const projectCheckboxes = document.querySelectorAll('.project-checkbox-item input[type="checkbox"]');
    const form = document.getElementById('add-to-project-form');
    
    // Modal Elements
    const modal = document.getElementById('confirmation-modal');
    const closeModalBtn = document.getElementById('close-modal-btn');
    const cancelConfirmBtn = document.getElementById('cancel-confirm-btn');
    const confirmSubmitBtn = document.getElementById('confirm-submit-btn');
    const confirmEmployeesList = document.getElementById('confirm-employees-list');
    const confirmProjectsList = document.getElementById('confirm-projects-list');
    
    let selectedEmployeeData = [];
    
    /* ========================================
       LOAD PRESELECTED EMPLOYEES
    ======================================== */
    async function loadPreselectedEmployees() {
        if (preselectedEmployees.length === 0) {
            employeeDisplay.innerHTML = '<p class="empty-message">No employees selected. Please go back to the Employee Directory and select employees.</p>';
            return;
        }
        
        // Fetch employee details from server
        try {
            const response = await fetch(`add-to-project.php?ajax=get_employees&ids=${preselectedEmployees.join(',')}`);
            const employees = await response.json();
            
            if (employees && employees.length > 0) {
                selectedEmployeeData = employees;
                displaySelectedEmployees();
            } else {
                // Fallback if fetch fails
                selectedEmployeeData = preselectedEmployees.map(id => ({
                    id: id,
                    name: `Employee ${id}`
                }));
                displaySelectedEmployees();
            }
        } catch (error) {
            console.error('Error fetching employee details:', error);
            // Fallback
            selectedEmployeeData = preselectedEmployees.map(id => ({
                id: id,
                name: `Employee ${id}`
            }));
            displaySelectedEmployees();
        }
    }
    
    function displaySelectedEmployees() {
        if (selectedEmployeeData.length === 0) {
            employeeDisplay.innerHTML = '<p class="empty-message">No employees selected</p>';
            return;
        }
        
        employeeDisplay.innerHTML = selectedEmployeeData.map(emp => `
            <span class="employee-pill" style="background-color: ${emp.color || '#E5E7EB'}; color: #fff;">
                <i data-feather="user"></i>
                ${emp.name}
            </span>
        `).join('');
        
        feather.replace();
    }
    
    /* ========================================
       PROJECT DROPDOWN TOGGLE
    ======================================== */
    projectToggle.addEventListener('click', (e) => {
        e.preventDefault();
        const isHidden = projectDropdown.hasAttribute('hidden');
        
        if (isHidden) {
            projectDropdown.removeAttribute('hidden');
            projectToggle.classList.add('open');
        } else {
            projectDropdown.setAttribute('hidden', '');
            projectToggle.classList.remove('open');
        }
        
        feather.replace();
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!projectToggle.contains(e.target) && !projectDropdown.contains(e.target)) {
            projectDropdown.setAttribute('hidden', '');
            projectToggle.classList.remove('open');
            feather.replace();
        }
    });
    
    /* ========================================
       PROJECT SELECTION
    ======================================== */
    function updateProjectLabel() {
        const checkedBoxes = document.querySelectorAll('.project-checkbox-item input[type="checkbox"]:checked');
        
        if (checkedBoxes.length === 0) {
            projectLabel.textContent = 'Select projects...';
            projectLabel.classList.remove('has-selection');
        } else if (checkedBoxes.length === 1) {
            projectLabel.textContent = checkedBoxes[0].dataset.projectName;
            projectLabel.classList.add('has-selection');
        } else {
            projectLabel.textContent = `${checkedBoxes.length} projects selected`;
            projectLabel.classList.add('has-selection');
        }
    }
    
    projectCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateProjectLabel);
    });
    
    /* ========================================
       PROJECT SEARCH
    ======================================== */
    projectSearch.addEventListener('input', (e) => {
        const searchTerm = e.target.value.toLowerCase();
        const items = document.querySelectorAll('.project-checkbox-item');
        
        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    });
    
    /* ========================================
       FORM SUBMISSION
    ======================================== */
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        
        // Get selected projects
        const checkedProjects = Array.from(
            document.querySelectorAll('.project-checkbox-item input[type="checkbox"]:checked')
        );
        
        if (checkedProjects.length === 0) {
            alert('Please select at least one project.');
            return;
        }
        
        if (selectedEmployeeData.length === 0) {
            alert('No employees selected. Please go back to the Employee Directory.');
            return;
        }
        
        // Show confirmation modal
        showConfirmationModal(checkedProjects);
    });
    
    /* ========================================
       CONFIRMATION MODAL
    ======================================== */
    function showConfirmationModal(selectedProjects) {
        // Populate employee list
        confirmEmployeesList.innerHTML = selectedEmployeeData.map(emp => 
            `<li>${emp.name}</li>`
        ).join('');
        
        // Populate project list
        confirmProjectsList.innerHTML = selectedProjects.map(checkbox => 
            `<li>${checkbox.dataset.projectName}</li>`
        ).join('');
        
        modal.classList.add('show');
        feather.replace();
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
       CONFIRM SUBMISSION
    ======================================== */
    confirmSubmitBtn.addEventListener('click', async () => {
        // Get form data
        const formData = new FormData(form);
        
        // Add employee IDs
        preselectedEmployees.forEach(id => {
            formData.append('employee_ids[]', id);
        });
        
        // Disable button
        confirmSubmitBtn.disabled = true;
        confirmSubmitBtn.textContent = 'Adding...';
        
        try {
            const response = await fetch('add-to-project.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert(result.message);
                
                // Clear sessionStorage
                sessionStorage.removeItem('preselectedEmployees');
                
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