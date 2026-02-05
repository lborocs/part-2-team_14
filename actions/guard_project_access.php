<?php
// actions/guard_project_access.php
// Usage: require_once __DIR__ . '/../actions/guard_project_access.php';
// Then call: $access = guardProjectAccess($db, $projectId, $userId);

function guardProjectAccess(PDO $db, int $projectId, int $userId): array
{
    if ($projectId <= 0) {
        http_response_code(400);
        exit("Missing/invalid project_id.");
    }

    $stmt = $db->prepare("
        SELECT 
            p.project_id,
            p.project_name,
            p.description,
            p.status,
            p.deadline,
            p.team_leader_id,
            p.created_by,
            CASE WHEN p.team_leader_id = :uid THEN 1 ELSE 0 END AS is_team_leader,
            CASE WHEN pm.user_id IS NOT NULL THEN 1 ELSE 0 END AS is_member
        FROM projects p
        LEFT JOIN project_members pm
            ON pm.project_id = p.project_id
           AND pm.user_id = :uid
           AND pm.left_at IS NULL
        WHERE p.project_id = :pid
          AND (
              p.created_by = :uid
              OR p.team_leader_id = :uid
              OR pm.user_id IS NOT NULL
          )
        LIMIT 1
    ");

    $stmt->execute([':pid' => $projectId, ':uid' => $userId]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$project) {
        http_response_code(403);
        exit("You don't have access to this project.");
    }

    $roleLower = strtolower((string)($_SESSION['role'] ?? ''));
    $isManager = ($roleLower === 'manager');
    $isTeamLeaderOfThisProject = ((int)$project['is_team_leader'] === 1);
    $canManageProject = $isManager || $isTeamLeaderOfThisProject;
    $canCloseProject = $isManager;

    return [
        'project' => $project,
        'isManager' => $isManager,
        'isTeamLeaderOfThisProject' => $isTeamLeaderOfThisProject,
        'canManageProject' => $canManageProject,
        'canCloseProject' => $canCloseProject,
    ];
}