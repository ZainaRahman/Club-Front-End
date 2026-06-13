# K-MiNDS Club Portfolio

**K-MiNDS** (Machine Intelligence and Data Science) is a student club at KUET, Khulna. This repository contains the full-stack web portfolio for the club — a dynamic website where members can register, log in, explore club activities, and view achievements and projects.

---

## Features

- **Public Landing Page** — Hero section, about, focus areas, live stats counters, activities, achievements, upcoming events, and team showcase
- **User Authentication** — Registration, login, logout, and password change via PHP sessions
- **Role-based Dashboards** — Separate views for regular members and admins
- **Achievement Pages** — Dedicated pages for competition wins and publications
- **Projects Page** — Showcase of club projects
- **Dark Mode** — Toggle between light and dark themes
- **Responsive Design** — Mobile-friendly layout with carousels and animated elements
- **Social Links** — Facebook and LinkedIn integration

---

## Tech Stack

| Layer      | Technology              |
|------------|-------------------------|
| Frontend   | HTML, CSS, JavaScript   |
| Backend    | PHP                     |
| Database   | MySQL (via `mysqli`)    |

---

## Project Structure

```
Club-Portfolio/
├── Landing_page.html / .php   # Public-facing homepage
├── Landing_page.css           # Styles for the landing page
├── Landing_page.js            # Animations, counters, and UI interactions
├── login.php                  # Login form (loaded in modal iframe)
├── signup.php                 # Registration handler
├── logout.php                 # Session destruction
├── registration.html          # Member registration form
├── registration.css           # Registration page styles
├── change_password.php        # Authenticated password change
├── member_dashboard.php       # Dashboard for regular members
├── member_dashboard.css       # Member dashboard styles
├── member_dashboard.js        # Member dashboard scripts
├── admin_dashboard.php        # Admin panel (manage events, members, etc.)
├── competition-wins.php       # Competition achievements page
├── publications.php           # Research publications page
├── achievement-detail.css     # Styles for achievement detail pages
├── achievement-detail.js      # Scripts for achievement detail pages
├── projects.html              # Club projects showcase
├── projects.css               # Projects page styles
└── logo.png                   # Club logo
```

---

## Getting Started

### Prerequisites

- PHP 7.4+
- MySQL / MariaDB
- A local server such as [XAMPP](https://www.apachefriends.org/) or [WAMP](https://www.wampserver.com/)

### Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/ZainaRahman/Club-Portfolio.git
   ```

2. **Move files to your server's web root**
   (e.g., `htdocs/` for XAMPP or `www/` for WAMP)

3. **Create the database**
   - Open phpMyAdmin (or any MySQL client)
   - Create a database named `club_db`
   - The application will auto-create required tables on first run (e.g., `club_events`)

4. **Configure the database connection**
   - The PHP files connect using:
     ```php
     mysqli_connect("localhost", "root", "", "club_db");
     ```
   - Update the credentials if your MySQL setup differs

5. **Run the app**
   - Start Apache and MySQL in your local server
   - Visit `http://localhost/Club-Portfolio/Landing_page.php`

---

## Usage

- **Visitors** can browse the landing page, view focus areas, achievements, and projects without logging in
- **New users** can sign up via the registration form
- **Members** are redirected to their member dashboard after login
- **Admins** are redirected to the admin panel where they can manage events and content

---

## Club Info

- **Club:** K-MiNDS — Machine Intelligence and Data Science Club
- **University:** KUET, Khulna
- **Facebook:** [K-MiNDS Facebook](https://www.facebook.com/profile.php?id=61578154817750)
- **LinkedIn:** [K-MiNDS LinkedIn](https://www.linkedin.com/company/kuet-minds-machine-intelligence-and-data-science/)

---

## Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you'd like to change.

---

## License

This project is open source and available under the [MIT License](LICENSE).
