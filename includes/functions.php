<?php
// Central Helper Functions Library
// ICT Support Ticketing Solution (ISTS)

require_once __DIR__ . '/../config/config.php';

/**
 * Escape HTML special characters cleanly.
 */
function e($string) {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate asset URL.
 */
function asset_url($path) {
    return BASE_URL . '/assets/' . ltrim($path, '/');
}

/**
 * Generate site internal URL.
 */
function url($path) {
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * Render pure SVG Icon by identifier. Replaces all emojis across the system.
 */
function icon($name, $classes = '', $size = 18) {
    $classAttr = !empty($classes) ? ' class="' . e($classes) . '"' : '';
    $sz = (int)$size;
    $style = 'width:' . $sz . 'px; height:' . $sz . 'px; vertical-align:middle; display:inline-block; flex-shrink:0;';

    $paths = [
        'ticket' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>',
        'document' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
        'check' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>',
        'check_circle' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'clock' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'cog' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
        'lock' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>',
        'chart' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
        'users' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>',
        'user' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
        'plus' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>',
        'search' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>',
        'copy' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>',
        'print' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>',
        'arrow_right' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>',
        'arrow_left' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>',
        'alert' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>',
        'trash' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>',
        'mail' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
        'phone' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>',
        'building' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
        'key' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>',
        'logout' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>',
        'folder_empty' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z"/>',
        'external' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>',
        'shield' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>',
        'headset' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>',
        'dashboard' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>',
        'sparkles' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>',
        'filter' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>',
        'edit' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>',
        'eye' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>',
        'eye_off' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>'
    ];

    $path = $paths[$name] ?? $paths['ticket'];
    return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"' . $classAttr . ' style="' . $style . '">' . $path . '</svg>';
}

/**
 * Generate CSRF Token for forms.
 */
function generate_csrf_token() {
    if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output hidden input field for CSRF.
 */
function csrf_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

/**
 * Render password input field with visibility toggle eye icon.
 */
function password_input($name, $id = null, $placeholder = '••••••••', $required = true, $minlength = null, $extraAttrs = '') {
    $id = $id ?: $name;
    $req = $required ? ' required' : '';
    $min = $minlength ? ' minlength="' . (int)$minlength . '"' : '';
    return '<div class="password-wrapper">' .
           '<input type="password" id="' . e($id) . '" name="' . e($name) . '" class="form-control" placeholder="' . e($placeholder) . '"' . $req . $min . ' ' . $extraAttrs . '>' .
           '<button type="button" class="password-toggle-btn" tabindex="-1" aria-label="Toggle password visibility">' .
           icon('eye', 'eye-open-icon', 18) .
           '<span class="eye-closed-icon" style="display:none;">' . icon('eye_off', '', 18) . '</span>' .
           '</button>' .
           '</div>';
}

/**
 * Verify submitted CSRF Token.
 */
function verify_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], (string)$token);
}

/**
 * Set a session flash message.
 * Types: 'success', 'danger', 'warning', 'info'
 */
function set_flash($type, $message) {
    if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
        session_start();
    }
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear session flash message.
 */
function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Render Flash Alert Banner HTML.
 */
function render_flash() {
    $flash = get_flash();
    if (!$flash) return '';

    $typeMap = [
        'error'   => 'danger',
        'danger'  => 'danger',
        'success' => 'success',
        'warning' => 'warning',
        'info'    => 'info'
    ];
    $alertClass = $typeMap[$flash['type']] ?? 'info';
    $iconMap = [
        'danger'  => icon('alert', 'alert-icon', 20),
        'success' => icon('check_circle', 'alert-icon', 20),
        'warning' => icon('alert', 'alert-icon', 20),
        'info'    => icon('headset', 'alert-icon', 20)
    ];
    $iconHtml = $iconMap[$alertClass] ?? $iconMap['info'];

    return '<div class="alert alert-' . e($alertClass) . ' alert-dismissible" role="alert">' .
           '<div style="display:flex; align-items:center; gap:0.65rem;">' .
           $iconHtml .
           '<span>' . e($flash['message']) . '</span>' .
           '</div>' .
           '<button type="button" class="alert-close" onclick="this.parentElement.remove()" aria-label="Close alert">&times;</button>' .
           '</div>';
}

/**
 * Check and enforce session inactivity expiration (default: 60 mins).
 */
function check_session_inactivity($timeoutSeconds = 3600) {
    if (isset($_SESSION['officer_id'])) {
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeoutSeconds)) {
            session_unset();
            session_destroy();
            if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
                session_start();
            }
            set_flash('warning', 'Your session expired due to inactivity. Please sign in again.');
            header('Location: ' . url('login.php'));
            exit;
        }
        $_SESSION['last_activity'] = time();
    }
}

/**
 * Check if current visitor is authenticated.
 */
function is_logged_in() {
    return isset($_SESSION['officer_id']) && !empty($_SESSION['officer_id']);
}

/**
 * Check if current user has supervisor role.
 */
function is_supervisor() {
    return is_logged_in() && (($_SESSION['officer_role'] ?? '') === 'supervisor');
}

/**
 * Check if current user has officer role.
 */
function is_officer() {
    return is_logged_in() && (($_SESSION['officer_role'] ?? '') === 'officer');
}

/**
 * Require Officer Login Authentication.
 */
function require_officer() {
    check_session_inactivity();
    if (!isset($_SESSION['officer_id']) || $_SESSION['officer_role'] !== 'officer') {
        set_flash('danger', 'Please sign in to access your Officer dashboard.');
        header('Location: ' . url('login.php'));
        exit;
    }
    // Prevent browser from caching authenticated pages
    if (!headers_sent()) {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
}

/**
 * Require Supervisor Login Authentication.
 */
function require_supervisor() {
    check_session_inactivity();
    if (!isset($_SESSION['officer_id']) || $_SESSION['officer_role'] !== 'supervisor') {
        set_flash('danger', 'Please sign in to access the Supervisor portal.');
        header('Location: ' . url('login.php'));
        exit;
    }
    // Prevent browser from caching authenticated pages
    if (!headers_sent()) {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
}

/**
 * Format ticket status pill badge with icon and subtle dot.
 */
function render_status_badge($status) {
    $statusClassMap = [
        'Pending'     => 'badge-pending',
        'In Progress' => 'badge-in-progress',
        'Resolved'    => 'badge-resolved',
        'Closed'      => 'badge-closed'
    ];
    $iconMap = [
        'Pending'     => icon('clock', 'badge-icon', 13),
        'In Progress' => icon('cog', 'badge-icon', 13),
        'Resolved'    => icon('check', 'badge-icon', 13),
        'Closed'      => icon('lock', 'badge-icon', 13)
    ];

    $class = $statusClassMap[$status] ?? 'badge-default';
    $ico   = $iconMap[$status] ?? '';

    return '<span class="badge ' . $class . '"><span class="badge-dot"></span>' . $ico . ' ' . e($status) . '</span>';
}

/**
 * Format ticket priority badge.
 */
function render_priority_badge($priority) {
    $p = strtolower($priority ?? 'medium');
    $classMap = [
        'low'    => 'badge-priority-low',
        'medium' => 'badge-priority-medium',
        'high'   => 'badge-priority-high'
    ];
    $class = $classMap[$p] ?? 'badge-priority-medium';
    return '<span class="badge ' . $class . '">' . ucfirst(e($priority)) . '</span>';
}

/**
 * Format account active/inactive badge.
 */
function render_account_status_badge($status) {
    $status = strtolower($status ?? 'active');
    if ($status === 'active') {
        return '<span class="badge badge-resolved"><span class="badge-dot"></span> Active</span>';
    }
    return '<span class="badge badge-priority-high"><span class="badge-dot"></span> Inactive</span>';
}

/**
 * Pretty date formatting.
 */
function format_date($datetime) {
    if (!$datetime) return 'N/A';
    return date('M d, Y h:i A', strtotime($datetime));
}

/**
 * Escape CSV fields to prevent CSV Formula Injection (CWE-1236).
 */
function escape_csv_field($value) {
    $str = (string)($value ?? '');
    if ($str !== '' && in_array($str[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
        return "'" . $str;
    }
    return $str;
}

/**
 * Rate Limiting Helpers for brute-force protection
 */
function get_client_ip() {
    // Validate that X-Forwarded-For IPs are real IPs before trusting them
    $xForwardedFor = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    if ($xForwardedFor !== '') {
        // Take the first IP in a comma-separated list and validate
        $firstIp = trim(explode(',', $xForwardedFor)[0]);
        if (filter_var($firstIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $firstIp;
        }
    }
    // Cloudflare connecting IP
    $cfIp = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '';
    if ($cfIp !== '' && filter_var($cfIp, FILTER_VALIDATE_IP)) {
        return $cfIp;
    }
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

function check_rate_limit($action, $maxAttempts = MAX_LOGIN_ATTEMPTS, $lockoutMinutes = LOGIN_LOCKOUT_MINUTES) {
    $ip = get_client_ip();
    $key = 'rate_limit_' . md5($action . '_' . $ip);

    if (isset($_SESSION[$key])) {
        $data = $_SESSION[$key];
        if (time() < $data['lockout_until']) {
            $remaining = ceil(($data['lockout_until'] - time()) / 60);
            return [
                'allowed' => false,
                'remaining_minutes' => $remaining
            ];
        }
        if (time() >= $data['lockout_until'] && $data['lockout_until'] > 0) {
            unset($_SESSION[$key]);
        }
    }

    return ['allowed' => true, 'remaining_minutes' => 0];
}

function record_failed_attempt($action, $maxAttempts = MAX_LOGIN_ATTEMPTS, $lockoutMinutes = LOGIN_LOCKOUT_MINUTES) {
    $ip = get_client_ip();
    $key = 'rate_limit_' . md5($action . '_' . $ip);

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'attempts' => 1,
            'lockout_until' => 0
        ];
    } else {
        $_SESSION[$key]['attempts']++;
    }

    if ($_SESSION[$key]['attempts'] >= $maxAttempts) {
        $_SESSION[$key]['lockout_until'] = time() + ($lockoutMinutes * 60);
    }
}

function clear_rate_limit($action) {
    $ip = get_client_ip();
    $key = 'rate_limit_' . md5($action . '_' . $ip);
    unset($_SESSION[$key]);
}
