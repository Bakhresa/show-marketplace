Show Marketplace
Overview
The Show Marketplace is a web-based platform designed to connect event organizers and users. Admins can manage show listings (e.g., concerts, theater performances), while users can browse available shows, book tickets, and make payments via M-Pesa. The system supports real-time updates, ensuring that changes made by admins are reflected on the user dashboard within seconds.
Features

Admin Dashboard: Add, edit, and delete shows with details like title, genre, date, venue, price, and images.
User Dashboard: Browse shows with images, book tickets, manage bookings, and communicate with admins via chat.
Real-Time Updates: Changes made by admins (e.g., adding or editing shows) are reflected on the user dashboard within 10 seconds using polling.
Payment Integration: Secure payments via M-Pesa STK Push (sandbox mode).
Responsive Design: Built with Tailwind CSS for a mobile-friendly experience.
Chat System: Users can message admins, with real-time updates and unread message notifications.

Technologies Used

Backend: PHP, MySQL (PDO for database interactions)
Frontend: HTML, Tailwind CSS, JavaScript (AJAX for real-time updates)
Server: XAMPP (Apache, MySQL)
Payment: M-Pesa API (sandbox environment)

Installation
Prerequisites

XAMPP (or any LAMP/WAMP stack) installed
PHP 7.4 or higher
MySQL
Ngrok (for M-Pesa callback URL testing)
M-Pesa sandbox credentials (Consumer Key, Consumer Secret, PassKey)

Steps

Clone the Repository
git clone https://github.com/yourusername/show-marketplace.git
cd show-marketplace


Set Up the Database

Import the database schema:
Open phpMyAdmin (http://localhost:127.0.0.1/phpmyadmin).

Create a database named market.

Import the database.sql file (if provided) or run the following SQL to create the necessary tables:
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(12),
    password VARCHAR(255) NOT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE shows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    genre VARCHAR(100),
    date DATE NOT NULL,
    time TIME NOT NULL,
    venue VARCHAR(255) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    description TEXT,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    show_id INT,
    tickets INT NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'paid') DEFAULT 'pending',
    show_title VARCHAR(255),
    show_date DATE,
    show_venue VARCHAR(255),
    phone_number VARCHAR(12),
    checkout_request_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (show_id) REFERENCES shows(id) ON DELETE SET NULL
);

CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    sender_type ENUM('user', 'admin') NOT NULL,
    receiver_id INT NOT NULL,
    receiver_type ENUM('user', 'admin') NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);






Configure the Application

Copy the project files to your XAMPP htdocs directory (e.g., C:\xampp3\htdocs\market).
Update the database configuration in includes/config.php:$host = 'localhost';
$db = 'market';
$user = 'root';
$pass = ''; // Default XAMPP password




Set Up M-Pesa Credentials

Obtain sandbox credentials from the Safaricom Developer Portal.
Update user_dashboard.php with your credentials:$consumer_key = 'your_consumer_key';
$consumer_secret = 'your_consumer_secret';
$passkey = 'your_passkey';
$business_short_code = '174379'; // Sandbox shortcode
$callback_url = 'https://your-ngrok-url/market/callback.php';


Run Ngrok to expose your local server for the callback URL:ngrok http 127.0.0.1


Update the $callback_url with the Ngrok URL.


Set Up File Uploads

Create an uploads directory in the project root (e.g., C:\xampp3\htdocs\market\uploads).
Ensure the directory is writable by the server (e.g., chmod 777 uploads on Linux).


Start the Server

Start XAMPP (Apache and MySQL).
Access the application at http://localhost:127.0.0.1/market.



Usage

Register/Login

Register as a user or admin via the registration page.
Log in to access the respective dashboard.


Admin Actions

Add Show: Upload an image and enter show details.
Edit/Delete Show: Modify or remove existing shows.
Chat: Respond to user messages.


User Actions

Browse Shows: View available shows with images and details.
Book Tickets: Select a show, specify the number of tickets, and book.
Pay with M-Pesa: Enter your phone number to receive an STK Push for payment.
Manage Bookings: View pending and paid bookings, or delete them.
Chat with Admin: Send messages to the admin and receive real-time replies.



Project Structure

admin_dashboard.php: Admin interface for managing shows.
user_dashboard.php: User interface for browsing and booking shows.
api/: API endpoints for real-time updates (shows.php, notifications.php).
includes/: Configuration and shared files (config.php, header.php, footer.php).
uploads/: Directory for storing show images.
callback.php: M-Pesa callback handler (needs implementation).

Contributing

Fork the repository.
Create a feature branch (git checkout -b feature/your-feature).
Commit your changes (git commit -m "Add your feature").
Push to the branch (git push origin feature/your-feature).
Open a pull request.

License
This project is licensed under the MIT License - see the LICENSE file for details.
Acknowledgements

Built with Tailwind CSS.
Payment integration using Safaricom's M-Pesa API (sandbox).
Real-time updates inspired by AJAX polling techniques.

