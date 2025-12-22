-- 1. Users Table (Handles Login for both Admin and Prisoners)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- Will store Hashed passwords
    role ENUM('admin', 'prisoner') NOT NULL
);

-- 2. Prisoners Table (Core Data)
CREATE TABLE prisoners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT, -- Links to the users table so a prisoner can login
    full_name VARCHAR(100) NOT NULL,
    crime VARCHAR(255),
    sentence_duration INT, -- In months
    behavior_points INT DEFAULT 50, -- Start at average
    status ENUM('Normal', 'Isolated', 'Paroled') DEFAULT 'Normal',
    assigned_duty VARCHAR(100) DEFAULT 'None',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- 3. Work Logs (For the "Pending Permission" feature)
CREATE TABLE work_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prisoner_id INT,
    hours_worked INT,
    work_type VARCHAR(100),
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    FOREIGN KEY (prisoner_id) REFERENCES prisoners(id)
);

-- INSERT A DEFAULT ADMIN (Password is 'admin123' - hash it in real app)
-- Note: In production, use PHP password_hash() to generate the password string
INSERT INTO users (username, password, role) VALUES ('admin', '$2y$10$ThISiSaHaShEdPaSsWoRdExAmPlE', 'admin');