# Changelog

All notable changes to this project are documented here.
Format based on [Keep a Changelog](https://keepachangelog.com/) and [Semantic Versioning](https://semver.org/).

## [2.2.0] - 2026-11-20
### Added
- Audit logging for officer/supervisor actions, with CSV export (`supervisor/audit_logs.php`, `audit_logs_export.php`).
- Ticket categories management (`supervisor/categories.php`).
- Email notifications via `includes/mailer.php`.
- Public feedback submission (`submit_feedback.php`).
- Ticket attachment downloads (`download_attachment.php`).
- Ability to reopen a closed ticket (`reopen_ticket.php`).
- Database migrations for audit logs, performance, and the 2.2.0 schema changes.

## [2.1.0] - 2026-11-16
### Added
- Officer profile editing, status toggling and password reset from the supervisor portal.
- Password visibility toggle on all password fields.
- Chart.js analytics dashboard and formula-injection-safe CSV export.
- Brute-force login protection (rate limiting and temporary lockout).
- `.env`-based configuration with automatic base URL detection.

## [1.0.0]
- Initial public release of the ticketing workflow (public form, tracking, officer and supervisor portals).