<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php $logoUrl = $config['app']['base_url'] . '/public/assets/img/g2group.svg'; ?>
<section class="login-shell-split">
    <div class="login-brand-panel">
        <div class="login-brand-content">
            <div class="login-brand-mark login-brand-logo">
                <img src="<?= htmlspecialchars($logoUrl) ?>" alt="G2 Group">
            </div>
            <h1>Social Media Calendar &amp; Approval Platform</h1>
            <p>Plan campaigns, align agency teams, collect client feedback, and deliver approved artwork from one shared workspace.</p>
        </div>
        <div class="login-stats">
            <article><strong>500+</strong><span>Posts Managed</span></article>
            <article><strong>50+</strong><span>Active Clients</span></article>
            <article><strong>99%</strong><span>On-time Delivery</span></article>
        </div>
    </div>

    <div class="login-form-panel">
        <div class="login-form-head">
            <span class="mini-brand mini-brand-with-logo">
                <img src="<?= htmlspecialchars($logoUrl) ?>" alt="G2 Group">
                <span>G2 Social Calendar</span>
            </span>
            <h2>Welcome back</h2>
            <p>Sign in to manage calendars, approvals, artwork delivery, and client collaboration.</p>
        </div>
        <form method="post" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=login" class="stack">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
            <label>
                <span>Email</span>
                <input type="email" name="email" id="loginEmail" required>
            </label>
            <label>
                <span>Password</span>
                <input type="password" name="password" id="loginPassword" required>
            </label>
            <div class="login-row">
                <label class="check-row"><input type="checkbox" name="remember"> <span>Remember me</span></label>
                <a href="#" class="text-link">Forgot password?</a>
            </div>
            <button class="btn btn-primary btn-full" type="submit">Sign In</button>
        </form>

        <div class="demo-access">
            <div class="demo-head">
                <strong>Quick Demo Access</strong>
                <small>Development shortcuts for role testing.</small>
            </div>
            <div class="demo-grid">
                <button class="demo-card" type="button" data-demo-email="admin@g2.local" data-demo-password="password">
                    <strong>Admin</strong>
                    <span>Full workspace control</span>
                </button>
                <button class="demo-card" type="button" data-demo-email="fadi.chehade@greydoha.com" data-demo-password="password">
                    <strong>Fadi</strong>
                    <span>Assigned clients and approvals</span>
                </button>
            </div>
        </div>
    </div>
</section>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
