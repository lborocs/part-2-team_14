<?php
/* =======================
   SPECIALTY → CSS CLASS MAP
   ======================= */
$specialtyClassMap = [
    'Project Management' => 'spec-project-management',
    'Strategy'           => 'spec-strategy',
    'Leadership'         => 'spec-leadership',
    'Backend'            => 'spec-backend',
    'Python'             => 'spec-python',
    'SQL'                => 'spec-sql',
    'API Design'         => 'spec-api-design',
    'Frontend'           => 'spec-frontend',
    'React'              => 'spec-react',
    'CSS'                => 'spec-css',
    'JavaScript'         => 'spec-javascript',
    'Node.js'            => 'spec-node-js',
    'MongoDB'            => 'spec-mongodb',
    'DevOps'             => 'spec-devops',
    'AWS'                => 'spec-aws',
    'Docker'             => 'spec-docker',
    'CI/CD'              => 'spec-ci-cd',
    'UI Design'          => 'spec-ui-design',
    'Figma'              => 'spec-figma',
    'Prototyping'        => 'spec-prototyping',
];
?>

<div id="employees-count" class="employees-count">
    <?php if ($totalEmployees === 0): ?>
        No Results Found
    <?php else: ?>
        Showing <strong><?= $start ?>-<?= $end ?></strong>
        of <?= $totalEmployees ?> Employee Results
    <?php endif; ?>
</div>

<div id="employee-grid" class="employee-grid">
<?php foreach ($employees as $employee): ?>

    <?php
        $roleClass = match ($employee['role']) {
            'manager' => 'role-manager',
            'team_leader' => 'role-team-leader',
            'team_member' => 'role-team-member',
            'technical_specialist' => 'role-technical-specialist',
            default => 'role-team-member',
        };

        $specialties = [];
        if (!empty($employee['specialties'])) {
            $specialties = json_decode($employee['specialties'], true)
                ?? explode(',', $employee['specialties']);
        }
    ?>

    <article
        class="employee-card <?= $roleClass ?>"
        data-profile-url="employee-profile.php?id=<?= urlencode($employee['user_id']) ?>"
    >
        <div class="employee-card-top">
            <div class="employee-avatar">
                <img src="<?= htmlspecialchars($employee['profile_picture']) ?>" alt="">
            </div>
        </div>

        <div class="employee-card-body">
            <h3 class="employee-name">
                <?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?>
            </h3>

            <div class="employee-specialties">
                <div class="block-title">Specialties</div>

                <div class="specialties-container collapsed">
                    <?php foreach ($specialties as $skill): ?>
                        <?php
                            $skillName  = trim($skill);
                            $skillClass = $specialtyClassMap[$skillName] ?? 'spec-default';
                        ?>
                        <span class="specialty-pill <?= $skillClass ?>">
                            <?= htmlspecialchars($skillName) ?>
                        </span>
                    <?php endforeach; ?>
                </div>


                <button type="button" class="see-more-btn" hidden>...</button>
            </div>


            <div class="employee-card-footer">
                <i data-feather="mail"></i>
                <span class="employee-email">
                    <?= htmlspecialchars($employee['email']) ?>
                </span>
            </div>
        </div>
    </article>

<?php endforeach; ?>
</div>

<div id="employees-pagination" class="employees-pagination">

    <?php if ($page > 1): ?>
        <a class="pagination-btn" href="?page=<?= $page - 1 ?><?= $queryString ?>">Prev</a>
    <?php else: ?>
        <button class="pagination-btn" disabled>Prev</button>
    <?php endif; ?>

    <div class="pagination-pages">
    <?php
    if ($page > 1) {
        echo '<a class="pagination-page" href="?page=1' . $queryString . '">1</a>';
    }

    if ($page > $range + 2) {
        echo '<span class="pagination-ellipsis">…</span>';
    }

    for ($i = max(1, $page - $range); $i <= min($totalPages, $page + $range); $i++) {
        if ($i == $page) {
            echo '<span class="pagination-page active">' . $i . '</span>';
        } else {
            echo '<a class="pagination-page" href="?page=' . $i . $queryString . '">' . $i . '</a>';
        }
    }

    if ($page < $totalPages - ($range + 1)) {
        echo '<span class="pagination-ellipsis">…</span>';
    }

    if ($page < $totalPages) {
        echo '<a class="pagination-page" href="?page=' . $totalPages . $queryString . '">' . $totalPages . '</a>';
    }
    ?>
    </div>

    <?php if ($page < $totalPages): ?>
        <a class="pagination-btn" href="?page=<?= $page + 1 ?><?= $queryString ?>">Next</a>
    <?php else: ?>
        <button class="pagination-btn" disabled>Next</button>
    <?php endif; ?>

</div>
