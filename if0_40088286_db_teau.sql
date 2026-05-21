-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql210.infinityfree.com
-- Generation Time: May 21, 2026 at 05:54 AM
-- Server version: 11.4.10-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_40088286_db_teau`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_events`
--

CREATE TABLE `academic_events` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(7) DEFAULT '#4CAF50',
  `program_filter` varchar(100) DEFAULT 'ALL'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academic_events`
--

INSERT INTO `academic_events` (`id`, `title`, `start_date`, `end_date`, `description`, `color`, `program_filter`) VALUES
(1, 'National Day Holiday', '2025-10-20', '2025-10-21', 'University closed for National Day.', '#FFC107', 'ALL'),
(2, 'CS Final Project Submission', '2025-11-28', '2025-11-29', 'Deadline for all final year Computer Science projects.', '#DC3545', 'CS'),
(3, 'Course Registration Opens', '2025-12-05', NULL, 'Open registration for next semester. Be quick!', '#28A745', 'ALL'),
(4, 'Final Examination Period', '2025-12-15', '2025-12-23', 'The main period for all final semester examinations.', '#2196F3', 'ALL'),
(5, 'Tuition Fee Payment Deadline', '2025-11-10', NULL, 'Last day to pay fees without penalty for the current semester.', '#FF5722', 'ALL'),
(6, 'Last Day to Withdraw (W)', '2025-10-31', NULL, 'The last day to officially withdraw from any course without academic penalty.', '#9E9E9E', 'ALL'),
(7, 'Winter Holiday Break', '2025-12-24', '2026-01-05', 'University closed for the holiday season.', '#4CAF50', 'ALL'),
(8, 'IT Security Audit Workshop', '2025-10-25', NULL, 'Mandatory workshop for all Year 3 IT students on ethical hacking.', '#00BCD4', 'IT'),
(9, 'IT Capstone Defense Day', '2025-12-01', '2025-12-03', 'Presentation and defense of final year Capstone projects.', '#00BCD4', 'IT'),
(10, 'CS Algorithm Competition', '2025-11-05', NULL, 'Annual coding competition hosted by the CS department.', '#DC3545', 'CS'),
(11, 'Software Engineering Review', '2025-11-15', NULL, 'Review session for the Software Engineering final exam.', '#DC3545', 'CS'),
(12, 'BBA Internship Report Deadline', '2025-11-20', NULL, 'Final deadline for all BBA students completing their industrial attachment reports.', '#FF9800', 'BBA'),
(13, 'BBA Financial Modeling Seminar', '2025-11-08', NULL, 'Guest lecture on advanced financial modeling techniques.', '#FF9800', 'BBA'),
(14, 'EDU Teaching Practice Submission', '2025-12-08', NULL, 'Final submission of teaching practice logbooks and evaluation forms.', '#8BC34A', 'EDU'),
(15, 'EDU Curriculum Design Workshop', '2025-10-27', '2025-10-28', 'Two-day workshop on modern curriculum design for Year 2 students.', '#8BC34A', 'EDU'),
(16, 'NUR Clinical Skills Assessment', '2025-11-01', '2025-11-02', 'Practical assessment for all Year 1 and 2 Nursing students.', '#E91E63', 'NUR'),
(17, 'NUR Seminar: Public Health Crisis', '2025-11-25', NULL, 'Mandatory seminar on managing infectious disease outbreaks in public health.', '#E91E63', 'NUR'),
(18, 'IT Security Audit Workshop', '2025-10-25', NULL, 'Mandatory workshop for all Year 3 IT students on ethical hacking.', '#00BCD4', 'IT'),
(19, 'IT Capstone Defense Day', '2025-12-01', '2025-12-03', 'Presentation and defense of final year Capstone projects.', '#00BCD4', 'IT'),
(20, 'CS Algorithm Competition', '2025-11-05', NULL, 'Annual coding competition hosted by the CS department.', '#DC3545', 'CS'),
(21, 'Software Engineering Review', '2025-11-15', NULL, 'Review session for the Software Engineering final exam.', '#DC3545', 'CS'),
(22, 'BBA Internship Report Deadline', '2025-11-20', NULL, 'Final deadline for all BBA students completing their industrial attachment reports.', '#FF9800', 'BBA'),
(23, 'BBA Financial Modeling Seminar', '2025-11-08', NULL, 'Guest lecture on advanced financial modeling techniques.', '#FF9800', 'BBA'),
(24, 'EDU Teaching Practice Submission', '2025-12-08', NULL, 'Final submission of teaching practice logbooks and evaluation forms.', '#8BC34A', 'EDU'),
(25, 'EDU Curriculum Design Workshop', '2025-10-27', '2025-10-28', 'Two-day workshop on modern curriculum design for Year 2 students.', '#8BC34A', 'EDU'),
(26, 'NUR Clinical Skills Assessment', '2025-11-01', '2025-11-02', 'Practical assessment for all Year 1 and 2 Nursing students.', '#E91E63', 'NUR'),
(27, 'NUR Seminar: Public Health Crisis', '2025-11-25', NULL, 'Mandatory seminar on managing infectious disease outbreaks in public health.', '#E91E63', 'NUR'),
(28, 'IT Security Audit Workshop', '2025-10-25', NULL, 'Mandatory workshop for all Year 3 IT students on ethical hacking.', '#00BCD4', 'IT'),
(29, 'IT Capstone Defense Day', '2025-12-01', '2025-12-03', 'Presentation and defense of final year Capstone projects.', '#00BCD4', 'IT'),
(30, 'CS Algorithm Competition', '2025-11-05', NULL, 'Annual coding competition hosted by the CS department.', '#DC3545', 'CS'),
(31, 'Software Engineering Review', '2025-11-15', NULL, 'Review session for the Software Engineering final exam.', '#DC3545', 'CS'),
(32, 'BBA Internship Report Deadline', '2025-11-20', NULL, 'Final deadline for all BBA students completing their industrial attachment reports.', '#FF9800', 'BBA'),
(33, 'BBA Financial Modeling Seminar', '2025-11-08', NULL, 'Guest lecture on advanced financial modeling techniques.', '#FF9800', 'BBA'),
(34, 'EDU Teaching Practice Submission', '2025-12-08', NULL, 'Final submission of teaching practice logbooks and evaluation forms.', '#8BC34A', 'EDU'),
(35, 'EDU Curriculum Design Workshop', '2025-10-27', '2025-10-28', 'Two-day workshop on modern curriculum design for Year 2 students.', '#8BC34A', 'EDU'),
(36, 'NUR Clinical Skills Assessment', '2025-11-01', '2025-11-02', 'Practical assessment for all Year 1 and 2 Nursing students.', '#E91E63', 'NUR'),
(37, 'NUR Seminar: Public Health Crisis', '2025-11-25', NULL, 'Mandatory seminar on managing infectious disease outbreaks in public health.', '#E91E63', 'NUR'),
(38, 'IT Security Audit Workshop', '2025-10-25', NULL, 'Mandatory workshop for all Year 3 IT students on ethical hacking.', '#00BCD4', 'IT'),
(39, 'IT Capstone Defense Day', '2025-12-01', '2025-12-03', 'Presentation and defense of final year Capstone projects.', '#00BCD4', 'IT'),
(40, 'CS Algorithm Competition', '2025-11-05', NULL, 'Annual coding competition hosted by the CS department.', '#DC3545', 'CS'),
(41, 'Software Engineering Review', '2025-11-15', NULL, 'Review session for the Software Engineering final exam.', '#DC3545', 'CS'),
(42, 'BBA Internship Report Deadline', '2025-11-20', NULL, 'Final deadline for all BBA students completing their industrial attachment reports.', '#FF9800', 'BBA'),
(43, 'BBA Financial Modeling Seminar', '2025-11-08', NULL, 'Guest lecture on advanced financial modeling techniques.', '#FF9800', 'BBA'),
(44, 'EDU Teaching Practice Submission', '2025-12-08', NULL, 'Final submission of teaching practice logbooks and evaluation forms.', '#8BC34A', 'EDU'),
(45, 'EDU Curriculum Design Workshop', '2025-10-27', '2025-10-28', 'Two-day workshop on modern curriculum design for Year 2 students.', '#8BC34A', 'EDU'),
(46, 'NUR Clinical Skills Assessment', '2025-11-01', '2025-11-02', 'Practical assessment for all Year 1 and 2 Nursing students.', '#E91E63', 'NUR'),
(47, 'NUR Seminar: Public Health Crisis', '2025-11-25', NULL, 'Mandatory seminar on managing infectious disease outbreaks in public health.', '#E91E63', 'NUR'),
(48, 'IT Security Audit Workshop', '2025-10-25', NULL, 'Mandatory workshop for all Year 3 IT students on ethical hacking.', '#00BCD4', 'IT'),
(49, 'IT Capstone Defense Day', '2025-12-01', '2025-12-03', 'Presentation and defense of final year Capstone projects.', '#00BCD4', 'IT'),
(50, 'CS Algorithm Competition', '2025-11-05', NULL, 'Annual coding competition hosted by the CS department.', '#DC3545', 'CS'),
(51, 'Software Engineering Review', '2025-11-15', NULL, 'Review session for the Software Engineering final exam.', '#DC3545', 'CS'),
(52, 'BBA Internship Report Deadline', '2025-11-20', NULL, 'Final deadline for all BBA students completing their industrial attachment reports.', '#FF9800', 'BBA'),
(53, 'BBA Financial Modeling Seminar', '2025-11-08', NULL, 'Guest lecture on advanced financial modeling techniques.', '#FF9800', 'BBA'),
(54, 'EDU Teaching Practice Submission', '2025-12-08', NULL, 'Final submission of teaching practice logbooks and evaluation forms.', '#8BC34A', 'EDU'),
(55, 'EDU Curriculum Design Workshop', '2025-10-27', '2025-10-28', 'Two-day workshop on modern curriculum design for Year 2 students.', '#8BC34A', 'EDU'),
(56, 'NUR Clinical Skills Assessment', '2025-11-01', '2025-11-02', 'Practical assessment for all Year 1 and 2 Nursing students.', '#E91E63', 'NUR'),
(57, 'NUR Seminar: Public Health Crisis', '2025-11-25', NULL, 'Mandatory seminar on managing infectious disease outbreaks in public health.', '#E91E63', 'NUR'),
(58, 'IT Security Audit Workshop', '2025-10-25', NULL, 'Mandatory workshop for all Year 3 IT students on ethical hacking.', '#00BCD4', 'IT'),
(59, 'IT Capstone Defense Day', '2025-12-01', '2025-12-03', 'Presentation and defense of final year Capstone projects.', '#00BCD4', 'IT'),
(60, 'CS Algorithm Competition', '2025-11-05', NULL, 'Annual coding competition hosted by the CS department.', '#DC3545', 'CS'),
(61, 'Software Engineering Review', '2025-11-15', NULL, 'Review session for the Software Engineering final exam.', '#DC3545', 'CS'),
(62, 'BBA Internship Report Deadline', '2025-11-20', NULL, 'Final deadline for all BBA students completing their industrial attachment reports.', '#FF9800', 'BBA'),
(63, 'BBA Financial Modeling Seminar', '2025-11-08', NULL, 'Guest lecture on advanced financial modeling techniques.', '#FF9800', 'BBA'),
(64, 'EDU Teaching Practice Submission', '2025-12-08', NULL, 'Final submission of teaching practice logbooks and evaluation forms.', '#8BC34A', 'EDU'),
(65, 'EDU Curriculum Design Workshop', '2025-10-27', '2025-10-28', 'Two-day workshop on modern curriculum design for Year 2 students.', '#8BC34A', 'EDU'),
(66, 'NUR Clinical Skills Assessment', '2025-11-01', '2025-11-02', 'Practical assessment for all Year 1 and 2 Nursing students.', '#E91E63', 'NUR'),
(67, 'NUR Seminar: Public Health Crisis', '2025-11-25', NULL, 'Mandatory seminar on managing infectious disease outbreaks in public health.', '#E91E63', 'NUR');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `program` varchar(255) NOT NULL,
  `year` int(11) NOT NULL,
  `semester` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `isAdminUnit` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `program`, `year`, `semester`, `code`, `name`, `description`, `isAdminUnit`) VALUES
(2, 'Information Technology', 1, 1, 'IT102', 'Computer Systems', 'Covers the basic components of computer systems, including hardware and software architectures. ', 0),
(3, 'Information Technology', 1, 1, 'IT103', 'Database Management Systems I', 'Fundamentals of database design, normalization, and SQL.', 0),
(4, 'Information Technology', 1, 1, 'IT104', 'Web Development Fundamentals', 'Introduction to front-end web technologies like HTML, CSS, and JavaScript.', 0),
(5, 'Information Technology', 1, 1, 'IT105', 'Data Structures and Algorithms', 'Study of data structures and efficient algorithms for problem-solving.', 0),
(6, 'Information Technology', 1, 1, 'IT106', 'Introduction to Operating Systems', 'Explores the principles and functions of modern operating systems.', 0),
(7, 'Information Technology', 1, 1, 'IT107', 'Discrete Mathematics', 'Mathematical concepts crucial for computer science, including logic and set theory.', 0),
(8, 'Information Technology', 1, 1, 'IT108', 'Communication Skills', 'Enhances written and verbal communication for academic and professional settings.', 0),
(9, 'Information Technology', 1, 1, 'IT109', 'Physics for IT', 'Fundamental physics concepts with applications in information technology.', 0),
(10, 'Information Technology', 1, 1, 'IT110', 'Introduction to Software Engineering', 'Basics of the software development life cycle and its methodologies.', 0),
(11, 'Information Technology', 1, 2, 'IT111', 'Object-Oriented Programming', 'Advanced programming using object-oriented principles.', 0),
(12, 'Information Technology', 1, 2, 'IT112', 'Networking Essentials', 'Core concepts of computer networking, including protocols and network devices.', 0),
(13, 'Information Technology', 1, 2, 'IT113', 'Database Management Systems II', 'Advanced topics in database administration and security.', 0),
(14, 'Information Technology', 1, 2, 'IT114', 'Introduction to Cloud Computing', 'Explores cloud service models (IaaS, PaaS, SaaS) and deployment.', 0),
(15, 'Information Technology', 1, 2, 'IT115', 'System Analysis and Design', 'Techniques for analyzing system requirements and designing effective solutions.', 0),
(16, 'Information Technology', 1, 2, 'IT116', 'Cybersecurity Fundamentals', 'An overview of cybersecurity threats, vulnerabilities, and defenses.', 0),
(17, 'Information Technology', 1, 2, 'IT117', 'Introduction to AI', 'Foundational concepts of artificial intelligence and machine learning.', 0),
(18, 'Information Technology', 1, 2, 'IT118', 'Linear Algebra for IT', 'Linear algebra topics essential for data science and graphics.', 0),
(21, 'Computer Science', 1, 1, 'CS101', 'Programming Fundamentals', 'Basic programming and problem-solving using an imperative language.', 0),
(24, 'Computer Science', 1, 1, 'CS104', 'Introduction to Networks', 'Core principles of network communication and the OSI model.', 0),
(25, 'Computer Science', 1, 1, 'CS105', 'Data Structures', 'In-depth look at data structures and their implementation.', 0),
(26, 'Computer Science', 1, 1, 'CS106', 'Operating Systems', 'Study of process management, memory management, and file systems.', 0),
(27, 'Computer Science', 1, 1, 'CS107', 'Discrete Structures', 'Mathematical foundations of computer science.', 0),
(28, 'Computer Science', 1, 1, 'CS108', 'Technical Writing', 'Focuses on writing clear, concise technical documents.', 0),
(29, 'Computer Science', 1, 1, 'CS109', 'Web Design', 'Introduction to building websites with HTML and CSS.', 0),
(30, 'Computer Science', 1, 1, 'CS110', 'Introduction to Databases', 'Relational database concepts and SQL queries.', 0),
(31, 'Computer Science', 1, 2, 'CS111', 'Advanced Programming', 'Object-oriented design and development with practical application.', 0),
(32, 'Computer Science', 1, 2, 'CS112', 'Network Security', 'Techniques to secure computer networks and protect data.', 0),
(33, 'Computer Science', 1, 2, 'CS113', 'Calculus II', 'Continuation of calculus, focusing on integration and its applications.', 0),
(34, 'Computer Science', 1, 2, 'CS114', 'Algorithms Analysis', 'Analysis of algorithm efficiency and complexity.', 0),
(35, 'Computer Science', 1, 2, 'CS115', 'Software Engineering', 'Principles and practices of software development and maintenance.', 0),
(36, 'Computer Science', 1, 2, 'CS116', 'Introduction to Cryptography', 'Basics of secure communication and cryptographic algorithms.', 0),
(37, 'Computer Science', 1, 2, 'CS117', 'Artificial Intelligence', 'Exploration of AI concepts like search algorithms and knowledge representation.', 0),
(38, 'Computer Science', 1, 2, 'CS118', 'Introduction to Machine Learning', 'Introduction to supervised and unsupervised learning algorithms.', 0),
(39, 'Computer Science', 1, 2, 'CS119', 'Advanced Web Development', 'Building dynamic and interactive web applications.', 0),
(40, 'Computer Science', 1, 2, 'CS120', 'Operating System Principles', 'In-depth study of OS design and implementation.', 0),
(41, 'Business Administration', 1, 1, 'BA101', 'Principles of Management', 'Fundamentals of planning, organizing, leading, and controlling within an organization.', 0),
(42, 'Business Administration', 1, 1, 'BA102', 'Introduction to Financial Accounting', 'Covers the principles and practices of financial accounting, including preparing financial statements.', 0),
(43, 'Business Administration', 1, 1, 'BA103', 'Business Mathematics', 'Application of mathematical concepts to solve business problems.', 0),
(44, 'Business Administration', 1, 1, 'BA104', 'Microeconomics', 'Analysis of individual and firm behavior in making decisions regarding the allocation of resources.', 0),
(45, 'Business Administration', 1, 1, 'BA105', 'Business Communication', 'Develops effective written and oral communication skills for the business world.', 0),
(46, 'Business Administration', 1, 1, 'BA106', 'Business Law', 'Introduction to legal principles and regulations relevant to business operations.', 0),
(47, 'Business Administration', 1, 1, 'BA107', 'Introduction to Marketing', 'Covers marketing principles, strategies, and consumer behavior.', 0),
(48, 'Business Administration', 1, 1, 'BA108', 'Business Ethics', 'Explores ethical issues and social responsibility in a business context.', 0),
(49, 'Business Administration', 1, 1, 'BA109', 'Computer Applications in Business', 'Practical use of software tools like spreadsheets and databases for business tasks.', 0),
(50, 'Business Administration', 1, 1, 'BA110', 'Organizational Behavior', 'Study of how individuals and groups behave within organizations.', 0),
(51, 'Business Administration', 1, 2, 'BA111', 'Principles of Human Resource Management', 'Covers the functions of HR, including recruitment, training, and performance management.', 0),
(52, 'Business Administration', 1, 2, 'BA112', 'Managerial Accounting', 'Focuses on the use of accounting information for internal managerial decision-making.', 0),
(53, 'Business Administration', 1, 2, 'BA113', 'Macroeconomics', 'Study of the economy as a whole, including topics like inflation and unemployment.', 0),
(54, 'Business Administration', 1, 2, 'BA114', 'Business Statistics', 'Use of statistical methods to analyze and interpret business data.', 0),
(55, 'Business Administration', 1, 2, 'BA115', 'Financial Management', 'Explores financial decision-making, investment, and capital structure.', 0),
(56, 'Business Administration', 1, 2, 'BA116', 'Supply Chain Management', 'Covers the flow of goods and services from production to consumer.', 0),
(57, 'Business Administration', 1, 2, 'BA117', 'Global Business', 'Examines the challenges and opportunities of conducting business internationally.', 0),
(58, 'Business Administration', 1, 2, 'BA118', 'Consumer Behavior', 'In-depth study of how consumers make purchasing decisions.', 0),
(61, 'Nursing', 1, 1, 'NUR101', 'Anatomy and Physiology I', 'Study of the structure and function of the human body.', 0),
(62, 'Nursing', 1, 1, 'NUR102', 'Foundations of Nursing Practice', 'Introduction to the nursing profession, its history, and ethical principles.', 0),
(63, 'Nursing', 1, 1, 'NUR103', 'Pharmacology I', 'Basics of drug actions, administration, and patient safety.', 0),
(64, 'Nursing', 1, 1, 'NUR104', 'Microbiology', 'Covers microorganisms and their role in health and disease.', 0),
(65, 'Nursing', 1, 1, 'NUR105', 'Nutrition', 'Principles of human nutrition and its role in health maintenance.', 0),
(66, 'Nursing', 1, 1, 'NUR106', 'Health Assessment', 'Develops skills for conducting comprehensive health assessments.', 0),
(67, 'Nursing', 1, 1, 'NUR107', 'Medical-Surgical Nursing I', 'Care of adults with common medical and surgical conditions.', 0),
(68, 'Nursing', 1, 1, 'NUR108', 'Psychology for Nurses', 'Application of psychological principles to patient care and communication.', 0),
(69, 'Nursing', 1, 1, 'NUR109', 'Introduction to Sociology', 'Examines social factors influencing health and illness.', 0),
(70, 'Nursing', 1, 1, 'NUR110', 'Nursing Informatics', 'Use of information technology to support nursing practice.', 0),
(71, 'Nursing', 1, 2, 'NUR111', 'Anatomy and Physiology II', 'Continuation of the study of the human body, focusing on specific organ systems.', 0),
(72, 'Nursing', 1, 2, 'NUR112', 'Maternal and Child Health Nursing', 'Nursing care for women during pregnancy and for children from infancy to adolescence.', 0),
(73, 'Nursing', 1, 2, 'NUR113', 'Pharmacology II', 'Advanced topics in pharmacology, including complex drug interactions and therapies.', 0),
(74, 'Nursing', 1, 2, 'NUR114', 'Community Health Nursing', 'Focuses on health promotion and disease prevention in community settings.', 0),
(75, 'Nursing', 1, 2, 'NUR115', 'Medical-Surgical Nursing II', 'Advanced care of adults with complex health conditions.', 0),
(76, 'Nursing', 1, 2, 'NUR116', 'Mental Health Nursing', 'Care for individuals with mental health disorders and emotional challenges.', 0),
(77, 'Nursing', 1, 2, 'NUR117', 'Nursing Research', 'Introduction to the research process and evidence-based practice in nursing.', 0),
(78, 'Nursing', 1, 2, 'NUR118', 'Pathophysiology', 'Study of the changes in physiology associated with diseases.', 0),
(81, 'Information Technology', 2, 1, 'IT201', 'Advanced Web Development', 'Building dynamic and interactive web applications using frameworks.', 0),
(82, 'Information Technology', 2, 1, 'IT202', 'Network Administration', 'Managing and configuring computer networks, servers, and services.', 0),
(83, 'Information Technology', 2, 1, 'IT203', 'Software Quality Assurance', 'Principles and techniques for ensuring the quality of software systems.', 0),
(84, 'Information Technology', 2, 1, 'IT204', 'Operating Systems Administration', 'System administration tasks for popular operating systems.', 0),
(85, 'Information Technology', 2, 1, 'IT205', 'Ethical Hacking', 'An introduction to penetration testing and ethical hacking techniques.', 0),
(86, 'Information Technology', 2, 1, 'IT206', 'Database Design and Implementation', 'Advanced database design principles and implementation strategies.', 0),
(87, 'Information Technology', 2, 1, 'IT207', 'Mobile Application Development', 'Developing mobile applications for iOS and Android platforms.', 0),
(88, 'Information Technology', 2, 1, 'IT208', 'Internet of Things (IoT)', 'Explores the concepts, technologies, and applications of IoT.', 0),
(89, 'Information Technology', 2, 1, 'IT209', 'Data Visualization', 'Techniques and tools for creating visual representations of data.', 0),
(90, 'Information Technology', 2, 1, 'IT210', 'Introduction to UX/UI Design', 'Fundamentals of user experience and user interface design.', 0),
(91, 'Information Technology', 2, 2, 'IT211', 'DevOps Fundamentals', 'Introduction to the principles and practices of DevOps.', 0),
(92, 'Information Technology', 2, 2, 'IT212', 'Network Security', 'Advanced topics in securing computer networks and data.', 0),
(93, 'Information Technology', 2, 2, 'IT213', 'Big Data Analytics', 'An overview of technologies for processing and analyzing large datasets.', 0),
(94, 'Information Technology', 2, 2, 'IT214', 'Enterprise Resource Planning (ERP)', 'Explores the concepts and implementation of ERP systems.', 0),
(95, 'Information Technology', 2, 2, 'IT215', 'Cloud Security', 'Securing data and applications in a cloud computing environment.', 0),
(96, 'Information Technology', 2, 2, 'IT216', 'IT Project Management', 'Managing IT projects from initiation to completion.', 0),
(97, 'Information Technology', 2, 2, 'IT217', 'Business Intelligence', 'Using data to make informed business decisions.', 0),
(98, 'Information Technology', 2, 2, 'IT218', 'Virtualization and Containerization', 'Covers virtualization technologies and container platforms like Docker.', 0),
(101, 'Information Technology', 3, 1, 'IT301', 'Artificial Intelligence and Machine Learning', 'Advanced topics in AI and ML, including deep learning.', 0),
(102, 'Information Technology', 3, 1, 'IT302', 'Data Science with R', 'Introduction to data science using the R programming language.', 0),
(103, 'Information Technology', 3, 1, 'IT303', 'Advanced Cybersecurity', 'In-depth study of advanced cyber threats and defense strategies.', 0),
(104, 'Information Technology', 3, 1, 'IT304', 'Cloud Architecture', 'Designing and implementing scalable cloud solutions.', 0),
(105, 'Information Technology', 3, 1, 'IT305', 'Web Security', 'Protecting web applications from common security vulnerabilities.', 0),
(106, 'Information Technology', 3, 1, 'IT306', 'Database Administration', 'Managing and maintaining large-scale database systems.', 0),
(107, 'Information Technology', 3, 1, 'IT307', 'User-Centered Design', 'Methods for designing systems that are easy and intuitive to use.', 0),
(108, 'Information Technology', 3, 1, 'IT308', 'Enterprise Systems Integration', 'Integrating different enterprise systems to work together.', 0),
(109, 'Information Technology', 3, 1, 'IT309', 'IT Service Management', 'Covers ITIL framework and best practices for IT services.', 0),
(110, 'Information Technology', 3, 1, 'IT310', 'Advanced Data Structures', 'Complex data structures and their applications.', 0),
(111, 'Information Technology', 3, 2, 'IT311', 'Mobile Security', 'Securing mobile devices and applications.', 0),
(112, 'Information Technology', 3, 2, 'IT312', 'Blockchain Technology', 'Fundamentals of blockchain and decentralized applications.', 0),
(114, 'Information Technology', 3, 2, 'IT314', 'Ethical and Legal Issues in IT', 'Examines the legal and ethical responsibilities of IT professionals.', 0),
(115, 'Information Technology', 3, 2, 'IT315', 'Machine Learning Engineering', 'Engineering practices for building and deploying ML models.', 0),
(116, 'Information Technology', 3, 2, 'IT316', 'Database Performance Tuning', 'Optimizing database queries and system performance.', 0),
(118, 'Information Technology', 3, 2, 'IT318', 'Artificial Neural Networks', 'Study of the architecture and training of neural networks.', 0),
(119, 'Information Technology', 3, 2, 'IT319', 'Digital Forensics', 'Techniques for investigating digital crimes.', 0),
(120, 'Information Technology', 3, 2, 'IT320', 'Natural Language Processing', 'Covers methods for computers to understand human language.', 0),
(122, 'Information Technology', 4, 1, 'IT402', 'Capita IT Project', 'A year-long project for students to apply their skills to a real-world problem.', 0),
(123, 'Information Technology', 4, 1, 'IT403', 'Advanced Distributed Systems', 'Covers distributed computing and system design.', 0),
(125, 'Information Technology', 4, 1, 'IT405', 'Data Mining', 'Techniques for discovering patterns and insights from large datasets.', 0),
(126, 'Information Technology', 4, 1, 'IT406', 'Computer Vision', 'Methods for enabling computers to \"see\" and interpret images.', 0),
(127, 'Information Technology', 4, 1, 'IT407', 'Quantum Computing', 'Introduction to the principles of quantum mechanics for computing.', 0),
(128, 'Information Technology', 4, 1, 'IT408', 'Mobile Games Development', 'Advanced topics in creating games for mobile devices.', 0),
(129, 'Information Technology', 4, 1, 'IT409', 'IT and Society', 'Examines the social and ethical impacts of technology.', 0),
(130, 'Information Technology', 4, 1, 'IT410', 'Advanced AI', 'Current trends and research in artificial intelligence.', 0),
(131, 'Information Technology', 4, 2, 'IT411', 'Thesis Project', 'Culminating research and development project.', 0),
(132, 'Information Technology', 4, 2, 'IT412', 'Digital Signal Processing', 'Covers the theory and application of digital signal processing.', 0),
(133, 'Information Technology', 4, 2, 'IT413', 'Advanced Cryptography', 'In-depth study of modern cryptographic algorithms and protocols.', 0),
(134, 'Information Technology', 4, 2, 'IT414', 'Cloud Security Operations', 'Day-to-day security operations in a cloud environment.', 0),
(135, 'Information Technology', 4, 2, 'IT415', 'Computer Graphics', 'Principles of 2D and 3D computer graphics.', 0),
(136, 'Information Technology', 4, 2, 'IT416', 'Information Retrieval', 'Techniques for searching and retrieving information from large collections.', 0),
(137, 'Information Technology', 4, 2, 'IT417', 'E-commerce Systems', 'Designing and implementing e-commerce websites and platforms.', 0),
(138, 'Information Technology', 4, 2, 'IT418', 'Human-Computer Interaction', 'Principles of designing interactive computer systems.', 0),
(139, 'Information Technology', 4, 2, 'IT419', 'Data Warehousing', 'Designing and managing data warehouses for business intelligence.', 0),
(140, 'Information Technology', 4, 2, 'IT420', 'Professional Practice', 'Preparation for entering the professional IT workforce.', 0),
(141, 'Computer Science', 2, 1, 'CS201', 'Theory of Computation', 'Study of the limits and capabilities of computation.', 0),
(142, 'Computer Science', 2, 1, 'CS202', 'Compilers and Interpreters', 'Principles and techniques for building compilers.', 0),
(143, 'Computer Science', 2, 1, 'CS203', 'Machine Learning', 'In-depth study of machine learning algorithms and their applications.', 0),
(144, 'Computer Science', 2, 1, 'CS204', 'Computer Graphics', 'Fundamentals of creating and manipulating visual content.', 0),
(148, 'Computer Science', 2, 1, 'CS208', 'Software Project Management', 'Managing software development projects and teams.', 0),
(149, 'Computer Science', 2, 1, 'CS209', 'Programming Languages', 'Comparative study of different programming paradigms.', 0),
(150, 'Computer Science', 2, 1, 'CS210', 'Web Engineering', 'Designing and building complex web applications.', 0),
(151, 'Computer Science', 2, 2, 'CS211', 'Operating Systems Design', 'In-depth study of the design principles of operating systems.', 0),
(152, 'Computer Science', 2, 2, 'CS212', 'Computer Networks', 'Detailed study of network protocols, security, and performance.', 0),
(153, 'Computer Science', 2, 2, 'CS213', 'Advanced Algorithms', 'Analysis and design of advanced algorithms for complex problems.', 0),
(154, 'Computer Science', 2, 2, 'CS214', 'Computer Security', 'Foundations of computer security, including cryptography and system security.', 0),
(155, 'Computer Science', 2, 2, 'CS215', 'Introduction to Data Science', 'Methods and tools for working with data.', 0),
(156, 'Computer Science', 2, 2, 'CS216', 'Human-Computer Interaction', 'Designing and evaluating user interfaces.', 0),
(157, 'Computer Science', 2, 2, 'CS217', 'Robotics', 'Introduction to the mechanics, control, and programming of robots.', 0),
(158, 'Computer Science', 2, 2, 'CS218', 'Computational Biology', 'Application of computing to biological problems.', 0),
(159, 'Computer Science', 2, 2, 'CS219', 'Compiler Construction', 'Hands-on project to build a simple compiler.', 0),
(160, 'Computer Science', 2, 2, 'CS220', 'Scientific Computing', 'Using computation to solve scientific and engineering problems.', 0),
(161, 'Computer Science', 3, 1, 'CS301', 'Advanced Databases', 'Advanced topics in database systems, including NoSQL and big data.', 0),
(162, 'Computer Science', 3, 1, 'CS302', 'Data Mining', 'Techniques for discovering patterns in large datasets.', 0),
(164, 'Computer Science', 3, 1, 'CS304', 'Computer Vision', 'Theory and application of computer vision systems.', 0),
(166, 'Computer Science', 3, 1, 'CS306', 'Machine Learning II', 'Advanced machine learning models, including deep learning.', 0),
(167, 'Computer Science', 3, 1, 'CS307', 'Natural Language Processing', 'Methods for computers to process and understand human language.', 0),
(168, 'Computer Science', 3, 1, 'CS308', 'Cybersecurity', 'Comprehensive course on cybersecurity principles and practices.', 0),
(169, 'Computer Science', 3, 1, 'CS309', 'Parallel and Concurrent Programming', 'Advanced programming techniques for concurrent systems.', 0),
(170, 'Computer Science', 3, 1, 'CS310', 'Formal Languages and Automata', 'Mathematical study of computation and languages.', 0),
(171, 'Computer Science', 3, 2, 'CS311', 'Distributed Systems', 'Principles of designing and implementing distributed systems.', 0),
(172, 'Computer Science', 3, 2, 'CS312', 'Network Programming', 'Building network applications and services.', 0),
(173, 'Computer Science', 3, 2, 'CS313', 'Computational Complexity', 'Theoretical study of computational problem difficulty.', 0),
(174, 'Computer Science', 3, 2, 'CS314', 'Mobile Application Development', 'Developing applications for mobile devices.', 0),
(175, 'Computer Science', 3, 2, 'CS315', 'Information Retrieval', 'The science of searching for and retrieving information from data.', 0),
(176, 'Computer Science', 3, 2, 'CS316', 'Robotics and AI', 'Integration of AI into robotic systems.', 0),
(177, 'Computer Science', 3, 2, 'CS317', 'High-Performance Computing', 'Techniques for building very fast computer systems.', 0),
(178, 'Computer Science', 3, 2, 'CS318', 'Computer Graphics II', 'Advanced topics in rendering, animation, and visualization.', 0),
(179, 'Computer Science', 3, 2, 'CS319', 'Embedded Systems', 'Designing and programming embedded systems.', 0),
(180, 'Computer Science', 3, 2, 'CS320', 'Software Quality and Testing', 'Ensuring the quality and reliability of software.', 0),
(181, 'Computer Science', 4, 1, 'CS401', 'Advanced Operating Systems', 'Research-oriented topics in modern operating systems.', 0),
(182, 'Computer Science', 4, 1, 'CS402', 'Capita CS Project', 'A major project to demonstrate mastery of skills.', 0),
(184, 'Computer Science', 4, 1, 'CS404', 'Cloud Computing', 'Architecture and services of cloud computing platforms.', 0),
(186, 'Computer Science', 4, 1, 'CS406', 'Game Theory', 'Mathematical models of strategic interaction among rational decision-makers.', 0),
(187, 'Computer Science', 4, 1, 'CS407', 'Artificial Neural Networks', 'Study of deep learning and neural network architectures.', 0),
(188, 'Computer Science', 4, 1, 'CS408', 'Reinforcement Learning', 'AI that learns through trial and error.', 0),
(189, 'Computer Science', 4, 1, 'CS409', 'Compiler Design', 'Advanced topics in compiler optimization and design.', 0),
(190, 'Computer Science', 4, 1, 'CS410', 'Machine Learning in the Cloud', 'Deploying machine learning models on cloud platforms.', 0),
(191, 'Computer Science', 4, 2, 'CS411', 'Thesis Project', 'Culminating research project.', 0),
(192, 'Computer Science', 4, 2, 'CS412', 'Advanced Networking', 'Current research topics in computer networks.', 0),
(193, 'Computer Science', 4, 2, 'CS413', 'Computer Vision and Deep Learning', 'Application of deep learning to computer vision.', 0),
(194, 'Computer Science', 4, 2, 'CS414', 'Cybersecurity Capstone', 'A project-based course on solving real-world security problems.', 0),
(195, 'Computer Science', 4, 2, 'CS415', 'Advanced Algorithms II', 'Specialized algorithms for advanced problems.', 0),
(196, 'Computer Science', 4, 2, 'CS416', 'Software Engineering Practice', 'Practical application of software engineering principles.', 0),
(197, 'Computer Science', 4, 2, 'CS417', 'Data Science Capstone', 'A project to apply data science skills to a complex problem.', 0),
(198, 'Computer Science', 4, 2, 'CS418', 'Bioinformatics', 'Using computer science to manage and analyze biological data.', 0),
(199, 'Computer Science', 4, 2, 'CS419', 'Ethical Issues in Computing', 'Exploration of ethical challenges in the digital age.', 0),
(200, 'Computer Science', 4, 2, 'CS420', 'Professional Development', 'Preparing for a career in the computing industry.', 0),
(202, 'Business Administration', 2, 1, 'BA202', 'Business Finance', 'Principles of corporate finance, including capital budgeting and valuation.', 0),
(204, 'Business Administration', 2, 1, 'BA204', 'Principles of Marketing', 'In-depth look at marketing strategies and market analysis.', 0),
(205, 'Business Administration', 2, 1, 'BA205', 'Human Resource Management', 'Strategic management of human capital in an organization.', 0),
(206, 'Business Administration', 2, 1, 'BA206', 'Business Research Methods', 'Techniques for conducting business research and data analysis.', 0),
(207, 'Business Administration', 2, 1, 'BA207', 'International Business', 'Explores the complexities of doing business in a global environment.', 0),
(208, 'Business Administration', 2, 1, 'BA208', 'Supply Chain Analytics', 'Using data to optimize supply chain performance.', 0),
(209, 'Business Administration', 2, 1, 'BA209', 'Digital Marketing', 'Strategies for marketing products and services online.', 0),
(210, 'Business Administration', 2, 1, 'BA210', 'Investment Analysis', 'Introduction to financial markets and investment instruments.', 0),
(211, 'Business Administration', 2, 2, 'BA211', 'Cost Accounting', 'Methods for calculating and controlling business costs.', 0),
(212, 'Business Administration', 2, 2, 'BA212', 'Corporate Finance', 'Financing decisions, capital structure, and dividend policy.', 0),
(213, 'Business Administration', 2, 2, 'BA213', 'Service Operations Management', 'Managing operations in a service-based business.', 0),
(214, 'Business Administration', 2, 2, 'BA214', 'Consumer Behavior', 'In-depth study of the psychology of consumer purchasing.', 0),
(215, 'Business Administration', 2, 2, 'BA215', 'Labor Relations', 'Legal and practical aspects of managing labor relationships.', 0),
(216, 'Business Administration', 2, 2, 'BA216', 'Management Information Systems', 'How technology supports business processes and decision-making.', 0),
(217, 'Business Administration', 2, 2, 'BA217', 'Business Analytics', 'Using data and statistical analysis to improve business performance.', 0),
(218, 'Business Administration', 2, 2, 'BA218', 'Public Relations', 'Managing communication between an organization and its public.', 0),
(219, 'Business Administration', 2, 2, 'BA219', 'E-commerce', 'Building and managing online business platforms.', 0),
(220, 'Business Administration', 2, 2, 'BA220', 'Project Management II', 'Advanced techniques for project planning and execution.', 0),
(222, 'Business Administration', 3, 1, 'BA302', 'Corporate Governance', 'Rules, practices, and processes for directing and controlling a company.', 0),
(224, 'Business Administration', 3, 1, 'BA304', 'Market Research', 'Techniques for collecting and analyzing data on markets and customers.', 0),
(225, 'Business Administration', 3, 1, 'BA305', 'Training and Development', 'Designing and implementing employee training programs.', 0),
(226, 'Business Administration', 3, 1, 'BA306', 'Financial Derivatives', 'Introduction to options, futures, and swaps.', 0),
(227, 'Business Administration', 3, 1, 'BA307', 'International Finance', 'Managing financial aspects of international operations.', 0),
(228, 'Business Administration', 3, 1, 'BA308', 'Change Management', 'Strategies for managing organizational change.', 0),
(230, 'Business Administration', 3, 1, 'BA310', 'Business Law II', 'Advanced legal topics for business professionals.', 0),
(232, 'Business Administration', 3, 2, 'BA312', 'Risk Management', 'Identifying, assessing, and mitigating business risks.', 0),
(233, 'Business Administration', 3, 2, 'BA313', 'Quality Management', 'Tools and techniques for ensuring product and service quality.', 0),
(234, 'Business Administration', 3, 2, 'BA314', 'Sales Management', 'Managing a sales team and sales process.', 0),
(235, 'Business Administration', 3, 2, 'BA315', 'Compensation and Benefits', 'Designing effective compensation and benefits systems.', 0),
(236, 'Business Administration', 3, 2, 'BA316', 'Digital Transformation', 'Leveraging technology to fundamentally change business operations.', 0),
(237, 'Business Administration', 3, 2, 'BA317', 'Data-Driven Decision Making', 'Using data analytics to inform business strategies.', 0),
(238, 'Business Administration', 3, 2, 'BA318', 'Organizational Leadership', 'Developing leadership skills for managing teams and organizations.', 0),
(239, 'Business Administration', 3, 2, 'BA319', 'Corporate Social Responsibility', 'Integrating social and environmental concerns into business.', 0),
(240, 'Business Administration', 3, 2, 'BA320', 'Entrepreneurship II', 'Advanced topics in business startup and growth.', 0),
(241, 'Business Administration', 4, 1, 'BA401', 'Strategic Financial Management', 'Advanced topics in financial strategy and planning.', 0),
(242, 'Business Administration', 4, 1, 'BA402', 'Capita Business Project', 'A major project to apply business principles to a real case.', 0),
(244, 'Business Administration', 4, 1, 'BA404', 'Advanced Operations Management', 'Optimizing complex operational systems.', 0),
(246, 'Business Administration', 4, 1, 'BA406', 'Business Valuation', 'Methods for determining the value of a business.', 0),
(247, 'Business Administration', 4, 1, 'BA407', 'Strategic Human Resource Management', 'Aligning HR practices with business strategy.', 0),
(248, 'Business Administration', 4, 1, 'BA408', 'Mergers and Acquisitions', 'Financial and strategic aspects of M&A.', 0),
(249, 'Business Administration', 4, 1, 'BA409', 'International Trade', 'Laws and practices of global trade.', 0),
(250, 'Business Administration', 4, 1, 'BA410', 'Corporate Finance II', 'Advanced corporate finance models and applications.', 0),
(251, 'Business Administration', 4, 2, 'BA411', 'Thesis Project', 'Culminating research project on a business topic.', 0),
(252, 'Business Administration', 4, 2, 'BA412', 'Business Ethics and Sustainability', 'Deep dive into ethical dilemmas and sustainable business models.', 0),
(253, 'Business Administration', 4, 2, 'BA413', 'Financial Modeling', 'Building financial models using spreadsheet software.', 0),
(254, 'Business Administration', 4, 2, 'BA414', 'Advanced Digital Marketing', 'Advanced strategies and tools for online marketing.', 0),
(255, 'Business Administration', 4, 2, 'BA415', 'Entrepreneurship Capstone', 'Practical experience in developing a business plan.', 0),
(256, 'Business Administration', 4, 2, 'BA416', 'Innovation Management', 'Managing the process of innovation within an organization.', 0),
(258, 'Business Administration', 4, 2, 'BA418', 'Global Supply Chain Management', 'Managing complex global supply chains.', 0),
(260, 'Business Administration', 4, 2, 'BA420', 'Professional Practice in Business', 'Preparation for a career in business and management.', 0),
(261, 'Nursing', 2, 1, 'NUR201', 'Medical-Surgical Nursing III', 'Advanced care of patients with complex medical conditions.', 0),
(262, 'Nursing', 2, 1, 'NUR202', 'Obstetrical Nursing', 'Nursing care for women and newborns during pregnancy, labor, and postpartum.', 0),
(263, 'Nursing', 2, 1, 'NUR203', 'Pediatric Nursing', 'Specialized nursing care for infants, children, and adolescents.', 0),
(264, 'Nursing', 2, 1, 'NUR204', 'Mental Health Nursing II', 'Advanced psychiatric nursing concepts and interventions.', 0),
(265, 'Nursing', 2, 1, 'NUR205', 'Community Health Nursing II', 'Planning and implementing health programs for communities.', 0),
(266, 'Nursing', 2, 1, 'NUR206', 'Nursing Leadership and Management', 'Leadership theories and management principles for nurses.', 0),
(267, 'Nursing', 2, 1, 'NUR207', 'Pharmacology III', 'Clinical application of pharmacology in diverse patient populations.', 0),
(268, 'Nursing', 2, 1, 'NUR208', 'Nutrition and Dietetics', 'Role of nutrition in health, disease, and patient care.', 0),
(269, 'Nursing', 2, 1, 'NUR209', 'Health Promotion', 'Strategies for promoting health and wellness in individuals and groups.', 0),
(270, 'Nursing', 2, 1, 'NUR210', 'Nursing Ethics and Law', 'Legal and ethical issues in nursing practice.', 0),
(271, 'Nursing', 2, 2, 'NUR211', 'Medical-Surgical Nursing IV', 'Critical care nursing and advanced clinical reasoning.', 0),
(274, 'Nursing', 2, 2, 'NUR214', 'Oncology Nursing', 'Nursing care for patients with cancer.', 0),
(275, 'Nursing', 2, 2, 'NUR215', 'Critical Care Nursing', 'Care for critically ill patients in an intensive care setting.', 0),
(276, 'Nursing', 2, 2, 'NUR216', 'Public Health Nursing', 'Focuses on population health and public health policy.', 0),
(277, 'Nursing', 2, 2, 'NUR217', 'Nursing Theory', 'Study of major nursing theories and their application.', 0),
(278, 'Nursing', 2, 2, 'NUR218', 'Nursing Research II', 'Advanced research methods and data analysis in nursing.', 0),
(279, 'Nursing', 2, 2, 'NUR219', 'Informatics in Nursing', 'Use of health IT systems and electronic health records.', 0),
(280, 'Nursing', 2, 2, 'NUR220', 'Patient Education', 'Teaching and counseling patients on health and wellness.', 0),
(281, 'Nursing', 3, 1, 'NUR301', 'Advanced Medical-Surgical Nursing I', 'Specialized care for patients with specific complex diseases.', 0),
(282, 'Nursing', 3, 1, 'NUR302', 'Maternity Nursing', 'Comprehensive care of women throughout the childbearing process.', 0),
(283, 'Nursing', 3, 1, 'NUR303', 'Pediatric Critical Care', 'Nursing care for critically ill infants and children.', 0),
(284, 'Nursing', 3, 1, 'NUR304', 'Psychiatric-Mental Health Nursing', 'Advanced skills for caring for individuals with psychiatric disorders.', 0),
(285, 'Nursing', 3, 1, 'NUR305', 'Community Health Nursing III', 'Leadership and policy development in public health.', 0),
(286, 'Nursing', 3, 1, 'NUR306', 'Health Care Systems', 'Study of the structure and function of health care systems.', 0),
(288, 'Nursing', 3, 1, 'NUR308', 'Nursing Informatics II', 'Designing and evaluating health information systems.', 0),
(290, 'Nursing', 3, 1, 'NUR310', 'Clinical Simulation', 'Hands-on practice in a simulated clinical environment.', 0),
(291, 'Nursing', 3, 2, 'NUR311', 'Advanced Medical-Surgical Nursing II', 'Clinical practicum focusing on advanced medical-surgical skills.', 0),
(292, 'Nursing', 3, 2, 'NUR312', 'Pediatric Nursing II', 'Advanced pediatric health assessment and interventions.', 0),
(293, 'Nursing', 3, 2, 'NUR313', 'Community and Home Health', 'Providing nursing care in patient homes and community settings.', 0),
(294, 'Nursing', 3, 2, 'NUR314', 'Palliative and Hospice Care', 'Caring for patients at the end of life and their families.', 0),
(295, 'Nursing', 3, 2, 'NUR315', 'Nursing Research III', 'Developing and executing a nursing research project.', 0),
(296, 'Nursing', 3, 2, 'NUR316', 'Global Health', 'Exploration of health issues from a global perspective.', 0),
(297, 'Nursing', 3, 2, 'NUR317', 'Evidence-Based Practice', 'Using research findings to guide clinical decision-making.', 0),
(298, 'Nursing', 3, 2, 'NUR318', 'Nursing and Healthcare Policy', 'The role of nurses in shaping healthcare policy.', 0),
(299, 'Nursing', 3, 2, 'NUR319', 'Clinical Reasoning', 'Developing critical thinking skills for clinical practice.', 0),
(300, 'Nursing', 3, 2, 'NUR320', 'Professional Role Transition', 'Preparing for the transition from student to professional nurse.', 0),
(301, 'Nursing', 4, 1, 'NUR401', 'Advanced Clinical Practice', 'Clinical rotations in specialized areas of nursing.', 0),
(302, 'Nursing', 4, 1, 'NUR402', 'Capita Nursing Project', 'A comprehensive project to address a current nursing issue.', 0),
(303, 'Nursing', 4, 1, 'NUR403', 'Health Care Law and Ethics', 'Legal and ethical principles governing healthcare practice.', 0),
(305, 'Nursing', 4, 1, 'NUR405', 'Clinical Leadership', 'Developing leadership skills in a clinical setting.', 0),
(307, 'Nursing', 4, 1, 'NUR407', 'Nursing Informatics III', 'Implementing and managing health information systems.', 0),
(308, 'Nursing', 4, 1, 'NUR408', 'Interprofessional Collaboration', 'Working effectively with other healthcare professionals.', 0),
(309, 'Nursing', 4, 1, 'NUR409', 'Public Health Emergencies', 'Responding to public health crises and disasters.', 0),
(310, 'Nursing', 4, 1, 'NUR410', 'Clinical Decision Making', 'Advanced skills in making clinical judgments.', 0),
(311, 'Nursing', 4, 2, 'NUR411', 'Thesis Project', 'Culminating research project.', 0),
(312, 'Nursing', 4, 2, 'NUR412', 'Nursing Education', 'Theories and strategies for teaching in nursing.', 0),
(314, 'Nursing', 4, 2, 'NUR414', 'Advanced Nursing Practice', 'Clinical practice in a chosen specialty area.', 0),
(316, 'Nursing', 4, 2, 'NUR416', 'Evidence-Based Practice Capstone', 'Implementing a change based on nursing research.', 0),
(317, 'Nursing', 4, 2, 'NUR417', 'Palliative Care Certification Prep', 'Preparation for specialized certification in palliative care.', 0),
(318, 'Nursing', 4, 2, 'NUR418', 'Transcultural Nursing', 'Providing culturally competent care to diverse populations.', 0),
(319, 'Nursing', 4, 2, 'NUR419', 'Case Management', 'Managing and coordinating patient care services.', 0),
(320, 'Nursing', 4, 2, 'NUR420', 'Professional Portfolio', 'Building a professional portfolio for career advancement.', 0),
(321, 'Nursing', 2, 1, 'NUR201', 'Medical-Surgical Nursing III', 'Advanced care of patients with complex medical conditions.', 0),
(322, 'Nursing', 2, 1, 'NUR202', 'Obstetrical Nursing', 'Nursing care for women and newborns during pregnancy, labor, and postpartum.', 0),
(323, 'Nursing', 2, 1, 'NUR203', 'Pediatric Nursing', 'Specialized nursing care for infants, children, and adolescents.', 0),
(325, 'Nursing', 2, 1, 'NUR205', 'Community Health Nursing II', 'Planning and implementing health programs for communities.', 0),
(327, 'Nursing', 2, 1, 'NUR207', 'Pharmacology III', 'Clinical application of pharmacology in diverse patient populations.', 0),
(328, 'Nursing', 2, 1, 'NUR208', 'Nutrition and Dietetics', 'Role of nutrition in health, disease, and patient care.', 0),
(329, 'Nursing', 2, 1, 'NUR209', 'Health Promotion', 'Strategies for promoting health and wellness in individuals and groups.', 0),
(330, 'Nursing', 2, 1, 'NUR210', 'Nursing Ethics and Law', 'Legal and ethical issues in nursing practice.', 0),
(331, 'Nursing', 2, 2, 'NUR211', 'Medical-Surgical Nursing IV', 'Critical care nursing and advanced clinical reasoning.', 0),
(332, 'Nursing', 2, 2, 'NUR212', 'Emergency and Trauma Nursing', 'Nursing care in emergency and trauma situations.', 0),
(333, 'Nursing', 2, 2, 'NUR213', 'Gerontological Nursing II', 'Advanced care and support for older adults.', 0),
(334, 'Nursing', 2, 2, 'NUR214', 'Oncology Nursing', 'Nursing care for patients with cancer.', 0),
(335, 'Nursing', 2, 2, 'NUR215', 'Critical Care Nursing', 'Care for critically ill patients in an intensive care setting.', 0),
(336, 'Nursing', 2, 2, 'NUR216', 'Public Health Nursing', 'Focuses on population health and public health policy.', 0),
(337, 'Nursing', 2, 2, 'NUR217', 'Nursing Theory', 'Study of major nursing theories and their application.', 0),
(338, 'Nursing', 2, 2, 'NUR218', 'Nursing Research II', 'Advanced research methods and data analysis in nursing.', 0),
(339, 'Nursing', 2, 2, 'NUR219', 'Informatics in Nursing', 'Use of health IT systems and electronic health records.', 0),
(340, 'Nursing', 2, 2, 'NUR220', 'Patient Education', 'Teaching and counseling patients on health and wellness.', 0),
(341, 'Nursing', 3, 1, 'NUR301', 'Advanced Medical-Surgical Nursing I', 'Specialized care for patients with specific complex diseases.', 0),
(342, 'Nursing', 3, 1, 'NUR302', 'Maternity Nursing', 'Comprehensive care of women throughout the childbearing process.', 0),
(343, 'Nursing', 3, 1, 'NUR303', 'Pediatric Critical Care', 'Nursing care for critically ill infants and children.', 0),
(344, 'Nursing', 3, 1, 'NUR304', 'Psychiatric-Mental Health Nursing', 'Advanced skills for caring for individuals with psychiatric disorders.', 0),
(346, 'Nursing', 3, 1, 'NUR306', 'Health Care Systems', 'Study of the structure and function of health care systems.', 0),
(348, 'Nursing', 3, 1, 'NUR308', 'Nursing Informatics II', 'Designing and evaluating health information systems.', 0),
(349, 'Nursing', 3, 1, 'NUR309', 'Nursing Leadership', 'Developing leadership skills for a variety of nursing roles.', 0),
(350, 'Nursing', 3, 1, 'NUR310', 'Clinical Simulation', 'Hands-on practice in a simulated clinical environment.', 0),
(351, 'Nursing', 3, 2, 'NUR311', 'Advanced Medical-Surgical Nursing II', 'Clinical practicum focusing on advanced medical-surgical skills.', 0),
(352, 'Nursing', 3, 2, 'NUR312', 'Pediatric Nursing II', 'Advanced pediatric health assessment and interventions.', 0),
(353, 'Nursing', 3, 2, 'NUR313', 'Community and Home Health', 'Providing nursing care in patient homes and community settings.', 0),
(354, 'Nursing', 3, 2, 'NUR314', 'Palliative and Hospice Care', 'Caring for patients at the end of life and their families.', 0),
(355, 'Nursing', 3, 2, 'NUR315', 'Nursing Research III', 'Developing and executing a nursing research project.', 0),
(356, 'Nursing', 3, 2, 'NUR316', 'Global Health', 'Exploration of health issues from a global perspective.', 0),
(357, 'Nursing', 3, 2, 'NUR317', 'Evidence-Based Practice', 'Using research findings to guide clinical decision-making.', 0),
(358, 'Nursing', 3, 2, 'NUR318', 'Nursing and Healthcare Policy', 'The role of nurses in shaping healthcare policy.', 0),
(359, 'Nursing', 3, 2, 'NUR319', 'Clinical Reasoning', 'Developing critical thinking skills for clinical practice.', 0),
(360, 'Nursing', 3, 2, 'NUR320', 'Professional Role Transition', 'Preparing for the transition from student to professional nurse.', 0),
(361, 'Nursing', 4, 1, 'NUR401', 'Advanced Clinical Practice', 'Clinical rotations in specialized areas of nursing.', 0),
(362, 'Nursing', 4, 1, 'NUR402', 'Capita Nursing Project', 'A comprehensive project to address a current nursing issue.', 0),
(363, 'Nursing', 4, 1, 'NUR403', 'Health Care Law and Ethics', 'Legal and ethical principles governing healthcare practice.', 0),
(364, 'Nursing', 4, 1, 'NUR404', 'Nursing Management', 'Management roles and responsibilities in healthcare.', 0),
(365, 'Nursing', 4, 1, 'NUR405', 'Clinical Leadership', 'Developing leadership skills in a clinical setting.', 0),
(368, 'Nursing', 4, 1, 'NUR408', 'Interprofessional Collaboration', 'Working effectively with other healthcare professionals.', 0),
(369, 'Nursing', 4, 1, 'NUR409', 'Public Health Emergencies', 'Responding to public health crises and disasters.', 0),
(370, 'Nursing', 4, 1, 'NUR410', 'Clinical Decision Making', 'Advanced skills in making clinical judgments.', 0),
(371, 'Nursing', 4, 2, 'NUR411', 'Thesis Project', 'Culminating research project.', 0),
(372, 'Nursing', 4, 2, 'NUR412', 'Nursing Education', 'Theories and strategies for teaching in nursing.', 0),
(373, 'Nursing', 4, 2, 'NUR413', 'Nursing Informatics Capstone', 'A project to apply informatics principles to a clinical problem.', 0),
(374, 'Nursing', 4, 2, 'NUR414', 'Advanced Nursing Practice', 'Clinical practice in a chosen specialty area.', 0),
(375, 'Nursing', 4, 2, 'NUR415', 'Leadership in Community Health', 'Leading health initiatives in a community setting.', 0),
(376, 'Nursing', 4, 2, 'NUR416', 'Evidence-Based Practice Capstone', 'Implementing a change based on nursing research.', 0),
(377, 'Nursing', 4, 2, 'NUR417', 'Palliative Care Certification Prep', 'Preparation for specialized certification in palliative care.', 0),
(378, 'Nursing', 4, 2, 'NUR418', 'Transcultural Nursing', 'Providing culturally competent care to diverse populations.', 0),
(379, 'Nursing', 4, 2, 'NUR419', 'Case Management', 'Managing and coordinating patient care services.', 0),
(380, 'Nursing', 4, 2, 'NUR420', 'Professional Portfolio', 'Building a professional portfolio for career advancement.', 0),
(385, 'Information Technology', 1, 1, 'IT101', 'Introduction to Programming', 'Introduction to programming concepts and coding languages', 0),
(386, 'IT', 2, 1, 'ITEC210', 'Data Structures and Algorithms', 'In-depth study of data structures and fundamental algorithms.', 0),
(387, 'IT', 2, 1, 'ITEC221', 'Database Systems', 'Covers relational database theory, design, and SQL.', 0),
(388, 'IT', 2, 2, 'ITEC222', 'Introduction to Artificial Intelligence', 'An overview of AI concepts, machine learning, and neural networks.', 0),
(390, 'EDU', 2, 1, 'EDU201', 'Educational Psychology', 'Explores the psychological principles of learning and teaching.', 0),
(391, 'Education', 3, 2, 'EDU311', 'Teaching Practice II', 'An extended and more independent teaching practicum.', 0),
(392, 'Education', 3, 2, 'EDU312', 'Educational Entrepreneurship', 'Skills for creating and managing educational projects, products, or services.', 0),
(393, 'Education', 3, 2, 'EDU313', 'Modern Pedagogical Approaches', 'An exploration of contemporary teaching methods like flipped classrooms and project-based learning.', 0),
(394, 'Education', 3, 2, 'EDU314', 'Educational Law', 'Covers the legal frameworks and regulations that govern education systems.', 0),
(395, 'Education', 3, 2, 'EDU315', 'Special Topics in Education', 'A seminar course on current, relevant topics in the field.', 0),
(396, 'Education', 3, 1, 'EDU301', 'Advanced Curriculum Design', 'Advanced topics in designing and implementing innovative and effective curricula.', 0),
(397, 'Education', 3, 1, 'EDU302', 'Digital Citizenship and Online Safety', 'Strategies for teaching students to be responsible and safe in a digital world.', 0),
(398, 'Education', 3, 1, 'EDU303', 'Teacher Professionalism and Ethics', 'Explores ethical dilemmas and professional responsibilities in the teaching profession.', 0),
(399, 'Education', 3, 1, 'EDU304', 'Action Research in Education', 'Students conduct a small-scale research project to improve their own teaching practices.', 0),
(400, 'Education', 3, 1, 'EDU305', 'Technology Integration in Classrooms', 'How to effectively use modern technology to enhance the teaching and learning process.', 0),
(401, 'Education', 1, 1, 'EDU101', 'Foundations of Education', 'An overview of the historical, philosophical, and social foundations of education.', 0),
(402, 'Education', 1, 1, 'EDU102', 'Child and Adolescent Development', 'Study of the physical, cognitive, and psychosocial development of students.', 0),
(403, 'Education', 1, 1, 'EDU103', 'Curriculum Design and Instruction', 'Principles of designing effective curriculum and instructional strategies.', 0),
(404, 'Education', 1, 1, 'EDU104', 'Introduction to Pedagogy', 'Explores the theory and practice of teaching and learning.', 0),
(405, 'Education', 1, 1, 'EDU105', 'Educational Technology', 'An introduction to technology tools and digital literacy for modern classrooms.', 0),
(406, 'Education', 1, 2, 'EDU111', 'Classroom Management', 'Strategies for creating a positive and productive learning environment.', 0),
(407, 'Education', 1, 2, 'EDU112', 'Assessment and Evaluation', 'Covers various methods for assessing student learning and providing feedback.', 0),
(408, 'Education', 1, 2, 'EDU113', 'Educational Psychology', 'The application of psychological principles to the field of education.', 0),
(409, 'Education', 1, 2, 'EDU114', 'Literacy and Language Arts', 'Methods for teaching reading, writing, and communication skills.', 0),
(410, 'Education', 1, 2, 'EDU115', 'History of Education', 'A study of the evolution of educational systems and philosophies over time.', 0),
(411, 'Education', 2, 1, 'EDU201', 'Inclusive Education', 'Principles and practices for teaching diverse learners, including those with special needs.', 0),
(412, 'Education', 2, 1, 'EDU202', 'Educational Research Methods', 'Introduction to quantitative and qualitative research methodologies in education.', 0),
(413, 'Education', 2, 1, 'EDU203', 'Subject-Specific Pedagogy', 'Focuses on effective teaching methods for a specific subject area (e.g., Mathematics).', 0),
(414, 'Education', 2, 1, 'EDU204', 'Global Issues in Education', 'Examines major educational challenges and trends on a global scale.', 0),
(415, 'Education', 2, 1, 'EDU205', 'Guidance and Counseling', 'Basic principles and techniques of student guidance and counseling in schools.', 0),
(416, 'Education', 2, 2, 'EDU211', 'Educational Leadership and Administration', 'Principles of leadership and management within school settings.', 0),
(417, 'Education', 2, 2, 'EDU212', 'Teaching Practice I', 'Supervised teaching experience in a school environment to apply theoretical knowledge.', 0),
(418, 'Education', 2, 2, 'EDU213', 'Educational Statistics', 'Application of statistical concepts to analyze educational data.', 0),
(419, 'Education', 2, 2, 'EDU214', 'Comparative Education', 'A comparison of educational systems across different countries and cultures.', 0),
(420, 'Education', 2, 2, 'EDU215', 'Educational Policy', 'An analysis of educational policies and their impact on schools and students.', 0),
(421, 'Education', 3, 1, 'EDU301', 'Advanced Curriculum Design', 'Advanced topics in designing and implementing innovative and effective curricula.', 0),
(422, 'Education', 3, 1, 'EDU302', 'Digital Citizenship and Online Safety', 'Strategies for teaching students to be responsible and safe in a digital world.', 0),
(423, 'Education', 3, 1, 'EDU303', 'Teacher Professionalism and Ethics', 'Explores ethical dilemmas and professional responsibilities in the teaching profession.', 0),
(424, 'Education', 3, 1, 'EDU304', 'Action Research in Education', 'Students conduct a small-scale research project to improve their own teaching practices.', 0),
(425, 'Education', 3, 1, 'EDU305', 'Technology Integration in Classrooms', 'How to effectively use modern technology to enhance the teaching and learning process.', 0),
(426, 'Education', 3, 2, 'EDU311', 'Teaching Practice II', 'An extended and more independent teaching practicum.', 0),
(427, 'Education', 3, 2, 'EDU312', 'Educational Entrepreneurship', 'Skills for creating and managing educational projects, products, or services.', 0);
INSERT INTO `courses` (`id`, `program`, `year`, `semester`, `code`, `name`, `description`, `isAdminUnit`) VALUES
(428, 'Education', 3, 2, 'EDU313', 'Modern Pedagogical Approaches', 'An exploration of contemporary teaching methods like flipped classrooms and project-based learning.', 0),
(429, 'Education', 3, 2, 'EDU314', 'Educational Law', 'Covers the legal frameworks and regulations that govern education systems.', 0),
(430, 'Education', 3, 2, 'EDU315', 'Special Topics in Education', 'A seminar course on current, relevant topics in the field.', 0),
(431, 'Education', 4, 1, 'EDU401', 'Thesis/Capstone Project I', 'Initiation and proposal of a major research or development project.', 0),
(432, 'Education', 4, 1, 'EDU402', 'Educational Psychology Seminar', 'Advanced topics and recent research in educational psychology.', 0),
(433, 'Education', 4, 1, 'EDU403', 'Assessment for Learning', 'Using assessment as a tool to guide instruction and student improvement.', 0),
(434, 'Education', 4, 1, 'EDU404', 'Policy and Leadership in Education', 'Examines the intersection of educational policy and effective leadership.', 0),
(435, 'Education', 4, 1, 'EDU405', 'Diversity and Equity in Education', 'Focuses on creating equitable learning environments for all students.', 0),
(436, 'Education', 4, 2, 'EDU411', 'Thesis/Capstone Project II', 'Completion and defense of the final capstone project or thesis.', 0),
(437, 'Education', 4, 2, 'EDU412', 'Teaching Practice III', 'Final, intensive teaching practice to prepare for a full-time teaching role.', 0),
(438, 'Education', 4, 2, 'EDU413', 'Future of Education', 'Explores emerging trends, technologies, and challenges shaping the future of education.', 0),
(439, 'Education', 4, 2, 'EDU414', 'Professional Development Seminar', 'Prepares students for career entry, including resume building and interview skills.', 0),
(440, 'Education', 4, 2, 'EDU415', 'School-Community Relations', 'Strategies for building strong relationships between schools, families, and communities.', 0);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `fullName` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `program` varchar(255) DEFAULT NULL,
  `year` varchar(50) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `userType` varchar(50) NOT NULL DEFAULT 'student',
  `reset_token` varchar(255) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `fullName`, `email`, `program`, `year`, `semester`, `password`, `userType`, `reset_token`, `token_expiry`) VALUES
(3, 'Alice William', 'alice.williams@example.com', 'Business Administration', '1', '1', '7c90f2dc82aa5dd4501132f6d074a53a', 'student', NULL, NULL),
(4, 'Bob Johnson', 'bob.johnson@example.com', 'Computer Science', '4', '1', 'bb77d0d3b3f239fa5db73bdf27b8d29a', 'student', NULL, NULL),
(5, 'Charlie Brown', 'charlie.brown@example.com', 'Education', '2', '2', 'b4af804009cb036a4ccdc33431ef9ac9', 'student', NULL, NULL),
(9, 'Clark Kent', 'clark.kent@example.com', 'Business Administration', '2', '1', '84d961568a65073a3bcf0eb216b2a576', 'student', NULL, NULL),
(10, 'Barry Allen', 'barry.allen@example.com', 'Education', '1', '1', 'bd2f6a1d5242c962a05619c56fa47ba6', 'student', NULL, NULL),
(11, 'Peter Parker', 'peter.parker@example.com', 'Information Technology', '2', '2', '9f05aa4202e4ce8d6a72511dc735cce9', 'student', NULL, NULL),
(12, 'Tony Stark', 'tony.stark@example.com', 'Computer Science', '4', '2', '0d94d92e3dc096f64213a5b34fa9d098', 'student', NULL, NULL),
(13, 'Natasha Romanoff', 'natasha.romanoff@example.com', 'Nursing', '3', '1', 'da541fe52945785469d97fb4cfad9b92', 'student', NULL, NULL),
(14, 'Thor Odinson', 'thor.odinson@example.com', 'Education', '4', '1', 'b9e2eb85b3af6e9a3f7877fa352e76ed', 'student', NULL, NULL),
(15, 'Lois Lane', 'lois.lane@example.com', 'Business Administration', '3', '2', '966557f07150738f32f0137895083c43', 'student', NULL, NULL),
(16, 'Oliver Queen', 'oliver.queen@example.com', 'Computer Science', '1', '2', '81a48e9c4bb7b6f15fb5febb84bfe52d', 'student', NULL, NULL),
(17, 'Sara Lance', 'sara.lance@example.com', 'Information Technology', '3', '1', 'aac23b5bf0df8af9793372b8e72adb11', 'student', NULL, NULL),
(18, 'Barry Allen Jr.', 'barry.allenjr@example.com', 'Education', '2', '1', '6a153fcfd9550776bda89e41346e4430', 'student', NULL, NULL),
(19, 'Professor X', 'professor.x@example.com', 'Computer Science', '4', '1', 'e39a2a06ea7520715802b155edab49d3', 'faculty', NULL, NULL),
(20, 'Principal Dumbledore', 'dumbledore@teauschool.edu', 'Business Administration', '4', '2', '29aa9483cb49c975ea2171671a74c844', 'admin', NULL, NULL),
(22, 'George Worker', 'georgejuma147@gmail.com', 'Information Technology', '4', '1', '$2y$10$cxloAE2FJAM.eiBNBg2FS.Ps5MBLGpS6oaESa90zloZzm0jSjtEG6', 'admin', '0658b5a9551188f80853218e6ce6e026789fc2ccd5b59e62ebadfea8292cf131', '2025-10-21 13:57:22'),
(29, 'John Smith', 'john.smith@example.com', 'Computer Science', '1', '1', '$2y$10$as1igudgESFLpmAGKzrJ6OVANeDdzcHGRrSC1vcYcWRpR6ozz/cL.', 'student', NULL, NULL),
(47, 'Kizz Jacobs', 'jacoboduor374@gmail.com', 'Computer Science', '4', '2', '$2y$10$1E7F/v38enIVQ5ku5fQTpeEZhD4qwU5ffKrP8eHSroBVTArQxVAcy', 'student', NULL, NULL),
(48, 'Titus', 'titus@gmail.com', 'Computer Science', '4', '1', '$2y$10$ODOh79v.iGfJg2ctw/1s5uSRYcGnAlWvMGSdSjdgQY7p6.pQ8CCA6', 'student', NULL, NULL),
(49, 'SHEDRACK RAELN', 'shedrackngumbau3@gmail.com', 'Computer Science', '4', '1', '$2y$10$kgzr16I3SFq6ANUdu3ICU.nPC5j86/n1ziP238BN9EZhjZalftmha', 'student', NULL, NULL),
(50, 'SHEDRACK RAELN', 'shedrackraeln@gmail.com', 'Computer Science', '4', '1', '$2y$10$Po64U03h16RjVjdS99GBHeajOhJ8Sstt3Lr9pFc8JmgI5DMnWK/7i', 'student', NULL, NULL),
(51, 'john Mamlin', 'mamlinjohn@gmail.com', 'Computer Science', '1', '1', '$2y$10$AE5yW33wDm.61Vlws2KBhO3wU7ga1YKqGIK4Yg8YHQgKF.6zRtFYG', 'student', NULL, NULL),
(52, 'Nems', 'mariganeema@gmail.com', 'Computer Science', '4', '1', '$2y$10$kvSULzZMLvnLu.IKgYXpoO46MZHiBVmBiSK/t/8IKAe9iqIjWvFr2', 'student', NULL, NULL),
(53, 'Lucas Ogeto Gekara', 'lucasgekara546@gmail.com', 'Information Technology', '4', '1', '$2y$10$dDbESY2x4sIV84LjyG.4uO1kDZuO/vITZFfEVHCIHS3YtMVbLg4TS', 'student', NULL, NULL),
(54, 'Pius', 'gabrielplus2001@gmail.com', 'Computer Science', '4', '2', '$2y$10$6d4fSKIWDFo/U1tDkYGFaeDpNB93hfpVxvuU730z7VegXiEtBrDGG', 'student', NULL, NULL),
(58, 'Anyway', 'antonytorotich@gmail.com', 'Computer Science', '3', '2', '$2y$10$8FvMk7HwoGhWKLv94xxmjuc2ALCQurTwdpiBbXaP77fnIak.cEK1C', 'student', NULL, NULL),
(70, 'ken', 'ken@gmail.com', 'Nursing', '1', '1', '$2y$10$foLF.YjWNOqshOthzHUfb.HCw1mxg0/KK.fmY260eL1kBnDuZHTyy', 'student', NULL, NULL),
(71, 'Purity Nafula Wangila', 'wangilapurity79@gmail.com', 'Computer Science', '4', '1', '$2y$10$I/DtfYifCLTtUD1WbQFs7.19K3TmQfjtiOnsUaEVN1d360hm8jqYW', 'student', NULL, NULL),
(76, 'Quintos Ajunga', 'ajunga@hotmail.com', 'Computer Science', '4', '1', '$2y$10$8b7HrtQVefRawpBXAWhW8e2msGOw2sEIuWDfXK9s0r1EkRFl0d/7y', 'student', 'd421c2dcc03a1ffcd95ccf7795544bbc4015d4af62a864e6102ee718793ed9e3', '2025-10-21 14:23:14'),
(77, 'George Wanjala', 'georgesworker8@gmail.com', 'Business Administration', '1', '2', '$2y$10$YNSz.EG5tnAxWdDBIi5yA.w9j0xKcONLyZi0g0w8OionUZ/018ueu', 'faculty', NULL, NULL),
(82, 'Alice William', 'Alice@hotmail.com', 'Computer Science', '1', '1', '$2y$10$kVlggfCXHTfbd7mlrTI4vutrhoAxmxCMDw4nBeAh6b2DlMxeD5Jaa', 'student', NULL, NULL),
(84, 'lucas', 'luk@gmail.com', 'Computer Science', '4', '2', '$2y$10$9UXgzhm.Y3q2GC3VMq6OMOcnN.UTlA8zbIqoRRIDM55ynlpx2f6qG', 'student', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_activity_log`
--

CREATE TABLE `user_activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `login_time` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_activity_log`
--

INSERT INTO `user_activity_log` (`id`, `user_id`, `login_time`) VALUES
(1, 22, '2025-10-22 02:19:20'),
(2, 76, '2025-10-22 02:25:22'),
(3, 22, '2025-10-22 02:27:55'),
(4, 22, '2025-10-22 03:53:29'),
(5, 22, '2025-10-22 23:49:14'),
(6, 76, '2025-10-22 23:54:06'),
(7, 22, '2025-10-23 01:38:07'),
(8, 58, '2025-10-23 01:41:56'),
(9, 22, '2025-10-24 01:25:01'),
(10, 76, '2025-10-24 01:26:06'),
(11, 22, '2025-10-24 01:32:58'),
(12, 76, '2025-10-24 01:34:51'),
(13, 76, '2025-10-24 01:44:55'),
(14, 76, '2025-10-24 02:00:01'),
(15, 22, '2025-10-24 02:07:08'),
(16, 22, '2025-10-24 02:07:49'),
(17, 22, '2025-10-24 03:03:41'),
(18, 76, '2025-10-26 23:05:40'),
(19, 22, '2025-10-26 23:06:52'),
(20, 76, '2025-10-27 00:57:19'),
(21, 22, '2025-10-27 00:59:31'),
(22, 22, '2025-10-27 01:30:59'),
(23, 22, '2025-10-27 03:15:33'),
(24, 22, '2025-10-27 03:16:02'),
(25, 22, '2025-10-27 03:16:34'),
(26, 22, '2025-10-27 03:16:43'),
(27, 22, '2025-10-27 03:16:45'),
(28, 22, '2025-10-27 03:17:17'),
(29, 22, '2025-10-27 03:23:46'),
(30, 22, '2025-10-27 03:24:13'),
(31, 76, '2025-10-27 03:50:29'),
(32, 22, '2025-10-27 04:13:42'),
(33, 22, '2025-10-27 06:20:54'),
(34, 76, '2025-10-27 23:41:25'),
(35, 22, '2025-10-27 23:42:48'),
(36, 22, '2025-10-27 23:47:31'),
(37, 22, '2025-10-27 23:50:14'),
(38, 22, '2025-11-05 04:34:54'),
(39, 76, '2025-11-05 04:35:58'),
(40, 22, '2025-11-09 23:10:45'),
(41, 76, '2025-11-09 23:11:30'),
(42, 76, '2025-11-14 00:56:25'),
(43, 76, '2025-11-28 05:26:48'),
(44, 22, '2025-11-28 05:29:10'),
(45, 22, '2026-01-18 22:52:19'),
(46, 76, '2026-01-18 22:52:54'),
(47, 22, '2026-01-18 23:18:25'),
(48, 76, '2026-01-18 23:19:56'),
(49, 22, '2026-01-19 03:46:19'),
(50, 22, '2026-01-22 02:09:11'),
(51, 76, '2026-01-25 03:13:13'),
(52, 22, '2026-01-29 23:54:00'),
(53, 76, '2026-01-29 23:55:29'),
(54, 76, '2026-02-11 22:27:06'),
(55, 76, '2026-02-13 02:05:48'),
(56, 22, '2026-03-11 03:02:20'),
(57, 22, '2026-03-11 03:05:09'),
(58, 22, '2026-03-11 03:07:44'),
(59, 84, '2026-03-11 03:12:18');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_events`
--
ALTER TABLE `academic_events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `reset_token` (`reset_token`);

--
-- Indexes for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_events`
--
ALTER TABLE `academic_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=447;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
