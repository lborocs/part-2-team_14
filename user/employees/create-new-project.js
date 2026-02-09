document.addEventListener('DOMContentLoaded', () => {
    // Get preselected employees from sessionStorage
    const preselectedEmployees = JSON.parse(sessionStorage.getItem('preselectedEmployees') || '[]');
    
    // DOM Elements
    const employeeDisplay = document.getElementById('selected-employees-display');
    const employeeCountSpan = document.getElementById('employee-count');
    const minimumRequirementNote = document.getElementById('minimum-requirement-note');
    const leaderSearchInput = document.getElementById('leader-search');
    const leaderResults = document.getElementById('leader-results');
    const teamLeaderIdInput = document.getElementById('team-leader-id');
    const form = document.getElementById('create-project-form');
    
    let selectedEmployeeData = [];
    
    /* ========================================
       VALIDATE MINIMUM EMPLOYEES
    ======================================== */
    function validateMinimumEmployees() {
        if (preselectedEmployees.length < 4) {
            alert('⚠️ Minimum 4 employees required to create a project.\n\nYou have selected ' + preselectedEmployees.length + ' employee(s). Please go back and select at least 4 employees.');
            // Redirect back to employee directory
            setTimeout(() => {
                window.location.href = 'employee-directory.php';
            }, 100);
            return false;
        }
        return true;
    }
    
    // Check minimum employees on load
    if (!validateMinimumEmployees()) {
        return; // Stop execution if validation fails
    }
    
    /* ========================================
       UPDATE REQUIREMENT NOTE COLOR
    ======================================== */
    function updateRequirementNote() {
        const count = preselectedEmployees.length;
        
        if (count >= 4) {
            // Green - requirement met
            minimumRequirementNote.classList.add('requirement-met');
        } else {
            // Red - requirement not met
            minimumRequirementNote.classList.remove('requirement-met');
        }
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
        
        // Update requirement note color
        updateRequirementNote();
        
        // Fetch employee details from server (with colors!)
        try {
            const response = await fetch(`create-new-project.php?ajax=get_employees&ids=${preselectedEmployees.join(',')}`);
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
       LEADER AUTOCOMPLETE SEARCH
       (Limited to selected employees only)
    ======================================== */
    let searchTimeout;

    if (leaderSearchInput) {
        leaderSearchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            const query = leaderSearchInput.value.trim().toLowerCase();

            if (query.length < 1) {
                leaderResults.style.display = 'none';
                return;
            }

            searchTimeout = setTimeout(() => {
                // Filter selected employees based on search query
                const matchedEmployees = selectedEmployeeData.filter(emp => {
                    const name = emp.name.toLowerCase();
                    const email = emp.email ? emp.email.toLowerCase() : '';
                    return name.includes(query) || email.includes(query);
                });

                if (matchedEmployees.length === 0) {
                    leaderResults.innerHTML = '<div class="autocomplete-item" style="cursor: default; color: #999;">No matching employees found</div>';
                    leaderResults.style.display = 'block';
                    return;
                }

                leaderResults.innerHTML = matchedEmployees.map(emp => `
                    <div class="autocomplete-item" data-id="${emp.id}" data-label="${emp.name}">
                        ${emp.name}${emp.email ? ' (' + emp.email + ')' : ''}
                    </div>
                `).join('');

                leaderResults.style.display = 'block';

                // Add click handlers
                leaderResults.querySelectorAll('.autocomplete-item').forEach(item => {
                    item.addEventListener('click', () => {
                        const id = item.dataset.id;
                        const label = item.dataset.label;
                        
                        leaderSearchInput.value = label;
                        teamLeaderIdInput.value = id;
                        leaderResults.style.display = 'none';
                    });
                });
            }, 200);
        });

        // Close results when clicking outside
        document.addEventListener('click', (e) => {
            if (!leaderSearchInput.contains(e.target) && !leaderResults.contains(e.target)) {
                leaderResults.style.display = 'none';
            }
        });
    }
    
    /* ========================================
       FORM SUBMISSION
    ======================================== */
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Validate minimum employees again
            if (preselectedEmployees.length < 4) {
                alert('⚠️ Minimum 4 employees required to create a project.\n\nPlease go back and select at least 4 employees.');
                return;
            }
            
            // Validate team leader selection
            if (!teamLeaderIdInput.value) {
                alert('Please select a team leader from the dropdown.');
                return;
            }
            
            // Validate that selected leader is in the employee list
            const leaderId = parseInt(teamLeaderIdInput.value);
            if (!preselectedEmployees.includes(leaderId.toString()) && !preselectedEmployees.includes(leaderId)) {
                alert('Invalid team leader selection. Please select a team leader from your chosen employees.');
                teamLeaderIdInput.value = '';
                leaderSearchInput.value = '';
                return;
            }
            
            if (selectedEmployeeData.length === 0) {
                alert('No employees selected. Please go back to the Employee Directory.');
                return;
            }
            
            // Prepare form data
            const formData = new FormData(form);
            
            // Add employee IDs (these will be added as 'member' role, not 'team_leader')
            preselectedEmployees.forEach(id => {
                formData.append('employee_ids[]', id);
            });
            
            // Disable submit button
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i data-feather="loader"></i> Creating...';
            feather.replace();
            
            try {
                const response = await fetch('create-new-project.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Clear sessionStorage
                    sessionStorage.removeItem('preselectedEmployees');
                    
                    // Show success message
                    alert('✅ Project created successfully!');
                    
                    // Redirect to new project page
                    window.location.href = `../project/projects.php?project_id=${result.project_id}`;
                } else {
                    alert('Error: ' + result.message);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                    feather.replace();
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                feather.replace();
            }
        });
    }
    
    /* INITIALIZE */
    loadPreselectedEmployees();
});