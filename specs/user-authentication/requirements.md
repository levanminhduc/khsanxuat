# User Authentication - Requirements

## Feature Overview

Session-based user authentication system for the production evaluation system (khsanxuat). Provides login, registration, password management, and session-based access control to protected resources.

**Status**: Implemented and synced from existing codebase

**Implementation Files**:
- `login.php`, `login_action.php` - Login interface and processing
- `register.php`, `register_action.php` - Registration interface and processing
- `account/forgot_password.php`, `account/forgot_password_action.php` - Password recovery
- `account/change_password.php`, `account/change_password_action.php` - Password change for authenticated users
- `theodoi.php:13-18` - Session check and redirect logic (example implementation)

---

## User Stories

### User Story 1 - User Login (Priority: P1) 🎯 MVP

As a system user, I want to log in with my username and password so that I can access protected features of the production evaluation system.

**Independent Test**: Access login.php, enter valid credentials, verify redirect to index.php or saved redirect_url

**Inferred from**: `login_action.php:1-52`, `theodoi.php:13-18`

**Acceptance Criteria**:

- [x] WHEN the user accesses a protected page without authentication, THE SYSTEM SHALL redirect to login.php
- [x] WHEN the user accesses a protected page without authentication, THE SYSTEM SHALL store the original URL in session as redirect_url
- [x] WHEN the user submits the login form with valid credentials, THE SYSTEM SHALL verify username and password against the user table
- [x] WHEN the login is successful, THE SYSTEM SHALL create session variables: id, name, full_name, username, user_id
- [x] WHEN the login is successful and redirect_url exists in session, THE SYSTEM SHALL redirect to the saved URL
- [x] WHEN the login is successful and no redirect_url exists, THE SYSTEM SHALL redirect to index.php
- [x] WHEN the login is successful, THE SYSTEM SHALL clear the redirect_url from session after use
- [x] WHEN the login is successful, THE SYSTEM SHALL log the event to error_log with user details
- [x] IF the username does not exist, THEN THE SYSTEM SHALL redirect to login.php with error message "Sai tên đăng nhập hoặc mật khẩu"
- [x] IF the password is incorrect, THEN THE SYSTEM SHALL redirect to login.php with error message "Sai tên đăng nhập hoặc mật khẩu"
- [x] IF the login fails, THEN THE SYSTEM SHALL log the failure reason to error_log
- [x] THE SYSTEM SHALL use prepared statements for database queries to prevent SQL injection
- [x] THE SYSTEM SHALL display error messages from URL parameter error_message
- [x] THE SYSTEM SHALL display success messages from URL parameter success_message

---

### User Story 2 - User Registration (Priority: P1) 🎯 MVP

As a new user, I want to register an account so that I can access the system.

**Independent Test**: Access register.php, fill form with unique username, verify account creation and redirect to login

**Inferred from**: `register_action.php:1-51`, `register.php:103-135`

**Acceptance Criteria**:

- [x] WHEN the user submits the registration form, THE SYSTEM SHALL validate that password matches confirm_password
- [x] IF the password confirmation does not match, THEN THE SYSTEM SHALL redirect to register.php with error "Mật khẩu xác nhận không khớp"
- [x] WHEN the user submits the registration form, THE SYSTEM SHALL check if the username already exists in the user table
- [x] IF the username already exists, THEN THE SYSTEM SHALL redirect to register.php with error "Tên đăng nhập đã tồn tại"
- [x] WHEN the username is unique and passwords match, THE SYSTEM SHALL insert a new record with name, password, full_name into the user table
- [x] WHEN the registration is successful, THE SYSTEM SHALL redirect to login.php with success message "Đăng ký thành công! Vui lòng đăng nhập"
- [x] IF the database insert fails, THEN THE SYSTEM SHALL redirect to register.php with error message including the database error
- [x] THE SYSTEM SHALL use prepared statements for all database operations
- [x] THE SYSTEM SHALL require username, full_name, password, and confirm_password fields in the registration form
- [x] THE SYSTEM SHALL display links to login.php and change_password.php on the registration page

---

### User Story 3 - Forgot Password Recovery (Priority: P2)

As a user who forgot their password, I want to reset my password using my username and full name so that I can regain access to my account.

**Independent Test**: Access account/forgot_password.php, enter username and full_name, set new password, verify login works

**Inferred from**: `account/forgot_password_action.php:1-61`, `account/forgot_password.php:103-135`

**Acceptance Criteria**:

- [x] WHEN the user submits the forgot password form, THE SYSTEM SHALL validate that new_password matches confirm_password
- [x] IF the password confirmation does not match, THEN THE SYSTEM SHALL redirect with error "Mật khẩu xác nhận không khớp"
- [x] WHEN the user submits the form, THE SYSTEM SHALL verify that the username exists in the user table
- [x] IF the username does not exist, THEN THE SYSTEM SHALL redirect with error "Tên đăng nhập không tồn tại"
- [x] WHEN the username exists, THE SYSTEM SHALL verify that full_name matches the database record
- [x] IF the full_name does not match, THEN THE SYSTEM SHALL redirect with error "Họ tên đầy đủ không khớp với tài khoản"
- [x] WHEN both username and full_name match, THE SYSTEM SHALL update the password in the user table
- [x] WHEN the password reset is successful, THE SYSTEM SHALL redirect to login.php with success message "Mật khẩu đã được đặt lại thành công! Vui lòng đăng nhập"
- [x] IF the database update fails, THEN THE SYSTEM SHALL redirect with error message including the database error
- [x] THE SYSTEM SHALL use prepared statements for database operations
- [x] THE SYSTEM SHALL display links to login.php and register.php on the forgot password page

---

### User Story 4 - Change Password (Priority: P2)

As a user, I want to change my password by verifying my current password so that I can update my credentials securely.

**Independent Test**: Access account/change_password.php, enter username, current password, and new password, verify update

**Inferred from**: `account/change_password_action.php:1-61`, `account/change_password.php:103-135`

**Acceptance Criteria**:

- [x] WHEN the user submits the change password form, THE SYSTEM SHALL validate that new_password matches confirm_password
- [x] IF the password confirmation does not match, THEN THE SYSTEM SHALL redirect with error "Mật khẩu xác nhận không khớp"
- [x] WHEN the user submits the form, THE SYSTEM SHALL verify that the username exists in the user table
- [x] IF the username does not exist, THEN THE SYSTEM SHALL redirect with error "Tên đăng nhập không tồn tại"
- [x] WHEN the username exists, THE SYSTEM SHALL verify that current_password matches the database password
- [x] IF the current password is incorrect, THEN THE SYSTEM SHALL redirect with error "Mật khẩu hiện tại không đúng"
- [x] WHEN the current password matches, THE SYSTEM SHALL update the password in the user table with the new password
- [x] WHEN the password change is successful, THE SYSTEM SHALL redirect to login.php with success message "Mật khẩu đã được thay đổi thành công! Vui lòng đăng nhập lại"
- [x] IF the database update fails, THEN THE SYSTEM SHALL redirect with error message including the database error
- [x] THE SYSTEM SHALL use prepared statements for database operations
- [x] THE SYSTEM SHALL display links to login.php and forgot_password.php on the change password page

---

### User Story 5 - Session Management (Priority: P1) 🎯 MVP

As the system, I need to manage user sessions to maintain authenticated state across requests and protect resources from unauthorized access.

**Independent Test**: Log in, verify session variables exist, access protected page, verify session check works

**Inferred from**: `login_action.php:2, 20-24`, `theodoi.php:8-18`, session usage across system

**Acceptance Criteria**:

- [x] WHEN a user successfully logs in, THE SYSTEM SHALL initialize a PHP session using session_start()
- [x] WHEN a user successfully logs in, THE SYSTEM SHALL store the following session variables: id, name, full_name, username, user_id
- [x] WHEN checking authentication on protected pages, THE SYSTEM SHALL verify that $_SESSION['user_id'] is set
- [x] IF $_SESSION['user_id'] is not set on a protected page, THEN THE SYSTEM SHALL save current URL to $_SESSION['redirect_url']
- [x] IF $_SESSION['user_id'] is not set on a protected page, THEN THE SYSTEM SHALL redirect to login.php
- [x] WHEN a user accesses a protected page while authenticated, THE SYSTEM SHALL allow access without redirect
- [x] THE SYSTEM SHALL use session_status() == PHP_SESSION_NONE to check if session is already started before calling session_start()

---

## Database Schema

**Table**: `user`

Referenced in: `login_action.php:9`, `register_action.php:34`, `account/forgot_password_action.php:18`, `account/change_password_action.php:18`

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT | PRIMARY KEY, AUTO_INCREMENT | User ID |
| name | VARCHAR | UNIQUE, NOT NULL | Username (login credential) |
| password | VARCHAR | NOT NULL | User password |
| full_name | VARCHAR | NOT NULL | Full display name |

**Indexes**: Primary key on `id`, unique constraint on `name` (inferred from uniqueness check)

---

## Security Notes

### 🔴 CRITICAL SECURITY ISSUES

1. **Plain Text Passwords**: Passwords are stored in plain text in the database without hashing
   - **Location**: `register_action.php:36`, `account/forgot_password_action.php:40`, `account/change_password_action.php:40`
   - **Risk**: If database is compromised, all user passwords are exposed
   - **Recommendation**: Implement password hashing using `password_hash()` and `password_verify()`

2. **Plain Text Password Comparison**: Password verification uses string equality instead of secure comparison
   - **Location**: `login_action.php:18`, `account/change_password_action.php:29`
   - **Risk**: Vulnerable to timing attacks
   - **Recommendation**: Use `password_verify()` with hashed passwords

### ✅ Security Features Currently Implemented

1. **SQL Injection Protection**: Prepared statements used for all database queries
   - See: `login_action.php:10-13`, `register_action.php:19-22`, etc.

2. **Error Message Obfuscation**: Login errors don't distinguish between invalid username and invalid password
   - See: `login_action.php:41, 47` - both show "Sai tên đăng nhập hoặc mật khẩu"

3. **XSS Protection**: Error/success messages use `htmlspecialchars()` for output
   - See: `login.php:110`, `register.php:110`

4. **Session-Based Authentication**: Server-side session management for access control
   - See: `theodoi.php:8-18`

5. **Redirect URL Validation**: Stored redirect URLs are from internal navigation only (set by system, not user input)
   - See: `theodoi.php:15`, `login_action.php:30-33`

---

## Error Handling

**Error Display Pattern**:
- Errors passed via URL query parameter `error_message`
- Displayed in red `.error-message` div
- URL-encoded for safe transmission
- HTML-escaped for safe display

**Success Display Pattern**:
- Success messages passed via URL query parameter `success_message`
- Displayed in green `.success-message` div
- HTML-escaped for safe display

**Database Error Handling**:
- Database errors included in user-facing error messages (may expose technical details)
- See: `register_action.php:49`, `account/forgot_password_action.php:53`

---

## UI/UX Patterns

**Form Layout**: Consistent across all authentication pages
- Logo at top (300px width)
- Centered white container on teal background
- Blue primary button color (`rgb(42, 7, 240)`)
- Required text inputs with labels
- Links to related pages at bottom

**Navigation Links**:
- Login page → Register, Forgot Password, Change Password
- Register page → Login, Change Password
- Forgot Password → Login, Register
- Change Password → Login, Forgot Password

**Files**: See `login.php:1-132`, `register.php:1-137`, `account/forgot_password.php:1-137`, `account/change_password.php:1-137`

---

## Implementation Notes

**Status**: Synced from existing implementation on Thu Jan 29 2026

**Assumptions Made**:

| Decision | Chosen | Reasoning |
|----------|--------|-----------|
| Spec Level | Level 1 (requirements.md only) | Simple authentication flow, no complex architecture |
| Story Priority - Login/Session | P1 (MVP) | Core functionality required for system access |
| Story Priority - Registration | P1 (MVP) | Users need ability to create accounts |
| Story Priority - Password Reset | P2 | Enhancement feature, not critical for initial access |
| Story Priority - Change Password | P2 | Enhancement feature, users can use forgot password as alternative |
| User Table Structure | Minimal schema with id, name, password, full_name | Inferred from SQL queries across all auth files |
| Session Variable Names | id, name, full_name, username, user_id | Directly extracted from `login_action.php:20-24` |
| Password Recovery Method | Username + Full Name verification | Inferred from `account/forgot_password_action.php:18-29` |

**Constitution Compliance**: No `.constitution.md` file found - skipped compliance check

**Ready for**: Review and security hardening (password hashing implementation recommended)
