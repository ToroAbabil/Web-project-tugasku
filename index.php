<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/tasks.php';
require_once __DIR__ . '/includes/google.php';

$isLoggedIn = isset($_SESSION['access_token']);
$user       = $_SESSION['user'] ?? null;
$tasks      = loadTasks();
$stats      = getStats();

// Sort tasks: pending first by deadline, then submitted
uasort($tasks, function($a, $b) {
    if ($a['status'] !== $b['status']) {
        return $a['status'] === 'pending' ? -1 : 1;
    }
    return strtotime($a['deadline']) - strtotime($b['deadline']);
});

$authUrl = getAuthUrl();

// Motivational quotes
$quotes = [
    "Tugas yang belum selesai adalah hutang pada dirimu sendiri.",
    "Mulai sekarang, bukan nanti. Nanti itu tidak pernah datang.",
    "Disiplin adalah jembatan antara tujuan dan pencapaian.",
    "Kamu tidak harus merasa mau dulu baru mulai. Mulai dulu, baru rasanya datang.",
    "Setiap tugas yang kamu selesaikan adalah bukti kamu bisa.",
    "Rasa malas cuma 5 menit. Setelah itu, kamu akan ketagihan produktif.",
];
$todayQuote = $quotes[date('N') % count($quotes)];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TugasKu — Jangan Kabur dari Deadline</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
/* ── RESET & BASE ── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --bg:        #0a0a0f;
  --surface:   #12121a;
  --border:    #1e1e2e;
  --border2:   #2a2a3e;
  --accent:    #e8ff47;
  --accent2:   #ff6b47;
  --accent3:   #47c8ff;
  --text:      #e8e8f0;
  --muted:     #6b6b8a;
  --safe:      #47ff9c;
  --warn:      #ffd447;
  --danger:    #ff4757;
  --radius:    12px;
  --mono:      'Space Mono', monospace;
  --sans:      'Syne', sans-serif;
}

html { scroll-behavior: smooth; }
body {
  background: var(--bg);
  color: var(--text);
  font-family: var(--sans);
  min-height: 100vh;
  overflow-x: hidden;
}

/* ── NOISE TEXTURE ── */
body::before {
  content: '';
  position: fixed;
  inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
  pointer-events: none;
  z-index: 0;
}

/* ── HEADER ── */
header {
  position: sticky;
  top: 0;
  z-index: 100;
  background: rgba(10,10,15,0.85);
  backdrop-filter: blur(20px);
  border-bottom: 1px solid var(--border);
  padding: 0 24px;
  height: 64px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.logo {
  display: flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
}
.logo-icon {
  width: 36px; height: 36px;
  background: var(--accent);
  border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-size: 18px;
}
.logo-text {
  font-size: 18px;
  font-weight: 800;
  color: var(--text);
  letter-spacing: -0.5px;
}
.logo-text span { color: var(--accent); }

.header-right { display: flex; align-items: center; gap: 12px; }

.user-badge {
  display: flex;
  align-items: center;
  gap: 8px;
  background: var(--surface);
  border: 1px solid var(--border);
  padding: 6px 12px 6px 6px;
  border-radius: 100px;
  font-size: 13px;
  color: var(--muted);
}
.user-badge img {
  width: 28px; height: 28px;
  border-radius: 50%;
  object-fit: cover;
}

/* ── BUTTONS ── */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  border-radius: 8px;
  font-family: var(--sans);
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  border: none;
  transition: all 0.2s;
  text-decoration: none;
}
.btn-primary {
  background: var(--accent);
  color: #0a0a0f;
}
.btn-primary:hover { background: #f0ff6a; transform: translateY(-1px); }
.btn-ghost {
  background: transparent;
  color: var(--muted);
  border: 1px solid var(--border2);
}
.btn-ghost:hover { color: var(--text); border-color: var(--muted); }
.btn-danger {
  background: rgba(255,71,87,0.1);
  color: var(--danger);
  border: 1px solid rgba(255,71,87,0.2);
}
.btn-danger:hover { background: rgba(255,71,87,0.2); }
.btn-sm { padding: 6px 12px; font-size: 12px; }
.btn-google {
  background: white;
  color: #1a1a1a;
  font-size: 15px;
  padding: 14px 28px;
}
.btn-google:hover { background: #f0f0f0; transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.3); }

/* ── MAIN LAYOUT ── */
main {
  max-width: 1100px;
  margin: 0 auto;
  padding: 32px 24px 80px;
  position: relative;
  z-index: 1;
}

/* ── LOGIN SCREEN ── */
.login-screen {
  min-height: calc(100vh - 64px);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 40px 24px;
}
.login-hero {
  font-size: clamp(40px, 8vw, 80px);
  font-weight: 800;
  line-height: 1.05;
  letter-spacing: -2px;
  margin-bottom: 20px;
}
.login-hero .highlight {
  color: var(--accent);
  position: relative;
  display: inline-block;
}
.login-hero .highlight::after {
  content: '';
  position: absolute;
  bottom: 4px;
  left: 0; right: 0;
  height: 4px;
  background: var(--accent);
  border-radius: 2px;
}
.login-sub {
  font-size: 17px;
  color: var(--muted);
  max-width: 460px;
  line-height: 1.6;
  margin-bottom: 8px;
}
.login-quote {
  font-family: var(--mono);
  font-size: 13px;
  color: var(--accent2);
  margin-bottom: 40px;
  padding: 12px 20px;
  border: 1px solid rgba(255,107,71,0.2);
  border-radius: 8px;
  background: rgba(255,107,71,0.05);
  max-width: 500px;
}

.features {
  display: flex;
  gap: 16px;
  margin-bottom: 48px;
  flex-wrap: wrap;
  justify-content: center;
}
.feature-chip {
  padding: 8px 16px;
  background: var(--surface);
  border: 1px solid var(--border2);
  border-radius: 100px;
  font-size: 13px;
  color: var(--muted);
  display: flex;
  align-items: center;
  gap: 6px;
}

/* ── STATS BAR ── */
.stats-bar {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 12px;
  margin-bottom: 32px;
}
.stat-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 16px 20px;
  position: relative;
  overflow: hidden;
}
.stat-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 2px;
}
.stat-card.streak::before   { background: var(--accent); }
.stat-card.done::before     { background: var(--safe); }
.stat-card.pending::before  { background: var(--warn); }
.stat-card.late::before     { background: var(--danger); }
.stat-value {
  font-family: var(--mono);
  font-size: 32px;
  font-weight: 700;
  line-height: 1;
  margin-bottom: 4px;
}
.stat-card.streak .stat-value  { color: var(--accent); }
.stat-card.done .stat-value    { color: var(--safe); }
.stat-card.pending .stat-value { color: var(--warn); }
.stat-card.late .stat-value    { color: var(--danger); }
.stat-label { font-size: 12px; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; }

/* ── QUOTE BAR ── */
.quote-bar {
  display: flex;
  align-items: center;
  gap: 12px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-left: 3px solid var(--accent);
  border-radius: var(--radius);
  padding: 16px 20px;
  margin-bottom: 28px;
  font-family: var(--mono);
  font-size: 13px;
  color: var(--muted);
}
.quote-icon { font-size: 20px; flex-shrink: 0; }

/* ── SECTION HEADER ── */
.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}
.section-title {
  font-size: 20px;
  font-weight: 800;
  letter-spacing: -0.5px;
}

/* ── TASK CARDS ── */
.task-list { display: flex; flex-direction: column; gap: 12px; }

.task-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 20px;
  position: relative;
  overflow: hidden;
  transition: border-color 0.2s, transform 0.2s;
}
.task-card:hover { border-color: var(--border2); transform: translateX(2px); }
.task-card.submitted {
  opacity: 0.6;
  border-color: rgba(71,255,156,0.15);
}
.task-card.submitted::after {
  content: '✓ SELESAI';
  position: absolute;
  top: 16px; right: 16px;
  font-family: var(--mono);
  font-size: 11px;
  color: var(--safe);
  background: rgba(71,255,156,0.1);
  border: 1px solid rgba(71,255,156,0.2);
  padding: 3px 8px;
  border-radius: 4px;
  letter-spacing: 1px;
}

.task-card-inner { display: flex; gap: 16px; align-items: flex-start; }

.priority-dot {
  width: 10px; height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
  margin-top: 6px;
}
.priority-dot.high   { background: var(--danger); box-shadow: 0 0 8px var(--danger); }
.priority-dot.medium { background: var(--warn); }
.priority-dot.low    { background: var(--muted); }

.task-body { flex: 1; min-width: 0; }
.task-title {
  font-size: 16px;
  font-weight: 700;
  margin-bottom: 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.task-subject {
  font-family: var(--mono);
  font-size: 11px;
  color: var(--accent3);
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-bottom: 8px;
}
.task-desc { font-size: 13px; color: var(--muted); margin-bottom: 12px; }

.task-footer { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.deadline-badge {
  display: flex;
  align-items: center;
  gap: 6px;
  font-family: var(--mono);
  font-size: 12px;
  padding: 4px 10px;
  border-radius: 6px;
  font-weight: 700;
}
.deadline-badge.safe     { color: var(--safe);  background: rgba(71,255,156,0.1);  border: 1px solid rgba(71,255,156,0.2); }
.deadline-badge.soon     { color: var(--warn);  background: rgba(255,212,71,0.1);  border: 1px solid rgba(255,212,71,0.2); }
.deadline-badge.warning  { color: var(--warn);  background: rgba(255,212,71,0.15); border: 1px solid rgba(255,212,71,0.3); }
.deadline-badge.critical { color: var(--accent2);background:rgba(255,107,71,0.15); border: 1px solid rgba(255,107,71,0.3); }
.deadline-badge.overdue  { color: var(--danger); background: rgba(255,71,87,0.1);  border: 1px solid rgba(255,71,87,0.2); }

.countdown { font-family: var(--mono); font-size: 11px; color: var(--muted); }

.task-actions { display: flex; gap: 8px; margin-left: auto; }

/* ── STREAK BADGE ── */
.streak-banner {
  background: linear-gradient(135deg, rgba(232,255,71,0.1), rgba(232,255,71,0.03));
  border: 1px solid rgba(232,255,71,0.2);
  border-radius: var(--radius);
  padding: 20px 24px;
  margin-bottom: 28px;
  display: flex;
  align-items: center;
  gap: 16px;
}
.streak-fire { font-size: 40px; line-height: 1; }
.streak-info h3 { font-size: 22px; font-weight: 800; color: var(--accent); }
.streak-info p { font-size: 13px; color: var(--muted); margin-top: 2px; }

/* ── EMPTY STATE ── */
.empty-state {
  text-align: center;
  padding: 64px 24px;
  border: 1px dashed var(--border2);
  border-radius: var(--radius);
}
.empty-state .empty-icon { font-size: 48px; margin-bottom: 16px; }
.empty-state h3 { font-size: 20px; margin-bottom: 8px; }
.empty-state p { color: var(--muted); font-size: 14px; }

/* ── MODAL ── */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.7);
  backdrop-filter: blur(8px);
  z-index: 200;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.25s;
}
.modal-overlay.active { opacity: 1; pointer-events: all; }

.modal {
  background: var(--surface);
  border: 1px solid var(--border2);
  border-radius: 16px;
  padding: 32px;
  width: 100%;
  max-width: 480px;
  transform: translateY(20px);
  transition: transform 0.25s;
}
.modal-overlay.active .modal { transform: translateY(0); }

.modal-title {
  font-size: 22px;
  font-weight: 800;
  margin-bottom: 24px;
}

/* ── FORM ── */
.form-group { margin-bottom: 16px; }
.form-label {
  display: block;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: var(--muted);
  margin-bottom: 8px;
}
.form-control {
  width: 100%;
  background: var(--bg);
  border: 1px solid var(--border2);
  border-radius: 8px;
  padding: 12px 14px;
  font-family: var(--sans);
  font-size: 14px;
  color: var(--text);
  transition: border-color 0.2s;
  outline: none;
}
.form-control:focus { border-color: var(--accent); }
.form-control::placeholder { color: var(--muted); }

.priority-selector { display: flex; gap: 8px; }
.priority-btn {
  flex: 1;
  padding: 10px;
  border: 1px solid var(--border2);
  border-radius: 8px;
  background: var(--bg);
  color: var(--muted);
  font-family: var(--sans);
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  text-align: center;
  transition: all 0.2s;
}
.priority-btn:hover { border-color: var(--muted); color: var(--text); }
.priority-btn.active-high   { background: rgba(255,71,87,0.15);   color: var(--danger); border-color: var(--danger); }
.priority-btn.active-medium { background: rgba(255,212,71,0.15);  color: var(--warn);   border-color: var(--warn); }
.priority-btn.active-low    { background: rgba(107,107,138,0.15); color: var(--muted);  border-color: var(--muted); }

.modal-footer { display: flex; gap: 8px; justify-content: flex-end; margin-top: 24px; }

/* ── UPLOAD AREA ── */
.upload-area {
  border: 2px dashed var(--border2);
  border-radius: 8px;
  padding: 32px;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s;
  position: relative;
}
.upload-area:hover, .upload-area.drag-over {
  border-color: var(--accent);
  background: rgba(232,255,71,0.03);
}
.upload-area input[type="file"] {
  position: absolute;
  inset: 0;
  opacity: 0;
  cursor: pointer;
  width: 100%;
  height: 100%;
}
.upload-icon { font-size: 32px; margin-bottom: 8px; }
.upload-text { font-size: 14px; color: var(--muted); }
.upload-filename { font-family: var(--mono); font-size: 12px; color: var(--accent); margin-top: 8px; }

/* ── TOAST ── */
.toast-container {
  position: fixed;
  bottom: 24px; right: 24px;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.toast {
  background: var(--surface);
  border: 1px solid var(--border2);
  border-radius: 10px;
  padding: 14px 20px;
  font-size: 14px;
  max-width: 360px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.4);
  animation: toastIn 0.3s ease;
  display: flex;
  align-items: flex-start;
  gap: 10px;
}
.toast.success { border-left: 3px solid var(--safe); }
.toast.error   { border-left: 3px solid var(--danger); }
.toast.info    { border-left: 3px solid var(--accent); }
@keyframes toastIn {
  from { transform: translateX(100%); opacity: 0; }
  to   { transform: translateX(0);    opacity: 1; }
}

/* ── LOADING ── */
.loading-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.5);
  backdrop-filter: blur(4px);
  z-index: 500;
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.2s;
}
.loading-overlay.active { opacity: 1; pointer-events: all; }
.spinner {
  width: 48px; height: 48px;
  border: 3px solid var(--border2);
  border-top-color: var(--accent);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── TABS ── */
.tab-bar {
  display: flex;
  gap: 4px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 4px;
  margin-bottom: 24px;
  width: fit-content;
}
.tab-btn {
  padding: 8px 20px;
  border-radius: 7px;
  border: none;
  background: transparent;
  color: var(--muted);
  font-family: var(--sans);
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}
.tab-btn.active { background: var(--bg); color: var(--text); }

/* ── RESPONSIVE ── */
@media (max-width: 600px) {
  .stats-bar { grid-template-columns: repeat(2, 1fr); }
  .task-actions { flex-wrap: wrap; }
  .login-hero { font-size: 36px; }
}
</style>
</head>
<body>

<!-- HEADER -->
<header>
  <a href="index.php" class="logo">
    <div class="logo-icon">📚</div>
    <div class="logo-text">Tugas<span>Ku</span></div>
  </a>
  <div class="header-right">
    <?php if ($isLoggedIn && $user): ?>
      <div class="user-badge">
        <?php if (!empty($user['picture'])): ?>
          <img src="<?= htmlspecialchars($user['picture']) ?>" alt="">
        <?php endif; ?>
        <?= htmlspecialchars($user['name']) ?>
      </div>
      <a href="logout.php" class="btn btn-ghost btn-sm">Keluar</a>
    <?php endif; ?>
  </div>
</header>

<main>

<?php if (!$isLoggedIn): ?>
<!-- ════ LOGIN SCREEN ════ -->
<div class="login-screen">
  <div class="login-hero">
    Bantai<br>Rasa <span class="highlight">Malas</span>mu<br>Disini.
  </div>
  <p class="login-sub">Web project iseng by 71project. Upload ke Google Drive, pantau deadline, dan bangun kebiasaan kerja tepat waktu.</p>
  <div class="login-quote">
    💬 "<?= $todayQuote ?>"
  </div>
  <div class="features">
    <div class="feature-chip">⏰ Countdown deadline</div>
    <div class="feature-chip">📤 Upload ke Google Drive</div>
    <div class="feature-chip">🔥 Streak tepat waktu</div>
    <div class="feature-chip">📊 Statistik tugas</div>
  </div>
  <a href="<?= $authUrl ?>" class="btn btn-google">
    <svg width="18" height="18" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
    Masuk dengan Google
  </a>
  <p style="margin-top:16px;font-size:12px;color:var(--muted)">
    Gratis selamanya. File kamu tersimpan di Google Drive milikmu sendiri.
  </p>
</div>

<?php else: ?>
<!-- ════ DASHBOARD ════ -->

<!-- Streak Banner -->
<?php if ($stats['streak'] >= 2): ?>
<div class="streak-banner">
  <div class="streak-fire">🔥</div>
  <div class="streak-info">
    <h3><?= $stats['streak'] ?> Streak Berturut-turut!</h3>
    <p>Kamu mengumpulkan <?= $stats['streak'] ?> tugas tepat waktu. Jangan putuskan streak ini!</p>
  </div>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="stats-bar">
  <div class="stat-card streak">
    <div class="stat-value"><?= $stats['streak'] ?>🔥</div>
    <div class="stat-label">Streak Sekarang</div>
  </div>
  <div class="stat-card done">
    <div class="stat-value"><?= $stats['done'] ?></div>
    <div class="stat-label">Selesai</div>
  </div>
  <div class="stat-card pending">
    <div class="stat-value"><?= $stats['pending'] ?></div>
    <div class="stat-label">Belum Selesai</div>
  </div>
  <div class="stat-card late">
    <div class="stat-value"><?= $stats['ontime'] ?>/<?= $stats['total'] ?></div>
    <div class="stat-label">Tepat Waktu</div>
  </div>
</div>

<!-- Quote -->
<div class="quote-bar">
  <div class="quote-icon">💬</div>
  <div><?= $todayQuote ?></div>
</div>

<!-- Tabs & Add Button -->
<div class="section-header">
  <div class="tab-bar">
    <button class="tab-btn active" onclick="filterTasks('all')" id="tab-all">Semua (<?= count($tasks) ?>)</button>
    <button class="tab-btn" onclick="filterTasks('pending')" id="tab-pending">
      Belum (<?= $stats['pending'] ?>)
    </button>
    <button class="tab-btn" onclick="filterTasks('submitted')" id="tab-done">
      Selesai (<?= $stats['done'] ?>)
    </button>
  </div>
  <button class="btn btn-primary" onclick="openModal('add-modal')">
    + Tambah Tugas
  </button>
</div>

<!-- Task List -->
<div class="task-list" id="task-list">
<?php if (empty($tasks)): ?>
  <div class="empty-state">
    <div class="empty-icon">🎯</div>
    <h3>Belum ada tugas</h3>
    <p>Tambahkan tugas pertamamu dan mulai kejar deadline!</p>
  </div>
<?php else: ?>
  <?php foreach ($tasks as $task): 
    $dlStatus = getDeadlineStatus($task['deadline']);
    $isPending = $task['status'] === 'pending';
  ?>
  <div class="task-card <?= $task['status'] === 'submitted' ? 'submitted' : '' ?>"
       data-status="<?= $task['status'] ?>"
       data-id="<?= $task['id'] ?>">
    <div class="task-card-inner">
      <div class="priority-dot <?= $task['priority'] ?>"></div>
      <div class="task-body">
        <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
        <?php if ($task['subject']): ?>
          <div class="task-subject"><?= htmlspecialchars($task['subject']) ?></div>
        <?php endif; ?>
        <?php if ($task['description']): ?>
          <div class="task-desc"><?= htmlspecialchars($task['description']) ?></div>
        <?php endif; ?>
        
        <div class="task-footer">
          <?php if ($task['status'] === 'submitted'): ?>
            <div class="deadline-badge safe">
              ✅ Dikumpulkan <?= date('d M Y, H:i', strtotime($task['submitted_at'])) ?>
            </div>
            <?php if ($task['drive_link']): ?>
              <a href="<?= htmlspecialchars($task['drive_link']) ?>" target="_blank" class="btn btn-ghost btn-sm">
                📂 Lihat di Drive
              </a>
            <?php endif; ?>
            <span style="font-size:12px;color:var(--muted)"><?= $task['streak_note'] ?></span>
          <?php else: ?>
            <div class="deadline-badge <?= $dlStatus['class'] ?>">
              ⏰ <?= $dlStatus['label'] ?>
            </div>
            <span class="countdown" data-deadline="<?= $task['deadline'] ?>">
              <?= date('d M Y, H:i', strtotime($task['deadline'])) ?>
            </span>
          <?php endif; ?>

          <div class="task-actions">
            <?php if ($isPending): ?>
              <button class="btn btn-primary btn-sm" onclick="openUploadModal('<?= $task['id'] ?>', '<?= addslashes($task['title']) ?>')">
                📤 Kumpulkan
              </button>
            <?php endif; ?>
            <button class="btn btn-danger btn-sm" onclick="deleteTask('<?= $task['id'] ?>')">🗑</button>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
<?php endif; ?>
</div>

<?php endif; ?>
</main>

<!-- ════ MODALS ════ -->

<!-- Add Task Modal -->
<div class="modal-overlay" id="add-modal">
  <div class="modal">
    <div class="modal-title">✏️ Tambah Tugas Baru</div>

    <div class="form-group">
      <label class="form-label">Judul Tugas *</label>
      <input type="text" id="task-title" class="form-control" placeholder="Contoh: Laporan Praktikum Kimia">
    </div>
    <div class="form-group">
      <label class="form-label">Mata Pelajaran / Mata Kuliah</label>
      <input type="text" id="task-subject" class="form-control" placeholder="Contoh: Kimia Dasar">
    </div>
    <div class="form-group">
      <label class="form-label">Deskripsi (Opsional)</label>
      <textarea id="task-desc" class="form-control" rows="2" placeholder="Catatan tambahan..."></textarea>
    </div>
    <div class="form-group">
      <label class="form-label">Deadline *</label>
      <input type="datetime-local" id="task-deadline" class="form-control">
    </div>
    <div class="form-group">
      <label class="form-label">Prioritas</label>
      <div class="priority-selector">
        <button class="priority-btn active-high" data-prio="high" onclick="setPriority('high')">🔴 Tinggi</button>
        <button class="priority-btn" data-prio="medium" onclick="setPriority('medium')">🟡 Sedang</button>
        <button class="priority-btn" data-prio="low" onclick="setPriority('low')">⚪ Rendah</button>
      </div>
      <input type="hidden" id="task-priority" value="high">
    </div>

    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('add-modal')">Batal</button>
      <button class="btn btn-primary" onclick="addTask()">+ Tambah Tugas</button>
    </div>
  </div>
</div>

<!-- Upload Modal -->
<div class="modal-overlay" id="upload-modal">
  <div class="modal">
    <div class="modal-title">📤 Kumpulkan Tugas</div>
    <p id="upload-task-name" style="color:var(--muted);margin-bottom:20px;font-size:14px"></p>
    <input type="hidden" id="upload-task-id">

    <div class="upload-area" id="upload-area">
      <input type="file" id="upload-file" accept="*/*" onchange="onFileSelect(this)">
      <div class="upload-icon">📁</div>
      <div class="upload-text">Klik atau drag & drop file kesini</div>
      <div class="upload-text" style="font-size:12px;margin-top:4px">Semua format diterima, maks. 50MB</div>
      <div class="upload-filename" id="upload-filename"></div>
    </div>

    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('upload-modal')">Batal</button>
      <button class="btn btn-primary" onclick="submitTask()" id="submit-btn" disabled>
        🚀 Upload ke Drive
      </button>
    </div>
  </div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toast-container"></div>

<!-- Loading -->
<div class="loading-overlay" id="loading">
  <div class="spinner"></div>
</div>

<script>
// ══ STATE ══
let currentFilter = 'all';

// ══ MODAL ══
function openModal(id) {
  document.getElementById(id).classList.add('active');
  if (id === 'add-modal') {
    // Set default deadline: tomorrow same time
    const tmrw = new Date(Date.now() + 86400000);
    const fmt = tmrw.toISOString().slice(0,16);
    document.getElementById('task-deadline').value = fmt;
    document.getElementById('task-title').focus();
  }
}
function closeModal(id) {
  document.getElementById(id).classList.remove('active');
}
document.querySelectorAll('.modal-overlay').forEach(o => {
  o.addEventListener('click', e => { if (e.target === o) closeModal(o.id); });
});

// ══ PRIORITY ══
function setPriority(prio) {
  document.getElementById('task-priority').value = prio;
  document.querySelectorAll('.priority-btn').forEach(b => {
    b.className = 'priority-btn';
    if (b.dataset.prio === prio) b.classList.add('active-' + prio);
  });
}

// ══ ADD TASK ══
function addTask() {
  const title    = document.getElementById('task-title').value.trim();
  const subject  = document.getElementById('task-subject').value.trim();
  const desc     = document.getElementById('task-desc').value.trim();
  const deadline = document.getElementById('task-deadline').value;
  const priority = document.getElementById('task-priority').value;

  if (!title) { toast('Judul tugas tidak boleh kosong!', 'error'); return; }
  if (!deadline) { toast('Deadline tidak boleh kosong!', 'error'); return; }

  const fd = new FormData();
  fd.append('action', 'add_task');
  fd.append('title', title);
  fd.append('subject', subject);
  fd.append('description', desc);
  fd.append('deadline', deadline.replace('T', ' ') + ':00');
  fd.append('priority', priority);

  showLoading();
  fetch('api.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      hideLoading();
      if (data.success) {
        toast(data.message, 'success');
        closeModal('add-modal');
        setTimeout(() => location.reload(), 800);
      } else {
        toast(data.message, 'error');
      }
    })
    .catch(() => { hideLoading(); toast('Terjadi kesalahan', 'error'); });
}

// ══ DELETE TASK ══
function deleteTask(id) {
  if (!confirm('Yakin hapus tugas ini?')) return;
  const fd = new FormData();
  fd.append('action', 'delete_task');
  fd.append('id', id);
  fetch('api.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        document.querySelector(`[data-id="${id}"]`)?.remove();
        toast('Tugas dihapus', 'info');
      }
    });
}

// ══ UPLOAD ══
function openUploadModal(id, title) {
  document.getElementById('upload-task-id').value = id;
  document.getElementById('upload-task-name').textContent = '📝 ' + title;
  document.getElementById('upload-filename').textContent = '';
  document.getElementById('upload-file').value = '';
  document.getElementById('submit-btn').disabled = true;
  openModal('upload-modal');
}

function onFileSelect(input) {
  const file = input.files[0];
  if (file) {
    document.getElementById('upload-filename').textContent = '✓ ' + file.name;
    document.getElementById('submit-btn').disabled = false;
  }
}

const uploadArea = document.getElementById('upload-area');
if (uploadArea) {
  uploadArea.addEventListener('dragover', e => { e.preventDefault(); uploadArea.classList.add('drag-over'); });
  uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('drag-over'));
  uploadArea.addEventListener('drop', e => {
    e.preventDefault();
    uploadArea.classList.remove('drag-over');
    const file = e.dataTransfer.files[0];
    if (file) {
      const input = document.getElementById('upload-file');
      const dt = new DataTransfer();
      dt.items.add(file);
      input.files = dt.files;
      onFileSelect(input);
    }
  });
}

function submitTask() {
  const taskId = document.getElementById('upload-task-id').value;
  const file   = document.getElementById('upload-file').files[0];
  if (!file) { toast('Pilih file dulu!', 'error'); return; }

  const fd = new FormData();
  fd.append('action', 'submit_task');
  fd.append('task_id', taskId);
  fd.append('file', file);

  showLoading();
  fetch('api.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      hideLoading();
      if (data.success) {
        closeModal('upload-modal');
        toast(data.message, 'success');
        if (data.on_time) confetti();
        setTimeout(() => location.reload(), 1500);
      } else {
        toast(data.message || 'Upload gagal', 'error');
        if (data.reauth) setTimeout(() => location.href='index.php', 2000);
      }
    })
    .catch(() => { hideLoading(); toast('Terjadi kesalahan saat upload', 'error'); });
}

// ══ FILTER TABS ══
function filterTasks(filter) {
  currentFilter = filter;
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('tab-' + (filter === 'submitted' ? 'done' : filter === 'pending' ? 'pending' : 'all')).classList.add('active');
  document.querySelectorAll('.task-card').forEach(card => {
    const status = card.dataset.status;
    card.style.display = (filter === 'all' || filter === status) ? '' : 'none';
  });
}

// ══ COUNTDOWN ══
function updateCountdowns() {
  document.querySelectorAll('.countdown[data-deadline]').forEach(el => {
    const deadline = new Date(el.dataset.deadline.replace(' ','T'));
    const diff = deadline - Date.now();
    if (diff <= 0) {
      el.textContent = '⚠️ Sudah lewat deadline!';
      return;
    }
    const d = Math.floor(diff / 86400000);
    const h = Math.floor((diff % 86400000) / 3600000);
    const m = Math.floor((diff % 3600000) / 60000);
    const s = Math.floor((diff % 60000) / 1000);
    if (d > 0) el.textContent = `${d}h ${h}j ${m}m lagi`;
    else if (h > 0) el.textContent = `${h}j ${m}m ${s}d lagi`;
    else el.textContent = `${m}m ${s}d lagi`;
  });
}
setInterval(updateCountdowns, 1000);
updateCountdowns();

// ══ TOAST ══
function toast(msg, type = 'info') {
  const c = document.getElementById('toast-container');
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  const icons = { success:'✅', error:'❌', info:'ℹ️' };
  t.innerHTML = `<span>${icons[type]||''}</span><span>${msg}</span>`;
  c.appendChild(t);
  setTimeout(() => t.remove(), 4000);
}

// ══ LOADING ══
function showLoading() { document.getElementById('loading').classList.add('active'); }
function hideLoading()  { document.getElementById('loading').classList.remove('active'); }

// ══ CONFETTI (tepat waktu) ══
function confetti() {
  const colors = ['#e8ff47','#47ff9c','#47c8ff','#ff6b47'];
  for (let i = 0; i < 60; i++) {
    const el = document.createElement('div');
    el.style.cssText = `
      position:fixed;top:-10px;
      left:${Math.random()*100}vw;
      width:${6+Math.random()*6}px;height:${6+Math.random()*6}px;
      background:${colors[Math.floor(Math.random()*colors.length)]};
      border-radius:${Math.random()>0.5?'50%':'2px'};
      z-index:9998;pointer-events:none;
      animation:fall ${1+Math.random()*2}s ease forwards;
    `;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 3000);
  }
  const style = document.createElement('style');
  style.textContent = `@keyframes fall { to { transform: translateY(110vh) rotate(720deg); opacity:0; } }`;
  document.head.appendChild(style);
}

// Enter key on modal
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.active').forEach(m => m.classList.remove('active'));
});
</script>
</body>
</html>
