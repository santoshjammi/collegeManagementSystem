<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$userRole = getUserRole($user['user_id']);
$notifications = getNotifications($userRole, $user);

// Handle delete course
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_course') {
    if (!hasRole(['Admin', 'Administrative Staff'])) {
        $_SESSION['error'] = 'Unauthorized access';
        redirect('/courses.php');
    }

    $course_id = $_POST['course_id'] ?? 0;

    // Check if course is being used by students or subjects
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE course_id = ?");
    $stmt->execute([$course_id]);
    $studentCount = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE course_id = ?");
    $stmt->execute([$course_id]);
    $subjectCount = $stmt->fetchColumn();

    if ($studentCount > 0 || $subjectCount > 0) {
        $_SESSION['error'] = 'Cannot delete course. It is being used by ' . $studentCount . ' students and ' . $subjectCount . ' subjects.';
        redirect('/courses.php');
    }

    $stmt = $pdo->prepare("DELETE FROM courses WHERE course_id = ?");
    $stmt->execute([$course_id]);
    $_SESSION['success'] = 'Course deleted successfully';
    redirect('/courses.php');
}

// Handle add/edit course
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_course') {
    if (!hasRole(['Admin', 'Administrative Staff'])) {
        $_SESSION['error'] = 'Unauthorized access';
        redirect('/courses.php');
    }

    $data = [
        'course_code' => trim($_POST['course_code'] ?? ''),
        'course_name' => trim($_POST['course_name'] ?? ''),
        'duration_years' => (int)($_POST['duration_years'] ?? 4),
    ];

    // Validation
    if (empty($data['course_code'])) {
        $_SESSION['error'] = 'Course code is required';
        redirect('/courses.php');
    }

    if (empty($data['course_name'])) {
        $_SESSION['error'] = 'Course name is required';
        redirect('/courses.php');
    }

    if ($data['duration_years'] < 1 || $data['duration_years'] > 8) {
        $_SESSION['error'] = 'Duration must be between 1 and 8 years';
        redirect('/courses.php');
    }

    // Check for duplicate course code
    $stmt = $pdo->prepare("SELECT course_id FROM courses WHERE course_code = ? AND course_id != ?");
    $stmt->execute([$data['course_code'], $_POST['course_id'] ?? 0]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = 'Course code already exists';
        redirect('/courses.php');
    }

    if (isset($_POST['course_id']) && $_POST['course_id']) {
        // Update
        $stmt = $pdo->prepare("
            UPDATE courses SET
                course_code = ?,
                course_name = ?,
                duration_years = ?
            WHERE course_id = ?
        ");
        $stmt->execute([
            $data['course_code'],
            $data['course_name'],
            $data['duration_years'],
            $_POST['course_id']
        ]);
        $_SESSION['success'] = 'Course updated successfully';
    } else {
        // Insert
        $stmt = $pdo->prepare("
            INSERT INTO courses (course_code, course_name, duration_years)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $data['course_code'],
            $data['course_name'],
            $data['duration_years']
        ]);
        $_SESSION['success'] = 'Course added successfully';
    }
    redirect('/courses.php');
}

// Get all courses with statistics
$stmt = $pdo->query("
    SELECT
        c.*,
        COUNT(DISTINCT s.student_pk_id) as student_count,
        COUNT(DISTINCT sub.subject_id) as subject_count,
        COUNT(DISTINCT b.batch_id) as batch_count
    FROM courses c
    LEFT JOIN students s ON c.course_id = s.course_id
    LEFT JOIN subjects sub ON c.course_id = sub.course_id
    LEFT JOIN batches b ON c.course_id = b.course_id
    GROUP BY c.course_id
    ORDER BY c.course_name
");
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get course for editing if requested
$editCourse = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE course_id = ?");
    $stmt->execute([$_GET['edit']]);
    $editCourse = $stmt->fetch(PDO::FETCH_ASSOC);
}

$pageTitle = 'Course Management';
include 'includes/navbar.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-mortarboard-fill text-primary"></i> Course Management</h2>
                <?php if (hasRole(['Admin', 'Administrative Staff'])): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#courseModal">
                    <i class="bi bi-plus-circle"></i> Add New Course
                </button>
                <?php endif; ?>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Courses (<?php echo count($courses); ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($courses)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-mortarboard text-muted" style="font-size: 3rem;"></i>
                        <h4 class="text-muted mt-3">No Courses Found</h4>
                        <p class="text-muted">Start by adding your first course to the system.</p>
                        <?php if (hasRole(['Admin', 'Administrative Staff'])): ?>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#courseModal">
                            <i class="bi bi-plus-circle"></i> Add First Course
                        </button>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Course Code</th>
                                    <th>Course Name</th>
                                    <th>Duration</th>
                                    <th>Students</th>
                                    <th>Subjects</th>
                                    <th>Batches</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($course['course_code']); ?></span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($course['course_name']); ?></strong>
                                    </td>
                                    <td><?php echo $course['duration_years']; ?> years</td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $course['student_count']; ?> students</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success"><?php echo $course['subject_count']; ?> subjects</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning"><?php echo $course['batch_count']; ?> batches</span>
                                    </td>
                                    <td>
                                        <?php if (hasRole(['Admin', 'Administrative Staff'])): ?>
                                        <div class="btn-group" role="group">
                                            <a href="?edit=<?php echo $course['course_id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger" title="Delete"
                                                    onclick="confirmDelete(<?php echo $course['course_id']; ?>, '<?php echo htmlspecialchars($course['course_name']); ?>')"
                                                    <?php echo ($course['student_count'] > 0 || $course['subject_count'] > 0) ? 'disabled' : ''; ?>>
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Course Modal -->
<?php if (hasRole(['Admin', 'Administrative Staff'])): ?>
<div class="modal fade" id="courseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-mortarboard-fill text-primary"></i>
                    <?php echo $editCourse ? 'Edit Course' : 'Add New Course'; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="save_course">
                    <?php if ($editCourse): ?>
                    <input type="hidden" name="course_id" value="<?php echo $editCourse['course_id']; ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="course_code" class="form-label">Course Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="course_code" name="course_code"
                               value="<?php echo htmlspecialchars($editCourse['course_code'] ?? ''); ?>" required
                               placeholder="e.g., CS101, BTECH-CS">
                        <div class="form-text">Unique identifier for the course (e.g., CS101, BTECH-CS)</div>
                    </div>

                    <div class="mb-3">
                        <label for="course_name" class="form-label">Course Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="course_name" name="course_name"
                               value="<?php echo htmlspecialchars($editCourse['course_name'] ?? ''); ?>" required
                               placeholder="e.g., Bachelor of Technology in Computer Science">
                        <div class="form-text">Full name of the course/program</div>
                    </div>

                    <div class="mb-3">
                        <label for="duration_years" class="form-label">Duration (Years) <span class="text-danger">*</span></label>
                        <select class="form-select" id="duration_years" name="duration_years" required>
                            <?php for ($i = 1; $i <= 8; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo (($editCourse['duration_years'] ?? 4) == $i) ? 'selected' : ''; ?>>
                                <?php echo $i; ?> year<?php echo $i > 1 ? 's' : ''; ?>
                            </option>
                            <?php endfor; ?>
                        </select>
                        <div class="form-text">Typical duration of the course</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> <?php echo $editCourse ? 'Update Course' : 'Add Course'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">
                    <i class="bi bi-exclamation-triangle"></i> Confirm Delete
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the course "<strong id="deleteCourseName"></strong>"?</p>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>Warning:</strong> This action cannot be undone. Make sure the course is not being used by any students or subjects.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" id="deleteForm">
                    <input type="hidden" name="action" value="delete_course">
                    <input type="hidden" name="course_id" id="deleteCourseId">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Delete Course
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Show modal if editing
<?php if ($editCourse): ?>
document.addEventListener('DOMContentLoaded', function() {
    var modal = new bootstrap.Modal(document.getElementById('courseModal'));
    modal.show();
});
<?php endif; ?>

function confirmDelete(courseId, courseName) {
    document.getElementById('deleteCourseId').value = courseId;
    document.getElementById('deleteCourseName').textContent = courseName;
    var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
</script>

<?php include 'includes/footer.php'; ?>