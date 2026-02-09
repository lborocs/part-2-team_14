# Testing Guide - Make-It-All

## Database Setup

Before testing, run these SQL statements to add the `like_count` column and `kb_post_likes` table:

```sql
-- Add like_count column to kb_posts
ALTER TABLE kb_posts ADD COLUMN like_count INT DEFAULT 0 AFTER view_count;

-- Create the kb_post_likes table
CREATE TABLE IF NOT EXISTS kb_post_likes (
    like_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_post_user_like (post_id, user_id),
    FOREIGN KEY (post_id) REFERENCES kb_posts(post_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_post (post_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Test Accounts

Use the following accounts to test. All passwords are: `password123`

| # | Name | Email | Role | Purpose |
|---|------|-------|------|---------|
| 1 | Alice Johnson | alice.manager@make-it-all.co.uk | Manager | Full admin access, can manage projects/employees |
| 2 | Bob Smith | bob.lead@make-it-all.co.uk | Team Leader | Team leader on Website Redesign project |
| 3 | Carol Williams | carol.dev@make-it-all.co.uk | Team Member | Regular member on Website Redesign |
| 4 | Dave Brown | dave.dev@make-it-all.co.uk | Team Member | Not registered (test registration) |
| 5 | Emma Davis | emma.specialist@make-it-all.co.uk | Technical Specialist | KB specialist |
| 6 | Frank Miller | frank.designer@make-it-all.co.uk | Team Member | Designer on Mobile App project |

---

## Dummy Data to Insert

Run these SQL statements to populate the database with test data:

```sql
-- =============================================
-- USERS (if not already inserted from schema)
-- =============================================
-- Password hash for 'password123'
SET @pw = '$2y$10$ysE1D1v.ezEci.mxqs3xcuQWfbbbgR/gMSWlPgHZCwesXZU1e54Sy';

INSERT IGNORE INTO users (email, password_hash, is_registered, first_name, last_name, role, specialties, profile_picture) VALUES
('alice.manager@make-it-all.co.uk', @pw, TRUE, 'Alice', 'Johnson', 'manager', '["Project Management","Strategy","Leadership"]', '/default-avatar.png'),
('bob.lead@make-it-all.co.uk', @pw, TRUE, 'Bob', 'Smith', 'team_leader', '["Backend","Python","SQL","API Design"]', '/default-avatar.png'),
('carol.dev@make-it-all.co.uk', @pw, TRUE, 'Carol', 'Williams', 'team_member', '["Frontend","React","CSS","JavaScript"]', '/default-avatar.png'),
('dave.dev@make-it-all.co.uk', NULL, FALSE, 'Dave', 'Brown', 'team_member', '["Backend","Node.js","MongoDB"]', '/default-avatar.png'),
('emma.specialist@make-it-all.co.uk', @pw, TRUE, 'Emma', 'Davis', 'technical_specialist', '["DevOps","AWS","Docker","CI/CD"]', '/default-avatar.png'),
('frank.designer@make-it-all.co.uk', @pw, TRUE, 'Frank', 'Miller', 'team_member', '["UI Design","Figma","Prototyping","CSS"]', '/default-avatar.png'),
('grace.tester@make-it-all.co.uk', @pw, TRUE, 'Grace', 'Taylor', 'team_member', '["Frontend","JavaScript","React","Node.js"]', '/default-avatar.png'),
('henry.analyst@make-it-all.co.uk', @pw, TRUE, 'Henry', 'Anderson', 'team_member', '["SQL","Python","Backend","API Design"]', '/default-avatar.png');

-- =============================================
-- PROJECTS
-- =============================================
INSERT IGNORE INTO projects (project_name, description, status, deadline, created_by, team_leader_id) VALUES
('Website Redesign', 'Complete overhaul of the company website with new branding and improved UX', 'active', '2025-12-15', 1, 2),
('Mobile App Development', 'Native mobile app for iOS and Android platforms', 'active', '2025-11-30', 1, 2),
('Internal Tools Upgrade', 'Modernize internal dashboards and reporting tools', 'active', '2026-01-31', 1, 2);

-- =============================================
-- PROJECT MEMBERS
-- =============================================
-- Get user IDs (adjust if auto-increment differs)
INSERT IGNORE INTO project_members (project_id, user_id, project_role) VALUES
-- Website Redesign (project 1)
(1, 2, 'team_leader'),
(1, 3, 'member'),
(1, 6, 'member'),
(1, 7, 'member'),
-- Mobile App Development (project 2)
(2, 2, 'team_leader'),
(2, 5, 'member'),
(2, 6, 'member'),
(2, 8, 'member'),
-- Internal Tools (project 3)
(3, 2, 'team_leader'),
(3, 3, 'member'),
(3, 5, 'member'),
(3, 8, 'member');

-- =============================================
-- TASKS
-- =============================================
INSERT IGNORE INTO tasks (task_name, description, project_id, status, priority, deadline, created_by, created_date) VALUES
-- Website Redesign tasks
('Design homepage mockup', 'Create Figma mockups for the new homepage design', 1, 'completed', 'high', '2025-10-30', 1, '2025-09-15'),
('Implement navigation bar', 'Build responsive navbar component with dropdown menus', 1, 'in_progress', 'high', '2025-11-10', 1, '2025-09-20'),
('Setup CI/CD pipeline', 'Configure GitHub Actions for automated testing and deployment', 1, 'to_do', 'medium', '2025-11-20', 1, '2025-09-25'),
('Write unit tests for auth', 'Unit tests for login, register, and session management', 1, 'to_do', 'medium', '2025-11-25', 1, '2025-10-01'),
('Redesign footer section', 'Update footer with new links and company information', 1, 'in_progress', 'low', '2025-11-15', 1, '2025-10-05'),
-- Mobile App tasks
('Setup React Native project', 'Initialize project with proper folder structure', 2, 'completed', 'high', '2025-10-15', 1, '2025-09-10'),
('Build login screen', 'Implement login UI with email/password fields', 2, 'in_progress', 'high', '2025-11-05', 1, '2025-09-18'),
('API integration layer', 'Create API service layer for backend communication', 2, 'to_do', 'high', '2025-11-20', 1, '2025-09-22'),
('Push notifications', 'Implement push notification system for iOS and Android', 2, 'to_do', 'medium', '2025-12-01', 1, '2025-10-01'),
-- Internal Tools tasks
('Dashboard wireframes', 'Design wireframes for the new analytics dashboard', 3, 'completed', 'medium', '2025-10-25', 1, '2025-09-12'),
('Reporting API endpoints', 'Build REST API for generating reports', 3, 'in_progress', 'high', '2025-11-30', 1, '2025-09-28'),
('Data migration script', 'Write script to migrate data from old system', 3, 'to_do', 'high', '2025-12-15', 1, '2025-10-10');

-- =============================================
-- TASK ASSIGNMENTS
-- =============================================
INSERT IGNORE INTO task_assignments (task_id, user_id, assigned_by, user_status) VALUES
-- Website Redesign assignments
(1, 6, 1, 'completed'),   -- Frank: Design homepage
(2, 3, 1, 'working'),     -- Carol: Implement navbar
(2, 7, 1, 'working'),     -- Grace: Implement navbar (shared)
(3, 5, 1, 'not_started'), -- Emma: CI/CD pipeline
(4, 3, 1, 'not_started'), -- Carol: Unit tests (solo)
(5, 6, 1, 'working'),     -- Frank: Redesign footer
-- Mobile App assignments
(6, 5, 1, 'completed'),   -- Emma: Setup React Native
(7, 6, 1, 'working'),     -- Frank: Build login screen
(8, 8, 1, 'not_started'), -- Henry: API integration
(9, 5, 1, 'not_started'), -- Emma: Push notifications
-- Internal Tools assignments
(10, 8, 1, 'completed'),  -- Henry: Dashboard wireframes
(11, 8, 1, 'working'),    -- Henry: Reporting API
(11, 3, 1, 'working'),    -- Carol: Reporting API (shared)
(12, 5, 1, 'not_started');-- Emma: Data migration

-- =============================================
-- KNOWLEDGE BASE TOPICS
-- =============================================
INSERT IGNORE INTO kb_topics (topic_name, description, created_by) VALUES
('General Discussion', 'Open discussions about company and team topics', 1),
('Technical Help', 'Ask for help with technical issues and bugs', 1),
('Best Practices', 'Share and discuss development best practices', 1),
('Project Updates', 'Updates and announcements about ongoing projects', 1);

-- =============================================
-- KNOWLEDGE BASE POSTS
-- =============================================
INSERT IGNORE INTO kb_posts (title, content, topic_id, author_id, tags, like_count, comment_count) VALUES
('How to setup the development environment', 'Step 1: Clone the repository\nStep 2: Run npm install\nStep 3: Configure your .env file\nStep 4: Start the dev server with npm run dev\n\nMake sure you have Node.js v18+ installed.', 2, 3, '["setup","development","guide"]', 5, 2),
('Git branching strategy', 'We use the following branching strategy:\n- main: production-ready code\n- develop: integration branch\n- feature/*: new features\n- hotfix/*: urgent production fixes\n\nAlways create a PR and get at least one review before merging.', 3, 2, '["git","branching","workflow"]', 8, 1),
('Welcome to Make-It-All!', 'Hello everyone! This is our new knowledge base platform. Feel free to ask questions, share knowledge, and help each other out.\n\nLets make this a great resource for our team.', 1, 1, '["welcome","announcement"]', 12, 3),
('React component best practices', 'Here are some tips for writing clean React components:\n\n1. Keep components small and focused\n2. Use custom hooks for shared logic\n3. Prefer composition over inheritance\n4. Use TypeScript for type safety\n5. Write tests for critical components', 3, 3, '["react","frontend","best-practices"]', 3, 0),
('Docker setup for local development', 'To run the app locally with Docker:\n\n```\ndocker-compose up -d\n```\n\nThis will start MySQL, PHP, and Apache containers. Access the app at http://localhost:8080', 2, 5, '["docker","devops","setup"]', 6, 1);

-- =============================================
-- KNOWLEDGE BASE COMMENTS
-- =============================================
INSERT IGNORE INTO kb_comments (post_id, author_id, content) VALUES
(1, 5, 'Great guide! I would also recommend using nvm to manage Node.js versions.'),
(1, 2, 'Thanks Carol, this helped me set up quickly.'),
(2, 3, 'Should we also use semantic commit messages?'),
(3, 3, 'Excited to use this platform!'),
(3, 5, 'Looking forward to sharing DevOps knowledge here.'),
(3, 6, 'This is great, thanks for setting it up!'),
(5, 3, 'This is super helpful for getting started quickly.');

-- =============================================
-- PERSONAL TASKS (for Home page testing)
-- =============================================
INSERT IGNORE INTO personal_tasks (user_id, title, description, priority, status, due_date) VALUES
(3, 'Review PR #45', 'Check the navbar component pull request', 'high', 'pending', '2025-11-05'),
(3, 'Update documentation', 'Add API endpoint documentation to README', 'medium', 'pending', '2025-11-10'),
(3, 'Team standup notes', 'Prepare notes for tomorrow morning standup', 'low', 'completed', '2025-10-28'),
(2, 'Sprint planning', 'Prepare user stories for next sprint', 'high', 'pending', '2025-11-03'),
(2, 'Code review training', 'Read article on effective code reviews', 'low', 'pending', '2025-11-15'),
(5, 'AWS certification study', 'Complete module 3 of AWS Solutions Architect course', 'medium', 'pending', '2025-11-20');
```

---

## Testing Assignments

### Nicole - Testing Areas: Members, Signin/Signout, Settings, Employee Directory, Progress

| # | Feature | Test Case | Account to Use | Expected Result |
|---|---------|-----------|----------------|-----------------|
| 1 | **Sign In** | Login with valid credentials | carol.dev@make-it-all.co.uk / password123 | Successfully logged in, redirected to projects |
| 2 | **Sign In** | Login with invalid password | carol.dev@make-it-all.co.uk / wrongpassword | Error message shown |
| 3 | **Sign In** | Login as manager | alice.manager@make-it-all.co.uk / password123 | Home page with manager dashboard visible |
| 4 | **Sign In** | Login as team leader | bob.lead@make-it-all.co.uk / password123 | Home page with team leader view |
| 5 | **Sign Out** | Click logout from any page | Any account | Redirected to login page, session cleared |
| 6 | **Settings** | View profile information | carol.dev@make-it-all.co.uk | Shows name, email, role correctly |
| 7 | **Settings** | Update profile picture | carol.dev@make-it-all.co.uk | New avatar shown across all pages |
| 8 | **Settings** | Change password | carol.dev@make-it-all.co.uk | Old password required, new password works on next login |
| 9 | **Employee Directory** | View all employees | alice.manager@make-it-all.co.uk | All 6+ employees shown in card grid |
| 10 | **Employee Directory** | Search for employee | alice.manager@make-it-all.co.uk | Search "Carol" shows Carol Williams |
| 11 | **Employee Directory** | Filter by specialties | alice.manager@make-it-all.co.uk | Filtering by "Frontend" shows Carol, Grace, Frank |
| 12 | **Employee Directory** | Click employee card | alice.manager@make-it-all.co.uk | Opens employee profile page |
| 13 | **Employee Directory** | Select mode + Add to Project | alice.manager@make-it-all.co.uk | Select Carol, Grace -> Add to Project -> Select project -> Success |
| 14 | **Employee Profile** | View profile with projects/stats | alice.manager@make-it-all.co.uk | Shows specialties, projects list, task chart |
| 15 | **Members Tab** | View project members | carol.dev@make-it-all.co.uk | Members tab shows all project members with cards |
| 16 | **Members Tab** | Non-manager cannot remove | carol.dev@make-it-all.co.uk | No "Remove" button visible on cards |
| 17 | **Members Tab** | Manager can remove member | alice.manager@make-it-all.co.uk | Remove button visible, shows task warning, member removed |
| 18 | **Members Tab** | Remove member with solo task | alice.manager@make-it-all.co.uk | Warning shows solo tasks will become unassigned |
| 19 | **Members Tab** | Re-add removed member | alice.manager@make-it-all.co.uk | Go to Employee Directory -> Select employee -> Add to project -> Success |
| 20 | **Progress** | View progress as team member | carol.dev@make-it-all.co.uk | Progress bar, countdown timer, upcoming deadlines |
| 21 | **Progress** | Click task in deadlines | carol.dev@make-it-all.co.uk | Task modal with Date Assigned, deadline, status |
| 22 | **Progress** | View progress as manager | alice.manager@make-it-all.co.uk | Manager progress view with team progress bars, Gantt chart |

### Sara - Testing Areas: Knowledge Base, Resources, Home, KanBan, Project Overview

| # | Feature | Test Case | Account to Use | Expected Result |
|---|---------|-----------|----------------|-----------------|
| 1 | **Home** | View manager dashboard | alice.manager@make-it-all.co.uk | Project health cards, Create Project button visible |
| 2 | **Home** | View team leader dashboard | bob.lead@make-it-all.co.uk | Project cards, employee dropdown, to-do list |
| 3 | **Home** | View team member dashboard | carol.dev@make-it-all.co.uk | Assigned projects visible |
| 4 | **Home** | Create new project (manager) | alice.manager@make-it-all.co.uk | Create Project form -> fill details -> success |
| 5 | **Project Overview** | View all projects | carol.dev@make-it-all.co.uk | Active/archived projects shown in card grid |
| 6 | **Project Overview** | Search projects | carol.dev@make-it-all.co.uk | Search "Website" shows Website Redesign |
| 7 | **Project Overview** | Click project card | carol.dev@make-it-all.co.uk | Opens project Kanban board |
| 8 | **KanBan Board** | View task columns | carol.dev@make-it-all.co.uk | 4 columns: To Do, In Progress, Review, Completed |
| 9 | **KanBan Board** | Click task card | carol.dev@make-it-all.co.uk | Task detail modal with title, priority, deadline, Date Assigned |
| 10 | **KanBan Board** | Mark task complete | carol.dev@make-it-all.co.uk | Task moves from current column to Review |
| 11 | **KanBan Board** | Drag task between columns (manager) | alice.manager@make-it-all.co.uk | Task moves to new status |
| 12 | **KanBan Board** | Add new task (manager) | alice.manager@make-it-all.co.uk | Click "+" in column -> Assign Task form |
| 13 | **KanBan Board** | Edit task via 3-dot menu (manager) | alice.manager@make-it-all.co.uk | Three-dot menu -> Edit Task -> form opens |
| 14 | **KanBan Board** | Delete task (manager) | alice.manager@make-it-all.co.uk | Three-dot menu -> Delete -> confirm -> task removed |
| 15 | **KanBan Board** | Close project (manager) | alice.manager@make-it-all.co.uk | Close Project button with archive icon -> confirm -> archived |
| 16 | **Resources** | View project resources | carol.dev@make-it-all.co.uk | Project contacts, details, uploaded files |
| 17 | **Resources** | Upload file (manager/leader) | bob.lead@make-it-all.co.uk | Choose file -> Upload -> appears in file list |
| 18 | **Resources** | Download file | carol.dev@make-it-all.co.uk | Click Download -> file downloads |
| 19 | **Resources** | Delete file (manager/leader) | bob.lead@make-it-all.co.uk | Click delete -> confirm -> file removed |
| 20 | **Knowledge Base** | View all topics | carol.dev@make-it-all.co.uk | Topics listed: General, Technical, Best Practices, Updates |
| 21 | **Knowledge Base** | View posts in topic | carol.dev@make-it-all.co.uk | Posts shown with author, date, like count |
| 22 | **Knowledge Base** | Create new post | carol.dev@make-it-all.co.uk | Fill title, content, select topic -> submit -> post appears |
| 23 | **Knowledge Base** | Like a post | carol.dev@make-it-all.co.uk | Click thumbs-up -> count increases, button turns orange |
| 24 | **Knowledge Base** | Unlike a post | carol.dev@make-it-all.co.uk | Click thumbs-up again -> count decreases, button resets |
| 25 | **Knowledge Base** | Add comment | carol.dev@make-it-all.co.uk | Type reply -> submit -> comment appears |
| 26 | **Knowledge Base** | Mark as solved | emma.specialist@make-it-all.co.uk | Mark solved button -> green badge appears |
| 27 | **Knowledge Base** | Search posts | carol.dev@make-it-all.co.uk | Search "docker" -> Docker setup post appears |
| 28 | **Knowledge Base** | Links are black (not blue) | Any account | All hyperlinks appear black, turn orange on hover |

---

## Breadcrumbs Verification (Both testers)

Check that breadcrumbs appear on every page:

| Page | Expected Breadcrumb |
|------|-------------------|
| Project Tasks (KanBan) | Projects > [Project Name] |
| Progress | Projects > [Project Name] |
| Resources | Projects > [Project Name] |
| Members | Projects > [Project Name] |
| Knowledge Base | Knowledge Base |
| KB Topic | Knowledge Base > [Topic Name] |
| KB Post | Knowledge Base > [Topic Name] > Post |

---

## Notes

- The default password for all test accounts is `password123`
- Dave Brown (dave.dev@) is NOT registered - use this to test the registration/sign-up flow
- Alice (manager) has full access to everything
- Bob (team_leader) can manage tasks on his projects but cannot manage employees
- Carol/Frank/Grace/Henry (team_members) have limited access - can view and complete tasks
- Emma (technical_specialist) can mark KB posts as solved
