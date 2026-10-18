# SmartSave

Open http://localhost/IM2_MIDTERM/ with Apache and MySQL running in XAMPP.
`index.php` is the login page and the site's default entry point.
Create an account on Sign Up, or log in with an existing account's email and password.
Each account sees only its own records; existing records remain attached to their original user.

## Pages

- Dashboard: balance, current-month income and expenses, six-month totals, recent activity, and savings progress.
- Income: add, search, edit, and delete income.
- Expense: the existing design and controls, with authentication, account ownership, and protected form submissions.
- Saving Goals: add, search, edit, delete, and contribute to a goal. Status changes to Completed at the target; excess contributions are rejected.
- My Account: edit personal details, change a password, and log out.

Goal contributions track savings progress. They do not automatically create an expense or transfer money.

## PHP Sessions

`includes/bootstrap.php` starts the PHP session with HttpOnly and SameSite cookies.
`login_user()` rotates the session ID and stores the authenticated account in `$_SESSION['user_id']`.
`require_login()` protects all finance pages and the expense backend.
Forms use a session CSRF token. `logout.php` clears the session and its cookie.
Passwords are stored using `password_hash()` and checked using `password_verify()`.

## Database Transactions

`transaction()` in `includes/bootstrap.php` calls PDO `beginTransaction()`, runs the database work,
then calls `commit()`. Any exception triggers `rollBack()`.
Signup, profile/password changes, and all income, expense, and goal writes use this helper.
Goal contributions also use `SELECT ... FOR UPDATE` to serialize simultaneous changes to the same goal.
The existing InnoDB tables support these transactions.

`database/setup.php` adds an email uniqueness constraint without changing existing account data.
It is idempotent and has already been run on this database. On another installation, run:

```powershell
& 'C:\xampp\php\php.exe' database\setup.php
```

## Verification

The checks create temporary QA accounts and clean up only their own records.

```powershell
& 'C:\xampp\php\php.exe' tests\transactions.php
node tests/http.mjs
```

`SMARTSAVE_URL` and `SMARTSAVE_PHP` can override the local URL and PHP executable for the HTTP checks.

Browser checks use the installed Chrome browser and Playwright:

```powershell
npm.cmd --prefix tests/browser ci --ignore-scripts
node tests/browser/verify.mjs
```

They verify the forms and navigation and save desktop/mobile screenshots in `tests/browser/screenshots/`.
The existing Bootstrap, icon, and Google Font CDN assets require internet access.
