<?php
session_start();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['role'], $_SESSION['email'], $_SESSION['user_id']);

if (!$isLoggedIn) {
    $role = 'manager';
    $isManager = true;
    $isTeamLeader = false;
} else {
    $role = $_SESSION['role'];
    $isManager = ($role === 'manager');
    $isTeamLeader = ($role === 'team_leader');
}

// Team members should be redirected to projects page
if ($role === 'team_member') {
    header('Location: ../project/projects-overview.php');
    exit;
}
?>
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
    <link rel="icon" type="image/png" href="/favicon.png">
    <script src="https://unpkg.com/feather-icons"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body id="home-page">
    <?php include '../to-do/todo_widget.php'; ?>
    <div class="dashboard-container">
        <nav class="sidebar">
            <div class="nav-top">
                <div class="logo-container">
                    <img src="../logo.png" alt="Make-It-All Logo" class="logo-icon">
                </div>
                <ul class="nav-main">
                    <?php if ($isManager || $isTeamLeader): ?>
                        <li class="active-parent"><a href="home.php"><i data-feather="home"></i>Home</a></li>
                    <?php endif; ?>
                    <li><a href="../project/projects-overview.php"><i data-feather="folder"></i>Projects</a></li>
                    <?php if ($isManager): ?>
                        <li><a href="../employees/employee-directory.php"><i data-feather="users"></i>Employees</a></li>
                    <?php endif; ?>
                    <li><a href="../knowledge-base/knowledge-base.html"><i data-feather="book-open"></i>Knowledge
                            Base</a></li>
                </ul>
            </div>
            <div class="nav-footer">
                <ul>
                    <li><a href="../settings.php"><i data-feather="settings"></i>Settings</a></li>
                </ul>
            </div>
        </nav>
        <main class="main-content">
            <header class="home-header">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                    <h1 style="margin: 0;">Welcome Back!</h1>
                    <div id="manager-actions" style="display: none;">
                        <a href="create-project.php" class="create-post-btn">
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
                    </select>

                    <!-- Filter dropdown to filter by project health -->
                    <select class="filter-dropdown" id="health-filter">
                        <option value = "">All Health Levels</option>
                    </select>

                    <!-- Filter dropdown to filter by resource level --> 
                    <select class="filter-dropdown" id="status-filter">
                        <option value="">All Resource Levels</option>
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
                            <label for="strugglingEmployees">Need Support</label>
                        </div>

                        <!-- Filter dropdown to filter by projects -->
                        <select class="filter-dropdown" id="filter-by-project-name">
                            <option value ="">All Projects</option>
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
                            </tbody>
                        </table>
                    </div>
                </div>
                <p id="no-employees-message" style="display: none; text-align: center; margin-top: 10px;">
                    No Employees Found.
                </p>
                <p id="no-struggling-employees-message" style="display: none; text-align: center; margin-top: 10px;">
                    No Employees Found.
                </p>
            </div>
        </main>
    </div>
    <script src="../app.js"></script>
    <script>
        async function displayProjects() {
            const container = document.getElementById("project-health-grid-container");

            try {
                const response = await fetch('get_projects.php');
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
                    let healthColour = "";
                    let healthBgColour = "";
                    let healthClass = "";
                    let healthContainerColour = "";
                    if (overdueTasks === 0){
                        projectHealth = "Good";
                        healthColour = "#10b981";
                        healthBgColour = "#d1fae5";
                        healthContainerColour = "#f0fdf4";
                        healthClass = "health-good";
                    } else if (overdueTasks >= 1 && overdueTasks < 5){
                        projectHealth = "Average";
                        healthColour = "#f59e0b";
                        healthBgColour = "#fef3c7";
                        healthContainerColour = "#fffbeb";
                        healthClass = "health-medium";
                    } else {
                        projectHealth = "Poor";
                        healthColour = "#ef4444";
                        healthBgColour = "#fee2e2";
                        healthContainerColour = "#fef2f2";
                        healthClass = "health-poor";
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
                    projectCard.classList.add(healthClass);
                    projectCard.dataset.projectId = project.project_id;
                    projectCard.dataset.priority = project.priority;
                    projectCard.dataset.resourceLevel = project.resource_level;
                    projectCard.dataset.deadline = project.deadline;
                    projectCard.dataset.completion = onTimePercent;
                    projectCard.dataset.health = healthClass;

                    // Add click handler to navigate to project page
                    projectCard.addEventListener('click', () => {
                        window.location.href = `../project/progress.php?project_id=${project.project_id}`;
                    });

                    projectCard.innerHTML = `
                    <h3>${project.project_name}</h3>
                    <div class="health-indicator-container ${healthClass}" style="background-color: ${healthContainerColour};">
                        <div class="health-icon-wrapper">
                            <div class="health-icon-outer" style="background-color: ${healthBgColour};">
                                <div class="health-icon-inner" style="background-color: ${healthColour};"></div>
                            </div>
                        </div>
                        <span class="health-label">${projectHealth} Health</span>
                    </div>
                    <div class="project-info">
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
                            <span class="resource-badge ${badgeClass}">${project.resource_level.replace('_', ' ').toUpperCase()}</span>
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
                loadHealthOptions();

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
                const normalised = item.priority.toLowerCase().trim();
                option.value = normalised;
                option.textContent = normalised.charAt(0).toUpperCase() + normalised.slice(1);
                priorityFilter.appendChild(option);
            });
        }

        let originalProjectCards = [];
        console.log(originalProjectCards);
        let currentProjectCards = [];

        // the different filters 
        const activeFilters = {
            priority: "",
            health: "",
            level: "",
            deadline: "",
            percentage: "",
            searchQuery: ""
        };

        // function to filter and sort the project cards

        function filterProjects(){
            const container = document.getElementById("project-health-grid-container");

            let cards = [...originalProjectCards];

            cards = applyFiltering(cards);

            cards = applySorting(cards);

            container.innerHTML = "";
            cards.forEach(card => container.appendChild(card));

            const message = document.getElementById("no-projects-message");
            if (cards.length === 0){
                message.style.display = "block";
            } else {
                message.style.display = "none";
            }

            currentProjectCards = cards;

        }

        // function to sort the project cards 
        function applySorting(cards){

            let sorted = [...cards];

            // sort by deadline

            if (activeFilters.deadline === "asc" || activeFilters.deadline === "desc"){
                sorted.sort((a,b) => {
                    const dateA = new Date(a.dataset.deadline);
                    const dateB = new Date(b.dataset.deadline);

                    if (activeFilters.deadline === "asc"){
                        return dateA - dateB;
                    } else {
                        return dateB - dateA;
                    }
                });
            }

            // sort by completion percentage 

            if (activeFilters.percentage === "asc" || activeFilters.percentage === "desc"){
                sorted.sort((a, b) => {
                    const completionA = Number(a.dataset.completion);
                    const completionB = Number(b.dataset.completion);

                    if (activeFilters.percentage === "asc"){
                        return completionA - completionB;
                    } else {
                        return completionB - completionA;
                    }
                });
            }

            return sorted;
        }

        // function to filter the project cards 
        function applyFiltering(cards){
            return cards.filter(card => {

                // Priority filter 

                if (activeFilters.priority && card.dataset.priority !== activeFilters.priority){
                    return false;
                }

                // Resource level filter 

                if (activeFilters.level && card.dataset.resourceLevel !== activeFilters.level){
                    return false;
                }

                // Health level filter

                if (activeFilters.health && card.dataset.health !== activeFilters.health){
                    return false;
                }

                // search filter 

                if (activeFilters.searchQuery){
                    const projectName = card.querySelector("h3").textContent.toLowerCase();
                    if (!projectName.includes(activeFilters.searchQuery.toLowerCase())){
                        return false;
                    }
                }

                return true;

            });
        }

        // function to create the filter pill 
        function createFilterPill(filterType, value){

            console.log("Before mapping:", filterType, value);

            value = String(value).trim().toLowerCase();

            const labelMaps = {
                priority: {
                    high: "High",
                    medium: "Medium",
                    low: "Low"
                },
                level: {
                    under_resourced: "Under Resourced",
                    sufficient: "Sufficient",
                    tight: "Tight"
                },
                health: {
                    "health-good": "Good",
                    "health-medium": "Average",
                    "health-poor": "Poor"
                },
                deadline: {
                    asc: "Earliest To Latest",
                    desc: "Latest To Earliest"
                },
                percentage: {
                    asc: "Lowest To Highest",
                    desc: "Highest To Lowest"
                }
            }

            let friendlyValue = value; 

            if (labelMaps[filterType] && labelMaps[filterType][value]){
                friendlyValue = labelMaps[filterType][value];
            } else if (filterType === "health" && value.startsWith("health-")){
                friendlyValue = value.replace("health-", "");
                friendlyValue = friendlyValue.charAt(0).toUpperCase() + friendlyValue.slice(1);
            } else {
                friendlyValue = value.charAt(0).toUpperCase() + value.slice(1).replace(/_/g, ' ');
            }

            console.log("After mapping:", filterType, friendlyValue);

            const pill = document.createElement("span");
            pill.classList.add("filter-pill", filterType);
            pill.innerHTML = `
                ${filterType.charAt(0).toUpperCase() + filterType.slice(1)}: ${friendlyValue}
                <span class="remove-pill">x</span>
            `;
            pill.querySelector(".remove-pill").addEventListener("click", function(){
                activeFilters[filterType] = "";
                let dropdown;
                if (filterType === 'priority'){
                    dropdown = document.getElementById('priority-filter');
                } else if (filterType === 'health'){
                    dropdown = document.getElementById('health-filter');
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

            console.log("Priority raw value:", JSON.stringify(value));

            document.getElementById("filter-pills").appendChild(pill);
        }

        // sort by priority event listener 
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

        // function to convert to title case
        function toTitleCase(str){
            return str
                .replace(/_/g, " ")
                .replace(/\w\S*/g, txt =>
                    txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase()
                );
        }

        // function to load the health level options
        function loadHealthOptions(){
            const healthFilter = document.getElementById("health-filter");
            const cards = document.querySelectorAll(".project-card");
            const healthLevels = new Set();
            healthFilter.innerHTML = '<option value="">All Health Levels</option>';

            cards.forEach(card => {
                if (card.dataset.health){
                    healthLevels.add(card.dataset.health);
                }
            });

            const labels = {
                "health-good": "Good",
                "health-medium": "Average",
                "health-poor": "Poor"
            };

            healthLevels.forEach(level => {
                const option = document.createElement("option");
                option.value = level;
                option.textContent = labels[level] || level;
                healthFilter.appendChild(option);
            });
        }

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
                const formatted = toTitleCase(level);
                const option = document.createElement("option");
                option.value = level;
                option.textContent = formatted;
                resourceFilter.appendChild(option);
            });
        }

        // sort by resource level event listener 
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

        // filter by health level event listener
        const healthFilter = document.getElementById("health-filter");
        healthFilter.addEventListener("change", function(){
            const selectedHealth = this.value;
            activeFilters.health = selectedHealth;

            document.querySelectorAll(".filter-pill.health").forEach(pill => pill.remove());

            if (selectedHealth){
                createFilterPill('health', selectedHealth);
            }

            filterProjects();
        });

        // sort by deadline event listener 
        document.getElementById("deadline-sort-filter").addEventListener("change", function(){
            const selectedDeadlineSort = this.value;
            activeFilters.deadline = selectedDeadlineSort;
            document.querySelectorAll(".filter-pill.deadline").forEach(pill => pill.remove());
            if(selectedDeadlineSort){
                createFilterPill('deadline', selectedDeadlineSort);
            }
            filterProjects();
        });

        // sort by completion percentage event listener
        document.getElementById("completion-sort-filter").addEventListener("change", function(){
            const selectedCompletionSort = this.value;
            activeFilters.percentage = selectedCompletionSort;
            document.querySelectorAll(".filter-pill.percentage").forEach(pill => pill.remove());
            if (selectedCompletionSort){
                createFilterPill('percentage', selectedCompletionSort);
            }

            filterProjects();
        });

        // search bar event listener 
        const searchInput = document.querySelector(".search-bar input");
        searchInput.addEventListener("input", function(){
            activeFilters.searchQuery = this.value.trim();
            filterProjects();
        });

        // employee performance table 

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

                let overdueTaskClass = "";
                if (emp.overdue_tasks == 0){
                    overdueTaskClass = "green-percent";
                } else if (emp.overdue_tasks == 1){
                    overdueTaskClass = "amber-percent";
                } else{
                    overdueTaskClass = "red-percent";
                }

                let projectsHTML = "";
                if (emp.projects){
                    const projectList = emp.projects.split(',');
                    projectsHTML = projectList.map(name => `<span class="project-badge">${name}</span>`).join(' ');
                }

                row.innerHTML = `
                    <td>
                        <a class="employee-link" href="../employees/employee-profile.php?id=${encodeURIComponent(emp.user_id)}">
                            ${emp.full_name}
                        </a>
                    </td>
                    <td>${projectsHTML}</td>
                    <td>${totalTasks}</td>
                    <td>${completedTasks}</td>
                    <td class="${overdueTaskClass}">${emp.overdue_tasks}</td>
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
            
            try {
                const response = await fetch('get_employees.php');
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
            } catch (error) {
                console.error("Error fetching employees:", error);
                tableBody.innerHTML = `<tr><td colspan="6">Error loading employees.</td></tr>`;
            }
        }

        // function to load the project name options by puling from the database 
        async function loadProjectNameOptions (){
            try{
                const response = await fetch('get-project-name.php');
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
            try{
                let employeesToDisplay = allEmployees;
                if (this.checked){
                    const response = await fetch('get-struggling-employees.php');
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


       document.addEventListener("DOMContentLoaded", async () => {
            await displayProjects();
            loadPriorityOptions();
            const container = document.getElementById("project-health-grid-container");
            originalProjectCards = Array.from(container.querySelectorAll(".project-card"));
            currentProjectCards = [...originalProjectCards];
            filterProjects();
            displayEmployees();
            loadProjectNameOptions();

            // Show Employees tab for managers
            const currentUser = JSON.parse(localStorage.getItem('currentUser') || '{}');
            if (currentUser.role === 'manager') {
                document.getElementById('nav-employees').style.display = 'block';
            }
            feather.replace();
        });
    </script>
</body>
</html>
