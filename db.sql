CREATE TABLE IF NOT EXISTS users (
 	id INT AUTO_INCREMENT PRIMARY KEY, 
 	NAME VARCHAR(50) NOT NULL, 
 	username VARCHAR(10) NOT NULL, 
 	email VARCHAR (50) NOT NULL UNIQUE, 
 	PASSWORD VARCHAR(50) NOT NULL, 
 	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 	updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP 
) ENGINE=INNODB;

CREATE TABLE if NOT EXISTS categories (
	id INT AUTO_INCREMENT PRIMARY KEY, 
	user_id INT, 
	NAME VARCHAR(100) NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	FOREIGN KEY (user_id) REFERENCES users(id) 
) ENGINE=INNODB;

CREATE TABLE if NOT EXISTS tasks (
	id INT AUTO_INCREMENT PRIMARY KEY, 
	title VARCHAR(100) NOT NULL, 
	DESCRIPTION TEXT, 
	STATUS ENUM('Not Started', 'In Progress', 'Completed') DEFAULT 'Not Started',
	deadline DATE,  
	user_id INT,
	category_id INT, 
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
	FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
	FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=INNODB;
	
CREATE TABLE if NOT EXISTS subcategories (
	id INT AUTO_INCREMENT PRIMARY KEY, 
	category_id INT, 
	NAME VARCHAR(100) NOT NULL, 
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
	FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE 
) ENGINE=INNODB;

ALTER TABLE tasks
ADD COLUMN subcategory_id INT NULL, 
ADD FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE SET NULL; 

ALTER TABLE users  
DROP COLUMN NAME;

ALTER TABLE users MODIFY password VARCHAR(255) NOT NULL;

-- Tambah user
INSERT INTO users (name, username, email, password)
VALUES 
('Mira A', 'mira', 'mira@example.com', '12345'),
('Budi S', 'budi', 'budi@example.com', 'abcd');

-- Tambah kategori
INSERT INTO categories (user_id, name)
VALUES 
(1, 'Kuliah'),
(1, 'Pribadi'),
(2, 'Kerja');

-- Tambah subkategori
INSERT INTO subcategories (category_id, NAME)
VALUES 
(1, 'Proker'),
(1, 'Ujian');

-- Tambah tugas
INSERT INTO tasks (title, description, status, deadline, user_id, category_id)
VALUES
('Kerjakan laporan UTS', 'Laporan CRUD PHP To Do List', 'In Progress', '2025-10-15', 1, 1),
('Belajar PHP', 'Fokus ke koneksi database', 'Not Started', '2025-10-20', 1, 2),
('Meeting project', 'Diskusi dengan tim', 'Completed', '2025-10-10', 2, 3);
