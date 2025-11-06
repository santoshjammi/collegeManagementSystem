-- GRADING AND TEST MANAGEMENT SYSTEM
-- Extension to the College Management System for academic grading

-- --- SUBJECTS/COURSES MANAGEMENT ---

-- 16. SUBJECTS Table: Academic subjects that can have tests and grades
CREATE TABLE subjects (
    subject_id INT PRIMARY KEY AUTO_INCREMENT,
    subject_code VARCHAR(20) NOT NULL UNIQUE COMMENT 'e.g., CS101, MATH201',
    subject_name VARCHAR(150) NOT NULL COMMENT 'e.g., Introduction to Computer Science',
    course_id INT NOT NULL COMMENT 'Which degree program this subject belongs to',
    semester INT NOT NULL COMMENT 'Which semester (1-8 typically)',
    credits INT DEFAULT 3 COMMENT 'Credit hours for this subject',
    description TEXT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE RESTRICT,
    UNIQUE KEY unique_subject_course_semester (subject_code, course_id, semester)
) ENGINE=InnoDB;

-- 17. SUBJECT_FACULTY Table: Many-to-many relationship between subjects and faculty
CREATE TABLE subject_faculty (
    assignment_id INT PRIMARY KEY AUTO_INCREMENT,
    subject_id INT NOT NULL,
    faculty_id INT NOT NULL,
    batch_id INT NOT NULL COMMENT 'Which batch this faculty teaches',
    academic_year YEAR NOT NULL COMMENT 'e.g., 2024',
    is_primary_faculty TINYINT(1) DEFAULT 0 COMMENT 'Main teacher vs assistant',
    assigned_date DATE DEFAULT (CURRENT_DATE),
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
    FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id) ON DELETE CASCADE,
    FOREIGN KEY (batch_id) REFERENCES batches(batch_id) ON DELETE CASCADE,
    UNIQUE KEY unique_assignment (subject_id, faculty_id, batch_id, academic_year)
) ENGINE=InnoDB;

-- --- TEST MANAGEMENT ---

-- 18. TEST_TYPES Table: Different types of evaluations
CREATE TABLE test_types (
    type_id INT PRIMARY KEY AUTO_INCREMENT,
    type_name VARCHAR(50) NOT NULL UNIQUE COMMENT 'e.g., Quiz, Mid-term, Final Exam, Assignment',
    weight_percentage DECIMAL(5,2) DEFAULT 100.00 COMMENT 'Default weight for grade calculation',
    description TEXT NULL
) ENGINE=InnoDB;

-- Insert default test types
INSERT INTO test_types (type_name, weight_percentage, description) VALUES
('Quiz', 10.00, 'Short assessment covering recent topics'),
('Assignment', 15.00, 'Take-home assignments and projects'),
('Mid-term Exam', 35.00, 'Mid-semester examination'),
('Final Exam', 40.00, 'Comprehensive final examination');

-- 19. TESTS Table: Individual test instances
CREATE TABLE tests (
    test_id INT PRIMARY KEY AUTO_INCREMENT,
    test_title VARCHAR(200) NOT NULL,
    subject_id INT NOT NULL,
    batch_id INT NOT NULL,
    test_type_id INT NOT NULL,
    faculty_id INT NOT NULL COMMENT 'Faculty who created/manages this test',
    test_date DATE NOT NULL,
    duration_minutes INT DEFAULT 60 COMMENT 'Test duration in minutes',
    total_marks DECIMAL(6,2) NOT NULL DEFAULT 100.00,
    passing_marks DECIMAL(6,2) NOT NULL DEFAULT 40.00,
    instructions TEXT NULL COMMENT 'Special instructions for students',
    is_published TINYINT(1) DEFAULT 0 COMMENT 'Whether results are visible to students',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
    FOREIGN KEY (batch_id) REFERENCES batches(batch_id) ON DELETE CASCADE,
    FOREIGN KEY (test_type_id) REFERENCES test_types(type_id) ON DELETE RESTRICT,
    FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- --- GRADING SYSTEM ---

-- 20. GRADE_SCALES Table: Define grading scales (can have multiple systems)
CREATE TABLE grade_scales (
    scale_id INT PRIMARY KEY AUTO_INCREMENT,
    scale_name VARCHAR(50) NOT NULL UNIQUE COMMENT 'e.g., Standard 10-Point, Percentage, Letter Grade',
    is_default TINYINT(1) DEFAULT 0,
    description TEXT NULL
) ENGINE=InnoDB;

-- Insert default grade scale
INSERT INTO grade_scales (scale_name, is_default, description) VALUES
('Percentage Scale', 1, 'Standard 0-100 percentage grading'),
('10-Point GPA', 0, 'Grade Point Average on 10-point scale'),
('Letter Grade', 0, 'A, B, C, D, F letter grading system');

-- 21. GRADE_RANGES Table: Define grade boundaries for each scale
CREATE TABLE grade_ranges (
    range_id INT PRIMARY KEY AUTO_INCREMENT,
    scale_id INT NOT NULL,
    grade_label VARCHAR(10) NOT NULL COMMENT 'e.g., A+, B, C, or numeric like 9.5',
    min_percentage DECIMAL(5,2) NOT NULL COMMENT 'Minimum percentage for this grade',
    max_percentage DECIMAL(5,2) NOT NULL COMMENT 'Maximum percentage for this grade',
    grade_points DECIMAL(4,2) NULL COMMENT 'GPA equivalent if applicable',
    FOREIGN KEY (scale_id) REFERENCES grade_scales(scale_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Insert default percentage grade ranges
INSERT INTO grade_ranges (scale_id, grade_label, min_percentage, max_percentage, grade_points) VALUES
(1, 'A+', 95.00, 100.00, 10.00),
(1, 'A', 90.00, 94.99, 9.00),
(1, 'B+', 85.00, 89.99, 8.00),
(1, 'B', 80.00, 84.99, 7.00),
(1, 'C+', 75.00, 79.99, 6.00),
(1, 'C', 70.00, 74.99, 5.00),
(1, 'D+', 65.00, 69.99, 4.00),
(1, 'D', 60.00, 64.99, 3.00),
(1, 'E', 50.00, 59.99, 2.00),
(1, 'F', 0.00, 49.99, 0.00);

-- 22. STUDENT_TEST_GRADES Table: Individual test results for students
CREATE TABLE student_test_grades (
    grade_id INT PRIMARY KEY AUTO_INCREMENT,
    test_id INT NOT NULL,
    student_id INT NOT NULL,
    marks_obtained DECIMAL(6,2) NOT NULL COMMENT 'Actual marks scored by student',
    percentage DECIMAL(5,2) GENERATED ALWAYS AS ((marks_obtained / (SELECT total_marks FROM tests WHERE tests.test_id = student_test_grades.test_id)) * 100) STORED,
    grade_label VARCHAR(10) NULL COMMENT 'Calculated grade (A, B, C, etc.)',
    grade_points DECIMAL(4,2) NULL COMMENT 'GPA points earned',
    remarks TEXT NULL COMMENT 'Faculty comments or notes',
    is_absent TINYINT(1) DEFAULT 0 COMMENT 'Student was absent for test',
    graded_by_faculty_id INT NOT NULL,
    graded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (test_id) REFERENCES tests(test_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(student_pk_id) ON DELETE CASCADE,
    FOREIGN KEY (graded_by_faculty_id) REFERENCES faculty(faculty_id) ON DELETE RESTRICT,
    UNIQUE KEY unique_student_test (test_id, student_id)
) ENGINE=InnoDB;

-- --- ACADEMIC PERFORMANCE TRACKING ---

-- 23. STUDENT_SUBJECT_GRADES Table: Consolidated subject-wise performance
CREATE TABLE student_subject_grades (
    record_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    academic_year YEAR NOT NULL,
    semester INT NOT NULL,
    total_tests INT DEFAULT 0 COMMENT 'Number of tests taken',
    total_marks DECIMAL(8,2) DEFAULT 0.00 COMMENT 'Sum of all test marks',
    total_possible_marks DECIMAL(8,2) DEFAULT 0.00 COMMENT 'Sum of all test total marks',
    overall_percentage DECIMAL(5,2) GENERATED ALWAYS AS (
        CASE WHEN total_possible_marks > 0 
        THEN (total_marks / total_possible_marks) * 100 
        ELSE 0 END
    ) STORED,
    final_grade VARCHAR(10) NULL COMMENT 'Final calculated grade for subject',
    final_grade_points DECIMAL(4,2) NULL COMMENT 'Final GPA points',
    is_completed TINYINT(1) DEFAULT 0 COMMENT 'Subject completion status',
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_pk_id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_subject_year (student_id, subject_id, academic_year, semester)
) ENGINE=InnoDB;

-- --- INDEXES for PERFORMANCE ---
CREATE INDEX idx_tests_subject_batch ON tests(subject_id, batch_id);
CREATE INDEX idx_tests_faculty ON tests(faculty_id);
CREATE INDEX idx_tests_date ON tests(test_date);
CREATE INDEX idx_grades_student ON student_test_grades(student_id);
CREATE INDEX idx_grades_test ON student_test_grades(test_id);
CREATE INDEX idx_subject_grades_student ON student_subject_grades(student_id);
CREATE INDEX idx_subject_faculty_assignment ON subject_faculty(subject_id, faculty_id, academic_year);

-- --- VIEWS FOR COMMON QUERIES ---

-- View: Student Grade Summary
CREATE VIEW view_student_grade_summary AS
SELECT 
    s.student_id,
    s.full_name AS student_name,
    sub.subject_code,
    sub.subject_name,
    ssg.academic_year,
    ssg.semester,
    ssg.total_tests,
    ssg.overall_percentage,
    ssg.final_grade,
    ssg.final_grade_points,
    ssg.is_completed
FROM student_subject_grades ssg
JOIN students s ON ssg.student_id = s.student_pk_id
JOIN subjects sub ON ssg.subject_id = sub.subject_id;

-- View: Faculty Teaching Load
CREATE VIEW view_faculty_teaching_load AS
SELECT 
    f.faculty_id,
    f.full_name AS faculty_name,
    sub.subject_code,
    sub.subject_name,
    b.batch_name,
    sf.academic_year,
    sf.is_primary_faculty,
    COUNT(t.test_id) AS total_tests_created
FROM subject_faculty sf
JOIN faculty f ON sf.faculty_id = f.faculty_id
JOIN subjects sub ON sf.subject_id = sub.subject_id
JOIN batches b ON sf.batch_id = b.batch_id
LEFT JOIN tests t ON sub.subject_id = t.subject_id AND sf.faculty_id = t.faculty_id
GROUP BY f.faculty_id, sub.subject_id, b.batch_id, sf.academic_year;

-- View: Test Performance Summary
CREATE VIEW view_test_performance AS
SELECT 
    t.test_id,
    t.test_title,
    sub.subject_name,
    tt.type_name AS test_type,
    t.test_date,
    t.total_marks,
    COUNT(stg.student_id) AS students_appeared,
    AVG(stg.marks_obtained) AS average_marks,
    AVG(stg.percentage) AS average_percentage,
    MAX(stg.marks_obtained) AS highest_marks,
    MIN(stg.marks_obtained) AS lowest_marks,
    COUNT(CASE WHEN stg.marks_obtained >= t.passing_marks THEN 1 END) AS students_passed,
    COUNT(CASE WHEN stg.is_absent = 1 THEN 1 END) AS students_absent
FROM tests t
JOIN subjects sub ON t.subject_id = sub.subject_id
JOIN test_types tt ON t.test_type_id = tt.type_id
LEFT JOIN student_test_grades stg ON t.test_id = stg.test_id AND stg.is_absent = 0
GROUP BY t.test_id;