CREATE DATABASE IF NOT EXISTS tourism_db;
USE tourism_db;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin', 'agent', 'guide', 'tourist') NOT NULL DEFAULT 'tourist',
    status ENUM('active', 'inactive', 'locked') NOT NULL DEFAULT 'active',
    login_attempts INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE tourists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    nationality VARCHAR(50),
    date_of_birth DATE,
    passport_number VARCHAR(50),
    emergency_contact VARCHAR(100),
    emergency_phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE tour_packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    package_name VARCHAR(150) NOT NULL,
    destination VARCHAR(100) NOT NULL,
    duration_days INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    max_capacity INT NOT NULL,
    description TEXT,
    inclusions TEXT,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_reference VARCHAR(20) UNIQUE NOT NULL,
    tourist_id INT NOT NULL,
    package_id INT NOT NULL,
    booking_date DATE NOT NULL,
    participants INT NOT NULL,
    total_cost DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'partial', 'paid', 'cancelled') NOT NULL DEFAULT 'pending',
    status ENUM('confirmed', 'pending', 'cancelled', 'completed') NOT NULL DEFAULT 'pending',
    special_requests TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tourist_id) REFERENCES tourists(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES tour_packages(id) ON DELETE CASCADE
);

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    amount_paid DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'online', 'bank_transfer', 'mpesa') NOT NULL,
    transaction_reference VARCHAR(100),
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

CREATE TABLE guides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    languages TEXT,
    specialization VARCHAR(200),
    contact_number VARCHAR(20),
    availability ENUM('available', 'occupied', 'unavailable') NOT NULL DEFAULT 'available',
    rating DECIMAL(2,1) DEFAULT 0.0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE guide_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guide_id INT NOT NULL,
    booking_id INT NOT NULL,
    assignment_date DATE NOT NULL,
    status ENUM('assigned', 'completed', 'cancelled') NOT NULL DEFAULT 'assigned',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (guide_id) REFERENCES guides(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

CREATE TABLE accommodations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    location VARCHAR(200) NOT NULL,
    contact_phone VARCHAR(20),
    contact_email VARCHAR(100),
    room_capacity INT NOT NULL,
    price_per_night DECIMAL(10,2) NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE package_accommodations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    package_id INT NOT NULL,
    accommodation_id INT NOT NULL,
    nights INT NOT NULL DEFAULT 1,
    FOREIGN KEY (package_id) REFERENCES tour_packages(id) ON DELETE CASCADE,
    FOREIGN KEY (accommodation_id) REFERENCES accommodations(id) ON DELETE CASCADE
);

CREATE TABLE itineraries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    package_id INT NOT NULL,
    day_number INT NOT NULL,
    activity TEXT NOT NULL,
    location VARCHAR(200),
    timing VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (package_id) REFERENCES tour_packages(id) ON DELETE CASCADE
);

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    delivery_status ENUM('sent', 'failed', 'pending') NOT NULL DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

INSERT INTO users (username, password, email, phone, role, status) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@tourism.com', '+255700000000', 'admin', 'active');

INSERT INTO tour_packages (package_name, destination, duration_days, price, max_capacity, description, inclusions) VALUES
('Safari Adventure', 'Serengeti National Park', 5, 1500.00, 20, 'Experience the wild beauty of Serengeti with game drives and camping.', 'Game drives, camping, meals, park fees'),
('Beach Holiday', 'Zanzibar', 7, 2000.00, 30, 'Relax on the pristine beaches of Zanzibar with water sports.', 'Hotel, meals, water sports, snorkeling'),
('Mountain Trek', 'Mount Kilimanjaro', 8, 2500.00, 15, 'Conquer the highest peak in Africa with expert guides.', 'Guide, permits, camping equipment, meals'),
('Cultural Tour', 'Arusha', 4, 800.00, 25, 'Explore local cultures, markets, and traditional villages.', 'Transport, guide, meals, cultural fees'),
('Lake Excursion', 'Lake Victoria', 3, 600.00, 30, 'Boat rides, fishing, and bird watching at Lake Victoria.', 'Boat ride, meals, guide, fishing equipment');
