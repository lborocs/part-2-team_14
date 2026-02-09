# Testing Guide - Make-It-All
## Comprehensive Test Data & Setup

---

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

**All passwords are: `Password123!`**

| # | Name | Email | Role | Purpose |
|---|------|-------|------|---------|
| 1 | Alice Johnson | alice.manager@make-it-all.co.uk | Manager | Full admin access, can manage projects/employees |
| 2 | Bob Smith | bob.lead@make-it-all.co.uk | Team Leader | Team leader on Website Redesign project |
| 3 | Carol Williams | carol.dev@make-it-all.co.uk | Team Member | Regular member on Website Redesign |
| 4 | Dave Brown | dave.dev@make-it-all.co.uk | Team Member | Not registered (test registration) |
| 5 | Emma Davis | emma.specialist@make-it-all.co.uk | Technical Specialist | KB specialist, DevOps expert |
| 6 | Frank Miller | frank.designer@make-it-all.co.uk | Team Member | Designer on Mobile App project |
| 7 | Grace Taylor | grace.tester@make-it-all.co.uk | Team Member | QA tester, frontend developer |
| 8 | Henry Anderson | henry.analyst@make-it-all.co.uk | Team Member | Backend developer, data analyst |
| 9 | Iris Chen | iris.pm@make-it-all.co.uk | Team Leader | Project manager for API project |
| 10 | Jack Wilson | jack.security@make-it-all.co.uk | Technical Specialist | Security specialist |

---

## Comprehensive Dummy Data

Run these SQL statements to populate the database with extensive test data:

```sql
-- =============================================
-- USERS
-- =============================================
-- Password hash for 'Password123!'
SET @pw = '$2y$12$HxjFRWlMRdll/4IZR4ur4eIenbGY.lV4DzIhSQNaEU3zLy2Qq5M1a';

INSERT IGNORE INTO users (email, password_hash, is_registered, first_name, last_name, role, specialties, profile_picture) VALUES
('alice.manager@make-it-all.co.uk', @pw, TRUE, 'Alice', 'Johnson', 'manager', '["Project Management","Strategy","Leadership","Budgeting"]', '/default-avatar.png'),
('bob.lead@make-it-all.co.uk', @pw, TRUE, 'Bob', 'Smith', 'team_leader', '["Backend","Python","SQL","API Design","Django"]', '/default-avatar.png'),
('carol.dev@make-it-all.co.uk', @pw, TRUE, 'Carol', 'Williams', 'team_member', '["Frontend","React","CSS","JavaScript","TypeScript"]', '/default-avatar.png'),
('dave.dev@make-it-all.co.uk', NULL, FALSE, 'Dave', 'Brown', 'team_member', '["Backend","Node.js","MongoDB","Express"]', '/default-avatar.png'),
('emma.specialist@make-it-all.co.uk', @pw, TRUE, 'Emma', 'Davis', 'technical_specialist', '["DevOps","AWS","Docker","CI/CD","Kubernetes"]', '/default-avatar.png'),
('frank.designer@make-it-all.co.uk', @pw, TRUE, 'Frank', 'Miller', 'team_member', '["UI Design","Figma","Prototyping","CSS","Adobe XD"]', '/default-avatar.png'),
('grace.tester@make-it-all.co.uk', @pw, TRUE, 'Grace', 'Taylor', 'team_member', '["Frontend","JavaScript","React","Node.js","Testing"]', '/default-avatar.png'),
('henry.analyst@make-it-all.co.uk', @pw, TRUE, 'Henry', 'Anderson', 'team_member', '["SQL","Python","Backend","API Design","Data Analysis"]', '/default-avatar.png'),
('iris.pm@make-it-all.co.uk', @pw, TRUE, 'Iris', 'Chen', 'team_leader', '["Project Management","Agile","Scrum","API Design","Documentation"]', '/default-avatar.png'),
('jack.security@make-it-all.co.uk', @pw, TRUE, 'Jack', 'Wilson', 'technical_specialist', '["Security","Penetration Testing","OAuth","Encryption","Compliance"]', '/default-avatar.png');

-- =============================================
-- PROJECTS
-- =============================================
INSERT IGNORE INTO projects (project_name, description, status, deadline, created_by, team_leader_id) VALUES
('Website Redesign', 'Complete overhaul of the company website with new branding, improved UX, and modern tech stack. Includes responsive design for mobile and tablet.', 'active', '2025-12-15', 1, 2),
('Mobile App Development', 'Native mobile app for iOS and Android platforms with offline capabilities, push notifications, and biometric authentication.', 'active', '2025-11-30', 1, 2),
('Internal Tools Upgrade', 'Modernize internal dashboards and reporting tools, integrate with Salesforce and analytics platforms.', 'active', '2026-01-31', 1, 2),
('REST API V2', 'Design and implement RESTful API v2 with improved authentication, rate limiting, and comprehensive documentation.', 'active', '2026-02-28', 1, 9),
('Cloud Migration', 'Migrate on-premise infrastructure to AWS cloud with zero downtime and improved scalability.', 'active', '2026-03-15', 1, 5),
('Security Audit', 'Comprehensive security audit of all systems, penetration testing, and implementation of security improvements.', 'active', '2025-12-30', 1, 10),
('E-commerce Platform', 'Build new e-commerce platform with payment processing, inventory management, and customer portal.', 'archived', '2024-08-30', 1, 9),
('Legacy System Decommission', 'Phase out old CRM system and migrate data to new platform.', 'archived', '2024-11-15', 1, 2);

-- =============================================
-- PROJECT MEMBERS
-- =============================================
-- Website Redesign (project 1) - 5 members
INSERT IGNORE INTO project_members (project_id, user_id, project_role) VALUES
(1, 2, 'team_leader'),
(1, 3, 'member'),
(1, 6, 'member'),
(1, 7, 'member'),
(1, 10, 'member'),
-- Mobile App Development (project 2) - 5 members
(2, 2, 'team_leader'),
(2, 3, 'member'),
(2, 5, 'member'),
(2, 6, 'member'),
(2, 8, 'member'),
-- Internal Tools (project 3) - 4 members
(3, 2, 'team_leader'),
(3, 3, 'member'),
(3, 5, 'member'),
(3, 8, 'member'),
-- REST API V2 (project 4) - 6 members
(4, 9, 'team_leader'),
(4, 2, 'member'),
(4, 8, 'member'),
(4, 10, 'member'),
(4, 3, 'member'),
(4, 7, 'member'),
-- Cloud Migration (project 5) - 3 members
(5, 5, 'team_leader'),
(5, 2, 'member'),
(5, 10, 'member'),
-- Security Audit (project 6) - 4 members
(6, 10, 'team_leader'),
(6, 5, 'member'),
(6, 2, 'member'),
(6, 8, 'member'),
-- E-commerce Platform (archived) - 3 members
(7, 9, 'team_leader'),
(7, 3, 'member'),
(7, 8, 'member'),
-- Legacy System Decommission (archived) - 2 members
(8, 2, 'team_leader'),
(8, 8, 'member');

-- =============================================
-- TASKS - Website Redesign (Project 1)
-- =============================================
INSERT IGNORE INTO tasks (task_name, description, project_id, status, priority, deadline, created_by, created_date) VALUES
-- Completed tasks
('Design homepage mockup', 'Create Figma mockups for the new homepage design with hero section, feature highlights, and testimonials', 1, 'completed', 'high', '2025-10-30', 1, '2025-09-15'),
('Brand guidelines document', 'Finalize brand guidelines including color palette, typography, logo usage, and tone of voice', 1, 'completed', 'high', '2025-10-25', 1, '2025-09-10'),
('User research interviews', 'Conduct 10 user interviews to understand pain points with current website', 1, 'completed', 'medium', '2025-10-20', 1, '2025-09-08'),
('Wireframes for all pages', 'Create low-fidelity wireframes for homepage, about, services, contact, and blog pages', 1, 'completed', 'high', '2025-10-28', 1, '2025-09-12'),
-- In Progress tasks
('Implement navigation bar', 'Build responsive navbar component with dropdown menus, mobile hamburger menu, and accessibility features', 1, 'in_progress', 'high', '2025-11-10', 1, '2025-09-20'),
('Redesign footer section', 'Update footer with new links, social media icons, newsletter signup, and company information', 1, 'in_progress', 'low', '2025-11-15', 1, '2025-10-05'),
('Accessibility compliance', 'Ensure WCAG 2.1 AA compliance across all pages, including keyboard navigation and screen reader support', 1, 'in_progress', 'high', '2025-11-25', 1, '2025-10-15'),
('Performance optimization', 'Optimize images, implement lazy loading, code splitting, and improve Core Web Vitals scores', 1, 'in_progress', 'medium', '2025-11-20', 1, '2025-10-18'),
-- To Do tasks
('Setup CI/CD pipeline', 'Configure GitHub Actions for automated testing, linting, and deployment to staging and production', 1, 'to_do', 'medium', '2025-11-20', 1, '2025-09-25'),
('Write unit tests for auth', 'Unit tests for login, register, password reset, and session management with 90% coverage', 1, 'to_do', 'medium', '2025-11-25', 1, '2025-10-01'),
('Blog system implementation', 'Build blog CMS with markdown support, categories, tags, and SEO optimization', 1, 'to_do', 'low', '2025-12-01', 1, '2025-10-10'),
('Contact form backend', 'Implement contact form with spam protection, email notifications, and database logging', 1, 'to_do', 'medium', '2025-11-18', 1, '2025-10-12'),
('Analytics integration', 'Integrate Google Analytics 4 and configure custom events for conversion tracking', 1, 'to_do', 'low', '2025-11-22', 1, '2025-10-20');

-- =============================================
-- TASKS - Mobile App Development (Project 2)
-- =============================================
INSERT IGNORE INTO tasks (task_name, description, project_id, status, priority, deadline, created_by, created_date) VALUES
-- Completed tasks
('Setup React Native project', 'Initialize project with proper folder structure, ESLint, Prettier, and TypeScript configuration', 2, 'completed', 'high', '2025-10-15', 1, '2025-09-10'),
('Design system foundation', 'Create reusable UI components library with consistent styling and theming support', 2, 'completed', 'high', '2025-10-22', 1, '2025-09-14'),
('Authentication flow design', 'Design user flows for login, registration, password reset, and biometric authentication', 2, 'completed', 'high', '2025-10-28', 1, '2025-09-16'),
-- In Progress tasks
('Build login screen', 'Implement login UI with email/password fields, social login buttons, and remember me option', 2, 'in_progress', 'high', '2025-11-05', 1, '2025-09-18'),
('Offline mode implementation', 'Implement offline data persistence using AsyncStorage and sync when connection restores', 2, 'in_progress', 'high', '2025-11-12', 1, '2025-10-01'),
('User profile screen', 'Build profile screen with avatar upload, personal info editing, and account settings', 2, 'in_progress', 'medium', '2025-11-08', 1, '2025-10-05'),
-- To Do tasks
('API integration layer', 'Create API service layer for backend communication with retry logic and error handling', 2, 'to_do', 'high', '2025-11-20', 1, '2025-09-22'),
('Push notifications', 'Implement push notification system for iOS and Android using Firebase Cloud Messaging', 2, 'to_do', 'medium', '2025-12-01', 1, '2025-10-01'),
('Biometric authentication', 'Add Face ID and fingerprint authentication support for iOS and Android', 2, 'to_do', 'high', '2025-11-15', 1, '2025-10-08'),
('App Store submission', 'Prepare app metadata, screenshots, privacy policy, and submit to App Store and Play Store', 2, 'to_do', 'high', '2025-11-28', 1, '2025-10-20'),
('Deep linking', 'Implement deep linking for seamless navigation from web to app', 2, 'to_do', 'low', '2025-12-05', 1, '2025-10-15'),
('Crash reporting', 'Integrate Sentry for crash reporting and performance monitoring', 2, 'to_do', 'medium', '2025-11-18', 1, '2025-10-12');

-- =============================================
-- TASKS - Internal Tools Upgrade (Project 3)
-- =============================================
INSERT IGNORE INTO tasks (task_name, description, project_id, status, priority, deadline, created_by, created_date) VALUES
-- Completed tasks
('Dashboard wireframes', 'Design wireframes for the new analytics dashboard with KPI cards and charts', 3, 'completed', 'medium', '2025-10-25', 1, '2025-09-12'),
('Database schema design', 'Design optimized database schema for reporting and analytics with proper indexing', 3, 'completed', 'high', '2025-10-30', 1, '2025-09-15'),
-- In Progress tasks
('Reporting API endpoints', 'Build REST API endpoints for generating reports with filters, pagination, and export options', 3, 'in_progress', 'high', '2025-11-30', 1, '2025-09-28'),
('Chart component library', 'Create reusable chart components using Chart.js for line, bar, and pie charts', 3, 'in_progress', 'medium', '2025-11-15', 1, '2025-10-05'),
-- To Do tasks
('Data migration script', 'Write Python script to migrate data from old system with data validation and error handling', 3, 'to_do', 'high', '2025-12-15', 1, '2025-10-10'),
('Salesforce integration', 'Integrate with Salesforce API for real-time data sync', 3, 'to_do', 'high', '2025-12-20', 1, '2025-10-12'),
('Export to Excel', 'Add functionality to export reports to Excel with formatting and charts', 3, 'to_do', 'medium', '2025-12-10', 1, '2025-10-18'),
('Email report scheduling', 'Implement scheduled email reports with customizable frequency and recipients', 3, 'to_do', 'low', '2025-12-28', 1, '2025-10-22'),
('Dashboard personalization', 'Allow users to customize dashboard layout and save widget preferences', 3, 'to_do', 'low', '2026-01-15', 1, '2025-10-25');

-- =============================================
-- TASKS - REST API V2 (Project 4)
-- =============================================
INSERT IGNORE INTO tasks (task_name, description, project_id, status, priority, deadline, created_by, created_date) VALUES
-- Completed tasks
('API specification document', 'Write OpenAPI 3.0 specification with all endpoints, request/response schemas, and examples', 4, 'completed', 'high', '2025-11-05', 1, '2025-09-20'),
('Authentication redesign', 'Design OAuth 2.0 + JWT authentication flow with refresh tokens', 4, 'completed', 'high', '2025-11-08', 1, '2025-09-22'),
-- In Progress tasks
('Rate limiting implementation', 'Implement rate limiting with Redis, different tiers for API keys', 4, 'in_progress', 'high', '2025-11-20', 1, '2025-10-01'),
('Versioning strategy', 'Implement API versioning via URL path with backward compatibility for v1', 4, 'in_progress', 'medium', '2025-11-18', 1, '2025-10-05'),
('Error handling standardization', 'Standardize error responses with consistent structure and helpful messages', 4, 'in_progress', 'high', '2025-11-22', 1, '2025-10-08'),
-- To Do tasks
('Webhook system', 'Build webhook system for real-time event notifications to client applications', 4, 'to_do', 'medium', '2025-12-05', 1, '2025-10-10'),
('API documentation site', 'Create interactive API documentation using Swagger UI with code examples', 4, 'to_do', 'high', '2025-12-01', 1, '2025-10-12'),
('SDK for JavaScript', 'Develop official JavaScript/TypeScript SDK for the API', 4, 'to_do', 'medium', '2025-12-15', 1, '2025-10-15'),
('SDK for Python', 'Develop official Python SDK with async support', 4, 'to_do', 'medium', '2025-12-20', 1, '2025-10-18'),
('Load testing', 'Perform load testing with 10,000 concurrent users and optimize bottlenecks', 4, 'to_do', 'high', '2026-01-10', 1, '2025-10-20');

-- =============================================
-- TASKS - Cloud Migration (Project 5)
-- =============================================
INSERT IGNORE INTO tasks (task_name, description, project_id, status, priority, deadline, created_by, created_date) VALUES
-- Completed tasks
('AWS account setup', 'Setup AWS Organization, accounts for dev/staging/prod, and IAM policies', 5, 'completed', 'high', '2025-10-20', 1, '2025-09-15'),
('Infrastructure as Code', 'Write Terraform scripts for VPC, subnets, security groups, and load balancers', 5, 'completed', 'high', '2025-10-28', 1, '2025-09-18'),
-- In Progress tasks
('Database migration plan', 'Create detailed plan for migrating PostgreSQL to RDS with minimal downtime', 5, 'in_progress', 'high', '2025-11-25', 1, '2025-10-01'),
('Docker containerization', 'Containerize all services and create Kubernetes deployment manifests', 5, 'in_progress', 'high', '2025-11-30', 1, '2025-10-05'),
('Monitoring setup', 'Setup CloudWatch, Prometheus, and Grafana for comprehensive monitoring', 5, 'in_progress', 'medium', '2025-12-05', 1, '2025-10-10'),
-- To Do tasks
('Load balancer configuration', 'Configure Application Load Balancer with health checks and SSL certificates', 5, 'to_do', 'high', '2025-12-10', 1, '2025-10-12'),
('Auto-scaling setup', 'Implement auto-scaling policies based on CPU and memory metrics', 5, 'to_do', 'high', '2025-12-15', 1, '2025-10-15'),
('Backup strategy', 'Implement automated backups with retention policies and disaster recovery plan', 5, 'to_do', 'high', '2025-12-20', 1, '2025-10-18'),
('Cost optimization', 'Analyze and optimize AWS costs, implement Reserved Instances where applicable', 5, 'to_do', 'medium', '2026-01-05', 1, '2025-10-20'),
('Documentation', 'Document architecture, deployment procedures, and troubleshooting guides', 5, 'to_do', 'medium', '2026-01-15', 1, '2025-10-22');

-- =============================================
-- TASKS - Security Audit (Project 6)
-- =============================================
INSERT IGNORE INTO tasks (task_name, description, project_id, status, priority, deadline, created_by, created_date) VALUES
-- Completed tasks
('Security policy review', 'Review and update security policies for access control, data handling, and incident response', 6, 'completed', 'high', '2025-10-25', 1, '2025-09-18'),
('Vulnerability scanning', 'Run automated vulnerability scans using OWASP ZAP and Nessus', 6, 'completed', 'high', '2025-10-30', 1, '2025-09-20'),
-- In Progress tasks
('Penetration testing', 'Conduct manual penetration testing of web applications and APIs', 6, 'in_progress', 'high', '2025-11-20', 1, '2025-10-01'),
('Code security audit', 'Review codebase for security vulnerabilities, SQL injection, XSS, CSRF', 6, 'in_progress', 'high', '2025-11-25', 1, '2025-10-05'),
-- To Do tasks
('Fix critical vulnerabilities', 'Address all critical and high severity vulnerabilities found during audit', 6, 'to_do', 'high', '2025-12-05', 1, '2025-10-08'),
('Implement 2FA', 'Add two-factor authentication support for all user accounts', 6, 'to_do', 'high', '2025-12-10', 1, '2025-10-10'),
('Security training', 'Conduct security awareness training for all developers', 6, 'to_do', 'medium', '2025-12-15', 1, '2025-10-12'),
('Compliance documentation', 'Prepare documentation for SOC 2 and GDPR compliance', 6, 'to_do', 'high', '2025-12-20', 1, '2025-10-15'),
('Incident response plan', 'Create comprehensive incident response plan with on-call rotation', 6, 'to_do', 'medium', '2025-12-28', 1, '2025-10-18');

-- =============================================
-- TASK ASSIGNMENTS
-- =============================================
INSERT IGNORE INTO task_assignments (task_id, user_id, assigned_by, user_status) VALUES
-- Website Redesign assignments (tasks 1-13)
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
-- Mobile App assignments (tasks 14-25)
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
-- Internal Tools assignments (tasks 26-34)
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
-- REST API V2 assignments (tasks 35-44)
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
-- Cloud Migration assignments (tasks 45-54)
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
-- Security Audit assignments (tasks 55-63)
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
-- KNOWLEDGE BASE TOPICS
-- =============================================
INSERT IGNORE INTO kb_topics (topic_name, description, created_by) VALUES
('General Discussion', 'Open discussions about company and team topics, announcements, and team building', 1),
('Technical Help', 'Ask for help with technical issues, bugs, errors, and troubleshooting', 1),
('Best Practices', 'Share and discuss development best practices, code standards, and methodologies', 1),
('Project Updates', 'Updates and announcements about ongoing projects, milestones, and achievements', 1),
('DevOps & Infrastructure', 'Discussions about deployment, CI/CD, cloud infrastructure, and monitoring', 1),
('Security & Compliance', 'Security best practices, vulnerability reports, and compliance requirements', 1),
('Career Development', 'Learning resources, certifications, career advice, and professional growth', 1),
('Tools & Resources', 'Share useful tools, libraries, frameworks, and development resources', 1);

-- =============================================
-- KNOWLEDGE BASE POSTS
-- =============================================
INSERT IGNORE INTO kb_posts (title, content, topic_id, author_id, tags, like_count, comment_count) VALUES
-- General Discussion
('Welcome to Make-It-All!', 'Hello everyone! This is our new knowledge base platform. Feel free to ask questions, share knowledge, and help each other out.\n\nLets make this a great resource for our team. Remember to:\n- Search before posting to avoid duplicates\n- Use descriptive titles\n- Tag your posts appropriately\n- Be respectful and constructive\n\nLooking forward to seeing this grow!', 1, 1, '["welcome","announcement","guidelines"]', 18, 5),
('Team lunch next Friday!', 'Reminder that we have our monthly team lunch next Friday at 12:30 PM at The Italian Place downtown.\n\nPlease RSVP in the Slack channel by Wednesday so we can get an accurate headcount. Dietary restrictions? Let me know!\n\nLooking forward to seeing everyone there!', 1, 1, '["social","lunch","event"]', 12, 3),
('New meeting room booking system', 'We have launched a new meeting room booking system! You can now book rooms through the company portal.\n\nFeatures:\n- See real-time availability\n- Recurring bookings\n- Automatic calendar invites\n- Equipment requests\n\nCheck it out at https://rooms.make-it-all.co.uk', 1, 1, '["announcement","facilities","tools"]', 8, 2),
-- Technical Help
('How to setup the development environment', 'Step 1: Clone the repository\n```bash\ngit clone https://github.com/make-it-all/main-app.git\ncd main-app\n```\n\nStep 2: Install dependencies\n```bash\nnpm install\n```\n\nStep 3: Configure your .env file\n```bash\ncp .env.example .env\n# Edit .env with your database credentials\n```\n\nStep 4: Start the dev server\n```bash\nnpm run dev\n```\n\nMake sure you have Node.js v18+ installed. For Windows users, use Git Bash or WSL2.', 2, 3, '["setup","development","guide","getting-started"]', 15, 4),
('Debugging CORS issues in API calls', 'If you are getting CORS errors when making API calls from the frontend:\n\n1. Make sure the API server includes proper CORS headers:\n```javascript\nres.header("Access-Control-Allow-Origin", process.env.FRONTEND_URL);\nres.header("Access-Control-Allow-Credentials", "true");\n```\n\n2. For development, you can use the proxy in vite.config.js:\n```javascript\nserver: {\n  proxy: {\n    "/api": "http://localhost:3000"\n  }\n}\n```\n\n3. For production, ensure your API domain is whitelisted in the CORS config.\n\nStill having issues? Check the network tab for the actual error response.', 2, 8, '["cors","api","debugging","frontend","backend"]', 11, 2),
('Database connection pool exhaustion', 'Ran into an issue today where our database connection pool was getting exhausted under load.\n\nThe problem: We were not properly closing database connections in error handlers.\n\nThe solution:\n```javascript\ntry {\n  const result = await db.query(sql);\n  return result;\n} catch (error) {\n  console.error(error);\n  throw error;\n} finally {\n  // IMPORTANT: Always release connection\n  await db.release();\n}\n```\n\nAlso increased pool size from 10 to 20 in production config. Monitoring shows much better performance now.', 2, 2, '["database","mysql","connection-pool","performance"]', 9, 1),
-- Best Practices
('Git branching strategy', 'We use the following branching strategy:\n\n**Main Branches:**\n- `main`: production-ready code, protected, requires PR + review\n- `develop`: integration branch for features, auto-deployed to staging\n\n**Supporting Branches:**\n- `feature/*`: new features (e.g., feature/user-authentication)\n- `bugfix/*`: bug fixes for develop\n- `hotfix/*`: urgent production fixes, branch from main\n- `release/*`: release preparation (version bump, final fixes)\n\n**Workflow:**\n1. Create feature branch from develop\n2. Make changes and commit regularly\n3. Push and create PR to develop\n4. Get at least one review\n5. Squash and merge to develop\n\n**Commit Messages:**\nFollow conventional commits format:\n```\nfeat: add user login\nfix: resolve navbar mobile bug\ndocs: update API documentation\ntest: add unit tests for auth\n```', 3, 2, '["git","branching","workflow","version-control"]', 22, 3),
('React component best practices', 'Here are some tips for writing clean React components:\n\n**1. Keep components small and focused**\nEach component should do one thing well. If a component is getting too large, split it.\n\n**2. Use custom hooks for shared logic**\n```javascript\n// Good example\nfunction useAuth() {\n  const [user, setUser] = useState(null);\n  // ... auth logic\n  return { user, login, logout };\n}\n```\n\n**3. Prefer composition over inheritance**\n```javascript\n// Good example\n<Card>\n  <CardHeader title="Hello" />\n  <CardBody>Content</CardBody>\n</Card>\n```\n\n**4. Use TypeScript for type safety**\nDefine prop types and interfaces for better autocomplete and error catching.\n\n**5. Write tests for critical components**\nUse React Testing Library for component tests, focus on user behavior not implementation.', 3, 3, '["react","frontend","best-practices","typescript"]', 14, 2),
('API error handling patterns', 'Consistent error handling makes debugging easier. Here is our standard approach:\n\n**Backend (Express):**\n```javascript\nclass ApiError extends Error {\n  constructor(statusCode, message, details = null) {\n    super(message);\n    this.statusCode = statusCode;\n    this.details = details;\n  }\n}\n\napp.use((err, req, res, next) => {\n  const status = err.statusCode || 500;\n  res.status(status).json({\n    error: {\n      message: err.message,\n      status: status,\n      details: err.details\n    }\n  });\n});\n```\n\n**Frontend:**\n```javascript\ntry {\n  const res = await fetch("/api/users");\n  if (!res.ok) {\n    const error = await res.json();\n    throw new Error(error.error.message);\n  }\n  return await res.json();\n} catch (error) {\n  showNotification(error.message, "error");\n}\n```\n\nAlways return consistent error structures!', 3, 2, '["api","error-handling","backend","best-practices"]', 17, 1),
('Code review checklist', 'Use this checklist when reviewing PRs:\n\n**Functionality:**\n- Code works as intended\n- Edge cases handled\n- No obvious bugs\n\n**Code Quality:**\n- Easy to read and understand\n- Follows project conventions\n- No code duplication\n- Appropriate comments\n\n**Testing:**\n- Unit tests included\n- Tests actually test the right things\n- Edge cases covered\n\n**Security:**\n- No hardcoded secrets\n- Input validation\n- SQL injection prevention\n- XSS prevention\n\n**Performance:**\n- No unnecessary database calls\n- Efficient algorithms\n- Images optimized\n\n**Documentation:**\n- README updated if needed\n- API docs updated\n- Complex logic explained\n\nDo not be afraid to ask questions!', 3, 1, '["code-review","checklist","best-practices","quality"]', 19, 2),
-- Project Updates
('Website Redesign - Progress Update', 'Great progress on the website redesign project this sprint!\n\n**Completed:**\n- All homepage mockups finalized\n- Brand guidelines approved\n- User research completed\n\n**In Progress:**\n- Navigation bar implementation\n- Footer redesign\n- Accessibility compliance audit\n\n**Upcoming:**\n- CI/CD pipeline setup\n- Blog system implementation\n\n**Blockers:**\nNone currently!\n\nWe are on track for the December 15th deadline. Great work team!', 4, 2, '["website-redesign","progress","update"]', 16, 4),
('Mobile App - Beta Testing Next Week', 'Exciting news! We are ready to start beta testing the mobile app next week.\n\n**What we need from you:**\n- Install TestFlight (iOS) or join the beta program (Android)\n- Test core features: login, profile, offline mode\n- Report any bugs or UX issues\n\nI will send out detailed testing instructions on Monday. Looking forward to your feedback!\n\nBig thanks to Frank, Emma, and Henry for getting us to this point!', 4, 2, '["mobile-app","beta","testing","announcement"]', 13, 2),
-- DevOps & Infrastructure
('Docker setup for local development', 'To run the app locally with Docker:\n\n**Prerequisites:**\n- Docker Desktop installed\n- At least 4GB RAM allocated to Docker\n\n**Setup:**\n```bash\n# Clone and navigate to project\ngit clone https://github.com/make-it-all/main-app.git\ncd main-app\n\n# Copy environment file\ncp .env.example .env\n\n# Start all services\ndocker-compose up -d\n```\n\n**Services:**\n- MySQL: localhost:3306\n- PHP/Apache: localhost:8080\n- phpMyAdmin: localhost:8081\n\n**Useful commands:**\n```bash\n# View logs\ndocker-compose logs -f\n\n# Restart services\ndocker-compose restart\n\n# Stop all services\ndocker-compose down\n\n# Rebuild after config changes\ndocker-compose up -d --build\n```\n\nIf you get permission errors on Linux, add your user to the docker group.', 5, 5, '["docker","devops","setup","local-development"]', 20, 3),
('CI/CD pipeline overview', 'Our CI/CD pipeline runs on GitHub Actions with these stages:\n\n**On Pull Request:**\n1. Linting (ESLint, Prettier)\n2. Unit tests (Jest)\n3. Integration tests\n4. Build verification\n5. Security scan (npm audit)\n\n**On Merge to Develop:**\n1. Run full test suite\n2. Build Docker image\n3. Push to container registry\n4. Deploy to staging\n5. Run smoke tests\n\n**On Merge to Main:**\n1. Create release tag\n2. Build production image\n3. Deploy to production (blue-green)\n4. Run health checks\n5. Notify team in Slack\n\n**Rollback:**\nIf deployment fails, previous version is automatically restored.\n\nAverage deploy time: 8 minutes', 5, 5, '["cicd","github-actions","deployment","automation"]', 11, 1),
('Monitoring and alerting setup', 'Our monitoring stack:\n\n**Application Monitoring:**\n- New Relic for APM\n- Sentry for error tracking\n- Custom metrics to CloudWatch\n\n**Infrastructure Monitoring:**\n- CloudWatch for AWS resources\n- Grafana dashboards\n- Uptime monitoring with Pingdom\n\n**Alerts:**\nSlack notifications for:\n- Error rate > 1%\n- Response time > 2s (p95)\n- CPU usage > 80%\n- Disk space < 20%\n- Failed deployments\n\n**On-Call Rotation:**\nPagerDuty for critical alerts (P1)\nWeekly rotation schedule\n\nAll dashboards accessible at https://monitoring.make-it-all.co.uk', 5, 5, '["monitoring","alerting","observability","cloudwatch"]', 7, 1),
-- Security & Compliance
('Security best practices checklist', 'Follow these security best practices for all projects:\n\n**Authentication:**\n- Use bcrypt for password hashing (cost factor 12+)\n- Implement rate limiting on auth endpoints\n- Support 2FA\n- Session timeout after 30 min inactivity\n\n**Authorization:**\n- Principle of least privilege\n- Role-based access control\n- Validate permissions on every request\n\n**Data Protection:**\n- Encrypt sensitive data at rest\n- Use HTTPS everywhere\n- Sanitize user inputs\n- Parameterized SQL queries\n\n**API Security:**\n- API key authentication\n- Rate limiting\n- Input validation\n- CORS configuration\n\n**Dependencies:**\n- Regular npm audit\n- Automated dependency updates\n- Review third-party packages\n\nSee the full security policy in the wiki.', 6, 10, '["security","best-practices","checklist","compliance"]', 15, 2),
('OWASP Top 10 for web developers', 'Understanding the OWASP Top 10 vulnerabilities:\n\n**1. Broken Access Control**\nAlways verify permissions server-side, never trust client.\n\n**2. Cryptographic Failures**\nUse TLS everywhere, bcrypt for passwords, never roll your own crypto.\n\n**3. Injection**\nUse parameterized queries, ORM, input validation.\n\n**4. Insecure Design**\nThreat model during design phase, principle of least privilege.\n\n**5. Security Misconfiguration**\nDisable debug mode in production, remove default accounts.\n\n**6. Vulnerable Components**\nKeep dependencies updated, use npm audit.\n\n**7. Authentication Failures**\nImplement MFA, rate limiting, secure session management.\n\n**8. Software Integrity Failures**\nVerify dependencies, use code signing.\n\n**9. Logging Failures**\nLog security events, monitor for anomalies.\n\n**10. SSRF**\nValidate and sanitize URLs, use allowlists.\n\nSee https://owasp.org/Top10 for details.', 6, 10, '["security","owasp","vulnerabilities","education"]', 12, 1),
-- Career Development
('Recommended learning resources', 'Here are some great resources for professional development:\n\n**Online Courses:**\n- Frontend Masters (React, TypeScript, Node.js)\n- Udemy (specific tech deep dives)\n- Pluralsight (enterprise software skills)\n- AWS Training and Certification\n\n**Books:**\n- "Clean Code" by Robert Martin\n- "Designing Data-Intensive Applications" by Martin Kleppmann\n- "The Pragmatic Programmer" by Hunt & Thomas\n- "System Design Interview" by Alex Xu\n\n**Newsletters:**\n- JavaScript Weekly\n- Node Weekly\n- React Status\n- TLDR Newsletter\n\n**YouTube Channels:**\n- Fireship (quick tech overviews)\n- Web Dev Simplified\n- Theo - t3.gg\n\n**Podcasts:**\n- Syntax.fm\n- Shop Talk Show\n- JS Party\n\nCompany reimburses $500/year for learning materials!', 7, 1, '["learning","resources","career","professional-development"]', 16, 5),
('AWS certification path', 'Planning to get AWS certified? Here is a recommended path:\n\n**Beginner:**\n1. AWS Certified Cloud Practitioner\n   - Good overview of AWS services\n   - Not technical, focused on business value\n   - Takes approximately 2 weeks to prepare\n\n**Intermediate:**\n2. AWS Certified Solutions Architect - Associate\n   - Most popular certification\n   - Covers architecture best practices\n   - Takes approximately 1-2 months to prepare\n\n**Advanced:**\n3. AWS Certified Solutions Architect - Professional\n   OR\n3. AWS Certified DevOps Engineer - Professional\n   - Very challenging\n   - Deep technical knowledge required\n   - Takes approximately 3-4 months to prepare\n\n**Study Resources:**\n- A Cloud Guru courses\n- AWS free tier for hands-on practice\n- AWS whitepapers\n- Practice exams\n\nCompany covers certification exam costs!', 7, 5, '["aws","certification","career","learning"]', 10, 3),
-- Tools & Resources
('VS Code extensions for web development', 'Essential VS Code extensions we recommend:\n\n**Productivity:**\n- GitLens (advanced Git integration)\n- Auto Rename Tag\n- Prettier (code formatting)\n- ESLint\n- Path Intellisense\n\n**React/Frontend:**\n- ES7+ React snippets\n- Tailwind CSS IntelliSense\n- CSS Peek\n\n**Backend:**\n- REST Client (API testing in VS Code)\n- Database Client\n- Docker\n\n**Themes:**\n- One Dark Pro\n- Material Icon Theme\n\n**Other:**\n- Live Share (pair programming)\n- TODO Highlight\n- Better Comments\n\nSettings sync across devices using GitHub!', 8, 3, '["vscode","tools","extensions","productivity"]', 13, 2),
('Useful npm packages for Node.js', 'Here are npm packages we use frequently:\n\n**Express Middleware:**\n- helmet (security headers)\n- cors (CORS handling)\n- express-rate-limit (rate limiting)\n- express-validator (input validation)\n- morgan (HTTP logging)\n\n**Utilities:**\n- lodash (utility functions)\n- date-fns (date manipulation)\n- axios (HTTP client)\n- dotenv (environment variables)\n- uuid (unique ID generation)\n\n**Database:**\n- mysql2 (MySQL client)\n- mongoose (MongoDB ODM)\n- sequelize (SQL ORM)\n\n**Testing:**\n- jest (test framework)\n- supertest (API testing)\n- @testing-library/react\n\n**Dev Tools:**\n- nodemon (auto-restart)\n- eslint\n- prettier\n\nAlways check npm trends before adding dependencies!', 8, 2, '["npm","nodejs","packages","tools"]', 9, 1),
('Chrome DevTools tips and tricks', 'Level up your debugging with these Chrome DevTools tips:\n\n**Console:**\n- Use `console.table()` for arrays/objects\n- `$0` references selected DOM element\n- `$_` returns last evaluated expression\n- `monitor(function)` logs function calls\n\n**Network:**\n- Right-click and Copy as fetch for API replay\n- Filter by domain: `domain:make-it-all.co.uk`\n- Throttle network to test slow connections\n\n**Sources:**\n- Conditional breakpoints (right-click line number)\n- `debugger;` statement in code\n- Blackbox scripts to skip library code\n\n**Performance:**\n- Record and analyze page load\n- Screenshot on timeline\n- Memory heap snapshots for leak detection\n\n**Application:**\n- Inspect cookies, localStorage, IndexedDB\n- Clear site data\n- Service worker debugging\n\nF12 is your best friend!', 8, 7, '["chrome","devtools","debugging","tips"]', 11, 2);

-- =============================================
-- KNOWLEDGE BASE COMMENTS
-- =============================================
INSERT IGNORE INTO kb_comments (post_id, author_id, content) VALUES
-- Comments on "Welcome to Make-It-All!"
(1, 3, 'Excited to use this platform! This will be so much better than digging through Slack messages.'),
(1, 5, 'Looking forward to sharing DevOps knowledge here. Will post some Docker guides soon.'),
(1, 6, 'This is great, thanks for setting it up! Love the clean interface.'),
(1, 2, 'Quick tip: use the search feature before posting to avoid duplicates!'),
(1, 7, 'Can we get categories for different project-specific discussions?'),
-- Comments on "Team lunch next Friday!"
(2, 3, 'I am in! Vegetarian option please.'),
(2, 6, 'Count me in!'),
(2, 5, 'Will be there!'),
-- Comments on "New meeting room booking system"
(3, 2, 'Finally! The old system was so clunky.'),
(3, 8, 'Does it integrate with Google Calendar?'),
-- Comments on "How to setup the development environment"
(4, 5, 'Great guide! I would also recommend using nvm to manage Node.js versions.'),
(4, 2, 'Thanks Carol, this helped me set up quickly. Should we add this to the official docs?'),
(4, 7, 'What about Windows users? Does WSL2 work well?'),
(4, 3, 'Yes, WSL2 works perfectly! I can write a quick addendum if needed.'),
-- Comments on "Debugging CORS issues in API calls"
(5, 3, 'This saved me hours today! Was banging my head against CORS errors.'),
(5, 2, 'We should add this to the troubleshooting guide.'),
-- Comments on "Database connection pool exhaustion"
(6, 5, 'Good catch! I will review our connection handling in the cloud migration project.'),
-- Comments on "Git branching strategy"
(7, 3, 'Should we also use semantic commit messages following conventional commits?'),
(7, 1, 'Yes! I will update the guide. Great suggestion.'),
(7, 8, 'What about the merge vs rebase debate? Do we have a preference?'),
-- Comments on "React component best practices"
(8, 7, 'The composition over inheritance tip is gold. Seen too many deep component hierarchies.'),
(8, 6, 'For point 5, are we using Jest + React Testing Library or something else?'),
-- Comments on "API error handling patterns"
(9, 8, 'We should use this pattern in the new API v2 project. Much cleaner than what we have now.'),
-- Comments on "Code review checklist"
(10, 3, 'This is super helpful! Going to bookmark this for every PR review.'),
(10, 6, 'Can we add design/UX considerations to this list?'),
-- Comments on "Website Redesign - Progress Update"
(11, 3, 'Great progress team! The accessibility work has been really interesting.'),
(11, 6, 'Thanks for the update Bob! Footer redesign should be done by end of week.'),
(11, 7, 'Navigation is coming along nicely. Testing on mobile devices tomorrow.'),
(11, 1, 'Excellent work everyone! Keep it up!'),
-- Comments on "Mobile App - Beta Testing Next Week"
(12, 3, 'Cannot wait to test this! When do we get the TestFlight link?'),
(12, 5, 'Thanks! Excited to see the feedback.'),
-- Comments on "Docker setup for local development"
(13, 3, 'This is super helpful for getting started quickly. No more works on my machine issues!'),
(13, 2, 'Should we add instructions for M1/M2 Mac users? Some platform differences there.'),
(13, 7, 'The phpMyAdmin access is really convenient for debugging database issues.'),
-- Comments on "Security best practices checklist"
(14, 2, 'Thanks Jack! Will review our auth implementation against this checklist.'),
(14, 5, 'We should run through this before every production deployment.'),
-- Comments on "Recommended learning resources"
(15, 3, 'Just finished Clean Code - absolutely worth the read!'),
(15, 7, 'Frontend Masters is amazing! Their TypeScript courses are top-notch.'),
(15, 6, 'Can we create a company library with some of these books?'),
(15, 2, 'I have been listening to Syntax.fm - great for keeping up with JS ecosystem.'),
(15, 5, 'The AWS Training materials helped me prepare for my certification.'),
-- Comments on "AWS certification path"
(16, 2, 'I am planning to get the Solutions Architect Associate cert this quarter!'),
(16, 10, 'The DevOps Professional cert is tough but totally worth it.'),
(16, 3, 'Does the company reimburse for practice exams too?'),
-- Comments on "VS Code extensions for web development"
(17, 7, 'GitLens is a game changer! Cannot imagine working without it now.'),
(17, 2, 'I also recommend the Error Lens extension - shows errors inline.'),
-- Comments on "Useful npm packages for Node.js"
(18, 8, 'We use most of these! date-fns is so much better than moment.js.'),
-- Comments on "Chrome DevTools tips and tricks"
(19, 3, 'The console.table() tip is amazing! Why did I not know about this before?'),
(19, 6, 'Conditional breakpoints have saved me so much debugging time.');

-- =============================================
-- KB POST LIKES
-- =============================================
INSERT IGNORE INTO kb_post_likes (post_id, user_id) VALUES
-- Welcome post (18 likes)
(1, 2), (1, 3), (1, 5), (1, 6), (1, 7), (1, 8), (1, 9), (1, 10),
(1, 1), (1, 4),
-- Team lunch (12 likes)
(2, 2), (2, 3), (2, 5), (2, 6), (2, 7), (2, 8), (2, 9), (2, 10),
(2, 1), (2, 4),
-- Meeting room (8 likes)
(3, 2), (3, 3), (3, 5), (3, 6), (3, 7), (3, 8),
-- Dev environment setup (15 likes)
(4, 2), (4, 5), (4, 6), (4, 7), (4, 8), (4, 9), (4, 10), (4, 1),
-- CORS debugging (11 likes)
(5, 2), (5, 3), (5, 5), (5, 6), (5, 7), (5, 9), (5, 10),
-- DB connection (9 likes)
(6, 3), (6, 5), (6, 7), (6, 8), (6, 9), (6, 10),
-- Git branching (22 likes)
(7, 2), (7, 3), (7, 5), (7, 6), (7, 7), (7, 8), (7, 9), (7, 10), (7, 1),
-- React best practices (14 likes)
(8, 2), (8, 5), (8, 6), (8, 7), (8, 8), (8, 9), (8, 10),
-- API error handling (17 likes)
(9, 3), (9, 5), (9, 6), (9, 7), (9, 8), (9, 9), (9, 10),
-- Code review checklist (19 likes)
(10, 2), (10, 3), (10, 5), (10, 6), (10, 7), (10, 8), (10, 9), (10, 10),
-- Website progress (16 likes)
(11, 1), (11, 3), (11, 5), (11, 6), (11, 7), (11, 8), (11, 9), (11, 10),
-- Mobile app beta (13 likes)
(12, 1), (12, 3), (12, 5), (12, 6), (12, 7), (12, 8), (12, 9),
-- Docker setup (20 likes)
(13, 2), (13, 3), (13, 6), (13, 7), (13, 8), (13, 9), (13, 10), (13, 1),
-- CI/CD pipeline (11 likes)
(14, 2), (14, 3), (14, 6), (14, 7), (14, 8), (14, 9), (14, 10),
-- Monitoring (7 likes)
(15, 2), (15, 3), (15, 6), (15, 8), (15, 10),
-- Security checklist (15 likes)
(16, 2), (16, 3), (16, 5), (16, 6), (16, 7), (16, 8), (16, 9),
-- OWASP Top 10 (12 likes)
(17, 2), (17, 3), (17, 5), (17, 6), (17, 7), (17, 8),
-- Learning resources (16 likes)
(18, 2), (18, 3), (18, 5), (18, 6), (18, 7), (18, 8), (18, 9), (18, 10),
-- AWS cert path (10 likes)
(19, 2), (19, 3), (19, 5), (19, 6), (19, 8), (19, 10),
-- VS Code extensions (13 likes)
(20, 2), (20, 3), (20, 5), (20, 6), (20, 7), (20, 8), (20, 9),
-- npm packages (9 likes)
(21, 2), (21, 3), (21, 5), (21, 6), (21, 7), (21, 8),
-- DevTools tips (11 likes)
(22, 2), (22, 3), (22, 5), (22, 6), (22, 7), (22, 8), (22, 9);

-- =============================================
-- PERSONAL TASKS (for Home page testing)
-- =============================================
INSERT INTO user_personal_tasks (task_name, description, user_id, related_project_id, deadline, is_completed) VALUES
('Review design feedback', 'Check Slack for design feedback from stakeholders', 6, 1, '2025-01-15 17:00:00', FALSE),
('Prepare for 1-on-1', 'List accomplishments and questions for manager meeting', 3, NULL, '2025-01-18 10:00:00', FALSE),
('Research React best practices', 'Read articles on React performance optimization', 3, 1, '2025-01-20 17:00:00', FALSE);

```

---

## Testing Assignments

### Nicole - Testing Areas: Members, Signin/Signout, Settings, Employee Directory, Progress

| # | Feature | Test Case | Account to Use | Expected Result |
|---|---------|-----------|----------------|-----------------|
| 1 | **Sign In** | Login with valid credentials | carol.dev@make-it-all.co.uk / Password123! | Successfully logged in, redirected to projects |
| 2 | **Sign In** | Login with invalid password | carol.dev@make-it-all.co.uk / wrongpassword | Error message shown |
| 3 | **Sign In** | Login as manager | alice.manager@make-it-all.co.uk / Password123! | Home page with manager dashboard visible |
| 4 | **Sign In** | Login as team leader | bob.lead@make-it-all.co.uk / Password123! | Home page with team leader view |
| 5 | **Sign In** | Login as technical specialist | emma.specialist@make-it-all.co.uk / Password123! | Specialist dashboard with appropriate access |
| 6 | **Sign Out** | Click logout from any page | Any account | Redirected to login page, session cleared |
| 7 | **Sign Out** | Try to access protected page after logout | Any account | Redirected to login page |
| 8 | **Settings** | View profile information | carol.dev@make-it-all.co.uk | Shows name, email, role, specialties correctly |
| 9 | **Settings** | Update profile picture | carol.dev@make-it-all.co.uk | New avatar shown across all pages |
| 10 | **Settings** | Change password | carol.dev@make-it-all.co.uk | Old password required, new password works on next login |
| 11 | **Settings** | Update specialties | carol.dev@make-it-all.co.uk | New specialties saved and displayed |
| 12 | **Employee Directory** | View all employees | alice.manager@make-it-all.co.uk | All 10 employees shown in card grid |
| 13 | **Employee Directory** | Search for employee | alice.manager@make-it-all.co.uk | Search "Carol" shows Carol Williams |
| 14 | **Employee Directory** | Filter by role | alice.manager@make-it-all.co.uk | Filtering by "Team Member" shows 5 team members |
| 15 | **Employee Directory** | Filter by specialties | alice.manager@make-it-all.co.uk | Filtering by "Frontend" shows Carol, Grace |
| 16 | **Employee Directory** | Click employee card | alice.manager@make-it-all.co.uk | Opens employee profile page with details |
| 17 | **Employee Directory** | Select mode + Add to Project | alice.manager@make-it-all.co.uk | Select Carol, Grace → Add to Project → Select project → Success |
| 18 | **Employee Directory** | Add multiple members at once | alice.manager@make-it-all.co.uk | Select 3+ employees → Add → All added successfully |
| 19 | **Employee Profile** | View profile with projects/stats | alice.manager@make-it-all.co.uk | Shows specialties, projects list, task statistics chart |
| 20 | **Employee Profile** | View as non-manager | carol.dev@make-it-all.co.uk | Can view but no edit/management options |
| 21 | **Members Tab** | View project members | carol.dev@make-it-all.co.uk | Members tab shows all project members with cards |
| 22 | **Members Tab** | Non-manager cannot remove | carol.dev@make-it-all.co.uk | No "Remove" button visible on cards |
| 23 | **Members Tab** | Manager can remove member | alice.manager@make-it-all.co.uk | Remove button visible, shows task warning, member removed |
| 24 | **Members Tab** | Remove member with solo task | alice.manager@make-it-all.co.uk | Warning shows solo tasks will become unassigned |
| 25 | **Members Tab** | Remove member with shared task | alice.manager@make-it-all.co.uk | No warning for shared tasks, member removed cleanly |
| 26 | **Members Tab** | Re-add removed member | alice.manager@make-it-all.co.uk | Go to Employee Directory → Select employee → Add to project → Success |
| 27 | **Progress** | View progress as team member | carol.dev@make-it-all.co.uk | Progress bar, countdown timer, upcoming deadlines list |
| 28 | **Progress** | View overdue tasks highlighted | carol.dev@make-it-all.co.uk | Overdue tasks shown in red (if any) |
| 29 | **Progress** | Click task in deadlines | carol.dev@make-it-all.co.uk | Task modal with Date Assigned, deadline, status, priority |
| 30 | **Progress** | View progress as manager | alice.manager@make-it-all.co.uk | Manager progress view with team progress bars, Gantt chart |
| 31 | **Progress** | Gantt chart shows all tasks | alice.manager@make-it-all.co.uk | Tasks displayed on timeline with correct dates |
| 32 | **Progress** | Filter progress by team member | alice.manager@make-it-all.co.uk | Dropdown filter → Select member → View their tasks only |

### Sara - Testing Areas: Knowledge Base, Resources, Home, KanBan, Project Overview

| # | Feature | Test Case | Account to Use | Expected Result |
|---|---------|-----------|----------------|-----------------|
| 1 | **Home** | View manager dashboard | alice.manager@make-it-all.co.uk | Project health cards, Create Project button visible |
| 2 | **Home** | View team leader dashboard | bob.lead@make-it-all.co.uk | Project cards, employee dropdown, to-do list |
| 3 | **Home** | View team member dashboard | carol.dev@make-it-all.co.uk | Assigned projects visible with status |
| 4 | **Home** | Personal tasks list | carol.dev@make-it-all.co.uk | Shows pending and completed personal tasks |
| 5 | **Home** | Mark personal task complete | carol.dev@make-it-all.co.uk | Click checkbox → Task marked complete |
| 6 | **Home** | Create new project (manager) | alice.manager@make-it-all.co.uk | Create Project form → fill details → success |
| 7 | **Home** | Quick stats accurate | bob.lead@make-it-all.co.uk | Active projects count, task counts match actual data |
| 8 | **Project Overview** | View all projects | carol.dev@make-it-all.co.uk | Active and archived projects shown in card grid |
| 9 | **Project Overview** | Filter active projects | carol.dev@make-it-all.co.uk | Shows 6 active projects |
| 10 | **Project Overview** | Filter archived projects | carol.dev@make-it-all.co.uk | Shows 2 archived projects |
| 11 | **Project Overview** | Search projects | carol.dev@make-it-all.co.uk | Search "API" shows REST API V2 project |
| 12 | **Project Overview** | Click project card | carol.dev@make-it-all.co.uk | Opens project Kanban board |
| 13 | **Project Overview** | View project details | carol.dev@make-it-all.co.uk | Shows deadline, description, team leader |
| 14 | **KanBan Board** | View task columns | carol.dev@make-it-all.co.uk | 4 columns: To Do, In Progress, Review, Completed |
| 15 | **KanBan Board** | Task counts accurate | carol.dev@make-it-all.co.uk | Column headers show correct task counts |
| 16 | **KanBan Board** | Click task card | carol.dev@make-it-all.co.uk | Task detail modal with all info (title, priority, deadline, Date Assigned, assignees) |
| 17 | **KanBan Board** | View assigned to me filter | carol.dev@make-it-all.co.uk | Filter shows only tasks assigned to Carol |
| 18 | **KanBan Board** | Mark task complete | carol.dev@make-it-all.co.uk | Task moves from current column to Review |
| 19 | **KanBan Board** | Drag task between columns (manager) | alice.manager@make-it-all.co.uk | Task moves to new status, updates saved |
| 20 | **KanBan Board** | Cannot drag as team member | carol.dev@make-it-all.co.uk | Drag disabled for non-managers |
| 21 | **KanBan Board** | Add new task (manager) | alice.manager@make-it-all.co.uk | Click "+" in column → Assign Task form → Submit → Task appears |
| 22 | **KanBan Board** | Assign task to multiple people | alice.manager@make-it-all.co.uk | Select 2+ assignees → Task shows all assignees |
| 23 | **KanBan Board** | Edit task via 3-dot menu (manager) | alice.manager@make-it-all.co.uk | Three-dot menu → Edit Task → form opens → Make changes → Save |
| 24 | **KanBan Board** | Delete task (manager) | alice.manager@make-it-all.co.uk | Three-dot menu → Delete → confirm → task removed |
| 25 | **KanBan Board** | Filter by priority | alice.manager@make-it-all.co.uk | High priority filter → Shows only high priority tasks |
| 26 | **KanBan Board** | Close project (manager) | alice.manager@make-it-all.co.uk | Close Project button with archive icon → confirm → archived |
| 27 | **Resources** | View project resources | carol.dev@make-it-all.co.uk | Project contacts, details, uploaded files section |
| 28 | **Resources** | Upload file (manager/leader) | bob.lead@make-it-all.co.uk | Choose file → Upload → appears in file list with uploader name |
| 29 | **Resources** | Download file | carol.dev@make-it-all.co.uk | Click Download button → file downloads |
| 30 | **Resources** | Delete file (manager/leader) | bob.lead@make-it-all.co.uk | Click delete icon → confirm → file removed |
| 31 | **Resources** | Team member cannot delete | carol.dev@make-it-all.co.uk | No delete button visible |
| 32 | **Resources** | View project details | carol.dev@make-it-all.co.uk | Shows description, deadline, status, team leader |
| 33 | **Knowledge Base** | View all topics | carol.dev@make-it-all.co.uk | 8 topics listed: General, Technical, Best Practices, Updates, DevOps, Security, Career, Tools |
| 34 | **Knowledge Base** | View posts in topic | carol.dev@make-it-all.co.uk | Posts shown with author, date, like count, comment count |
| 35 | **Knowledge Base** | Sort posts by recent | carol.dev@make-it-all.co.uk | Most recent posts appear first |
| 36 | **Knowledge Base** | Sort posts by popular | carol.dev@make-it-all.co.uk | Most liked posts appear first |
| 37 | **Knowledge Base** | Create new post | carol.dev@make-it-all.co.uk | Fill title, content, select topic → submit → post appears |
| 38 | **Knowledge Base** | Add tags to post | carol.dev@make-it-all.co.uk | Enter tags separated by commas → Tags saved and displayed |
| 39 | **Knowledge Base** | Like a post | carol.dev@make-it-all.co.uk | Click thumbs-up → count increases, button turns orange |
| 40 | **Knowledge Base** | Unlike a post | carol.dev@make-it-all.co.uk | Click thumbs-up again → count decreases, button resets to gray |
| 41 | **Knowledge Base** | Add comment | carol.dev@make-it-all.co.uk | Type reply → submit → comment appears with author and timestamp |
| 42 | **Knowledge Base** | View comment count | carol.dev@make-it-all.co.uk | Comment count updates when comment added |
| 43 | **Knowledge Base** | Mark as solved (specialist) | emma.specialist@make-it-all.co.uk | Mark solved button → green "Solved" badge appears |
| 44 | **Knowledge Base** | Cannot mark solved (non-specialist) | carol.dev@make-it-all.co.uk | No mark solved button visible |
| 45 | **Knowledge Base** | Search posts | carol.dev@make-it-all.co.uk | Search "docker" → Docker setup post appears in results |
| 46 | **Knowledge Base** | Search shows relevant results | carol.dev@make-it-all.co.uk | Search "react" → Shows all React-related posts |
| 47 | **Knowledge Base** | Filter by tag | carol.dev@make-it-all.co.uk | Click tag → Shows all posts with that tag |
| 48 | **Knowledge Base** | View post with many comments | carol.dev@make-it-all.co.uk | All comments displayed in chronological order |
| 49 | **Knowledge Base** | Code blocks formatted | carol.dev@make-it-all.co.uk | Code blocks have syntax highlighting and copy button |
| 50 | **Knowledge Base** | Links are black (not blue) | Any account | All hyperlinks appear black, turn orange on hover |

---

## Breadcrumbs Verification (Both testers)

Check that breadcrumbs appear correctly on every page:

| Page | Expected Breadcrumb |
|------|---------------------|
| Project Overview | Projects |
| Project Tasks (KanBan) | Projects > [Project Name] |
| Progress | Projects > [Project Name] > Progress |
| Resources | Projects > [Project Name] > Resources |
| Members | Projects > [Project Name] > Members |
| Knowledge Base Home | Knowledge Base |
| KB Topic View | Knowledge Base > [Topic Name] |
| KB Post View | Knowledge Base > [Topic Name] > [Post Title] |
| Employee Directory | Employees |
| Employee Profile | Employees > [Employee Name] |

---

## Additional Test Scenarios

### Cross-Project Testing (Both testers)

| # | Scenario | Expected Behavior |
|---|----------|-------------------|
| 1 | User assigned to multiple projects | Can switch between projects, sees correct tasks for each |
| 2 | Task deadlines approaching | Upcoming deadlines show on Progress page and Home dashboard |
| 3 | Notifications | User notified when assigned new task, tagged in KB post, etc. |
| 4 | Role-based permissions | Managers see edit/delete options, team members do not |

### Data Integrity Testing (Both testers)

| # | Scenario | Expected Behavior |
|---|----------|-------------------|
| 1 | Delete project with tasks | Confirm dialog warns about tasks, project and tasks deleted |
| 2 | Remove user from project | Tasks remain but show "Unassigned" if user was solo assignee |
| 3 | Mark task complete | Task moves to Completed, user status updates to "completed" |
| 4 | Upload duplicate filename | Either rename or replace with confirmation |

### Performance Testing (Optional)

| # | Scenario | Expected Behavior |
|---|----------|-------------------|
| 1 | Load project with 50+ tasks | Page loads in under 3 seconds |
| 2 | Search KB with 100+ posts | Search returns results in under 2 seconds |
| 3 | Large file upload (10MB+) | Upload progress shown, completes successfully |

---

## Notes

- **Default password for all test accounts is `Password123!`**
- Dave Brown (dave.dev@make-it-all.co.uk) is NOT registered - use this to test the registration/sign-up flow
- Alice (manager) has full access to everything
- Bob & Iris (team_leaders) can manage tasks on their projects but cannot manage employees
- Emma & Jack (technical_specialists) can mark KB posts as solved
- Carol, Frank, Grace, Henry (team_members) have limited access - can view and complete tasks

## Bug Reporting Format

When you find a bug, please report it using this format:

```
**Bug Title:** Short description of the issue

**Steps to Reproduce:**
1. Step one
2. Step two
3. Step three

**Expected Result:** What should happen

**Actual Result:** What actually happens

**Account Used:** email@make-it-all.co.uk

**Browser:** Chrome/Firefox/Safari version

**Screenshot:** (if applicable)

**Severity:** Critical / High / Medium / Low
```

---

## Test Coverage Summary

This test data provides:
- **10 users** across 4 different roles (Manager, Team Leader, Team Member, Technical Specialist)
- **8 projects** (6 active, 2 archived)
- **63 tasks** across different statuses (completed, in progress, to do)
- **Multiple task assignments** (solo and shared)
- **22 Knowledge Base posts** across 8 topics
- **40+ KB comments** showing active discussion
- **Post likes** demonstrating engagement
- **Personal tasks** for dashboard testing
- **Comprehensive role-based permissions** for testing access control

This data set supports thorough testing of all features including edge cases, multi-user scenarios, and role-based access control.