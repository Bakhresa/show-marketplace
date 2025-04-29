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

