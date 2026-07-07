CREATE DATABASE IF NOT EXISTS siperuk;
USE siperuk;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('USER','ADMIN') NOT NULL DEFAULT 'USER',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user: admin@siperuk.com / password123
INSERT INTO `users` (`name`, `email`, `password_hash`, `role`) VALUES
('Administrator', 'admin@siperuk.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ADMIN');
-- Insert default regular user: user@siperuk.com / password123
INSERT INTO `users` (`name`, `email`, `password_hash`, `role`) VALUES
('Mahasiswa 1', 'user@siperuk.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'USER');

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `capacity` int(11) NOT NULL,
  `facilities` text NOT NULL,
  `location` varchar(150) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('ACTIVE','MAINTENANCE','DELETED') NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `rooms` (`name`, `capacity`, `facilities`, `location`, `status`, `image`) VALUES
('Aula', 200, 'AC Sentral, Proyektor Utama, Sound System, Panggung, Kursi VIP', 'Gedung Utama Lt. 1', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Lab Networking', 40, 'AC, PC Workstation, Switch Cisco, Router MikroTik, Proyektor', 'Gedung Lab Lt. 2', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Lab Bisnis Intelegent', 40, 'AC, PC Dual Monitor, Proyektor, Papan Tulis Interaktif', 'Gedung Lab Lt. 2', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Lab Programing', 40, 'AC, PC High-End, Proyektor, Papan Tulis Kaca', 'Gedung Lab Lt. 3', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Lab Web', 40, 'AC, PC, Proyektor, Papan Tulis', 'Gedung Lab Lt. 3', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Lab Multimedia', 40, 'AC, Mac Studio, Pen Tablet, Green Screen, Kamera, Proyektor', 'Gedung Lab Lt. 3'),
('Ruang Kelas 1.1', 40, 'AC, Proyektor, Papan Tulis, Kursi Mahasiswa', 'Gedung Kelas Lt. 1', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Ruang Kelas 1.2', 40, 'AC, Proyektor, Papan Tulis, Kursi Mahasiswa', 'Gedung Kelas Lt. 1', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Ruang Kelas 1.3', 40, 'AC, Proyektor, Papan Tulis, Kursi Mahasiswa', 'Gedung Kelas Lt. 1', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Ruang Kelas 1.4', 40, 'AC, Proyektor, Papan Tulis, Kursi Mahasiswa', 'Gedung Kelas Lt. 1', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Ruang Kelas 1.5', 40, 'AC, Proyektor, Papan Tulis, Kursi Mahasiswa', 'Gedung Kelas Lt. 1', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Ruang Kelas 1.6', 40, 'AC, Proyektor, Papan Tulis, Kursi Mahasiswa', 'Gedung Kelas Lt. 1', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Ruang Kelas 1.7', 40, 'AC, Proyektor, Papan Tulis, Kursi Mahasiswa', 'Gedung Kelas Lt. 1', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Ruang Kelas 1.8', 40, 'AC, Proyektor, Papan Tulis, Kursi Mahasiswa', 'Gedung Kelas Lt. 1', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Ruang Kelas 1.9', 40, 'AC, Proyektor, Papan Tulis, Kursi Mahasiswa', 'Gedung Kelas Lt. 1', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Ruang Kelas 2.1', 40, 'AC, Proyektor, Papan Tulis, Kursi Mahasiswa', 'Gedung Kelas Lt. 2', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Ruang Kelas 2.2', 40, 'AC, Proyektor, Papan Tulis, Kursi Mahasiswa', 'Gedung Kelas Lt. 2', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Ruang Kelas 2.3', 40, 'AC, Proyektor, Papan Tulis, Kursi Mahasiswa', 'Gedung Kelas Lt. 2', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Ruang Kelas 2.4', 40, 'AC, Proyektor, Papan Tulis, Kursi Mahasiswa', 'Gedung Kelas Lt. 2', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Ruang Kelas 2.5', 40, 'AC, Proyektor, Papan Tulis, Kursi Mahasiswa', 'Gedung Kelas Lt. 2', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Ruang Kelas 2.6', 40, 'AC, Proyektor, Papan Tulis, Kursi Mahasiswa', 'Gedung Kelas Lt. 2', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Ruang Kelas 2.7', 40, 'AC, Proyektor, Papan Tulis, Kursi Mahasiswa', 'Gedung Kelas Lt. 2', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Ruang Kelas 2.8', 40, 'AC, Proyektor, Papan Tulis, Kursi Mahasiswa', 'Gedung Kelas Lt. 2', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Ruang Kelas 2.9', 40, 'AC, Proyektor, Papan Tulis, Kursi Mahasiswa', 'Gedung Kelas Lt. 2', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Ruang Kelas 3.1', 40, 'AC, Proyektor, Papan Tulis, Kursi Mahasiswa', 'Gedung Kelas Lt. 3', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Ruang Kelas 3.2', 40, 'AC, Proyektor, Papan Tulis, Kursi Mahasiswa', 'Gedung Kelas Lt. 3', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Ruang Kelas 3.3', 40, 'AC, Proyektor, Papan Tulis, Kursi Mahasiswa', 'Gedung Kelas Lt. 3', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Ruang Kelas 3.4', 40, 'AC, Proyektor, Papan Tulis, Kursi Mahasiswa', 'Gedung Kelas Lt. 3', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Ruang Kelas 3.5', 40, 'AC, Proyektor, Papan Tulis, Kursi Mahasiswa', 'Gedung Kelas Lt. 3', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Ruang Kelas 3.6', 40, 'AC, Proyektor, Papan Tulis, Kursi Mahasiswa', 'Gedung Kelas Lt. 3', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Ruang Kelas 3.7', 40, 'AC, Proyektor, Papan Tulis, Kursi Mahasiswa', 'Gedung Kelas Lt. 3', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Ruang Kelas 3.8', 40, 'AC, Proyektor, Papan Tulis, Kursi Mahasiswa', 'Gedung Kelas Lt. 3', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Ruang Kelas 3.9', 40, 'AC, Proyektor, Papan Tulis, Kursi Mahasiswa', 'Gedung Kelas Lt. 3', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Ruang Kelas 4.1', 40, 'AC, Proyektor, Papan Tulis, Kursi Mahasiswa', 'Gedung Kelas Lt. 4', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Ruang Kelas 4.2', 40, 'AC, Proyektor, Papan Tulis, Kursi Mahasiswa', 'Gedung Kelas Lt. 4', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Ruang Kelas 4.3', 40, 'AC, Proyektor, Papan Tulis, Kursi Mahasiswa', 'Gedung Kelas Lt. 4', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Ruang Kelas 4.4', 40, 'AC, Proyektor, Papan Tulis, Kursi Mahasiswa', 'Gedung Kelas Lt. 4', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Ruang Kelas 4.5', 40, 'AC, Proyektor, Papan Tulis, Kursi Mahasiswa', 'Gedung Kelas Lt. 4', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Ruang Kelas 4.6', 40, 'AC, Proyektor, Papan Tulis, Kursi Mahasiswa', 'Gedung Kelas Lt. 4', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Ruang Kelas 4.7', 40, 'AC, Proyektor, Papan Tulis, Kursi Mahasiswa', 'Gedung Kelas Lt. 4', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Ruang Kelas 4.8', 40, 'AC, Proyektor, Papan Tulis, Kursi Mahasiswa', 'Gedung Kelas Lt. 4', 'ACTIVE', 'uploads/rooms/kelas_default.jpg'),
('Ruang Kelas 4.9', 40, 'AC, Proyektor, Papan Tulis, Kursi Mahasiswa', 'Gedung Kelas Lt. 4', 'ACTIVE', 'uploads/rooms/kelas_default.jpg');

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `event_name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `document_url` varchar(255) DEFAULT NULL,
  `status` enum('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `room_id` (`room_id`),
  KEY `idx_room_time_status` (`room_id`, `status`, `start_time`, `end_time`),
  CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
