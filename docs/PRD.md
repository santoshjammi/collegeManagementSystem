SIMS Apex: Minimal Viable Product (MVP) PRD

1. Executive Summary & Goals

Attribute

Detail

Project

SIMS Apex: College Management System MVP

Objective

Digitize 6 core administrative and academic functions using a cost-effective technology stack.

Target Cost

₹4,95,000 (Fixed Price)

Target Launch

2 Months from start (Rapid Delivery)

Status

Scoped for Development

1.1. Core Business Goals (The "Why")

Digital Transition: Replace all paper-based processes for the 6 core modules with a centralized, digital database.

Cost Control: Adhere strictly to the ₹5,00,000 budget by focusing only on core CRUD (Create, Read, Update, Delete) functionality and essential reporting.

Efficiency Gain: Provide staff and administrators with immediate access to essential records (Student, Fee, Library) to save manual processing time.

2. Technology Stack & Design Principles

2.1. Technology Stack

The technology stack is explicitly chosen for its stability, low hosting cost, and speed of implementation for a database-driven MVP.

Layer

Technology

Rationale

Backend

PHP (Native/Light MVC)

Enterprise-grade stability, rapid scripting capabilities, and broad hosting support.

Database

MySQL

Open-source, highly reliable, and optimized for relational data structures.

Frontend Structure

HTML5 / Bootstrap 5

Provides standardized, responsive components (navbars, tables, forms) out-of-the-box.

Frontend Styling

Tailwind CSS

Used for utility-first custom styling, ensuring a clean, modern look beyond generic Bootstrap defaults.

2.2. Design & UX Principles (21st.dev Adherence)

The interface must be designed to be intuitive and usable by administrative staff and students who may not be highly technical.

Mobile-First Design: All data entry forms and tables must be fully responsive. On mobile, large data tables must be presented in a card view or a horizontally scrollable container for usability.

Simplicity: No unnecessary animations or complex visual effects. Focus on clear data presentation. Dashboards will be simple lists/counts, not complex graphs.

Accessibility: Use high-contrast color palettes (e.g., Bootstrap default success/warning/danger colors for status indicators) and ensure all form fields have clear labels.

Role-Based Interface: The navigation bar and dashboard content must dynamically change based on the logged-in user's role (Admin, Staff, Faculty, Student).

3. Detailed Functional Requirements (MVP User Stories)

3.1. User & Role Management (Authentication)

The system will support four distinct user roles, all authenticated via a simple Login page.

Role

Access

Admin

Full access to all modules (Configuration, CRUD, Reporting).

Staff

CRUD access to Admission, Fee, and Library modules. Read-only access to Student/Faculty profiles.

Faculty

Read-only access to Student profiles; CRUD access to their own Faculty Profile.

Student

Read-only access to their own profile, fee status, and library history.

User Stories:

As a User, I want to securely log in with a User ID and Password so I can access my personalized dashboard.

As an Admin, I want to create, edit, and deactivate user accounts and assign them one of the four core roles.

3.2. Student Management

Focus: Basic student demographic and core academic data.

User Stories (Admin/Staff):

As Staff, I want to enter a new student's full profile including: ID, Name, Contact, Program/Degree, Batch, Academic Status (e.g., Active, On Leave), and Anticipated Graduation Year.

As Staff, I want to search and filter students by Course, Batch, or Enrollment Status.

As Staff, I want to update a student's contact details or current course mapping.

As Admin, I want to export a basic list of all active students (Excel/CSV format).

3.3. Faculty Management

Focus: Directory of faculty and staff with role definitions and core academic specialization.

User Stories (Admin/Staff):

As Admin, I want to create and assign roles to Faculty/Staff profiles including: Name, Department, Role, Contact, Primary Subject/Specialization, Highest Degree (e.g., Ph.D., M.Tech), and a descriptive text field for Research Interests/Expertise.

As Staff, I want to view a list of all active Faculty members and their primary contact information.

As Faculty, I want to log in and view/update my personal contact details.

3.4. Admissions

Focus: Basic application data capture and manual status tracking.

User Stories (External/Staff):

As a Prospective Student, I want to fill out a simple online application form to submit my basic demographic and academic details.

As Staff, I want to view a list of all submitted applications, sorted by submission date.

As Staff, I want to manually change the status of an application (e.g., Pending Review, Approved, Rejected) via a dropdown menu.

As Staff, I want to convert an "Approved" application into an "Active Student Profile" in the Student Management module.

3.5. Fee Management

Focus: Defining a single standard fee structure and manual collection tracking.

User Stories (Admin/Staff):

As Admin, I want to define one Standard Fee Structure per Course/Batch (e.g., Tution Fee: 50,000, Exam Fee: 5,000).

As Staff, I want to manually record a fee payment against a student's name, noting the Amount Paid and Date of Payment.

As Staff, I want to view the outstanding fee balance for any student based on the standard fee structure minus recorded payments.

As Admin, I want to generate a report showing all students with an outstanding fee balance.

3.6. Library Management

Focus: Core cataloguing and issue/return functions.

User Stories (Staff):

As Staff, I want to add a new book (Title, Author, ISBN, Total Copies) to the Library Catalogue.

As Staff, I want to mark a book as "Issued" to a specific student ID (linking the book ID to the student ID and recording the Issue Date).

As Staff, I want to mark a book as "Returned," noting the Return Date.

As Staff, I want to view a list of all currently issued books and the students who possess them.

3.7. Placement System

Focus: Simple database for tracking placement activities.

User Stories (Staff):

As Staff, I want to add new Company Profiles (Name, Contact, Industry) to the placement database.

As Staff, I want to update a Student Profile with their Placement Status (e.g., Placed, Interviewing, Not Placed) and the Company Name.

As Staff, I want to view a report of all students who have been successfully placed, grouped by company.

4. Non-Functional Requirements

Category

Requirement

Priority

Performance

Page load times must be under 3 seconds on a standard broadband connection. Database queries must be optimized for fast data retrieval (no complex joins in the MVP).

High

Security

Implement parameterized queries (prepared statements in PHP/MySQL) to prevent SQL Injection. All password storage must use strong hashing (e.g., PHP's password_hash).

Critical

Scalability

Database schema must be normalized (third normal form) to allow for future feature expansion without major refactoring. Database MUST allow for future custom fields (EAV pattern) for Student and Faculty profiles in Phase 2.

High

Usability

All forms must include input validation (client-side via HTML/JS and server-side via PHP) to ensure data quality.

High

5. Out of Scope (Budget Control)

To maintain the fixed cost of ₹4,95,000, the following items are explicitly excluded from the MVP scope and must be considered for Phase 2:

Online Payment Gateway Integration. (All fee collection is manual entry).

Automated Timetable Generation or advanced scheduling features.

SMS/Email Notification Integration (Notifications are manual or TBD).

Parent Portal or Parent Login access.

Digital Library integration (only physical book tracking is supported).

Complex analytics, interactive dashboards, or predictive reports (only basic data exports).

Integrated Chat or Communication Tools.