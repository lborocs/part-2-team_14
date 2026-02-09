/*
* Make-It-All Knowledge Base
* This file simulates a backend and user authentication for the prototype.
*/

/**
 * Shows a success notification message that auto-dismisses after 3 seconds
 * @param {string} message - The message to display
 */

const IS_ARCHIVED = !!window.__IS_ARCHIVED__;

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

// Default feather icon for known topics (fallback when DB icon is missing/corrupted)
const DEFAULT_TOPIC_ICONS = {
    'Technical Support': 'tool',
    'HR Policies': 'users',
    'Project Best Practices': 'bar-chart-2',
    'Development Guidelines': 'code',
};

function renderTopicIcon(iconValue, topicName) {
    const val = (iconValue || '').trim();

    // feather icon name (like "tool", "users", "code")
    const featherPattern = /^[a-z0-9-]+$/;
    if (val && featherPattern.test(val)) return `<i data-feather="${val}"></i>`;

    // emoji (non-ascii, not corrupted "?")
    if (val && val !== '?' && /[^\u0000-\u007F]/.test(val)) {
        return `<span class="topic-emoji">${val}</span>`;
    }

    // fallback: use default icon for known topics, or a generic icon
    const fallback = (topicName && DEFAULT_TOPIC_ICONS[topicName]) || 'help-circle';
    return `<i data-feather="${fallback}"></i>`;
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
let simTasks = JSON.parse(localStorage.getItem('simTasks')) || [];
if (!localStorage.getItem('simTasks')) {
    localStorage.setItem('simTasks', JSON.stringify([]));
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
        const actionsBase = window.__ACTIONS_BASE__ || '../../actions/';
        const response = await fetch(actionsBase + 'login_sync.php', {
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
        const membersActive = path.includes("project-members.php") ? "active" : "";

        navLinks.innerHTML = `
      <a href="projects.php?project_id=${encodeURIComponent(projectId)}" class="${tasksActive}">Tasks</a>
      <a href="${progressPage}?project_id=${encodeURIComponent(projectId)}" class="${progressActive}">Progress</a>
      <a href="project-resources.php?project_id=${encodeURIComponent(projectId)}" class="${resourcesActive}">Resources</a>
      <a href="project-members.php?project_id=${encodeURIComponent(projectId)}" class="${membersActive}">Members</a>
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
    const postLink = `knowledge-base-post.html?post_id=${post.id}`;
    const topicClass = post.topic.toLowerCase().split(' ')[0];

    const avatarSrc = post.profilePicture || '../../default-avatar.png';

    const solvedBadge = Number(post.is_solved) === 1
        ? `<span class="kb-solved-badge">Solved</span>`
        : "";

    return `
    <div class="post-card">
      <div class="post-card-header">
        <img class="post-card-avatar" src="${avatarSrc}" alt="${post.author}" onerror="this.src='../../default-avatar.png'">
        <div>
          <span class="post-card-author">${post.author}</span>
          <span class="post-card-date">${post.date}</span>
        </div>

        <span class="post-card-tag ${topicClass}">${post.topic}</span>
        ${solvedBadge}
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

let KB_ACTIVE_TOPIC = null;      // string or null
let KB_ACTIVE_POSTS = [];        // array of posts currently in that topic

/**
 * Switches the main KB page to show a specific topic.
 * @param {string} topicName - The name of the topic, e.g., "Software Issues".
 * @param {object} currentUser - The current user object.
 */
async function showTopicView(topicName, currentUser) {
    // tell CSS we’re in “topic page” mode
    document.body.dataset.topicsView = "topic";

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

    // 7. Fetch posts from DB for this topic
    const all = await fetchKbPostsFromDb("new", 50); // get latest + filter
    const topicPosts = all.filter(p => p.topic === topicName);

    KB_ACTIVE_TOPIC = topicName;
    KB_ACTIVE_POSTS = topicPosts;

    renderPostList(topicPosts, currentUser.email);

}

/**
 * Renders the topic cards into the grid for the main page ONLY (main topics + Add New Topic).
 * @param {boolean} showAll If true, render all topics (unused for main page now). If false, render main topics only.
 * @param {object} currentUser Current user (for click-through)
 */
async function renderTopicGrid(showAll, currentUser) {
    const grid = document.getElementById('topic-grid');
    if (!grid) return;

    await ensureKbTopicsLoaded();

    // Main KB page: ONLY show original topics (ones with icons)
    const publicTopics = KB_TOPICS.filter(t => String(t.is_public) === "1");

    // originals = ones that have a DB icon set OR a known default
    const originalTopics = publicTopics.filter(t =>
        (t.icon && t.icon.trim() !== "" && t.icon.trim() !== "?") || DEFAULT_TOPIC_ICONS[t.topic_name]
    );

    // main page shows originals only
    const topicsToShow = showAll ? publicTopics : originalTopics;


    const topicCardsHtml = topicsToShow.map(t => `
    <a href="#" class="topic-card" data-topic="${t.topic_name}">
      ${renderTopicIcon(t.icon, t.topic_name)}
      <span>${t.topic_name}</span>
    </a>
  `).join('');

    const addCardHtml = `
    <a href="knowledge-base-create-topic.html?user=${currentUser.email}" class="topic-card add-topic-card" id="add-topic-card">
      <i data-feather="plus"></i>
      <span>Add New Topic</span>
    </a>
  `;

    grid.innerHTML = topicCardsHtml + addCardHtml;

    grid.querySelectorAll('.topic-card:not(.add-topic-card)').forEach(card => {
        card.addEventListener('click', async (e) => {
            e.preventDefault();
            const topicName = card.dataset.topic;
            await showTopicView(topicName, currentUser);
        });
    });

    feather.replace();
}


/* ===========================
   KB DB BACKEND (PHP actions)
   =========================== */
const KB_ACTIONS_BASE = "actions";

// ===========================
// TOPICS (DB-driven)
// ===========================
let KB_TOPICS = [];                 // array of {topic_id, topic_name, description, icon, ...}
let TOPIC_ID_TO_NAME = {};          // { [id]: name }
let TOPIC_NAME_TO_ID = {};          // { [name]: id }
let TOPIC_NAME_TO_ICON = {};        // { [name]: icon }

async function fetchKbTopicsFromDb() {
    const res = await fetch(`${KB_ACTIONS_BASE}/fetch_topics.php`, { credentials: "include" });
    const data = await res.json();
    if (!data.success) return [];

    return data.topics || [];
}

async function createKbTopicInDb({ topic_name, description, icon }) {
    const fd = new FormData();
    fd.append("topic_name", topic_name);
    fd.append("description", description || "");
    fd.append("icon", icon || "");


    const res = await fetch(`${KB_ACTIONS_BASE}/create_topic.php`, {
        method: "POST",
        body: fd,
        credentials: "include"
    });

    return await res.json();
}


async function ensureKbTopicsLoaded() {
    if (KB_TOPICS.length) return;

    KB_TOPICS = await fetchKbTopicsFromDb();

    TOPIC_ID_TO_NAME = {};
    TOPIC_NAME_TO_ID = {};
    TOPIC_NAME_TO_ICON = {};

    KB_TOPICS.forEach(t => {
        TOPIC_ID_TO_NAME[t.topic_id] = t.topic_name;
        TOPIC_NAME_TO_ID[t.topic_name] = t.topic_id;
        TOPIC_NAME_TO_ICON[t.topic_name] = t.icon || "";

    });
}

function getTopicIdFromName(name) {
    return TOPIC_NAME_TO_ID[name] || 0;
}


// Format MySQL datetime -> "3 October 2025"
function formatKbMysqlDate(mysqlDate) {
    if (!mysqlDate) return "";
    const d = new Date(String(mysqlDate).replace(" ", "T"));
    return d.toLocaleDateString("en-GB", { day: "numeric", month: "long", year: "numeric" });
}

function mapDbPostToUiPost(p) {
    const topicName = p.topic_name || TOPIC_ID_TO_NAME[p.topic_id] || "General";

    return {
        id: p.post_id,
        topic: topicName,
        title: p.title,
        author: p.author_name || "Unknown",
        authorEmail: "",
        profilePicture: p.profile_picture || null,
        date: formatKbMysqlDate(p.created_at),
        content: p.snippet || "",
        fullContent: p.content || "",
        is_solved: Number(p.is_solved || 0),
        reactions: {
            up: p.view_count ?? 0,
            lightbulb: 0,
            comments: p.comment_count ?? 0
        },
        replies: []
    };
}


async function fetchKbPostsFromDb(type = "popular", limit = 20, search = "") {
    const url =
        `${KB_ACTIONS_BASE}/fetch_posts.php?type=${encodeURIComponent(type)}&limit=${encodeURIComponent(limit)}&search=${encodeURIComponent(search)}`;

    const res = await fetch(url, { credentials: "include" });
    const data = await res.json();
    if (!data.success) return [];
    return (data.posts || []).map(mapDbPostToUiPost);
}


async function createKbPostInDb({ title, topicName, details }) {
    const topicId = getTopicIdFromName(topicName);
    const fd = new FormData();
    fd.append("title", title);
    fd.append("topic_id", String(topicId));
    fd.append("content", details);

    const res = await fetch(`${KB_ACTIONS_BASE}/create_post.php`, {
        method: "POST",
        body: fd,
        credentials: "include"
    });

    const data = await res.json();
    return data; // {success, post_id} or {success:false,message}
}

async function loadKbIndex(currentUser) {
    // Make sure the Create Post button is visible
    const createBtn = document.getElementById('create-post-btn-topic');
    if (createBtn) createBtn.style.display = 'inline-flex';

    // Render topics (keep existing)
    document.body.dataset.topicsView = 'main';
    await renderTopicGrid(false, currentUser);


    // Update "View more topics" link
    const viewMoreLink = document.getElementById('view-more-topics');
    if (viewMoreLink) {
        viewMoreLink.setAttribute('href', 'all-topics.html');
    }

    // Load popular posts from DB
    const posts = await fetchKbPostsFromDb("popular", 20);
    renderPostList(posts, currentUser.email);

    KB_ACTIVE_TOPIC = null;
    KB_ACTIVE_POSTS = [];

    // Make Popular/New tabs actually fetch DB versions
    const tabsContainer = document.getElementById("post-tabs-container");
    if (tabsContainer) {
        const buttons = tabsContainer.querySelectorAll("button");
        const popularBtn = buttons[0];
        const newBtn = buttons[1];

        if (popularBtn && newBtn) {
            popularBtn.addEventListener("click", async () => {
                popularBtn.classList.add("active");
                newBtn.classList.remove("active");
                const p = await fetchKbPostsFromDb("popular", 20);
                renderPostList(p, currentUser.email);
            });

            newBtn.addEventListener("click", async () => {
                newBtn.classList.add("active");
                popularBtn.classList.remove("active");
                const n = await fetchKbPostsFromDb("new", 20);
                renderPostList(n, currentUser.email);
            });
        }
    }

    setupKbSearch(currentUser);

}

function setupKbSearch(currentUser) {
    const input = document.getElementById("kb-search-input");
    if (!input) return;

    const tabsContainer = document.getElementById("post-tabs-container");
    const buttons = tabsContainer ? tabsContainer.querySelectorAll("button") : [];
    const popularBtn = buttons[0];
    const newBtn = buttons[1];

    function getActiveType() {
        return newBtn && newBtn.classList.contains("active") ? "new" : "popular";
    }

    let t = null;

    async function runSearch() {
        const q = input.value.trim();

        // if search cleared, restore the current view
        if (q === "") {
            if (KB_ACTIVE_TOPIC) {
                renderPostList(KB_ACTIVE_POSTS, currentUser.email);
            } else {
                const type = getActiveType();
                const posts = await fetchKbPostsFromDb(type, 20, "");
                renderPostList(posts, currentUser.email);
            }
            return;
        }

        // if in a topic, filter locally (DON'T hit DB)
        if (KB_ACTIVE_TOPIC) {
            const qLower = q.toLowerCase();
            const filtered = KB_ACTIVE_POSTS.filter(p =>
                (p.title || "").toLowerCase().includes(qLower) ||
                (p.content || "").toLowerCase().includes(qLower) ||
                (p.author || "").toLowerCase().includes(qLower)
            );
            renderPostList(filtered, currentUser.email);
            return;
        }

        // normal global search (DB)
        const type = getActiveType();
        const posts = await fetchKbPostsFromDb(type, 20, q);
        renderPostList(posts, currentUser.email);
    }

    // prevent double-binding if loadKbIndex is called again
    if (input.dataset.bound === "1") return;
    input.dataset.bound = "1";

    input.addEventListener("input", () => {
        clearTimeout(t);
        t = setTimeout(runSearch, 250);
    });
}


/**
 * Runs on the Create Post page 
 */
async function setupCreateForm(currentUser) {
    const form = document.getElementById("create-post-form");

    // NEW elements (searchable)
    const topicInput = document.getElementById("post-topic-input");   // visible
    const topicsList = document.getElementById("topics-datalist");    // datalist
    const topicHidden = document.getElementById("post-topic");        // hidden (submitted)
    const topicError = document.getElementById("topic-error");        // optional msg

    if (!form || !topicInput || !topicsList || !topicHidden) return;

    await ensureKbTopicsLoaded();

    // only public topics
    const topicNames = KB_TOPICS
        .filter(t => String(t.is_public) === "1")
        .map(t => String(t.topic_name || "").trim())
        .filter(Boolean);

    // fill datalist
    topicsList.innerHTML = topicNames
        .map(name => `<option value="${escapeHtml(name)}"></option>`)
        .join("");

    // Preselect if topic passed in URL (?topic=...)
    const urlParams = new URLSearchParams(window.location.search);
    const preselectedTopic = urlParams.get("topic");
    if (preselectedTopic) {
        topicInput.value = preselectedTopic;
        topicHidden.value = topicNames.includes(preselectedTopic) ? preselectedTopic : "";
    }

    function syncTopic() {
        const v = String(topicInput.value || "").trim();
        const ok = topicNames.includes(v);

        topicHidden.value = ok ? v : "";

        if (topicError) {
            topicError.style.display = (v && !ok) ? "block" : "none";
        }
    }

    topicInput.addEventListener("input", syncTopic);
    topicInput.addEventListener("blur", syncTopic);

    // Handle form submission
    form.addEventListener("submit", async (e) => {
        e.preventDefault();

        const title = document.getElementById("post-title").value.trim();
        const topic = topicHidden.value;
        const details = document.getElementById("post-details").value.trim();

        if (!title || !topic || !details) {
            alert("Please fill out all required fields (and pick a valid topic).");
            return;
        }

        const result = await createKbPostInDb({ title, topicName: topic, details });

        if (!result.success) {
            alert(result.message || "Could not create post.");
            return;
        }

        sessionStorage.setItem("postCreated", `Post "${title}" created successfully!`);
        window.location.href = `knowledge-base.html?user=${currentUser.email}`;
    });
}

function escapeHtml(str) {
    return String(str)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
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
        endpointUrl: "create-project.php?ajax=leaders",
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

    // Change password
    document.getElementById('password-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const current_password = document.getElementById('current-password').value;
        const new_password = document.getElementById('new-password').value;
        const confirm_password = document.getElementById('confirm-password').value;

        const res = await fetch(`${window.__ACTIONS_BASE__}change_password.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                current_password,
                new_password,
                confirm_password
            })
        });

        const data = await res.json();
        alert(data.message);

        if (data.success) {
            e.target.reset();
        }
    });

    // Update notification preferences
    document.getElementById('notifications-form').addEventListener('submit', (e) => {
        e.preventDefault();
        alert('Notification preferences saved!');
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
async function loadAllTopicsPage(currentUser) {
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
        await ensureKbTopicsLoaded();
        const allTopics = KB_TOPICS
            .filter(t => String(t.is_public) === "1")
            .map(t => String(t.topic_name || "").trim())
            .filter(Boolean);

        function renderTopicsList(topicNames) {
        if (!listEl) return;

        if (!topicNames.length) {
            listEl.innerHTML = '<p>No topics found.</p>';
            return;
        }

        listEl.innerHTML = topicNames
            .map(t => `
            <div class="topic-row" data-topic="${escapeHtml(t)}">
                <span class="topic-name">${escapeHtml(t)}</span>
                <i data-feather="arrow-right"></i>
            </div>
            `)
            .join('');

        // Re-bind click handlers every re-render
        listEl.querySelectorAll('.topic-row').forEach((row) => {
            row.addEventListener('click', () => {
            const topicName = row.dataset.topic;
            sessionStorage.setItem('returnToTopic', topicName);
            window.location.href = `knowledge-base.html?user=${currentUser.email}`;
            });
        });

        feather.replace();
        }

        // initial render
        renderTopicsList(allTopics);

        // bind search
        setupAllTopicsSearch(allTopics, renderTopicsList);


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

function setupAllTopicsSearch(allTopics, renderFn) {
  const input = document.getElementById("kb-topics-search-input");
  if (!input) return;

  if (input.dataset.bound === "1") return;
  input.dataset.bound = "1";

  let t = null;

  input.addEventListener("input", () => {
    clearTimeout(t);
    t = setTimeout(() => {
      const q = String(input.value || "").trim().toLowerCase();

      if (!q) {
        renderFn(allTopics);
        return;
      }

      const filtered = allTopics.filter(name =>
        name.toLowerCase().includes(q)
      );

      renderFn(filtered);
    }, 200);
  });
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

async function renderTrendingPosts(currentUser) {
    const trendingPostsList = document.getElementById('trending-posts-list');
    if (!trendingPostsList) return;

    const posts = await fetchKbPostsFromDb("popular", 3); // DB
    trendingPostsList.innerHTML = posts.map(post => {
        const topicClass = post.topic.toLowerCase().split(' ')[0];
        return `
      <div class="trending-post">
        <div class="post-header">
          <div class="post-avatar ${post.avatarClass || 'avatar-3'}"></div>
          <div class="post-author-info">
            <p class="post-author-name">${post.author}</p>
            <span class="post-date">${post.date}</span>
          </div>
          <span class="post-tag ${topicClass}">${post.topic}</span>
        </div>
        <h3 class="post-title">${post.title}</h3>
        <p class="post-excerpt">${post.content.substring(0, 100)}...</p>
        <div class="post-stats">
          <span class="post-stat"><i data-feather="eye"></i> ${post.reactions.up}</span>
          <span class="post-stat"><i data-feather="message-circle"></i> ${post.reactions.comments}</span>
        </div>
      </div>
    `;
    }).join("");

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

    if (window.__CAN_MANAGE_PROJECT__) {
        window.location.href = `manager-progress.php?project_id=${encodeURIComponent(currentProjectId)}`;
        return;
    }

    updateSidebarAndNav();

    let projectTasks = [];
    try {
        projectTasks = await fetchProjectTasksFromDb(currentProjectId);
    } catch (err) {
        console.error("Failed to load tasks from DB:", err);
        projectTasks = [];
    }
    window.__TASKS_NORM__ = projectTasks;

    const userEmail = String(currentUser.email || "").toLowerCase();
    const userTasks = projectTasks.filter(t =>
        Array.isArray(t.assignedTo) &&
        t.assignedTo.map(e => String(e).toLowerCase()).includes(userEmail)
    );

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
    window.__TASKS_NORM__ = projectTasks;

    console.log('Tasks loaded for countdown:', projectTasks.length);
    console.log('Current user email:', currentUser?.email);
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

    if (window.initProgressWidgets) {
        window.initProgressWidgets();
    }
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

function setupCreateTopicForm(currentUser) {
    const createTopicForm = document.getElementById('create-topic-form');
    if (!createTopicForm) return;

    createTopicForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        await ensureKbTopicsLoaded();

        const topicName = document.getElementById('topic-name').value.trim();
        const topicDescription = document.getElementById('topic-description').value.trim();

        // If you have an icon input, use it. If not, default to "tag".
        const iconInput = document.getElementById('topic-icon');
        const topicIcon = iconInput ? iconInput.value.trim() : "";

        if (!topicName) {
            alert('Please enter a topic name.');
            return;
        }

        // duplicate check (case-insensitive) against DB-loaded topics
        const exists = KB_TOPICS.some(t => t.topic_name.toLowerCase() === topicName.toLowerCase());
        if (exists) {
            alert('A topic with that name already exists. Please choose a different name.');
            return;
        }

        const result = await createKbTopicInDb({
            topic_name: topicName,
            description: topicDescription,
            icon: topicIcon || ""
        });

        if (!result.success) {
            alert(result.message || "Could not create topic.");
            return;
        }

        // reset cache so homepage loads new topic instantly
        KB_TOPICS = [];
        sessionStorage.setItem('topicCreated', `Topic "${topicName}" created successfully!`);

        window.location.href = `knowledge-base.html?user=${currentUser.email}`;
    });
}

function setupAssignTaskForm() {
    const form = document.getElementById("assign-task-form");
    if (!form) return;

    // ✅ Prevent double-binding
    if (form.dataset.bound === "1") return;
    form.dataset.bound = "1";

    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        e.stopPropagation();

        const modal = document.getElementById("assign-task-modal");
        const isEditMode = modal?.dataset.editMode === "true";
        const editingTaskId = modal?.dataset.editingTaskId;

        const titleEl = document.getElementById("modal-task-title");
        const priorityEl = document.getElementById("modal-task-priority");
        const deadlineEl = document.getElementById("modal-task-deadline");
        const descriptionEl = document.getElementById("modal-task-description");
        const statusEl = document.getElementById("modal-task-status");

        if (!titleEl || !priorityEl || !deadlineEl) {
            console.error("Form fields not found");
            return;
        }

        const taskName = titleEl.value.trim();
        const priority = priorityEl.value;
        const deadline = deadlineEl.value;
        const description = descriptionEl?.value.trim() || "";
        const rawStatus = statusEl?.value || "todo";

        const assignees = Array.from(
            document.querySelectorAll('#modal-task-assignees input[type="checkbox"]:checked')
        ).map(cb => cb.value);

        if (!taskName || !deadline || assignees.length === 0) {
            alert("Please fill all required fields and select at least one assignee");
            return;
        }

        const statusMap = {
            todo: "to_do",
            inprogress: "in_progress",
            review: "review",
            completed: "completed"
        };

        const status = statusMap[rawStatus] || "to_do";

        const formData = new FormData();
        formData.append("task_name", taskName);
        formData.append("priority", priority);
        formData.append("deadline", deadline);
        formData.append("description", description);
        formData.append("status", status);
        assignees.forEach(a => formData.append("assignees[]", a));

        const pid = getCurrentProjectId() || window.__PROJECT__?.project_id;

        try {
            if (isEditMode && editingTaskId) {
                // ✨ UPDATE existing task
                formData.append("ajax", "update_task");
                formData.append("task_id", editingTaskId);

                const res = await fetch(
                    `projects.php?project_id=${encodeURIComponent(pid)}`,
                    { method: "POST", body: formData }
                );

                const data = await res.json();

                if (!data.success) {
                    alert(data.message || "Update failed");
                    return;
                }

                showSuccessNotification("Task updated successfully!");

            } else {
                // CREATE new task
                formData.append("ajax", "create_task");

                const res = await fetch(
                    `projects.php?project_id=${encodeURIComponent(pid)}`,
                    { method: "POST", body: formData }
                );

                const data = await res.json();

                if (!data.success) {
                    alert(data.message || "Create failed");
                    return;
                }

                showSuccessNotification("Task created successfully!");
            }

            // Close modal and reset
            closeTaskModal();

            // Refresh the board
            fetchAndRenderTasks();

        } catch (err) {
            console.error("Task submission error:", err);
            alert("An error occurred. Please try again.");
        }
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
        };

        return `
      <div class="task-status-menu" data-task-id="${task.id}">
        <button class="status-pill icon-only" aria-label="Task actions">
          <span class="ellipsis">⋯</span>
        </button>

        <div class="status-dropdown" hidden>
         

          <div class="dropdown-section">
            <div class="dropdown-label">STATUS</div>
            ${Object.entries(statuses)
                .filter(([k]) => k !== task.status)
                .map(([k, v]) =>
                    `<button data-action="status" data-value="${k}">${v}</button>`
                ).join("")}
          </div>

          <div class="dropdown-divider"></div>

          <div class="dropdown-section">
            <div class="dropdown-label">PRIORITY</div>
            ${Object.entries(priorities)
                .filter(([k]) => k !== task.priority)
                .map(([k, v]) =>
                    `<button data-action="priority" data-value="${k}">${v}</button>`
                ).join("")}
          </div>

          <div class="dropdown-divider"></div>

           <div class="dropdown-section">
            <button data-action="edit" class="dropdown-edit-btn">
              <i data-feather="edit-2"></i>
              Edit Task
            </button>
          </div>

          <div class="dropdown-divider"></div>

          <div class="dropdown-section">
            <button data-action="delete" class="dropdown-delete-btn">
              <i data-feather="trash-2"></i>
              Delete Task
            </button>
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

    const isDraggable = isManagerView && !isLeaderOnApollo && !IS_ARCHIVED;
    const showMoveBtn = isManagerView && !isLeaderOnApollo;

    const usersMap = getUsersMap();

    const assignees = (task.assignedTo || []).map(email => {
        const user = usersMap[email];
        return user
            ? { name: user.name, avatarClass: user.avatarClass, avatarUrl: user.avatarUrl }
            : { name: email, avatarClass: "avatar-3", avatarUrl: null };
    }).filter(Boolean);

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

    // Format deadline for display AND check if overdue
    let formattedDeadline = 'No deadline';
    let isOverdue = false;
    let dueDateClass = 'task-due-date';

    if (task.deadline) {
        const deadlineDate = new Date(task.deadline);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        deadlineDate.setHours(0, 0, 0, 0);

        // Check if overdue (and not completed)
        if (deadlineDate < today && task.status !== 'completed') {
            isOverdue = true;
            dueDateClass = 'task-due-date overdue';
        }

        formattedDeadline = deadlineDate.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
    }

    return `
  <div class="task-card" data-task-id="${task.id}" ${isDraggable ? 'draggable="true"' : ''}>
    <span class="priority ${task.priority}">${priorityText}</span>
    <h3 class="task-title">${task.title}</h3>

    ${(isManagerView && !IS_ARCHIVED) ? renderStatusPill(task) : ""}

    <div class="task-footer">
      <div class="${dueDateClass}">
        ${formattedDeadline}
      </div>

      <div class="task-assignees">
        ${assigneesHtml}
        ${moreAssignees}
      </div>
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

    const raw = await res.text();
    let data;
    try { data = JSON.parse(raw); }
    catch { throw new Error("Server did not return JSON: " + raw); }

    if (!res.ok || !data.success) {
        throw new Error(data.message || `Update failed (${res.status})`);
    }

    // ✅ Trigger UI refresh after successful update
    const currentUser = window.__CURRENT_USER__;
    const projectId = getCurrentProjectId();
    if (currentUser && projectId) {
        renderTaskBoard(currentUser, projectId);
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
    if (!pid) {
        throw new Error("No project ID available");
    }

    try {
        const res = await fetch(`projects.php?project_id=${encodeURIComponent(pid)}`, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                ajax: "delete_task",
                task_id: taskId
            })
        });

        // Check if response is ok first
        if (!res.ok) {
            throw new Error(`Server error: ${res.status}`);
        }

        const raw = await res.text();

        // Check if response is empty or not JSON
        if (!raw || raw.trim() === '') {
            // If empty response but status was ok, consider it success
            return { success: true };
        }

        let data;
        try {
            data = JSON.parse(raw);
        } catch (parseError) {
            console.error("JSON parse error. Raw response:", raw);
            // If we can't parse but got 200 OK, assume success
            if (res.status === 200) {
                return { success: true };
            }
            throw new Error("Server did not return valid JSON: " + raw.substring(0, 100));
        }

        if (!data.success) {
            throw new Error(data.message || `Delete failed (${res.status})`);
        }

        return data;

    } catch (err) {
        console.error("Delete task error:", err);
        throw err;
    }
}

function setupStatusPillActions(currentUser, currentProjectId) {
    // ✅ PREVENT DUPLICATE BINDINGS
    const board = document.querySelector('.task-board');
    if (!board) return;

    if (board.dataset.statusActionsbound === "1") return;
    board.dataset.statusActionsbound = "1";

    // Open/close dropdown (using event delegation on board)
    board.addEventListener("click", (e) => {
        const pill = e.target.closest(".status-pill");
        if (!pill) return;

        e.preventDefault();
        e.stopPropagation();

        if (!window.__CAN_MANAGE_PROJECT__) return;

        // Close all others
        document.querySelectorAll(".status-dropdown").forEach(m => m.hidden = true);
        document.querySelectorAll(".task-card").forEach(c => c.classList.remove("menu-open"));

        const menu = pill.nextElementSibling;
        const card = pill.closest(".task-card");

        if (menu && card) {
            menu.hidden = false;
            card.classList.add("menu-open");
        }
    });

    // Handle dropdown actions (using event delegation on board)
    board.addEventListener("click", async (e) => {
        const option = e.target.closest(".status-dropdown button");
        if (!option) return;

        e.preventDefault();
        e.stopPropagation();

        if (!window.__CAN_MANAGE_PROJECT__) return;

        const wrapper = option.closest(".task-status-menu");
        const taskId = wrapper?.dataset.taskId;
        if (!taskId) return;

        const action = option.dataset.action;
        const value = option.dataset.value;

        const tasks = window.__TASKS_NORM__ || [];
        const task = tasks.find(t => String(t.id) === String(taskId));
        if (!task) return;

        // Close dropdown
        document.querySelectorAll(".status-dropdown").forEach(m => m.hidden = true);
        document.querySelectorAll(".task-card").forEach(c => c.classList.remove("menu-open"));

        if (action === "edit") {
            openEditTaskModal(taskId);
            return;
        }

        // Handle DELETE action
        if (action === "delete") {
            const ok = confirm("Are you sure you want to delete this task? This cannot be undone.");
            if (!ok) return;

            try {
                await deleteTaskInDb(task.id);

                if (Array.isArray(window.__TASKS_NORM__)) {
                    window.__TASKS_NORM__ = window.__TASKS_NORM__.filter(t => String(t.id) !== String(taskId));
                }
                if (Array.isArray(window.__TASKS__)) {
                    window.__TASKS__ = window.__TASKS__.filter(t => String(t.task_id) !== String(taskId));
                }

                showSuccessNotification("Task deleted successfully!");
                renderTaskBoard(currentUser, currentProjectId);
            } catch (err) {
                console.error("Delete error:", err);
                alert("Could not delete task. Please try again.");
            }
            return;
        }

        // Handle STATUS change
        if (action === "status" && task.status !== value) {
            const old = task.status;
            task.status = value;

            try {
                await updateTaskStatusInDb(task.id, denormalizeStatus(value));
                showSuccessNotification("Task status updated!");
            } catch (err) {
                task.status = old;
                console.error("Status update error:", err);
                alert("Could not change status");
                return;
            }
        }

        // Handle PRIORITY change
        if (action === "priority" && task.priority !== value) {
            const old = task.priority;
            task.priority = value;

            try {
                await updateTaskPriorityInDb(task.id, value);
                showSuccessNotification("Task priority updated!");
            } catch (err) {
                task.priority = old;
                console.error("Priority update error:", err);
                alert("Could not change priority");
                return;
            }
        }

        renderTaskBoard(currentUser, currentProjectId);
    });

    // Close all menus when clicking outside (single listener on document)
    document.addEventListener("click", (e) => {
        if (e.target.closest(".task-status-menu")) return;
        document.querySelectorAll(".status-dropdown").forEach(m => m.hidden = true);
        document.querySelectorAll(".task-card").forEach(c => c.classList.remove("menu-open"));
    }, { once: false }); // Don't use 'once' here as we want it to persist
}

/**
 * Opens the assign task modal in EDIT mode with pre-filled data
 */
function openEditTaskModal(taskId) {
    const tasks = window.__TASKS_NORM__ || [];
    const task = tasks.find(t => String(t.id) === String(taskId));

    if (!task) {
        alert("Task not found");
        return;
    }

    const modal = document.getElementById("assign-task-modal");
    if (!modal) return;

    // Get all form elements
    const titleInput = document.getElementById("modal-task-title");
    const descriptionInput = document.getElementById("modal-task-description");
    const prioritySelect = document.getElementById("modal-task-priority");
    const deadlineInput = document.getElementById("modal-task-deadline");
    const statusSelect = document.getElementById("modal-task-status");
    const assigneesList = document.getElementById("modal-task-assignees");
    const modalTitle = document.querySelector("#assign-task-modal .modal-header h2");
    const submitBtn = document.querySelector("#assign-task-modal button[type='submit']");

    // Change modal title and button text
    if (modalTitle) modalTitle.textContent = "Edit Task";
    if (submitBtn) submitBtn.textContent = "Update Task";

    // Pre-fill the form with existing task data
    if (titleInput) titleInput.value = task.title || "";
    if (descriptionInput) descriptionInput.value = task.description || "";
    if (prioritySelect) prioritySelect.value = task.priority || "medium";
    if (statusSelect) statusSelect.value = task.status || "todo";

    // Format deadline for date input (YYYY-MM-DD)
    if (deadlineInput && task.deadline) {
        const deadlineDate = new Date(task.deadline);
        const yyyy = deadlineDate.getFullYear();
        const mm = String(deadlineDate.getMonth() + 1).padStart(2, "0");
        const dd = String(deadlineDate.getDate()).padStart(2, "0");
        deadlineInput.value = `${yyyy}-${mm}-${dd}`;
    }

    // Pre-check assigned users
    if (assigneesList && Array.isArray(task.assignedTo)) {
        const checkboxes = assigneesList.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(cb => {
            const email = cb.value.toLowerCase();
            const isAssigned = task.assignedTo.some(
                assignedEmail => String(assignedEmail).toLowerCase() === email
            );
            cb.checked = isAssigned;
        });
    }

    // Update selected count
    updateSelectedCount();

    // Store task ID in modal for submission
    modal.dataset.editingTaskId = taskId;
    modal.dataset.editMode = "true";

    // Show modal
    modal.style.display = "flex";
    document.body.style.overflow = "hidden";

    if (window.feather) feather.replace();
}

/**
 * Closes the task modal and resets it to CREATE mode
 */
function closeTaskModal() {
    const modal = document.getElementById("assign-task-modal");
    if (!modal) return;

    modal.style.display = "none";
    document.body.style.overflow = "";

    // Reset modal to CREATE mode
    const modalTitle = modal.querySelector(".modal-header h2");
    const submitBtn = modal.querySelector("button[type='submit']");

    if (modalTitle) modalTitle.textContent = "Assign New Task";
    if (submitBtn) submitBtn.textContent = "Assign Task";

    // Clear edit mode flags
    delete modal.dataset.editMode;
    delete modal.dataset.editingTaskId;

    // Reset form
    const form = document.getElementById("assign-task-form");
    if (form) form.reset();

    // Update selected count
    const countEl = document.getElementById("assignee-selected-count");
    if (countEl) countEl.textContent = "Selected: 0";
}

/**
 * Helper function to update assignee selected count
 */
function updateSelectedCount() {
    const selectedCountEl = document.getElementById("assignee-selected-count");
    if (!selectedCountEl) return;

    const checked = document.querySelectorAll('#modal-task-assignees input[type="checkbox"]:checked');
    selectedCountEl.textContent = `Selected: ${checked.length}`;
}



/**
 * Initializes drag and drop functionality for the board
 */
function setupBoardDnDOnce(currentUser, currentProjectId) {
    if (!IS_ARCHIVED) {
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
}

function ensureTaskDetailsMenuUI(detailsModal) {
    // Add a unique scope class so CSS only affects THIS modal
    detailsModal.classList.add("task-details-modal");

    const header = detailsModal.querySelector(".modal-header");
    if (!header) return null;

    // Create / reuse right-side actions container
    let actions = header.querySelector(".details-header-actions");
    if (!actions) {
        actions = document.createElement("div");
        actions.className = "details-header-actions";
        header.appendChild(actions);
    }

    // Move the CLOSE button into the actions container
    const closeBtn =
        header.querySelector("#details-close-modal-btn") ||
        header.querySelector(".close-btn");

    if (closeBtn && closeBtn.parentElement !== actions) {
        actions.appendChild(closeBtn);
    }

    // If menu already exists, return references
    let wrap = actions.querySelector(".task-status-menu.details-menu");
    if (wrap) {
        return {
            menuBtn: wrap.querySelector(".status-pill"),
            dropdown: wrap.querySelector(".status-dropdown"),
        };
    }

    // Create menu WITHOUT any feather icons - use pure text
    wrap = document.createElement("div");
    wrap.className = "task-status-menu details-menu";

    wrap.innerHTML = `
    <button type="button" class="status-pill" aria-label="Task actions">
      <span class="ellipsis">⋯</span>
    </button>

    <div class="status-dropdown" hidden>
      <button type="button" data-action="edit">
        Edit Task
      </button>

      <div class="dropdown-divider"></div>

      <button type="button" data-action="delete">
        Delete Task
      </button>
    </div>
  `;

    // Insert the menu BEFORE the close button
    if (closeBtn && closeBtn.parentElement === actions) {
        actions.insertBefore(wrap, closeBtn);
    } else {
        actions.appendChild(wrap);
    }

    return {
        menuBtn: wrap.querySelector(".status-pill"),
        dropdown: wrap.querySelector(".status-dropdown"),
    };
}



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

    document.addEventListener("click", (e) => {
        // If they clicked the 3-dot menu, do NOT open modal
        if (e.target.closest(".task-status-menu")) return;
        // Don't open task details when clicking task action controls
        if (e.target.closest(".task-actions")) return;
        if (e.target.closest(".task-move-select")) return;

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
        // Buttons
        const markBtn = document.getElementById("project-complete-btn");
        const deleteBtn = document.getElementById("delete-task-btn");

        const role = getEffectiveRole(currentUser);
        const canManageProject = !!window.__CAN_MANAGE_PROJECT__;
        const isManagerLike = role === "manager" || canManageProject;

        // Hide the old Delete button entirely
        if (deleteBtn) deleteBtn.style.display = "none";

        // Regular employees: can only mark complete from todo/inprogress
        if (!isManagerLike) {
            const canMarkComplete = task.status === "todo" || task.status === "inprogress";
            if (markBtn) markBtn.style.display = canMarkComplete ? "inline-flex" : "none";
        } else {
            // Managers/leaders: hide mark complete
            if (markBtn) markBtn.style.display = "none";
        }

        // Setup the 3-dot menu
        const menuUI = ensureTaskDetailsMenuUI(detailsModal);

        if (menuUI) {
            const { menuBtn, dropdown } = menuUI;

            // Only show menu for manager/leader
            menuBtn.style.display = isManagerLike ? "inline-flex" : "none";
            dropdown.hidden = true;

            // Store the current taskId on the menu for actions
            menuBtn.dataset.taskId = String(task.id);
            dropdown.dataset.taskId = String(task.id);

            // **FIX: Remove old listeners and rebind**
            const newMenuBtn = menuBtn.cloneNode(true);
            menuBtn.parentNode.replaceChild(newMenuBtn, menuBtn);

            const newDropdown = dropdown.cloneNode(true);
            dropdown.parentNode.replaceChild(newDropdown, dropdown);

            // Toggle dropdown
            newMenuBtn.addEventListener("click", (e) => {
                e.preventDefault();
                e.stopPropagation();
                newDropdown.hidden = !newDropdown.hidden;
                if (window.feather) feather.replace();
            });

            // Handle menu actions (edit/delete)
            newDropdown.addEventListener("click", async (e) => {
                const item = e.target.closest("button[data-action]");
                if (!item) return;

                e.preventDefault();
                e.stopPropagation();

                const action = item.dataset.action;
                const id = newDropdown.dataset.taskId;

                newDropdown.hidden = true;

                if (action === "edit") {
                    // close task details modal first
                    detailsModal.style.display = "none";

                    // open edit modal immediately
                    openEditTaskModal(id);
                    return;
                }

                if (action === "delete") {
                    const ok = confirm("Are you sure you want to delete this task? This cannot be undone.");
                    if (!ok) return;

                    try {
                        await deleteTaskInDb(id);

                        // remove locally too
                        if (Array.isArray(window.__TASKS_NORM__)) {
                            window.__TASKS_NORM__ = window.__TASKS_NORM__.filter((t) => String(t.id) !== String(id));
                        }
                        if (Array.isArray(window.__TASKS__)) {
                            window.__TASKS__ = window.__TASKS__.filter((t) => String(t.task_id) !== String(id));
                        }

                        // close modal + rerender
                        detailsModal.style.display = "none";
                        document.body.style.overflow = "";
                        showSuccessNotification("Task deleted successfully!");
                        renderTaskBoard(currentUser, getCurrentProjectId());
                    } catch (err) {
                        console.error(err);
                        alert("Could not delete task. Check console.");
                    }
                }
            });

            // Close dropdown when clicking elsewhere inside modal
            detailsModal.addEventListener("click", (e) => {
                if (e.target.closest(".details-header-actions")) return;
                newDropdown.hidden = true;
            });
        }

        // Rebind mark complete button
        if (markBtn) {
            const freshMarkBtn = markBtn.cloneNode(true);
            markBtn.parentNode.replaceChild(freshMarkBtn, markBtn);

            freshMarkBtn.addEventListener("click", async () => {
                // Safety: regular employees can only mark complete from todo/inprogress
                if (!isManagerLike && !(task.status === "todo" || task.status === "inprogress")) {
                    alert("You can only mark a task complete if it is in To Do or In Progress.");
                    return;
                }

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

                    detailsModal.style.display = "none";
                    document.body.style.overflow = "";
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

    // Close dropdown on ESC
    document.addEventListener("keydown", (e) => {
        if (e.key !== "Escape") return;
        const dd = detailsModal.querySelector(".status-dropdown");
        if (dd) dd.hidden = true;
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
    console.log("Manager progress tasks:", projectTasks.length, projectTasks);

    //renderManagerDeadlines(projectTasks);
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
        const usersMap = getUsersMap();
        const user = usersMap[email];
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
async function loadProjectResourcesPage(currentUser) {
    updateSidebarAndNav();

    const res = await fetch('project-resources.php?action=list', {
        headers: { 'Accept': 'application/json' }
    });

    const data = await res.json();
    if (!data.success) {
        console.error(data.message);
        return;
    }

    const project = data.project;

    // Fill in project details
    document.getElementById('project-created-date').textContent =
        new Date(project.created_at).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });

    document.getElementById('project-description').textContent =
        project.description || 'No description provided for this project.';

    // Build project contacts
    const contactsList = document.getElementById('project-contacts-list');
    contactsList.innerHTML = '';

    const contacts = [];

    if (project.manager_first_name) {
        contacts.push({
            name: `${project.manager_first_name} ${project.manager_last_name}`,
            role: 'Project Manager',
            avatar: project.manager_avatar || '/default-avatar.png'
        });
    }

    if (project.team_leader_first_name) {
        contacts.push({
            name: `${project.team_leader_first_name} ${project.team_leader_last_name}`,
            role: 'Team Leader',
            avatar: project.team_leader_avatar || '/default-avatar.png'
        });
    }

    contactsList.innerHTML = contacts.map(c => `
        <div class="contact-item">
            <div class="avatar">
                ${c.avatar ? `<img src="${c.avatar}" alt="${c.name}">` : `<span class="avatar-fallback">${c.name.split(' ').map(n => n[0]).join('')}</span>`}
            </div>
            <div class="contact-info">
                <span class="contact-name">${c.name}</span>
                <span class="contact-role">${c.role}</span>
            </div>
        </div>
    `).join('');
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

        // If user clicked an option in the menu (Mark complete / Archive / Reinstate / Update)
        if (menuItem) {
            const card = menuItem.closest(".project-card");
            const projectId = card.dataset.projectId;
            const action = menuItem.dataset.action;

            // Close menu immediately
            const menu = card.querySelector(".card-menu-dropdown");
            if (menu) menu.hidden = true;

            // ✅ Update opens modal (no DB call here)
            if (action === "update") {
                openUpdateProjectModal(card);
                return;
            }

            // others go to PHP action handler
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
        // Only show "Mark as complete" if progress is 100%
        const progress = parseInt(cardEl.dataset.progress || "0", 10);
        const isComplete = (progress >= 100);

        dropdown.innerHTML = `
      ${isComplete ? `<button type="button" class="card-menu-item" data-action="complete">
        Mark as complete
      </button>` : ''}
      <button type="button" class="card-menu-item" data-action="archive">
        Move to archives
      </button>
      <button type="button" class="card-menu-item" data-action="update">
        Update project
      </button>
    `;
    } else if (state === "archived") {
        dropdown.innerHTML = `
      <button type="button" class="card-menu-item" data-action="reinstate">
        Reinstate
      </button>
    `;
    }

    dropdown.hidden = true;
}


function updateEmptyStates() {
    // Update Active empty state
    const activeGrid = document.querySelector("#active-section .projects-grid");
    if (activeGrid) {
        const hasActiveCards = activeGrid.querySelector(".project-card:not(.project-card--archived)") !== null;
        const existingActiveEmpty = activeGrid.querySelector(".empty-state");

        if (!hasActiveCards) {
            if (!existingActiveEmpty) {
                activeGrid.innerHTML = `
          <div class="empty-state">
            <i data-feather="inbox"></i>
            <p>No current active projects</p>
          </div>
        `;
                if (window.feather) feather.replace();
            }
        } else {
            existingActiveEmpty?.remove();
        }
    }

    // Update Archived empty state
    const archivedGrid = document.querySelector("#archived-section .projects-grid");
    if (archivedGrid) {
        const hasArchivedCards = archivedGrid.querySelector(".project-card") !== null;
        const existingArchivedEmpty = archivedGrid.querySelector(".empty-state");

        if (!hasArchivedCards) {
            if (!existingArchivedEmpty) {
                archivedGrid.innerHTML = `
          <div class="empty-state">
            <i data-feather="archive"></i>
            <p>No archived projects</p>
          </div>
        `;
                if (window.feather) feather.replace();
            }
        } else {
            existingArchivedEmpty?.remove();
        }
    }
}

function restoreDeadlinePill(cardEl) {
    const deadlineStr = cardEl.dataset.deadline || "";

    // ✅ Recalculate the deadline pill based on current date
    if (!deadlineStr) {
        const pill = cardEl.querySelector(".days-pill");
        if (pill) {
            pill.className = "days-pill";
            const span = pill.querySelector("span");
            if (span) span.textContent = "No date set";
        }
        cardEl.dataset.deadlineText = "No date set";
        cardEl.dataset.deadlineClass = "days-pill";
        return;
    }

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const d = new Date(deadlineStr);
    d.setHours(0, 0, 0, 0);

    const diffDays = Math.round((d - today) / (1000 * 60 * 60 * 24));

    let text = "";
    let cls = "days-pill";

    if (diffDays < 0) {
        text = `${Math.abs(diffDays)} days overdue`;
        cls = "days-pill is-overdue";
    } else if (diffDays === 0) {
        text = "Due today";
        cls = "days-pill is-due";
    } else {
        text = `${diffDays} days left`;
        cls = "days-pill";
    }

    // ✅ Update the pill in the DOM
    const pill = cardEl.querySelector(".days-pill");
    if (pill) {
        pill.className = cls;
        const span = pill.querySelector("span");
        if (span) span.textContent = text;
    }

    // ✅ Update datasets for future reference
    cardEl.dataset.deadlineText = text;
    cardEl.dataset.deadlineClass = cls;
}

function insertCardInSortedPosition(card, grid, sortBy = "due") {
    if (!grid) return;

    const existingCards = Array.from(grid.querySelectorAll(".project-card:not(.empty-state)"));

    // If no cards, just append
    if (existingCards.length === 0) {
        grid.appendChild(card);
        return;
    }

    const priorityRank = (p) => {
        if (p === 'high') return 3;
        if (p === 'medium') return 2;
        if (p === 'low') return 1;
        return 0;
    };

    // Find the correct position based on current sort
    let insertBeforeCard = null;

    for (const existingCard of existingCards) {
        let shouldInsertBefore = false;

        switch (sortBy) {
            case "due": {
                const cardDate = card.dataset.deadline
                    ? new Date(card.dataset.deadline)
                    : new Date('9999-12-31');
                const existingDate = existingCard.dataset.deadline
                    ? new Date(existingCard.dataset.deadline)
                    : new Date('9999-12-31');
                shouldInsertBefore = cardDate < existingDate;
                break;
            }

            case "progress": {
                const cardProgress = Number(card.dataset.progress) || 0;
                const existingProgress = Number(existingCard.dataset.progress) || 0;
                shouldInsertBefore = cardProgress > existingProgress; // Higher % first
                break;
            }

            case "name": {
                const cardName = (card.dataset.name || "").toLowerCase();
                const existingName = (existingCard.dataset.name || "").toLowerCase();
                shouldInsertBefore = cardName < existingName;
                break;
            }

            case "priorityHigh": {
                const cardPriority = priorityRank(card.dataset.priority || "");
                const existingPriority = priorityRank(existingCard.dataset.priority || "");
                shouldInsertBefore = cardPriority > existingPriority;
                break;
            }

            case "priorityLow": {
                const cardPriority = priorityRank(card.dataset.priority || "");
                const existingPriority = priorityRank(existingCard.dataset.priority || "");
                shouldInsertBefore = cardPriority < existingPriority;
                break;
            }

            default:
                shouldInsertBefore = false;
        }

        if (shouldInsertBefore) {
            insertBeforeCard = existingCard;
            break;
        }
    }

    // Insert at the correct position
    if (insertBeforeCard) {
        grid.insertBefore(card, insertBeforeCard);
    } else {
        grid.appendChild(card);
    }
}



function updateCardUIAfterAction(action, cardEl) {
    const activeGrid = document.querySelector("#active-section .projects-grid");
    const archivedGrid = document.querySelector("#archived-section .projects-grid");

    // Helper: set status pill text
    function setPill(card, text, extraClass) {
        const pill = card.querySelector(".days-pill");
        if (!pill) return;

        pill.className = "days-pill";
        if (extraClass) pill.classList.add(extraClass);

        const span = pill.querySelector("span");
        if (span) span.textContent = text;
    }

    if (action === "archive") {
        archivedGrid?.querySelector(".empty-state")?.remove();
        archivedGrid.appendChild(cardEl);

        cardEl.classList.add("project-card--archived");
        setPill(cardEl, "Archived");

        const archivedSection = document.getElementById("archived-section");
        if (archivedSection) archivedSection.style.display = "";

        renderCardMenu(cardEl, "archived");
        updateEmptyStates();
    }

    if (action === "complete") {
        archivedGrid?.querySelector(".empty-state")?.remove();
        archivedGrid.appendChild(cardEl);

        cardEl.classList.add("project-card--archived");

        setPill(cardEl, "Completed", "is-completed");

        cardEl.dataset.wasCompleted = "true";

        const archivedSection = document.getElementById("archived-section");
        if (archivedSection) archivedSection.style.display = "";

        renderCardMenu(cardEl, "archived");
        updateEmptyStates();
    }


    if (action === "reinstate") {
        // ✅ Remove empty state first
        activeGrid?.querySelector(".empty-state")?.remove();

        // ✅ Remove archived styling
        cardEl.classList.remove("project-card--archived");

        // ✅ CRITICAL: Restore the deadline pill BEFORE moving the card
        restoreDeadlinePill(cardEl);

        // ✅ Update the menu to show active options
        renderCardMenu(cardEl, "active");

        // ✅ Insert card in the correct sorted position
        const deadline = cardEl.dataset.deadline || "";
        const sortDropdown = document.getElementById("sortProjects");
        const currentSort = sortDropdown ? sortDropdown.value : "due";

        insertCardInSortedPosition(cardEl, activeGrid, currentSort);

        // ✅ Update empty states
        updateEmptyStates();
    }

    if (window.feather) feather.replace();
}

function refreshProjectsGrid() {
    const grid = document.querySelector(".projects-grid");
    if (!grid) return;

    const cards = Array.from(grid.querySelectorAll(".project-card"));

    // Clear grid safely
    grid.innerHTML = "";

    // Re-append cards (single source of truth)
    cards.forEach(card => grid.appendChild(card));

    feather.replace();
}


function openUpdateProjectModal(card) {
    const modal = document.getElementById("update-project-modal");
    const closeBtn = document.getElementById("update-project-close-btn");
    const form = document.getElementById("update-project-form");

    if (!modal || !form) return;

    // Prefill from card dataset
    const projectId = card.dataset.projectId || "";
    const name = card.querySelector(".project-title")?.textContent?.trim() || "";
    const deadline = card.dataset.deadline || "";
    const desc = card.dataset.description || "";
    const leaderId = card.dataset.teamLeaderId || "";
    const leaderName = card.dataset.teamLeaderName || "";

    document.getElementById("update-project-id").value = projectId;
    document.getElementById("update-project-name").value = name;
    document.getElementById("update-project-deadline").value = deadline;
    document.getElementById("update-project-description").value = desc;

    // leader fields
    const leaderSearch = document.getElementById("update-leader-search");
    const leaderHidden = document.getElementById("update-team-leader-id");
    if (leaderSearch) leaderSearch.value = leaderName !== "Unassigned" ? leaderName : "";
    if (leaderHidden) leaderHidden.value = leaderId;

    // Setup autocomplete once
    if (form.dataset.autocompleteBound !== "1") {
        form.dataset.autocompleteBound = "1";

        setupLeaderAutocomplete({
            inputId: "update-leader-search",
            hiddenId: "update-team-leader-id",
            resultsId: "update-leader-results",
            endpointUrl: "projects-overview.php?ajax=leaders",
            formId: "update-project-form",
        });
    }

    // open / close
    modal.style.display = "flex";
    document.body.style.overflow = "hidden";
    if (window.feather) feather.replace();

    const close = () => {
        modal.style.display = "none";
        document.body.style.overflow = "";
    };

    // Prevent double binding
    if (modal.dataset.bound !== "1") {
        modal.dataset.bound = "1";

        // ONLY close via X button
        closeBtn?.addEventListener("click", close);
    }


    // Submit once
    if (form.dataset.submitBound !== "1") {
        form.dataset.submitBound = "1";

        form.addEventListener("submit", async (e) => {
            e.preventDefault();
            e.stopPropagation();

            const pid = document.getElementById("update-project-id").value;
            const projectName = document.getElementById("update-project-name").value.trim();
            const deadline = document.getElementById("update-project-deadline").value;
            const description = document.getElementById("update-project-description").value.trim();
            const teamLeaderId = document.getElementById("update-team-leader-id").value;

            if (!projectName) {
                alert("Project name is required.");
                return;
            }
            if (!teamLeaderId) {
                alert("Please select a Team Leader from the suggestions.");
                return;
            }

            try {
                const res = await fetch(window.location.href, {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: new URLSearchParams({
                        action: "update_project",
                        project_id: pid,
                        project_name: projectName,
                        deadline: deadline,
                        description: description,
                        team_leader_id: teamLeaderId
                    })
                });

                const raw = await res.text();
                let data;
                try {
                    data = JSON.parse(raw);
                } catch {
                    console.error("Non-JSON response:", raw);
                    alert("Server did not return JSON. Check console.");
                    return;
                }

                if (!data.success) {
                    alert(data.message || "Update failed");
                    return;
                }

                // ✅ Find the card and update it
                const card = document.querySelector(`[data-project-id="${pid}"]`);
                if (card) {
                    applyUpdatedProjectToCard(card, data.updated);
                }

                // ✅ Close modal
                modal.style.display = "none";
                document.body.style.overflow = "";

                // ✅ Show success message
                showSuccessNotification("Project updated successfully!");

                // ✅ Re-render feather icons
                if (window.feather) feather.replace();

            } catch (err) {
                console.error(err);
                alert("Network/server error updating project.");
            }
        });
    }

}

function applyUpdatedProjectToCard(card, updated) {
    if (!card || !updated) return;

    // ✅ Update title
    const titleEl = card.querySelector(".project-title");
    if (titleEl) titleEl.textContent = updated.project_name;

    // ✅ Update description (if displayed anywhere)
    const descEl = card.querySelector(".project-description");
    if (descEl) descEl.textContent = updated.description || "";

    // ✅ Update datasets
    card.dataset.name = String(updated.project_name || "").toLowerCase();
    card.dataset.deadline = updated.deadline || "";
    card.dataset.description = updated.description || "";
    card.dataset.teamLeaderId = updated.team_leader_id || "";
    card.dataset.teamLeaderName = updated.leader_name || "Unassigned";

    // ✅ Update leader name
    const leaderNameEl = card.querySelector(".leader-name");
    if (leaderNameEl) leaderNameEl.textContent = updated.leader_name || "Unassigned";

    // ✅ Update leader avatar
    const leaderRow = card.querySelector(".leader-row");
    const avatarImg = card.querySelector("img.leader-avatar");
    const avatarDefault = card.querySelector(".leader-avatar--default");

    if (updated.leader_picture) {
        // Remove default avatar if it exists
        if (avatarDefault) avatarDefault.remove();

        if (avatarImg) {
            // Update existing image
            avatarImg.src = updated.leader_picture;
        } else {
            // Create new image
            if (leaderRow) {
                const firstChild = leaderRow.firstElementChild;
                leaderRow.insertAdjacentHTML(
                    "afterbegin",
                    `<img class="leader-avatar" src="${updated.leader_picture}" alt="Leader pfp">`
                );
            }
        }
    } else {
        // No picture - show default avatar
        if (avatarImg) avatarImg.remove();

        if (!avatarDefault && leaderRow) {
            leaderRow.insertAdjacentHTML(
                "afterbegin",
                `<div class="leader-avatar leader-avatar--default" aria-hidden="true">
                    <i data-feather="user"></i>
                </div>`
            );
        }
    }

    // ✅ Update deadline pill
    updateDeadlinePillUI(card, updated.deadline);

    // ✅ Re-render icons
    if (window.feather) feather.replace();
}

function updateDeadlinePillUI(card, deadlineStr) {
    const pill = card.querySelector(".days-pill");
    if (!pill) return;

    // if no deadline
    if (!deadlineStr) {
        pill.className = "days-pill";
        const span = pill.querySelector("span");
        if (span) span.textContent = "No date set";
        card.dataset.deadlineText = "No date set";
        card.dataset.deadlineClass = "days-pill";
        return;
    }

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const d = new Date(deadlineStr);
    d.setHours(0, 0, 0, 0);

    const diffDays = Math.round((d - today) / (1000 * 60 * 60 * 24));

    let text = "";
    let cls = "days-pill";

    if (diffDays < 0) {
        text = `${Math.abs(diffDays)} days overdue`;
        cls = "days-pill is-overdue";
    } else if (diffDays === 0) {
        text = "Due today";
        cls = "days-pill is-due";
    } else {
        text = `${diffDays} days left`;
        cls = "days-pill";
    }

    pill.className = cls;
    const span = pill.querySelector("span");
    if (span) span.textContent = text;

    // keep datasets in sync for restore + future
    card.dataset.deadlineText = text;
    card.dataset.deadlineClass = cls;
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
/**
 * Injects the floating To-Do widget into a page (for static HTML pages that can't use PHP include).
 * @param {string} todoBase - Relative path to the to-do actions directory, e.g. "../to-do/"
 */
function injectTodoWidget(todoBase) {
    // Inline styles (same as todo_widget.php)
    const style = document.createElement('style');
    style.textContent = `
    .todo-panel[hidden] { display:none; opacity:0; transform:translateY(10px) scale(0.95); }
    .floating-todo-item { position:relative; padding-right:40px !important; cursor:pointer; }
    .todo-delete-btn { position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; color:#D93025; cursor:pointer; padding:4px; display:flex; align-items:center; justify-content:center; border-radius:4px; opacity:0.3; transition:opacity 0.2s; z-index:2; }
    .floating-todo-item:hover .todo-delete-btn { opacity:1; }
    .todo-delete-btn:hover { background:#FFF1F0; }
    .floating-todo-checkbox { cursor:pointer; }
  `;
    document.head.appendChild(style);

    // Widget HTML
    const wrapper = document.createElement('div');
    wrapper.innerHTML = `
    <div class="floating-todo-widget" id="floating-todo-widget">
      <button class="todo-fab" id="todo-fab" aria-label="Toggle personal todos">
        <i data-feather="check-square"></i>
        <span class="todo-badge hidden" id="todo-badge">0</span>
      </button>
      <div class="todo-panel" id="todo-panel" hidden>
        <div class="todo-panel-header">
          <h3>My To-Dos</h3>
          <button class="todo-close-btn" id="todo-close-btn"><i data-feather="x"></i></button>
        </div>
        <div class="todo-panel-content">
          <div style="padding:15px; border-bottom:1px solid #E8E4D9; background:#FEFBF4;">
            <form id="quick-add-todo-form" style="display:flex; flex-direction:column; gap:8px;">
              <input type="text" id="new-todo-name" placeholder="Quick add task..." required style="padding:10px; border:1px solid #D4CDB8; border-radius:8px; font-size:13px; font-family:inherit; width:100%;">
              <div style="display:flex; gap:8px;">
                <input type="datetime-local" id="new-todo-date" style="padding:8px; border:1px solid #D4CDB8; border-radius:8px; font-size:11px; flex-grow:1; font-family:inherit;">
                <button type="submit" class="todo-add-btn" style="width:auto; padding:0 15px; height:35px; margin:0;">Add</button>
              </div>
            </form>
          </div>
          <div class="todo-panel-list" id="floating-todo-list">
            <div class="floating-todo-empty"><i data-feather="loader"></i><p>Loading...</p></div>
          </div>
          <div class="todo-panel-footer" style="text-align:center;">
            <small style="color:#8C8C8C; font-size:11px;" id="todo-status-text">Ready to work!</small>
          </div>
        </div>
      </div>
    </div>
  `;
    document.body.insertBefore(wrapper.firstElementChild, document.body.firstChild);

    // Widget logic
    const TODO_BASE = todoBase;
    const fab = document.getElementById('todo-fab');
    const panel = document.getElementById('todo-panel');
    const closeBtn = document.getElementById('todo-close-btn');
    const todoList = document.getElementById('floating-todo-list');
    const badge = document.getElementById('todo-badge');
    const addForm = document.getElementById('quick-add-todo-form');

    async function toggleTaskStatus(todoId, currentStatus) {
        try {
            const res = await fetch(TODO_BASE + 'update_todo_status.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ todo_id: todoId, is_completed: currentStatus == 1 ? 0 : 1 })
            });
            const result = await res.json();
            if (result.success) { await loadTodos(); await updateBadge(); }
            else alert(result.message || 'Failed to update task');
        } catch (e) { console.error('Toggle error:', e); }
    }

    async function deleteTask(todoId) {
        if (!confirm("Delete this task?")) return;
        try {
            const res = await fetch(TODO_BASE + 'delete_todo.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ todo_id: todoId })
            });
            const result = await res.json();
            if (result.success) { await loadTodos(); await updateBadge(); }
            else alert(result.message || 'Failed to delete task');
        } catch (e) { console.error('Delete error:', e); }
    }

    async function loadTodos() {
        try {
            const response = await fetch(TODO_BASE + 'get_personal_todos.php');
            const data = await response.json();
            if (data.error) {
                todoList.innerHTML = '<div class="floating-todo-empty"><i data-feather="alert-triangle"></i><p>' + data.error + '</p></div>';
                feather.replace(); return;
            }
            if (!data || data.length === 0) {
                todoList.innerHTML = '<div class="floating-todo-empty"><i data-feather="clipboard"></i><p>All caught up!</p></div>';
                feather.replace(); return;
            }
            todoList.innerHTML = data.map(todo => {
                const isDone = todo.is_completed == 1;
                const deadline = todo.deadline ? new Date(todo.deadline) : null;
                const formattedDate = deadline ? deadline.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + ' ' + deadline.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }) : 'No deadline';
                const isOverdue = deadline && deadline < new Date() && !isDone;
                return '<div class="floating-todo-item ' + (isDone ? 'completed' : '') + '">' +
                    '<div class="floating-todo-checkbox ' + (isDone ? 'checked' : '') + '" onclick="event.stopPropagation(); window.todoApp.toggleTaskStatus(' + todo.personal_task_id + ',' + todo.is_completed + ')">' +
                    (isDone ? '<i data-feather="check" style="width:12px;height:12px;"></i>' : '') +
                    '</div>' +
                    '<div class="floating-todo-content">' +
                    '<div class="floating-todo-text">' + todo.task_name + '</div>' +
                    '<div class="floating-todo-meta"><span class="' + (isOverdue ? 'overdue' : '') + '">' + formattedDate + '</span></div>' +
                    '</div>' +
                    '<button class="todo-delete-btn" onclick="event.stopPropagation(); window.todoApp.deleteTask(' + todo.personal_task_id + ')">' +
                    '<i data-feather="trash-2" style="width:14px;height:14px;"></i>' +
                    '</button>' +
                    '</div>';
            }).join('');
            feather.replace();
        } catch (e) {
            console.error('Load error:', e);
            todoList.innerHTML = '<div class="floating-todo-empty"><i data-feather="alert-triangle"></i><p>Error loading tasks</p></div>';
            feather.replace();
        }
    }

    async function updateBadge() {
        try {
            const response = await fetch(TODO_BASE + 'count_incomplete_todos.php');
            const data = await response.json();
            if (data.error) return;
            const count = data.count || 0;
            if (count > 0) {
                badge.textContent = count;
                badge.classList.remove('hidden');
                document.getElementById('todo-status-text').textContent = 'You have ' + count + ' pending tasks';
            } else {
                badge.classList.add('hidden');
                document.getElementById('todo-status-text').textContent = 'All tasks completed!';
            }
        } catch (e) { console.error('Badge error:', e); }
    }

    fab.addEventListener('click', () => {
        if (panel.hasAttribute('hidden')) { panel.removeAttribute('hidden'); loadTodos(); }
        else panel.setAttribute('hidden', '');
    });
    closeBtn.addEventListener('click', () => panel.setAttribute('hidden', ''));

    addForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const nameInput = document.getElementById('new-todo-name');
        const dateInput = document.getElementById('new-todo-date');
        if (!nameInput.value.trim()) { alert('Please enter a task name'); return; }
        try {
            const res = await fetch(TODO_BASE + 'create_personal_todo.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ task_name: nameInput.value, deadline: dateInput.value || null })
            });
            const result = await res.json();
            if (result.success) { nameInput.value = ''; dateInput.value = ''; await loadTodos(); await updateBadge(); }
            else alert(result.message || 'Failed to create task');
        } catch (e) { console.error('Create error:', e); }
    });

    window.todoApp = { toggleTaskStatus, deleteTask, loadTodos, updateBadge };
    updateBadge();
    feather.replace();
}


// === DOCUMENT LOAD =============================
// ===============================================

document.addEventListener('DOMContentLoaded', async () => {

    // Get the "logged in" user
    const currentUser = await getCurrentUser();
    if (!currentUser) return;
    // Make current user globally available
    window.__CURRENT_USER__ = currentUser;


    // Store user in window object for floating widget
    window.__USER__ = currentUser;
    window.__CURRENT_USER__ = currentUser; // Also set for task board rendering

    // Sync sidebar nav items based on role (for static HTML KB pages)
    const role = (currentUser.role || '').toLowerCase();
    const navHome = document.getElementById('nav-home');
    const navEmployees = document.getElementById('nav-employees');
    if (role === 'manager' || role === 'team_leader') {
        if (navHome) navHome.style.display = '';
    }
    if (role === 'manager') {
        if (navEmployees) navEmployees.style.display = '';
    }

    // Run page-specific logic based on body ID
    const pageId = document.body.id;

    // Inject the floating To-Do widget on KB pages (HTML pages can't use PHP include)
    const kbPages = ['kb-index', 'kb-post', 'kb-create', 'kb-create-topic', 'kb-topics-all'];
    if (kbPages.includes(pageId)) {
        injectTodoWidget('../to-do/');
    }

    if (pageId === 'kb-index') {
        const returnTopic = sessionStorage.getItem('returnToTopic');
        const showCreatedNotification = sessionStorage.getItem('topicCreated');
        const showPostNotification = sessionStorage.getItem('postCreated');
        setupKbLikeButtonsOnce();

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
            sessionStorage.removeItem('returnToTopic');

            await loadKbIndex(currentUser);          // attach listeners + load posts/topics
            await showTopicView(returnTopic, currentUser); // then switch view safely
        } else {
            await loadKbIndex(currentUser);
        }

    } else if (pageId === 'kb-post') {
        // DB-driven post page uses post.js now
    } else if (pageId === 'kb-create') {
        await setupCreateForm(currentUser);
    } else if (pageId === 'settings-page') {
        loadSettingsPage(currentUser);
    } else if (pageId === 'kb-topics-all') {
        await loadAllTopicsPage(currentUser);
    } else if (pageId === 'kb-create-topic') {
        // Create Topic form page
        setupCreateTopicForm(currentUser);
    } else if (pageId === 'home-page') {
        // Home page with to-do list
        loadHomePage(currentUser);
    } else if (pageId === 'progress-page') {
        // Team Member Progress page (with redirect)
        await loadProgressPage(currentUser);
    } else if (pageId === 'manager-progress-page') {
        // Manager Progress page
        await loadManagerProgressPage(currentUser);
    } else if (pageId === 'project-members-page') {
        // Project Members page
        updateSidebarAndNav();
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

    filterToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        filterPanel.style.display =
            filterPanel.style.display === 'flex' ? 'none' : 'flex';
    });

    // Close when clicking outside
    document.addEventListener('click', (e) => {
        if (!filterPanel.contains(e.target) && !filterToggle.contains(e.target)) {
            filterPanel.style.display = 'none';
        }
    });
})();

function matchesDueFilter(deadline, filter, status) {
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
            // Overdue tasks that aren't completed
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
        status: normalizeDbStatus(t.status),
        deadline: t.deadline,
        createdDate: t.created_date || t.date_assigned || t.created_at,
        assignedTo: Array.isArray(t.assignedUsers)
            ? t.assignedUsers.map(u => u.email)
            : []
    }));
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
    console.log("fetchAndRenderTasks running on:", document.body?.id);

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

            window.__TASKS__ = (data.tasks || []).filter(task => {
                const matches = matchesDueFilter(task.deadline, dueValue);
                // If overdue filter is active, exclude completed tasks
                if (dueValue === "overdue" && normalizeDbStatus(task.status) === "completed") {
                    return false;
                }
                return matches;
            });


            window.__TASKS_NORM__ = []; // force rebuild from filtered list

            // If this page has a kanban board, rerender it
            if (document.querySelector(".task-board")) {
                clearTaskColumns();
                renderTaskBoard(window.__CURRENT_USER__, getCurrentProjectId());
                updateAddTaskButtonsVisibility();
                updateTaskCounts();
            }

            if (document.body?.id === "manager-progress-page") {
                return;
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

document.addEventListener("DOMContentLoaded", () => {
    const pageId = document.body?.id;

    if (pageId === "projects-page") {
        fetchAndRenderTasks();
        setupAssignTaskForm();
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

    // Close filter panel when clicking outside
    document.addEventListener('click', (e) => {
        const filterPanel = document.getElementById('filter-panel');
        const filterToggle = document.getElementById('filter-toggle');

        if (!filterPanel || !filterToggle) return;

        // If click is outside both the panel and toggle button
        if (!filterPanel.contains(e.target) && !filterToggle.contains(e.target)) {
            filterPanel.style.display = 'none';
        }
    });
});

(function renderAnnouncements() {
    const announcements = [
        {
            title: "🔧 Scheduled maintenance",
            body: "The platform will be briefly unavailable on Sunday from 9:00–9:30 PM for routine updates. Please save any drafts beforehand.",
            date: "9 Feb 2026"
        },
        {
            title: "⏱ Response time expectations",
            body: "Technical posts are usually answered by a specialist within 24–48 hours. If your issue is urgent, make sure your title clearly explains the problem.",
            date: "6 Feb 2026"
        },
        {
            title: "💙 Mental health & wellbeing support",
            body: "If you’re feeling overwhelmed or struggling, confidential mental health support is available through our employee assistance programme. You’re not alone — reaching out is okay.",
            date: "5 Feb 2026"
        }
    ];

    const container = document.querySelector(".announcement-content-placeholder");
    if (!container) return;

    // Show latest 3 announcements only (cleaner UI)
    const latest = announcements.slice(0, 3);

    container.innerHTML = latest.map(a => `
    <div class="announcement-item">
      <strong class="announcement-title">${a.title}</strong>
      <p class="announcement-body">${a.body}</p>
      <span class="announcement-date">${a.date}</span>
    </div>
  `).join("");
})();

function setupKbLikeButtonsOnce() {
    if (document.body.dataset.kbLikesBound === "1") return;
    document.body.dataset.kbLikesBound = "1";

    document.addEventListener("click", async (e) => {
        const btn = e.target.closest(".kb-like-btn");
        if (!btn) return;

        e.preventDefault();
        e.stopPropagation();

        const postId = parseInt(btn.dataset.postId, 10);
        if (!postId) return;

        const countEl = btn.querySelector(".kb-like-count");
        const oldVal = countEl ? parseInt(countEl.textContent || "0", 10) : 0;

        // optimistic UI
        if (countEl) countEl.textContent = String(oldVal + 1);
        btn.disabled = true;

        try {
            const fd = new FormData();
            fd.append("post_id", String(postId));

            const res = await fetch(`${KB_ACTIONS_BASE}/like_post.php`, {
                method: "POST",
                body: fd,
                credentials: "include"
            });

            const data = await res.json();
            if (!data.success) throw new Error(data.message || "Like failed");

            // sync UI with DB value
            if (countEl) countEl.textContent = String(data.view_count ?? (oldVal + 1));
        } catch (err) {
            // revert on fail
            if (countEl) countEl.textContent = String(oldVal);
            console.error(err);
            alert("Could not like post. Please try again.");
        } finally {
            btn.disabled = false;
            if (window.feather) feather.replace();
        }
    });
}
