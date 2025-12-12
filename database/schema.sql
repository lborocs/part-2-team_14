-- ============================================================================
-- Team 14 - Productivity & Knowledge Management System
-- Database Schema - Version 1.0
-- ============================================================================
-- Author: Simi Olusola (Backend Lead)
-- Date: December 2024
-- Description: Complete database schema for productivity tracking system
-- ============================================================================

-- Drop existing tables (in reverse order of dependencies)
DROP TABLE IF EXISTS user_personal_tasks;
DROP TABLE IF EXISTS training_recommendations;
DROP TABLE IF EXISTS task_history;
DROP TABLE IF EXISTS project_resources;
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
-- Purpose: Store all employees at Make-It-All (registered and non-registered)
-- ============================================================================

CREATE TABLE users (
    -- Primary Key
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Authentication
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NULL COMMENT 'NULL for non-registered employees',
    is_registered BOOLEAN DEFAULT FALSE,
    
    -- Personal Information
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    profile_picture VARCHAR(255) DEFAULT '/default-avatar.png',
    
    -- Professional Information
    role ENUM('manager', 'team_leader', 'team_member', 'technical_specialist') NOT NULL DEFAULT 'team_member',
    department VARCHAR(100),
    specialties TEXT COMMENT 'JSON array: ["Frontend", "React", "UX Design"]',
    manager_id INT NULL COMMENT 'Self-referencing FK - who manages this user',
    
    -- Account Status
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (manager_id) REFERENCES users(user_id) ON DELETE SET NULL,
    
    -- Indexes for performance
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_manager (manager_id),
    INDEX idx_is_registered (is_registered),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- TABLE 2: PROJECTS
-- ============================================================================
-- Purpose: Store all projects (active, on-hold, completed, archived)
-- ============================================================================

CREATE TABLE projects (
    -- Primary Key
    project_id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Project Information
    project_name VARCHAR(255) NOT NULL,
    description TEXT,
    
    -- People
    created_by INT NOT NULL COMMENT 'Manager who created the project',
    team_leader_id INT NULL COMMENT 'Assigned team leader',
    
    -- Timeline
    start_date DATE NOT NULL,
    deadline DATE NOT NULL,
    estimated_completion_date DATE NULL COMMENT 'Calculated based on velocity',
    completed_date DATE NULL,
    
    -- Status
    status ENUM('planning', 'active', 'on_hold', 'completed', 'archived') DEFAULT 'active',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    
    -- Health Metrics (auto-calculated via triggers)
    completion_percentage DECIMAL(5,2) DEFAULT 0.00,
    overdue_tasks_count INT DEFAULT 0,
    resource_level ENUM('sufficient', 'tight', 'under_resourced') DEFAULT 'sufficient',
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    archived_at TIMESTAMP NULL,
    
    -- Foreign Keys
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    FOREIGN KEY (team_leader_id) REFERENCES users(user_id) ON DELETE SET NULL,
    
    -- Indexes
    INDEX idx_status (status),
    INDEX idx_created_by (created_by),
    INDEX idx_team_leader (team_leader_id),
    INDEX idx_deadline (deadline),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- TABLE 3: PROJECT_MEMBERS
-- ============================================================================
-- Purpose: Junction table linking users to projects (many-to-many)
-- ============================================================================

CREATE TABLE project_members (
    -- Primary Key
    project_member_id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Foreign Keys
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    
    -- Role in this specific project
    project_role ENUM('team_leader', 'member') DEFAULT 'member',
    
    -- Timestamps
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    left_at TIMESTAMP NULL COMMENT 'Soft delete - set when member leaves project',
    
    -- Foreign Key Constraints
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    
    -- Unique constraint: user can only be in project once
    UNIQUE KEY unique_project_user (project_id, user_id),
    
    -- Indexes
    INDEX idx_project (project_id),
    INDEX idx_user (user_id),
    INDEX idx_left_at (left_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- TABLE 4: TASKS
-- ============================================================================
-- Purpose: Store all tasks within projects
-- ============================================================================

CREATE TABLE tasks (
    -- Primary Key
    task_id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Task Information
    task_name VARCHAR(255) NOT NULL,
    description TEXT,
    
    -- Assignment
    project_id INT NOT NULL,
    created_by INT NOT NULL COMMENT 'Manager/Team Leader who created task',
    
    -- Timeline
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deadline TIMESTAMP NOT NULL,
    started_date TIMESTAMP NULL COMMENT 'Set when moved to In Progress',
    completed_date TIMESTAMP NULL COMMENT 'Set when moved to Completed',
    
    -- Status
    status ENUM('to_do', 'in_progress', 'review', 'completed') DEFAULT 'to_do',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    
    -- Task Management
    estimated_hours DECIMAL(5,2) NULL,
    actual_hours DECIMAL(5,2) NULL,
    reopened_count INT DEFAULT 0 COMMENT 'Times task was reopened after completion',
    last_reopened_date TIMESTAMP NULL,
    
    -- Timestamps
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    
    -- Indexes
    INDEX idx_project (project_id),
    INDEX idx_status (status),
    INDEX idx_deadline (deadline),
    INDEX idx_created_by (created_by),
    INDEX idx_created_date (created_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- TABLE 5: TASK_ASSIGNMENTS
-- ============================================================================
-- Purpose: Junction table linking users to tasks (supports multiple assignees)
-- ============================================================================

CREATE TABLE task_assignments (
    -- Primary Key
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Foreign Keys
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    
    -- Assignment Details
    assigned_by INT NOT NULL COMMENT 'Who assigned this task',
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Status tracking per assignee
    user_status ENUM('not_started', 'working', 'completed') DEFAULT 'not_started',
    user_completed_at TIMESTAMP NULL,
    
    -- Foreign Key Constraints
    FOREIGN KEY (task_id) REFERENCES tasks(task_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    
    -- Unique constraint: user can only be assigned to task once
    UNIQUE KEY unique_task_user (task_id, user_id),
    
    -- Indexes
    INDEX idx_task (task_id),
    INDEX idx_user (user_id),
    INDEX idx_assigned_by (assigned_by),
    INDEX idx_user_status (user_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- TABLE 6: KB_TOPICS
-- ============================================================================
-- Purpose: Top-level categories for knowledge base
-- ============================================================================

CREATE TABLE kb_topics (
    -- Primary Key
    topic_id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Topic Information
    topic_name VARCHAR(255) NOT NULL,
    description TEXT,
    icon VARCHAR(50) COMMENT 'Icon class or emoji',
    
    -- Metadata
    created_by INT NOT NULL,
    post_count INT DEFAULT 0 COMMENT 'Denormalized count for performance',
    
    -- Access Control
    is_public BOOLEAN DEFAULT TRUE,
    restricted_to_role ENUM('all', 'manager', 'team_leader', 'technical_specialist') DEFAULT 'all',
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    
    -- Indexes
    INDEX idx_created_by (created_by),
    INDEX idx_is_public (is_public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- TABLE 7: KB_POSTS
-- ============================================================================
-- Purpose: Knowledge base posts under topics
-- ============================================================================

CREATE TABLE kb_posts (
    -- Primary Key
    post_id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Post Information
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    
    -- Relationships
    topic_id INT NOT NULL,
    author_id INT NOT NULL,
    
    -- Tags (stored as JSON array)
    tags JSON COMMENT 'Array: ["bug-fix", "authentication", "frontend"]',
    
    -- Engagement
    view_count INT DEFAULT 0,
    comment_count INT DEFAULT 0 COMMENT 'Denormalized for performance',
    
    -- Status
    is_solved BOOLEAN DEFAULT FALSE,
    
    -- Edit tracking
    is_edited BOOLEAN DEFAULT FALSE,
    last_edited_at TIMESTAMP NULL,
    last_edited_by INT NULL COMMENT 'May differ from author if specialist edited',
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (topic_id) REFERENCES kb_topics(topic_id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES users(user_id) ON DELETE RESTRICT,
    FOREIGN KEY (last_edited_by) REFERENCES users(user_id) ON DELETE SET NULL,
    
    -- Indexes
    INDEX idx_topic (topic_id),
    INDEX idx_author (author_id),
    INDEX idx_created_at (created_at),
    INDEX idx_is_solved (is_solved)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- TABLE 8: KB_COMMENTS
-- ============================================================================
-- Purpose: Comments on knowledge base posts (flat structure, no nesting)
-- ============================================================================

CREATE TABLE kb_comments (
    -- Primary Key
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Comment Information
    content TEXT NOT NULL,
    
    -- Relationships
    post_id INT NOT NULL,
    author_id INT NOT NULL,
    
    -- Edit tracking
    is_edited BOOLEAN DEFAULT FALSE,
    last_edited_at TIMESTAMP NULL,
    last_edited_by INT NULL COMMENT 'May differ from author if specialist edited',
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (post_id) REFERENCES kb_posts(post_id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES users(user_id) ON DELETE RESTRICT,
    FOREIGN KEY (last_edited_by) REFERENCES users(user_id) ON DELETE SET NULL,
    
    -- Indexes
    INDEX idx_post (post_id),
    INDEX idx_author (author_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- TABLE 9: PROJECT_RESOURCES
-- ============================================================================
-- Purpose: Store metadata for uploaded files
-- ============================================================================

CREATE TABLE project_resources (
    -- Primary Key
    resource_id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- File Information
    file_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(50) NOT NULL COMMENT 'pdf, docx, xlsx, png, jpg',
    file_size INT NOT NULL COMMENT 'Size in bytes',
    file_path VARCHAR(500) NOT NULL COMMENT 'Storage path on server',
    
    -- Relationships
    project_id INT NOT NULL,
    uploaded_by INT NOT NULL,
    
    -- Metadata
    description TEXT,
    
    -- Timestamps
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    
    -- Indexes
    INDEX idx_project (project_id),
    INDEX idx_uploaded_by (uploaded_by),
    INDEX idx_file_type (file_type),
    INDEX idx_uploaded_at (uploaded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- TABLE 10: TASK_HISTORY
-- ============================================================================
-- Purpose: Audit trail for task changes (who changed what, when)
-- ============================================================================

CREATE TABLE task_history (
    -- Primary Key
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Task Reference
    task_id INT NOT NULL,
    
    -- Change Information
    changed_by INT NOT NULL,
    action ENUM('created', 'status_changed', 'assigned', 'unassigned', 'updated', 'deleted') NOT NULL,
    
    -- Before/After States
    old_status ENUM('to_do', 'in_progress', 'review', 'completed') NULL,
    new_status ENUM('to_do', 'in_progress', 'review', 'completed') NULL,
    
    -- Additional Details
    notes TEXT,
    
    -- Timestamp
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (task_id) REFERENCES tasks(task_id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    
    -- Indexes
    INDEX idx_task (task_id),
    INDEX idx_changed_by (changed_by),
    INDEX idx_changed_at (changed_at),
    INDEX idx_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- TABLE 11: TRAINING_RECOMMENDATIONS
-- ============================================================================
-- Purpose: Track training needs identified by the system or managers
-- ============================================================================

CREATE TABLE training_recommendations (
    -- Primary Key
    recommendation_id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- User & Project
    user_id INT NOT NULL,
    project_id INT NULL COMMENT 'NULL if company-wide recommendation',
    
    -- Recommendation
    recommendation_type ENUM('completion_rate', 'overdue_tasks', 'avg_time', 'quality', 'technical') NOT NULL,
    recommendation_text TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    
    -- Status
    status ENUM('pending', 'acknowledged', 'in_progress', 'completed', 'dismissed') DEFAULT 'pending',
    dismissed_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_user (user_id),
    INDEX idx_project (project_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- TABLE 12: USER_PERSONAL_TASKS
-- ============================================================================
-- Purpose: Personal to-do list items (not project-related)
-- ============================================================================

CREATE TABLE user_personal_tasks (
    -- Primary Key
    personal_task_id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Task Information
    task_name VARCHAR(255) NOT NULL,
    description TEXT,
    
    -- User
    user_id INT NOT NULL,
    
    -- Optional Project Link
    related_project_id INT NULL COMMENT 'Can tag with project for organization',
    
    -- Timeline
    deadline TIMESTAMP NULL,
    
    -- Status
    is_completed BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (related_project_id) REFERENCES projects(project_id) ON DELETE SET NULL,
    
    -- Indexes
    INDEX idx_user (user_id),
    INDEX idx_related_project (related_project_id),
    INDEX idx_is_completed (is_completed),
    INDEX idx_deadline (deadline)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- TRIGGERS
-- ============================================================================
-- These automatically update calculated fields
-- ============================================================================

-- ----------------------------------------------------------------------------
-- TRIGGER 1: Auto-update project completion percentage
-- ----------------------------------------------------------------------------
-- When a task's status changes, recalculate the project's completion rate
-- ----------------------------------------------------------------------------

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

DELIMITER ;


-- ----------------------------------------------------------------------------
-- TRIGGER 2: Auto-update overdue tasks count
-- ----------------------------------------------------------------------------
-- When a task's status or deadline changes, recalculate overdue task count
-- ----------------------------------------------------------------------------

DELIMITER //

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

DELIMITER ;


-- ----------------------------------------------------------------------------
-- TRIGGER 3: Auto-update post comment count (on insert)
-- ----------------------------------------------------------------------------

DELIMITER //

CREATE TRIGGER update_comment_count_insert
AFTER INSERT ON kb_comments
FOR EACH ROW
BEGIN
    UPDATE kb_posts
    SET comment_count = comment_count + 1
    WHERE post_id = NEW.post_id;
END//

DELIMITER ;


-- ----------------------------------------------------------------------------
-- TRIGGER 4: Auto-update post comment count (on delete)
-- ----------------------------------------------------------------------------

DELIMITER //

CREATE TRIGGER update_comment_count_delete
AFTER DELETE ON kb_comments
FOR EACH ROW
BEGIN
    UPDATE kb_posts
    SET comment_count = comment_count - 1
    WHERE post_id = OLD.post_id;
END//

DELIMITER ;


-- ----------------------------------------------------------------------------
-- TRIGGER 5: Auto-update topic post count (on insert)
-- ----------------------------------------------------------------------------

DELIMITER //

CREATE TRIGGER update_topic_post_count_insert
AFTER INSERT ON kb_posts
FOR EACH ROW
BEGIN
    UPDATE kb_topics
    SET post_count = post_count + 1
    WHERE topic_id = NEW.topic_id;
END//

DELIMITER ;


-- ----------------------------------------------------------------------------
-- TRIGGER 6: Auto-update topic post count (on delete)
-- ----------------------------------------------------------------------------

DELIMITER //

CREATE TRIGGER update_topic_post_count_delete
AFTER DELETE ON kb_posts
FOR EACH ROW
BEGIN
    UPDATE kb_topics
    SET post_count = post_count - 1
    WHERE topic_id = OLD.topic_id;
END//

DELIMITER ;


-- ----------------------------------------------------------------------------
-- TRIGGER 7: Log task status changes to task_history
-- ----------------------------------------------------------------------------

DELIMITER //

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
-- SAMPLE DATA (Optional - for testing)
-- ============================================================================
-- Uncomment the sections below to populate with sample data
-- ============================================================================

-- Insert sample users
INSERT INTO users (email, first_name, last_name, role, specialties, is_registered, password_hash) VALUES
('alice.manager@makeitall.com', 'Alice', 'Johnson', 'manager', '["Project Management", "Strategy", "Leadership"]', TRUE, '$2b$10$examplehash1'),
('bob.lead@makeitall.com', 'Bob', 'Smith', 'team_leader', '["Backend", "Python", "SQL", "API Design"]', TRUE, '$2b$10$examplehash2'),
('carol.dev@makeitall.com', 'Carol', 'Williams', 'team_member', '["Frontend", "React", "CSS", "JavaScript"]', TRUE, '$2b$10$examplehash3'),
('dave.dev@makeitall.com', 'Dave', 'Brown', 'team_member', '["Backend", "Node.js", "MongoDB"]', FALSE, NULL),
('emma.specialist@makeitall.com', 'Emma', 'Davis', 'technical_specialist', '["DevOps", "AWS", "Docker", "CI/CD"]', TRUE, '$2b$10$examplehash4'),
('frank.designer@makeitall.com', 'Frank', 'Miller', 'team_member', '["UI Design", "Figma", "Prototyping"]', TRUE, '$2b$10$examplehash5');

-- Set manager relationships
UPDATE users SET manager_id = 1 WHERE user_id IN (2, 3, 4, 5, 6);

-- Insert sample project
INSERT INTO projects (project_name, description, created_by, team_leader_id, start_date, deadline, status, priority) VALUES
('Website Redesign', 'Complete overhaul of company website with modern UI/UX', 1, 2, '2025-01-01', '2025-02-15', 'active', 'high'),
('Mobile App Development', 'Build iOS and Android mobile app for customer portal', 1, 2, '2025-01-10', '2025-03-30', 'active', 'medium'),
('Internal Tools Upgrade', 'Upgrade internal CRM and project management tools', 1, NULL, '2025-02-01', '2025-04-15', 'planning', 'low');

-- Add team members to projects
INSERT INTO project_members (project_id, user_id, project_role) VALUES
(1, 2, 'team_leader'),
(1, 3, 'member'),
(1, 4, 'member'),
(1, 6, 'member'),
(2, 2, 'team_leader'),
(2, 4, 'member'),
(2, 5, 'member');

-- Insert sample tasks
INSERT INTO tasks (task_name, description, project_id, created_by, deadline, status, priority) VALUES
('Design homepage mockup', 'Create Figma mockup for new homepage design', 1, 2, '2025-01-10 17:00:00', 'completed', 'high'),
('Implement navigation', 'Build responsive navigation component with mobile menu', 1, 2, '2025-01-15 17:00:00', 'in_progress', 'high'),
('Set up database', 'Configure MySQL database schema and initial tables', 1, 2, '2025-01-12 17:00:00', 'completed', 'high'),
('Create user authentication', 'Implement login/signup with JWT tokens', 1, 2, '2025-01-20 17:00:00', 'in_progress', 'high'),
('Build product catalog', 'Create catalog page with filtering and search', 1, 2, '2025-01-25 17:00:00', 'to_do', 'medium'),
('Setup CI/CD pipeline', 'Configure automated testing and deployment', 1, 2, '2025-02-01 17:00:00', 'to_do', 'medium'),
('Write API documentation', 'Document all API endpoints with examples', 1, 2, '2025-02-10 17:00:00', 'to_do', 'low');

-- Update completed tasks with completion dates
UPDATE tasks 
SET completed_date = '2025-01-09 16:30:00', started_date = '2025-01-05 09:00:00'
WHERE task_id = 1;

UPDATE tasks 
SET completed_date = '2025-01-11 14:45:00', started_date = '2025-01-10 10:00:00'
WHERE task_id = 3;

UPDATE tasks 
SET started_date = '2025-01-14 09:00:00'
WHERE task_id IN (2, 4);

-- Assign tasks to team members
INSERT INTO task_assignments (task_id, user_id, assigned_by, user_status, user_completed_at) VALUES
(1, 6, 2, 'completed', '2025-01-09 16:30:00'),
(2, 3, 2, 'working', NULL),
(3, 4, 2, 'completed', '2025-01-11 14:45:00'),
(4, 4, 2, 'working', NULL),
(5, 3, 2, 'not_started', NULL),
(6, 5, 2, 'not_started', NULL),
(7, 4, 2, 'not_started', NULL);

-- Insert Knowledge Base topics
INSERT INTO kb_topics (topic_name, description, created_by, icon) VALUES
('Technical Support', 'IT support, troubleshooting, and technical documentation', 1, '🔧'),
('HR Policies', 'Company policies, benefits, and HR information', 1, '👥'),
('Project Best Practices', 'Tips and guidelines for successful project management', 1, '📊'),
('Development Guidelines', 'Coding standards, architecture decisions, and dev tips', 2, '💻');

-- Insert sample posts
INSERT INTO kb_posts (title, content, topic_id, author_id, tags) VALUES
('How to reset your password', 'Follow these steps to reset your password:\n1. Click "Forgot Password" on login page\n2. Enter your email\n3. Check your email for reset link\n4. Click link and enter new password\n\nNote: Password must be at least 8 characters with 1 letter, 3 numbers, and 1 special character.', 1, 2, '["authentication", "password", "account"]'),
('Requesting PTO', 'To request time off:\n1. Log into HR portal\n2. Navigate to Time Off section\n3. Select dates and reason\n4. Submit for manager approval\n\nRequests should be submitted at least 2 weeks in advance for planned vacations.', 2, 1, '["pto", "vacation", "time-off"]'),
('Code Review Checklist', 'Before submitting a PR, ensure:\n✓ All tests pass\n✓ Code follows style guide\n✓ No console.logs in production code\n✓ Comments explain complex logic\n✓ Variable names are descriptive\n✓ No hardcoded values', 4, 2, '["code-review", "best-practices", "development"]');

-- Insert sample comments
INSERT INTO kb_comments (content, post_id, author_id) VALUES
('This helped me reset my password, thanks!', 1, 3),
('Can we add 2FA to make this more secure?', 1, 5),
('Great checklist! I always forget about the console.logs.', 3, 4);

-- Insert sample personal tasks
INSERT INTO user_personal_tasks (task_name, description, user_id, related_project_id, deadline, is_completed) VALUES
('Review design feedback', 'Check Slack for design feedback from stakeholders', 6, 1, '2025-01-15 17:00:00', FALSE),
('Prepare for 1-on-1', 'List accomplishments and questions for manager meeting', 3, NULL, '2025-01-18 10:00:00', FALSE),
('Research React best practices', 'Read articles on React performance optimization', 3, 1, '2025-01-20 17:00:00', FALSE);

-- ============================================================================
-- VERIFICATION QUERIES
-- ============================================================================
-- Run these to verify the schema was created correctly
-- ============================================================================

-- Check all tables were created
SELECT 
    TABLE_NAME,
    TABLE_ROWS,
    CREATE_TIME
FROM 
    INFORMATION_SCHEMA.TABLES
WHERE 
    TABLE_SCHEMA = DATABASE()
ORDER BY 
    TABLE_NAME;

-- Verify foreign key relationships
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE
    REFERENCED_TABLE_SCHEMA = DATABASE()
    AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY
    TABLE_NAME, COLUMN_NAME;

-- Check indexes
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS COLUMNS,
    INDEX_TYPE
FROM
    INFORMATION_SCHEMA.STATISTICS
WHERE
    TABLE_SCHEMA = DATABASE()
GROUP BY
    TABLE_NAME, INDEX_NAME, INDEX_TYPE
ORDER BY
    TABLE_NAME, INDEX_NAME;

-- Verify triggers
SELECT 
    TRIGGER_NAME,
    EVENT_MANIPULATION,
    EVENT_OBJECT_TABLE,
    ACTION_TIMING
FROM
    INFORMATION_SCHEMA.TRIGGERS
WHERE
    TRIGGER_SCHEMA = DATABASE()
ORDER BY
    EVENT_OBJECT_TABLE, ACTION_TIMING;

-- ============================================================================
-- SETUP COMPLETE
-- ============================================================================
-- All tables, triggers, and sample data have been created successfully!
-- 
-- Next Steps:
-- 1. Verify all tables exist (run verification queries above)
-- 2. Test database connection from your application
-- 3. Create your first API endpoint
-- 4. Share setup instructions with team
-- 
-- Questions? Contact: Simi Olusola (Backend Lead)
-- ============================================================================
