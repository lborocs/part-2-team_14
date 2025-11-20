/*
* Make-It-All Knowledge Base
* This file simulates a backend and user authentication for the prototype.
*/

/**
 * Shows a success notification message that auto-dismisses after 3 seconds
 * @param {string} message - The message to display
 */
function showSuccessNotification(message) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = 'success-notification';
    notification.innerHTML = `
        <i data-feather="check-circle"></i>
        <span>${message}</span>
    `;

    // Add to body
    document.body.appendChild(notification);

    // Replace feather icons
    feather.replace();

    // Remove after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Accounts created for the purpose of the prototype.
// In a real app, this data would come from a server and database.
// We use 'localStorage' to make new posts and replies persist during the session.

// Simulated User Accounts
const simUsers = {
    'user@make-it-all.co.uk': {
        name: 'Steve Adams',
        role: 'member',
        avatarClass: 'avatar-1'
    },
    'specialist@make-it-all.co.uk': {
        name: 'Jane Doe',
        role: 'specialist',
        avatarClass: 'avatar-4'
    },
    'manager@make-it-all.co.uk': {
        name: 'Ben Carter',
        role: 'manager',
        avatarClass: 'avatar-2'
    },
    'leader@make-it-all.co.uk': {
        name: 'Sarah Johnson',
        role: 'team_leader',
        avatarClass: 'avatar-3'
    },
    'member1@make-it-all.co.uk': {
        name: 'David Lee',
        role: 'member',
        avatarClass: 'avatar-4'
    },
    'member2@make-it-all.co.uk': {
        name: 'Emily Chen',
        role: 'member',
        avatarClass: 'avatar-2'
    },
    'member3@make-it-all.co.uk': {
        name: 'Michael Brown',
        role: 'member',
        avatarClass: 'avatar-1'
    }
};

// Initial hardcoded posts
const initialPosts = [
    // Software Issues
    {
        id: 1,
        topic: 'Software Issues',
        title: 'Jasmine software crashing on startup',
        author: 'Steve Adams',
        authorEmail: 'user@make-it-all.co.uk',
        date: '3 October 2025',
        content: "Every time I try to open the Jasmine client on my machine, it shows the splash screen and then immediately closes. \n\nI've tried: \n- Restarting my computer \n- Reinstalling the software \n\nNothing seems to work. Any ideas?",
        reactions: { up: 4, lightbulb: 1, comments: 1 },
        replies: [
            {
                id: 101,
                author: 'Jane Doe',
                authorRole: 'specialist',
                avatarClass: 'avatar-4',
                date: '3 October 2025',
                content: "Hi Steve, this is a known issue with the latest Windows update. The team is working on a patch. \n\nFor now, you can fix this by deleting the cache file at: \n`C:\\Users\\[YourName]\\AppData\\Local\\Jasmine\\cache.dat` \n\nLet me know if that works!"
            }
        ]
    },
    {
        id: 3,
        topic: 'Software Issues',
        title: 'Cannot access shared drive',
        author: 'Maria Garcia',
        authorEmail: 'maria@make-it-all.co.uk',
        date: '1 October 2025',
        content: 'I keep getting a "Permission Denied" error when trying to access the //PROJECTS/ shared drive. I had access yesterday. Did something change?',
        reactions: { up: 7, lightbulb: 0, comments: 0 },
        replies: []
    },
    // Printing
    {
        id: 2,
        topic: 'Printing',
        title: 'Printer not connecting to WiFi',
        author: 'Steve Adams',
        authorEmail: 'user@make-it-all.co.uk',
        date: '2 October 2025',
        content: 'I have tried to connect my printer (HP LaserJet M404) to WiFi numerous times, even tried restarting it and the router. My laptop can see the WiFi, but the printer cannot. \n\nAny suggestions to fix it?',
        reactions: { up: 2, lightbulb: 0, comments: 0 },
        replies: []
    },
    // Network
    {
        id: 4,
        topic: 'Network',
        title: 'Company VPN is extremely slow today',
        author: 'Ben Carter',
        authorEmail: 'manager@make-it-all.co.uk',
        date: '4 October 2025',
        content: 'Is anyone else experiencing very slow speeds on the company VPN? My file transfers are timing out and video calls are impossible.',
        reactions: { up: 12, lightbulb: 0, comments: 0 },
        replies: []
    },
    // Security
    {
        id: 5,
        topic: 'Security',
        title: 'Suspicious Phishing Email Received',
        author: 'Steve Adams',
        authorEmail: 'user@make-it-all.co.uk',
        date: '4 October 2025',
        content: "I received an email from 'IT Support' asking me to validate my password by clicking a link. This looks like a phishing attempt. Forwarding to the security team, but wanted to warn others.",
        reactions: { up: 9, lightbulb: 3, comments: 1 },
        replies: [
            {
                id: 102,
                author: 'Jane Doe',
                authorRole: 'specialist',
                avatarClass: 'avatar-4',
                date: '4 October 2025',
                content: "Thanks, Steve. This is correct. That is a phishing email. **DO NOT** click the link. Our team is working to block it now. Well spotted!"
            }
        ]
    },
    // Database
    {
        id: 6,
        topic: 'Database',
        title: 'Query timeout on customer_report table',
        author: 'Jane Doe',
        authorEmail: 'specialist@make-it-all.co.uk',
        date: '1 October 2025',
        content: 'Running a standard SELECT query on the `customer_report` view is timing out after 30 seconds. This report is critical for month-end. Investigating now.',
        reactions: { up: 1, lightbulb: 1, comments: 0 },
        replies: []
    },
    // Finance
    {
        id: 7,
        topic: 'Finance',
        title: 'Question about new expense reporting tool',
        author: 'Maria Garcia',
        authorEmail: 'maria@make-it-all.co.uk',
        date: '29 September 2025',
        content: "Where can I find the training guide for the new 'Expensify' tool? The old Concur portal is now read-only.",
        reactions: { up: 3, lightbulb: 0, comments: 0 },
        replies: []
    }
];

// Load posts from localStorage or use initial set
let simPosts = JSON.parse(localStorage.getItem('simPosts')) || initialPosts;
if (!localStorage.getItem('simPosts')) {
    localStorage.setItem('simPosts', JSON.stringify(simPosts));
}

// Projects data
const initialProjects = [
    {
        id: 'project15',
        name: 'Project 15',
        createdBy: 'manager@make-it-all.co.uk',
        createdDate: '2025-09-15',
        teamLeader: 'leader@make-it-all.co.uk'
    },
    {
        id: 'apollo',
        name: 'Project Apollo',
        createdBy: 'manager@make-it-all.co.uk',
        createdDate: '2025-08-20',
        teamLeader: 'member2@make-it-all.co.uk'
    }
];


let simProjects = JSON.parse(localStorage.getItem('simProjects')) || initialProjects;
if (!localStorage.getItem('simProjects')) {
    localStorage.setItem('simProjects', JSON.stringify(simProjects));
}

function saveProjects() {
    localStorage.setItem('simProjects', JSON.stringify(simProjects));
}

// *** ADDED: MOCK ARCHIVED PROJECTS DATA ***
const simArchivedProjects = [
    {
        name: 'Project Alpha',
        teamLeader: 'Alice Brown',
        avatarClass: 'avatar-brown',
        description: 'UI Design',
        createdDate: '20 Jun 2025',
        closedDate: '15 Oct 2025'
    },
    {
        name: 'Project Beta',
        teamLeader: 'Levi Jones',
        avatarClass: 'avatar-orange',
        description: 'Testing',
        createdDate: '5 March 2025',
        closedDate: '1 May 2025'
    },
    {
        name: 'Project 12',
        teamLeader: 'Bill Smith',
        avatarClass: 'avatar-green',
        description: 'Requirements',
        createdDate: '6 March 2025',
        closedDate: '29 April 2025'
    },
    {
        name: 'Project 13',
        teamLeader: 'Eva Smith',
        avatarClass: 'avatar-green',
        description: 'Stakeholder Engagement',
        createdDate: '27 November 2024',
        closedDate: '10 April 2025'
    }
];
// *** END ADDED DATA ***

// *** UPDATED TASKS DATA ***
// Added description, createdDate, and more realistic tasks
const initialTasks = [
    // --- PROJECT APOLLO (apollo) ---
    {
        id: 1,
        title: 'Write onboarding documentation',
        project: 'Project Apollo',
        projectId: 'apollo',
        assignedTo: ['user@make-it-all.co.uk'],
        priority: 'medium',
        status: 'inprogress',
        deadline: '2025-10-28',
        createdDate: '2025-10-20',
        description: 'Create the full onboarding doc for new hires. Cover tooling, contacts, and first-week goals.',
        createdBy: 'manager@make-it-all.co.uk',
        type: 'assigned'
    },
    {
        id: 2,
        title: 'Fix login bug (Apollo)',
        project: 'Project Apollo',
        projectId: 'apollo',
        assignedTo: ['specialist@make-it-all.co.uk'],
        priority: 'urgent',
        status: 'inprogress',
        deadline: '2025-10-26', // Not overdue
        createdDate: '2025-10-24',
        description: 'Users reporting being logged out every 5 minutes. Investigate session token expiry.',
        createdBy: 'manager@make-it-all.co.uk',
        type: 'assigned'
    },
    {
        id: 4,
        title: 'Code review for API integration',
        project: 'Project Apollo',
        projectId: 'apollo',
        assignedTo: ['leader@make-it-all.co.uk'], // Assigned to Team Leader
        priority: 'medium',
        status: 'review',
        deadline: '2025-10-25', // Overdue
        createdDate: '2025-10-23',
        description: 'Review Jane\'s PR #451 for the Stripe API connector.',
        createdBy: 'manager@make-it-all.co.uk',
        type: 'assigned'
    },
    {
        id: 7,
        title: 'Design dashboard mockups (v2)',
        project: 'Project Apollo',
        projectId: 'apollo',
        assignedTo: ['user@make-it-all.co.uk'],
        priority: 'high',
        status: 'review',
        deadline: '2025-10-26', // Not overdue
        createdDate: '2025-10-21',
        description: 'Create high-fidelity mockups for the new admin dashboard based on client feedback.',
        createdBy: 'manager@make-it-all.co.uk',
        type: 'assigned'
    },
    {
        id: 10,
        title: 'Finalize user testing report',
        project: 'Project Apollo',
        projectId: 'apollo',
        assignedTo: ['user@make-it-all.co.uk'],
        priority: 'low',
        status: 'completed',
        deadline: '2025-10-22',
        createdDate: '2025-10-18',
        description: 'Collate all feedback from the user testing session into a summary document.',
        createdBy: 'manager@make-it-all.co.uk',
        type: 'assigned'
    },
    // --- PROJECT 15 (project15) ---
    {
        id: 3,
        title: 'Prepare client presentation slides',
        project: 'Project 15',
        projectId: 'project15',
        assignedTo: ['user@make-it-all.co.uk', 'leader@make-it-all.co.uk'],
        priority: 'high',
        status: 'todo',
        deadline: '2025-10-27', // Not overdue
        createdDate: '2025-10-22',
        description: 'Build the deck for the Q4 review. Focus on metrics from slide 5 of the brief.',
        createdBy: 'manager@make-it-all.co.uk',
        type: 'assigned'
    },
    {
        id: 5,
        title: 'Update payment gateway tests',
        project: 'Project 15',
        projectId: 'project15',
        assignedTo: ['specialist@make-it-all.co.uk'],
        priority: 'medium',
        status: 'todo',
        deadline: '2025-10-29', // Not overdue
        createdDate: '2025-10-24',
        description: 'Add new test cases for failed payments and 3DS verification.',
        createdBy: 'manager@make-it-all.co.uk',
        type: 'assigned'
    },
    {
        id: 6,
        title: 'Schedule team retro',
        project: 'Project 15',
        projectId: 'project15',
        assignedTo: ['leader@make-it-all.co.uk'],
        priority: 'low',
        status: 'completed',
        deadline: '2025-10-24',
        createdDate: '2025-10-20',
        description: 'Book a 1-hour slot for the end-of-project retrospective.',
        createdBy: 'manager@make-it-all.co.uk',
        type: 'assigned'
    },
    {
        id: 8,
        title: 'Client demo script finalization',
        project: 'Project 15',
        projectId: 'project15',
        assignedTo: ['manager@make-it-all.co.uk'],
        priority: 'urgent',
        status: 'todo',
        deadline: '2025-10-26', // Not overdue
        createdDate: '2025-10-25',
        description: 'Final pass on the demo script. Check all talking points.',
        createdBy: 'manager@make-it-all.co.uk',
        type: 'assigned'
    },
    {
        id: 9,
        title: 'Deploy staging build',
        project: 'Project 15',
        projectId: 'project15',
        assignedTo: ['specialist@make-it-all.co.uk'],
        priority: 'high',
        status: 'review',
        deadline: '2025-10-24', // Overdue
        createdDate: '2025-10-24',
        description: 'Push latest `main` branch to the staging server for client review.',
        createdBy: 'manager@make-it-all.co.uk',
        type: 'assigned'
    },
    {
        id: 11,
        title: 'Fix ARIA labels',
        project: 'Project 15',
        projectId: 'project15',
        assignedTo: ['user@make-it-all.co.uk'],
        priority: 'low',
        status: 'todo',
        deadline: '2025-10-23', // Overdue
        createdDate: '2025-10-20',
        description: 'Run accessibility check and fix labels.',
        createdBy: 'manager@make-it-all.co.uk',
        type: 'assigned'
    },
    {
        id: 12,
        title: 'Update E2E tests',
        project: 'Project 15',
        projectId: 'project15',
        assignedTo: ['specialist@make-it-all.co.uk'],
        priority: 'medium',
        status: 'todo',
        deadline: '2025-10-22', // Overdue
        createdDate: '2025-10-20',
        description: 'E2E tests are failing on CI.',
        createdBy: 'manager@make-it-all.co.uk',
        type: 'assigned'
    },
    {
        id: 13,
        title: 'Draft blog post',
        project: 'Project 15',
        projectId: 'project15',
        assignedTo: ['user@make-it-all.co.uk'],
        priority: 'low',
        status: 'todo',
        deadline: '2025-10-21', // Overdue
        createdDate: '2025-10-19',
        description: 'Draft blog post for project launch.',
        createdBy: 'manager@make-it-all.co.uk',
        type: 'assigned'
    }
];

// Personal to-do items (created by users themselves)
const initialPersonalTodos = [
    // Steve Adams (user@make-it-all.co.uk)
    {
        id: 101,
        title: 'Review project 15 documentation',
        project: 'Project 15',
        projectId: 'project15',
        owner: 'user@make-it-all.co.uk',
        priority: 'medium',
        status: 'todo',
        deadline: '2025-10-26',
        type: 'personal'
    },
    {
        id: 102,
        title: 'Prepare weekly report',
        project: 'Project Apollo',
        projectId: 'apollo',
        owner: 'user@make-it-all.co.uk',
        priority: 'low',
        status: 'completed', // One completed task
        deadline: '2025-10-27',
        type: 'personal'
    },
    // Jane Doe (specialist@make-it-all.co.uk)
    {
        id: 103,
        title: 'Research new security patch',
        project: 'Security',
        projectId: null,
        owner: 'specialist@make-it-all.co.uk',
        priority: 'high',
        status: 'todo',
        deadline: '2025-10-28',
        type: 'personal'
    },
    // Ben Carter (manager@make-it-all.co.uk)
    {
        id: 104,
        title: 'Schedule 1-on-1s',
        project: 'Project 15',
        projectId: null,
        owner: 'manager@make-it-all.co.uk',
        priority: 'medium',
        status: 'todo',
        deadline: '2025-10-27',
        type: 'personal'
    }
];

// Load tasks from localStorage or use initial set
let simTasks = JSON.parse(localStorage.getItem('simTasks')) || initialTasks;
if (!localStorage.getItem('simTasks')) {
    localStorage.setItem('simTasks', JSON.stringify(simTasks));
}

let simPersonalTodos = JSON.parse(localStorage.getItem('simPersonalTodos')) || initialPersonalTodos;
if (!localStorage.getItem('simPersonalTodos')) {
    localStorage.setItem('simPersonalTodos', JSON.stringify(simPersonalTodos));
}

function saveTasks() {
    localStorage.setItem('simTasks', JSON.stringify(simTasks));
}

function savePersonalTodos() {
    localStorage.setItem('simPersonalTodos', JSON.stringify(simPersonalTodos));
}


// HELPER FUNCTIONS

/**
 * Gets the current simulated user from the URL query parameter.
 * This now correctly defaults and finds the user.
 */
function getCurrentUser() {
    const urlParams = new URLSearchParams(window.location.search);
    let userEmail = urlParams.get('user'); // Get email from URL

    //If not in URL, check sessionStorage (backup)
    if (!userEmail) {
        userEmail = sessionStorage.getItem('currentUserEmail');
        console.warn('User parameter missing from URL, using session backup:', userEmail);
    }

    //Find the user in our simulated DB
    if (userEmail && simUsers[userEmail]) {
        //Store in session as backup
        sessionStorage.setItem('currentUserEmail', userEmail);

        return {
            email: userEmail,
            ...simUsers[userEmail]
        };
    }

    //Fallback if absolutely no user info exists
    console.error('No valid user found! Defaulting to member account.');
    const fallbackEmail = 'user@make-it-all.co.uk';
    sessionStorage.setItem('currentUserEmail', fallbackEmail);

    return {
        email: fallbackEmail,
        ...simUsers[fallbackEmail]
    };
}

/**
 * NEW: Gets the current project ID from the URL.
 * Defaults to 'project15' if none is set.
 */
function getCurrentProjectId() {
    const urlParams = new URLSearchParams(window.location.search);
    const projectId = urlParams.get('project');
    return projectId || 'project15'; // Default to 'project15'
}

/**
 * Persists the current user's email AND project in all internal links.
 * This simulates a "logged in" session as you navigate.
 */
function persistUserQueryParam(currentUser) {
    const userQuery = `user=${currentUser.email}`;
    const urlParams = new URLSearchParams(window.location.search);
    const projectQuery = urlParams.get('project') ? `project=${urlParams.get('project')}` : 'project=project15';

    document.querySelectorAll('a').forEach(a => {
        // Check if it's an internal link
        if (a.href && a.hostname === window.location.hostname && !a.href.includes('#')) {
            // Check if it's a mailto link, if so, skip
            if (a.protocol === "mailto:") return;

            // Don't modify links that already have params
            if (a.href.includes('?')) return;

            // Rebuild href to include both user and project
            if (a.search) {
                if (!a.search.includes('user=')) a.search += `&${userQuery}`;
                if (a.pathname.includes('projects') || a.pathname.includes('progress') || a.pathname.includes('project-resources')) {
                    if (!a.search.includes('project=')) a.search += `&${projectQuery}`;
                }
            } else {
                a.href += `?${userQuery}`;
                if (a.pathname.includes('projects') || a.pathname.includes('progress') || a.pathname.includes('project-resources')) {
                    a.href += `&${projectQuery}`;
                }
            }
        }
    });
}

/**
 * NEW: Dynamically updates sidebar links and project header/nav
 */
function updateSidebarAndNav(currentUser, currentProjectId) {
    const project = simProjects.find(p => p.id === currentProjectId) || simProjects[0];
    const userQuery = `user=${currentUser.email}`;

    // 1. Update Sidebar
    const sidebarList = document.getElementById('project-sidebar-list');
    if (sidebarList) {
        sidebarList.innerHTML = simProjects.map(p => `
            <li class="${p.id === currentProjectId ? 'active' : ''}">
                <a href="projects.html?project=${p.id}&${userQuery}">${p.name}</a>
            </li>
        `).join('');
    }

    // 2. Update Header
    const breadcrumb = document.getElementById('project-name-breadcrumb');
    const header = document.getElementById('project-name-header');
    if (breadcrumb) breadcrumb.textContent = project.name;
    if (header) header.textContent = project.name;

    // 3. Update Nav Links (Tasks, Progress, etc.)
    const navLinks = document.getElementById('project-nav-links');
    if (navLinks) {
        // Check for the special "Leader on Apollo" case
        const isManagerView = (currentUser.role === 'manager' || currentUser.role === 'team_leader');
        const isLeaderOnApollo = (currentUser.email === 'leader@make-it-all.co.uk' && currentProjectId === 'apollo');

        let progressPage = 'progress.html'; // Default to member view
        if (isManagerView && !isLeaderOnApollo) {
            progressPage = 'manager-progress.html'; // Manager/Leader view
        }

        const tasksLink = `projects.html?project=${currentProjectId}&${userQuery}`;
        const progressLink = `${progressPage}?project=${currentProjectId}&${userQuery}`;
        const resourcesLink = `project-resources.html?project=${currentProjectId}&${userQuery}`; // <-- NEW LINK

        const path = window.location.pathname;
        const tasksActive = path.includes('projects.html') ? 'active' : '';
        const progressActive = path.includes('progress.html') || path.includes('manager-progress.html') ? 'active' : '';
        const resourcesActive = path.includes('project-resources.html') ? 'active' : ''; // <-- NEW CHECK

        navLinks.innerHTML = `
        <a href="${tasksLink}" class="${tasksActive}">Tasks</a>
        <a href="${progressLink}" class="${progressActive}">Progress</a>
        <a href="${resourcesLink}" class="${resourcesActive}">Resources</a>
    `;
    }

    // *** ADDED: Show/Hide "Close Project" button and add listener ***
    const closeProjectBtn = document.getElementById('close-project-btn');
    if (closeProjectBtn) {
        if (currentUser.role === 'manager') {
            closeProjectBtn.style.display = 'block';
            closeProjectBtn.addEventListener('click', () => {
                showSuccessNotification('This is a prototype feature. Project closing will be implemented later.');
            });
        } else {
            closeProjectBtn.style.display = 'none';
        }
    }
    // *** END ADDED CODE ***
}


/**
 * Generates the HTML for a single post card.
 * @param {object} post - The post object.
 * @param {string} currentUserEmail - The email of the current user.
 */
function createPostCardHTML(post, currentUserEmail) {
    const postLink = `knowledge-base-post.html?id=${post.id}&user=${currentUserEmail}`;
    const topicClass = post.topic.toLowerCase().split(' ')[0]; // 'software issues' -> 'software'

    // Determine avatar class
    let avatarClass = 'avatar-3'; // Default avatar
    if (post.authorEmail === 'user@make-it-all.co.uk') avatarClass = 'avatar-1';
    if (post.authorEmail === 'specialist@make-it-all.co.uk') avatarClass = 'avatar-4';
    if (post.authorEmail === 'manager@make-it-all.co.uk') avatarClass = 'avatar-2';

    return `
        <div class="post-card">
            <div class="post-card-header">
                <div class="post-card-avatar ${avatarClass}"></div>
                <div>
                    <span class="post-card-author">${post.author}</span>
                    <span class="post-card-date">${post.date}</span>
                </div>
                <span class="post-card-tag ${topicClass}">${post.topic}</span>
            </div>
            <a href="${postLink}" class="post-card-body">
                <h3>${post.title}</h3>
                <p>${post.content}</p>
            </a>
            <div class="post-card-footer">
                <span><i data-feather="thumbs-up"></i> ${post.reactions.up}</span>
                <span><i data-feather="message-circle"></i> ${post.reactions.comments}</span>
            </div>
        </div>
    `;
}

/**
 * Saves the current state of simPosts to localStorage.
 */
function savePosts() {
    localStorage.setItem('simPosts', JSON.stringify(simPosts));
}

/* ===========================
   TOPICS STORE (persist custom topics)
   =========================== */
const MAIN_TOPICS = ["Printing", "Software Issues", "Network", "Security", "Database", "Finance"];
let customTopics = JSON.parse(localStorage.getItem('customTopics')) || [];

function getAllTopics() {
    return [...MAIN_TOPICS, ...customTopics];
}
function saveCustomTopics() {
    localStorage.setItem('customTopics', JSON.stringify(customTopics));
}


/* ===========================
   PAGE-SPECIFIC LOGIC
   =========================== */

/**
 * Renders a list of posts into the container.
 * @param {Array} posts - An array of post objects to render.
 * @param {string} currentUserEmail - The email of the current user.
 */
function renderPostList(posts, currentUserEmail) {
    const postListContainer = document.getElementById('post-list-container');
    if (posts.length > 0) {
        const postsHtml = posts
            .map(post => createPostCardHTML(post, currentUserEmail))
            .join('');
        postListContainer.innerHTML = postsHtml;
    } else {
        postListContainer.innerHTML = '<p>No posts found for this topic.</p>';
    }
    // Re-activate icons after rendering
    feather.replace();
}

/**
 * Switches the main KB page to show a specific topic.
 * @param {string} topicName - The name of the topic, e.g., "Software Issues".
 * @param {object} currentUser - The current user object.
 */
function showTopicView(topicName, currentUser) {
    // 1. Hide the topic grid and its parent section
    document.getElementById('kb-topics-section').style.display = 'none';

    // 2. Hide the main page sidebar (Announcements)
    document.getElementById('announcements-widget').style.display = 'none';

    // 3. Show the topic-specific sidebar
    document.getElementById('topic-sidebar-widgets').style.display = 'block';

    // 4. Update the header to show breadcrumbs and new title
    const titleContainer = document.getElementById('kb-title-container');
    titleContainer.innerHTML = `
        <p class="breadcrumbs">
            <a href="knowledge-base.html?user=${currentUser.email}">Knowledge Base</a> > ${topicName}
        </p>
        <h1>${topicName}</h1>
    `;

    // Pass the topic to the create page via query param (FIXED: reference the element directly)
    const createHref = `knowledge-base-create.html?user=${currentUser.email}&topic=${encodeURIComponent(topicName)}`;
    const createBtnEl = document.getElementById('create-post-btn-topic');
    if (createBtnEl) createBtnEl.href = createHref;

    // Also update the "Start new discussion" link in the sidebar
    const startDiscussionLink = document.getElementById('start-discussion-link');
    if (startDiscussionLink) startDiscussionLink.href = createHref;

    // 6. Update the "Posts" list title and hide tabs
    document.getElementById('posts-list-title').textContent = 'All Posts';
    document.getElementById('post-tabs-container').style.display = 'none';

    // 7. Filter and render the posts for this topic
    const topicPosts = simPosts.filter(post => post.topic === topicName);
    renderPostList(topicPosts, currentUser.email);
}

/**
 * Renders the topic cards into the grid for the main page ONLY (main topics + Add New Topic).
 * @param {boolean} showAll If true, render all topics (unused for main page now). If false, render main topics only.
 * @param {object} currentUser Current user (for click-through)
 */
function renderTopicGrid(showAll, currentUser) {
    const grid = document.getElementById('topic-grid');
    if (!grid) return;

    // For main page we always show only MAIN_TOPICS
    const topics = MAIN_TOPICS;

    // Build cards
    const topicCardsHtml = topics.map(t => `
        <a href="#" class="topic-card" data-topic="${t}">
            <i data-feather="${iconForTopic(t)}"></i>
            <span>${t}</span>
        </a>
    `).join('');

    // “Add New Topic” card
    const addCardHtml = `
        <a href="knowledge-base-create-topic.html?user=${currentUser.email}" class="topic-card add-topic-card" id="add-topic-card">
            <i data-feather="plus"></i>
            <span>Add New Topic</span>
        </a>
    `;

    grid.innerHTML = topicCardsHtml + addCardHtml;

    // Hook up topic clicks
    grid.querySelectorAll('.topic-card:not(.add-topic-card)').forEach(card => {
        card.addEventListener('click', (e) => {
            e.preventDefault();
            const topicName = card.dataset.topic;
            showTopicView(topicName, currentUser);
        });
    });

    // Note: Add New Topic now links to form page instead of prompt

    feather.replace();
}

/** Pick an icon per topic (fallback: tag) */
function iconForTopic(topic) {
    const map = {
        "Printing": "printer",
        "Software Issues": "alert-triangle",
        "Network": "wifi",
        "Security": "shield",
        "Database": "database",
        "Finance": "shopping-cart"
    };
    return map[topic] || "tag";
}

/**
 * Runs on the Knowledge Base Index page (knowledge-base.html)
 */
function loadKbIndex(currentUser) {
    // Make sure the Create Post button is visible
    const createBtn = document.getElementById('create-post-btn-topic');
    if (createBtn) createBtn.style.display = 'inline-flex';

    // Load and render popular posts
    const popularPosts = [...simPosts].sort((a, b) => b.reactions.up - a.reactions.up);
    renderPostList(popularPosts, currentUser.email);

    // Render the main topics grid (main topics + Add New Topic)
    document.body.dataset.topicsView = 'main';
    renderTopicGrid(false, currentUser);

    // Update "View more topics" link to All Topics page
    const viewMoreLink = document.getElementById('view-more-topics');
    if (viewMoreLink) {
        viewMoreLink.setAttribute('href', 'all-topics.html');
    }
}

/**
 * Runs on the Create Post page (knowledge-base-create.html)
 */
function setupCreateForm(currentUser) {
    const form = document.getElementById('create-post-form');
    const topicSelect = document.getElementById('post-topic');

    if (!form || !topicSelect) return;

    // Populate the topic dropdown with all topics (main + custom)
    const allTopics = getAllTopics();
    topicSelect.innerHTML = '<option value="">Select a topic...</option>';
    allTopics.forEach(topic => {
        const option = document.createElement('option');
        option.value = topic;
        option.textContent = topic;
        topicSelect.appendChild(option);
    });

    // Check if a topic was passed via URL parameter (from topic view)
    const urlParams = new URLSearchParams(window.location.search);
    const preselectedTopic = urlParams.get('topic');
    if (preselectedTopic) {
        topicSelect.value = preselectedTopic;
    }

    // Handle form submission
    form.addEventListener('submit', (e) => {
        e.preventDefault();

        const title = document.getElementById('post-title').value.trim();
        const topic = document.getElementById('post-topic').value;
        const details = document.getElementById('post-details').value.trim();

        if (!title || !topic || !details) {
            alert('Please fill out all required fields.');
            return;
        }

        // Create new post object
        const newPost = {
            id: new Date().getTime(),
            topic: topic,
            title: title,
            author: currentUser.name,
            authorEmail: currentUser.email,
            date: new Date().toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' }),
            content: details,
            reactions: { up: 0, lightbulb: 0, comments: 0 },
            replies: []
        };

        // Add to posts array and save
        simPosts.push(newPost);
        savePosts();

        // Store success message
        sessionStorage.setItem('postCreated', `Post "${title}" created successfully!`);

        // Redirect back to knowledge base
        window.location.href = `knowledge-base.html?user=${currentUser.email}`;
    });
}

/**
 * Runs on the single Knowledge Base Post page (knowledge-base-post.html)
 */
function loadKbPost(currentUser) {
    const urlParams = new URLSearchParams(window.location.search);
    const postId = parseInt(urlParams.get('id'));
    const post = simPosts.find(p => p.id === postId);

    if (!post) {
        document.getElementById('post-title-placeholder').textContent = 'Post not found';
        document.getElementById('post-full-content').innerHTML = '<p>Could not find a post with that ID. <a href="knowledge-base/knowledge-base.html">Go back to Knowledge Base</a></p>';
        return;
    }

    // --- Determine avatar class for post author
    let avatarClass = 'avatar-3'; // Default
    if (post.authorEmail === 'user@make-it-all.co.uk') avatarClass = 'avatar-1';
    if (post.authorEmail === 'specialist@make-it-all.co.uk') avatarClass = 'avatar-4';
    if (post.authorEmail === 'manager@make-it-all.co.uk') avatarClass = 'avatar-2';

    // --- Fill in post details ---
    document.getElementById('post-title-placeholder').textContent = post.title;
    document.getElementById('post-breadcrumbs').innerHTML = `
        <a href="knowledge-base.html?user=${currentUser.email}">Knowledge Base</a> >
        <a href="knowledge-base.html?user=${currentUser.email}" onclick="sessionStorage.setItem('returnToTopic', '${post.topic}');">${post.topic}</a> >
        Post
    `;
    document.title = `Make-It-All - ${post.title}`; // Update browser tab title

    const postContentEl = document.getElementById('post-full-content');
    postContentEl.innerHTML = `
        <div class="post-card">
            <div class="post-card-header">
                <div class="post-card-avatar ${avatarClass}"></div>
                <div>
                    <span class="post-card-author">${post.author}</span>
                    <span class="post-card-date">${post.date}</span>
                </div>
            </div>
            <div class="post-card-body">
                <p>${post.content.replace(/\n/g, '<br>')}</p>
            </div>
            <div class="post-card-footer">
                <span><i data-feather="thumbs-up"></i> ${post.reactions.up}</span>
                <span><i data-feather="message-circle"></i> ${post.reactions.comments}</span>
            </div>
        </div>
    `;

    // --- Fill in replies ---
    const repliesListEl = document.getElementById('post-replies-list');
    if (post.replies.length > 0) {
        const repliesHtml = post.replies.map(reply => `
            <div class="reply-card">
                <div class="reply-avatar ${reply.avatarClass}"></div>
                <div class="reply-content">
                    <div class="reply-header">
                        <span class="reply-author">${reply.author} ${reply.authorRole === 'specialist' ? '(Specialist)' : ''}</span>
                        <span class="reply-date">${reply.date}</span>
                    </div>
                    <div class="reply-body">
                        <p>${reply.content.replace(/\n/g, '<br>')}</p>
                    </div>
                </div>
            </div>
        `).join('');
        repliesListEl.innerHTML = repliesHtml;
    } else {
        repliesListEl.innerHTML = '<p>No replies yet.</p>';
    }

    // --- Handle Role-Based Permissions (Replying) ---
    //Define technical vs non-technical topics
    const technicalTopics = ['Printing', 'Software Issues', 'Network', 'Security', 'Database'];
    const isTechnicalTopic = technicalTopics.includes(post.topic);

    //Determine if current user can reply
    let canReply = false;

    if (isTechnicalTopic) {
        //Technical topics: Only specialists can reply
        canReply = (currentUser.role === 'specialist');
    } else {
        //Non-technical topics: Anyone can reply
        canReply = true;
    }

    if (canReply) {
        const replyForm = document.getElementById('reply-form');
        replyForm.style.display = 'block';

        replyForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const replyContent = document.getElementById('reply-content').value;

            if (!replyContent) return;

            //Create the new reply object
            const newReply = {
                id: new Date().getTime(), //Unique ID
                author: currentUser.name,
                authorRole: currentUser.role,
                avatarClass: currentUser.avatarClass,
                date: new Date().toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' }),
                content: replyContent
            };

            //Add reply to the post and save
            post.replies.push(newReply);
            post.reactions.comments = post.replies.length; //Update comment count
            savePosts();

            //Reload the page to show the new reply
            window.location.reload();
        });
    } else {
        //Show a message explaining why they can't reply
        const replyForm = document.getElementById('reply-form');
        replyForm.style.display = 'block';
        replyForm.innerHTML = `
            <div style="padding: 20px; background: #FFF3CD; border: 1px solid #FFE69C; border-radius: 8px; text-align: center;">
                <p style="margin: 0; color: #856404; font-weight: 500;">
                    <i data-feather="alert-circle" style="width: 18px; height: 18px; vertical-align: middle;"></i>
                    Only Technical Specialists can reply to technical posts.
                </p>
            </div>
        `;
        feather.replace(); //Re-render the icon
    }
}

/**
 * Runs on the Create Project page (create-project.html)
 */
function setupCreateProjectPage(currentUser) {
    const form = document.getElementById('create-project-form');
    if (!form) return;

    // --- Get and populate new form fields ---
    const leaderSelect = document.getElementById('project-leader');

    // Populate Leader Dropdown (Anyone can be a leader)
    if (leaderSelect) {
        leaderSelect.innerHTML = '<option value="">Select a leader...</option>';
        for (const email in simUsers) {
            const user = simUsers[email];

            // FIX: The role filter is removed. Every user is now available.
            leaderSelect.innerHTML += `<option value="${email}">${user.name} (${user.role})</option>`;
        }
    }

    // NOTE: Team Member Checklist population logic remains removed.

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const projectName = document.getElementById('project-name').value;
        const projectDesc = document.getElementById('project-description').value;

        // --- Get values from new fields ---
        const teamLeaderEmail = document.getElementById('project-leader').value;

        // Team Member collection logic remains removed, saving an empty array.
        const teamMemberEmails = [];

        // Updated validation
        if (!projectName || !teamLeaderEmail) {
            alert('Please enter a project name and select a team leader.');
            return;
        }

        const newProject = {
            id: projectName.toLowerCase().replace(/\s+/g, '-') + '-' + new Date().getTime(),
            name: projectName,
            description: projectDesc,
            createdBy: currentUser.email,
            createdDate: new Date().toISOString().split('T')[0],
            // --- Save new data to the project object ---
            teamLeader: teamLeaderEmail,
            teamMembers: teamMemberEmails // Saved as empty array
            // --- END NEW CODE ---
        };

        simProjects.push(newProject);
        saveProjects();

        sessionStorage.setItem('projectCreated', `Project "${projectName}" created successfully!`);
        // Redirect to the new project's page
        window.location.href = `../project/projects.html?project=${newProject.id}&user=${currentUser.email}`;
    });
}


/**
 * Runs on the Settings page (settings.html)
 */
function loadSettingsPage(currentUser) {
    // 1. Populate user data
    document.getElementById('profile-name').value = currentUser.name;
    document.getElementById('profile-email').value = currentUser.email;

    // Capitalize the first letter of the role
    const role = currentUser.role.charAt(0).toUpperCase() + currentUser.role.slice(1);
    document.getElementById('profile-role').value = role;

    // 2. Add form submit listeners (prototype alerts)
    document.getElementById('profile-form').addEventListener('submit', (e) => {
        e.preventDefault();
        // In a real app, you'd save this new name
        const newName = document.getElementById('profile-name').value;
        alert(`Profile updated! (Name changed to ${newName})`);
    });

    document.getElementById('password-form').addEventListener('submit', (e) => {
        e.preventDefault();
        alert('Password updated! (This is a demo)');
    });

    document.getElementById('notifications-form').addEventListener('submit', (e) => {
        e.preventDefault();
        alert('Notification preferences saved!');
    });

    // 3. Add Sign Out logic
    document.getElementById('sign-out-btn').addEventListener('click', (e) => {
        e.preventDefault();

        // Clear the simulated session
        // This clears the posts you created, etc.
        localStorage.clear();
        sessionStorage.clear();

        alert('Signing out...');

        // Redirect to the login page (assuming it's index.html)
        window.location.href = '../index.html';
    });
}

/* ===========================
   ALL TOPICS PAGE (all-topics.html)
   =========================== */
/**
 * Runs on All Topics page (all-topics.html)
 * Shows a plain list (no buttons) of all topics: main + custom.
 */
function loadAllTopicsPage(currentUser) {
    // Title + breadcrumbs
    const titleContainer = document.getElementById('kb-title-container');
    if (titleContainer) {
        titleContainer.innerHTML = `
       <p class="breadcrumbs">
         <a href="knowledge-base.html?user=${currentUser.email}">Knowledge Base</a> > All Topics
       </p>
       <h1>All Topics</h1>
     `;
    }

    // Build clickable rows
    const listEl = document.getElementById('all-topics-list');
    if (listEl) {
        const topics = getAllTopics();
        if (topics.length === 0) {
            listEl.innerHTML = '<p>No topics yet.</p>';
        } else {
            listEl.innerHTML = topics
                .map(
                    (t) => `
           <div class="topic-row" data-topic="${t}">
             <span class="topic-name">${t}</span>
             <i data-feather="arrow-right"></i>
           </div>
         `
                )
                .join('');
        }

        // Make each row clickable
        listEl.querySelectorAll('.topic-row').forEach((row) => {
            row.addEventListener('click', () => {
                const topicName = row.dataset.topic;
                sessionStorage.setItem('returnToTopic', topicName);
                window.location.href = `knowledge-base.html?user=${currentUser.email}`;
            });
        });
    }

    feather.replace();
}

/**
 * Runs on the Home page (home/home.html)
 */
function loadHomePage(currentUser) {
    // Update page label based on role
    const pageLabel = document.getElementById('page-label-text');
    if (pageLabel) {
        const roleText = currentUser.role === 'manager' ? 'Manager' :
            currentUser.role === 'team_leader' ? 'Team Leader' : 'User';
        pageLabel.textContent = `Homepage (${roleText})`;
    }

    // Show appropriate action buttons based on role
    if (currentUser.role === 'manager') {
        document.getElementById('manager-actions').style.display = 'block';
    } else if (currentUser.role === 'team_leader') {
        // Team leaders don't have manager actions on home page in this design
        // document.getElementById('leader-actions').style.display = 'block';
    }

    // Render Total Tasks Chart
    renderTotalTasksChart(currentUser);

    // Render To-Do List
    renderToDoList(currentUser);

    // Render Trending Posts
    renderTrendingPosts(currentUser);

    // Render Notifications
    renderNotifications();

    feather.replace();
}

/**
 * Renders the total tasks donut chart (assigned tasks only) or bar chart for managers
 */
function renderTotalTasksChart(currentUser) {
    const ctx = document.getElementById('totalTasksChart');
    if (!ctx) return;

    // Destroy existing chart if it exists
    const existingChart = Chart.getChart(ctx);
    if (existingChart) {
        existingChart.destroy();
    }

    // Get donut-specific elements
    const donutCenter = document.querySelector('.donut-center');
    const chartLegend = document.querySelector('.chart-legend');

    // For managers, show bar chart with projects
    if (currentUser.role === 'manager') {
        // Hide donut-specific elements
        if (donutCenter) donutCenter.style.display = 'none';
        if (chartLegend) chartLegend.style.display = 'none';

        // Get all tasks grouped by project
        const projectData = {};
        simProjects.forEach(project => {
            const projectTasks = simTasks.filter(task =>
                task.projectId === project.id && task.type === 'assigned'
            );

            projectData[project.name] = {
                todo: projectTasks.filter(t => t.status === 'todo').length,
                inprogress: projectTasks.filter(t => t.status === 'inprogress').length,
                review: projectTasks.filter(t => t.status === 'review').length,
                completed: projectTasks.filter(t => t.status === 'completed').length,
                total: projectTasks.length
            };
        });

        const projectNames = Object.keys(projectData);
        const totalTasks = projectNames.reduce((sum, name) => sum + projectData[name].total, 0);
        document.getElementById('totalTasksCount').textContent = totalTasks;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: projectNames,
                datasets: [
                    {
                        label: 'To Do',
                        data: projectNames.map(name => projectData[name].todo),
                        backgroundColor: '#D93025'
                    },
                    {
                        label: 'In Progress',
                        data: projectNames.map(name => projectData[name].inprogress),
                        backgroundColor: '#E6A100'
                    },
                    {
                        label: 'In Review',
                        data: projectNames.map(name => projectData[name].review),
                        backgroundColor: '#34A853'
                    },
                    {
                        label: 'Completed',
                        data: projectNames.map(name => projectData[name].completed),
                        backgroundColor: '#4285F4'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        stacked: true,
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        ticks: {
                            font: {
                                size: 12
                            },
                            stepSize: 20
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            font: {
                                size: 12,
                                weight: 400
                            },
                            padding: 10,
                            boxWidth: 60,  // Make legend boxes larger
                            boxHeight: 30
                        }
                    }
                },

            }
        });
    } else {
        // For non-managers, show donut chart
        // Show donut-specific elements
        if (donutCenter) donutCenter.style.display = 'block';
        if (chartLegend) chartLegend.style.display = 'flex';

        const userTasks = simTasks.filter(task =>
            task.assignedTo.includes(currentUser.email) && task.type === 'assigned'
        );

        const todoCount = userTasks.filter(t => t.status === 'todo').length;
        const inProgressCount = userTasks.filter(t => t.status === 'inprogress').length;
        const reviewCount = userTasks.filter(t => t.status === 'review').length;
        const completedCount = userTasks.filter(t => t.status === 'completed').length;

        const totalCount = userTasks.length;
        document.getElementById('totalTasksCount').textContent = totalCount;

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['To Do', 'In Progress', 'In Review', 'Completed'],
                datasets: [{
                    data: [todoCount, inProgressCount, reviewCount, completedCount],
                    backgroundColor: ['#D93025', '#E6A100', '#34A853', '#4285F4'],
                    borderWidth: 0
                }]
            },
            options: {
                cutout: '70%',
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
}

/**
 * Renders the to-do list with personal todos
 */
function renderToDoList(currentUser) {
    const projectSelect = document.getElementById('project-select');
    const todoItemsList = document.getElementById('todo-items-list');
    const newTaskBtn = document.getElementById('new-task-btn');

    // Populate project filter from personal todos
    const projects = [...new Set(simPersonalTodos.filter(t => t.owner === currentUser.email).map(t => t.project))];
    projectSelect.innerHTML = '<option value="">All Projects</option>'; // Add 'All' option
    projects.forEach(project => {
        if (project) { // Only add if project is not null
            const option = document.createElement('option');
            option.value = project;
            option.textContent = project;
            projectSelect.appendChild(option);
        }
    });

    // Get user's personal todos
    let personalTodos = simPersonalTodos.filter(todo =>
        todo.owner === currentUser.email
    );

    // Show "+ New Task" button for all users (to create personal todos)
    newTaskBtn.style.display = 'flex';
    newTaskBtn.onclick = () => {
        // This link is now correct
        window.location.href = `create-todo.html?user=${currentUser.email}`;
    };

    // Sort by priority (default)
    const priorityOrder = { urgent: 0, high: 1, medium: 2, low: 3 };
    personalTodos.sort((a, b) => priorityOrder[a.priority] - priorityOrder[b.priority]);

    // Render tasks
    renderTodoItems(personalTodos, currentUser);

    // Add event listeners for sorting and filtering
    const applyFilters = () => {
        const selectedProject = projectSelect.value;
        const sortBy = document.querySelector('.sort-btn.active').dataset.sort;

        let filteredTasks = simPersonalTodos.filter(todo => todo.owner === currentUser.email);

        if (selectedProject) {
            filteredTasks = filteredTasks.filter(t => t.project === selectedProject);
        }

        if (sortBy === 'priority') {
            filteredTasks.sort((a, b) => priorityOrder[a.priority] - priorityOrder[b.priority]);
        } else if (sortBy === 'deadline') {
            // Handle null/empty deadlines
            filteredTasks.sort((a, b) => {
                const dateA = a.deadline ? new Date(a.deadline) : new Date('2999-12-31');
                const dateB = b.deadline ? new Date(b.deadline) : new Date('2999-12-31');
                return dateA - dateB;
            });
        }

        renderTodoItems(filteredTasks, currentUser);
    };

    projectSelect.addEventListener('change', applyFilters);

    document.querySelectorAll('.sort-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.sort-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            applyFilters();
        });
    });
}

function renderTodoItems(tasks, currentUser) {
    const todoItemsList = document.getElementById('todo-items-list');

    if (tasks.length === 0) {
        todoItemsList.innerHTML = '<p style="text-align: center; color: #8C8C8C; padding: 20px;">No personal to-dos found.</p>';
        return;
    }

    todoItemsList.innerHTML = tasks.map(task => {
        const deadline = task.deadline ? new Date(task.deadline) : null;
        const formattedDate = deadline ? deadline.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) : 'No date';
        const isPersonal = task.type === 'personal'; // This will always be true now

        return `
            <div class="todo-item ${task.status === 'completed' ? 'completed' : ''}" data-task-id="${task.id}" data-task-type="${task.type || 'assigned'}">
                <div class="todo-checkbox ${task.status === 'completed' ? 'checked' : ''}">
                    ${task.status === 'completed' ? '<i data-feather="check"></i>' : ''}
                </div>
                <div class="todo-content">
                    <p class="todo-title">${task.title} ${isPersonal ? '<span class="personal-badge">Personal</span>' : ''}</p>
                    <div class="todo-meta">
                        <span class="todo-priority">
                            <span class="priority-dot ${task.priority}"></span>
                            ${task.priority.charAt(0).toUpperCase() + task.priority.slice(1)}
                        </span>
                        <span class="todo-date">${formattedDate}</span>
                        <span class="todo-project">${task.project || 'General'}</span>
                    </div>
                </div>
            </div>
        `;
    }).join('');

    // Add checkbox click handlers
    document.querySelectorAll('.todo-checkbox').forEach(checkbox => {
        checkbox.addEventListener('click', (e) => {
            e.stopPropagation();
            const taskItem = checkbox.closest('.todo-item');
            const taskId = parseInt(taskItem.dataset.taskId);
            const taskType = taskItem.dataset.taskType;

            if (taskType === 'personal') {
                const task = simPersonalTodos.find(t => t.id === taskId);
                if (task) {
                    // Toggle status
                    task.status = task.status === 'completed' ? 'todo' : 'completed';
                    savePersonalTodos(); // Save the change

                    // --- UI toggle (no reload) ---
                    taskItem.classList.toggle('completed');
                    checkbox.classList.toggle('checked');
                    if (task.status === 'completed') {
                        checkbox.innerHTML = '<i data-feather="check"></i>';
                        feather.replace(); // Redraw the new icon
                    } else {
                        checkbox.innerHTML = '';
                    }
                }
            }
            // Removed 'else' block for assigned tasks, as they are no longer in this list
        });
    });

    feather.replace();
}

/**
 * Renders trending posts
 */
function renderTrendingPosts(currentUser) {
    const trendingPostsList = document.getElementById('trending-posts-list');
    const topPosts = [...simPosts].sort((a, b) => b.reactions.up - a.reactions.up).slice(0, 3);

    trendingPostsList.innerHTML = topPosts.map(post => {
        let avatarClass = 'avatar-3';
        if (post.authorEmail === 'user@make-it-all.co.uk') avatarClass = 'avatar-1';
        if (post.authorEmail === 'specialist@make-it-all.co.uk') avatarClass = 'avatar-4';
        if (post.authorEmail === 'manager@make-it-all.co.uk') avatarClass = 'avatar-2';

        const topicClass = post.topic.toLowerCase().split(' ')[0];

        return `
            <div class="trending-post">
                <div class="post-header">
                    <div class="post-avatar ${avatarClass}">${post.author.split(' ').map(n => n[0]).join('')}</div>
                    <div class="post-author-info">
                        <p class="post-author-name">${post.author}</p>
                        <span class="post-date">${post.date.split(' ').slice(0, 2).join(' ')}</span>
                    </div>
                    <span class="post-tag ${topicClass}">${post.topic}</span>
                </div>
                <h3 class="post-title">${post.title}</h3>
                <p class="post-excerpt">${post.content.substring(0, 100)}...</p>
                <div class="post-stats">
                    <span class="post-stat"><i data-feather="thumbs-up"></i> ${post.reactions.up}</span>
                    <span class="post-stat"><i data-feather="message-circle"></i> ${post.reactions.comments}</span>
                </div>
            </div>
        `;
    }).join('');

    feather.replace();
}

/**
 * Renders notifications
 */
function renderNotifications() {
    const notificationsList = document.getElementById('notifications-list');

    notificationsList.innerHTML = simNotifications.map(notif => {
        return `
            <div class="notification-item ${!notif.read ? 'unread' : ''}">
                ${!notif.read ? '<span class="notification-badge"></span>' : ''}
                <div class="notification-icon ${notif.type === 'task_completed' ? 'completed' : 'post'}">
                    <i data-feather="${notif.type === 'task_completed' ? 'check-circle' : 'file-text'}"></i>
                </div>
                <div class="notification-content">
                    <p class="notification-title">${notif.title}</p>
                    <p class="notification-text">${notif.text}</p>
                    <span class="notification-time">${notif.time}</span>
                </div>
            </div>
        `;
    }).join('');

    // Mark all as read button
    document.querySelector('.mark-read-btn').addEventListener('click', () => {
        simNotifications.forEach(n => n.read = true);
        localStorage.setItem('simNotifications', JSON.stringify(simNotifications));
        renderNotifications();
    });

    feather.replace();
}

/**
 * Runs on the Progress page (progress.html) - shows only assigned tasks
 */
function loadProgressPage(currentUser) {
    const currentProjectId = getCurrentProjectId();

    // --- NEW: Role-based Redirect ---
    // Check if user is a manager/leader
    const isManagerView = (currentUser.role === 'manager' || currentUser.role === 'team_leader');
    // Check for the special "Leader on Apollo" exception
    const isLeaderOnApollo = (currentUser.email === 'leader@make-it-all.co.uk' && currentProjectId === 'apollo');

    if (isManagerView && !isLeaderOnApollo) {
        // This is a manager/leader, redirect them to the manager progress page
        window.location.href = `project/manager-progress.html?project=${currentProjectId}&user=${currentUser.email}`;
        return; // Stop loading this page
    }
    // --- End Redirect ---

    // If we are here, it's a team member (or the special exception)
    updateSidebarAndNav(currentUser, currentProjectId);

    // Only use assigned tasks for this user AND this project
    const userTasks = simTasks.filter(task =>
        task.assignedTo &&
        task.assignedTo.includes(currentUser.email) &&
        task.type === 'assigned' &&
        task.projectId === currentProjectId // <-- NEW FILTER
    );

    // Calculate task progress
    const completedTasks = userTasks.filter(t => t.status === 'completed').length;
    const totalTasks = userTasks.length;
    const progressPercent = totalTasks > 0 ? Math.round((completedTasks / totalTasks) * 100) : 0;

    document.getElementById('task-progress-fill').style.width = progressPercent + '%';
    document.getElementById('progress-text').textContent =
        `You have completed ${progressPercent}% of your assigned tasks for this project.`;

    // Render upcoming deadlines (from this project's tasks)
    renderUpcomingDeadlines(userTasks);

    // Render workload (pass project ID)
    renderWorkload(currentUser, currentProjectId);

    // Render urgent tasks (from this project's tasks)
    renderUrgentTasks(userTasks, currentUser);

    // Render task distribution chart (from this project's tasks)
    renderTaskDistributionChart(userTasks);

    feather.replace();
}

function renderUpcomingDeadlines(userTasks) {
    const deadlinesList = document.getElementById('deadlines-list');
    const today = new Date("2025-10-25T12:00:00"); // Hardcode date for demo consistency
    today.setHours(0, 0, 0, 0);

    const upcomingTasks = userTasks
        .filter(t => t.status !== 'completed')
        .sort((a, b) => new Date(a.deadline) - new Date(b.deadline))
        .slice(0, 3);

    if (upcomingTasks.length === 0) {
        deadlinesList.innerHTML = '<p class="no-deadlines">No upcoming deadlines. You\'re all caught up!</p>';
        return;
    }

    deadlinesList.innerHTML = upcomingTasks.map(task => {
        const deadline = new Date(task.deadline);
        const formattedDate = deadline.toLocaleDateString('en-US', { weekday: 'short', day: 'numeric', month: 'short' });

        let status = 'on-track';
        let statusText = 'On track';

        if (deadline < today) {
            status = 'overdue';
            statusText = 'Overdue';
        } else {
            const daysUntil = Math.ceil((deadline - today) / (1000 * 60 * 60 * 24));
            if (daysUntil <= 2) {
                status = 'at-risk';
                statusText = 'At risk';
            }
        }

        return `
            <div class="deadline-item">
                <p class="deadline-title">${task.title}</p>
                <div class="deadline-info">
                    <span class="deadline-date">${formattedDate}</span>
                    <span class="deadline-status ${status}">${statusText}</span>
                </div>
            </div>
        `;
    }).join('');
}

function renderWorkload(currentUser, currentProjectId) {
    // Get this user's tasks for this project
    const userTasks = simTasks.filter(task =>
        task.assignedTo &&
        task.assignedTo.includes(currentUser.email) &&
        task.status !== 'completed' &&
        task.projectId === currentProjectId
    );
    const userTaskCount = userTasks.length;

    // Calculate team average for this project
    const allTasksInProject = simTasks.filter(t => t.status !== 'completed' && t.projectId === currentProjectId);
    const uniqueUsersInProject = [...new Set(allTasksInProject.flatMap(t => t.assignedTo))];
    const teamAverage = uniqueUsersInProject.length > 0 ? Math.round(allTasksInProject.length / uniqueUsersInProject.length) : 0;

    const maxTasks = Math.max(userTaskCount, teamAverage, 5); // Set a minimum max of 5 for display
    const userPercent = (userTaskCount / maxTasks) * 100;
    const teamPercent = (teamAverage / maxTasks) * 100;

    document.getElementById('user-workload').style.width = userPercent + '%';
    document.getElementById('team-workload').style.width = teamPercent + '%';
    document.getElementById('user-task-count').textContent = `${userTaskCount} tasks`;
    document.getElementById('team-task-count').textContent = `${teamAverage} tasks`;
}

function renderUrgentTasks(userTasks, currentUser) {
    const urgentTasksList = document.getElementById('urgent-tasks-list');
    const today = new Date("2025-10-25T12:00:00"); // Hardcode date
    today.setHours(0, 0, 0, 0);

    const urgentTasks = userTasks.filter(task => {
        if (task.status === 'completed') return false;
        const deadline = new Date(task.deadline);
        const daysUntil = (deadline - today) / (1000 * 60 * 60 * 24);

        return task.priority === 'urgent' || daysUntil < 0 ||
            (daysUntil >= 0 && daysUntil <= 2);
    }).slice(0, 3);

    if (urgentTasks.length === 0) {
        urgentTasksList.innerHTML = '<p class="no-deadlines">No urgent tasks. Keep it up!</p>';
        return;
    }

    urgentTasksList.innerHTML = urgentTasks.map(task => {
        const deadline = new Date(task.deadline);
        const formattedDate = deadline.toLocaleDateString('en-US', { weekday: 'short', day: 'numeric', month: 'short' });

        const isOverdue = new Date(task.deadline) < today;

        return `
            <div class="urgent-task">
                <p class="urgent-task-title">${task.title}</p>
                <div class="urgent-task-meta">
                    <span class="urgent-task-date">${formattedDate}</span>
                    <span class="urgent-task-status">${isOverdue ? 'Overdue' : 'At risk'}</span>
                </div>
            </div>
        `;
    }).join('');
}

function renderTaskDistributionChart(userTasks) {
    const todoCount = userTasks.filter(t => t.status === 'todo').length;
    const inProgressCount = userTasks.filter(t => t.status === 'inprogress').length;
    const reviewCount = userTasks.filter(t => t.status === 'review').length;
    const completedCount = userTasks.filter(t => t.status === 'completed').length;

    document.getElementById('todo-count').textContent = `To Do: ${todoCount}`;
    document.getElementById('inprogress-count').textContent = `In Progress: ${inProgressCount}`;
    document.getElementById('review-count').textContent = `Review: ${reviewCount}`;
    document.getElementById('completed-count').textContent = `Completed: ${completedCount}`;

    const ctx = document.getElementById('taskDistributionChart');
    if (ctx) {
        // Destroy existing chart if it exists
        const existingChart = Chart.getChart(ctx);
        if (existingChart) {
            existingChart.destroy();
        }

        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['To Do', 'In Progress', 'Review', 'Completed'],
                datasets: [{
                    data: [todoCount, inProgressCount, reviewCount, completedCount],
                    backgroundColor: ['#FF8C8C', '#FFD166', '#A8DADC', '#81C5D4'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
}

/**
 * Runs on the Create Topic page (knowledge-base-create-topic.html)
 */
function setupCreateTopicForm(currentUser) {
    const createTopicForm = document.getElementById('create-topic-form');

    if (!createTopicForm) return;

    createTopicForm.addEventListener('submit', (e) => {
        e.preventDefault();

        const topicName = document.getElementById('topic-name').value.trim();
        const topicDescription = document.getElementById('topic-description').value.trim();

        if (!topicName) {
            alert('Please enter a topic name.');
            return;
        }

        // Check for duplicates (case-insensitive)
        if (getAllTopics().some(t => t.toLowerCase() === topicName.toLowerCase())) {
            alert('A topic with that name already exists. Please choose a different name.');
            return;
        }

        // Add the new topic to custom topics
        customTopics.push(topicName);
        saveCustomTopics();

        // Store success message to show on next page
        sessionStorage.setItem('topicCreated', `Topic "${topicName}" created successfully!`);

        // Redirect to knowledge base
        window.location.href = `knowledge-base.html?user=${currentUser.email}`;
    });
}

/**
 * Runs on the standalone Assign Task page (assign-task.html)
 */
function setupAssignTaskForm(currentUser) {
    const form = document.getElementById('assign-task-form');
    if (!form) return;

    // Populate projects
    const projectSelect = document.getElementById('task-project');
    if (projectSelect) {
        projectSelect.innerHTML = '<option value="">Select a project...</option>';
        simProjects.forEach(p => {
            projectSelect.innerHTML += `<option value="${p.id}">${p.name}</option>`;
        });
    }

    // Populate assignees
    const assigneeSelect = document.getElementById('task-assignee');
    if (assigneeSelect) {
        assigneeSelect.innerHTML = '<option value="">Select team member...</option>';
        for (const email in simUsers) {
            assigneeSelect.innerHTML += `<option value="${email}">${simUsers[email].name}</option>`;
        }
    }

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const title = document.getElementById('task-title').value;
        const projectId = document.getElementById('task-project').value;
        const assigneeEmail = document.getElementById('task-assignee').value;
        const priority = document.getElementById('task-priority').value;
        const deadline = document.getElementById('task-deadline').value;
        const description = document.getElementById('task-description').value;

        if (!title || !projectId || !assigneeEmail || !priority || !deadline) {
            alert('Please fill out all required fields.');
            return;
        }

        const project = simProjects.find(p => p.id === projectId);

        const newTask = {
            id: new Date().getTime(),
            title: title,
            project: project.name,
            projectId: project.id,
            assignedTo: [assigneeEmail],
            priority: priority,
            status: 'todo', // Default to 'todo'
            deadline: deadline,
            createdDate: new Date().toISOString().split('T')[0],
            description: description,
            createdBy: currentUser.email,
            type: 'assigned'
        };

        simTasks.push(newTask);
        saveTasks();

        sessionStorage.setItem('taskCreated', 'Task assigned successfully!');
        window.location.href = `home/home.html?user=${currentUser.email}`;
    });
}

/**
 * Runs on the Create Project page (create-project.html)
 */
function setupCreateProjectForm(currentUser) {
    const form = document.getElementById('create-project-form');
    if (!form) return;

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const projectName = document.getElementById('project-name').value;
        const projectDesc = document.getElementById('project-description').value;

        if (!projectName) {
            alert('Please enter a project name.');
            return;
        }

        const newProject = {
            id: projectName.toLowerCase().replace(/\s+/g, '-') + '-' + new Date().getTime(),
            name: projectName,
            description: projectDesc,
            createdBy: currentUser.email,
            createdDate: new Date().toISOString().split('T')[0]
        };

        simProjects.push(newProject);
        saveProjects();

        sessionStorage.setItem('projectCreated', `Project "${projectName}" created successfully!`);
        // Redirect to the new project's page
        window.location.href = `project/projects.html?project=${newProject.id}&user=${currentUser.email}`;
    });
}

/**
 * Generates HTML for a single task card for the project board
 */
function createTaskCardHTML(task, currentUser) {
    // Check for the special "Leader on Apollo" case
    const currentProjectId = getCurrentProjectId();
    const isManagerView = (currentUser.role === 'manager' || currentUser.role === 'team_leader');
    const isLeaderOnApollo = (currentUser.email === 'leader@make-it-all.co.uk' && currentProjectId === 'apollo');

    const isDraggable = isManagerView && !isLeaderOnApollo;

    // Find assignee names
    const assignees = task.assignedTo.map(email => {
        const user = simUsers[email];
        return user ? { name: user.name, avatarClass: user.avatarClass } : null;
    }).filter(Boolean);

    const assigneesHtml = assignees.map((user, index) => {
        if (index < 3) { // Show max 3 avatars
            return `<span class="avatar ${user.avatarClass}" title="${user.name}"></span>`
        }
        return ''
    }).join('');

    const moreAssignees = assignees.length > 3 ? `<span class="avatar-more">+${assignees.length - 3}</span>` : '';

    // Capitalize priority
    const priorityText = task.priority.charAt(0).toUpperCase() + task.priority.slice(1);

    return `
        <div class="task-card" data-task-id="${task.id}" ${isDraggable ? 'draggable="true"' : ''}>
            <span class="priority ${task.priority}">${priorityText}</span>
            <h3 class="task-title">${task.title}</h3>
            ${task.project ? `<p class="task-tag">${task.project}</p>` : ''}
            <div class="task-assignees">
                ${assigneesHtml}
                ${moreAssignees}
            </div>
        </div>
    `;
}

/**
 * Renders all tasks onto the project board
 */
function renderTaskBoard(currentUser, currentProjectId) {
    // Get all task columns
    const columns = document.querySelectorAll('.task-column');

    // Create a map of status to column elements
    const columnMap = {};
    columns.forEach(column => {
        const status = column.dataset.status;
        columnMap[status] = {
            list: column.querySelector('.task-list'),
            count: column.querySelector('.task-count')
        };
    });

    // Clear existing tasks
    Object.values(columnMap).forEach(col => {
        if (col.list) col.list.innerHTML = '';
    });

    // Check for the special "Leader on Apollo" case
    const isManagerView = (currentUser.role === 'manager' || currentUser.role === 'team_leader');
    const isLeaderOnApollo = (currentUser.email === 'leader@make-it-all.co.uk' && currentProjectId === 'apollo');

    // *** NEW: Filter tasks based on role AND project ***
    let tasksToRender = [];
    if (isManagerView && !isLeaderOnApollo) {
        tasksToRender = simTasks.filter(task => task.projectId === currentProjectId); // M/TL see all tasks for this project
    } else {
        tasksToRender = simTasks.filter(task =>
            task.assignedTo.includes(currentUser.email) &&
            task.projectId === currentProjectId // Members see only their tasks for this project
        );
    }

    // Filter tasks by status
    const tasksByStatus = {
        todo: tasksToRender.filter(t => t.status === 'todo'),
        inprogress: tasksToRender.filter(t => t.status === 'inprogress'),
        review: tasksToRender.filter(t => t.status === 'review'),
        completed: tasksToRender.filter(t => t.status === 'completed')
    };

    // Render tasks into their respective columns
    Object.keys(tasksByStatus).forEach(status => {
        const tasks = tasksByStatus[status];
        const column = columnMap[status];

        if (column && column.list) {
            tasks.forEach(task => {
                column.list.innerHTML += createTaskCardHTML(task, currentUser);
            });

            // Update count
            if (column.count) {
                column.count.textContent = tasks.length;
            }
        }
    });

    // *** NEW: Initialize D&D and Modals ***
    // Re-initialize drag and drop if user is manager/leader (and not the exception)
    if (isManagerView && !isLeaderOnApollo) {
        initDragAndDrop(currentUser, currentProjectId);
    }

    // Initialize task detail click listeners for everyone
    initTaskDetailsModal(currentUser);

    feather.replace();
}

/**
 * Initializes drag and drop functionality for the board
 */
function initDragAndDrop(currentUser, currentProjectId) {
    const taskCards = document.querySelectorAll('.task-card[draggable="true"]');
    const taskColumns = document.querySelectorAll('.task-column');

    taskCards.forEach(card => {
        card.addEventListener('dragstart', (e) => {
            e.dataTransfer.setData('text/plain', card.dataset.taskId);
            setTimeout(() => card.classList.add('dragging'), 0);
        });

        card.addEventListener('dragend', () => {
            card.classList.remove('dragging');
        });
    });

    taskColumns.forEach(column => {
        column.addEventListener('dragover', (e) => {
            e.preventDefault();
            const taskList = column.querySelector('.task-list');
            taskList.classList.add('drag-over');
        });

        column.addEventListener('dragleave', () => {
            const taskList = column.querySelector('.task-list');
            taskList.classList.remove('drag-over');
        });

        column.addEventListener('drop', (e) => {
            e.preventDefault();
            const taskList = column.querySelector('.task-list');
            taskList.classList.remove('drag-over');

            const taskId = e.dataTransfer.getData('text/plain');
            const newStatus = column.dataset.status; // This now works!

            const task = simTasks.find(t => t.id == taskId);

            if (task && task.status !== newStatus) {
                task.status = newStatus;
                saveTasks();
                renderTaskBoard(currentUser, currentProjectId); // Re-render the whole board
            }
        });
    });
}

/**
 * NEW: Initializes click listeners for task cards to show details
 */
function initTaskDetailsModal(currentUser) {
    const detailsModal = document.getElementById('task-details-modal');
    const detailsCloseBtn = document.getElementById('details-close-modal-btn');

    if (!detailsModal || !detailsCloseBtn) return;

    const closeModal = () => {
        detailsModal.style.display = 'none';
    }

    detailsCloseBtn.addEventListener('click', closeModal);

    detailsModal.addEventListener('click', (e) => {
        if (e.target === detailsModal) {
            closeModal();
        }
    });

    document.querySelectorAll('.task-card').forEach(card => {
        card.addEventListener('click', (e) => {
            const taskId = card.dataset.taskId;
            const task = simTasks.find(t => t.id == taskId);

            if (!task) return;

            // Find assignees
            const assignees = task.assignedTo.map(email => simUsers[email] ? simUsers[email].name : 'Unknown').join(', ');

            // Format dates
            const createdDate = new Date(task.createdDate).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
            const deadlineDate = new Date(task.deadline).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });

            document.getElementById('details-task-title').textContent = task.title;
            document.getElementById('details-task-project').textContent = task.project;
            document.getElementById('details-task-priority').textContent = task.priority;
            document.getElementById('details-task-priority').className = `priority-badge ${task.priority}`;
            document.getElementById('details-task-assignees').textContent = assignees;
            document.getElementById('details-task-created').textContent = createdDate;
            document.getElementById('details-task-deadline').textContent = deadlineDate;
            document.getElementById('details-task-description').textContent = task.description || 'No description provided.';
            const project_btn = document.getElementById('project-complete-btn');

            if (project_btn) {
                const currentProjectId = getCurrentProjectId();
                const isManagerView = (currentUser.role === 'manager' || currentUser.role === 'team_leader');
                const isLeaderOnApollo = (currentUser.email === 'leader@make-it-all.co.uk' && currentProjectId === 'apollo');

                // Hide button for managers/team leaders
                if (isManagerView && !isLeaderOnApollo) {
                    project_btn.style.display = 'none';
                } else {
                    // Show button for regular members and the special exception
                    project_btn.style.display = 'inline-block';

                    const new_btn = project_btn.cloneNode(true);
                    project_btn.parentNode.replaceChild(new_btn, project_btn);
                    new_btn.addEventListener('click', () => {
                        if (confirm("Are you sure you want to put this task up for review?")) {
                            alert("This is a prototype, so this functionality is not fully implemented.");
                            closeModal();
                        }
                    });
                }
            }
            detailsModal.style.display = 'flex';
        });
    });
}


/**
 * *** COMPLETELY REVISED FUNCTION ***
 * Runs on the Projects page (projects.html)
 */
function loadProjectsPage(currentUser) {
    const currentProjectId = getCurrentProjectId();
    updateSidebarAndNav(currentUser, currentProjectId);

    // Check for the special "Leader on Apollo" case
    const isManagerView = (currentUser.role === 'manager' || currentUser.role === 'team_leader');
    const isLeaderOnApollo = (currentUser.email === 'leader@make-it-all.co.uk' && currentProjectId === 'apollo');

    const showManagerControls = isManagerView && !isLeaderOnApollo;

    // Get modal elements
    const modal = document.getElementById('assign-task-modal');
    const closeModalBtn = document.getElementById('close-modal-btn');
    const modalForm = document.getElementById('modal-assign-task-form');
    const modalStatusInput = document.getElementById('modal-task-status');
    const modalProjectSelect = document.getElementById('modal-task-project');

    // --- Modal Functions ---
    const openModal = (status) => {
        if (!modal) return;
        modalStatusInput.value = status; // Set the hidden status input
        // Set project dropdown to current project
        modalProjectSelect.value = currentProjectId;
        modal.style.display = 'flex';
        feather.replace();
    };
    const closeModal = () => {
        if (!modal) return;
        modal.style.display = 'none';
        modalForm.reset();
    };

    // Populate modal dropdowns
    if (modalProjectSelect) {
        modalProjectSelect.innerHTML = '<option value="">Select a project...</option>';
        simProjects.forEach(p => {
            modalProjectSelect.innerHTML += `<option value="${p.id}">${p.name}</option>`;
        });
    }

    // Populate assignee checklist
    const modalAssigneeList = document.getElementById('modal-task-assignees');
    if (modalAssigneeList) {
        modalAssigneeList.innerHTML = '';
        for (const email in simUsers) {
            const user = simUsers[email];
            const checkboxItem = document.createElement('div');
            checkboxItem.className = 'assignee-checkbox-item';
            checkboxItem.innerHTML = `
                <input type="checkbox" id="assignee-${email}" value="${email}">
                <label for="assignee-${email}">${user.name} (${user.role})</label>
            `;
            modalAssigneeList.appendChild(checkboxItem);
        }
    }

    // --- Role-based UI (Column "+" buttons) ---
    const addButtons = document.querySelectorAll('.add-task');
    addButtons.forEach(btn => {
        if (showManagerControls) {
            btn.style.display = 'grid'; // Show the button
            btn.addEventListener('click', (e) => {
                // Get status from parent column's data-status
                const status = e.currentTarget.closest('.task-column').dataset.status;
                openModal(status);
            });
        } else {
            btn.style.display = 'none'; // Hide for team members
        }
    });

    // --- Modal Event Listeners ---
    if (modal) {
        closeModalBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal(); // Close if clicking overlay
        });

        modalForm.addEventListener('submit', (e) => {
            e.preventDefault();

            const title = document.getElementById('modal-task-title').value;
            const projectId = document.getElementById('modal-task-project').value;

            // Get all checked assignees
            const assigneeCheckboxes = document.querySelectorAll('#modal-task-assignees input[type="checkbox"]:checked');
            const assigneeEmails = Array.from(assigneeCheckboxes).map(cb => cb.value);

            const priority = document.getElementById('modal-task-priority').value;
            const deadline = document.getElementById('modal-task-deadline').value;
            const description = document.getElementById('modal-task-description').value;
            const status = modalStatusInput.value;

            // Deadline validation
            const today = new Date();
            today.setHours(0, 0, 0, 0); // Reset time for clean comparison
            const selectedDate = new Date(deadline);

            if (selectedDate < today) {
                alert('Deadline cannot be before today’s date. Please choose a valid date.');
                return;
            }

            if (!title || !projectId || assigneeEmails.length === 0 || !priority || !deadline) {
                alert('Please fill out all required fields and select at least one assignee.');
                return;
            }


            if (!title || !projectId || assigneeEmails.length === 0 || !priority || !deadline) {
                alert('Please fill out all required fields and select at least one assignee.');
                return;
            }

            const project = simProjects.find(p => p.id === projectId);

            const newTask = {
                id: new Date().getTime(),
                title: title,
                project: project ? project.name : 'Unknown Project',
                projectId: projectId,
                assignedTo: assigneeEmails,
                priority: priority,
                status: status,
                deadline: deadline,
                createdDate: new Date().toISOString().split('T')[0], // Set created date
                description: description, // Set description
                createdBy: currentUser.email,
                type: 'assigned'
            };

            simTasks.push(newTask);
            saveTasks();

            renderTaskBoard(currentUser, currentProjectId); // Refresh the board with the new task
            closeModal();
            showSuccessNotification('Task assigned successfully!');
        });
    }


    // --- Initial Render ---
    renderTaskBoard(currentUser, currentProjectId);

    // Show notifications from other pages
    const showProjectNotification = sessionStorage.getItem('projectCreated');
    if (showProjectNotification) {
        showSuccessNotification(showProjectNotification);
        sessionStorage.removeItem('projectCreated');
    }
    // --- DELETE TASK BUTTON LOGIC (Manager only) ---
    const deleteBtn = document.getElementById('delete-task-btn');

    if (deleteBtn) {
        if (currentUser.role === 'manager') {
            deleteBtn.style.display = 'inline-block';

            deleteBtn.onclick = () => {
                const confirmDelete = confirm("Are you sure you want to delete this task?");
                if (!confirmDelete) return;

                const taskTitle = document.getElementById("details-task-title").textContent.trim();
                const taskIndex = simTasks.findIndex(t => t.title === taskTitle);

                if (taskIndex !== -1) {
                    simTasks.splice(taskIndex, 1);
                    saveTasks();
                    renderTaskBoard(currentUser, getCurrentProjectId());
                    showSuccessNotification("Task deleted successfully!");
                    document.getElementById("task-details-modal").style.display = "none";
                } else {
                    alert("Error: Task not found.");
                }
            };
        } else {
            deleteBtn.style.display = "none"; // Hide for everyone else
        }
    }

}

// ===============================================
// === NEW FUNCTIONS FOR MANAGER PROGRESS PAGE ===
// ===============================================

/**
 * Runs on the Manager Progress page (manager-progress-page)
 */
function loadManagerProgressPage(currentUser) {
    const currentProjectId = getCurrentProjectId();
    updateSidebarAndNav(currentUser, currentProjectId);

    // Get all tasks for this specific project
    const projectTasks = simTasks.filter(task => task.projectId === currentProjectId);

    // Render all components
    renderManagerTaskProgress(projectTasks);
    renderManagerDeadlines(projectTasks);
    renderProjectResources(projectTasks);
    renderTasksPerMemberChart(projectTasks);

    feather.replace();
}

/**
 * (Manager) Renders the overall project task progress
 */
function renderManagerTaskProgress(projectTasks) {
    const completedTasks = projectTasks.filter(t => t.status === 'completed').length;
    const totalTasks = projectTasks.length;
    const progressPercent = totalTasks > 0 ? Math.round((completedTasks / totalTasks) * 100) : 0;

    document.getElementById('task-progress-fill').style.width = progressPercent + '%';
    document.getElementById('progress-text').textContent =
        `Your team has completed ${progressPercent}% of tasks assigned for this project.`;
}

/**
 * (Manager) Renders upcoming deadlines for the whole team
 */
function renderManagerDeadlines(projectTasks) {
    const deadlinesList = document.getElementById('deadlines-list');
    const today = new Date("2025-10-25T12:00:00"); // Hardcode date for demo consistency
    today.setHours(0, 0, 0, 0);

    const upcomingTasks = projectTasks
        .filter(t => t.status !== 'completed')
        .sort((a, b) => new Date(a.deadline) - new Date(b.deadline))
        .slice(0, 5); // Show top 5 for manager view

    if (upcomingTasks.length === 0) {
        deadlinesList.innerHTML = '<p class="no-deadlines">No upcoming deadlines for the team!</p>';
        return;
    }

    deadlinesList.innerHTML = upcomingTasks.map(task => {
        const deadline = new Date(task.deadline);
        const formattedDate = deadline.toLocaleDateString('en-US', { weekday: 'short', day: 'numeric', month: 'short' });

        let status = 'on-track';
        let statusText = 'On track';

        if (deadline < today) {
            status = 'overdue';
            statusText = 'Overdue';
        } else {
            const daysUntil = Math.ceil((deadline - today) / (1000 * 60 * 60 * 24));
            if (daysUntil <= 2) {
                status = 'at-risk';
                statusText = 'At risk';
            }
        }

        // Find assignee names
        const assignees = task.assignedTo.map(email => simUsers[email] ? simUsers[email].name.split(' ')[0] : 'N/A').join(', ');

        return `
            <div class="deadline-item">
                <div class="deadline-item-left">
                    <p class="deadline-title">${task.title}</p>
                    <p class="deadline-assignee">Assignee: ${assignees}</p>
                </div>
                <div class="deadline-info">
                    <span class="deadline-date">${formattedDate}</span>
                    <span class="deadline-status ${status}">${statusText}</span>
                </div>
            </div>
        `;
    }).join('');
}

/**
 * (Manager) Renders the Project Resources card
 */
function renderProjectResources(projectTasks) {
    const today = new Date("2025-10-25T12:00:00"); // Hardcode date
    today.setHours(0, 0, 0, 0);

    const overdueTasks = projectTasks.filter(t =>
        t.status !== 'completed' && new Date(t.deadline) < today
    ).length;

    const card = document.getElementById('project-resource-card');
    const statusText = document.getElementById('resource-status-text');
    const statusDesc = document.getElementById('resource-status-desc');

    // Remove old classes
    card.classList.remove('status-green', 'status-yellow', 'status-red');

    if (overdueTasks === 0) {
        // Green
        card.classList.add('status-green');
        statusText.textContent = 'Sufficiently Resourced';
        statusDesc.textContent = 'No tasks are overdue. Resources are efficiently allocated.';
    } else if (overdueTasks >= 1 && overdueTasks <= 3) {
        // Yellow
        card.classList.add('status-yellow');
        statusText.textContent = 'Tightly Resourced';
        statusDesc.textContent = `One or more tasks are overdue. These tasks may need more resources to be completed.`;
    } else {
        // Red
        card.classList.add('status-red');
        statusText.textContent = 'Under-Resourced';
        statusDesc.textContent = `More than three tasks are overdue. This project needs more resources, and your team members may require additional training.`;
    }
}

/**
 * (Manager) Renders the "Tasks per member" stacked bar chart
 */
function renderTasksPerMemberChart(projectTasks) {
    // Get all unique users in this project
    const userEmails = [...new Set(projectTasks.flatMap(t => t.assignedTo))];

    const labels = [];
    const todoData = [];
    const inProgressData = [];
    const completedData = [];

    //Aggregate task counts for each user
    userEmails.forEach(email => {
        const user = simUsers[email];
        if (!user) return;

        labels.push(user.name);
        const userTasks = projectTasks.filter(t => t.assignedTo.includes(email));

        todoData.push(userTasks.filter(t => t.status === 'todo' || t.status === 'review').length); // Combine To Do and Review as "Not Started"
        inProgressData.push(userTasks.filter(t => t.status === 'inprogress').length);
        completedData.push(userTasks.filter(t => t.status === 'completed').length);
    });

    //Render the chart
    const container = document.getElementById('tasks-per-member-chart-container');
    container.innerHTML = '<canvas id="tasksPerMemberChart"></canvas>'; // Clear and add canvas
    const ctx = document.getElementById('tasksPerMemberChart');

    if (ctx) {
        //Destroy existing chart if it exists
        const existingChart = Chart.getChart(ctx);
        if (existingChart) {
            existingChart.destroy();
        }

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Completed',
                        data: completedData,
                        backgroundColor: '#34A853',
                    },
                    {
                        label: 'In Progress',
                        data: inProgressData,
                        backgroundColor: '#E6A100',
                    },
                    {
                        label: 'Not Started',
                        data: todoData,
                        backgroundColor: '#D93025',
                    }
                ]
            },
            options: {
                indexAxis: 'y', // Makes it a horizontal bar chart
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        stacked: true, // Stacks the bars
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                family: "'Poppins', sans-serif"
                            }
                        }
                    },
                    y: {
                        stacked: true,
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                family: "'Poppins', sans-serif"
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        font: {
                            family: "'Poppins', sans-serif"
                        }
                    }
                },
                // Adjust height dynamically based on number of users
                aspectRatio: Math.max(0.5, 4 / labels.length),
            }
        });
    }
}

/**
 * Runs on the Project Resources page (project-resources.html)
 */
function loadProjectResourcesPage(currentUser) {
    const currentProjectId = getCurrentProjectId();
    updateSidebarAndNav(currentUser, currentProjectId);

    const project = simProjects.find(p => p.id === currentProjectId);

    if (project) {
        // --- Fill in basic project details ---
        document.getElementById('project-created-date').textContent =
            new Date(project.createdDate).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
        document.getElementById('project-description').textContent =
            project.description || 'No description provided for this project.';

        // --- Build project contacts dynamically ---
        const contactsList = document.getElementById('project-contacts-list');
        if (contactsList) {
            contactsList.innerHTML = ''; // clear any old content

            const contacts = [];

            // Always include the project manager (creator)
            if (project.createdBy && simUsers[project.createdBy]) {
                const manager = simUsers[project.createdBy];
                contacts.push({
                    name: manager.name,
                    role: 'Project Manager',
                    email: project.createdBy,
                    avatarClass: manager.avatarClass
                });
            }

            // Add team leader if exists
            if (project.teamLeader && simUsers[project.teamLeader]) {
                const leader = simUsers[project.teamLeader];
                contacts.push({
                    name: leader.name,
                    role: 'Team Leader',
                    email: project.teamLeader,
                    avatarClass: leader.avatarClass
                });
            }

            // Render both contacts
            contactsList.innerHTML = contacts.map(c => `
                 <div class="contact-item">
                     <span class="avatar ${c.avatarClass}">
                         ${c.name.split(' ').map(n => n[0]).join('')}
                     </span>
                     <div class="contact-info">
                         <span class="contact-name">${c.name}</span>
                         <span class="contact-role">${c.role}</span>
                         <a href="mailto:${c.email}">${c.email}</a>
                     </div>
                 </div>
             `).join('');
        }
    }

    // --- Show upload button for managers or leaders ---
    if (currentUser.role === 'manager' || currentUser.role === 'team_leader') {
        const uploadBtn = document.getElementById('upload-btn');
        if (uploadBtn) {
            uploadBtn.style.display = 'inline-flex';
            uploadBtn.addEventListener('click', () => {
                alert('This is a prototype demo feature. File upload is not functional.');
            });
        }
    }
}


/**
 * Runs on the Create Personal To-Do page (create-todo.html)
 */
function setupCreateTodoForm(currentUser) {
    const form = document.getElementById('create-todo-form');
    if (!form) return;

    // Populate projects dropdown
    const projectSelect = document.getElementById('todo-project');
    if (projectSelect) {
        const projects = [...new Set(simTasks.filter(t => t.assignedTo.includes(currentUser.email)).map(t => t.project))];
        projects.forEach(p => {
            projectSelect.innerHTML += `<option value="${p}">${p}</option>`;
        });
    }

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const title = document.getElementById('todo-title').value;
        const project = document.getElementById('todo-project').value;
        const priority = document.getElementById('todo-priority').value;
        const deadline = document.getElementById('todo-deadline').value;

        //Deadline validation
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const selectedDate = new Date(deadline);

        if (selectedDate < today) {
            alert('Deadline cannot be before today’s date. Please choose a valid date.');
            return;
        }


        const newTodo = {
            id: new Date().getTime(),
            title: title,
            project: project || null,
            projectId: simProjects.find(p => p.name === project)?.id || null,
            owner: currentUser.email,
            priority: priority,
            status: 'todo',
            deadline: deadline || null,
            type: 'personal'
        };

        simPersonalTodos.push(newTodo);
        savePersonalTodos();

        sessionStorage.setItem('taskCreated', 'Personal to-do added!');
        window.location.href = `home.html?user=${currentUser.email}`;
    });
}


// *** ADDED: New function to load Project Archive page ***
function loadProjectArchivePage(currentUser) {
    const gridContainer = document.getElementById('archive-grid-container');
    if (!gridContainer) return;

    // Generate HTML for each card from mock data
    gridContainer.innerHTML = simArchivedProjects.map(project => {
        return `
            <div class="archive-card">
                <h2>${project.name}</h2>
                <ul>
                    <li>
                        <strong>Team Leader:</strong>
                        <span>${project.teamLeader}</span>
                        <span class="team-leader-avatar ${project.avatarClass}">
                            ${project.teamLeader.split(' ').map(n => n[0]).join('')}
                        </span>
                    </li>
                    <li>
                        <strong>Description:</strong>
                        <span>${project.description}</span>
                    </li>
                    <li>
                        <strong>Date Created:</strong>
                        <span>${project.createdDate}</span>
                    </li>
                    <li>
                        <strong>Date Closed:</strong>
                        <span>${project.closedDate}</span>
                    </li>
                </ul>
            </div>
        `;
    }).join('');
}
// *** END ADDED FUNCTION ***


// ===============================================
// === DOCUMENT LOAD =============================
// ===============================================

document.addEventListener('DOMContentLoaded', () => {

    // Get the "logged in" user
    const currentUser = getCurrentUser();

    // Make all links on the page keep the user "logged in"
    persistUserQueryParam(currentUser);

    // *** ADDED: Show "Project Archive" in sidebar for managers ***
    const navArchive = document.getElementById('nav-archive');
    if (navArchive && currentUser.role === 'manager') {
        navArchive.style.display = 'block';
    }
    // *** END ADDED CODE ***


    // Run page-specific logic based on body ID
    const pageId = document.body.id;

    if (pageId === 'kb-index') {
        const returnTopic = sessionStorage.getItem('returnToTopic');
        const showCreatedNotification = sessionStorage.getItem('topicCreated');
        const showPostNotification = sessionStorage.getItem('postCreated');

        // Show any pending notifications
        if (showCreatedNotification) {
            showSuccessNotification(showCreatedNotification);
            sessionStorage.removeItem('topicCreated');
        }
        if (showPostNotification) {
            showSuccessNotification(showPostNotification);
            sessionStorage.removeItem('postCreated');
        }

        if (returnTopic) {
            sessionStorage.removeItem('returnToTopic'); // Clear it after use
            loadKbIndex(currentUser); // Load index to attach listeners
            showTopicView(returnTopic, currentUser); // Immediately switch to topic view
        } else {
            loadKbIndex(currentUser);
        }
    } else if (pageId === 'kb-post') {
        loadKbPost(currentUser);
    } else if (pageId === 'kb-create') {
        setupCreateForm(currentUser);
    } else if (pageId === 'settings-page') {
        loadSettingsPage(currentUser);
    } else if (pageId === 'kb-topics-all') {
        // dedicated "All Topics" page
        loadAllTopicsPage(currentUser);
    } else if (pageId === 'kb-create-topic') {
        // Create Topic form page
        setupCreateTopicForm(currentUser);
    } else if (pageId === 'home-page') {
        // Home page with to-do list
        loadHomePage(currentUser);
    } else if (pageId === 'progress-page') {
        // Team Member Progress page (with redirect)
        loadProgressPage(currentUser);
    } else if (pageId === 'manager-progress-page') {
        // Manager Progress page
        loadManagerProgressPage(currentUser);
    } else if (pageId === 'project-resources-page') {
        // Project Resources page
        loadProjectResourcesPage(currentUser);
    } else if (pageId === 'assign-task-page') {
        // Standalone Assign Task form
        setupAssignTaskForm(currentUser);
    } else if (pageId === 'create-project-page') {
        setupCreateProjectPage(currentUser);
    } else if (pageId === 'create-todo-page') {
        // Create Personal To-Do form
        setupCreateTodoForm(currentUser);
    } else if (pageId === 'projects-page') {
        // Project Kanban Board
        loadProjectsPage(currentUser);
    } else if (pageId === 'project-archive-page') {
        // *** ADDED: Load logic for new archive page ***
        loadProjectArchivePage(currentUser);
    }

    // Finally, activate all Feather icons
    feather.replace();
});

// I've put this for testing, just type resetAllData(); to refresh the web page after testing
function resetAllData() {
    if (confirm("This will erase all current data and reload the defaults. Continue?")) {
        localStorage.clear();
        sessionStorage.clear();
        location.reload();
    }
}
