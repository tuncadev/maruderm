<?php

namespace Maruderm\Analytics;

final class AnalyticsAdminPage
{
    public function __construct(private readonly AnalyticsRepository $repository)
    {
    }

    public function register(): void
    {
        add_menu_page(
            'Maruderm Analytics',
            'Analytics',
            'manage_woocommerce',
            'maruderm-analytics',
            [$this, 'render'],
            'dashicons-chart-area',
            56
        );
    }

    public function render(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You do not have permission to access this page.'));
        }

        $days = isset($_GET['days']) ? absint($_GET['days']) : 30;
        $days = in_array($days, [7, 30, 90], true) ? $days : 30;
        $report = $this->repository->report($days);
        $selectedSession = isset($_GET['journey']) ? sanitize_text_field(wp_unslash($_GET['journey'])) : '';
        $journey = $selectedSession !== '' ? $this->repository->journey($selectedSession, $days) : [];
        ?>
        <div class="wrap maruderm-analytics">
            <h1>Maruderm Analytics</h1>
            <p>Aggregated statistics without names, email addresses, account IDs, IP addresses, or fingerprints. Raw events are retained for 90 days.</p>
            <nav class="nav-tab-wrapper">
                <?php foreach ([7, 30, 90] as $period) : ?>
                    <a class="nav-tab <?php echo $days === $period ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=maruderm-analytics&days=' . $period)); ?>"><?php echo esc_html($period . ' days'); ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="maruderm-analytics__cards">
                <?php
                $cards = [
                    'Sessions' => $report['sessions'],
                    'Page views' => $report['page_views'],
                    'Engaged sessions' => $report['engaged_sessions'],
                    'Bounce rate' => $report['bounce_rate'] . '%',
                    'Product views' => $report['product_views'],
                    'Added to cart' => $report['add_to_cart'],
                    'Checkout starts' => $report['checkout_started'],
                    'Completed checkouts' => $report['checkout_completed'],
                ];
                foreach ($cards as $label => $value) :
                    ?>
                    <article><span><?php echo esc_html($label); ?></span><strong><?php echo esc_html((string) $value); ?></strong></article>
                <?php endforeach; ?>
            </div>
            <div class="maruderm-analytics__grid">
                <?php $this->renderTable('Most viewed products', $report['top_products'], 'Product'); ?>
                <?php $this->renderTable('Most viewed categories', $report['top_categories'], 'Category'); ?>
                <?php $this->renderTable('Most viewed pages', $report['top_pages'], 'Path'); ?>
                <?php $this->renderTable('Checkout funnel', $report['checkout_steps'], 'Step'); ?>
                <?php $this->renderTable('Scroll depth', $report['scroll_depth'], 'Percentage', '%'); ?>
                <?php $this->renderTable('Session type', array_map(static fn (array $row): array => ['label' => (int) $row['label'] === 1 ? 'Logged in' : 'Anonymous', 'total' => $row['total']], $report['login_split']), 'Type'); ?>
            </div>
            <?php $this->renderSessions($report['sessions_list'], $days); ?>
            <?php if ($selectedSession !== '') : ?>
                <?php $this->renderJourney($selectedSession, $journey, $days); ?>
            <?php endif; ?>
        </div>
        <style>
            .maruderm-analytics__cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:14px;margin:22px 0}.maruderm-analytics__cards article,.maruderm-analytics__panel{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:18px}.maruderm-analytics__cards span{display:block;color:#646970;margin-bottom:8px}.maruderm-analytics__cards strong{font-size:28px}.maruderm-analytics__grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.maruderm-analytics__panel{margin-top:16px}.maruderm-analytics__grid .maruderm-analytics__panel{margin-top:0}.maruderm-analytics__panel h2{margin-top:0}.maruderm-analytics__panel table{width:100%;border-collapse:collapse}.maruderm-analytics__panel th{text-align:left}.maruderm-analytics__panel td{padding:8px;border-top:1px solid #eee}.maruderm-analytics__panel td:last-child{text-align:right;font-weight:600}.maruderm-analytics__journey td:last-child{text-align:left;font-weight:400}.maruderm-analytics__journey-sequence{width:48px;color:#646970}.maruderm-analytics__muted{color:#646970;font-size:12px}@media(max-width:782px){.maruderm-analytics__grid{grid-template-columns:1fr}.maruderm-analytics__panel{overflow-x:auto}}
        </style>
        <?php
    }

    /** @param array<int, array<string, mixed>> $rows */
    private function renderTable(string $title, array $rows, string $labelHeading, string $suffix = ''): void
    {
        ?>
        <section class="maruderm-analytics__panel">
            <h2><?php echo esc_html($title); ?></h2>
            <table><thead><tr><th><?php echo esc_html($labelHeading); ?></th><th>Sessions / events</th></tr></thead><tbody>
            <?php if ($rows === []) : ?><tr><td colspan="2">No data yet</td></tr><?php endif; ?>
            <?php foreach ($rows as $row) : ?><tr><td><?php echo esc_html((string) $row['label'] . $suffix); ?></td><td><?php echo esc_html((string) $row['total']); ?></td></tr><?php endforeach; ?>
            </tbody></table>
        </section>
        <?php
    }

    /** @param array<int, array<string, mixed>> $sessions */
    private function renderSessions(array $sessions, int $days): void
    {
        ?>
        <section class="maruderm-analytics__panel">
            <h2>Individual session journeys</h2>
            <p class="maruderm-analytics__muted">The anonymous code is the first 10 characters of a one-way temporary session hash, not a user identifier.</p>
            <table><thead><tr><th>Session</th><th>Type</th><th>Started</th><th>Source</th><th>Entry</th><th>Exit</th><th>Pages</th><th>Actions</th><th></th></tr></thead><tbody>
            <?php if ($sessions === []) : ?><tr><td colspan="9">No data yet</td></tr><?php endif; ?>
            <?php foreach ($sessions as $session) : ?>
                <tr>
                    <td><code><?php echo esc_html(substr((string) $session['session_hash'], 0, 10)); ?></code></td>
                    <td><?php echo (int) $session['logged_in'] === 1 ? 'Logged in' : 'Anonymous'; ?></td>
                    <td><?php echo esc_html((string) $session['started_at']); ?></td>
                    <td><?php echo esc_html((string) ($session['referrer_host'] ?: 'Direct')); ?></td>
                    <td><?php echo esc_html((string) $session['entry_path']); ?></td>
                    <td><?php echo esc_html((string) $session['exit_path']); ?></td>
                    <td><?php echo esc_html((string) $session['pages']); ?></td>
                    <td><?php echo esc_html((string) $session['actions']); ?></td>
                    <td><a class="button button-small" href="<?php echo esc_url(admin_url('admin.php?page=maruderm-analytics&days=' . $days . '&journey=' . rawurlencode((string) $session['session_hash']) . '#journey')); ?>">View journey</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody></table>
        </section>
        <?php
    }

    /** @param array<int, array<string, mixed>> $events */
    private function renderJourney(string $sessionHash, array $events, int $days): void
    {
        ?>
        <section class="maruderm-analytics__panel maruderm-analytics__journey" id="journey">
            <h2>Session journey <code><?php echo esc_html(substr($sessionHash, 0, 10)); ?></code></h2>
            <p><a href="<?php echo esc_url(admin_url('admin.php?page=maruderm-analytics&days=' . $days)); ?>">Close details</a></p>
            <table><thead><tr><th>#</th><th>Time (UTC)</th><th>Action</th><th>Page</th><th>Previous/source</th><th>Details</th></tr></thead><tbody>
            <?php if ($events === []) : ?><tr><td colspan="6">No session events were found in the selected period.</td></tr><?php endif; ?>
            <?php foreach ($events as $event) : ?>
                <tr>
                    <td class="maruderm-analytics__journey-sequence"><?php echo esc_html((string) $event['sequence_no']); ?></td>
                    <td><?php echo esc_html((string) $event['occurred_at']); ?></td>
                    <td><?php echo esc_html($this->eventLabel((string) $event['event_type'])); ?></td>
                    <td><?php echo esc_html((string) $event['path']); ?></td>
                    <td><?php echo esc_html((string) ($event['referrer_path'] ?: $event['referrer_host'])); ?></td>
                    <td><?php echo esc_html($this->eventDetails($event)); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody></table>
        </section>
        <?php
    }

    private function eventLabel(string $eventType): string
    {
        return [
            'page_view' => 'Page view',
            'engagement' => 'Engagement',
            'scroll_depth' => 'Scroll depth',
            'product_view' => 'Product view',
            'category_view' => 'Category view',
            'add_to_cart' => 'Added to cart',
            'checkout_started' => 'Checkout started',
            'checkout_step' => 'Checkout step',
            'checkout_completed' => 'Checkout completed',
        ][$eventType] ?? $eventType;
    }

    /** @param array<string, mixed> $event */
    private function eventDetails(array $event): string
    {
        $details = array_filter([
            (string) $event['object_name'],
            (string) $event['category_name'],
            (string) $event['checkout_step'],
            (int) $event['scroll_depth'] > 0 ? $event['scroll_depth'] . '%' : '',
            (int) $event['duration_ms'] > 0 ? round(((int) $event['duration_ms']) / 1000, 1) . ' s' : '',
        ]);

        return implode(' · ', $details);
    }
}
