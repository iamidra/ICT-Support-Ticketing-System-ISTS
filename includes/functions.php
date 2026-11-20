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
        'eye_off' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>',
        'paperclip' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>',
        'star' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>',
        'history' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'tag' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>'
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
 * Check and enforce session inactivity expiration (default: 15 mins).
 */
function check_session_inactivity($timeoutSeconds = null) {
    if ($timeoutSeconds === null) {
        $timeoutSeconds = defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 900;
    }
    if (isset($_SESSION['officer_id'])) {
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeoutSeconds)) {
            session_unset();
            session_destroy();
            if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
                session_start();
            }
            $mins = round($timeoutSeconds / 60);
            set_flash('warning', "Your session expired after {$mins} minutes of inactivity. Please sign in again.");
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
 * Secure Client IP resolution.
 * Only trusts X-Forwarded-For / CF-Connecting-IP if REMOTE_ADDR is in TRUSTED_PROXIES.
 */
function get_client_ip() {
    $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $trustedProxies = env('TRUSTED_PROXIES', '');

    if (!empty($trustedProxies)) {
        $trustedList = array_map('trim', explode(',', $trustedProxies));
        if (in_array($remoteAddr, $trustedList, true)) {
            // Inspect Cloudflare IP
            $cfIp = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '';
            if (!empty($cfIp) && filter_var($cfIp, FILTER_VALIDATE_IP)) {
                return $cfIp;
            }

            // Inspect X-Forwarded-For
            $xForwardedFor = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
            if (!empty($xForwardedFor)) {
                $ips = array_map('trim', explode(',', $xForwardedFor));
                foreach ($ips as $candidate) {
                    if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                        return $candidate;
                    }
                }
            }
        }
    }

    return $remoteAddr;
}

/**
 * Persistent Database-Backed Rate Limiting with Session Fallback
 */
function check_rate_limit($action, $identifier = null, $maxAttempts = MAX_LOGIN_ATTEMPTS, $lockoutMinutes = LOGIN_LOCKOUT_MINUTES) {
    global $pdo;
    $ip = get_client_ip();
    $ipKey = hash('sha256', $action . ':ip:' . $ip);
    $userKey = $identifier ? hash('sha256', $action . ':user:' . strtolower(trim($identifier))) : null;

    // 1. Check persistent database rate limits
    if ($pdo) {
        // High-performance GC: probabilistically purge expired records (1 in 50 calls)
        if (mt_rand(1, 50) === 1) {
            try {
                $pdo->exec("DELETE FROM rate_limits WHERE (lockout_until IS NOT NULL AND lockout_until < NOW()) OR (lockout_until IS NULL AND last_attempt < DATE_SUB(NOW(), INTERVAL 1 HOUR))");
            } catch (Throwable $e) {}
        }

        try {
            $keys = [$ipKey];
            if ($userKey) $keys[] = $userKey;
            $placeholders = implode(',', array_fill(0, count($keys), '?'));
            $stmt = $pdo->prepare(
                "SELECT lockout_until FROM rate_limits 
                 WHERE rate_key IN ($placeholders) AND lockout_until > NOW() 
                 ORDER BY lockout_until DESC LIMIT 1"
            );
            $stmt->execute($keys);
            $locked = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($locked && !empty($locked['lockout_until'])) {
                $rem = ceil((strtotime($locked['lockout_until']) - time()) / 60);
                return ['allowed' => false, 'remaining_minutes' => max(1, (int)$rem)];
            }
        } catch (Throwable $e) {
            error_log("DB Rate limit check error: " . $e->getMessage());
        }
    }

    // 2. Secondary session check
    $sessionKey = 'rate_limit_' . md5($action . '_' . $ip);
    if (isset($_SESSION[$sessionKey])) {
        $data = $_SESSION[$sessionKey];
        if (time() < $data['lockout_until']) {
            $remaining = ceil(($data['lockout_until'] - time()) / 60);
            return ['allowed' => false, 'remaining_minutes' => max(1, (int)$remaining)];
        }
        if (time() >= $data['lockout_until'] && $data['lockout_until'] > 0) {
            unset($_SESSION[$sessionKey]);
        }
    }

    return ['allowed' => true, 'remaining_minutes' => 0];
}

/**
 * Record a failed attempt in persistent database and session
 */
function record_failed_attempt($action, $identifier = null, $maxAttempts = MAX_LOGIN_ATTEMPTS, $lockoutMinutes = LOGIN_LOCKOUT_MINUTES) {
    global $pdo;
    $ip = get_client_ip();
    $ipKey = hash('sha256', $action . ':ip:' . $ip);
    $userKey = $identifier ? hash('sha256', $action . ':user:' . strtolower(trim($identifier))) : null;

    // 1. Persistent DB record
    if ($pdo) {
        try {
            $records = [
                ['key' => $ipKey, 'ident' => 'ip:' . $ip]
            ];
            if ($userKey && $identifier) {
                $records[] = ['key' => $userKey, 'ident' => 'user:' . strtolower(trim($identifier))];
            }

            foreach ($records as $r) {
                $stmt = $pdo->prepare(
                    "INSERT INTO rate_limits (rate_key, action, identifier, ip_address, attempts, lockout_until, last_attempt)
                     VALUES (?, ?, ?, ?, 1, NULL, NOW())
                     ON DUPLICATE KEY UPDATE
                         attempts = attempts + 1,
                         lockout_until = IF(attempts >= ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), lockout_until),
                         last_attempt = NOW()"
                );
                $stmt->execute([$r['key'], $action, $r['ident'], $ip, (int)$maxAttempts, (int)$lockoutMinutes]);
            }
        } catch (Throwable $e) {
            error_log("DB Record failed attempt error: " . $e->getMessage());
        }
    }

    // 2. Secondary session record
    $sessionKey = 'rate_limit_' . md5($action . '_' . $ip);
    if (!isset($_SESSION[$sessionKey])) {
        $_SESSION[$sessionKey] = ['attempts' => 1, 'lockout_until' => 0];
    } else {
        $_SESSION[$sessionKey]['attempts']++;
    }
    if ($_SESSION[$sessionKey]['attempts'] >= $maxAttempts) {
        $_SESSION[$sessionKey]['lockout_until'] = time() + ($lockoutMinutes * 60);
    }
}

/**
 * Clear rate limits upon successful authentication
 */
function clear_rate_limit($action, $identifier = null) {
    global $pdo;
    $ip = get_client_ip();
    $ipKey = hash('sha256', $action . ':ip:' . $ip);
    $userKey = $identifier ? hash('sha256', $action . ':user:' . strtolower(trim($identifier))) : null;

    if ($pdo) {
        try {
            $keys = [$ipKey];
            if ($userKey) $keys[] = $userKey;
            $placeholders = implode(',', array_fill(0, count($keys), '?'));
            $stmt = $pdo->prepare("DELETE FROM rate_limits WHERE rate_key IN ($placeholders)");
            $stmt->execute($keys);

            // Clean expired records older than 24 hours
            if (mt_rand(1, 10) === 1) {
                $pdo->exec("DELETE FROM rate_limits WHERE last_attempt < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
            }
        } catch (Throwable $e) {
            error_log("DB Clear rate limit error: " . $e->getMessage());
        }
    }

    $sessionKey = 'rate_limit_' . md5($action . '_' . $ip);
    unset($_SESSION[$sessionKey]);
}

/**
 * Calculate ticket due date based on SLA priority.
 * High: 4 hours, Medium: 24 hours, Low: 72 hours
 */
function calculate_due_date($priority, $fromTime = null) {
    $baseTime = $fromTime ? strtotime($fromTime) : time();
    $p = strtolower($priority ?? 'medium');
    $hours = 24;
    if ($p === 'high') {
        $hours = 4;
    } elseif ($p === 'low') {
        $hours = 72;
    }
    return date('Y-m-d H:i:s', $baseTime + ($hours * 3600));
}

/**
 * Generate a cryptographically unguessable, human-readable ticket code.
 * Format: ISTS-XXXX-XXXX (e.g. ISTS-8X7D-2041)
 * Uses Crockford-style base-32 alphabet excluding easily confusable characters (0, O, 1, I).
 * Ensures mathematical entropy of 32^8 (over 1.09 trillion combinations).
 * Checks database for uniqueness if PDO instance is provided.
 */
function generate_ticket_code(?PDO $pdo = null): string {
    // 32 characters: 2-9, A-Z except I and O
    $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    $alphaLen = strlen($alphabet); // 32

    $maxAttempts = 10;
    for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
        $part1 = '';
        $part2 = '';
        for ($i = 0; $i < 4; $i++) {
            $part1 .= $alphabet[random_int(0, $alphaLen - 1)];
            $part2 .= $alphabet[random_int(0, $alphaLen - 1)];
        }
        $code = 'ISTS-' . $part1 . '-' . $part2;

        if (!$pdo) {
            return $code;
        }

        try {
            $stmt = $pdo->prepare("SELECT 1 FROM tickets WHERE ticket_code = ? LIMIT 1");
            $stmt->execute([$code]);
            if (!$stmt->fetchColumn()) {
                return $code;
            }
        } catch (Throwable $e) {
            // If DB query fails, still return generated high-entropy code
            return $code;
        }
    }

    // High entropy fallback if loop reaches max
    return 'ISTS-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4)) . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
}

/**
 * Verify whether supplied contact string (email or phone) matches a ticket record.
 * Handles case-insensitive email matching and forgiving phone digit normalization.
 */
function verify_ticket_requester_contact(array $ticket, string $contact): bool {
    $contact = trim($contact);
    if ($contact === '') {
        return false;
    }

    // 1. Direct or case-insensitive email match
    if (!empty($ticket['email'])) {
        if (strcasecmp(trim($ticket['email']), $contact) === 0) {
            return true;
        }
    }

    // 2. Normalized phone comparison
    if (!empty($ticket['phone'])) {
        $ticketPhone = trim($ticket['phone']);
        if (strcasecmp($ticketPhone, $contact) === 0) {
            return true;
        }

        $ticketDigits = preg_replace('/\D+/', '', $ticketPhone);
        $contactDigits = preg_replace('/\D+/', '', $contact);

        if (!empty($ticketDigits) && !empty($contactDigits)) {
            if ($ticketDigits === $contactDigits) {
                return true;
            }

            // If both numbers have at least 6 digits, compare trailing digits
            // Handles +255 700 000 000 vs 0700000000 vs 700000000
            $lenT = strlen($ticketDigits);
            $lenC = strlen($contactDigits);
            if ($lenT >= 6 && $lenC >= 6) {
                if (str_ends_with($ticketDigits, $contactDigits) || str_ends_with($contactDigits, $ticketDigits)) {
                    return true;
                }
                $compareLen = min($lenT, $lenC, 9);
                if ($compareLen >= 7 && substr($ticketDigits, -$compareLen) === substr($contactDigits, -$compareLen)) {
                    return true;
                }
            }
        }
    }

    return false;
}

/**
 * Format and render SLA status badge.
 */
function render_sla_badge($dueDate, $status) {
    if (in_array($status, ['Resolved', 'Closed'], true)) {
        return '<span class="badge badge-resolved"><span class="badge-dot"></span> Resolved</span>';
    }
    if (empty($dueDate)) {
        return '<span class="badge badge-default">No SLA</span>';
    }

    $dueTs = strtotime($dueDate);
    $now = time();
    $diffMinutes = ($dueTs - $now) / 60;

    if ($diffMinutes < 0) {
        $overdueMins = abs(round($diffMinutes));
        $overdueStr = $overdueMins < 60 ? $overdueMins . 'm' : round($overdueMins / 60, 1) . 'h';
        return '<span class="badge badge-priority-high" title="Due ' . e(format_date($dueDate)) . '"><span class="badge-dot"></span> Overdue (' . $overdueStr . ')</span>';
    } elseif ($diffMinutes <= 120) {
        $remainMins = round($diffMinutes);
        return '<span class="badge badge-priority-medium" title="Due ' . e(format_date($dueDate)) . '"><span class="badge-dot"></span> Due Soon (' . $remainMins . 'm)</span>';
    } else {
        $remainHours = round($diffMinutes / 60, 1);
        return '<span class="badge badge-in-progress" title="Due ' . e(format_date($dueDate)) . '"><span class="badge-dot"></span> On Track (' . $remainHours . 'h)</span>';
    }
}

/**
 * Log a system-wide audit event into the audit_logs table.
 */
function log_audit_event(
    string $action,
    string $category = 'system',
    ?string $details = null,
    ?string $targetType = null,
    ?string $targetId = null,
    ?int $actorId = null,
    ?string $actorName = null,
    ?string $actorRole = null
): void {
    global $pdo;
    if (!$pdo) return;

    if ($actorId === null && isset($_SESSION['officer_id'])) {
        $actorId = (int)$_SESSION['officer_id'];
    }
    if ($actorName === null) {
        $actorName = $_SESSION['officer_name'] ?? ($actorId ? 'Officer #' . $actorId : 'Public / Guest');
    }
    if ($actorRole === null) {
        $actorRole = $_SESSION['officer_role'] ?? 'public';
    }

    $ip = get_client_ip();
    $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'CLI / Unknown', 0, 255);

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO audit_logs (actor_id, actor_name, actor_role, action, category, target_type, target_id, details, ip_address, user_agent, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([
            $actorId,
            substr($actorName, 0, 100),
            in_array($actorRole, ['supervisor', 'officer', 'public', 'system'], true) ? $actorRole : 'public',
            substr($action, 0, 60),
            substr($category, 0, 30),
            $targetType ? substr($targetType, 0, 40) : null,
            $targetId ? substr((string)$targetId, 0, 60) : null,
            $details,
            substr($ip, 0, 45),
            $userAgent
        ]);
    } catch (Throwable $e) {
        error_log("Failed to insert audit log: " . $e->getMessage());
    }
}

/**
 * Render category badge for audit logs.
 */
function render_audit_category_badge(string $category): string {
    $styles = [
        'auth'     => 'background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe;',
        'ticket'   => 'background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0;',
        'officer'  => 'background:#fdf2f8; color:#be185d; border:1px solid #fbcfe8;',
        'category' => 'background:#faf5ff; color:#7e22ce; border:1px solid #e9d5ff;',
        'security' => 'background:#fff1f2; color:#be123c; border:1px solid #fecdd3;',
        'system'   => 'background:#f8fafc; color:#475569; border:1px solid #e2e8f0;'
    ];
    $style = $styles[$category] ?? $styles['system'];
    return '<span class="badge" style="' . $style . '; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.04em;">' . e($category) . '</span>';
}

/**
 * Render actor role badge for audit logs.
 */
function render_audit_actor_badge(string $role): string {
    $styles = [
        'supervisor' => 'background:#dbeafe; color:#1e40af;',
        'officer'    => 'background:#d1fae5; color:#065f46;',
        'public'     => 'background:#f1f5f9; color:#475569;',
        'system'     => 'background:#ede9fe; color:#5b21b6;'
    ];
    $style = $styles[$role] ?? $styles['public'];
    return '<span class="badge" style="' . $style . '; font-size:0.725rem;">' . ucfirst(e($role)) . '</span>';
}

/**
 * Log ticket lifecycle activity to ticket_history table and global audit logs.
 */
function log_ticket_activity($ticketId, $actorName, $actorRole, $action, $details = null) {
    global $pdo;
    if (!$pdo || !$ticketId) return;

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO ticket_history (ticket_id, actor_name, actor_role, action, details, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([(int)$ticketId, substr($actorName, 0, 100), $actorRole, substr($action, 0, 50), $details]);

        // Propagate to global audit log
        log_audit_event(
            'ticket.' . $action,
            'ticket',
            $details,
            'ticket',
            (string)$ticketId,
            isset($_SESSION['officer_id']) ? (int)$_SESSION['officer_id'] : null,
            $actorName,
            $actorRole
        );
    } catch (Throwable $e) {
        error_log("Failed to log ticket activity: " . $e->getMessage());
    }
}

/**
 * Fetch activity history for a ticket.
 */
function get_ticket_history($ticketId) {
    global $pdo;
    if (!$pdo || !$ticketId) return [];

    try {
        $stmt = $pdo->prepare(
            "SELECT * FROM ticket_history WHERE ticket_id = ? ORDER BY created_at DESC"
        );
        $stmt->execute([(int)$ticketId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Process and save an uploaded file attachment for a ticket.
 */
function save_ticket_attachment($file, $ticketId, $uploaderType = 'public', $uploaderId = null, $resolutionId = null) {
    global $pdo;
    if (!$pdo || !$ticketId || empty($file) || !isset($file['error'])) {
        return ['success' => false, 'error' => 'No file provided.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['success' => true, 'attachment_id' => null]; // No file was uploaded, valid scenario
        }
        return ['success' => false, 'error' => 'Upload error code: ' . $file['error']];
    }

    // 10MB maximum file size
    $maxSize = 10 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File size exceeds maximum limit of 10 MB.'];
    }

    $originalName = basename($file['name']);
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    // Whitelist allowed extensions
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv', 'zip', 'log'];
    if (!in_array($ext, $allowedExts, true)) {
        return ['success' => false, 'error' => 'File type ".' . e($ext) . '" is not permitted. Allowed: ' . implode(', ', $allowedExts)];
    }

    $uploadDir = dirname(__DIR__) . '/uploads';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }

    $storedName = 'att_' . $ticketId . '_' . bin2hex(random_bytes(10)) . '.' . $ext;
    $destination = $uploadDir . '/' . $storedName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'error' => 'Failed to save uploaded file to storage.'];
    }

    $mimeType = $file['type'] ?: 'application/octet-stream';
    $fileSize = (int)$file['size'];

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO attachments (ticket_id, resolution_id, uploaded_by_type, uploader_id, original_filename, stored_filename, file_size, mime_type, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([
            (int)$ticketId,
            $resolutionId ? (int)$resolutionId : null,
            $uploaderType,
            $uploaderId ? (int)$uploaderId : null,
            $originalName,
            $storedName,
            $fileSize,
            $mimeType
        ]);

        $attachId = (int)$pdo->lastInsertId();
        return ['success' => true, 'attachment_id' => $attachId, 'filename' => $originalName];
    } catch (Throwable $e) {
        @unlink($destination);
        error_log("Failed to insert attachment record: " . $e->getMessage());
        return ['success' => false, 'error' => 'Database error recording attachment.'];
    }
}

/**
 * Fetch attachments associated with a ticket.
 */
function get_ticket_attachments($ticketId, $resolutionId = null) {
    global $pdo;
    if (!$pdo || !$ticketId) return [];

    try {
        if ($resolutionId !== null) {
            $stmt = $pdo->prepare("SELECT * FROM attachments WHERE ticket_id = ? AND resolution_id = ? ORDER BY created_at ASC");
            $stmt->execute([(int)$ticketId, (int)$resolutionId]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM attachments WHERE ticket_id = ? ORDER BY created_at ASC");
            $stmt->execute([(int)$ticketId]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Format bytes to readable size (KB, MB).
 */
function format_bytes($bytes, $precision = 1) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Render attachments display HTML.
 */
function render_attachments_list($attachments) {
    if (empty($attachments)) return '';

    $html = '<div class="attachments-list" style="margin-top: 0.75rem; display: flex; flex-direction: column; gap: 0.5rem;">';
    foreach ($attachments as $att) {
        $fileUrl = url('download_attachment.php?id=' . (int)$att['attachment_id']);
        $isImg = str_starts_with($att['mime_type'], 'image/');
        $iconName = $isImg ? 'eye' : 'paperclip';

        $html .= '<div style="display: flex; align-items: center; justify-content: space-between; background: #ffffff; border: 1px solid var(--color-border); padding: 0.5rem 0.85rem; border-radius: var(--radius-sm); font-size: 0.85rem;">' .
                 '<div style="display: flex; align-items: center; gap: 0.5rem; overflow: hidden;">' .
                 icon($iconName, '', 16) .
                 '<a href="' . $fileUrl . '" target="_blank" style="font-weight: 500; text-decoration: underline; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;" title="' . e($att['original_filename']) . '">' . e($att['original_filename']) . '</a>' .
                 '<span style="color: var(--color-text-muted); font-size: 0.75rem;">(' . format_bytes($att['file_size']) . ')</span>' .
                 '</div>' .
                 '<a href="' . $fileUrl . '" download class="btn btn-secondary btn-sm" style="padding: 0.2rem 0.5rem; font-size: 0.75rem;">Download</a>' .
                 '</div>';
    }
    $html .= '</div>';
    return $html;
}

/**
 * Render responsive pagination controls for tables and lists.
 */
function render_pagination($currentPage, $totalPages, $totalRecords, $perPage, $queryParams = [], $itemLabel = 'tickets') {
    if ($totalRecords <= 0) return '';

    $startRecord = (($currentPage - 1) * $perPage) + 1;
    $endRecord = min($currentPage * $perPage, $totalRecords);

    $buildUrl = function($pageNum) use ($queryParams) {
        $params = array_merge($queryParams, ['page' => $pageNum]);
        return '?' . http_build_query($params);
    };

    $html = '<div class="pagination-wrapper">';
    $html .= '<div class="pagination-info">';
    $html .= 'Showing <strong>' . (int)$startRecord . '</strong> to <strong>' . (int)$endRecord . '</strong> of <strong>' . (int)$totalRecords . '</strong> ' . e($itemLabel);
    $html .= '</div>';

    if ($totalPages > 1) {
        $html .= '<div class="pagination-links">';

        // Previous button
        if ($currentPage > 1) {
            $html .= '<a href="' . e($buildUrl($currentPage - 1)) . '" class="btn btn-secondary btn-sm pagination-btn">&laquo; Prev</a>';
        } else {
            $html .= '<span class="btn btn-secondary btn-sm pagination-btn disabled" style="opacity:0.4; pointer-events:none;">&laquo; Prev</span>';
        }

        $startPage = max(1, $currentPage - 2);
        $endPage = min($totalPages, $currentPage + 2);

        if ($startPage > 1) {
            $html .= '<a href="' . e($buildUrl(1)) . '" class="btn btn-secondary btn-sm pagination-btn">1</a>';
            if ($startPage > 2) {
                $html .= '<span class="pagination-ellipsis">&hellip;</span>';
            }
        }

        for ($p = $startPage; $p <= $endPage; $p++) {
            if ($p === $currentPage) {
                $html .= '<span class="btn btn-primary btn-sm pagination-btn active" style="font-weight:700;">' . $p . '</span>';
            } else {
                $html .= '<a href="' . e($buildUrl($p)) . '" class="btn btn-secondary btn-sm pagination-btn">' . $p . '</a>';
            }
        }

        if ($endPage < $totalPages) {
            if ($endPage < $totalPages - 1) {
                $html .= '<span class="pagination-ellipsis">&hellip;</span>';
            }
            $html .= '<a href="' . e($buildUrl($totalPages)) . '" class="btn btn-secondary btn-sm pagination-btn">' . $totalPages . '</a>';
        }

        // Next button
        if ($currentPage < $totalPages) {
            $html .= '<a href="' . e($buildUrl($currentPage + 1)) . '" class="btn btn-secondary btn-sm pagination-btn">Next &raquo;</a>';
        } else {
            $html .= '<span class="btn btn-secondary btn-sm pagination-btn disabled" style="opacity:0.4; pointer-events:none;">Next &raquo;</span>';
        }

        $html .= '</div>';
    }

    $html .= '</div>';
    return $html;
}

