<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make-It-All - Home</title>
    <link rel="stylesheet" href="../dashboard.css">
    <link rel="stylesheet" href="home.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body id="home-page">
    <div class="dashboard-container">
        <nav class="sidebar">
            <div class="nav-top">
                <div class="logo-container">
                    <img src="../logo.png" alt="Make-It-All Logo" class="logo-icon">
                </div>
                <ul class="nav-main">
                    <li class="active-parent"><a href="home.html"><i data-feather="home"></i>Home</a></li>
                    <li><a href="../project/projects.html"><i data-feather="folder"></i>Projects</a></li>
                    <li id="nav-archive" style="display: none;"><a href="../project/project-archive.html"><i
                                data-feather="archive"></i>Project Archive</a></li>
                    <li><a href="../knowledge-base/knowledge-base.html"><i data-feather="book-open"></i>Knowledge
                            Base</a></li>
                </ul>
            </div>
            <div class="nav-footer">
                <ul>
                    <li><a href="../settings.html"><i data-feather="settings"></i>Settings</a></li>
                </ul>
            </div>
        </nav>
        <main class="main-content">
            <header class="home-header">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                    <h1 style="margin: 0;">Welcome Back!</h1>
                    <div id="manager-actions" style="display: none;">
                        <a href="create-project.html" class="create-post-btn">
                            <i data-feather="folder-plus"></i> Create Project
                        </a>
                    </div>
                </div>
                <p class="page-label">Here's what's happening with your projects</p>
            </header>
            <div class="manager-dashboard" id="manager-dashboard" style="display : block;">
                <h2>Project Health</h2>
                <div class="project-health-controls">

                    <!-- search bar to search for projects -->
                    <div class="search-bar">
                        <input type="text" placeholder="Search projects...">
                        <span class="icon"><i data-feather="search"></i></span>
                    </div>

                    <!-- Filter dropdown to filter by priority --> 
                    <select class="filter-dropdown" id="priority-filter">
                        <option value="">All Priorites</option>
                        <!-- Options will be dynamically populated -->
                    </select>

                    <!-- Filter dropdown to filter by resource level --> 
                    <select class="filter-dropdown" id="status-filter">
                        <option value="">All Resource Levels</option>
                        <!-- options will be dynamically populated -->
                    </select>

                    <!-- Filter dropdown to sort by deadline --> 
                    <select class="filter-dropdown" id="deadline-sort-filter">
                        <option value ="">Sort By Deadline</option>
                        <option value="asc">Earliest To Latest</option>
                        <option value="desc">Latest To Earliest</option>
                    </select>

                    <!-- Filter dropdown to sort by completion % -->
                    <select class="filter-dropdown" id="completion-sort-filter">
                        <option value="">Sort By Completion %</option>
                        <option value="asc">Lowest To Highest</option>
                        <option value="desc">Highest To Lowest</option>
                    </select>
                </div>

                <!-- create filter fills -->
                <div class="filter-pills" id="filter-pills">
                    <!-- filter pills will be dynamically inserted here -->
                </div>

                <!-- project health cards will be displayed here -->
                <div class="project-health-content">
                    <div class="project-health-grid" id="project-health-grid-container"></div>

                    <!-- If there are no projects found after being filtered this message will be displayed -->
                    <p id="no-projects-message" style="display: none; text-align: center; margin-top: 20px;">
                        No projects found.
                    </p>
                </div>
                <!-- Employee performance table -->
                <div class="employee-table">
                    <h2>Employee Performance</h2>
                    <div class="employee-performance-controls">

                        <!-- search bar to search for employees -->
                        <div class="search-bar">
                            <input type="text" id="employee-search" placeholder="Search Employees...">
                            <span class="icon"><i data-feather="search"></i></span>
                        </div>

                        <!-- checkbox to filter for struggling employees -->
                         <div class="checkbox-filter">
                            <input type="checkbox" id="struggling-checkbox" name="strugglingCheckbox">
                            <label for="strugglingEmployees">Struggling Employees</label>
                        </div>

                        <!-- Filter dropdown to filter by projects -->
                        <select class="filter-dropdown" id="filter-by-project-name">
                            <option value ="">All Projects</option>
                            <!-- options will be dynamically populated -->
                        </select>
                    </div>
                    <div class="employee-table-content" id="employee-table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Employee Name</th>
                                    <th>Project</th>
                                    <th>Tasks Assigned</th>
                                    <th>Tasks Completed</th>
                                    <th>Overdue Tasks</th>
                                    <th>On-Time</th>
                                </tr>
                            </thead>
                            <tbody id="employee-table">
                                <!-- rows will be dynamically inserted here -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <p id="no-employees-message" style="display: none;">
                    No Employees Found.
                </p>
                <p id="no-struggling-employees-message" style="display: none; text-align: center; margin-top: 10px;">
                    No Struggling Employees Found.
                </p>
            </div>
        </main>
    </div>
    <script src="../app.js"></script>
    <script>
        async function displayProjects() {
            const managerId = 1; // replace with function to get manager Id dynamically 
            const container = document.getElementById("project-health-grid-container");

            try {
                const response = await fetch(`get_projects.php?created_by=${managerId}`);
                console.log(response);
                const projects = await response.json();
                console.log(projects);

                // Manager has no projects 
                if (projects.length === 0) {
                    container.innerHTML = "<p>No active projects found.</p>";
                    return;
                }

                container.innerHTML = "";
                projects.forEach(project => {

                    // calculates the completion percentage 
                    const totalTasks = project.total_tasks;
                    const completedTasks = project.completed_tasks;
                    const onTimePercent = totalTasks > 0 ? Math.round((completedTasks / totalTasks) * 100) : 0;

                    // determines the status colour by looking at the resource level
                    const dotColour = project.overdue_tasks > 0 ? 'red' : 'green';
                    let badgeClass = "";
                    if (project.resource_level === 'under_resourced'){
                        badgeClass = "under-resourced";
                    } else if (project.resource_level === 'sufficient'){
                        badgeClass = "sufficient";
                    }
                    else if (project.resource_level === 'tight'){
                        badgeClass = "tight";
                    }

                    // determines project health by looking at the number of overdue tasks
                    const overdueTasks = Number(project.overdue_tasks);

                    let projectHealth = "";
                    if (overdueTasks === 0){
                        projectHealth = "Good";
                    } else if (overdueTasks >= 1 && overdueTasks < 5){
                        projectHealth = "Average";
                    } else {
                        projectHealth = "Poor";
                    }

                    // determines the short recommendation text by looking ath the resource level 
                    let recommendation = "";
                    if (project.resource_level === "under_resourced"){
                        recommendation = "Allocate Extra Resources";
                    }
                    else if (project.resource_level === "sufficient"){
                        recommendation = "No Immediate Action";
                    } else if (project.resource_level === "tight"){
                        recommendation = "Avoid Adding Extra Tasks"
                    }

                    // determines the colour of the priority badge by looking at the priority 
                    let priorityClass = "";
                    if (project.priority === 'high'){
                        priorityClass = "high";
                    } else if (project.priority === 'medium'){
                        priorityClass = "medium";
                    }

                    // creates the project health card 

                    const projectCard = document.createElement("div");
                    projectCard.classList.add("project-card");
                    projectCard.dataset.priority = project.priority;
                    projectCard.dataset.resourceLevel = project.resource_level;
                    projectCard.dataset.deadline = project.deadline;
                    projectCard.dataset.completion = onTimePercent;
                    if (project.priority === "high"){
                        projectCard.classList.add("high-priority");
                    } else if (project.priority === "medium"){
                        projectCard.classList.add("medium-priority");
                    }
                    projectCard.innerHTML = `
                    <h3>${project.project_name}</h3>
                    <div class="project-info">
                        <div class="info-row">
                            <span class="info-label">Project Health: </span>
                            <span class="info-value">${projectHealth}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Priority:</span>
                            <span class="priority-badge ${priorityClass}">${project.priority}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Deadline:</span>
                            <span class="info-value">${new Date(project.deadline).toLocaleDateString()}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Completion:</span>
                            <span class="info-value">${onTimePercent}%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${onTimePercent}%;"></div>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Total Tasks:</span>
                            <span class="info-value">${totalTasks}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Completed Tasks:</span>
                            <span class="info-value">${completedTasks}</span>
                        </div>
                        <div class="status-indicator">
                            <span class="status-dot" style="background-color: ${dotColour};"></span>
                            <span class="info-label">Overdue Tasks:</span>
                            <span class="info-value">${project.overdue_tasks}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Resource Level:</span>
                            <span class="resource-badge ${badgeClass}">${project.resource_level.replace('_', ' ')}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Recommendation:</span>
                            <span class="info-value">${recommendation}</span>
                        </div>
                    </div>
                    `;

                    container.appendChild(projectCard);
                });

                loadResourceLevelOptions();

                feather.replace();
            } catch (error) {
                console.error("Error fetching projects:", error);
                container.innerHTML = "<p>Error loading projects.</p>";
            }
        }

        // loads the priority options by pulling data from the database

        async function loadPriorityOptions(){
            const response = await fetch('get-priority.php');
            const priorities = await response.json();
            console.log(priorities);

            const priorityFilter = document.getElementById("priority-filter");

            priorities.forEach(item => {
                const option = document.createElement("option");
                option.value = item.priority;
                option.textContent = item.priority.charAt(0).toUpperCase() + item.priority.slice(1);
                priorityFilter.appendChild(option);
            });
        }

        // the different filters 
        const activeFilters = {
            priority: "",
            level: "",
            deadline: "",
            percentage: "",
            searchQuery: ""
        };

        // function to filter the project cards 
        function filterProjects(){
            const cards = document.querySelectorAll(".project-card");
            let anyVisible = false;
            cards.forEach(card => {
                let showCard = true;

                if (activeFilters.priority && card.dataset.priority !== activeFilters.priority){
                    showCard = false;
                }

                if (activeFilters.level && card.dataset.resourceLevel !== activeFilters.level){
                    showCard = false;
                }

                if (activeFilters.searchQuery){
                    const projectName = card.querySelector("h3").textContent.toLowerCase();
                    if (!projectName.includes(activeFilters.searchQuery.toLowerCase())){
                        showCard = false;
                    }
                }

                card.style.display = showCard ? "block" : "none";

                if (showCard) anyVisible = true;
            });

            const message = document.getElementById("no-projects-message");
            if (!anyVisible){
                message.style.display = "block";
            } else {
                message.style.display = "none";
            }
        }

        // function to create the filter pill 
        function createFilterPill(filterType, value){
            const pill = document.createElement("span");
            pill.classList.add("filter-pill", filterType);
            pill.innerHTML = `
                ${filterType.charAt(0).toUpperCase() + filterType.slice(1)}: ${value.charAt(0).toUpperCase() + value.slice(1).replace(/_/g, ' ')}
                <span class="remove-pill">x</span>
            `;
            pill.querySelector(".remove-pill").addEventListener("click", function(){
                activeFilters[filterType] = "";
                let dropdown;
                if (filterType === 'priority'){
                    dropdown = document.getElementById('priority-filter');
                } else if (filterType === 'level'){
                    dropdown = document.getElementById('status-filter');
                } else if (filterType === 'deadline'){
                    dropdown = document.getElementById('deadline-sort-filter');
                } else if (filterType === 'percentage'){
                    dropdown = document.getElementById('completion-sort-filter');
                }

                if(dropdown){
                    dropdown.value = "";
                }

                pill.remove();
                filterProjects();
            });
            document.getElementById("filter-pills").appendChild(pill);
        }

        // function to filter the project cards by priority 
        function filterProjectsByPriority(selectedPriority){
            const cards = document.querySelectorAll(".project-card");
            cards.forEach(card => {
                if (!selectedPriority || card.dataset.priority === selectedPriority){
                    card.style.display = "block";
                } else {
                    card.style.display = "none";
                }
            });
        }

        const priorityFilter = document.getElementById("priority-filter");
        priorityFilter.addEventListener("change", function(){
            const selectedPriority = this.value;
            activeFilters.priority = selectedPriority;

            document.querySelectorAll(".filter-pill.priority").forEach(pill => pill.remove());
            if (selectedPriority){
                createFilterPill('priority', selectedPriority);
            }
            filterProjects();
        });

        // function to load the resource level options
        function loadResourceLevelOptions(){
            const resourceFilter = document.getElementById("status-filter");
            const cards = document.querySelectorAll(".project-card");
            const resourceLevels = new Set();
            resourceFilter.innerHTML = '<option value="">All Resource Levels</option>';
            cards.forEach(card => {
                resourceLevels.add(card.dataset.resourceLevel);
            });
            resourceLevels.forEach(level => {
                const option = document.createElement("option");
                option.value = level;
                option.textContent = level.replace("_", " ");
                resourceFilter.appendChild(option);
            });
        }

        // function to filter the project cards by resource level
        function filterProjectsByResourceLevel(selectedResourceLevel){
            const cards = document.querySelectorAll(".project-card");
            cards.forEach(card => {
                if(!selectedResourceLevel || card.dataset.resourceLevel === selectedResourceLevel){
                    card.style.display = "block";
                } else {
                    card.style.display = "none";
                }
            });
        }

        const resourceFilter = document.getElementById("status-filter");
        resourceFilter.addEventListener("change", function(){
            const selectedResourceLevel = this.value;
            activeFilters.level = selectedResourceLevel;

            document.querySelectorAll(".filter-pill.level").forEach(pill => pill.remove());

            if (selectedResourceLevel){
                createFilterPill('level', selectedResourceLevel);
            }

            filterProjects();
        });

        // function to sort the project cards by deadline
        function sortProjectsByDeadline(selectedDeadlineSort){
            const container = document.getElementById("project-health-grid-container");
            const cards = Array.from(container.querySelectorAll(".project-card")).filter(card => card.style.display !== "none");
            cards.sort((a, b) => {
                const dateA = new Date(a.dataset.deadline);
                const dateB = new Date(b.dataset.deadline);
                if (selectedDeadlineSort === "asc"){
                    return dateA - dateB;
                } else if (selectedDeadlineSort === "desc"){
                    return dateB - dateA;
                } 
                return 0;
            });

            container.innerHTML = "";
            cards.forEach(card => container.appendChild(card));
        }

        document.getElementById("deadline-sort-filter").addEventListener("change", function(){
            const selectedDeadlineSort = this.value;
            activeFilters.deadline = selectedDeadlineSort;
            document.querySelectorAll(".filter-pill.deadline").forEach(pill => pill.remove());
            filterProjects();
            if(selectedDeadlineSort){
                createFilterPill('deadline', selectedDeadlineSort);
                sortProjectsByDeadline(selectedDeadlineSort)
            }
        });

        // function to sort project cards by completion percentage 
        function sortProjectsByCompletion (selectedCompletionSort){
            const container = document.getElementById("project-health-grid-container");
            const cards = Array.from(container.querySelectorAll(".project-card")).filter(card => card.style.display !== "none");
            cards.sort((a,b) => {
                const completionA = Number(a.dataset.completion);
                const completionB = Number(b.dataset.completion);
                if (selectedCompletionSort === "asc"){
                    return completionA - completionB;
                } else if (selectedCompletionSort === "desc"){
                    return completionB - completionA;
                }
                return 0;
            });
            container.innerHTML = "";
            cards.forEach(card => container.appendChild(card));
        }

        document.getElementById("completion-sort-filter").addEventListener("change", function(){
            const selectedCompletionSort = this.value;
            activeFilters.percentage = selectedCompletionSort;
            document.querySelectorAll(".filter-pill.percentage").forEach(pill => pill.remove());
            filterProjects();
            if (selectedCompletionSort){
                createFilterPill('percentage', selectedCompletionSort);
                sortProjectsByCompletion(selectedCompletionSort);
            }
        });

        const searchInput = document.querySelector(".search-bar input");
        searchInput.addEventListener("input", function(){
            activeFilters.searchQuery = this.value.trim();
            filterProjects();
        });

        let allEmployees = []
        
        // function to render employee table 
        function renderEmployeeTable(employees){
            const tableBody = document.getElementById("employee-table");
            tableBody.innerHTML = "";

            employees.forEach(emp => {
                const totalTasks = emp.total_tasks;
                const completedTasks = emp.completed_tasks;
                const onTimePercent = totalTasks > 0 ? Math.round((completedTasks / totalTasks) * 100) : 0;
                const row = document.createElement("tr");

                let percentClass = "";
                if (onTimePercent > 70){
                    percentClass = "green-percent";
                } else if (onTimePercent >= 30 && onTimePercent <= 69){
                    percentClass = "amber-percent";
                } else if (onTimePercent <= 29){
                    percentClass = "red-percent";
                }

                let projectsHTML = "";
                if (emp.projects){
                    const projectList = emp.projects.split(',');
                    projectsHTML = projectList.map(name => `<span class="project-badge">${name}</span>`).join(' ');
                }

                row.innerHTML = `
                    <td>${emp.full_name}</td>
                    <td>${projectsHTML}</td>
                    <td>${totalTasks}</td>
                    <td>${completedTasks}</td>
                    <td>${emp.overdue_tasks}</td>
                    <td class="${percentClass}">${onTimePercent}%</td>
            `   ;

                tableBody.appendChild(row);
            });
        }

        // function to get struggling employees
        async function displayEmployees() {
            const tableContainer = document.getElementById("employee-table-container");
            const tableBody = document.getElementById("employee-table");
            const noEmployeesMessage = document.getElementById("no-employees-message");
            
            const managerId = 2; 
            try {
                const response = await fetch(`get_employees.php?assigned_by=${managerId}`);
                console.log(response);
                const employees = await response.json();
                console.log(employees);

                if (!Array.isArray(employees) || employees.length == 0){
                    tableContainer.style.display = "none";
                    noEmployeesMessage.style.display = "block";
                    return;
                }

                allEmployees = employees;
                console.log(allEmployees.length);
                noEmployeesMessage.style.display = "none";
                tableContainer.style.display = "block";
                renderEmployeeTable(allEmployees);
                /**tableBody.innerHTML = "";

                console.log(employees);
                employees.forEach(emp => {
                    const totalTasks = emp.total_tasks;
                    const completedTasks = emp.completed_tasks;
                    const onTimePercent = totalTasks > 0 ? Math.round((completedTasks / totalTasks) * 100) : 0;
                    const row = document.createElement("tr");

                    let percentClass = "";
                    if (onTimePercent > 70){
                        percentClass = "green-percent";
                    } else if (onTimePercent >= 30 && onTimePercent <= 69){
                        percentClass = "amber-percent";
                    } else if (onTimePercent <= 29){
                        percentClass = "red-percent";
                    }

                    row.innerHTML = `
                        <td>${emp.full_name}</td>
                        <td>${emp.projects}</td>
                        <td>${totalTasks}</td>
                        <td>${completedTasks}</td>
                        <td>${emp.overdue_tasks}</td>
                        <td class="${percentClass}">${onTimePercent}%</td>
            `       ;

                    tableBody.appendChild(row);
                });**/

            } catch (error) {
                console.error("Error fetching employees:", error);
                tableBody.innerHTML = `<tr><td colspan="6">Error loading employees.</td></tr>`;
            }
        }

        // function to load the project name options by puling from the database 
        async function loadProjectNameOptions (){
            const teamLeaderId = 2; // change to be dynamic 

            try{
                const response = await fetch(`get-project-name.php?team_leader_id=${teamLeaderId}`);
                console.log(response);
                const projectNames = await response.json();
                console.log(projectNames);
                projectNames.forEach(project =>{
                    const option = document.createElement("option");
                    option.value = String(project.project_id);
                    option.textContent = project.project_name.charAt(0).toUpperCase() + project.project_name.slice(1);
                    projectNameFilter.appendChild(option);
                });
            }catch (error){
                console.error("Error Fetching Project Names:" , error);
            };
        }

        const projectNameFilter = document.getElementById("filter-by-project-name"); 
        projectNameFilter.addEventListener("change", function(){
            const selectedProjectId = this.value;
            console.log('Selected option:', this.selectedIndex, this.options[this.selectedIndex]);
            console.log('selectedProjectId:', selectedProjectId, 'type:', typeof selectedProjectId);

            let filteredEmployees;

            if (selectedProjectId === ""){
                filteredEmployees = allEmployees;
            } else {
                filteredEmployees = allEmployees.filter(emp => {
                    if (!emp.project_ids){
                        return false;
                    }
                    const projectIds = emp.project_ids.split(',');
                    return projectIds.includes(selectedProjectId);
                });
            }

            const noEmployeesMessage = document.getElementById("no-employees-message");

            if (filteredEmployees.length === 0){
                noEmployeesMessage.style.display = "block";
                document.getElementById("employee-table-container").style.display = "none";
            } else {
                noEmployeesMessage.style.display = "none";
                document.getElementById("employee-table-container").style.display = "block";
            }
            renderEmployeeTable(filteredEmployees);
        });

        // filter by struggling employees 

        const strugglingCheckbox = document.getElementById("struggling-checkbox");

        strugglingCheckbox.addEventListener("change", async function(){
            const teamLeaderId = 2; // change to be dynamic
            try{
                let employeesToDisplay = allEmployees;
                if (this.checked){
                    const response = await fetch(`get-struggling-employees.php?team_leader_id=${teamLeaderId}`);
                    employeesToDisplay = await response.json();
                }

                const tableContainer = document.getElementById("employee-table-container");
                const noEmployeesMessage = document.getElementById("no-employees-message");
                const noStrugglingMessage = document.getElementById("no-struggling-employees-message");

                if (this.checked && employeesToDisplay.length === 0) {
                    noStrugglingMessage.style.display = "block";
                    tableContainer.style.display = "none";
                } else if (!this.checked && employeesToDisplay.length === 0){
                    noEmployeesMessage.style.display = "block";
                    tableContainer.style.display = "none";
                    noStrugglingMessage.style.display = "none";
                } else {
                    noEmployeesMessage.style.display = "none";
                    noStrugglingMessage.style.display = "none";
                    tableContainer.style.display = "block";
                }

                renderEmployeeTable(employeesToDisplay);
            } catch(error){
                console.error("Error fetching employees:", error);
            }
        });

        // search for employees by name 
        const employeeSearchInput = document.getElementById("employee-search");
        employeeSearchInput.addEventListener("input", function(){
            const query = this.value.trim().toLowerCase();
            try{
                let employeesToDisplay = allEmployees;
                const filteredEmployees = employeesToDisplay.filter(emp =>{
                    return emp.full_name.toLowerCase().includes(query);
                });

                renderEmployeeTable(filteredEmployees);

                const tableContainer = document.getElementById("employee-table-container");
                const noEmployeesMessage = document.getElementById("no-employees-message");

                if (filteredEmployees.length === 0) {
                    noEmployeesMessage.style.display = "block";
                    tableContainer.style.display = "none";
                } else {
                    noEmployeesMessage.style.display = "none";
                    tableContainer.style.display = "block";
                }
            } catch (error){
                console.error("Error Fetching Employees:" , error);
            }
        });


       document.addEventListener("DOMContentLoaded", () => {
            displayProjects();
            loadPriorityOptions();
            displayEmployees();
            loadProjectNameOptions();
        });
    </script>
</body>
</html>