-- Additional tables needed for the traditional PHP implementation

-- Admissions table (different from applications in schema)
CREATE TABLE IF NOT EXISTS admissions (
    admission_pk_id INT PRIMARY KEY AUTO_INCREMENT,
    application_number VARCHAR(50) NOT NULL UNIQUE,
    applicant_name VARCHAR(200) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(15),
    course_applied VARCHAR(150),
    application_date DATE DEFAULT (CURRENT_DATE),
    status ENUM('pending', 'approved', 'rejected', 'waitlisted') DEFAULT 'pending',
    entrance_score DECIMAL(5,2) NULL,
    interview_score DECIMAL(5,2) NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Enhanced fee_payments table
CREATE TABLE IF NOT EXISTS fee_payments_enhanced (
    fee_payment_pk_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    fee_type VARCHAR(100) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    due_date DATE NULL,
    payment_date DATE NULL,
    payment_method VARCHAR(50),
    payment_status ENUM('pending', 'paid', 'overdue', 'refunded') DEFAULT 'pending',
    transaction_id VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_pk_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Library books table
CREATE TABLE IF NOT EXISTS library_books (
    book_pk_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(150),
    isbn VARCHAR(30),
    category VARCHAR(100),
    publisher VARCHAR(150),
    publication_year YEAR,
    pages INT,
    copies_total INT DEFAULT 1,
    status ENUM('available', 'issued', 'damaged', 'lost') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Library issues table (enhanced)
CREATE TABLE IF NOT EXISTS library_issues_enhanced (
    issue_pk_id INT PRIMARY KEY AUTO_INCREMENT,
    book_id INT NOT NULL,
    student_id INT NOT NULL,
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE NULL,
    status ENUM('issued', 'returned', 'overdue') DEFAULT 'issued',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES library_books(book_pk_id) ON DELETE RESTRICT,
    FOREIGN KEY (student_id) REFERENCES students(student_pk_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Enhanced placements table
CREATE TABLE IF NOT EXISTS placements (
    placement_pk_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    company_name VARCHAR(200) NOT NULL,
    position VARCHAR(150) NOT NULL,
    package_amount DECIMAL(10, 2) NULL COMMENT 'Package in lakhs',
    placement_date DATE NULL,
    location VARCHAR(150),
    employment_type ENUM('full_time', 'part_time', 'internship', 'contract', 'freelance') DEFAULT 'full_time',
    status ENUM('placed', 'interviewed', 'shortlisted', 'rejected') DEFAULT 'placed',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_pk_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Faculty papers and publications table
CREATE TABLE IF NOT EXISTS faculty_papers (
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
) ENGINE=InnoDB;