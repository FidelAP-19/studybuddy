# StudyBuddy: Peer Tutoring Request Platform

StudyBuddy is a PHP and MySQL web platform where students ask classmates for help with a course.
A student fills out a form, the data is saved to a MySQL database, and the request appears on a
Request Board. Tutors log in to see contact details and update each request's status.

Built for CIS 344, using the PHP examples in *Fundamentals of Database Systems*, 7th ed.
(Elmasri & Navathe), Chapter 11, as a guide.

## Requirements

- MAMP or XAMPP (or PHP 8.1+ with MySQL 8)
- A web browser

## Setup

1. Copy the `studybuddy-php` folder into your web folder:
   - MAMP: `/Applications/MAMP/htdocs/`
   - XAMPP: `C:\xampp\htdocs\`
2. Start Apache and MySQL.
3. Open phpMyAdmin, click **Import**, choose `database/schema.mysql.sql`, and click **Go**.
   This creates the `studybuddy` database, its tables and a list of courses.
4. Copy `config.local.sample.php` to a new file named `config.local.php`.
   Set the MySQL port, user and password to match your setup:
   - MAMP: user `root`, password `root`, port `8889` (or `3306` if you changed it)
   - XAMPP: user `root`, password empty, port `3306`
5. Open the site at `http://localhost/studybuddy-php/` (MAMP with its default ports: `http://localhost:8888/studybuddy-php/`).

The footer of each page says **"connected to MySQL"** when the connection works.

## How to use it

- **Ask for Help:** fill out the form and click **Save my request**.
- **Request Board:** see all requests. You can search, filter by course, status and urgency, and sort them.
- **Stats:** see the number of requests per course and the most common free days.
- **Tutor Login:** the password is `tutor123`. Tutors can see student e-mails, mark requests
  Matched or Closed, and export the list as a CSV file.

## The form and the database

The database has three tables:

- `COURSE`: the list of courses
- `STUDENT`: one row per student, identified by e-mail
- `TUTOR_REQUEST`: one row per form submission, linked to a student and a course

| Form field | Saved to |
|---|---|
| First name, last name, e-mail, major, year | `STUDENT` |
| Course, topic, description, urgency, meeting mode, days free | `TUTOR_REQUEST` |

When the form is submitted, `index.php`:

1. Checks every field on the server and shows an error message under any field that is wrong.
2. Looks up the student by e-mail. A returning student is updated, and a new student is inserted.
3. Inserts the request into `TUTOR_REQUEST`.
4. Shows a confirmation page with the data that was saved.

Steps 2 and 3 run in one transaction, so a submission is either saved completely or not at all.

To see the saved data, open `studybuddy` → `TUTOR_REQUEST` → **Browse** in phpMyAdmin.

## Security

- All queries use prepared statements, which prevents SQL injection.
- All output is escaped, which prevents cross-site scripting.
- The forms use CSRF tokens.
- The tutor password is stored as a hash.
- Database passwords are kept in `config.local.php`, which is not uploaded to GitHub.

## Unique features

- Returning students are recognized by e-mail, so no duplicate records are created
- Request Board with search, filters, sorting and pages
- Requests are color-coded by urgency
- Tutor login with a status workflow (Open, Matched, Closed) and CSV export
- Stats page built on a SQL view (`COURSE_DEMAND`)
- The public board shows only first name and last initial
- Spam protection: a hidden honeypot field and a short wait between submissions

## Files

| File | Purpose |
|---|---|
| `index.php` | The form; validates and saves the data |
| `requests.php` | Request Board |
| `request.php` | One request in full; tutors change its status here |
| `stats.php` | Statistics |
| `login.php`, `logout.php` | Tutor login |
| `export.php` | CSV download |
| `includes/db.php` | Database connection |
| `database/schema.mysql.sql` | Creates the database and tables |
