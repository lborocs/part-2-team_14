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

function getUsersMap() {
    // Prefer PHP injected users
    if (window.__USERS__ && typeof window.__USERS__ === "object") return window.__USERS__;

    // Fallback if you still have simUsers defined somewhere else
    if (typeof window.simUsers === "object") return window.simUsers;

    // Final fallback
    return {};
}


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
let simTasks = JSON.parse(localStorage.getItem('simTasks'));
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
 * Gets the current user from the PHP session.
 */
async function getCurrentUser() {
    try {
        const response = await fetch('../../actions/login_sync.php', {
            credentials: 'include'
        });

        const data = await response.json();

        if (!data.loggedIn) {
            // Redirects if not logged in
            window.location.href = '../index.html';
            return null;
        }

        return data.user;

    } catch (err) {
        console.error('Failed to sync login state:', err);
        return null;
    }
}

function getUsersSource() {
    // Prefer users injected by PHP
    if (window.__USERS__ && typeof window.__USERS__ === "object") return window.__USERS__;
    // fallback (if you ever define simUsers elsewhere)
    if (typeof simUsers !== "undefined") return simUsers;
    return {}; // safe fallback
}


function getCurrentUserStatus() {
    if (window.__CAN_MANAGE_PROJECT__) {
        return {
            role: "manager",
            name: "Project Manager"
        };
    }

    return {
        role: "member",
        name: "Team Member"
    };
}

function updateAddTaskButtonsVisibility() {
  const canManage = !!window.__CAN_MANAGE_PROJECT__;
  document.querySelectorAll(".add-task").forEach(btn => {
    btn.style.display = canManage ? "" : "none";
  });
}


/**
 * NEW: Gets the current project ID from the URL.
 * Defaults to 'NULL' if none is set.
 */
function getCurrentProjectId() {
    const params = new URLSearchParams(window.location.search);
    if (params.has('project_id')) {
        return params.get('project_id'); // numeric string like "2"
    }
    return null;
}

/**
 * NEW: Dynamically updates sidebar links and project header/nav
 */
function updateSidebarAndNav() {
    const project = window.__PROJECT__ || {};
    const role = (window.__ROLE__ || "team_member").toLowerCase();

    // 1) Update header/breadcrumb (do NOT overwrite with "Project")
    const breadcrumb = document.getElementById("project-name-breadcrumb");
    const header = document.getElementById("project-name-header");

    // Use best available name WITHOUT flashing back to "Project"
    const projectName =
        (project && project.project_name) ||
        sessionStorage.getItem("currentProjectName") ||
        null;

    if (projectName) {
        if (breadcrumb) breadcrumb.textContent = projectName;
        if (header) header.textContent = projectName;
    }
    // else: leave whatever PHP already rendered


    // 2) Read project_id from URL
    const params = new URLSearchParams(window.location.search);
    const projectId = params.get("project_id");
    if (!projectId) return;

    // 3) Decide which Progress page to use (manager/team_leader)
    const isManager = (role === "manager");
    const canManageProject = !!window.__CAN_MANAGE_PROJECT__;
    const progressPage = (isManager || canManageProject) ? "manager-progress.php" : "progress.php";



    // 4) Build nav links
    const navLinks = document.getElementById("project-nav-links");
    if (navLinks) {
        const path = window.location.pathname.toLowerCase();

        const tasksActive = (path.includes("projects.php") || path.includes("projects.html")) ? "active" : "";

        const progressActive =
            path.includes("progress.php") ||
                path.includes("manager-progress.") ||
                path.includes("progress.php") ||
                path.includes("manager-progress.php")
                ? "active"
                : "";

        const resourcesActive = path.includes("project-resources.php") ? "active" : "";

        navLinks.innerHTML = `
      <a href="projects.php?project_id=${encodeURIComponent(projectId)}" class="${tasksActive}">Tasks</a>
      <a href="${progressPage}?project_id=${encodeURIComponent(projectId)}" class="${progressActive}">Progress</a>
      <a href="project-resources.php?project_id=${encodeURIComponent(projectId)}" class="${resourcesActive}">Resources</a>
    `;
    }

    // 5) Example: manager-only button
    const closeProjectBtn = document.getElementById("close-project-btn");
    if (closeProjectBtn) {
        closeProjectBtn.style.display = (role === "manager") ? "block" : "none";
    }
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
    const form = document.getElementById("create-project-form");
    if (!form) return;

    // searchable leader input (AJAX)
    setupLeaderAutocomplete({
        inputId: "leader-search",
        hiddenId: "team-leader-id",
        resultsId: "leader-results",
        // If you made the endpoint INSIDE create-project.php:
        endpointUrl: "create-project.php?ajax=leaders",
        // If you instead created a separate file, use:
        // endpointUrl: "search_leaders.php",
        formId: "create-project-form",
    });

    //  add a safety check to ensure they picked from suggestions
    form.addEventListener("submit", (e) => {
        const projectName = document.getElementById("project-name")?.value?.trim();
        const leaderId = document.getElementById("team-leader-id")?.value;

        if (!projectName || !leaderId) {
            e.preventDefault();
            alert("Please enter a project name and select a team leader from the suggestions.");
            return;
        }
    });
}



/**
 * Runs on the Settings page (settings.php)
 */
function loadSettingsPage(currentUser) {
    document.getElementById('profile-name').value = currentUser.name;
    document.getElementById('profile-email').value = currentUser.email;
    const role = currentUser.role.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
    document.getElementById('profile-role').value = role;

    // Upload a new profile picture
    const uploadBtn = document.getElementById("upload-image-btn");
    const fileInput = document.getElementById("profile-image-input");
    const profileImg = document.getElementById("profile-picture");

    uploadBtn.addEventListener("click", () => {
        fileInput.click();
    });

    fileInput.addEventListener("change", () => {
        const file = fileInput.files[0];
        if (!file) return;

        // Update the image on the page
        const reader = new FileReader();
        reader.onload = e => {
            profileImg.src = e.target.result;
        };
        reader.readAsDataURL(file);

        // Use file name as the database path
        const simulatedPath = `/${file.name}`;

        fetch("settings.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ profile_picture: simulatedPath })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                console.log("Profile picture updated in DB!");
            } else {
                console.error("Failed to update profile picture in DB.");
            }
        })
        .catch(err => console.error(err));
    });

    // Delete current profile picture
    const deleteBtn = document.getElementById("delete-image-btn");

    deleteBtn.addEventListener("click", () => {
        const defaultPath = "/default-avatar.png";
        profileImg.src = defaultPath;

        fetch("settings.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ profile_picture: defaultPath })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                console.log("Profile picture reset to default in DB!");
            } else {
                console.error("Failed to reset profile picture in DB.");
            }
        })
        .catch(err => console.error(err));
    });

    // Update password
    document.getElementById('password-form').addEventListener('submit', (e) => {
        e.preventDefault();
        alert('Password updated! (This is a demo)');
    });

    // Update notification preferences
    document.getElementById('notifications-form').addEventListener('submit', (e) => {
        e.preventDefault();
        alert('Notification preferences saved!');
    });

    const express = require("express");
    const multer = require("multer");
    const path = require("path");

    const upload = multer({ dest: "uploads/" }); // temp storage
    const app = express();

    app.post("/api/update-profile-picture", upload.single("profileImage"), (req, res) => {
        const file = req.file;
        if (!file) return res.json({ success: false });

        // Here you would:
        // 1. Move/rename the file
        // 2. Update the database with the new filename/path
        // 3. Return success
        console.log(file); // contains file info
        res.json({ success: true });
    });

    // Sign Out logic
    document.getElementById('sign-out-btn').addEventListener('click', (e) => {
        e.preventDefault();

        // Clear the simulated session
        localStorage.clear();
        sessionStorage.clear();

        alert('Signing out...');

        // Redirect to the login page
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
 * Runs on the Home page (home/home.php)
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
 * Runs on the Progress page (progress.php) - shows only assigned tasks
 */
async function loadProgressPage(currentUser) {
    const currentProjectId = getCurrentProjectId();

    if (!currentProjectId) {
        alert("No project selected");
        window.location.href = "projects-overview.php";
        return;
    }

    // ✅ Project is already injected by PHP, but keep this as a refresh (optional)
    try {
        const response = await fetch(
            `projects.php?ajax=get_project&project_id=${encodeURIComponent(currentProjectId)}`,
            { credentials: "include" }
        );
        const data = await response.json();

        if (data.success && data.project) {
            window.__PROJECT__ = data.project;
        }
    } catch (err) {
        console.error("Error fetching project:", err);
        // don't hard-fail; we still have __PROJECT__ from PHP
    }

    // ✅ Role-based redirect (keep it simple)
    if (window.__CAN_MANAGE_PROJECT__) {
        window.location.href = `manager-progress.php?project_id=${encodeURIComponent(currentProjectId)}`;
        return;
    }

    // ✅ Update breadcrumbs/nav using injected project
    updateSidebarAndNav();

    // ✅ Pull tasks from DB (NOT simTasks)
    let projectTasks = [];
    try {
        projectTasks = await fetchProjectTasksFromDb(currentProjectId);
    } catch (err) {
        console.error("Failed to load tasks from DB:", err);
        projectTasks = [];
    }

    // ✅ Only tasks assigned to this user
    const userEmail = String(currentUser.email || "").toLowerCase();
    const userTasks = projectTasks.filter(t =>
        Array.isArray(t.assignedTo) &&
        t.assignedTo.map(e => String(e).toLowerCase()).includes(userEmail)
    );

    // ✅ Update Task Progress widget (only if elements exist)
    const fillEl = document.getElementById("task-progress-fill");
    const textEl = document.getElementById("progress-text");

    const completed = userTasks.filter(t => t.status === "completed").length;
    const total = userTasks.length;
    const pct = total > 0 ? Math.round((completed / total) * 100) : 0;

    if (fillEl) fillEl.style.width = pct + "%";
    if (textEl) {
        textEl.textContent = total
            ? `You have completed ${pct}% of your assigned tasks for this project.`
            : `You don’t have any assigned tasks for this project yet.`;
    }

    // ✅ Update Upcoming Deadlines widget (only if element exists)
    const listEl = document.getElementById("deadlines-list");
    if (listEl) {
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const upcoming = userTasks
            .filter(t => t.status !== "completed" && t.deadline)
            .sort((a, b) => new Date(a.deadline) - new Date(b.deadline))
            .slice(0, 3);

        if (!upcoming.length) {
            listEl.innerHTML = `<p class="no-deadlines">No upcoming deadlines. You’re all caught up!</p>`;
        } else {
            listEl.innerHTML = upcoming.map(task => {
                const d = new Date(task.deadline);
                d.setHours(0, 0, 0, 0);

                const formatted = d.toLocaleDateString("en-GB", {
                    weekday: "short",
                    day: "numeric",
                    month: "short"
                });

                let status = "on-track";
                let statusText = "On track";

                if (d < today) {
                    status = "overdue";
                    statusText = "Overdue";
                } else {
                    const daysUntil = Math.ceil((d - today) / (1000 * 60 * 60 * 24));
                    if (daysUntil <= 2) {
                        status = "at-risk";
                        statusText = "At risk";
                    }
                }

                return `
          <div class="deadline-item">
            <p class="deadline-title">${task.title}</p>
            <div class="deadline-info">
              <span class="deadline-date">${formatted}</span>
              <span class="deadline-status ${status}">${statusText}</span>
            </div>
          </div>
        `;
            }).join("");
        }
    }

    if (window.feather) feather.replace();
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
function setupAssignTaskForm() {
    const form = document.getElementById("assign-task-form");
    if (!form) return;

    // ✅ Prevent double-binding (stops multiple submit handlers)
    if (form.dataset.bound === "1") return;
    form.dataset.bound = "1";


    form.addEventListener("submit", async (e) => {
        e.preventDefault(); // ⛔ STOP PAGE RELOAD

        const titleEl = document.getElementById("modal-task-title");
        const priorityEl = document.getElementById("modal-task-priority");
        const deadlineEl = document.getElementById("modal-task-deadline");

        if (!titleEl || !priorityEl || !deadlineEl) {
            console.error("Assign task form fields not found");
            return;
        }

        const taskName = titleEl.value.trim();
        const priority = priorityEl.value;
        const deadline = deadlineEl.value;
        const description =
            document.getElementById("modal-task-description")?.value.trim() || "";

        const assignees = Array.from(
            document.querySelectorAll(
                '#modal-task-assignees input[type="checkbox"]:checked'
            )
        ).map((cb) => cb.value);

        if (!taskName || !deadline || assignees.length === 0) {
            alert("Please fill all required fields");
            return;
        }

        const formData = new FormData();
        formData.append("ajax", "create_task");
        formData.append("task_name", taskName);
        formData.append("priority", priority);
        formData.append("deadline", deadline);
        formData.append("description", description);

        const rawStatus =
            document.getElementById("modal-task-status")?.value || "todo";

        const statusMap = {
            todo: "to_do",
            inprogress: "in_progress",
            review: "review",
            completed: "completed",
        };

        formData.append("status", statusMap[rawStatus] || "to_do");

        assignees.forEach((a) => formData.append("assignees[]", a));

        const res = await fetch(
            `projects.php?project_id=${encodeURIComponent(
                window.__PROJECT__.project_id
            )}`,
            {
                method: "POST",
                body: formData,
            }
        );

        const data = await res.json();

        if (!data.success) {
            alert(data.message || "Failed to create task");
            return;
        }

        // Close modal + refresh
        document.getElementById("assign-task-modal").style.display = "none";
        document.body.style.overflow = "";

        fetchAndRenderTasks(); // reload Kanban
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
    function renderStatusPill(task) {
    const statuses = {
        todo: "To Do",
        inprogress: "In Progress",
        review: "Review",
        completed: "Completed"
    };

    const priorities = {
        low: "Low",
        medium: "Medium",
        high: "High",
        urgent: "Urgent"
    };

    return `
      <div class="task-status-menu" data-task-id="${task.id}">
        <button class="status-pill icon-only" aria-label="Task actions">
          <span class="ellipsis">⋯</span>
        </button>

        <div class="status-dropdown" hidden>
          <div class="dropdown-section">
            <div class="dropdown-label">Change status</div>
            ${Object.entries(statuses)
                .filter(([k]) => k !== task.status)
                .map(([k, v]) =>
                    `<button data-action="status" data-value="${k}">
                        Move to ${v}
                     </button>`
                ).join("")}
          </div>

          <div class="dropdown-divider"></div>

          <div class="dropdown-section">
            <div class="dropdown-label">Change priority</div>
            ${Object.entries(priorities)
                .filter(([k]) => k !== task.priority)
                .map(([k, v]) =>
                    `<button data-action="priority" data-value="${k}">
                        Set priority: ${v}
                     </button>`
                ).join("")}
          </div>
        </div>
      </div>
    `;
}

    // Check for the special "Leader on Apollo" case
    const currentProjectId = getCurrentProjectId();

    const role = String(window.__ROLE__ || currentUser.role || "team_member").toLowerCase();
    const isManager = (role === "manager");
    const canManageProject = !!window.__CAN_MANAGE_PROJECT__;
    const isManagerView = isManager || canManageProject;


    const isLeaderOnApollo = (currentUser.email === 'leader@make-it-all.co.uk' && currentProjectId === 'apollo');

    const isDraggable = isManagerView && !isLeaderOnApollo;
    const showMoveBtn = isManagerView && !isLeaderOnApollo;

    const usersMap = getUsersMap();

    const assignees = (task.assignedTo || []).map(email => {
        const user = usersMap[email];
        return user
            ? { name: user.name, avatarClass: user.avatarClass, avatarUrl: user.avatarUrl }
            : { name: email, avatarClass: "avatar-3", avatarUrl: null }; // fallback
    }).filter(Boolean);

    console.log("Task:", task);
    console.log("assignedTo: " + task.assignedTo);

    console.log("Assignees", assignees);
    const assigneesHtml = assignees.map((user, index) => {
        if (index >= 3) return '';

        if (user.avatarUrl) {
            return `
      <span class="avatar" title="${user.name}">
        <img class="avatar-img" src="${user.avatarUrl}" alt="${user.name}">
      </span>
    `;
        }

        return `<span class="avatar ${user.avatarClass}" title="${user.name}"></span>`;
    }).join('');


    const moreAssignees = assignees.length > 3 ? `<span class="avatar-more">+${assignees.length - 3}</span>` : '';

    // Capitalize priority
    const priorityText = task.priority.charAt(0).toUpperCase() + task.priority.slice(1);

 return `
  <div class="task-card" data-task-id="${task.id}" ${isDraggable ? 'draggable="true"' : ''}>
    <span class="priority ${task.priority}">${priorityText}</span>
    <h3 class="task-title">${task.title}</h3>
    ${task.description ? `<p class="task-desc">${task.description}</p>` : ''}

    ${isManagerView ? renderStatusPill(task) : ""}

    <div class="task-assignees">
      ${assigneesHtml}
      ${moreAssignees}
    </div>
  </div>
`;


}

function getEffectiveRole(currentUser) {
    return String(window.__ROLE__ || currentUser?.role || "team_member").toLowerCase();
}

function normalizeDbStatus(status) {
    const map = {
        to_do: 'todo',
        in_progress: 'inprogress',
        review: 'review',
        completed: 'completed'
    };
    return map[status] || 'todo';
}

function denormalizeStatus(uiStatus) {
    const map = {
        todo: "to_do",
        inprogress: "in_progress",
        review: "review",
        completed: "completed",
    };
    return map[uiStatus] || "to_do";
}

/**
 * Renders all tasks onto the project board
 */
function renderTaskBoard(currentUser, currentProjectId) {
    updateAddTaskButtonsVisibility();

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
    const role = getEffectiveRole(currentUser);
    const isManager = (role === "manager");
    const canManageProject = !!window.__CAN_MANAGE_PROJECT__;
    const isManagerView = isManager || canManageProject;

    const isLeaderOnApollo = (currentUser.email === 'leader@make-it-all.co.uk' && currentProjectId === 'apollo');

    // Prefer the normalized array if it exists (because we mutate it during drag/drop)
    let tasksToRender = Array.isArray(window.__TASKS_NORM__) && window.__TASKS_NORM__.length
        ? window.__TASKS_NORM__
        : [];
    console.log("task lists:", document.querySelectorAll(".task-list").length);

    // If no normalized tasks yet, build them from DB-injected tasks
    if (!tasksToRender.length) {
        const dbTasks = Array.isArray(window.__TASKS__) ? window.__TASKS__ : [];
        tasksToRender = dbTasks.map(t => ({
            // normalize shape so your UI keeps working
            id: t.task_id,
            title: t.task_name,
            description: t.description || t.task_description || "",
            priority: t.priority || 'medium',
            status: normalizeDbStatus(t.status),
            assignedTo: Array.isArray(t.assignedUsers)
                ? t.assignedUsers.map(u => u.email)
                : (Array.isArray(t.assignedTo) ? t.assignedTo : []),
            project: window.__PROJECT__?.project_name || '',
            projectId: currentProjectId,
            createdDate: t.created_date,
            deadline: t.deadline,
            created_by: t.created_by
        }));
    }
    window.__TASKS_NORM__ = tasksToRender;

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
        setupBoardDnDOnce(currentUser, currentProjectId);
    }
    setupStatusPillActions(currentUser, currentProjectId);


    // Initialize task detail click listeners for everyone
    initTaskDetailsModal(currentUser);

    feather.replace();
}

async function updateTaskStatusInDb(taskId, newStatus) {
    const res = await fetch(`projects.php?project_id=${encodeURIComponent(getCurrentProjectId())}`, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
            ajax: "update_task_status",
            task_id: taskId,
            new_status: newStatus
        })
    });

    const raw = await res.text(); // helpful for debugging
    let data;
    try { data = JSON.parse(raw); } catch { throw new Error("Server did not return JSON: " + raw); }

    if (!res.ok || !data.success) {
        throw new Error(data.message || `Update failed (${res.status})`);
    }
    return data;
}
async function updateTaskPriorityInDb(taskId, priority) {
    const res = await fetch(
        `projects.php?project_id=${encodeURIComponent(getCurrentProjectId())}`,
        {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: new URLSearchParams({
                ajax: "update_task_priority",
                task_id: taskId,
                priority: priority
            })
        }
    );

    const text = await res.text();
    let data;

    try {
        data = JSON.parse(text);
    } catch {
        throw new Error("Invalid JSON response: " + text);
    }

    if (!res.ok || !data.success) {
        throw new Error(data.message || "Priority update failed");
    }

    return data;
}


async function deleteTaskInDb(taskId) {
    const pid = getCurrentProjectId();

    const res = await fetch(`projects.php?project_id=${encodeURIComponent(pid)}`, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
            ajax: "delete_task",
            task_id: taskId
        })
    });

    const raw = await res.text();
    let data;
    try { data = JSON.parse(raw); }
    catch { throw new Error("Server did not return JSON: " + raw); }

    if (!res.ok || !data.success) {
        throw new Error(data.message || `Delete failed (${res.status})`);
    }

    return data;
}

function setupStatusPillActions(currentUser, currentProjectId) {

   document.addEventListener("click", (e) => {
    const pill = e.target.closest(".status-pill");
    if (!pill) return;

    e.preventDefault();
    e.stopPropagation();

    if (!window.__CAN_MANAGE_PROJECT__) return;

    // close all others
    document.querySelectorAll(".status-dropdown").forEach(m => m.hidden = true);
    document.querySelectorAll(".task-card").forEach(c => c.classList.remove("menu-open"));

    const menu = pill.nextElementSibling;
    const card = pill.closest(".task-card");

    if (menu && card) {
        menu.hidden = false;
        card.classList.add("menu-open");
    }
});


    // Handle move (CLICK OPTION)
    document.addEventListener("click", async (e) => {
    const option = e.target.closest(".status-dropdown button");
    if (!option) return;

    e.preventDefault();
    e.stopPropagation();

    if (!window.__CAN_MANAGE_PROJECT__) return;

    const wrapper = option.closest(".task-status-menu");
    const taskId = wrapper.dataset.taskId;
    const action = option.dataset.action;
    const value = option.dataset.value;

    const tasks = window.__TASKS_NORM__ || [];
    const task = tasks.find(t => String(t.id) === String(taskId));
    if (!task) return;

    if (action === "status" && task.status !== value) {
        const old = task.status;
        task.status = value;

        try {
            await updateTaskStatusInDb(task.id, denormalizeStatus(value));
        } catch {
            task.status = old;
            alert("Could not change status");
            return;
        }
    }

    if (action === "priority" && task.priority !== value) {
        const old = task.priority;
        task.priority = value;

        try {
            await updateTaskPriorityInDb(task.id, value);
        } catch {
            task.priority = old;
            alert("Could not change priority");
            return;
        }
    }

    renderTaskBoard(currentUser, currentProjectId);
});


   document.addEventListener("click", (e) => {
    if (e.target.closest(".task-status-menu")) return;

    document.querySelectorAll(".status-dropdown").forEach(m => m.hidden = true);
    document.querySelectorAll(".task-card").forEach(c => c.classList.remove("menu-open"));
});

}



/**
 * Initializes drag and drop functionality for the board
 */
function setupBoardDnDOnce(currentUser, currentProjectId) {
    const board = document.querySelector(".task-board");
    if (!board) return;

    // Prevent double-binding
    if (board.dataset.dndBound === "1") return;
    board.dataset.dndBound = "1";

    // 1) dragstart/dragend using event delegation
    board.addEventListener("dragstart", (e) => {
        const card = e.target.closest('.task-card[draggable="true"]');
        if (!card) return;

        e.dataTransfer.setData("text/plain", card.dataset.taskId);
        e.dataTransfer.effectAllowed = "move";
        setTimeout(() => card.classList.add("dragging"), 0);
    });

    board.addEventListener("dragend", (e) => {
        const card = e.target.closest(".task-card");
        if (card) card.classList.remove("dragging");

        board.querySelectorAll(".task-list.drag-over")
            .forEach((l) => l.classList.remove("drag-over"));
    });

    // 2) dragover/dragleave/drop delegation for columns/lists
    board.addEventListener("dragover", (e) => {
        const col = e.target.closest(".task-column");
        if (!col) return;

        e.preventDefault(); // required
        e.dataTransfer.dropEffect = "move";

        const list = col.querySelector(".task-list");
        if (list) list.classList.add("drag-over");
    });

    board.addEventListener("dragleave", (e) => {
        const col = e.target.closest(".task-column");
        if (!col) return;

        const related = e.relatedTarget;
        // if still inside the same column, ignore
        if (related && col.contains(related)) return;

        const list = col.querySelector(".task-list");
        if (list) list.classList.remove("drag-over");
    });

    board.addEventListener("drop", async (e) => {
        const col = e.target.closest(".task-column");
        if (!col) return;

        e.preventDefault();

        const list = col.querySelector(".task-list");
        if (list) list.classList.remove("drag-over");

        const taskId = e.dataTransfer.getData("text/plain");
        const newStatus = col.dataset.status;

        const tasksNorm = window.__TASKS_NORM__ || [];
        const normTask = tasksNorm.find(t => String(t.id) === String(taskId));
        if (!normTask) return;

        const oldStatus = normTask.status;
        if (oldStatus === newStatus) return;

        // optimistic update
        normTask.status = newStatus;

        // keep __TASKS__ in sync too (so refresh/rerender doesn't snap back)
        if (Array.isArray(window.__TASKS__)) {
            const dbTask = window.__TASKS__.find(t => String(t.task_id) === String(taskId));
            if (dbTask) dbTask.status = denormalizeStatus(newStatus);
        }

        try {
            await updateTaskStatusInDb(taskId, denormalizeStatus(newStatus));
        } catch (err) {
            console.error(err);
            normTask.status = oldStatus; // revert
            alert("Could not save change. Please try again.");
        }

        // rerender after drop
        renderTaskBoard(currentUser, currentProjectId);
    });
}


/**
 * NEW: Initializes click listeners for task cards to show details
 */
function initTaskDetailsModal(currentUser) {
    const detailsModal = document.getElementById("task-details-modal");
    const detailsCloseBtn = document.getElementById("details-close-modal-btn");

    if (!detailsModal || !detailsCloseBtn) return;

    // Prevent multiple bindings
    if (detailsModal.dataset.bound === "1") return;
    detailsModal.dataset.bound = "1";

    const closeModal = () => {
        detailsModal.style.display = "none";
        document.body.style.overflow = "";
    };

    detailsCloseBtn.addEventListener("click", closeModal);

    detailsModal.addEventListener("click", (e) => {
        if (e.target === detailsModal) closeModal();
    });

    // ✅ Event delegation for clicking a task card
    document.addEventListener("click", (e) => {
        // If they clicked the 3-dot menu, do NOT open modal
        if (e.target.closest(".task-status-menu")) return;

        const card = e.target.closest(".task-card");
        if (!card) return;

        const taskId = card.dataset.taskId;
        const allTasks = Array.isArray(window.__TASKS_NORM__) ? window.__TASKS_NORM__ : [];
        const task = allTasks.find((t) => String(t.id) === String(taskId));
        if (!task) return;

        const usersObj = getUsersSource();

        // Fill modal fields
        document.getElementById("details-task-title").textContent = task.title || "";
        document.getElementById("details-task-project").textContent = task.project || "";
        document.getElementById("details-task-description").textContent =
            task.description || "No description provided.";

        // Priority badge
        const prEl = document.getElementById("details-task-priority");
        if (prEl) {
            prEl.textContent = task.priority || "";
            prEl.className = `priority-badge ${task.priority || "medium"}`;
        }

        // Dates (safe formatting)
        const createdDateEl = document.getElementById("details-task-created");
        const deadlineDateEl = document.getElementById("details-task-deadline");

        const createdDate = task.createdDate ? new Date(task.createdDate) : null;
        const deadlineDate = task.deadline ? new Date(task.deadline) : null;

        if (createdDateEl) {
            createdDateEl.textContent = createdDate
                ? createdDate.toLocaleDateString("en-GB", { day: "numeric", month: "long", year: "numeric" })
                : "N/A";
        }

        if (deadlineDateEl) {
            deadlineDateEl.textContent = deadlineDate
                ? deadlineDate.toLocaleDateString("en-GB", { day: "numeric", month: "long", year: "numeric" })
                : "No deadline";
        }

        // Assignees
        const assigneesEl = document.getElementById("details-task-assignees");
        if (assigneesEl) {
            if (task.assignedTo && task.assignedTo.length > 0) {
                assigneesEl.textContent = task.assignedTo
                    .map((email) => usersObj[email]?.name || email)
                    .join(", ");
            } else {
                assigneesEl.textContent = "Unassigned";
            }
        }

        // Buttons
        const markBtn = document.getElementById("project-complete-btn");
        const deleteBtn = document.getElementById("delete-task-btn");

        const role = getEffectiveRole(currentUser);
        const canManageProject = !!window.__CAN_MANAGE_PROJECT__;
        const isManagerLike = role === "manager" || canManageProject;

        // Team members: show mark complete. Managers/leaders: show delete.
        if (markBtn) markBtn.style.display = isManagerLike ? "none" : "inline-flex";
        if (deleteBtn) deleteBtn.style.display = isManagerLike ? "inline-flex" : "none";

        // Rebind delete button safely
        if (deleteBtn) {
            const freshDeleteBtn = deleteBtn.cloneNode(true);
            deleteBtn.parentNode.replaceChild(freshDeleteBtn, deleteBtn);

            freshDeleteBtn.addEventListener("click", async () => {
                const ok = confirm("Are you sure you want to delete this task? This cannot be undone.");
                if (!ok) return;

                try {
                    await deleteTaskInDb(task.id);

                    // remove locally too
                    if (Array.isArray(window.__TASKS_NORM__)) {
                        window.__TASKS_NORM__ = window.__TASKS_NORM__.filter((t) => String(t.id) !== String(task.id));
                    }
                    if (Array.isArray(window.__TASKS__)) {
                        window.__TASKS__ = window.__TASKS__.filter((t) => String(t.task_id) !== String(task.id));
                    }

                    closeModal();
                    showSuccessNotification("Task deleted successfully!");
                    renderTaskBoard(currentUser, getCurrentProjectId());
                } catch (err) {
                    console.error(err);
                    alert("Could not delete task. Check console.");
                }
            });
        }

        // Rebind mark complete button safely
        if (markBtn) {
            const freshMarkBtn = markBtn.cloneNode(true);
            markBtn.parentNode.replaceChild(freshMarkBtn, markBtn);

            freshMarkBtn.addEventListener("click", async () => {
                const ok = confirm("Mark this task as complete? It will be moved to Review.");
                if (!ok) return;

                try {
                    const tasksNorm = window.__TASKS_NORM__ || [];
                    const t = tasksNorm.find((x) => String(x.id) === String(task.id));
                    if (t) t.status = "review";

                    if (Array.isArray(window.__TASKS__)) {
                        const dbTask = window.__TASKS__.find((x) => String(x.task_id) === String(task.id));
                        if (dbTask) dbTask.status = "review";
                    }

                    await updateTaskStatusInDb(task.id, denormalizeStatus("review"));

                    closeModal();
                    showSuccessNotification("Task sent to Review!");
                    renderTaskBoard(currentUser, getCurrentProjectId());
                } catch (err) {
                    console.error(err);
                    alert("Could not update task. Check console.");
                }
            });
        }

        // Show modal
        detailsModal.style.display = "flex";
        document.body.style.overflow = "hidden";

        if (window.feather) feather.replace();
    });
}

// =============================
// Close Project (modal + DB update)
// Works on BOTH projects.php and manager-progress.php
// =============================
function setupCloseProjectControls() {
    const closeProjectBtn = document.getElementById("close-project-btn");
    const closeProjectModal = document.getElementById("close-project-modal");
    const closeProjectOk = document.getElementById("close-project-ok");
    const closeProjectCancel = document.getElementById("close-project-cancel");
    const closeProjectX = document.getElementById("close-project-x");

    // If this page doesn't have the button/modal, just skip
    if (!closeProjectBtn || !closeProjectModal || !closeProjectOk) return;

    // Prevent double-binding if the function runs twice
    if (closeProjectModal.dataset.bound === "1") return;
    closeProjectModal.dataset.bound = "1";

    function openModal() {
        closeProjectModal.style.display = "flex";
        if (window.feather) feather.replace();
    }

    function closeModal() {
        closeProjectModal.style.display = "none";
    }

    closeProjectBtn.addEventListener("click", (e) => {
        e.preventDefault();
        openModal();
    });

    closeProjectCancel?.addEventListener("click", closeModal);
    closeProjectX?.addEventListener("click", closeModal);

    closeProjectModal.addEventListener("click", (e) => {
        if (e.target === closeProjectModal) closeModal();
    });

    closeProjectOk.addEventListener("click", async () => {
        try {
            const pid = getCurrentProjectId();
            if (!pid) throw new Error("Missing project_id in URL");

            // ✅ POST BACK TO THE CURRENT PAGE (so manager-progress.php handler runs)
            const url = `${window.location.pathname}?project_id=${encodeURIComponent(pid)}`;

            const res = await fetch(url, {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams({ ajax: "close_project" })
            });

            const raw = await res.text();
            let data;
            try { data = JSON.parse(raw); }
            catch { throw new Error("Server did not return JSON: " + raw); }

            if (!res.ok || !data.success) throw new Error(data.message || "Close project failed");

            // success -> go overview
            window.location.href = "projects-overview.php";
        } catch (err) {
            console.error(err);
            alert("Could not close project. Check console.");
        }
    });
}

/**
 * *** COMPLETELY REVISED FUNCTION ***
 * Runs on the Projects page (projects.html)
 */
function loadProjectsPage(currentUser) {
    const currentProjectId = getCurrentProjectId();
    updateSidebarAndNav();
    setupCloseProjectControls();


    const role = getEffectiveRole(currentUser);
    const canManageProject = !!window.__CAN_MANAGE_PROJECT__;
    const showManagerControls = (role === "manager") || canManageProject;


    // -----------------------------
    // Modal elements
    // -----------------------------
    const modal = document.getElementById("assign-task-modal");
    const closeModalBtn = document.getElementById("close-modal-btn");
    const modalForm = document.getElementById("modal-assign-task-form");
    const modalStatusInput = document.getElementById("modal-task-status");
    const modalProjectSelect = document.getElementById("modal-task-project");
    const modalAssigneeList = document.getElementById("modal-task-assignees");

    let openModal = (status) => {
        if (!modal) return;
        if (modalStatusInput) modalStatusInput.value = status || "todo";
        if (modalProjectSelect) modalProjectSelect.value = currentProjectId || "";
        modal.style.display = "flex";
        setMinDeadlineToday();
        feather.replace();
    };

    function setMinDeadlineToday() {
        const deadlineInput = document.getElementById("modal-task-deadline");
        if (!deadlineInput) return;

        // today in YYYY-MM-DD (matches <input type="date"> format)
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, "0");
        const dd = String(today.getDate()).padStart(2, "0");
        const minDate = `${yyyy}-${mm}-${dd}`;

        deadlineInput.min = minDate;

        // optional: if the input already has a past value, clear it
        if (deadlineInput.value && deadlineInput.value < minDate) {
            deadlineInput.value = "";
        }
    }


    const closeModal = () => {
        if (!modal) return;
        modal.style.display = "none";
        if (modalForm) modalForm.reset();
    };

    if (modalProjectSelect) {
        const pid = currentProjectId || "";
        const pname = window.__PROJECT__?.project_name || "Current Project";

        modalProjectSelect.innerHTML = pid
            ? `<option value="${pid}" selected>${pname}</option>`
            : `<option value="" selected>${pname}</option>`;
    }


    if (modalAssigneeList) {
        modalAssigneeList.innerHTML = "";

        // Prefer DB users injected by PHP
        const usersObj = window.__USERS__ || simUsers || {};

        Object.entries(usersObj).forEach(([email, user]) => {
            modalAssigneeList.insertAdjacentHTML(
                "beforeend"
                ,
                `
      <div class="assignee-checkbox-item">
        <input type="checkbox" id="assignee-${email}" value="${email}">
        <label for="assignee-${email}">${user.name} (${user.role})</label>
      </div>
      `
            );
        });
    }

    // --- Assignee search + selected count ---
    const assigneeSearch = document.getElementById("assignee-search");
    const selectedCountEl = document.getElementById("assignee-selected-count");

    function updateSelectedCount() {
        if (!selectedCountEl) return;
        const checked = document.querySelectorAll('#modal-task-assignees input[type="checkbox"]:checked');
        selectedCountEl.textContent = `Selected: ${checked.length}`;
    }

    function applyAssigneeFilter() {
        if (!assigneeSearch || !modalAssigneeList) return;

        const q = assigneeSearch.value.trim().toLowerCase();
        const items = modalAssigneeList.querySelectorAll(".assignee-checkbox-item");

        items.forEach((row) => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(q) ? "" : "none";
        });
    }

    if (assigneeSearch) {
        assigneeSearch.addEventListener("input", applyAssigneeFilter);
    }

    // update count whenever someone checks/unchecks
    if (modalAssigneeList) {
        modalAssigneeList.addEventListener("change", (e) => {
            if (e.target.matches('input[type="checkbox"]')) {
                updateSelectedCount();
            }
        });
    }

   


    // =============================
    // Close Project (modal + DB update)
    // =============================
    const closeProjectBtn = document.getElementById("close-project-btn");
    const closeProjectModal = document.getElementById("close-project-modal");
    const closeProjectOk = document.getElementById("close-project-ok");
    const closeProjectCancel = document.getElementById("close-project-cancel");
    const closeProjectX = document.getElementById("close-project-x");

    function openCloseProjectModal() {
        if (!closeProjectModal) return;
        closeProjectModal.style.display = "flex";
        if (window.feather) feather.replace();
    }

    function closeCloseProjectModal() {
        if (!closeProjectModal) return;
        closeProjectModal.style.display = "none";
    }

    // open modal when button clicked
    if (closeProjectBtn) {
        closeProjectBtn.addEventListener("click", (e) => {
            e.preventDefault();
            openCloseProjectModal();
        });
    }

    // close modal actions
    if (closeProjectCancel) closeProjectCancel.addEventListener("click", closeCloseProjectModal);
    if (closeProjectX) closeProjectX.addEventListener("click", closeCloseProjectModal);

    // click outside closes
    if (closeProjectModal) {
        closeProjectModal.addEventListener("click", (e) => {
            if (e.target === closeProjectModal) closeCloseProjectModal();
        });
    }

    // OK = update DB then redirect
    if (closeProjectOk) {
        closeProjectOk.addEventListener("click", async () => {
            try {
                const pid = getCurrentProjectId();
                const res = await fetch(`projects.php?project_id=${encodeURIComponent(pid)}`, {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: new URLSearchParams({ ajax: "close_project" })
                });

                const raw = await res.text();
                let data;
                try { data = JSON.parse(raw); }
                catch { throw new Error("Server did not return JSON: " + raw); }

                if (!res.ok || !data.success) throw new Error(data.message || "Close project failed");

                // success -> go overview
                window.location.href = "projects-overview.php";
            } catch (err) {
                console.error(err);
                alert("Could not close project. Check console.");
            }
        });
    }


    // -----------------------------
    // Modal close listeners
    // -----------------------------
    if (modal && closeModalBtn) {
        closeModalBtn.onclick = closeModal;
        modal.onclick = (e) => {
            if (e.target === modal) closeModal();
        };
    }

    // -----------------------------
    // Modal submit (DB create task)
    // -----------------------------
    if (modalForm && modalForm.dataset.bound !== "1") {
        modalForm.dataset.bound = "1";

        modalForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            e.stopPropagation();

            const title = document.getElementById("modal-task-title")?.value.trim();
            const deadline = document.getElementById("modal-task-deadline")?.value;
            const priority = document.getElementById("modal-task-priority")?.value || "medium";

            const assignees = Array.from(
                document.querySelectorAll("#modal-task-assignees input:checked")
            ).map(cb => cb.value);

            const rawStatus = document.getElementById("modal-task-status")?.value || "todo";

            const statusMap = {
                todo: "to_do",
                inprogress: "in_progress",
                review: "review",
                completed: "completed"
            };

            const status = statusMap[rawStatus] || "to_do";

            if (!title || !deadline || assignees.length === 0) {
                alert("Please fill out Title, Deadline and select at least 1 assignee.");
                return;
            }

            const fd = new FormData();
            fd.append("ajax", "create_task");
            fd.append("task_name", title);
            fd.append("deadline", deadline);
            fd.append("priority", priority);
            fd.append("status", status);
            fd.append("description", document.getElementById("modal-task-description")?.value.trim() || "");

            assignees.forEach(a => fd.append("assignees[]", a));

            const pid = getCurrentProjectId() || window.__PROJECT__?.project_id;

            const res = await fetch(
                `projects.php?project_id=${encodeURIComponent(pid)}`,
                { method: "POST", body: fd }
            );

            const data = await res.json();

            if (!data.success) {
                alert(data.message || "Create failed");
                return;
            }

            // Close modal + refresh board
            document.getElementById("assign-task-modal").style.display = "none";
            document.body.style.overflow = "";

            fetchAndRenderTasks();
        });
    }



    // Notifications (leave as-is)
    const showProjectNotification = sessionStorage.getItem("projectCreated");
    if (showProjectNotification) {
        showSuccessNotification(showProjectNotification);
        sessionStorage.removeItem("projectCreated");
    }

    feather.replace();

    // Initial render of task board
    renderTaskBoard(currentUser, currentProjectId);
    setupBoardDnDOnce(currentUser, currentProjectId);
    initTaskDetailsModal(currentUser);
    updateAddTaskButtonsVisibility();
}


function openAssignTaskModal(status = "todo") {
    const modal = document.getElementById("assign-task-modal");
    if (!modal) return;

    // status
    const statusInput = document.getElementById("modal-task-status");
    if (statusInput) statusInput.value = status;

    // project (lock to current project)
    const projectSelect = document.getElementById("modal-task-project");
    const pid = getCurrentProjectId();
    const pname = window.__PROJECT__?.project_name || "Current Project";

    if (projectSelect) {
        projectSelect.innerHTML = `<option value="${pid}" selected>${pname}</option>`;
    }

    // assignees
    const list = document.getElementById("modal-task-assignees");
    const countEl = document.getElementById("assignee-selected-count");
    if (list) {
        list.innerHTML = "";
        let count = 0;

        const users = window.__USERS__ || {};

        Object.entries(users).forEach(([email, user]) => {
            const row = document.createElement("div");
            row.className = "assignee-checkbox-item";

            row.innerHTML = `
                <input type="checkbox" value="${email}">
                <label>${user.name}</label>
            `;

            const cb = row.querySelector("input");
            cb.addEventListener("change", () => {
                count += cb.checked ? 1 : -1;
                if (countEl) countEl.textContent = `Selected: ${count}`;
            });

            list.appendChild(row);
        });

        if (countEl) countEl.textContent = "Selected: 0";
    }

    // deadline min = today
    const deadline = document.getElementById("modal-task-deadline");
    if (deadline) {
        deadline.min = new Date().toISOString().split("T")[0];
    }

    modal.style.display = "flex";
    document.body.style.overflow = "hidden";
    feather.replace();
}


function initManagerMemberProgressList() {
    const listEl = document.getElementById("member-progress-list");
    const searchEl = document.getElementById("member-progress-search");
    const hintEl = document.getElementById("member-progress-hint");
    if (!listEl || !searchEl) return;

    const pid = new URLSearchParams(window.location.search).get("project_id");
    if (!pid) return;

    let people = [];

    function render(filtered) {
        listEl.innerHTML = "";

        if (!filtered.length) {
            hintEl && (hintEl.style.display = "block");
            return;
        }
        hintEl && (hintEl.style.display = "none");

        filtered.forEach(p => {
            const row = document.createElement("div");
            row.className = "member-progress-row";

            const nameWrap = document.createElement("div");
            nameWrap.className = "member-name-wrap";

            const name = document.createElement("div");
            name.className = "member-name";
            name.textContent = p.name;

            const sub = document.createElement("div");
            sub.className = "member-sub";
            sub.textContent = `${p.completed_tasks}/${p.total_tasks} completed`;

            nameWrap.appendChild(name);
            nameWrap.appendChild(sub);

            const barWrap = document.createElement("div");
            barWrap.className = "member-bar-wrap";

            const bar = document.createElement("div");
            bar.className = "member-bar";

            const fill = document.createElement("div");
            fill.className = "member-bar-fill";
            fill.style.width = `${p.percent}%`;

            bar.appendChild(fill);

            const pct = document.createElement("div");
            pct.className = "member-percent";
            pct.textContent = `${p.percent}%`;

            barWrap.appendChild(bar);
            barWrap.appendChild(pct);

            row.appendChild(nameWrap);
            row.appendChild(barWrap);

            listEl.appendChild(row);

        });
    }

    fetch(`manager-progress.php?project_id=${encodeURIComponent(pid)}&ajax=member_progress`)
        .then(r => r.json())
        .then(data => {
            if (!data || !data.success) return;
            people = data.people || [];
            render(people);
        })
        .catch(() => { });

    searchEl.addEventListener("input", () => {
        const q = searchEl.value.trim().toLowerCase();
        if (!q) return render(people);

        const filtered = people.filter(p =>
            (p.name || "").toLowerCase().includes(q) ||
            (p.email || "").toLowerCase().includes(q)
        );
        render(filtered);
    });
}

/**
 * Runs on the Manager Progress page (manager-progress-page)
 */
async function loadManagerProgressPage(currentUser) {
    const currentProjectId = getCurrentProjectId();

    if (!currentProjectId) {
        alert('No project selected');
        window.location.href = 'projects-overview.php';
        return;
    }

    // --- Fetch project data from database ---
    try {
        const response = await fetch(`projects.php?ajax=get_project&project_id=${encodeURIComponent(currentProjectId)}`);
        const data = await response.json();

        if (data.success && data.project) {
            window.__PROJECT__ = data.project;
        } else {
            console.error('Failed to load project:', data.message);
            return;
        }
    } catch (err) {
        console.error('Error fetching project:', err);
        return;
    }

    updateSidebarAndNav();
    setupCloseProjectControls();

    const projectTasks = await fetchProjectTasksFromDb(currentProjectId);

    renderManagerDeadlines(projectTasks);
    initManagerMemberProgressList(); // renders the left list via ajax=member_progress

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

const usersMap = getUsersMap();
        const assignees = (task.assignedTo || [])
            .map(email => (usersMap[email]?.name || email).split(" ")[0])
            .join(", ");

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
 * Runs on the Project Resources page (project-resources.php)
 */
function loadProjectResourcesPage(currentUser) {
    const currentProjectId = getCurrentProjectId();
    updateSidebarAndNav();

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
        window.location.href = `home.php?user=${currentUser.email}`;
    });
}


function sortProjects() {
    const sortSelect = document.getElementById('sortProjects');
    const grid = document.querySelector('.projects-grid');

    // Safety check
    if (!sortSelect || !grid) return;

    const priorityRank = (p) => {
        // higher number = higher priority
        if (p === 'high') return 3;
        if (p === 'medium') return 2;
        if (p === 'low') return 1;
        return 0;
    };

    const getPriority = (card) =>
        String(card.dataset.priority || '').trim().toLowerCase();

    sortSelect.addEventListener('change', () => {
        const cards = Array.from(grid.querySelectorAll('.project-card'));
        const sortBy = sortSelect.value;

        cards.sort((a, b) => {
            if (sortBy === 'name') {
                return (a.dataset.name || '').localeCompare(b.dataset.name || '');
            }

            if (sortBy === 'progress') {
                return (Number(b.dataset.progress) || 0) - (Number(a.dataset.progress) || 0);
            }

            if (sortBy === 'due') {
                const aDate = a.dataset.deadline ? new Date(a.dataset.deadline) : new Date('9999-12-31');
                const bDate = b.dataset.deadline ? new Date(b.dataset.deadline) : new Date('9999-12-31');
                return aDate - bDate;
            }

            if (sortBy === 'priorityHigh') {
                return priorityRank(getPriority(b)) - priorityRank(getPriority(a));
            }

            if (sortBy === 'priorityLow') {
                return priorityRank(getPriority(a)) - priorityRank(getPriority(b));
            }
            return 0;
        });

        // Re-attach cards in new order
        cards.forEach(card => grid.appendChild(card));
    });
}


function archivedJump() {
    const btn = document.getElementById("jumpToArchived");
    const archived = document.getElementById("archived-section");
    if (!btn || !archived) return;

    btn.addEventListener("click", () => {
        archived.scrollIntoView({ behavior: "smooth", block: "start" });
    });

}

function setupArchivedToggle() {
    const btn = document.getElementById("jumpToArchived");
    const content = document.getElementById("archived-content");
    const section = document.getElementById("archived-section");

    if (!btn || !content || !section) return;

    btn.addEventListener("click", () => {
        const isHidden = content.classList.contains("is-hidden");

        content.classList.toggle("is-hidden");
        btn.classList.toggle("is-open", isHidden);

        if (isHidden) {
            section.scrollIntoView({ behavior: "smooth", block: "start" });
        }
    });
}

function setupProjectCardMenus() {
    // 1) Open/close the 3-dots menu

    document.addEventListener("click", (e) => {
        const menuBtn = e.target.closest(".card-menu-btn");
        const menuItem = e.target.closest(".card-menu-item");

        // If user clicked the 3 dots button
        if (menuBtn) {
            const card = menuBtn.closest(".project-card");
            const menu = card.querySelector(".card-menu-dropdown");

            // Close any other open menus first
            document.querySelectorAll(".card-menu-dropdown").forEach((m) => {
                if (m !== menu) m.hidden = true;
            });

            // Toggle this menu
            menu.hidden = !menu.hidden;
            return;
        }

        // If user clicked an option in the menu (Mark complete / Archive / Reinstate)
        if (menuItem) {
            const card = menuItem.closest(".project-card");
            const projectId = card.dataset.projectId; // from data-project-id
            const action = menuItem.dataset.action;   // "complete", "archive", "reinstate"

            // Close menu immediately
            const menu = card.querySelector(".card-menu-dropdown");
            if (menu) menu.hidden = true;

            // Send request to PHP (same page)
            runProjectAction(projectId, action, card);
            return;
        }

        // If user clicked anywhere else, close all menus
        document.querySelectorAll(".card-menu-dropdown").forEach((m) => (m.hidden = true));
    });
}

async function runProjectAction(projectId, action, cardEl) {
    try {
        const res = await fetch(window.location.href, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({ project_id: projectId, action }),
        });

        // Read ONCE as text so we can debug whatever the server returns
        const raw = await res.text();
        console.log("Status:", res.status);
        console.log("Raw response:", raw);

        // If server didn't return 2xx, show it
        if (!res.ok) {
            alert(`Server error ${res.status}. Check console.`);
            return;
        }

        // Try to parse JSON
        let data;
        try {
            data = JSON.parse(raw);
        } catch (e) {
            alert("Server did not return JSON. Check console for raw response.");
            return;
        }

        if (!data.success) {
            alert(data.message || "Action failed");
            return;
        }

        updateCardUIAfterAction(action, cardEl);

    } catch (err) {
        console.error(err);
        alert("Network/Server error");
    }
}

function renderCardMenu(cardEl, state) {
    const dropdown = cardEl.querySelector(".card-menu-dropdown");
    if (!dropdown) return;

    if (state === "active") {
        dropdown.innerHTML = `
      <button type="button" class="card-menu-item" data-action="complete">
        Mark as complete
      </button>
      <button type="button" class="card-menu-item" data-action="archive">
        Move to archives
      </button>
    `;
    } else if (state === "archived") {
        dropdown.innerHTML = `
      <button type="button" class="card-menu-item" data-action="reinstate">
        Reinstate
      </button>
    `;
    }

    dropdown.hidden = true; // always close after update
}


function updateArchivedEmptyState() {
    const archivedGrid = document.querySelector("#archived-section .projects-grid");
    if (!archivedGrid) return;

    const hasArchivedCards = archivedGrid.querySelector(".project-card") !== null;
    const existingEmpty = archivedGrid.querySelector(".empty-state");

    if (!hasArchivedCards) {
        if (!existingEmpty) {
            archivedGrid.insertAdjacentHTML("beforeend", `
        <div class="empty-state">
          <i data-feather="archive"></i>
          <p>No archived projects</p>
        </div>
      `);

            // Re-render feather icon inside the new empty state
            if (window.feather) feather.replace();
        }
    } else {
        existingEmpty?.remove();
    }
}

function restoreDeadlinePill(cardEl) {
    const text = cardEl.dataset.deadlineText || "";
    const cls = cardEl.dataset.deadlineClass || "days-pill";

    const pill = cardEl.querySelector(".days-pill");
    if (!pill) return;

    pill.className = cls;

    const span = pill.querySelector("span");
    if (span) span.textContent = text;
}



function updateCardUIAfterAction(action, cardEl) {
    const activeGrid = document.querySelector("#active-section .projects-grid");
    const archivedGrid = document.querySelector("#archived-section .projects-grid");

    // Helper: set progress to 100% visually
    function setProgressTo100(card) {
        const fill = card.querySelector(".progress-fill");
        const text = card.querySelector(".progress-text");
        if (fill) fill.style.width = "100%";
        if (text) text.textContent = "100% complete";

        // Also update dataset so sorting works later
        card.dataset.progress = "100";
    }

    // Helper: set status pill text
    function setPill(card, text, extraClass) {
        const pill = card.querySelector(".days-pill");
        if (!pill) return;

        pill.className = "days-pill"; // reset
        if (extraClass) pill.classList.add(extraClass);

        const span = pill.querySelector("span");
        if (span) span.textContent = text;
    }

    console.log("archivedGrid is", archivedGrid);

    if (action === "archive") {
        archivedGrid?.querySelector(".empty-state")?.remove();
        archivedGrid.appendChild(cardEl);

        cardEl.classList.add("project-card--archived");
        setPill(cardEl, "Archived");

        const archivedSection = document.getElementById("archived-section");
        if (archivedSection) archivedSection.style.display = "";

        renderCardMenu(cardEl, "archived");
        updateArchivedEmptyState();
    }


    if (action === "complete") {
        // Remove empty state if this is the first archived item
        archivedGrid?.querySelector(".empty-state")?.remove();

        // Move card to archived grid
        archivedGrid.appendChild(cardEl);

        // Mark card as archived + completed
        cardEl.classList.add("project-card--archived");

        // Set progress to 100%
        setProgressTo100(cardEl);

        // Update status pill
        setPill(cardEl, "Completed", "is-completed");

        // Make sure archived section is visible
        const archivedSection = document.getElementById("archived-section");
        if (archivedSection) archivedSection.style.display = "";

        // Switch menu to "Reinstate"
        renderCardMenu(cardEl, "archived");
        updateArchivedEmptyState();
    }


    if (action === "reinstate") {
        if (activeGrid) activeGrid.appendChild(cardEl);
        cardEl.classList.remove("project-card--archived");
        const originalText = cardEl.dataset.pillText || "Active";
        const originalClass = cardEl.dataset.pillClass || "days-pill";
        setPill(cardEl, originalText);
        cardEl.querySelector(".days-pill").className = originalClass;

        renderCardMenu(cardEl, "active");
        restoreDeadlinePill(cardEl);
        updateArchivedEmptyState();
    }

}

function setupLeaderAutocomplete({
    inputId,
    hiddenId,
    resultsId,
    endpointUrl,
    formId
}) {
    const input = document.getElementById(inputId);
    const hidden = document.getElementById(hiddenId);
    const resultsBox = document.getElementById(resultsId);
    const form = document.getElementById(formId);

    if (!input || !hidden || !resultsBox || !form) return;

    let timer = null;
    let activeIndex = -1;
    let items = [];

    function closeResults() {
        resultsBox.style.display = "none";
        resultsBox.innerHTML = "";
        activeIndex = -1;
        items = [];
    }

    function renderResults(list) {
        items = list;
        activeIndex = -1;

        if (!list.length) {
            resultsBox.style.display = "none";
            resultsBox.innerHTML = "";
            return;
        }

        resultsBox.style.display = "block";
        resultsBox.innerHTML = list
            .map((u, idx) => `
        <div class="autocomplete-item" data-idx="${idx}">
          ${escapeHtml(u.label)}
        </div>
      `)
            .join("");
    }

    function selectItem(idx) {
        const u = items[idx];
        if (!u) return;
        input.value = u.label;     // what user sees
        hidden.value = u.id;       // what gets submitted
        closeResults();
    }

    function escapeHtml(str) {
        return String(str)
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;")
            .replaceAll("'", "&#039;");
    }

    async function fetchResults(query) {
        const urlObj = new URL(endpointUrl, window.location.href);
        urlObj.searchParams.set("q", query);

        const res = await fetch(urlObj.toString(), { credentials: "same-origin" });
        if (!res.ok) return [];

        return await res.json();
    }


    input.addEventListener("input", () => {
        // user typed something new -> reset hidden until they pick from list
        hidden.value = "";

        const q = input.value.trim();
        if (q.length < 2) {
            closeResults();
            return;
        }

        clearTimeout(timer);
        timer = setTimeout(async () => {
            const data = await fetchResults(q);
            // if server returned error object, treat as empty
            if (!Array.isArray(data)) {
                closeResults();
                return;
            }
            renderResults(data);
        }, 250); // debounce
    });

    resultsBox.addEventListener("click", (e) => {
        const item = e.target.closest(".autocomplete-item");
        if (!item) return;
        selectItem(parseInt(item.dataset.idx, 10));
    });

    input.addEventListener("keydown", (e) => {
        if (!items.length) return;

        if (e.key === "ArrowDown") {
            e.preventDefault();
            activeIndex = Math.min(activeIndex + 1, items.length - 1);
        } else if (e.key === "ArrowUp") {
            e.preventDefault();
            activeIndex = Math.max(activeIndex - 1, 0);
        } else if (e.key === "Enter") {
            // Only select if dropdown is open and we have an active choice
            if (resultsBox.style.display === "block" && activeIndex >= 0) {
                e.preventDefault();
                selectItem(activeIndex);
            }
            return;
        } else if (e.key === "Escape") {
            closeResults();
            return;
        } else {
            return;
        }

        // highlight active
        const children = Array.from(resultsBox.querySelectorAll(".autocomplete-item"));
        children.forEach((el, i) => el.classList.toggle("active", i === activeIndex));
    });

    document.addEventListener("click", (e) => {
        if (!resultsBox.contains(e.target) && e.target !== input) closeResults();
    });

    form.addEventListener("submit", (e) => {
        if (!hidden.value) {
            e.preventDefault();
            alert("Please select a Team Leader from the suggestions.");
        }
    });
}

function setupProjectsOverviewSearch() {
    const input = document.getElementById("project-search");
    if (!input) return;

    // ACTIVE grid + cards
    const activeSection = document.getElementById("active-section");
    const activeGrid = activeSection?.querySelector(".projects-grid");
    const activeCards = activeGrid ? Array.from(activeGrid.querySelectorAll(".project-card")) : [];

    // ARCHIVED grid + cards (manager only: may not exist)
    const archivedContent = document.getElementById("archived-content"); // has is-hidden
    const archivedGrid = archivedContent?.querySelector(".projects-grid");
    const archivedCards = archivedGrid ? Array.from(archivedGrid.querySelectorAll(".project-card")) : [];

    // Toggle button (your arrow) — id is jumpToArchived
    const archiveToggleBtn = document.getElementById("jumpToArchived");

    // Remember default state (usually hidden)
    const archivedWasHiddenInitially = archivedContent
        ? archivedContent.classList.contains("is-hidden")
        : true;

    // Helper: make an empty-state box that looks like your existing ones
    function ensureEmptyState(gridEl, id, iconName, message) {
        if (!gridEl) return null;

        let el = document.getElementById(id);
        if (!el) {
            el = document.createElement("div");
            el.id = id;
            el.className = "empty-state";
            el.style.display = "none";
            el.innerHTML = `
        <i data-feather="${iconName}"></i>
        <p>${message}</p>
      `;
            gridEl.appendChild(el);

            // Make feather icons render for newly added elements
            if (window.feather) feather.replace();
        }
        return el;
    }

    const activeNoMatch = ensureEmptyState(
        activeGrid,
        "active-no-match",
        "search",
        "Sorry, no active projects match your search."
    );

    const archivedNoMatch = ensureEmptyState(
        archivedGrid,
        "archived-no-match",
        "search",
        "Sorry, no archived projects match your search."
    );

    // Helper: show/hide cards by query
    function filterCards(cards, queryLower) {
        let matches = 0;
        for (const card of cards) {
            const name = (card.dataset.name || "").toLowerCase(); // you already set data-name
            const ok = name.includes(queryLower);
            card.style.display = ok ? "" : "none";
            if (ok) matches++;
        }
        return matches;
    }

    function showAll(cards) {
        cards.forEach((c) => (c.style.display = ""));
    }

    function openArchived() {
        if (!archivedContent) return;
        archivedContent.classList.remove("is-hidden");
        if (archiveToggleBtn) archiveToggleBtn.classList.add("is-open");
    }

    function restoreArchivedDefault() {
        if (!archivedContent) return;
        if (archivedWasHiddenInitially) {
            archivedContent.classList.add("is-hidden");
            if (archiveToggleBtn) archiveToggleBtn.classList.remove("is-open");
        }
    }

    function resetSearchUI() {
        // reset cards
        showAll(activeCards);
        showAll(archivedCards);

        // hide messages
        if (activeNoMatch) activeNoMatch.style.display = "none";
        if (archivedNoMatch) archivedNoMatch.style.display = "none";

        // restore archived default (hidden)
        restoreArchivedDefault();
    }

    // While typing: always open archived + filter both
    input.addEventListener("input", () => {
        const q = input.value.trim().toLowerCase();

        // If empty, reset
        if (q === "") {
            resetSearchUI();
            return;
        }

        // Always show archived section while searching (your requirement)
        openArchived();

        // Filter
        const activeMatches = filterCards(activeCards, q);
        const archivedMatches = filterCards(archivedCards, q);

        // Show "no matches" message INSIDE each section grid
        if (activeNoMatch) activeNoMatch.style.display = activeMatches === 0 ? "" : "none";
        if (archivedNoMatch) archivedNoMatch.style.display = archivedMatches === 0 ? "" : "none";
    });

    // When they click out (blur): clear text + reset to normal
    input.addEventListener("blur", () => {
        if (input.value.trim() !== "") {
            input.value = "";
            resetSearchUI();
        }
    });
}
function setupProjectCardNavigation() {
    document.addEventListener("click", (e) => {
        // Don’t navigate if they clicked menu / 3-dot / dropdown
        if (e.target.closest(".card-menu, .card-menu-btn, .card-menu-dropdown")) return;

        const card = e.target.closest(".project-card");
        if (!card) return;

        const projectId = card.dataset.projectId;
        if (!projectId) return;

            // 🔍 DEBUG – BEFORE navigation
        console.log("Navigating to", projectId);

        // 🔍 DEBUG – AFTER navigation attempt
        setTimeout(() => console.log("Still here"), 0);

        // Go to DB-backed project page
         sessionStorage.setItem("currentProjectName", card.dataset.name || "Project");
        window.location.href = `projects.php?project_id=${encodeURIComponent(projectId)}`;
    });
}

document.addEventListener("click", (e) => {
    const addBtn = e.target.closest(".add-task");
    if (!addBtn) return;

    if (!window.__CAN_MANAGE_PROJECT__) return;

    e.preventDefault();

    const column = addBtn.closest(".task-column");
    const status = column?.dataset.status || "todo";

    openAssignTaskModal(status);
});
document.addEventListener("click", (e) => {
    if (e.target.closest("#close-modal-btn")) {
        const modal = document.getElementById("assign-task-modal");
        modal.style.display = "none";
        document.body.style.overflow = "";
    }

    if (e.target.id === "assign-task-modal") {
        e.target.style.display = "none";
        document.body.style.overflow = "";
    }
});


// ===============================================
// === DOCUMENT LOAD =============================
// ===============================================

document.addEventListener('DOMContentLoaded', async () => {

    // Get the "logged in" user
    const currentUser = await getCurrentUser();
    if (!currentUser) return;

    // Store user in window object for floating widget
    window.__USER__ = currentUser;
    window.__CURRENT_USER__ = currentUser; // Also set for task board rendering

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
    } else if (pageId === "projects-overview-page") {
        sortProjects();
        archivedJump();
        setupArchivedToggle();
        setupProjectCardMenus();
        setupProjectsOverviewSearch();
        setupProjectCardNavigation();
    }
    fetchAndRenderTasks();
    setupAssignTaskForm();
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


// =============================
// TASK SEARCH (AJAX)
// =============================
(function () {
    const searchInput = document.getElementById("task-search-input");
    const statusFilter = document.getElementById("filter-status");
    const priorityFilter = document.getElementById("filter-priority");
    const dueFilter = document.getElementById("filter-due");


    if (!searchInput) return;

    let searchTimeout = null;

        function applyFilters() {
        fetchAndRenderTasks({
            search: searchInput.value.trim(),
            status: statusFilter?.value || "",
            priority: priorityFilter?.value || "",
            due: dueFilter?.value || "",
            page: 1
        });
    }


    searchInput.addEventListener("input", () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 300);
    });

    statusFilter?.addEventListener("change", applyFilters);
    priorityFilter?.addEventListener("change", applyFilters);
    dueFilter?.addEventListener("change", applyFilters);

})();

(function () {
    const filterToggle = document.getElementById('filter-toggle');
    const filterPanel = document.getElementById('filter-panel');

    if (!filterToggle || !filterPanel) return;

    filterToggle.addEventListener('click', () => {
        filterPanel.style.display =
            filterPanel.style.display === 'flex' ? 'none' : 'flex';
    });
})();
function matchesDueFilter(deadline, filter) {
    if (!filter) return true;           // no filter
    if (!deadline) return false;        // no date → never matches

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const d = new Date(deadline);
    d.setHours(0, 0, 0, 0);

    const diffDays = Math.floor(
        (d - today) / (1000 * 60 * 60 * 24)
    );

    switch (filter) {
        case "overdue":
            return diffDays < 0;

        case "today":
            return diffDays === 0;

        case "week":
            return diffDays >= 0 && diffDays <= 7;

        case "month":
            return diffDays >= 0 && diffDays <= 30;

        default:
            return true;
    }
}

async function fetchProjectTasksFromDb(projectId) {
    const url = `projects.php?project_id=${encodeURIComponent(projectId)}&ajax=fetch_tasks`;
    const res = await fetch(url, { credentials: "include" });
    const data = await res.json();
    if (!data.success) return [];

    // data.tasks are DB shape -> normalize to your UI shape
    return (data.tasks || []).map(t => ({
        id: t.task_id,
        title: t.task_name,
        description: t.description || "",
        priority: t.priority || "medium",
        status: normalizeDbStatus(t.status),     // you already have this function
        deadline: t.deadline,
        assignedTo: Array.isArray(t.assignedUsers)
            ? t.assignedUsers.map(u => u.email)
            : []
    }));
}

function fetchAndRenderTasks({ search = "", status = "", priority = "", due = "", page = 1 } = {}) {
    const pid = getCurrentProjectId();

    // Always hit projects.php for task AJAX (works from ANY page)
    const url = new URL("projects.php", window.location.href);
    url.searchParams.set("project_id", pid || "");
    url.searchParams.set("ajax", "fetch_tasks");
    url.searchParams.set("search", search);
    url.searchParams.set("status", status);
    url.searchParams.set("priority", priority);
    url.searchParams.set("due", due);
    url.searchParams.set("page", page);

    fetch(url.toString(), { credentials: "include" })
        .then(res => res.json())
        .then(data => {
            if (!data.success) return;

            const dueValue = document.getElementById("filter-due")?.value || "";

            // keep your due filter logic
            window.__TASKS__ = (data.tasks || []).filter(task =>
                matchesDueFilter(task.deadline, dueValue)
            );

            window.__TASKS_NORM__ = []; // force rebuild from filtered list

            // If this page has a kanban board, rerender it
            if (document.querySelector(".task-board")) {
                clearTaskColumns();
                renderTaskBoard(window.__CURRENT_USER__, getCurrentProjectId());
                updateAddTaskButtonsVisibility();
                updateTaskCounts();
            }

            // If this page is manager-progress and you want charts to reflect filters:
            // rebuild normalized tasks and re-render manager widgets
            if (document.body?.id === "manager-progress-page") {
                const normalized = (window.__TASKS__ || []).map(t => ({
                    id: t.task_id,
                    title: t.task_name,
                    description: t.description || "",
                    priority: t.priority || "medium",
                    status: normalizeDbStatus(t.status),
                    deadline: t.deadline,
                    assignedTo: Array.isArray(t.assignedUsers) ? t.assignedUsers.map(u => u.email) : []
                }));

                // ✅ Only deadlines refresh from task filters
                renderManagerDeadlines(normalized);

                // Optional: if you want the Team Progress list to refresh after filtering,
                // you'd need to pass filters into ajax=member_progress too (not doing that now).
                if (window.feather) feather.replace();
            }

        })
        .catch(err => console.error("Task fetch error:", err));
}


function clearTaskColumns() {
    document.querySelectorAll(".task-column .task-list")
        .forEach(col => col.innerHTML = "");
}

function updateTaskCounts() {
    document.querySelectorAll(".task-column").forEach(col => {
        const count = col.querySelectorAll(".task-card").length;
        col.querySelector(".task-count").textContent = count;
    });
}
/*function renderTaskCard(task) {
    const statusMap = {
        "to_do": "todo",
        "in_progress": "inprogress",
        "review": "review",
        "completed": "completed"
    };

    const columnKey = statusMap[task.status];
    if (!columnKey) return;

    const column = document.querySelector(
        `.task-column[data-status="${columnKey}"] .task-list`
    );

    if (!column) return;

    const card = document.createElement("div");
    card.className = "task-card";
    card.dataset.taskId = task.task_id;

    card.innerHTML = `
        <span class="priority ${task.priority}">${task.priority.toUpperCase()}</span>
        <h4>${task.task_name}</h4>
        <p>${task.description || ""}</p>
    `;

    column.appendChild(card);
}*/
document.addEventListener("DOMContentLoaded", () => {
    const pageId = document.body?.id;

    // Call it on BOTH pages (only does board rerender if board exists)
    if (pageId === "projects-page" || pageId === "manager-progress-page") {
        fetchAndRenderTasks();
    }

    if (pageId === "projects-page") {
        setupAssignTaskForm();
    }
    if (document.body.id === "manager-progress-page") {
        initManagerMemberProgressList();
    }

});



/**
 * Helper: Get current user email from various sources
 */
function getCurrentUserEmail() {
  // Try from window object first (set by PHP)
  if (window.__USER__?.email) return window.__USER__.email;
  
  // Try from URL parameter
  const params = new URLSearchParams(window.location.search);
  if (params.has('user')) return params.get('user');
  
  // Default fallback (you might need to adjust this)
  return 'user@make-it-all.co.uk';
}

// =============================
// CLEAR FILTERS BUTTON (fixed)
// =============================
document.addEventListener("DOMContentLoaded", () => {
    const filterClearBtn = document.getElementById("filter-clear");
    const filterStatus = document.getElementById("filter-status");
    const filterPriority = document.getElementById("filter-priority");
    const filterDue = document.getElementById("filter-due");
    const panel = document.getElementById("filter-panel");
    const searchInput = document.getElementById("task-search-input");

    if (!filterClearBtn) return;

    filterClearBtn.addEventListener("click", () => {
        // Reset dropdowns
        if (filterStatus) filterStatus.value = "";
        if (filterPriority) filterPriority.value = "";
        if (filterDue) filterDue.value = "";

        // Re-fetch tasks with no filters (keep search text if you want)
        fetchAndRenderTasks({
            search: searchInput?.value.trim() || "",
            status: "",
            priority: "",
            due: "",
            page: 1
        });

        // Close filter panel (match your toggle logic)
        if (panel) panel.style.display = "none";
    });
});


