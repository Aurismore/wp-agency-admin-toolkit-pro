<?php
namespace Aurismore\AAT\Admin;

use Aurismore\AAT\Tickets;

if (!defined('ABSPATH')) exit;

class TicketsPage extends Page {
    const PER_PAGE = 20;

    public function slug() {
        return 'wp-admin-toolkit-tickets';
    }

    public function title() {
        return 'Support Tickets';
    }

    public function menu_label() {
        $label = 'Tickets';
        $new_count = Tickets::count_by_status('new');
        if ($new_count > 0) {
            $label .= ' <span class="awaiting-mod count-' . absint($new_count) . '"><span class="pending-count">' . number_format_i18n($new_count) . '</span></span>';
        }
        return $label;
    }

    protected function notices() {
        parent::notices();
        if (isset($_GET['aat_ticket_updated'])) {
            if ((int) $_GET['aat_ticket_updated'] === 1) {
                $this->notice('Ticket status updated.');
            } else {
                $this->notice('The ticket status could not be updated.', 'error');
            }
        }
        if (isset($_GET['aat_ticket_deleted'])) $this->notice('Ticket deleted.');
        if (isset($_GET['aat_ticket_bulk'])) $this->notice(sprintf('%d ticket(s) updated.', absint($_GET['aat_ticket_bulk'])));
    }

    protected function content($s) {
        $ticket_id = absint($_GET['ticket'] ?? 0);
        if ($ticket_id) {
            $this->render_detail($ticket_id);
            return;
        }
        $this->render_list();
    }

    private function status_badge($status) {
        return '<span class="aat-ticket-status aat-ticket-status-' . esc_attr($status) . '">' . esc_html(Tickets::status_label($status)) . '</span>';
    }

    private function format_date($mysql_date) {
        return mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $mysql_date);
    }

    private function render_list() {
        $status = sanitize_key(wp_unslash($_GET['status'] ?? ''));
        if (!isset(Tickets::STATUSES[$status])) $status = '';
        $search = sanitize_text_field(wp_unslash($_GET['s'] ?? ''));
        $paged = max(1, absint($_GET['paged'] ?? 1));

        $counts = Tickets::counts();
        $result = Tickets::query([
            'status' => $status,
            'search' => $search,
            'paged' => $paged,
            'per_page' => self::PER_PAGE,
        ]);
        $rows = $result['rows'];
        $total = $result['total'];
        $total_pages = (int) ceil($total / self::PER_PAGE);
        ?>
        <div class="aat-card aat-wide aat-tickets-card">
            <h2>Tickets</h2>
            <p>Support requests submitted from the client dashboard and admin support button. Tickets are stored in the database and keep their status here even when email or webhook delivery fails.</p>

            <div class="aat-ticket-filters">
                <a class="<?php echo $status === '' ? 'aat-tab-current' : ''; ?>" href="<?php echo esc_url($this->url()); ?>">All <span>(<?php echo (int) $counts['all']; ?>)</span></a>
                <?php foreach (Tickets::STATUSES as $status_key => $status_label): ?>
                    <a class="<?php echo $status === $status_key ? 'aat-tab-current' : ''; ?>" href="<?php echo esc_url($this->url(['status' => $status_key])); ?>"><?php echo esc_html($status_label); ?> <span>(<?php echo (int) $counts[$status_key]; ?>)</span></a>
                <?php endforeach; ?>
            </div>

            <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="aat-ticket-search">
                <input type="hidden" name="page" value="<?php echo esc_attr($this->slug()); ?>">
                <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?php echo esc_attr($status); ?>"><?php endif; ?>
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search subject, message or requester">
                <button class="button">Search</button>
            </form>

            <?php if (empty($rows)): ?>
                <p><?php echo $search !== '' || $status !== '' ? 'No tickets match this filter.' : 'No support tickets have been received yet.'; ?></p>
            <?php else: ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="aat_ticket_bulk">
                    <?php wp_nonce_field('aat_ticket_bulk'); ?>
                    <div class="aat-ticket-toolbar">
                        <select name="bulk_action">
                            <option value="">Bulk actions</option>
                            <?php foreach (Tickets::STATUSES as $status_key => $status_label): ?>
                                <option value="<?php echo esc_attr($status_key); ?>">Mark as <?php echo esc_html(strtolower($status_label)); ?></option>
                            <?php endforeach; ?>
                            <option value="delete">Delete</option>
                        </select>
                        <button class="button" onclick="return this.form.bulk_action.value !== 'delete' || confirm('Delete the selected tickets permanently?');">Apply</button>
                    </div>
                    <table class="wp-list-table widefat fixed striped aat-tickets-table">
                        <thead>
                            <tr>
                                <td class="manage-column column-cb check-column"><input type="checkbox" id="aat-tickets-select-all"></td>
                                <th class="aat-col-id">ID</th>
                                <th>Subject</th>
                                <th>From</th>
                                <th>Category</th>
                                <th class="aat-col-priority">Priority</th>
                                <th class="aat-col-status">Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $ticket): ?>
                                <tr>
                                    <th scope="row" class="check-column"><input type="checkbox" class="aat-ticket-cb" name="ticket_ids[]" value="<?php echo (int) $ticket->id; ?>"></th>
                                    <td class="aat-col-id">#<?php echo (int) $ticket->id; ?></td>
                                    <td><a href="<?php echo esc_url($this->url(['ticket' => (int) $ticket->id])); ?>"><strong><?php echo esc_html($ticket->subject !== '' ? $ticket->subject : '(no subject)'); ?></strong></a></td>
                                    <td><?php echo esc_html($ticket->user_name); ?><?php if ($ticket->user_email): ?><br><small><?php echo esc_html($ticket->user_email); ?></small><?php endif; ?></td>
                                    <td><?php echo esc_html($ticket->category); ?></td>
                                    <td class="aat-col-priority"><?php echo esc_html($ticket->priority); ?></td>
                                    <td class="aat-col-status"><?php echo $this->status_badge($ticket->status); ?></td>
                                    <td><?php echo esc_html($this->format_date($ticket->created_at)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </form>

                <?php if ($total_pages > 1): ?>
                    <div class="aat-ticket-pagination tablenav"><div class="tablenav-pages">
                        <?php
                        echo paginate_links([
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'total' => $total_pages,
                            'current' => $paged,
                        ]);
                        ?>
                    </div></div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_detail($ticket_id) {
        $ticket = Tickets::get($ticket_id);
        if (!$ticket) {
            echo '<div class="aat-card aat-wide"><p>Ticket not found. It may have been deleted.</p><p><a class="button" href="' . esc_url($this->url()) . '">Back to tickets</a></p></div>';
            return;
        }
        $diagnostics = '';
        if (!empty($ticket->diagnostics)) {
            $decoded = json_decode($ticket->diagnostics, true);
            $diagnostics = is_array($decoded) ? wp_json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : $ticket->diagnostics;
        }
        $delete_url = wp_nonce_url(
            admin_url('admin-post.php?action=aat_delete_ticket&ticket_id=' . (int) $ticket->id),
            'aat_delete_ticket_' . (int) $ticket->id
        );
        ?>
        <div class="aat-card aat-wide aat-ticket-detail">
            <p><a href="<?php echo esc_url($this->url()); ?>">&larr; Back to tickets</a></p>
            <h2>#<?php echo (int) $ticket->id; ?> — <?php echo esc_html($ticket->subject !== '' ? $ticket->subject : '(no subject)'); ?></h2>

            <div class="aat-status-grid">
                <div class="aat-status-row"><strong>Status</strong><span><?php echo $this->status_badge($ticket->status); ?></span></div>
                <div class="aat-status-row"><strong>Priority</strong><span><?php echo esc_html($ticket->priority); ?></span></div>
                <div class="aat-status-row"><strong>Category</strong><span><?php echo esc_html($ticket->category); ?></span></div>
                <div class="aat-status-row"><strong>Requester</strong><span><?php echo esc_html(trim($ticket->user_name . ' ' . ($ticket->user_email ? '(' . $ticket->user_email . ')' : ''))); ?></span></div>
                <div class="aat-status-row"><strong>Submitted</strong><span><?php echo esc_html($this->format_date($ticket->created_at)); ?></span></div>
                <div class="aat-status-row"><strong>Last updated</strong><span><?php echo esc_html($this->format_date($ticket->updated_at)); ?></span></div>
                <?php if ($ticket->page_url): ?>
                    <div class="aat-status-row"><strong>Page</strong><span><a href="<?php echo esc_url($ticket->page_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($ticket->page_url); ?></a></span></div>
                <?php endif; ?>
            </div>

            <h3>Message</h3>
            <div class="aat-ticket-message"><?php echo wp_kses_post(wpautop(esc_html($ticket->message))); ?></div>

            <?php if ($diagnostics): ?>
                <h3>Diagnostics</h3>
                <pre class="aat-ticket-diagnostics"><?php echo esc_html($diagnostics); ?></pre>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="aat-ticket-status-form">
                <input type="hidden" name="action" value="aat_update_ticket">
                <input type="hidden" name="ticket_id" value="<?php echo (int) $ticket->id; ?>">
                <?php wp_nonce_field('aat_update_ticket'); ?>
                <label for="aat-ticket-status">Change status</label>
                <select id="aat-ticket-status" name="ticket_status">
                    <?php foreach (Tickets::STATUSES as $status_key => $status_label): ?>
                        <option value="<?php echo esc_attr($status_key); ?>" <?php selected($ticket->status, $status_key); ?>><?php echo esc_html($status_label); ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="button button-primary">Update status</button>
                <a class="button button-link-delete" href="<?php echo esc_url($delete_url); ?>" onclick="return confirm('Delete this ticket permanently?');">Delete ticket</a>
            </form>
        </div>
        <?php
    }
}
