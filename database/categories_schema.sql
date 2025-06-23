-- Course Categories Enhancement SQL
-- Add this to your database to enable course categorization

-- Create categories table
CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    color VARCHAR(7) DEFAULT '#3B82F6', -- Hex color for category badge
    icon VARCHAR(50) DEFAULT 'book', -- Icon name for category
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add category_id to courses table (if not exists)
ALTER TABLE courses 
ADD COLUMN category_id INT DEFAULT NULL,
ADD FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL;

-- Insert default categories
INSERT INTO categories (name, description, color, icon) VALUES
('Web Development', 'Learn modern web technologies including HTML, CSS, JavaScript, and frameworks', '#3B82F6', 'code'),
('Data Science', 'Master data analysis, machine learning, and statistical modeling', '#10B981', 'chart-bar'),
('Digital Marketing', 'Grow your business with SEO, social media, and online advertising strategies', '#F59E0B', 'megaphone'),
('Design', 'Create beautiful user interfaces and user experiences', '#8B5CF6', 'palette'),
('Business', 'Develop entrepreneurial skills and business acumen', '#EF4444', 'briefcase'),
('Programming', 'Learn programming languages and software development', '#6366F1', 'terminal');

-- Update existing courses with categories (example assignments)
-- You can modify these based on your actual course content
UPDATE courses SET category_id = 1 WHERE title LIKE '%web%' OR title LIKE '%html%' OR title LIKE '%css%' OR title LIKE '%javascript%';
UPDATE courses SET category_id = 2 WHERE title LIKE '%data%' OR title LIKE '%analytics%' OR title LIKE '%python%' OR title LIKE '%machine learning%';
UPDATE courses SET category_id = 3 WHERE title LIKE '%marketing%' OR title LIKE '%seo%' OR title LIKE '%social media%';
UPDATE courses SET category_id = 4 WHERE title LIKE '%design%' OR title LIKE '%ui%' OR title LIKE '%ux%';
UPDATE courses SET category_id = 5 WHERE title LIKE '%business%' OR title LIKE '%entrepreneur%' OR title LIKE '%management%';
UPDATE courses SET category_id = 6 WHERE title LIKE '%programming%' OR title LIKE '%coding%' OR title LIKE '%software%';

