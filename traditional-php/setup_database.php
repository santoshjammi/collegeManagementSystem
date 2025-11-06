<?php
// Database setup script
echo "Setting up College Management System database...\n\n";

try {
    // Connect to MySQL (without specifying database)
    $pdo = new PDO("mysql:host=localhost;charset=utf8mb4", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS college_management");
    echo "✓ Database 'college_management' created or already exists\n";

    // Select the database
    $pdo->exec("USE college_management");

    // Disable foreign key checks to allow dropping tables with dependencies
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    echo "✓ Disabled foreign key checks\n";

    // Get all existing tables and drop them
    $stmt = $pdo->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($existingTables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS `$table`");
    }
    echo "✓ Dropped all existing tables\n";

    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "✓ Re-enabled foreign key checks\n";

    // Create tables manually
    $tables = [
        "CREATE TABLE roles (
            role_id INT PRIMARY KEY AUTO_INCREMENT,
            role_name VARCHAR(50) NOT NULL UNIQUE
        ) ENGINE=InnoDB",

        "CREATE TABLE users (
            user_id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(100) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role_id INT NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (role_id) REFERENCES roles(role_id)
        ) ENGINE=InnoDB",

        "CREATE TABLE courses (
            course_id INT PRIMARY KEY AUTO_INCREMENT,
            course_code VARCHAR(10) NOT NULL UNIQUE,
            course_name VARCHAR(150) NOT NULL,
            duration_years INT
        ) ENGINE=InnoDB",

        "CREATE TABLE batches (
            batch_id INT PRIMARY KEY AUTO_INCREMENT,
            course_id INT NOT NULL,
            batch_year YEAR NOT NULL,
            batch_name VARCHAR(50) NOT NULL,
            FOREIGN KEY (course_id) REFERENCES courses(course_id)
        ) ENGINE=InnoDB",

        "CREATE TABLE students (
            student_pk_id INT PRIMARY KEY AUTO_INCREMENT,
            student_id VARCHAR(20) NOT NULL UNIQUE,
            user_id INT NULL UNIQUE,
            full_name VARCHAR(200) NOT NULL,
            email VARCHAR(100) UNIQUE,
            course_id INT NOT NULL,
            batch_id INT NOT NULL,
            academic_status ENUM('Active', 'On Leave', 'Suspended', 'Graduated') DEFAULT 'Active',
            profile_picture VARCHAR(255) NULL,
            gender ENUM('Male', 'Female', 'Other') DEFAULT 'Other',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id),
            FOREIGN KEY (course_id) REFERENCES courses(course_id),
            FOREIGN KEY (batch_id) REFERENCES batches(batch_id)
        ) ENGINE=InnoDB",

        "CREATE TABLE faculty (
            faculty_id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NULL UNIQUE,
            full_name VARCHAR(200) NOT NULL,
            email VARCHAR(100) UNIQUE,
            contact_number VARCHAR(20),
            department VARCHAR(100),
            employment_role VARCHAR(100),
            highest_degree VARCHAR(100),
            primary_subject VARCHAR(100),
            is_active TINYINT(1) DEFAULT 1,
            profile_picture VARCHAR(255) NULL,
            gender ENUM('Male', 'Female', 'Other') DEFAULT 'Other',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id)
        ) ENGINE=InnoDB",

        "CREATE TABLE faculty_papers (
            paper_id INT PRIMARY KEY AUTO_INCREMENT,
            faculty_id INT NOT NULL,
            title VARCHAR(500) NOT NULL,
            authors TEXT NOT NULL COMMENT 'Comma-separated list of authors',
            journal_name VARCHAR(300) NULL COMMENT 'Journal, conference, or publication venue',
            publication_date DATE NULL,
            doi VARCHAR(100) NULL COMMENT 'Digital Object Identifier',
            abstract TEXT NULL,
            keywords TEXT NULL COMMENT 'Comma-separated keywords',
            paper_url VARCHAR(500) NULL COMMENT 'Link to the paper if available online',
            citation_count INT DEFAULT 0 COMMENT 'Number of citations',
            publication_type ENUM('Journal Article', 'Conference Paper', 'Book Chapter', 'Book', 'Thesis', 'Working Paper', 'Other') DEFAULT 'Journal Article',
            status ENUM('Published', 'Accepted', 'Submitted', 'Draft') DEFAULT 'Published',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id) ON DELETE CASCADE
        ) ENGINE=InnoDB",

        "CREATE TABLE student_papers (
            paper_id INT PRIMARY KEY AUTO_INCREMENT,
            student_id INT NOT NULL,
            title VARCHAR(500) NOT NULL,
            authors TEXT NOT NULL COMMENT 'Comma-separated list of authors',
            journal_name VARCHAR(300) NULL COMMENT 'Journal, conference, or publication venue',
            publication_date DATE NULL,
            doi VARCHAR(100) NULL COMMENT 'Digital Object Identifier',
            abstract TEXT NULL,
            keywords TEXT NULL COMMENT 'Comma-separated keywords',
            paper_url VARCHAR(500) NULL COMMENT 'Link to the paper if available online',
            citation_count INT DEFAULT 0 COMMENT 'Number of citations',
            publication_type ENUM('Journal Article', 'Conference Paper', 'Book Chapter', 'Book', 'Thesis', 'Working Paper', 'Other') DEFAULT 'Journal Article',
            status ENUM('Published', 'Accepted', 'Submitted', 'Draft') DEFAULT 'Published',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(student_pk_id) ON DELETE CASCADE
        ) ENGINE=InnoDB",

        "CREATE TABLE subjects (
            subject_id INT PRIMARY KEY AUTO_INCREMENT,
            subject_code VARCHAR(20) NOT NULL UNIQUE,
            subject_name VARCHAR(200) NOT NULL,
            description TEXT NULL,
            credits INT DEFAULT 4,
            faculty_id INT NULL,
            course_id INT NULL,
            semester INT DEFAULT 1,
            subject_type ENUM('Theory', 'Practical', 'Lab') DEFAULT 'Theory',
            academic_year VARCHAR(20) DEFAULT '2024-25',
            status ENUM('Active', 'Inactive') DEFAULT 'Active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id),
            FOREIGN KEY (course_id) REFERENCES courses(course_id)
        ) ENGINE=InnoDB",

        "CREATE TABLE tests (
            test_id INT PRIMARY KEY AUTO_INCREMENT,
            test_name VARCHAR(200) NOT NULL,
            subject_id INT NOT NULL,
            faculty_id INT NOT NULL,
            max_marks INT DEFAULT 100,
            test_date DATE NULL,
            test_type ENUM('Quiz', 'Midterm', 'Final', 'Assignment', 'Project') DEFAULT 'Quiz',
            duration INT DEFAULT 60 COMMENT 'Duration in minutes',
            instructions TEXT NULL,
            status ENUM('Draft', 'Published', 'Completed', 'Cancelled') DEFAULT 'Published',
            is_published TINYINT(1) DEFAULT 0 COMMENT 'Whether test is published and visible to students',
            created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
            FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id)
        ) ENGINE=InnoDB",

        "CREATE TABLE student_test_grades (
            grade_id INT PRIMARY KEY AUTO_INCREMENT,
            student_id INT NOT NULL,
            test_id INT NOT NULL,
            faculty_id INT NOT NULL,
            subject_id INT NOT NULL,
            marks_obtained DECIMAL(5,2) NULL,
            max_marks INT NOT NULL,
            percentage DECIMAL(5,2) NULL,
            letter_grade VARCHAR(5) NULL,
            status ENUM('Pending', 'Completed', 'Absent') DEFAULT 'Pending',
            is_absent TINYINT(1) DEFAULT 0,
            graded_by INT NULL,
            remarks TEXT NULL,
            graded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_student_test (student_id, test_id),
            FOREIGN KEY (student_id) REFERENCES students(student_pk_id) ON DELETE CASCADE,
            FOREIGN KEY (test_id) REFERENCES tests(test_id) ON DELETE CASCADE,
            FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id),
            FOREIGN KEY (subject_id) REFERENCES subjects(subject_id),
            FOREIGN KEY (graded_by) REFERENCES faculty(faculty_id) ON DELETE SET NULL
        ) ENGINE=InnoDB",

        "CREATE TABLE admissions (
            admission_id INT PRIMARY KEY AUTO_INCREMENT,
            student_id INT NULL,
            full_name VARCHAR(200) NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(20) NULL,
            course_id INT NOT NULL,
            batch_id INT NULL,
            application_date DATE DEFAULT (CURRENT_DATE),
            status ENUM('Pending', 'Approved', 'Rejected', 'Admitted') DEFAULT 'Pending',
            documents_submitted TEXT NULL COMMENT 'Comma-separated list of submitted documents',
            remarks TEXT NULL,
            processed_by INT NULL,
            processed_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(student_pk_id),
            FOREIGN KEY (course_id) REFERENCES courses(course_id),
            FOREIGN KEY (batch_id) REFERENCES batches(batch_id),
            FOREIGN KEY (processed_by) REFERENCES users(user_id)
        ) ENGINE=InnoDB",

        "CREATE TABLE fee_payments (
            payment_id INT PRIMARY KEY AUTO_INCREMENT,
            student_id INT NOT NULL,
            fee_type VARCHAR(100) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_date DATE NULL,
            due_date DATE NULL,
            payment_status ENUM('Pending', 'Paid', 'Overdue', 'Cancelled') DEFAULT 'Pending',
            payment_method VARCHAR(50) NULL,
            transaction_id VARCHAR(100) NULL,
            remarks TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(student_pk_id) ON DELETE CASCADE
        ) ENGINE=InnoDB",

        "CREATE TABLE library_transactions (
            transaction_id INT PRIMARY KEY AUTO_INCREMENT,
            student_id INT NOT NULL,
            book_title VARCHAR(300) NOT NULL,
            book_author VARCHAR(200) NULL,
            isbn VARCHAR(20) NULL,
            issue_date DATE DEFAULT (CURRENT_DATE),
            due_date DATE NULL,
            return_date DATE NULL,
            status ENUM('Issued', 'Returned', 'Overdue', 'Lost') DEFAULT 'Issued',
            fine_amount DECIMAL(8,2) DEFAULT 0,
            remarks TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(student_pk_id) ON DELETE CASCADE
        ) ENGINE=InnoDB",

        "CREATE TABLE placement_offers (
            offer_id INT PRIMARY KEY AUTO_INCREMENT,
            student_id INT NOT NULL,
            company_name VARCHAR(200) NOT NULL,
            job_title VARCHAR(200) NOT NULL,
            salary_package DECIMAL(12,2) NULL,
            offer_date DATE NULL,
            joining_date DATE NULL,
            status ENUM('Offered', 'Accepted', 'Rejected', 'Joined') DEFAULT 'Offered',
            contact_person VARCHAR(100) NULL,
            contact_email VARCHAR(100) NULL,
            remarks TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(student_pk_id) ON DELETE CASCADE
        ) ENGINE=InnoDB",

        "CREATE TABLE announcements (
            announcement_id INT PRIMARY KEY AUTO_INCREMENT,
            title VARCHAR(200) NOT NULL,
            content TEXT NOT NULL,
            announcement_type ENUM('General', 'Academic', 'Event', 'Important', 'Emergency') DEFAULT 'General',
            target_audience ENUM('All', 'Students', 'Faculty', 'Admin') DEFAULT 'All',
            priority ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
            posted_by INT NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            expires_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (posted_by) REFERENCES users(user_id)
        ) ENGINE=InnoDB",

        "CREATE TABLE notifications (
            notification_id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            notification_type ENUM('Info', 'Warning', 'Success', 'Error', 'Reminder') DEFAULT 'Info',
            is_read TINYINT(1) DEFAULT 0,
            related_id INT NULL COMMENT 'ID of related record (announcement, grade, etc.)',
            related_type VARCHAR(50) NULL COMMENT 'Type of related record',
            expires_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB",

        "CREATE TABLE alerts (
            alert_id INT PRIMARY KEY AUTO_INCREMENT,
            title VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            alert_type ENUM('System', 'Academic', 'Financial', 'Placement', 'General') DEFAULT 'General',
            severity ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
            target_users JSON NULL COMMENT 'JSON array of user IDs, or null for all users',
            is_active TINYINT(1) DEFAULT 1,
            auto_expire TINYINT(1) DEFAULT 0,
            expires_at DATETIME NULL,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(user_id)
        ) ENGINE=InnoDB"
    ];

    foreach ($tables as $table) {
        $pdo->exec($table);
    }
    echo "✓ Database tables created successfully\n";

    // Insert default roles
    $pdo->exec("INSERT INTO roles (role_name) VALUES
        ('Admin'), ('Administrative Staff'), ('Faculty'), ('Student')
        ON DUPLICATE KEY UPDATE role_name = role_name");
    echo "✓ Default roles inserted\n";

    // Insert sample course and batch
    $pdo->exec("INSERT INTO courses (course_code, course_name, duration_years) VALUES
        ('CS', 'Computer Science', 4)");
    $pdo->exec("INSERT INTO batches (course_id, batch_year, batch_name) VALUES
        (1, 2024, 'CS 2024 Intake')");
    echo "✓ Sample course and batch created\n";

    // Insert sample faculty
    $pdo->exec("INSERT INTO faculty (full_name, email, department, is_active) VALUES
        ('Dr. Sarah Johnson', 'sarah.johnson@university.edu', 'Computer Science', 1),
        ('Dr. Michael Chen', 'michael.chen@university.edu', 'Information Technology', 1)");
    echo "✓ Sample faculty created\n";

    // Insert sample faculty papers
    $pdo->exec("INSERT INTO faculty_papers (faculty_id, title, authors, journal_name, publication_date, doi, abstract, keywords, paper_url, citation_count, publication_type, status) VALUES
        (1, 'Machine Learning in Education: A Comprehensive Survey', 'Dr. Sarah Johnson, John Smith, Mary Johnson', 'IEEE Transactions on Learning Technologies', '2024-03-15', '10.1109/TLT.2024.1234567', 'This paper provides a comprehensive survey of machine learning applications in educational settings, covering various techniques and their effectiveness.', 'machine learning, education, survey, technology', 'https://ieeexplore.ieee.org/document/1234567', 25, 'Journal Article', 'Published'),
        (1, 'Adaptive Learning Systems: Current Trends and Future Directions', 'Dr. Sarah Johnson, Robert Davis', 'International Journal of Artificial Intelligence in Education', '2023-11-08', '10.1007/s40593-023-00345-6', 'An analysis of adaptive learning systems, examining their implementation, effectiveness, and future directions in educational technology.', 'adaptive learning, educational technology, survey, artificial intelligence', 'https://link.springer.com/article/10.1007/s40593-023-00345-6', 45, 'Journal Article', 'Published'),
        (2, 'Cybersecurity Education in Higher Education Institutions', 'Dr. Michael Chen, Lisa Wang, David Kumar', 'Journal of Cybersecurity Education, Research and Practice', '2024-01-20', '10.1002/csec.1234', 'This study examines the current state of cybersecurity education in universities and proposes a comprehensive curriculum framework.', 'cybersecurity, education, curriculum, higher education', 'https://onlinelibrary.wiley.com/doi/10.1002/csec.1234', 18, 'Journal Article', 'Published'),
        (2, 'Big Data Analytics in Healthcare: Challenges and Opportunities', 'Dr. Michael Chen, Maria Rodriguez, James Wilson', 'Journal of Biomedical Informatics', '2023-09-12', '10.1016/j.jbi.2023.104567', 'An analysis of big data applications in healthcare, discussing technical challenges, privacy concerns, and potential benefits for patient care.', 'big data, healthcare, analytics, privacy, patient care', 'https://www.sciencedirect.com/science/article/pii/S1532046423004567', 67, 'Journal Article', 'Published'),
        (1, 'Deep Learning for Automated Code Review', 'Dr. Sarah Johnson, Alex Thompson', 'ACM Transactions on Software Engineering and Methodology', '2024-06-01', '10.1145/1234567.1234568', 'This paper presents a novel deep learning approach for automated code review, demonstrating significant improvements in bug detection accuracy.', 'deep learning, code review, software engineering, bug detection', 'https://dl.acm.org/doi/10.1145/1234567.1234568', 12, 'Journal Article', 'Published')");
    echo "✓ Sample faculty papers created\n";

    // Insert sample students
    $pdo->exec("INSERT INTO students (student_id, full_name, email, course_id, batch_id, academic_status) VALUES
        ('CS2024001', 'Alice Johnson', 'alice.johnson@student.university.edu', 1, 1, 'Active'),
        ('CS2024002', 'Bob Smith', 'bob.smith@student.university.edu', 1, 1, 'Active'),
        ('CS2024003', 'Carol Davis', 'carol.davis@student.university.edu', 1, 1, 'Active')");
    echo "✓ Sample students created\n";

    // Insert sample student papers
    $pdo->exec("INSERT INTO student_papers (student_id, title, authors, journal_name, publication_date, doi, abstract, keywords, paper_url, citation_count, publication_type, status) VALUES
        (1, 'Student Perspectives on Online Learning During COVID-19', 'Alice Johnson, Dr. Sarah Johnson', 'Journal of Higher Education Technology', '2024-02-10', '10.1080/12345678.2024.1234567', 'This study explores undergraduate student experiences with online learning platforms during the COVID-19 pandemic, highlighting both challenges and opportunities for digital education.', 'online learning, COVID-19, student experience, digital education', 'https://www.tandfonline.com/doi/full/10.1080/12345678.2024.1234567', 8, 'Journal Article', 'Published'),
        (1, 'Mobile Application Development for Campus Navigation', 'Alice Johnson, Bob Smith', 'International Journal of Mobile Learning and Organisation', '2023-12-15', '10.1504/IJMLO.2024.123456', 'This paper presents the development and evaluation of a mobile application designed to help new students navigate university campuses more effectively.', 'mobile application, campus navigation, user experience, student services', 'https://www.inderscienceonline.com/doi/abs/10.1504/IJMLO.2024.123456', 5, 'Journal Article', 'Published'),
        (2, 'Blockchain Technology in Academic Credential Verification', 'Bob Smith, Carol Davis', 'Journal of Educational Technology Systems', '2024-01-08', '10.1177/123456789', 'An analysis of blockchain technology applications for secure academic credential verification and its potential impact on educational institutions.', 'blockchain, credential verification, academic integrity, technology adoption', 'https://journals.sagepub.com/doi/10.1177/123456789', 12, 'Journal Article', 'Published'),
        (3, 'AI-Powered Study Group Formation Algorithm', 'Carol Davis', 'Computer Applications in Engineering Education', '2023-10-20', '10.1002/cae.12345', 'This research proposes an algorithm that uses artificial intelligence to optimally form study groups based on student learning styles, academic performance, and collaborative preferences.', 'artificial intelligence, study groups, collaborative learning, algorithm design', 'https://onlinelibrary.wiley.com/doi/10.1002/cae.12345', 15, 'Journal Article', 'Published'),
        (2, 'Virtual Reality in STEM Education: A Systematic Review', 'Bob Smith, Alice Johnson, Dr. Michael Chen', 'Education and Information Technologies', '2024-04-12', '10.1007/s10639-024-12345-6', 'A comprehensive review of virtual reality applications in STEM education, examining effectiveness, implementation challenges, and future research directions.', 'virtual reality, STEM education, systematic review, educational technology', 'https://link.springer.com/article/10.1007/s10639-024-12345-6', 22, 'Journal Article', 'Published')");
    echo "✓ Sample student papers created\n";

    // Verify setup
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM roles");
    $roleCount = $stmt->fetch()['count'];
    echo "✓ Roles table has $roleCount roles\n";

    echo "\n🎉 Database setup complete!\n";
    echo "You can now run: php create_test_users.php\n";

} catch (Exception $e) {
    echo "❌ Database setup failed: " . $e->getMessage() . "\n";
    echo "Make sure MySQL is running and you have the correct credentials.\n";
}
?>