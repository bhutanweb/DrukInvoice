CREATE DATABASE IF NOT EXISTS druk_grants;
USE druk_grants;

CREATE TABLE IF NOT EXISTS grants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    grantee VARCHAR(255) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    status ENUM('Pending','Active','Paused','Completed','Closed') NOT NULL DEFAULT 'Pending',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    progress_percent INT NOT NULL DEFAULT 0,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS grant_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grant_id INT NOT NULL,
    update_date DATE NOT NULL,
    summary TEXT NOT NULL,
    progress_percent INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (grant_id) REFERENCES grants(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grant_id INT,
    message VARCHAR(255) NOT NULL,
    type ENUM('Info','Warning','Alert') NOT NULL DEFAULT 'Info',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (grant_id) REFERENCES grants(id) ON DELETE SET NULL
);
