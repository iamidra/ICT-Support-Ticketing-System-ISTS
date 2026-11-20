# Changelog

All notable changes to this project are documented here.
Format based on [Keep a Changelog](https://keepachangelog.com/) and [Semantic Versioning](https://semver.org/).

## [2.2.0] - 2026-11-21
### Added
- **Server-Side Pagination & Filtering**: Implemented scalable `LIMIT`/`OFFSET` pagination (`render_pagination()`) with server-side status/priority/search filtering for supervisor and officer dashboards, supporting hundreds of thousands of tickets without memory bloat.
- **Aggregated SQL Metrics**: Converted PHP `foreach` dashboard KPI calculations into instant, index-backed aggregate queries (`SUM(CASE ...)`).
- **Schema Auto-Sync Gating**: Eliminated repetitive `SHOW COLUMNS` DDL metadata queries on every web request using a file-based sync marker (`sys_cache/.schema_synced`).
- **High-Performance Composite Database Indexes**: Added `(assigned_officer_id, status)`, `(status, created_at)`, `(priority, status)`, `(ticket_id, is_internal)`, and `(ticket_id, resolution_id)` indexes to optimize critical queue queries.
- **Memory-Capped CSV Streaming**: Converted report export to a row-by-row streaming cursor with `session_write_close()` to eliminate memory exhaustion and session locking during large downloads.
- **HTTP Caching & Compression**: Enabled 30-day browser caching (`mod_expires`) and Gzip/Deflate compression (`mod_deflate`) for CSS, JS, SVG, and images in `.htaccess`.
- **Automatic Rate Limit Garbage Collection**: Added probabilistic pruning to purge expired rate limit entries from the database.
- **Cryptographic Unguessable Ticket Generator**: Uses CSPRNG Crockford Base-32 codes (`ISTS-8X7D-2041`, over 1.09 trillion combinations) to ensure ticket privacy and eliminate ID enumeration and scraping.
- **Simplified Public Tracking (`track_status.php`)**: Cleaned tracking portal to a streamlined, single-field code lookup.
- **File Attachments & Screenshot Uploads**: Requesters can upload files/screenshots during ticket submission, and technicians/supervisors can attach supporting documents or diagnostics during resolution notes.
- **Secure File Download Handler (`download_attachment.php`)**: Validates MIME types, prevents directory traversal, and restricts internal attachments to authenticated staff.
- **Internal Staff Notes vs. Public Notes**: Technicians and supervisors can flag notes as internal-only, hiding sensitive technical handovers or credentials from public requesters.
- **SLA Target Due Dates**: Calculates priority-based response deadlines (High: 4h, Medium: 24h, Low: 72h) with real-time status badges (On Track, Due Soon, Overdue).
- **Customer Satisfaction (CSAT) Feedback**: Public requesters can rate resolved tickets (1–5 stars) and submit qualitative feedback.
- **Ticket Reopen Capability**: Requesters can reopen resolved tickets if the problem recurs, notifying the assigned technician.
- **Activity & Audit Trail**: Full timeline logging of ticket creation, status transitions, reassignments, notes, reopens, and ratings.
- **Dynamic Category Management**: Supervisor portal interface (`supervisor/categories.php`) for full CRUD management of support categories with live ticket counts.
- **Email Notification Service (`includes/mailer.php`)**: Transactional notifications for ticket creation, technician assignment, updates, and resolutions.
- **Enhanced Supervisor Analytics**: CSAT satisfaction averages and SLA compliance metrics added to the supervisor reports and CSV export.
- **Hardened Session Security**: Reduced session inactivity lifetime to 15 minutes (`SESSION_LIFETIME=150`) and implemented session ID regeneration on login, password reset, and logout.

## [2.1.0] - 2026-11-16
### Added
- Officer profile editing, status toggling and password reset from the supervisor portal.
- Password visibility toggle on all password fields.
- Chart.js analytics dashboard and formula-injection-safe CSV export.
- Brute-force login protection (rate limiting and temporary lockout).
- `.env`-based configuration with automatic base URL detection.

## [2.0.0]
- Initial public release of the ticketing workflow (public form, tracking, officer and supervisor portals).
