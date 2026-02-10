-- ============================================================================
-- Team 14 - Productivity & Knowledge Management System
-- Database Schema + Test Data - Complete Setup
-- ============================================================================
-- Author: Simi Olusola (Backend Lead)
-- Description: Run this single file to create all tables, triggers, and test data.
-- All passwords are: Password123!
-- ============================================================================

-- Drop existing tables (in reverse order of dependencies)
DROP TABLE IF EXISTS user_personal_tasks;
DROP TABLE IF EXISTS training_recommendations;
DROP TABLE IF EXISTS task_history;
DROP TABLE IF EXISTS project_resources;
DROP TABLE IF EXISTS kb_post_likes;
DROP TABLE IF EXISTS kb_comments;
DROP TABLE IF EXISTS kb_posts;
DROP TABLE IF EXISTS kb_topics;
DROP TABLE IF EXISTS task_assignments;
DROP TABLE IF EXISTS tasks;
DROP TABLE IF EXISTS project_members;
DROP TABLE IF EXISTS projects;
DROP TABLE IF EXISTS users;

-- ============================================================================
-- TABLE 1: USERS
-- ============================================================================

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NULL COMMENT 'NULL for non-registered employees',
    is_registered BOOLEAN DEFAULT FALSE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    profile_picture VARCHAR(255) DEFAULT '/default-avatar.png',
    role ENUM('manager', 'team_leader', 'team_member', 'technical_specialist') NOT NULL DEFAULT 'team_member',
    department VARCHAR(100),
    specialties TEXT COMMENT 'JSON array: ["Frontend", "React", "UX Design"]',
    manager_id INT NULL COMMENT 'Self-referencing FK - who manages this user',
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (manager_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_manager (manager_id),
    INDEX idx_is_registered (is_registered),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- TABLE 2: PROJECTS
-- ============================================================================

CREATE TABLE projects (
    project_id INT AUTO_INCREMENT PRIMARY KEY,
    project_name VARCHAR(255) NOT NULL,
    description TEXT,
    created_by INT NOT NULL COMMENT 'Manager who created the project',
    team_leader_id INT NULL COMMENT 'Assigned team leader',
    start_date DATE NOT NULL,
    deadline DATE NOT NULL,
    estimated_completion_date DATE NULL COMMENT 'Calculated based on velocity',
    completed_date DATE NULL,
    status ENUM('planning', 'active', 'on_hold', 'completed', 'archived') DEFAULT 'active',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    completion_percentage DECIMAL(5,2) DEFAULT 0.00,
    overdue_tasks_count INT DEFAULT 0,
    resource_level ENUM('sufficient', 'tight', 'under_resourced') DEFAULT 'sufficient',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    archived_at TIMESTAMP NULL,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    FOREIGN KEY (team_leader_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_created_by (created_by),
    INDEX idx_team_leader (team_leader_id),
    INDEX idx_deadline (deadline),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- TABLE 3: PROJECT_MEMBERS
-- ============================================================================

CREATE TABLE project_members (
    project_member_id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    project_role ENUM('team_leader', 'member') DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    left_at TIMESTAMP NULL COMMENT 'Soft delete - set when member leaves project',
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_project_user (project_id, user_id),
    INDEX idx_project (project_id),
    INDEX idx_user (user_id),
    INDEX idx_left_at (left_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- TABLE 4: TASKS
-- ============================================================================

CREATE TABLE tasks (
    task_id INT AUTO_INCREMENT PRIMARY KEY,
    task_name VARCHAR(255) NOT NULL,
    description TEXT,
    project_id INT NOT NULL,
    created_by INT NOT NULL COMMENT 'Manager/Team Leader who created task',
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deadline TIMESTAMP NOT NULL,
    started_date TIMESTAMP NULL COMMENT 'Set when moved to In Progress',
    completed_date TIMESTAMP NULL COMMENT 'Set when moved to Completed',
    status ENUM('to_do', 'in_progress', 'review', 'completed') DEFAULT 'to_do',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    estimated_hours DECIMAL(5,2) NULL,
    actual_hours DECIMAL(5,2) NULL,
    reopened_count INT DEFAULT 0 COMMENT 'Times task was reopened after completion',
    last_reopened_date TIMESTAMP NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_project (project_id),
    INDEX idx_status (status),
    INDEX idx_deadline (deadline),
    INDEX idx_created_by (created_by),
    INDEX idx_created_date (created_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- TABLE 5: TASK_ASSIGNMENTS
-- ============================================================================

CREATE TABLE task_assignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    assigned_by INT NOT NULL COMMENT 'Who assigned this task',
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_status ENUM('not_started', 'working', 'completed') DEFAULT 'not_started',
    user_completed_at TIMESTAMP NULL,
    FOREIGN KEY (task_id) REFERENCES tasks(task_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    UNIQUE KEY unique_task_user (task_id, user_id),
    INDEX idx_task (task_id),
    INDEX idx_user (user_id),
    INDEX idx_assigned_by (assigned_by),
    INDEX idx_user_status (user_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- TABLE 6: KB_TOPICS
-- ============================================================================

CREATE TABLE kb_topics (
    topic_id INT AUTO_INCREMENT PRIMARY KEY,
    topic_name VARCHAR(255) NOT NULL,
    description TEXT,
    icon VARCHAR(50) COMMENT 'Icon class or emoji',
    created_by INT NOT NULL,
    post_count INT DEFAULT 0 COMMENT 'Denormalized count for performance',
    is_public BOOLEAN DEFAULT TRUE,
    restricted_to_role ENUM('all', 'manager', 'team_leader', 'technical_specialist') DEFAULT 'all',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_created_by (created_by),
    INDEX idx_is_public (is_public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- TABLE 7: KB_POSTS
-- ============================================================================

CREATE TABLE kb_posts (
    post_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    topic_id INT NOT NULL,
    author_id INT NOT NULL,
    tags JSON COMMENT 'Array: ["bug-fix", "authentication", "frontend"]',
    view_count INT DEFAULT 0,
    like_count INT DEFAULT 0 COMMENT 'Denormalized count of likes',
    comment_count INT DEFAULT 0 COMMENT 'Denormalized for performance',
    is_solved BOOLEAN DEFAULT FALSE,
    is_edited BOOLEAN DEFAULT FALSE,
    last_edited_at TIMESTAMP NULL,
    last_edited_by INT NULL COMMENT 'May differ from author if specialist edited',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (topic_id) REFERENCES kb_topics(topic_id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES users(user_id) ON DELETE RESTRICT,
    FOREIGN KEY (last_edited_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_topic (topic_id),
    INDEX idx_author (author_id),
    INDEX idx_created_at (created_at),
    INDEX idx_is_solved (is_solved)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- TABLE 7b: KB_POST_LIKES
-- ============================================================================

CREATE TABLE kb_post_likes (
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


-- ============================================================================
-- TABLE 8: KB_COMMENTS
-- ============================================================================

CREATE TABLE kb_comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    content TEXT NOT NULL,
    post_id INT NOT NULL,
    author_id INT NOT NULL,
    is_edited BOOLEAN DEFAULT FALSE,
    last_edited_at TIMESTAMP NULL,
    last_edited_by INT NULL COMMENT 'May differ from author if specialist edited',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES kb_posts(post_id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES users(user_id) ON DELETE RESTRICT,
    FOREIGN KEY (last_edited_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_post (post_id),
    INDEX idx_author (author_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- TABLE 9: PROJECT_RESOURCES
-- ============================================================================

CREATE TABLE project_resources (
    resource_id INT AUTO_INCREMENT PRIMARY KEY,
    file_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(50) NOT NULL COMMENT 'pdf, docx, xlsx, png, jpg',
    file_size INT NOT NULL COMMENT 'Size in bytes',
    file_path VARCHAR(500) NOT NULL COMMENT 'Storage path on server',
    project_id INT NOT NULL,
    uploaded_by INT NOT NULL,
    description TEXT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_project (project_id),
    INDEX idx_uploaded_by (uploaded_by),
    INDEX idx_file_type (file_type),
    INDEX idx_uploaded_at (uploaded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- TABLE 10: TASK_HISTORY
-- ============================================================================

CREATE TABLE task_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    changed_by INT NOT NULL,
    action ENUM('created', 'status_changed', 'assigned', 'unassigned', 'updated', 'deleted') NOT NULL,
    old_status ENUM('to_do', 'in_progress', 'review', 'completed') NULL,
    new_status ENUM('to_do', 'in_progress', 'review', 'completed') NULL,
    notes TEXT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(task_id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_task (task_id),
    INDEX idx_changed_by (changed_by),
    INDEX idx_changed_at (changed_at),
    INDEX idx_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- TABLE 11: TRAINING_RECOMMENDATIONS
-- ============================================================================

CREATE TABLE training_recommendations (
    recommendation_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    project_id INT NULL COMMENT 'NULL if company-wide recommendation',
    recommendation_type ENUM('completion_rate', 'overdue_tasks', 'avg_time', 'quality', 'technical') NOT NULL,
    recommendation_text TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    status ENUM('pending', 'acknowledged', 'in_progress', 'completed', 'dismissed') DEFAULT 'pending',
    dismissed_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_project (project_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- TABLE 12: USER_PERSONAL_TASKS
-- ============================================================================

CREATE TABLE user_personal_tasks (
    personal_task_id INT AUTO_INCREMENT PRIMARY KEY,
    task_name VARCHAR(255) NOT NULL,
    description TEXT,
    user_id INT NOT NULL,
    related_project_id INT NULL COMMENT 'Can tag with project for organization',
    deadline TIMESTAMP NULL,
    is_completed BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (related_project_id) REFERENCES projects(project_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_related_project (related_project_id),
    INDEX idx_is_completed (is_completed),
    INDEX idx_deadline (deadline)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- TRIGGERS
-- ============================================================================

DELIMITER //

CREATE TRIGGER update_project_completion
AFTER UPDATE ON tasks
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        UPDATE projects
        SET completion_percentage = (
            SELECT ROUND(
                (COUNT(CASE WHEN status = 'completed' THEN 1 END) * 100.0) /
                NULLIF(COUNT(*), 0),
                2
            )
            FROM tasks
            WHERE project_id = NEW.project_id
        )
        WHERE project_id = NEW.project_id;
    END IF;
END//

CREATE TRIGGER update_overdue_count
AFTER UPDATE ON tasks
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status OR OLD.deadline != NEW.deadline THEN
        UPDATE projects
        SET overdue_tasks_count = (
            SELECT COUNT(*)
            FROM tasks
            WHERE project_id = NEW.project_id
              AND deadline < NOW()
              AND status != 'completed'
        ),
        resource_level = CASE
            WHEN (
                SELECT COUNT(*)
                FROM tasks
                WHERE project_id = NEW.project_id
                  AND deadline < NOW()
                  AND status != 'completed'
            ) > 3 THEN 'under_resourced'
            WHEN (
                SELECT COUNT(*)
                FROM tasks
                WHERE project_id = NEW.project_id
                  AND deadline < NOW()
                  AND status != 'completed'
            ) BETWEEN 1 AND 3 THEN 'tight'
            ELSE 'sufficient'
        END
        WHERE project_id = NEW.project_id;
    END IF;
END//

CREATE TRIGGER update_comment_count_insert
AFTER INSERT ON kb_comments
FOR EACH ROW
BEGIN
    UPDATE kb_posts
    SET comment_count = comment_count + 1
    WHERE post_id = NEW.post_id;
END//

CREATE TRIGGER update_comment_count_delete
AFTER DELETE ON kb_comments
FOR EACH ROW
BEGIN
    UPDATE kb_posts
    SET comment_count = comment_count - 1
    WHERE post_id = OLD.post_id;
END//

CREATE TRIGGER update_topic_post_count_insert
AFTER INSERT ON kb_posts
FOR EACH ROW
BEGIN
    UPDATE kb_topics
    SET post_count = post_count + 1
    WHERE topic_id = NEW.topic_id;
END//

CREATE TRIGGER update_topic_post_count_delete
AFTER DELETE ON kb_posts
FOR EACH ROW
BEGIN
    UPDATE kb_topics
    SET post_count = post_count - 1
    WHERE topic_id = OLD.topic_id;
END//

CREATE TRIGGER log_task_status_change
AFTER UPDATE ON tasks
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO task_history (task_id, changed_by, action, old_status, new_status)
        VALUES (NEW.task_id, NEW.created_by, 'status_changed', OLD.status, NEW.status);
    END IF;
END//

DELIMITER ;


-- ============================================================================
-- ============================================================================
--                          TEST DATA
-- ============================================================================
-- ============================================================================
-- 10 users, 8 projects, 63 tasks, knowledge base, personal tasks
-- All passwords: Password123!
-- Dates centred around: 9 February 2026
-- ============================================================================

SET @pw = '$2y$12$HxjFRWlMRdll/4IZR4ur4eIenbGY.lV4DzIhSQNaEU3zLy2Qq5M1a';

-- =============================================
-- USERS (IDs 1-10)
-- =============================================

INSERT INTO users (email, password_hash, is_registered, first_name, last_name, role, specialties, profile_picture) VALUES
('alice.manager@make-it-all.co.uk',    @pw,  TRUE,  'Alice', 'Johnson',  'manager',              '["Project Management","Strategy","Leadership","Budgeting"]',                   '/default-avatar.png'),
('bob.lead@make-it-all.co.uk',         @pw,  TRUE,  'Bob',   'Smith',    'team_leader',          '["Backend","Python","SQL","API Design","Django"]',                             '/default-avatar.png'),
('carol.dev@make-it-all.co.uk',        @pw,  TRUE,  'Carol', 'Williams', 'team_member',          '["Frontend","React","CSS","JavaScript","TypeScript"]',                         '/default-avatar.png'),
('dave.dev@make-it-all.co.uk',         NULL, FALSE,  'Dave',  'Brown',   'team_member',          '["Backend","Node.js","MongoDB","Express"]',                                    '/default-avatar.png'),
('emma.specialist@make-it-all.co.uk',  @pw,  TRUE,  'Emma',  'Davis',   'technical_specialist',  '["DevOps","AWS","Docker","CI/CD","Kubernetes"]',                               '/default-avatar.png'),
('frank.designer@make-it-all.co.uk',   @pw,  TRUE,  'Frank', 'Miller',  'team_member',          '["UI Design","Figma","Prototyping","CSS","Adobe XD"]',                         '/default-avatar.png'),
('grace.tester@make-it-all.co.uk',     @pw,  TRUE,  'Grace', 'Taylor',  'team_member',          '["Frontend","JavaScript","React","Node.js","Testing"]',                        '/default-avatar.png'),
('henry.analyst@make-it-all.co.uk',    @pw,  TRUE,  'Henry', 'Anderson','team_member',          '["SQL","Python","Backend","API Design","Data Analysis"]',                      '/default-avatar.png'),
('iris.pm@make-it-all.co.uk',          @pw,  TRUE,  'Iris',  'Chen',    'team_leader',          '["Project Management","Agile","Scrum","API Design","Documentation"]',          '/default-avatar.png'),
('jack.security@make-it-all.co.uk',    @pw,  TRUE,  'Jack',  'Wilson',  'technical_specialist',  '["Security","Penetration Testing","OAuth","Encryption","Compliance"]',         '/default-avatar.png');

UPDATE users SET manager_id = 1 WHERE user_id IN (2, 3, 4, 5, 6, 7, 8, 9, 10);

-- =============================================
-- PROJECTS (IDs 1-8)
-- =============================================

INSERT INTO projects (project_name, description, status, start_date, deadline, priority, created_by, team_leader_id) VALUES
('Website Redesign',           'Complete overhaul of the company website with new branding, improved UX, and modern tech stack. Includes responsive design for mobile and tablet.',                       'active',   '2025-12-01', '2026-03-15', 'high',   1, 2),
('Mobile App Development',     'Native mobile app for iOS and Android platforms with offline capabilities, push notifications, and biometric authentication.',                                           'active',   '2025-12-01', '2026-02-28', 'high',   1, 2),
('Internal Tools Upgrade',     'Modernize internal dashboards and reporting tools, integrate with Salesforce and analytics platforms.',                                                                   'active',   '2025-12-15', '2026-04-30', 'medium', 1, 2),
('REST API V2',                'Design and implement RESTful API v2 with improved authentication, rate limiting, and comprehensive documentation.',                                                       'active',   '2025-12-15', '2026-05-31', 'high',   1, 9),
('Cloud Migration',            'Migrate on-premise infrastructure to AWS cloud with zero downtime and improved scalability.',                                                                             'active',   '2025-12-15', '2026-06-15', 'high',   1, 5),
('Security Audit',             'Comprehensive security audit of all systems, penetration testing, and implementation of security improvements.',                                                           'active',   '2025-12-15', '2026-03-30', 'high',   1, 10),
('E-commerce Platform',        'Build new e-commerce platform with payment processing, inventory management, and customer portal.',                                                                       'archived', '2025-06-01', '2025-08-30', 'medium', 1, 9),
('Legacy System Decommission', 'Phase out old CRM system and migrate data to new platform.',                                                                                                             'archived', '2025-09-01', '2025-11-15', 'low',    1, 2);

UPDATE projects SET archived_at = '2025-09-01 00:00:00' WHERE project_id = 7;
UPDATE projects SET archived_at = '2025-11-20 00:00:00' WHERE project_id = 8;

-- =============================================
-- PROJECT MEMBERS
-- =============================================

INSERT INTO project_members (project_id, user_id, project_role) VALUES
-- Website Redesign (project 1) - 5 members
(1, 2,  'team_leader'),
(1, 3,  'member'),
(1, 6,  'member'),
(1, 7,  'member'),
(1, 10, 'member'),
-- Mobile App Development (project 2) - 5 members
(2, 2, 'team_leader'),
(2, 3, 'member'),
(2, 5, 'member'),
(2, 6, 'member'),
(2, 8, 'member'),
-- Internal Tools Upgrade (project 3) - 4 members
(3, 2, 'team_leader'),
(3, 3, 'member'),
(3, 5, 'member'),
(3, 8, 'member'),
-- REST API V2 (project 4) - 6 members
(4, 9,  'team_leader'),
(4, 2,  'member'),
(4, 8,  'member'),
(4, 10, 'member'),
(4, 3,  'member'),
(4, 7,  'member'),
-- Cloud Migration (project 5) - 3 members
(5, 5,  'team_leader'),
(5, 2,  'member'),
(5, 10, 'member'),
-- Security Audit (project 6) - 4 members
(6, 10, 'team_leader'),
(6, 5,  'member'),
(6, 2,  'member'),
(6, 8,  'member'),
-- E-commerce Platform (archived, project 7) - 3 members
(7, 9, 'team_leader'),
(7, 3, 'member'),
(7, 8, 'member'),
-- Legacy System Decommission (archived, project 8) - 2 members
(8, 2, 'team_leader'),
(8, 8, 'member');

-- =============================================
-- TASKS - Website Redesign (Project 1) - IDs 1-13
-- =============================================

INSERT INTO tasks (task_name, description, project_id, status, priority, deadline, created_by, created_date) VALUES
-- Completed (deadlines in Jan 2026 - already past)
('Design homepage mockup',     'Create Figma mockups for the new homepage design with hero section, feature highlights, and testimonials',                         1, 'completed',   'high',   '2026-01-30', 1, '2025-12-15'),
('Brand guidelines document',  'Finalize brand guidelines including color palette, typography, logo usage, and tone of voice',                                     1, 'completed',   'high',   '2026-01-25', 1, '2025-12-10'),
('User research interviews',   'Conduct 10 user interviews to understand pain points with current website',                                                        1, 'completed',   'medium', '2026-01-20', 1, '2025-12-08'),
('Wireframes for all pages',   'Create low-fidelity wireframes for homepage, about, services, contact, and blog pages',                                            1, 'completed',   'high',   '2026-01-28', 1, '2025-12-12'),
-- In Progress (deadlines around now - Feb 2026)
('Implement navigation bar',   'Build responsive navbar component with dropdown menus, mobile hamburger menu, and accessibility features',                         1, 'in_progress', 'high',   '2026-02-10', 1, '2025-12-20'),
('Redesign footer section',    'Update footer with new links, social media icons, newsletter signup, and company information',                                      1, 'in_progress', 'low',    '2026-02-15', 1, '2026-01-05'),
('Accessibility compliance',   'Ensure WCAG 2.1 AA compliance across all pages, including keyboard navigation and screen reader support',                          1, 'in_progress', 'high',   '2026-02-25', 1, '2026-01-15'),
('Performance optimization',   'Optimize images, implement lazy loading, code splitting, and improve Core Web Vitals scores',                                      1, 'in_progress', 'medium', '2026-02-20', 1, '2026-01-18'),
-- To Do (deadlines in late Feb / Mar 2026)
('Setup CI/CD pipeline',       'Configure GitHub Actions for automated testing, linting, and deployment to staging and production',                                 1, 'to_do',       'medium', '2026-02-20', 1, '2025-12-25'),
('Write unit tests for auth',  'Unit tests for login, register, password reset, and session management with 90% coverage',                                         1, 'to_do',       'medium', '2026-02-25', 1, '2026-01-01'),
('Blog system implementation', 'Build blog CMS with markdown support, categories, tags, and SEO optimization',                                                     1, 'to_do',       'low',    '2026-03-01', 1, '2026-01-10'),
('Contact form backend',       'Implement contact form with spam protection, email notifications, and database logging',                                            1, 'to_do',       'medium', '2026-02-18', 1, '2026-01-12'),
('Analytics integration',      'Integrate Google Analytics 4 and configure custom events for conversion tracking',                                                  1, 'to_do',       'low',    '2026-02-22', 1, '2026-01-20');

-- =============================================
-- TASKS - Mobile App Development (Project 2) - IDs 14-25
-- =============================================

INSERT INTO tasks (task_name, description, project_id, status, priority, deadline, created_by, created_date) VALUES
-- Completed
('Setup React Native project',   'Initialize project with proper folder structure, ESLint, Prettier, and TypeScript configuration',                                2, 'completed',   'high',   '2026-01-15', 1, '2025-12-10'),
('Design system foundation',     'Create reusable UI components library with consistent styling and theming support',                                               2, 'completed',   'high',   '2026-01-22', 1, '2025-12-14'),
('Authentication flow design',   'Design user flows for login, registration, password reset, and biometric authentication',                                         2, 'completed',   'high',   '2026-01-28', 1, '2025-12-16'),
-- In Progress
('Build login screen',           'Implement login UI with email/password fields, social login buttons, and remember me option',                                     2, 'in_progress', 'high',   '2026-02-05', 1, '2025-12-18'),
('Offline mode implementation',  'Implement offline data persistence using AsyncStorage and sync when connection restores',                                          2, 'in_progress', 'high',   '2026-02-12', 1, '2026-01-01'),
('User profile screen',          'Build profile screen with avatar upload, personal info editing, and account settings',                                             2, 'in_progress', 'medium', '2026-02-08', 1, '2026-01-05'),
-- To Do
('API integration layer',        'Create API service layer for backend communication with retry logic and error handling',                                           2, 'to_do',       'high',   '2026-02-20', 1, '2025-12-22'),
('Push notifications',           'Implement push notification system for iOS and Android using Firebase Cloud Messaging',                                            2, 'to_do',       'medium', '2026-03-01', 1, '2026-01-01'),
('Biometric authentication',     'Add Face ID and fingerprint authentication support for iOS and Android',                                                           2, 'to_do',       'high',   '2026-02-15', 1, '2026-01-08'),
('App Store submission',          'Prepare app metadata, screenshots, privacy policy, and submit to App Store and Play Store',                                       2, 'to_do',       'high',   '2026-02-28', 1, '2026-01-20'),
('Deep linking',                  'Implement deep linking for seamless navigation from web to app',                                                                  2, 'to_do',       'low',    '2026-03-05', 1, '2026-01-15'),
('Crash reporting',               'Integrate Sentry for crash reporting and performance monitoring',                                                                  2, 'to_do',       'medium', '2026-02-18', 1, '2026-01-12');

-- =============================================
-- TASKS - Internal Tools Upgrade (Project 3) - IDs 26-34
-- =============================================

INSERT INTO tasks (task_name, description, project_id, status, priority, deadline, created_by, created_date) VALUES
-- Completed
('Dashboard wireframes',        'Design wireframes for the new analytics dashboard with KPI cards and charts',                                                      3, 'completed',   'medium', '2026-01-25', 1, '2025-12-12'),
('Database schema design',      'Design optimized database schema for reporting and analytics with proper indexing',                                                 3, 'completed',   'high',   '2026-01-30', 1, '2025-12-15'),
-- In Progress
('Reporting API endpoints',     'Build REST API endpoints for generating reports with filters, pagination, and export options',                                      3, 'in_progress', 'high',   '2026-02-28', 1, '2025-12-28'),
('Chart component library',     'Create reusable chart components using Chart.js for line, bar, and pie charts',                                                    3, 'in_progress', 'medium', '2026-02-15', 1, '2026-01-05'),
-- To Do
('Data migration script',       'Write Python script to migrate data from old system with data validation and error handling',                                       3, 'to_do',       'high',   '2026-03-15', 1, '2026-01-10'),
('Salesforce integration',      'Integrate with Salesforce API for real-time data sync',                                                                             3, 'to_do',       'high',   '2026-03-20', 1, '2026-01-12'),
('Export to Excel',             'Add functionality to export reports to Excel with formatting and charts',                                                            3, 'to_do',       'medium', '2026-03-10', 1, '2026-01-18'),
('Email report scheduling',    'Implement scheduled email reports with customizable frequency and recipients',                                                        3, 'to_do',       'low',    '2026-03-28', 1, '2026-01-22'),
('Dashboard personalization',  'Allow users to customize dashboard layout and save widget preferences',                                                               3, 'to_do',       'low',    '2026-04-15', 1, '2026-01-25');

-- =============================================
-- TASKS - REST API V2 (Project 4) - IDs 35-44
-- =============================================

INSERT INTO tasks (task_name, description, project_id, status, priority, deadline, created_by, created_date) VALUES
-- Completed
('API specification document',    'Write OpenAPI 3.0 specification with all endpoints, request/response schemas, and examples',                                     4, 'completed',   'high',   '2026-02-05', 1, '2025-12-20'),
('Authentication redesign',       'Design OAuth 2.0 + JWT authentication flow with refresh tokens',                                                                 4, 'completed',   'high',   '2026-02-08', 1, '2025-12-22'),
-- In Progress
('Rate limiting implementation',  'Implement rate limiting with Redis, different tiers for API keys',                                                                4, 'in_progress', 'high',   '2026-02-20', 1, '2026-01-01'),
('Versioning strategy',           'Implement API versioning via URL path with backward compatibility for v1',                                                        4, 'in_progress', 'medium', '2026-02-18', 1, '2026-01-05'),
('Error handling standardization','Standardize error responses with consistent structure and helpful messages',                                                       4, 'in_progress', 'high',   '2026-02-22', 1, '2026-01-08'),
-- To Do
('Webhook system',                'Build webhook system for real-time event notifications to client applications',                                                    4, 'to_do',       'medium', '2026-03-05', 1, '2026-01-10'),
('API documentation site',        'Create interactive API documentation using Swagger UI with code examples',                                                        4, 'to_do',       'high',   '2026-03-01', 1, '2026-01-12'),
('SDK for JavaScript',            'Develop official JavaScript/TypeScript SDK for the API',                                                                          4, 'to_do',       'medium', '2026-03-15', 1, '2026-01-15'),
('SDK for Python',                'Develop official Python SDK with async support',                                                                                  4, 'to_do',       'medium', '2026-03-20', 1, '2026-01-18'),
('Load testing',                  'Perform load testing with 10,000 concurrent users and optimize bottlenecks',                                                      4, 'to_do',       'high',   '2026-04-10', 1, '2026-01-20');

-- =============================================
-- TASKS - Cloud Migration (Project 5) - IDs 45-54
-- =============================================

INSERT INTO tasks (task_name, description, project_id, status, priority, deadline, created_by, created_date) VALUES
-- Completed
('AWS account setup',            'Setup AWS Organization, accounts for dev/staging/prod, and IAM policies',                                                          5, 'completed',   'high',   '2026-01-20', 1, '2025-12-15'),
('Infrastructure as Code',       'Write Terraform scripts for VPC, subnets, security groups, and load balancers',                                                   5, 'completed',   'high',   '2026-01-28', 1, '2025-12-18'),
-- In Progress
('Database migration plan',      'Create detailed plan for migrating PostgreSQL to RDS with minimal downtime',                                                       5, 'in_progress', 'high',   '2026-02-25', 1, '2026-01-01'),
('Docker containerization',      'Containerize all services and create Kubernetes deployment manifests',                                                              5, 'in_progress', 'high',   '2026-02-28', 1, '2026-01-05'),
('Monitoring setup',             'Setup CloudWatch, Prometheus, and Grafana for comprehensive monitoring',                                                            5, 'in_progress', 'medium', '2026-03-05', 1, '2026-01-10'),
-- To Do
('Load balancer configuration',  'Configure Application Load Balancer with health checks and SSL certificates',                                                      5, 'to_do',       'high',   '2026-03-10', 1, '2026-01-12'),
('Auto-scaling setup',           'Implement auto-scaling policies based on CPU and memory metrics',                                                                   5, 'to_do',       'high',   '2026-03-15', 1, '2026-01-15'),
('Backup strategy',              'Implement automated backups with retention policies and disaster recovery plan',                                                    5, 'to_do',       'high',   '2026-03-20', 1, '2026-01-18'),
('Cost optimization',            'Analyze and optimize AWS costs, implement Reserved Instances where applicable',                                                     5, 'to_do',       'medium', '2026-04-05', 1, '2026-01-20'),
('Documentation',                'Document architecture, deployment procedures, and troubleshooting guides',                                                          5, 'to_do',       'medium', '2026-04-15', 1, '2026-01-22');

-- =============================================
-- TASKS - Security Audit (Project 6) - IDs 55-63
-- =============================================

INSERT INTO tasks (task_name, description, project_id, status, priority, deadline, created_by, created_date) VALUES
-- Completed
('Security policy review',      'Review and update security policies for access control, data handling, and incident response',                                      6, 'completed',   'high',   '2026-01-25', 1, '2025-12-18'),
('Vulnerability scanning',      'Run automated vulnerability scans using OWASP ZAP and Nessus',                                                                     6, 'completed',   'high',   '2026-01-30', 1, '2025-12-20'),
-- In Progress
('Penetration testing',          'Conduct manual penetration testing of web applications and APIs',                                                                   6, 'in_progress', 'high',   '2026-02-20', 1, '2026-01-01'),
('Code security audit',          'Review codebase for security vulnerabilities, SQL injection, XSS, CSRF',                                                           6, 'in_progress', 'high',   '2026-02-25', 1, '2026-01-05'),
-- To Do
('Fix critical vulnerabilities', 'Address all critical and high severity vulnerabilities found during audit',                                                         6, 'to_do',       'high',   '2026-03-05', 1, '2026-01-08'),
('Implement 2FA',                'Add two-factor authentication support for all user accounts',                                                                       6, 'to_do',       'high',   '2026-03-10', 1, '2026-01-10'),
('Security training',            'Conduct security awareness training for all developers',                                                                            6, 'to_do',       'medium', '2026-03-15', 1, '2026-01-12'),
('Compliance documentation',     'Prepare documentation for SOC 2 and GDPR compliance',                                                                              6, 'to_do',       'high',   '2026-03-20', 1, '2026-01-15'),
('Incident response plan',       'Create comprehensive incident response plan with on-call rotation',                                                                 6, 'to_do',       'medium', '2026-03-28', 1, '2026-01-18');

-- =============================================
-- TASK ASSIGNMENTS
-- =============================================

INSERT INTO task_assignments (task_id, user_id, assigned_by, user_status) VALUES
-- Website Redesign (tasks 1-13)
(1, 6, 1, 'completed'),
(2, 6, 1, 'completed'),
(3, 3, 1, 'completed'),
(4, 6, 1, 'completed'),
(5, 3, 1, 'working'),
(5, 7, 1, 'working'),
(6, 6, 1, 'working'),
(7, 3, 1, 'working'),
(7, 10, 1, 'working'),
(8, 5, 1, 'working'),
(9, 5, 1, 'not_started'),
(10, 3, 1, 'not_started'),
(10, 7, 1, 'not_started'),
(11, 3, 1, 'not_started'),
(12, 2, 1, 'not_started'),
(13, 7, 1, 'not_started'),
-- Mobile App (tasks 14-25)
(14, 5, 1, 'completed'),
(15, 6, 1, 'completed'),
(16, 6, 1, 'completed'),
(17, 6, 1, 'working'),
(18, 5, 1, 'working'),
(18, 8, 1, 'working'),
(19, 3, 1, 'working'),
(20, 8, 1, 'not_started'),
(21, 5, 1, 'not_started'),
(22, 5, 1, 'not_started'),
(23, 6, 1, 'not_started'),
(24, 3, 1, 'not_started'),
(25, 5, 1, 'not_started'),
-- Internal Tools (tasks 26-34)
(26, 8, 1, 'completed'),
(27, 8, 1, 'completed'),
(28, 8, 1, 'working'),
(28, 3, 1, 'working'),
(29, 3, 1, 'working'),
(30, 8, 1, 'not_started'),
(31, 5, 1, 'not_started'),
(32, 8, 1, 'not_started'),
(33, 2, 1, 'not_started'),
(34, 3, 1, 'not_started'),
-- REST API V2 (tasks 35-44)
(35, 9, 1, 'completed'),
(36, 10, 1, 'completed'),
(37, 2, 1, 'working'),
(37, 8, 1, 'working'),
(38, 9, 1, 'working'),
(39, 2, 1, 'working'),
(40, 8, 1, 'not_started'),
(41, 9, 1, 'not_started'),
(42, 3, 1, 'not_started'),
(43, 7, 1, 'not_started'),
(44, 2, 1, 'not_started'),
-- Cloud Migration (tasks 45-54)
(45, 5, 1, 'completed'),
(46, 5, 1, 'completed'),
(47, 5, 1, 'working'),
(47, 2, 1, 'working'),
(48, 5, 1, 'working'),
(49, 5, 1, 'working'),
(50, 5, 1, 'not_started'),
(51, 5, 1, 'not_started'),
(52, 5, 1, 'not_started'),
(52, 2, 1, 'not_started'),
(53, 5, 1, 'not_started'),
(54, 5, 1, 'not_started'),
-- Security Audit (tasks 55-63)
(55, 10, 1, 'completed'),
(56, 10, 1, 'completed'),
(57, 10, 1, 'working'),
(58, 10, 1, 'working'),
(58, 2, 1, 'working'),
(59, 10, 1, 'not_started'),
(59, 2, 1, 'not_started'),
(60, 10, 1, 'not_started'),
(60, 8, 1, 'not_started'),
(61, 10, 1, 'not_started'),
(62, 10, 1, 'not_started'),
(63, 10, 1, 'not_started');

-- =============================================
-- KNOWLEDGE BASE TOPICS (IDs 1-8)
-- =============================================

INSERT INTO kb_topics (topic_name, description, created_by) VALUES
('General Discussion',       'Open discussions about company and team topics, announcements, and team building',               1),
('Technical Help',           'Ask for help with technical issues, bugs, errors, and troubleshooting',                           1),
('Best Practices',           'Share and discuss development best practices, code standards, and methodologies',                 1),
('Project Updates',          'Updates and announcements about ongoing projects, milestones, and achievements',                  1),
('DevOps & Infrastructure',  'Discussions about deployment, CI/CD, cloud infrastructure, and monitoring',                       1),
('Security & Compliance',    'Security best practices, vulnerability reports, and compliance requirements',                     1),
('Career Development',       'Learning resources, certifications, career advice, and professional growth',                      1),
('Tools & Resources',        'Share useful tools, libraries, frameworks, and development resources',                            1);

INSERT INTO kb_topics (topic_name, description, created_by, icon) VALUES
('Technical Support', 'IT support, troubleshooting, and technical documentation', 1, 'tool'),
('HR Policies', 'Company policies, benefits, and HR information', 1, 'users'),
('Project Best Practices', 'Tips and guidelines for successful project management', 1, 'bar-chart-2'),
('Development Guidelines', 'Coding standards, architecture decisions, and dev tips', 2, 'code');


-- =============================================
-- KNOWLEDGE BASE POSTS (IDs 1-22)
-- =============================================

INSERT INTO kb_posts (title, content, topic_id, author_id, tags, like_count) VALUES
('Welcome to Make-It-All!',
'Hello everyone! This is our new knowledge base platform. Feel free to ask questions, share knowledge, and help each other out.\n\nLets make this a great resource for our team. Remember to:\n- Search before posting to avoid duplicates\n- Use descriptive titles\n- Tag your posts appropriately\n- Be respectful and constructive\n\nLooking forward to seeing this grow!',
1, 1, '["welcome","announcement","guidelines"]', 10),

('Team lunch next Friday!',
'Reminder that we have our monthly team lunch next Friday at 12:30 PM at The Italian Place downtown.\n\nPlease RSVP in the Slack channel by Wednesday so we can get an accurate headcount. Dietary restrictions? Let me know!\n\nLooking forward to seeing everyone there!',
1, 1, '["social","lunch","event"]', 10),

('New meeting room booking system',
'We have launched a new meeting room booking system! You can now book rooms through the company portal.\n\nFeatures:\n- See real-time availability\n- Recurring bookings\n- Automatic calendar invites\n- Equipment requests\n\nCheck it out at https://rooms.make-it-all.co.uk',
1, 1, '["announcement","facilities","tools"]', 6),

('How to setup the development environment',
'Step 1: Clone the repository\n```bash\ngit clone https://github.com/make-it-all/main-app.git\ncd main-app\n```\n\nStep 2: Install dependencies\n```bash\nnpm install\n```\n\nStep 3: Configure your .env file\n```bash\ncp .env.example .env\n# Edit .env with your database credentials\n```\n\nStep 4: Start the dev server\n```bash\nnpm run dev\n```\n\nMake sure you have Node.js v18+ installed. For Windows users, use Git Bash or WSL2.',
2, 3, '["setup","development","guide","getting-started"]', 8),

('Debugging CORS issues in API calls',
'If you are getting CORS errors when making API calls from the frontend:\n\n1. Make sure the API server includes proper CORS headers:\n```javascript\nres.header("Access-Control-Allow-Origin", process.env.FRONTEND_URL);\nres.header("Access-Control-Allow-Credentials", "true");\n```\n\n2. For development, you can use the proxy in vite.config.js:\n```javascript\nserver: {\n  proxy: {\n    "/api": "http://localhost:3000"\n  }\n}\n```\n\n3. For production, ensure your API domain is whitelisted in the CORS config.\n\nStill having issues? Check the network tab for the actual error response.',
2, 8, '["cors","api","debugging","frontend","backend"]', 7),

('Database connection pool exhaustion',
'Ran into an issue today where our database connection pool was getting exhausted under load.\n\nThe problem: We were not properly closing database connections in error handlers.\n\nThe solution:\n```javascript\ntry {\n  const result = await db.query(sql);\n  return result;\n} catch (error) {\n  console.error(error);\n  throw error;\n} finally {\n  // IMPORTANT: Always release connection\n  await db.release();\n}\n```\n\nAlso increased pool size from 10 to 20 in production config. Monitoring shows much better performance now.',
2, 2, '["database","mysql","connection-pool","performance"]', 6),

('Git branching strategy',
'We use the following branching strategy:\n\n**Main Branches:**\n- `main`: production-ready code, protected, requires PR + review\n- `develop`: integration branch for features, auto-deployed to staging\n\n**Supporting Branches:**\n- `feature/*`: new features (e.g., feature/user-authentication)\n- `bugfix/*`: bug fixes for develop\n- `hotfix/*`: urgent production fixes, branch from main\n- `release/*`: release preparation (version bump, final fixes)\n\n**Workflow:**\n1. Create feature branch from develop\n2. Make changes and commit regularly\n3. Push and create PR to develop\n4. Get at least one review\n5. Squash and merge to develop\n\n**Commit Messages:**\nFollow conventional commits format:\n```\nfeat: add user login\nfix: resolve navbar mobile bug\ndocs: update API documentation\ntest: add unit tests for auth\n```',
3, 2, '["git","branching","workflow","version-control"]', 9),

('React component best practices',
'Here are some tips for writing clean React components:\n\n**1. Keep components small and focused**\nEach component should do one thing well. If a component is getting too large, split it.\n\n**2. Use custom hooks for shared logic**\n```javascript\nfunction useAuth() {\n  const [user, setUser] = useState(null);\n  return { user, login, logout };\n}\n```\n\n**3. Prefer composition over inheritance**\n```javascript\n<Card>\n  <CardHeader title="Hello" />\n  <CardBody>Content</CardBody>\n</Card>\n```\n\n**4. Use TypeScript for type safety**\nDefine prop types and interfaces for better autocomplete and error catching.\n\n**5. Write tests for critical components**\nUse React Testing Library for component tests, focus on user behavior not implementation.',
3, 3, '["react","frontend","best-practices","typescript"]', 7),

('API error handling patterns',
'Consistent error handling makes debugging easier. Here is our standard approach:\n\n**Backend (Express):**\n```javascript\nclass ApiError extends Error {\n  constructor(statusCode, message, details = null) {\n    super(message);\n    this.statusCode = statusCode;\n    this.details = details;\n  }\n}\n\napp.use((err, req, res, next) => {\n  const status = err.statusCode || 500;\n  res.status(status).json({\n    error: { message: err.message, status: status, details: err.details }\n  });\n});\n```\n\nAlways return consistent error structures!',
3, 2, '["api","error-handling","backend","best-practices"]', 7),

('Code review checklist',
'Use this checklist when reviewing PRs:\n\n**Functionality:**\n- Code works as intended\n- Edge cases handled\n- No obvious bugs\n\n**Code Quality:**\n- Easy to read and understand\n- Follows project conventions\n- No code duplication\n\n**Testing:**\n- Unit tests included\n- Edge cases covered\n\n**Security:**\n- No hardcoded secrets\n- Input validation\n- SQL injection prevention\n- XSS prevention\n\n**Performance:**\n- No unnecessary database calls\n- Efficient algorithms\n\nDo not be afraid to ask questions!',
3, 1, '["code-review","checklist","best-practices","quality"]', 8),

('Website Redesign - Progress Update',
'Great progress on the website redesign project this sprint!\n\n**Completed:**\n- All homepage mockups finalized\n- Brand guidelines approved\n- User research completed\n\n**In Progress:**\n- Navigation bar implementation\n- Footer redesign\n- Accessibility compliance audit\n\n**Upcoming:**\n- CI/CD pipeline setup\n- Blog system implementation\n\n**Blockers:**\nNone currently!\n\nWe are on track for the March 15th deadline. Great work team!',
4, 2, '["website-redesign","progress","update"]', 8),

('Mobile App - Beta Testing Next Week',
'Exciting news! We are ready to start beta testing the mobile app next week.\n\n**What we need from you:**\n- Install TestFlight (iOS) or join the beta program (Android)\n- Test core features: login, profile, offline mode\n- Report any bugs or UX issues\n\nI will send out detailed testing instructions on Monday. Looking forward to your feedback!\n\nBig thanks to Frank, Emma, and Henry for getting us to this point!',
4, 2, '["mobile-app","beta","testing","announcement"]', 7),

('Docker setup for local development',
'To run the app locally with Docker:\n\n**Prerequisites:**\n- Docker Desktop installed\n- At least 4GB RAM allocated to Docker\n\n**Setup:**\n```bash\ngit clone https://github.com/make-it-all/main-app.git\ncd main-app\ncp .env.example .env\ndocker-compose up -d\n```\n\n**Services:**\n- MySQL: localhost:3306\n- PHP/Apache: localhost:8080\n- phpMyAdmin: localhost:8081\n\n**Useful commands:**\n```bash\ndocker-compose logs -f\ndocker-compose restart\ndocker-compose down\ndocker-compose up -d --build\n```\n\nIf you get permission errors on Linux, add your user to the docker group.',
5, 5, '["docker","devops","setup","local-development"]', 8),

('CI/CD pipeline overview',
'Our CI/CD pipeline runs on GitHub Actions with these stages:\n\n**On Pull Request:**\n1. Linting (ESLint, Prettier)\n2. Unit tests (Jest)\n3. Integration tests\n4. Build verification\n5. Security scan (npm audit)\n\n**On Merge to Develop:**\n1. Run full test suite\n2. Build Docker image\n3. Push to container registry\n4. Deploy to staging\n5. Run smoke tests\n\n**On Merge to Main:**\n1. Create release tag\n2. Build production image\n3. Deploy to production (blue-green)\n4. Run health checks\n5. Notify team in Slack\n\nAverage deploy time: 8 minutes',
5, 5, '["cicd","github-actions","deployment","automation"]', 7),

('Monitoring and alerting setup',
'Our monitoring stack:\n\n**Application Monitoring:**\n- New Relic for APM\n- Sentry for error tracking\n- Custom metrics to CloudWatch\n\n**Infrastructure Monitoring:**\n- CloudWatch for AWS resources\n- Grafana dashboards\n- Uptime monitoring with Pingdom\n\n**Alerts:**\nSlack notifications for:\n- Error rate > 1%\n- Response time > 2s (p95)\n- CPU usage > 80%\n- Disk space < 20%\n- Failed deployments\n\n**On-Call Rotation:**\nPagerDuty for critical alerts (P1)\nWeekly rotation schedule',
5, 5, '["monitoring","alerting","observability","cloudwatch"]', 5),

('Security best practices checklist',
'Follow these security best practices for all projects:\n\n**Authentication:**\n- Use bcrypt for password hashing (cost factor 12+)\n- Implement rate limiting on auth endpoints\n- Support 2FA\n- Session timeout after 30 min inactivity\n\n**Authorization:**\n- Principle of least privilege\n- Role-based access control\n- Validate permissions on every request\n\n**Data Protection:**\n- Encrypt sensitive data at rest\n- Use HTTPS everywhere\n- Sanitize user inputs\n- Parameterized SQL queries\n\n**Dependencies:**\n- Regular npm audit\n- Automated dependency updates\n- Review third-party packages\n\nSee the full security policy in the wiki.',
6, 10, '["security","best-practices","checklist","compliance"]', 7),

('OWASP Top 10 for web developers',
'Understanding the OWASP Top 10 vulnerabilities:\n\n1. Broken Access Control\n2. Cryptographic Failures\n3. Injection\n4. Insecure Design\n5. Security Misconfiguration\n6. Vulnerable Components\n7. Authentication Failures\n8. Software Integrity Failures\n9. Logging Failures\n10. SSRF\n\nSee https://owasp.org/Top10 for details.',
6, 10, '["security","owasp","vulnerabilities","education"]', 6),

('Recommended learning resources',
'Here are some great resources for professional development:\n\n**Online Courses:**\n- Frontend Masters (React, TypeScript, Node.js)\n- Udemy (specific tech deep dives)\n- Pluralsight (enterprise software skills)\n- AWS Training and Certification\n\n**Books:**\n- "Clean Code" by Robert Martin\n- "Designing Data-Intensive Applications" by Martin Kleppmann\n- "The Pragmatic Programmer" by Hunt & Thomas\n- "System Design Interview" by Alex Xu\n\nCompany reimburses $500/year for learning materials!',
7, 1, '["learning","resources","career","professional-development"]', 8),

('AWS certification path',
'Planning to get AWS certified? Here is a recommended path:\n\n**Beginner:** AWS Certified Cloud Practitioner (2 weeks prep)\n**Intermediate:** AWS Certified Solutions Architect - Associate (1-2 months prep)\n**Advanced:** AWS Certified Solutions Architect - Professional OR DevOps Engineer (3-4 months prep)\n\n**Study Resources:**\n- A Cloud Guru courses\n- AWS free tier for hands-on practice\n- AWS whitepapers\n- Practice exams\n\nCompany covers certification exam costs!',
7, 5, '["aws","certification","career","learning"]', 6),

('VS Code extensions for web development',
'Essential VS Code extensions we recommend:\n\n**Productivity:** GitLens, Auto Rename Tag, Prettier, ESLint, Path Intellisense\n**React/Frontend:** ES7+ React snippets, Tailwind CSS IntelliSense, CSS Peek\n**Backend:** REST Client, Database Client, Docker\n**Themes:** One Dark Pro, Material Icon Theme\n**Other:** Live Share, TODO Highlight, Better Comments\n\nSettings sync across devices using GitHub!',
8, 3, '["vscode","tools","extensions","productivity"]', 7),

('Useful npm packages for Node.js',
'npm packages we use frequently:\n\n**Express Middleware:** helmet, cors, express-rate-limit, express-validator, morgan\n**Utilities:** lodash, date-fns, axios, dotenv, uuid\n**Database:** mysql2, mongoose, sequelize\n**Testing:** jest, supertest, @testing-library/react\n**Dev Tools:** nodemon, eslint, prettier\n\nAlways check npm trends before adding dependencies!',
8, 2, '["npm","nodejs","packages","tools"]', 6),

('Chrome DevTools tips and tricks',
'Level up your debugging with these Chrome DevTools tips:\n\n**Console:** console.table(), $0 for selected element, $_ for last result, monitor(fn)\n**Network:** Copy as fetch, domain filtering, network throttling\n**Sources:** Conditional breakpoints, debugger statement, blackbox scripts\n**Performance:** Record page load, screenshot timeline, memory heap snapshots\n**Application:** Inspect cookies/localStorage/IndexedDB, clear site data\n\nF12 is your best friend!',
8, 7, '["chrome","devtools","debugging","tips"]', 7);

-- =============================================
-- KNOWLEDGE BASE COMMENTS
-- =============================================

INSERT INTO kb_comments (post_id, author_id, content) VALUES
-- Post 1: Welcome
(1, 3,  'Excited to use this platform! This will be so much better than digging through Slack messages.'),
(1, 5,  'Looking forward to sharing DevOps knowledge here. Will post some Docker guides soon.'),
(1, 6,  'This is great, thanks for setting it up! Love the clean interface.'),
(1, 2,  'Quick tip: use the search feature before posting to avoid duplicates!'),
(1, 7,  'Can we get categories for different project-specific discussions?'),
-- Post 2: Team lunch
(2, 3,  'I am in! Vegetarian option please.'),
(2, 6,  'Count me in!'),
(2, 5,  'Will be there!'),
-- Post 3: Meeting room
(3, 2,  'Finally! The old system was so clunky.'),
(3, 8,  'Does it integrate with Google Calendar?'),
-- Post 4: Dev environment
(4, 5,  'Great guide! I would also recommend using nvm to manage Node.js versions.'),
(4, 2,  'Thanks Carol, this helped me set up quickly. Should we add this to the official docs?'),
(4, 7,  'What about Windows users? Does WSL2 work well?'),
(4, 3,  'Yes, WSL2 works perfectly! I can write a quick addendum if needed.'),
-- Post 5: CORS
(5, 3,  'This saved me hours today! Was banging my head against CORS errors.'),
(5, 2,  'We should add this to the troubleshooting guide.'),
-- Post 6: DB connection
(6, 5,  'Good catch! I will review our connection handling in the cloud migration project.'),
-- Post 7: Git branching
(7, 3,  'Should we also use semantic commit messages following conventional commits?'),
(7, 1,  'Yes! I will update the guide. Great suggestion.'),
(7, 8,  'What about the merge vs rebase debate? Do we have a preference?'),
-- Post 8: React
(8, 7,  'The composition over inheritance tip is gold. Seen too many deep component hierarchies.'),
(8, 6,  'For point 5, are we using Jest + React Testing Library or something else?'),
-- Post 9: API error
(9, 8,  'We should use this pattern in the new API v2 project. Much cleaner than what we have now.'),
-- Post 10: Code review
(10, 3, 'This is super helpful! Going to bookmark this for every PR review.'),
(10, 6, 'Can we add design/UX considerations to this list?'),
-- Post 11: Website progress
(11, 3, 'Great progress team! The accessibility work has been really interesting.'),
(11, 6, 'Thanks for the update Bob! Footer redesign should be done by end of week.'),
(11, 7, 'Navigation is coming along nicely. Testing on mobile devices tomorrow.'),
(11, 1, 'Excellent work everyone! Keep it up!'),
-- Post 12: Mobile beta
(12, 3, 'Cannot wait to test this! When do we get the TestFlight link?'),
(12, 5, 'Thanks! Excited to see the feedback.'),
-- Post 13: Docker
(13, 3, 'This is super helpful for getting started quickly. No more works on my machine issues!'),
(13, 2, 'Should we add instructions for M1/M2 Mac users? Some platform differences there.'),
(13, 7, 'The phpMyAdmin access is really convenient for debugging database issues.'),
-- Post 16: Security checklist
(16, 2, 'Thanks Jack! Will review our auth implementation against this checklist.'),
(16, 5, 'We should run through this before every production deployment.'),
-- Post 18: Learning resources
(18, 3, 'Just finished Clean Code - absolutely worth the read!'),
(18, 7, 'Frontend Masters is amazing! Their TypeScript courses are top-notch.'),
(18, 6, 'Can we create a company library with some of these books?'),
(18, 2, 'I have been listening to Syntax.fm - great for keeping up with JS ecosystem.'),
(18, 5, 'The AWS Training materials helped me prepare for my certification.'),
-- Post 19: AWS cert
(19, 2,  'I am planning to get the Solutions Architect Associate cert this quarter!'),
(19, 10, 'The DevOps Professional cert is tough but totally worth it.'),
(19, 3,  'Does the company reimburse for practice exams too?'),
-- Post 20: VS Code
(20, 7, 'GitLens is a game changer! Cannot imagine working without it now.'),
(20, 2, 'I also recommend the Error Lens extension - shows errors inline.'),
-- Post 21: npm packages
(21, 8, 'We use most of these! date-fns is so much better than moment.js.'),
-- Post 22: DevTools
(22, 3, 'The console.table() tip is amazing! Why did I not know about this before?'),
(22, 6, 'Conditional breakpoints have saved me so much debugging time.');

-- =============================================
-- KB POST LIKES
-- =============================================

INSERT INTO kb_post_likes (post_id, user_id) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 7), (1, 8), (1, 9), (1, 10),
(2, 1), (2, 2), (2, 3), (2, 4), (2, 5), (2, 6), (2, 7), (2, 8), (2, 9), (2, 10),
(3, 2), (3, 3), (3, 5), (3, 6), (3, 7), (3, 8),
(4, 1), (4, 2), (4, 5), (4, 6), (4, 7), (4, 8), (4, 9), (4, 10),
(5, 2), (5, 3), (5, 5), (5, 6), (5, 7), (5, 9), (5, 10),
(6, 3), (6, 5), (6, 7), (6, 8), (6, 9), (6, 10),
(7, 1), (7, 2), (7, 3), (7, 5), (7, 6), (7, 7), (7, 8), (7, 9), (7, 10),
(8, 2), (8, 5), (8, 6), (8, 7), (8, 8), (8, 9), (8, 10),
(9, 3), (9, 5), (9, 6), (9, 7), (9, 8), (9, 9), (9, 10),
(10, 2), (10, 3), (10, 5), (10, 6), (10, 7), (10, 8), (10, 9), (10, 10),
(11, 1), (11, 3), (11, 5), (11, 6), (11, 7), (11, 8), (11, 9), (11, 10),
(12, 1), (12, 3), (12, 5), (12, 6), (12, 7), (12, 8), (12, 9),
(13, 1), (13, 2), (13, 3), (13, 6), (13, 7), (13, 8), (13, 9), (13, 10),
(14, 2), (14, 3), (14, 6), (14, 7), (14, 8), (14, 9), (14, 10),
(15, 2), (15, 3), (15, 6), (15, 8), (15, 10),
(16, 2), (16, 3), (16, 5), (16, 6), (16, 7), (16, 8), (16, 9),
(17, 2), (17, 3), (17, 5), (17, 6), (17, 7), (17, 8),
(18, 2), (18, 3), (18, 5), (18, 6), (18, 7), (18, 8), (18, 9), (18, 10),
(19, 2), (19, 3), (19, 5), (19, 6), (19, 8), (19, 10),
(20, 2), (20, 3), (20, 5), (20, 6), (20, 7), (20, 8), (20, 9),
(21, 2), (21, 3), (21, 5), (21, 6), (21, 7), (21, 8),
(22, 2), (22, 3), (22, 5), (22, 6), (22, 7), (22, 8), (22, 9);

-- =============================================
-- PERSONAL TASKS (for Home page testing)
-- =============================================

INSERT INTO user_personal_tasks (task_name, description, user_id, related_project_id, deadline, is_completed) VALUES
('Review design feedback',          'Check Slack for design feedback from stakeholders',                   6, 1,    '2026-02-15 17:00:00', FALSE),
('Prepare for 1-on-1',             'List accomplishments and questions for manager meeting',               3, NULL, '2026-02-18 10:00:00', FALSE),
('Research React best practices',  'Read articles on React performance optimization',                      3, 1,    '2026-02-20 17:00:00', FALSE);


-- ============================================================================
-- VERIFICATION QUERIES
-- ============================================================================

SELECT TABLE_NAME, TABLE_ROWS, CREATE_TIME
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_NAME;

SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE REFERENCED_TABLE_SCHEMA = DATABASE()
  AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME, COLUMN_NAME;

SELECT TRIGGER_NAME, EVENT_MANIPULATION, EVENT_OBJECT_TABLE, ACTION_TIMING
FROM INFORMATION_SCHEMA.TRIGGERS
WHERE TRIGGER_SCHEMA = DATABASE()
ORDER BY EVENT_OBJECT_TABLE, ACTION_TIMING;

-- ============================================================================
-- SETUP COMPLETE
-- ============================================================================
-- All tables, triggers, and test data created.
-- All passwords: Password123!
-- Dave Brown (dave.dev@make-it-all.co.uk) is NOT registered - use for signup testing.
-- ============================================================================
