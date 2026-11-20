<?php
// Migration for Performance & Scalability composite indexes
require_once __DIR__ . '/../includes/db_connect.php';

echo "=== ISTS Performance & Scalability Index Migration ===\n\n";

$indexes = [
    [
        'table' => 'tickets',
        'name'  => 'idx_officer_status',
        'cols'  => '(assigned_officer_id, status)'
    ],
    [
        'table' => 'tickets',
        'name'  => 'idx_status_created',
        'cols'  => '(status, created_at)'
    ],
    [
        'table' => 'tickets',
        'name'  => 'idx_priority_status',
        'cols'  => '(priority, status)'
    ],
    [
        'table' => 'resolutions',
        'name'  => 'idx_ticket_internal',
        'cols'  => '(ticket_id, is_internal)'
    ],
    [
        'table' => 'rate_limits',
        'name'  => 'idx_action_ip_expires',
        'cols'  => '(action, ip_address, expires_at)'
    ],
    [
        'table' => 'attachments',
        'name'  => 'idx_att_ticket_res',
        'cols'  => '(ticket_id, resolution_id)'
    ]
];

foreach ($indexes as $idx) {
    try {
        $check = $pdo->query("SHOW INDEX FROM {$idx['table']} WHERE Key_name = '{$idx['name']}'")->fetch();
        if ($check) {
            echo " [EXISTS] Index {$idx['name']} on table {$idx['table']}\n";
        } else {
            $pdo->exec("ALTER TABLE {$idx['table']} ADD INDEX {$idx['name']} {$idx['cols']}");
            echo " [CREATED] Index {$idx['name']} on table {$idx['table']} {$idx['cols']}\n";
        }
    } catch (Throwable $e) {
        echo " [WARNING] Table {$idx['table']} / Index {$idx['name']}: " . $e->getMessage() . "\n";
    }
}

// Refresh schema cache file
$cacheDir = __DIR__ . '/../sys_cache';
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
}
@file_put_contents($cacheDir . '/.schema_synced', date('Y-m-d H:i:s'));
echo " [OK] Refreshed schema sync marker.\n";

echo "\nMigration completed successfully.\n";
