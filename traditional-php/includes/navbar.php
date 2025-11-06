<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
            <i class="bi bi-mortarboard-fill me-2"></i>
            College Management System
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                        <i class="bi bi-house-door"></i> Dashboard
                    </a>
                </li>

                <?php if (hasRole(['Administrative Staff', 'Admin'])): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-gear"></i> Administration
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="admissions.php"><i class="bi bi-file-earmark-text"></i> Admissions</a></li>
                        <li><a class="dropdown-item" href="fees.php"><i class="bi bi-currency-dollar"></i> Fees</a></li>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if (canAccessModule('courses') || canAccessModule('subjects') || canAccessModule('tests') || canAccessModule('grades') || canAccessModule('students') || canAccessModule('faculty')): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="academicDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-mortarboard"></i> Academic
                    </a>
                    <ul class="dropdown-menu">
                        <?php if (canAccessModule('students')): ?>
                        <li><a class="dropdown-item" href="students.php"><i class="bi bi-people"></i> Students</a></li>
                        <?php endif; ?>
                        <?php if (canAccessModule('faculty')): ?>
                        <li><a class="dropdown-item" href="faculty.php"><i class="bi bi-person-badge"></i> Faculty</a></li>
                        <?php endif; ?>
                        <?php if (canAccessModule('courses')): ?>
                        <li><a class="dropdown-item" href="courses.php"><i class="bi bi-book"></i> Courses</a></li>
                        <?php endif; ?>
                        <?php if (canAccessModule('subjects')): ?>
                        <li><a class="dropdown-item" href="subjects.php"><i class="bi bi-journal-text"></i> Subjects</a></li>
                        <?php endif; ?>
                        <?php if (canAccessModule('tests')): ?>
                        <li><a class="dropdown-item" href="tests.php"><i class="bi bi-clipboard-check"></i> Tests</a></li>
                        <?php endif; ?>
                        <?php if (canAccessModule('grades')): ?>
                        <li><a class="dropdown-item" href="grades.php"><i class="bi bi-graph-up"></i> Grades</a></li>
                        <?php endif; ?>
                        <?php if (canAccessModule('papers')): ?>
                        <li><a class="dropdown-item" href="faculty_papers.php"><i class="bi bi-file-earmark-text"></i> Papers & Publications</a></li>
                        <?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if (canAccessModule('library')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'library.php' ? 'active' : ''; ?>" href="library.php">
                        <i class="bi bi-book"></i> Library
                    </a>
                </li>
                <?php endif; ?>

                <?php if (canAccessModule('placements')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'placements.php' ? 'active' : ''; ?>" href="placements.php">
                        <i class="bi bi-briefcase"></i> Placements
                    </a>
                </li>
                <?php endif; ?>

                <?php if (hasRole(['Student'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'student-grades.php' ? 'active' : ''; ?>" href="student-grades.php">
                        <i class="bi bi-award"></i> My Grades
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'fees.php' ? 'active' : ''; ?>" href="fees.php">
                        <i class="bi bi-currency-dollar"></i> Fees
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'library.php' ? 'active' : ''; ?>" href="library.php">
                        <i class="bi bi-book"></i> Library
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav">
                <!-- Notifications -->
                <?php if (!empty($notifications)): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link position-relative" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-bell"></i>
                        <span class="notification-badge"><?php echo count($notifications); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" style="min-width: 300px;">
                        <li><h6 class="dropdown-header">Notifications</h6></li>
                        <?php foreach ($notifications as $notification): ?>
                        <li>
                            <a class="dropdown-item d-flex align-items-start" href="<?php echo $notification['url']; ?>">
                                <div class="me-2 mt-1">
                                    <i class="bi bi-<?php
                                        echo $notification['type'] === 'danger' ? 'exclamation-triangle' :
                                             ($notification['type'] === 'warning' ? 'exclamation-circle' :
                                             ($notification['type'] === 'info' ? 'info-circle' : 'check-circle'));
                                    ?> text-<?php echo $notification['type']; ?>"></i>
                                </div>
                                <div>
                                    <small class="text-muted"><?php echo $notification['message']; ?></small>
                                </div>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <?php endif; ?>

                <!-- User Menu -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?php echo escape($user['full_name'] ?? $user['username']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header"><?php echo $userRole; ?></h6></li>
                        <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person"></i> Profile</a></li>
                        <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear"></i> Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>