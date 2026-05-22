<?php
include 'config.php';

// ===== Date filter =====
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to   = $_GET['date_to']   ?? date('Y-m-d');

// sanitize
$date_from = preg_replace('/[^0-9\-]/', '', $date_from);
$date_to   = preg_replace('/[^0-9\-]/', '', $date_to);

// ===== 1. ผู้เข้าชมทั้งหมด (ช่วงวันที่) =====
$r = mysqli_query($conn, "SELECT COUNT(*) AS total FROM visitor_log
    WHERE DATE(visited_at) BETWEEN '$date_from' AND '$date_to'");
$total_visitors = mysqli_fetch_assoc($r)['total'];

// ===== 2. ผู้เข้าชมเมื่อวาน (เปรียบเทียบ) =====
$yesterday_from = date('Y-m-d', strtotime($date_from . ' -1 day'));
$yesterday_to   = date('Y-m-d', strtotime($date_to   . ' -1 day'));
$r2 = mysqli_query($conn, "SELECT COUNT(*) AS total FROM visitor_log
    WHERE DATE(visited_at) BETWEEN '$yesterday_from' AND '$yesterday_to'");
$prev_visitors = mysqli_fetch_assoc($r2)['total'];
$visitor_diff  = $prev_visitors > 0
    ? round(($total_visitors - $prev_visitors) / $prev_visitors * 100, 1)
    : ($total_visitors > 0 ? 100 : 0);

// ===== 3. กราฟผู้เข้าชมรายวัน (ช่วงที่เลือก) =====
$sql_daily = "SELECT DATE(visited_at) AS d, COUNT(*) AS c
    FROM visitor_log
    WHERE DATE(visited_at) BETWEEN '$date_from' AND '$date_to'
    GROUP BY DATE(visited_at)
    ORDER BY d ASC";
$r_daily = mysqli_query($conn, $sql_daily);
$daily_labels = [];
$daily_data   = [];
while ($row = mysqli_fetch_assoc($r_daily)) {
    $daily_labels[] = date('d/m', strtotime($row['d']));
    $daily_data[]   = (int)$row['c'];
}

// ===== 4. เพศ =====
$r_gender = mysqli_query($conn, "SELECT gender, COUNT(*) AS c FROM visitor_log
    WHERE DATE(visited_at) BETWEEN '$date_from' AND '$date_to'
    GROUP BY gender");
$gender_labels = [];
$gender_data   = [];
while ($row = mysqli_fetch_assoc($r_gender)) {
    $map = ['male' => 'ชาย', 'female' => 'หญิง', 'unspecified' => 'LGBTQ+'];
    $gender_labels[] = $map[$row['gender']] ?? $row['gender'];
    $gender_data[]   = (int)$row['c'];
}

// ===== 5. ช่วงอายุ =====
$r_age = mysqli_query($conn, "SELECT age_range, COUNT(*) AS c FROM visitor_log
    WHERE DATE(visited_at) BETWEEN '$date_from' AND '$date_to'
    GROUP BY age_range ORDER BY FIELD(age_range,'15-25','26-35','36-45','46-55','56-65','65+')");
$age_labels = [];
$age_data   = [];
while ($row = mysqli_fetch_assoc($r_age)) {
    $age_labels[] = $row['age_range'];
    $age_data[]   = (int)$row['c'];
}

// ===== 6. Top 5 สถานที่ =====
$r_place = mysqli_query($conn, "SELECT p.place_name, COUNT(v.view_id) AS views
    FROM place_view_log v
    JOIN place p ON p.place_id = v.place_id
    WHERE DATE(v.viewed_at) BETWEEN '$date_from' AND '$date_to'
    GROUP BY v.place_id
    ORDER BY views DESC
    LIMIT 5");
$place_labels = [];
$place_data   = [];
while ($row = mysqli_fetch_assoc($r_place)) {
    $place_labels[] = $row['place_name'];
    $place_data[]   = (int)$row['views'];
}

// ===== 7. จำนวนการเข้าชม place ทั้งหมดในช่วง =====
$r_pv = mysqli_query($conn, "SELECT COUNT(*) AS total FROM place_view_log
    WHERE DATE(viewed_at) BETWEEN '$date_from' AND '$date_to'");
$total_place_views = mysqli_fetch_assoc($r_pv)['total'];

// ===== 8. หน้าที่เข้าชมสูงสุด (เดือนก่อน เทียบ) =====
$r_prev_pv = mysqli_query($conn, "SELECT COUNT(*) AS total FROM place_view_log
    WHERE DATE(viewed_at) BETWEEN '$yesterday_from' AND '$yesterday_to'");
$prev_place_views = mysqli_fetch_assoc($r_prev_pv)['total'];
$pv_diff = $prev_place_views > 0
    ? round(($total_place_views - $prev_place_views) / $prev_place_views * 100, 1)
    : ($total_place_views > 0 ? 100 : 0);

// ===== 9. สัดส่วน category (travel vs eat) =====
$r_cat = mysqli_query($conn, "SELECT p.category, COUNT(v.view_id) AS c
    FROM place_view_log v
    JOIN place p ON p.place_id = v.place_id
    WHERE DATE(v.viewed_at) BETWEEN '$date_from' AND '$date_to'
    GROUP BY p.category");
$cat_labels = [];
$cat_data   = [];
while ($row = mysqli_fetch_assoc($r_cat)) {
    $cat_labels[] = $row['category'] === 'travel' ? 'ที่เที่ยว' : 'ที่กิน';
    $cat_data[]   = (int)$row['c'];
}

// encode JSON สำหรับ Chart.js
$json = fn($v) => json_encode($v, JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>รายงานสารสนเทศ — THAPHET</title>
<link href="https://fonts.googleapis.com/css2?family=Mitr:wght@300;400;500;600&family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<style>
/* ===== RESET & BASE ===== */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --green-dark:  #0a5c2e;
  --green-mid:   #0f7a3c;
  --green-light: #e8f5ee;
  --green-pale:  #f2faf5;
  --accent:      #f5a623;
  --accent2:     #e84545;
  --text-main:   #1a2e1f;
  --text-muted:  #5a7060;
  --border:      #d0e8d8;
  --white:       #ffffff;
  --card-shadow: 0 2px 12px rgba(10,92,46,.08);
}

body {
  font-family: 'Sarabun', sans-serif;
  background: #f0f7f3;
  color: var(--text-main);
  min-height: 100vh;
}

/* ===== TOPBAR ===== */
.topbar {
  background: var(--green-dark);
  padding: 14px 32px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: sticky;
  top: 0;
  z-index: 100;
  box-shadow: 0 2px 10px rgba(0,0,0,.25);
}
.topbar-logo {
  font-family: 'Mitr', sans-serif;
  font-size: 1.3rem;
  font-weight: 600;
  color: #fff;
  letter-spacing: .04em;
  display: flex;
  align-items: center;
  gap: 10px;
}
.topbar-logo span { color: #7edea8; }
.topbar-back {
  font-family: 'Mitr', sans-serif;
  font-size: .85rem;
  color: rgba(255,255,255,.75);
  text-decoration: none;
  border: 1px solid rgba(255,255,255,.3);
  padding: 6px 14px;
  border-radius: 20px;
  transition: all .2s;
}
.topbar-back:hover { background: rgba(255,255,255,.15); color: #fff; }

/* ===== PAGE WRAPPER ===== */
.page { max-width: 1280px; margin: 0 auto; padding: 28px 24px 60px; }

/* ===== PAGE HEADER ===== */
.page-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 28px;
}
.page-title {
  font-family: 'Mitr', sans-serif;
  font-size: 1.6rem;
  font-weight: 600;
  color: var(--green-dark);
}
.page-sub {
  font-size: .9rem;
  color: var(--text-muted);
  margin-top: 4px;
}
.export-btns { display: flex; gap: 10px; }
.btn-export {
  font-family: 'Mitr', sans-serif;
  font-size: .8rem;
  font-weight: 500;
  padding: 8px 18px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  text-decoration: none;
  transition: filter .2s;
}
.btn-export:hover { filter: brightness(1.08); }
.btn-excel { background: #217346; color: #fff; }
.btn-pdf   { background: #c0392b; color: #fff; }
.btn-print { background: var(--green-dark); color: #fff; }

/* ===== FILTER CARD ===== */
.filter-card {
  background: var(--white);
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 18px 24px;
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 28px;
  box-shadow: var(--card-shadow);
}
.filter-label {
  font-family: 'Mitr', sans-serif;
  font-size: .85rem;
  color: var(--text-muted);
  display: flex;
  align-items: center;
  gap: 6px;
}
.filter-label svg { width: 16px; height: 16px; }
.filter-group { display: flex; align-items: center; gap: 8px; }
.filter-group label { font-size: .82rem; color: var(--text-muted); }
.filter-group input[type="date"] {
  font-family: 'Sarabun', sans-serif;
  font-size: .85rem;
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 7px 10px;
  color: var(--text-main);
  outline: none;
  transition: border .2s;
}
.filter-group input[type="date"]:focus { border-color: var(--green-mid); }
.btn-filter {
  font-family: 'Mitr', sans-serif;
  font-size: .85rem;
  font-weight: 500;
  background: var(--green-mid);
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 8px 20px;
  cursor: pointer;
  transition: background .2s;
}
.btn-filter:hover { background: var(--green-dark); }
.quick-btns { display: flex; gap: 6px; margin-left: auto; }
.btn-quick {
  font-family: 'Mitr', sans-serif;
  font-size: .75rem;
  background: var(--green-light);
  color: var(--green-dark);
  border: 1px solid var(--border);
  border-radius: 6px;
  padding: 5px 10px;
  cursor: pointer;
  text-decoration: none;
  transition: all .2s;
}
.btn-quick:hover { background: var(--green-dark); color: #fff; }
.range-label {
  font-family: 'Mitr', sans-serif;
  font-size: .8rem;
  color: var(--green-dark);
  background: var(--green-light);
  border: 1px solid var(--border);
  border-radius: 6px;
  padding: 4px 10px;
}

/* ===== STAT CARDS ===== */
.stat-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 28px;
}
.stat-card {
  background: var(--white);
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 20px 22px;
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: var(--card-shadow);
  transition: transform .2s;
}
.stat-card:hover { transform: translateY(-2px); }
.stat-icon {
  width: 46px;
  height: 46px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.3rem;
  flex-shrink: 0;
}
.ic-green  { background: var(--green-light); }
.ic-amber  { background: #fff5e0; }
.ic-blue   { background: #e8f0fb; }
.ic-red    { background: #fce8e8; }
.stat-body {}
.stat-num {
  font-family: 'Mitr', sans-serif;
  font-size: 1.9rem;
  font-weight: 600;
  color: var(--text-main);
  line-height: 1;
}
.stat-name {
  font-size: .8rem;
  color: var(--text-muted);
  margin-top: 3px;
}
.stat-diff {
  font-size: .75rem;
  font-weight: 600;
  margin-top: 4px;
  display: inline-flex;
  align-items: center;
  gap: 3px;
}
.diff-up   { color: #0f7a3c; }
.diff-down { color: #c0392b; }
.diff-flat { color: #888; }

/* ===== CHART GRID ===== */
.chart-grid-top {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 20px;
}
.chart-grid-bot {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr;
  gap: 20px;
  margin-bottom: 20px;
}
.chart-card {
  background: var(--white);
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 22px 24px;
  box-shadow: var(--card-shadow);
}
.chart-card.full { grid-column: 1 / -1; }
.chart-title {
  font-family: 'Mitr', sans-serif;
  font-size: .95rem;
  font-weight: 500;
  color: var(--green-dark);
  margin-bottom: 4px;
}
.chart-sub {
  font-size: .78rem;
  color: var(--text-muted);
  margin-bottom: 16px;
}
.chart-wrap { position: relative; }
.chart-empty {
  text-align: center;
  color: var(--text-muted);
  font-size: .85rem;
  padding: 40px 0;
}

/* ===== FOOTER ===== */
.report-footer {
  margin-top: 40px;
  border-top: 1px solid var(--border);
  padding-top: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
}
.report-footer span { font-size: .78rem; color: var(--text-muted); }

/* ===== PRINT ===== */
@media print {
  .topbar, .filter-card, .export-btns, .quick-btns, .btn-print { display: none !important; }
  .page { padding: 10px; }
  body { background: #fff; }
  .chart-grid-top, .chart-grid-bot { grid-template-columns: 1fr 1fr; }
}

/* ===== RESPONSIVE ===== */
@media (max-width: 900px) {
  .chart-grid-top, .chart-grid-bot { grid-template-columns: 1fr; }
}
@media (max-width: 600px) {
  .page { padding: 16px 12px 40px; }
  .topbar { padding: 12px 16px; }
  .filter-card { flex-direction: column; align-items: flex-start; }
  .quick-btns { margin-left: 0; }
}
</style>
</head>
<body>

<!-- TOPBAR -->
<header class="topbar">
  <div class="topbar-logo">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#7edea8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
    THAPHET <span>/ รายงานสารสนเทศ</span>
  </div>
  <a href="index.php" class="topbar-back">← กลับหน้าหลัก</a>
</header>

<div class="page">

  <!-- PAGE HEADER -->
  <div class="page-header">
    <div>
      <div class="page-title">รายงานสารสนเทศ</div>
      <div class="page-sub">ข้อมูลผู้เข้าชม · สถานที่ · พฤติกรรม | THAPHET — อำเภอท่ายาง จ.เพชรบุรี</div>
    </div>
    <div class="export-btns">
      <button class="btn-export btn-print" onclick="window.print()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        Print
      </button>
    </div>
  </div>

  <!-- FILTER -->
  <form class="filter-card" method="GET">
    <span class="filter-label">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      ช่วงวันที่
    </span>
    <div class="filter-group">
      <label>จาก</label>
      <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
    </div>
    <div class="filter-group">
      <label>ถึง</label>
      <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
    </div>
    <button type="submit" class="btn-filter">แสดงข้อมูล</button>
    <div class="quick-btns">
      <a class="btn-quick" href="?date_from=<?= date('Y-m-d') ?>&date_to=<?= date('Y-m-d') ?>">วันนี้</a>
      <a class="btn-quick" href="?date_from=<?= date('Y-m-d', strtotime('-6 days')) ?>&date_to=<?= date('Y-m-d') ?>">7 วัน</a>
      <a class="btn-quick" href="?date_from=<?= date('Y-m-d', strtotime('-29 days')) ?>&date_to=<?= date('Y-m-d') ?>">30 วัน</a>
      <a class="btn-quick" href="?date_from=<?= date('Y-m-01') ?>&date_to=<?= date('Y-m-d') ?>">เดือนนี้</a>
    </div>
    <span class="range-label"><?= date('d/m/Y', strtotime($date_from)) ?> — <?= date('d/m/Y', strtotime($date_to)) ?></span>
  </form>

  <!-- STAT CARDS -->
  <div class="stat-grid">
    <div class="stat-card">
      <div class="stat-icon ic-green">👥</div>
      <div class="stat-body">
        <div class="stat-num"><?= number_format($total_visitors) ?></div>
        <div class="stat-name">ผู้เข้าชมทั้งหมด</div>
        <?php if ($visitor_diff > 0): ?>
          <div class="stat-diff diff-up">▲ <?= $visitor_diff ?>% จากช่วงก่อน</div>
        <?php elseif ($visitor_diff < 0): ?>
          <div class="stat-diff diff-down">▼ <?= abs($visitor_diff) ?>% จากช่วงก่อน</div>
        <?php else: ?>
          <div class="stat-diff diff-flat">— เท่าเดิม</div>
        <?php endif; ?>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon ic-amber">📍</div>
      <div class="stat-body">
        <div class="stat-num"><?= number_format($total_place_views) ?></div>
        <div class="stat-name">การเข้าชมสถานที่</div>
        <?php if ($pv_diff > 0): ?>
          <div class="stat-diff diff-up">▲ <?= $pv_diff ?>% จากช่วงก่อน</div>
        <?php elseif ($pv_diff < 0): ?>
          <div class="stat-diff diff-down">▼ <?= abs($pv_diff) ?>% จากช่วงก่อน</div>
        <?php else: ?>
          <div class="stat-diff diff-flat">— เท่าเดิม</div>
        <?php endif; ?>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon ic-blue">📊</div>
      <div class="stat-body">
        <div class="stat-num"><?= count($place_labels) > 0 ? $place_labels[0] : '—' ?></div>
        <div class="stat-name">สถานที่ยอดนิยมอันดับ 1</div>
        <?php if (!empty($place_data)): ?>
          <div class="stat-diff diff-flat"><?= $place_data[0] ?> ครั้ง</div>
        <?php endif; ?>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon ic-red">🎂</div>
      <div class="stat-body">
        <?php
          $top_age_idx = !empty($age_data) ? array_search(max($age_data), $age_data) : -1;
        ?>
        <div class="stat-num"><?= $top_age_idx >= 0 ? $age_labels[$top_age_idx] : '—' ?></div>
        <div class="stat-name">กลุ่มอายุที่มากที่สุด</div>
        <?php if ($top_age_idx >= 0): ?>
          <div class="stat-diff diff-flat">
            <?= round($age_data[$top_age_idx] / max(1, array_sum($age_data)) * 100, 1) ?>% ของผู้เข้าชม
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ROW 1: กราฟรายวัน (full width) -->
  <div style="margin-bottom:20px">
    <div class="chart-card">
      <div class="chart-title">ผู้เข้าชมรายวัน</div>
      <div class="chart-sub">จำนวนผู้เข้าชมแยกตามวัน ในช่วง <?= date('d/m/Y', strtotime($date_from)) ?> — <?= date('d/m/Y', strtotime($date_to)) ?></div>
      <?php if (empty($daily_labels)): ?>
        <div class="chart-empty">ไม่พบข้อมูลในช่วงวันที่เลือก</div>
      <?php else: ?>
        <div class="chart-wrap" style="height:240px"><canvas id="chartDaily"></canvas></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ROW 2: Top5 + เพศ -->
  <div class="chart-grid-top">
    <div class="chart-card">
      <div class="chart-title">สถานที่ที่มีผู้เข้าชมมากสุด</div>
      <div class="chart-sub">Top 5 ในช่วงวันที่เลือก</div>
      <?php if (empty($place_labels)): ?>
        <div class="chart-empty">ไม่พบข้อมูลในช่วงวันที่เลือก</div>
      <?php else: ?>
        <div class="chart-wrap" style="height:240px"><canvas id="chartPlace"></canvas></div>
      <?php endif; ?>
    </div>
    <div class="chart-card">
      <div class="chart-title">เพศของผู้เข้าชม</div>
      <div class="chart-sub">จากข้อมูลแบบสอบถาม (<?= number_format($total_visitors) ?> คน)</div>
      <?php if (empty($gender_labels)): ?>
        <div class="chart-empty">ไม่พบข้อมูลในช่วงวันที่เลือก</div>
      <?php else: ?>
        <div class="chart-wrap" style="height:240px"><canvas id="chartGender"></canvas></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ROW 3: ช่วงอายุ + ประเภทสถานที่ -->
  <div class="chart-grid-bot">
    <div class="chart-card">
      <div class="chart-title">ช่วงอายุของผู้เข้าชม</div>
      <div class="chart-sub">จากข้อมูลแบบสอบถาม ในช่วงวันที่เลือก</div>
      <?php if (empty($age_labels)): ?>
        <div class="chart-empty">ไม่พบข้อมูลในช่วงวันที่เลือก</div>
      <?php else: ?>
        <div class="chart-wrap" style="height:220px"><canvas id="chartAge"></canvas></div>
      <?php endif; ?>
    </div>
    <div class="chart-card">
      <div class="chart-title">ประเภทสถานที่</div>
      <div class="chart-sub">ที่เที่ยว vs ที่กิน</div>
      <?php if (empty($cat_labels)): ?>
        <div class="chart-empty">ไม่พบข้อมูล</div>
      <?php else: ?>
        <div class="chart-wrap" style="height:220px"><canvas id="chartCat"></canvas></div>
      <?php endif; ?>
    </div>
    <!-- Summary box -->
    <div class="chart-card" style="display:flex;flex-direction:column;justify-content:center;gap:14px">
      <div class="chart-title">สรุปภาพรวม</div>
      <?php
        $top_gender_idx = !empty($gender_data) ? array_search(max($gender_data), $gender_data) : -1;
        $top_cat_idx    = !empty($cat_data)    ? array_search(max($cat_data),    $cat_data)    : -1;
      ?>
      <div style="font-size:.82rem;line-height:2;color:var(--text-muted)">
        <div>📅 ช่วงเวลา <strong style="color:var(--text-main)"><?= date('d/m/Y', strtotime($date_from)) ?> — <?= date('d/m/Y', strtotime($date_to)) ?></strong></div>
        <div>👥 ผู้เข้าชมรวม <strong style="color:var(--green-dark)"><?= number_format($total_visitors) ?> คน</strong></div>
        <div>📍 การเข้าชมสถานที่ <strong style="color:var(--green-dark)"><?= number_format($total_place_views) ?> ครั้ง</strong></div>
        <?php if ($top_gender_idx >= 0): ?>
          <div>⚧ เพศหลัก <strong style="color:var(--text-main)"><?= $gender_labels[$top_gender_idx] ?></strong>
            (<?= round($gender_data[$top_gender_idx] / max(1, array_sum($gender_data)) * 100, 1) ?>%)</div>
        <?php endif; ?>
        <?php if ($top_age_idx >= 0): ?>
          <div>🎂 อายุหลัก <strong style="color:var(--text-main)"><?= $age_labels[$top_age_idx] ?> ปี</strong>
            (<?= round($age_data[$top_age_idx] / max(1, array_sum($age_data)) * 100, 1) ?>%)</div>
        <?php endif; ?>
        <?php if (!empty($place_labels)): ?>
          <div>🏆 สถานที่ยอดนิยม <strong style="color:var(--text-main)"><?= $place_labels[0] ?></strong></div>
        <?php endif; ?>
        <?php if ($top_cat_idx >= 0): ?>
          <div>🗂 ประเภทยอดนิยม <strong style="color:var(--text-main)"><?= $cat_labels[$top_cat_idx] ?></strong></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- FOOTER -->
  <div class="report-footer">
    <span>THAPHET Information Report</span>
    <span>สร้างเมื่อ <?= date('d/m/Y H:i') ?></span>
  </div>

</div>

<script>
const green  = '#0f7a3c';
const green2 = '#4caf82';
const amber  = '#f5a623';
const blue   = '#3b82f6';
const red    = '#e84545';
const muted  = '#d0e8d8';

Chart.defaults.font.family = "'Sarabun', sans-serif";
Chart.defaults.font.size   = 12;
Chart.defaults.color       = '#5a7060';

// ---- กราฟรายวัน ----
<?php if (!empty($daily_labels)): ?>
new Chart(document.getElementById('chartDaily'), {
  type: 'bar',
  data: {
    labels: <?= $json($daily_labels) ?>,
    datasets: [{
      label: 'ผู้เข้าชม',
      data: <?= $json($daily_data) ?>,
      backgroundColor: '<?= $green2 ?>',
      borderRadius: 6,
      borderSkipped: false,
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { display: false } },
      y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: muted } }
    }
  }
});
<?php endif; ?>

// ---- Top 5 สถานที่ (แนวนอน) ----
<?php if (!empty($place_labels)): ?>
new Chart(document.getElementById('chartPlace'), {
  type: 'bar',
  data: {
    labels: <?= $json($place_labels) ?>,
    datasets: [{
      label: 'ครั้ง',
      data: <?= $json($place_data) ?>,
      backgroundColor: ['<?= $green ?>', green2, amber, blue, red],
      borderRadius: 6,
      borderSkipped: false,
    }]
  },
  options: {
    indexAxis: 'y',
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      x: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: muted } },
      y: { grid: { display: false } }
    }
  }
});
<?php endif; ?>

// ---- เพศ (Doughnut) ----
<?php if (!empty($gender_labels)): ?>
new Chart(document.getElementById('chartGender'), {
  type: 'doughnut',
  data: {
    labels: <?= $json($gender_labels) ?>,
    datasets: [{
      data: <?= $json($gender_data) ?>,
      backgroundColor: [blue, red, amber],
      borderWidth: 2, borderColor: '#fff',
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    cutout: '60%',
    plugins: {
      legend: { position: 'bottom', labels: { boxWidth: 12, padding: 10 } }
    }
  }
});
<?php endif; ?>

// ---- ช่วงอายุ ----
<?php if (!empty($age_labels)): ?>
new Chart(document.getElementById('chartAge'), {
  type: 'bar',
  data: {
    labels: <?= $json($age_labels) ?>,
    datasets: [{
      label: 'คน',
      data: <?= $json($age_data) ?>,
      backgroundColor: green2,
      borderRadius: 6,
      borderSkipped: false,
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { display: false } },
      y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: muted } }
    }
  }
});
<?php endif; ?>

// ---- ประเภทสถานที่ (Pie) ----
<?php if (!empty($cat_labels)): ?>
new Chart(document.getElementById('chartCat'), {
  type: 'pie',
  data: {
    labels: <?= $json($cat_labels) ?>,
    datasets: [{
      data: <?= $json($cat_data) ?>,
      backgroundColor: [green, amber],
      borderWidth: 2, borderColor: '#fff',
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: {
      legend: { position: 'bottom', labels: { boxWidth: 12, padding: 10 } }
    }
  }
});
<?php endif; ?>
</script>

</body>
</html>