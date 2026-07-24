<?php
namespace Aurismore\AAT;

if (!defined('ABSPATH')) exit;

/**
 * Database-backed support tickets.
 *
 * Support requests are stored in a dedicated {$prefix}aat_tickets table before
 * email/webhook delivery is attempted, so a request survives delivery failure.
 * Each ticket carries a status that the agency can change from the Tickets
 * admin page.
 */
class Tickets {
    private $core;

    const TABLE = 'aat_tickets';
    const DB_VERSION = '1';
    const DB_VERSION_OPTION = 'aat_db_version';

    const STATUSES = [
        'new' => 'New',
        'in_progress' => 'In progress',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
    ];

    public function __construct(Core $core) {
        $this->core = $core;
        if (is_admin()) {
            // `init` fires before admin_menu, so sites that update in place
            // (without re-activating) get the table before the menu badge or
            // Tickets page ever query it.
            add_action('init', [__CLASS__, 'maybe_install']);
        }
        add_action('admin_post_aat_update_ticket', [$this, 'handle_update']);
        add_action('admin_post_aat_delete_ticket', [$this, 'handle_delete']);
        add_action('admin_post_aat_ticket_bulk', [$this, 'handle_bulk']);
    }

    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . self::TABLE;
    }

    public static function is_installed() {
        return get_option(self::DB_VERSION_OPTION) === self::DB_VERSION;
    }

    public static function maybe_install() {
        if (!self::is_installed()) {
            self::install();
        }
    }

    public static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        dbDelta("CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            user_name varchar(190) NOT NULL DEFAULT '',
            user_email varchar(190) NOT NULL DEFAULT '',
            subject varchar(190) NOT NULL DEFAULT '',
            message longtext NOT NULL,
            category varchar(100) NOT NULL DEFAULT '',
            priority varchar(20) NOT NULL DEFAULT 'Normal',
            status varchar(20) NOT NULL DEFAULT 'new',
            page_url text NOT NULL,
            diagnostics longtext NULL,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY created_at (created_at)
        ) {$charset_collate};");

        update_option(self::DB_VERSION_OPTION, self::DB_VERSION);
        self::migrate_legacy_log();
    }

    /**
     * Import the pre-1.26 option-based support log into the tickets table.
     * Entries keep their original timestamps and start as "new" — they were
     * never tracked, so the agency can triage and close them from the list.
     */
    private static function migrate_legacy_log() {
        $log = get_option('aat_support_log', []);
        if (is_array($log)) {
            foreach ($log as $item) {
                if (!is_array($item)) continue;
                $user_name = sanitize_text_field($item['user'] ?? '');
                $user_email = '';
                if (preg_match('/^(.*)\s+\(([^)]+)\)$/', $user_name, $m) && is_email($m[2])) {
                    $user_name = trim($m[1]);
                    $user_email = $m[2];
                }
                self::create([
                    'created_at' => $item['created_at'] ?? '',
                    'user_name' => $user_name,
                    'user_email' => $user_email,
                    'subject' => $item['subject'] ?? '',
                    'message' => $item['message'] ?? '',
                    'category' => $item['category'] ?? '',
                    'priority' => $item['priority'] ?? 'Normal',
                    'page_url' => $item['page'] ?? '',
                ]);
            }
        }
        delete_option('aat_support_log');
    }

    /**
     * Insert a ticket. Returns the new ticket ID, or 0 on failure.
     */
    public static function create($data) {
        global $wpdb;
        $now = current_time('mysql');
        $created_at = !empty($data['created_at']) ? sanitize_text_field($data['created_at']) : $now;
        $status = sanitize_key($data['status'] ?? 'new');
        if (!isset(self::STATUSES[$status])) {
            $status = 'new';
        }

        $inserted = $wpdb->insert(
            self::table_name(),
            [
                'created_at' => $created_at,
                'updated_at' => $now,
                'user_id' => absint($data['user_id'] ?? 0),
                'user_name' => sanitize_text_field($data['user_name'] ?? ''),
                'user_email' => sanitize_email($data['user_email'] ?? ''),
                'subject' => sanitize_text_field($data['subject'] ?? ''),
                'message' => sanitize_textarea_field($data['message'] ?? ''),
                'category' => sanitize_text_field($data['category'] ?? ''),
                'priority' => sanitize_text_field($data['priority'] ?? 'Normal'),
                'status' => $status,
                'page_url' => esc_url_raw($data['page_url'] ?? ''),
                'diagnostics' => is_string($data['diagnostics'] ?? '') ? $data['diagnostics'] : '',
            ],
            ['%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        return $inserted ? (int) $wpdb->insert_id : 0;
    }

    public static function get($id) {
        global $wpdb;
        $id = absint($id);
        if (!$id || !self::is_installed()) return null;
        return $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . self::table_name() . ' WHERE id = %d', $id));
    }

    public static function update_status($id, $status) {
        global $wpdb;
        $id = absint($id);
        $status = sanitize_key($status);
        if (!$id || !isset(self::STATUSES[$status])) return false;
        return (bool) $wpdb->update(
            self::table_name(),
            ['status' => $status, 'updated_at' => current_time('mysql')],
            ['id' => $id],
            ['%s', '%s'],
            ['%d']
        );
    }

    public static function delete($id) {
        global $wpdb;
        $id = absint($id);
        if (!$id) return false;
        return (bool) $wpdb->delete(self::table_name(), ['id' => $id], ['%d']);
    }

    /**
     * Query tickets. Returns ['rows' => [], 'total' => int].
     */
    public static function query($args = []) {
        global $wpdb;
        if (!self::is_installed()) {
            return ['rows' => [], 'total' => 0];
        }
        $args = wp_parse_args($args, [
            'status' => '',
            'search' => '',
            'paged' => 1,
            'per_page' => 20,
        ]);

        $where = 'WHERE 1=1';
        $params = [];
        if ($args['status'] !== '' && isset(self::STATUSES[$args['status']])) {
            $where .= ' AND status = %s';
            $params[] = $args['status'];
        }
        if ($args['search'] !== '') {
            $like = '%' . $wpdb->esc_like($args['search']) . '%';
            $where .= ' AND (subject LIKE %s OR message LIKE %s OR user_name LIKE %s OR user_email LIKE %s)';
            array_push($params, $like, $like, $like, $like);
        }

        $table = self::table_name();
        $count_sql = "SELECT COUNT(*) FROM {$table} {$where}";
        $total = (int) $wpdb->get_var($params ? $wpdb->prepare($count_sql, $params) : $count_sql);

        $per_page = max(1, absint($args['per_page']));
        $offset = (max(1, absint($args['paged'])) - 1) * $per_page;
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} {$where} ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d",
            array_merge($params, [$per_page, $offset])
        ));

        return ['rows' => $rows ?: [], 'total' => $total];
    }

    /**
     * Per-status counts plus an 'all' total, for the filter views and badge.
     */
    public static function counts() {
        global $wpdb;
        $counts = array_fill_keys(array_keys(self::STATUSES), 0);
        $counts['all'] = 0;
        if (!self::is_installed()) {
            return $counts;
        }
        $rows = $wpdb->get_results('SELECT status, COUNT(*) AS total FROM ' . self::table_name() . ' GROUP BY status');
        foreach ((array) $rows as $row) {
            if (isset($counts[$row->status])) {
                $counts[$row->status] = (int) $row->total;
            }
            $counts['all'] += (int) $row->total;
        }
        return $counts;
    }

    public static function count_by_status($status) {
        global $wpdb;
        $status = sanitize_key($status);
        if (!isset(self::STATUSES[$status]) || !self::is_installed()) return 0;
        return (int) $wpdb->get_var($wpdb->prepare(
            'SELECT COUNT(*) FROM ' . self::table_name() . ' WHERE status = %s',
            $status
        ));
    }

    public static function status_label($status) {
        return self::STATUSES[$status] ?? ucfirst((string) $status);
    }

    private static function list_url($args = []) {
        return add_query_arg($args, admin_url('admin.php?page=wp-admin-toolkit-tickets'));
    }

    public function handle_update() {
        if (!current_user_can(AAT_CAP) || !check_admin_referer('aat_update_ticket')) wp_die('Access denied.');
        $id = absint($_POST['ticket_id'] ?? 0);
        $status = sanitize_key(wp_unslash($_POST['ticket_status'] ?? ''));
        $updated = $id ? self::update_status($id, $status) : false;
        wp_safe_redirect(self::list_url(['ticket' => $id, 'aat_ticket_updated' => $updated ? 1 : 0]));
        exit;
    }

    public function handle_delete() {
        $id = absint($_GET['ticket_id'] ?? 0);
        if (!current_user_can(AAT_CAP) || !check_admin_referer('aat_delete_ticket_' . $id)) wp_die('Access denied.');
        self::delete($id);
        wp_safe_redirect(self::list_url(['aat_ticket_deleted' => 1]));
        exit;
    }

    public function handle_bulk() {
        if (!current_user_can(AAT_CAP) || !check_admin_referer('aat_ticket_bulk')) wp_die('Access denied.');
        $action = sanitize_key(wp_unslash($_POST['bulk_action'] ?? ''));
        $ids = array_filter(array_map('absint', (array) ($_POST['ticket_ids'] ?? [])));
        $done = 0;
        foreach ($ids as $id) {
            if ($action === 'delete') {
                if (self::delete($id)) $done++;
            } elseif (isset(self::STATUSES[$action])) {
                if (self::update_status($id, $action)) $done++;
            }
        }
        wp_safe_redirect(self::list_url(['aat_ticket_bulk' => $done]));
        exit;
    }
}
