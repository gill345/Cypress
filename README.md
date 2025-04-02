<h1>Project Cypress</h1>
Link to site: <a href="https://cypress.great-site.net/">Cypress</a> 

Cypress is a community-driven platform for reporting and tracking public issues on a Toronto map. Users can create alerts for problems like potholes or broken streetlights, while city workers can update and resolve them in real time. 

## Features
- User Authentication: Register, log in, and change passwords securely. Passwords and Security Question Answers are hashed for secure storage. A Captcha prevents bots from signing up.
- Report a Problem To Admins: Users can click on a place on the map, choose the problem type, and add a description. 
- Admin Report Verification: Admins can review, update status, and delete reports from a unique dashboard.
- Filter out Duplicate Reports: System analyzes distance and report types to determine duplicate reports. Admins are able to remove duplicate reports and accept a single report with the click of a button.
- View Reports: Users can view all in progress and recently resolved reports on an interactive map. Furthermore, users are able to filter out reports by type, status, urgency, or by time. Filters set are saved up to a month using a cookie.
- Subscribe to Reports: Users can subscribe to reports on the map or upon submission and receive emails on updates to problem status.
- Responsive Design: Works on various devices, ie. Phone, Laptop, Desktop, etc. 

## Technologies Used
- **Frontend:** HTML, CSS, Bootstrap, JavaScript
- **Backend:** PHP
- **Database:** MySQL

## Setup Instructions
1. Clone the repository: `git clone https://github.com/gill345/cypress.git`
2. Set up a MySQL database and import the provided SQL schema.
3. Configure the database connection in `db.php`.
4. Ensure you have PHP and a web server (like XAMPP, or InfinityFree) installed.
5. Get a Google Map API Key for map searching functionality.
6. For Mail Functionality setup SMTP variables in required files: `index.php`, `report.php`, `admin.php`
7. Start the server and access the application via the browser.

## Database Structure
- **Users:** Stores user information (`id`, `name`, `email`, `password`, `role`, `created_at`, `security_question`, `security_answer`).
- **City_Reports:** Stores user reports (`id`, `user_id`, `description`, `report_type`, `latitude`, `longitude`, `contact_info`, `created_at`, `status`, `urgency`, `notify_updates`, `emergency_contacted`, `city_service_contacted`).


## Future Enhancements
- Improved Duplicate Filter Schema
- Cleaner UI
