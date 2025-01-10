<?php defined('ABSPATH') || exit; ?>

<div class="wrap macp-wrap">
    <h1>Cache Metrics</h1>

    <div class="macp-dashboard-wrap">
        <!-- Overall Stats -->
        <div class="macp-card">
            <h2>Overall Statistics</h2>
            <p>Total Requests: <?php echo number_format($metrics['total_requests']); ?></p>
        </div>

        <!-- HTML Cache Stats -->
        <div class="macp-card">
            <h2>HTML Cache Performance</h2>
            <div class="macp-stats">
                <p>Hit Rate: <?php echo number_format($metrics['html_cache']['hit_rate'], 2); ?>%</p>
                <p>Cache Hits: <?php echo number_format($metrics['html_cache']['hits']); ?></p>
                <p>Cache Misses: <?php echo number_format($metrics['html_cache']['misses']); ?></p>
            </div>
        </div>

        <!-- Redis Cache Stats -->
        <div class="macp-card">
            <h2>Redis Cache Performance</h2>
            <div class="macp-stats">
                <p>Hit Rate: <?php echo number_format($metrics['redis_cache']['hit_rate'], 2); ?>%</p>
                <p>Cache Hits: <?php echo number_format($metrics['redis_cache']['hits']); ?></p>
                <p>Cache Misses: <?php echo number_format($metrics['redis_cache']['misses']); ?></p>
            </div>
        </div>
    </div>
</div>