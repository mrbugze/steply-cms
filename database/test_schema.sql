-- Basic CMS Database Schema for Testing
-- This creates the minimum required tables for the modernized CMS

USE cms_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'instructor', 'student') DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    color VARCHAR(7) DEFAULT '#3B82F6',
    icon VARCHAR(50) DEFAULT 'book',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Courses table
CREATE TABLE IF NOT EXISTS courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    image_path VARCHAR(255),
    instructor_id INT,
    category_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (instructor_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL
);

-- Wallets table
CREATE TABLE IF NOT EXISTS wallets (
    wallet_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    balance DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Enrollments table
CREATE TABLE IF NOT EXISTS enrollments (
    enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (user_id, course_id)
);

-- Transactions table
CREATE TABLE IF NOT EXISTS transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    wallet_id INT NOT NULL,
    transaction_type ENUM('credit', 'debit') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (wallet_id) REFERENCES wallets(wallet_id) ON DELETE CASCADE
);

-- Insert default admin user
INSERT IGNORE INTO users (username, email, password_hash, role) VALUES 
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert sample categories
INSERT IGNORE INTO categories (name, description, color, icon) VALUES
('Web Development', 'Learn modern web technologies including HTML, CSS, JavaScript, and frameworks', '#3B82F6', 'code'),
('Data Science', 'Master data analysis, machine learning, and statistical modeling', '#10B981', 'chart-bar'),
('Digital Marketing', 'Grow your business with SEO, social media, and online advertising strategies', '#F59E0B', 'megaphone'),
('Design', 'Create beautiful user interfaces and user experiences', '#8B5CF6', 'palette'),
('Business', 'Develop entrepreneurial skills and business acumen', '#EF4444', 'briefcase'),
('Programming', 'Learn programming languages and software development', '#6366F1', 'terminal');

-- Insert sample instructor
INSERT IGNORE INTO users (username, email, password_hash, role) VALUES 
('instructor1', 'instructor@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'instructor');

-- Insert sample courses
INSERT IGNORE INTO courses (title, description, price, instructor_id, category_id) VALUES
('Modern JavaScript Fundamentals', 'Learn the latest JavaScript features and best practices for modern web development. This comprehensive course covers ES6+, async/await, modules, and more.', 99.99, 2, 1),
('React for Beginners', 'Build dynamic user interfaces with React. Learn components, state management, hooks, and modern React patterns.', 129.99, 2, 1),
('Python Data Analysis', 'Master data analysis with Python using pandas, numpy, and matplotlib. Perfect for aspiring data scientists.', 149.99, 2, 2),
('UI/UX Design Principles', 'Learn the fundamentals of user interface and user experience design. Create beautiful and functional designs.', 89.99, 2, 4),
('Digital Marketing Strategy', 'Develop effective digital marketing campaigns using SEO, social media, and content marketing strategies.', 79.99, 2, 3),
('Business Analytics', 'Learn to make data-driven business decisions using analytics tools and methodologies.', 119.99, 2, 5);

-- Insert sample student
INSERT IGNORE INTO users (username, email, password_hash, role) VALUES 
('student1', 'student@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student');

-- Create wallet for student
INSERT IGNORE INTO wallets (user_id, balance) VALUES (3, 500.00);

