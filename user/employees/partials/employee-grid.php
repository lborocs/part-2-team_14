<?php
session_start(); // Ensure session is available

// Re-define specialty colors (must match employee-directory.php)
$specialtyColors = [
    'Project Management' => '#1565C0',
    'Strategy'           => '#0277BD',
    'Leadership'         => '#2E7D32',
    'Backend'            => '#512DA8',
    'Python'             => '#F9A825',
    'SQL'                => '#558B2F',
    'API Design'         => '#00695C',
    'Frontend'           => '#AD1457',
    'React'              => '#0288D1',
    'CSS'                => '#3949AB',
    'JavaScript'         => '#F9A825',
    'Node.js'            => '#2E7D32',
    'MongoDB'            => '#00796B',
    'DevOps'             => '#6A1B9A',
    'AWS'                => '#EF6C00',
    'Docker'             => '#0277BD',
    'CI/CD'              => '#455A64',
    'UI Design'          => '#C2185B',
    'Figma'              => '#7B1FA2',
    'Prototyping'        => '#303F9F',
];

// Re-define color function (must match employee-directory.php)
function getEmployeeColor($userId, $userSpecialties, $specialtyColors, &$colorMap) {
    // If color already assigned in this session, return it
    if (isset($colorMap[$userId])) {
        return $colorMap[$userId];
    }
    
    // Parse specialties
    $specialties = [];
    if (!empty($userSpecialties)) {
        $specialties = json_decode($userSpecialties, true) ?? explode(',', $userSpecialties);
        $specialties = array_map('trim', $specialties);
    }
    
    // Get matching colors from user's specialties
    $availableColors = [];
    foreach ($specialties as $spec) {
        if (isset($specialtyColors[$spec])) {
            $availableColors[] = $specialtyColors[$spec];
        }
    }
    
    // If no matching specialty colors, use a random specialty color from the palette
    if (empty($availableColors)) {
        $availableColors = array_values($specialtyColors);
    }
    
    // Randomly pick one color from available options
    $selectedColor = $availableColors[array_rand($availableColors)];
    
    // Store in session
    $colorMap[$userId] = $selectedColor;
    
    return $selectedColor;
}
?>

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
        $employeeColor = getEmployeeColor(
            $employee['user_id'], 
            $employee['specialties'], 
            $specialtyColors, 
            $_SESSION['employee_colors']
        );

        $specialties = [];
        if (!empty($employee['specialties'])) {
            $specialties = json_decode($employee['specialties'], true)
                ?? explode(',', $employee['specialties']);
        }
    ?>

    <article
        class="employee-card"
        data-profile-url="employee-profile.php?id=<?= urlencode($employee['user_id']) ?>"
        data-employee-id="<?= $employee['user_id'] ?>"
    >
        <!-- Selection checkbox (hidden by default) -->
        <div class="employee-checkbox" data-employee-id="<?= $employee['user_id'] ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>
        
        <div class="employee-card-top" style="background-color: <?= htmlspecialchars($employeeColor) ?>;">
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
