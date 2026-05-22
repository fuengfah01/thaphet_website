<?php
// ===== Handle AJAX date-range request (ต้องอยู่ก่อน include header.php) =====
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    include '../config.php';
    header('Content-Type: application/json; charset=utf-8');

    // ── get_min_date shortcut ──
    if (isset($_GET['get_min_date'])) {
        $res = mysqli_query($conn, "SELECT MIN(DATE(visited_at)) AS min_date FROM visitor_log");
        $row = mysqli_fetch_assoc($res);
        echo json_encode(['min_date' => $row['min_date'] ?? date('Y-m-d')]);
        exit;
    }

    $date_from = isset($_GET['date_from']) ? mysqli_real_escape_string($conn, $_GET['date_from']) : date('Y-m-d', strtotime('-6 days'));
    $date_to   = isset($_GET['date_to'])   ? mysqli_real_escape_string($conn, $_GET['date_to'])   : date('Y-m-d');

    // ── Visitor per day in range ──────────────────────────────
    $v_labels = [];
    $v_data   = [];
    $day_th   = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัส', 'ศุกร์', 'เสาร์'];
    $cur = strtotime($date_from);
    $end = strtotime($date_to);
    while ($cur <= $end) {
        $d    = date('Y-m-d', $cur);
        $dow  = (int)date('w', $cur);
        $v_labels[] = $day_th[$dow] . ' ' . date('d/m', $cur);
        $res  = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM visitor_log WHERE DATE(visited_at) = '$d'");
        $v_data[] = (int)(mysqli_fetch_assoc($res)['cnt'] ?? 0);
        $cur  = strtotime('+1 day', $cur);
    }

    // ── Top 5 places in range ─────────────────────────────────
    $top_res = mysqli_query($conn, "
        SELECT p.place_name, COUNT(*) AS view_count
        FROM place_view_log pvl
        JOIN place p ON p.place_id = pvl.place_id
        WHERE DATE(pvl.viewed_at) BETWEEN '$date_from' AND '$date_to'
        GROUP BY pvl.place_id ORDER BY view_count DESC LIMIT 5
    ");
    $tp_labels = []; $tp_data = [];
    while ($row = mysqli_fetch_assoc($top_res)) {
        $tp_labels[] = $row['place_name'];
        $tp_data[]   = (int)$row['view_count'];
    }
    if (empty($tp_labels)) {
        $fb = mysqli_query($conn, "SELECT place_name FROM place ORDER BY place_id DESC LIMIT 5");
        while ($row = mysqli_fetch_assoc($fb)) { $tp_labels[] = $row['place_name']; $tp_data[] = 0; }
    }

    // ── Age in range ──────────────────────────────────────────
    $age_ranges = ['15-25', '26-35', '36-45', '46-55', '56-65', '65+'];
    $age_tot_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM visitor_log WHERE DATE(visited_at) BETWEEN '$date_from' AND '$date_to'");
    $age_total   = max(1, (int)(mysqli_fetch_assoc($age_tot_res)['total'] ?? 0));
    $age_arr = [];
    foreach ($age_ranges as $r) {
        $res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM visitor_log WHERE age_range = '$r' AND DATE(visited_at) BETWEEN '$date_from' AND '$date_to'");
        $cnt = (int)(mysqli_fetch_assoc($res)['cnt'] ?? 0);
        $age_arr[] = ['range' => $r, 'count' => $cnt, 'pct' => round($cnt / $age_total * 100)];
    }

    // ── Gender in range ───────────────────────────────────────
    $g_labels = []; $g_data = [];
    $g_res = mysqli_query($conn, "SELECT gender, COUNT(*) AS cnt FROM visitor_log WHERE DATE(visited_at) BETWEEN '$date_from' AND '$date_to' GROUP BY gender ORDER BY cnt DESC");
    while ($grow = mysqli_fetch_assoc($g_res)) {
        $lbl = match(strtolower($grow['gender'])) {
            'male'        => 'เพศชาย',
            'female'      => 'เพศหญิง',
            'lgbtq+'      => 'LGBTQ+',
            'unspecified' => 'ไม่ระบุ',
            default       => $grow['gender']
        };
        $g_labels[] = $lbl;
        $g_data[]   = (int)$grow['cnt'];
    }

    // ── Total visitors in range ───────────────────────────────
    $total_v_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM visitor_log WHERE DATE(visited_at) BETWEEN '$date_from' AND '$date_to'");
    $total_v = (int)(mysqli_fetch_assoc($total_v_res)['total'] ?? 0);

    // ── % เปรียบเทียบ (เทียบกับช่วงก่อนหน้าที่มีความยาวเท่ากัน) ──
    $range_days  = max(1, (int)((strtotime($date_to) - strtotime($date_from)) / 86400) + 1);
    $prev_to     = date('Y-m-d', strtotime($date_from . ' -1 day'));
    $prev_from   = date('Y-m-d', strtotime($prev_to   . ' -' . ($range_days - 1) . ' days'));

    $prev_v_res  = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM visitor_log WHERE DATE(visited_at) BETWEEN '$prev_from' AND '$prev_to'");
    $prev_v      = (int)(mysqli_fetch_assoc($prev_v_res)['cnt'] ?? 0);
    $visitor_pct = ($prev_v > 0) ? round(($total_v - $prev_v) / $prev_v * 100) : ($total_v > 0 ? 100 : 0);

    echo json_encode([
        'visitor_labels'    => $v_labels,
        'visitor_data'      => $v_data,
        'place_labels'      => $tp_labels,
        'place_data'        => $tp_data,
        'age'               => $age_arr,
        'age_total'         => $age_total,
        'gender_labels'     => $g_labels,
        'gender_data'       => $g_data,
        'gender_total'      => array_sum($g_data),
        'total_visitor'     => $total_v,
        'visitor_pct'       => $visitor_pct,
        'prev_from'         => $prev_from,
        'prev_to'           => $prev_to,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== โหลดปกติ (ไม่ใช่ AJAX) =====
include 'check_login.php';
include '../config.php';
include 'header.php';

// ===== Static counts (ไม่มีวันที่ — แสดงตลอดกาล) =====
$place_count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM place");
$place_count = mysqli_fetch_assoc($place_count_result)['total'] ?? 0;

$content_count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM content");
$content_count = mysqli_fetch_assoc($content_count_result)['total'] ?? 0;

$chatbot_count_res = mysqli_query($conn, "
SELECT
(SELECT COUNT(*) FROM place) +
(SELECT COUNT(*) FROM restaurant) +
(SELECT COUNT(*) FROM activity) +
(SELECT COUNT(*) FROM souvenir_shop) +
(SELECT COUNT(*) FROM about_us) AS total
");
$chatbot_count = mysqli_fetch_assoc($chatbot_count_res)['total'] ?? 0;

// ===== Default range: 7 วันย้อนหลัง =====
$default_from = date('Y-m-d', strtotime('-6 days'));
$default_to   = date('Y-m-d');

// ===== ผู้เข้าชม 7 วัน (initial load) =====
$visitor_data   = [];
$visitor_labels = [];
$day_th_arr = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัส', 'ศุกร์', 'เสาร์'];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dow  = (int)date('w', strtotime($date));
    $visitor_labels[] = $day_th_arr[$dow] . ' ' . date('d/m', strtotime($date));
    $res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM visitor_log WHERE DATE(visited_at) = '$date'");
    $visitor_data[] = (int)(mysqli_fetch_assoc($res)['cnt'] ?? 0);
}

// ===== Top 5 สถานที่ (7 วัน) =====
$top_places_result = mysqli_query($conn, "
    SELECT p.place_name, COUNT(*) AS view_count
    FROM place_view_log pvl
    JOIN place p ON p.place_id = pvl.place_id
    WHERE DATE(pvl.viewed_at) BETWEEN '$default_from' AND '$default_to'
    GROUP BY pvl.place_id ORDER BY view_count DESC LIMIT 5
");
$top_place_labels = []; $top_place_data = [];
while ($row = mysqli_fetch_assoc($top_places_result)) {
    $top_place_labels[] = $row['place_name'];
    $top_place_data[]   = (int)$row['view_count'];
}
if (empty($top_place_labels)) {
    $fallback = mysqli_query($conn, "SELECT place_name FROM place ORDER BY place_id DESC LIMIT 5");
    while ($row = mysqli_fetch_assoc($fallback)) { $top_place_labels[] = $row['place_name']; $top_place_data[] = 0; }
}

// ===== ช่วงอายุ (7 วัน) =====
$age_ranges  = ['15-25', '26-35', '36-45', '46-55', '56-65', '65+'];
$age_counts  = [];
$age_total_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM visitor_log WHERE DATE(visited_at) BETWEEN '$default_from' AND '$default_to'");
$age_total   = max(1, (int)(mysqli_fetch_assoc($age_total_res)['total'] ?? 1));
foreach ($age_ranges as $range) {
    $res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM visitor_log WHERE age_range = '$range' AND DATE(visited_at) BETWEEN '$default_from' AND '$default_to'");
    $age_counts[$range] = (int)(mysqli_fetch_assoc($res)['cnt'] ?? 0);
}

// ===== เพศ (7 วัน) =====
$gender_data = []; $gender_labels = [];
$gender_res_all = mysqli_query($conn, "SELECT gender, COUNT(*) AS cnt FROM visitor_log WHERE DATE(visited_at) BETWEEN '$default_from' AND '$default_to' GROUP BY gender ORDER BY cnt DESC");
while ($grow = mysqli_fetch_assoc($gender_res_all)) {
    $lbl = match(strtolower($grow['gender'])) {
        'male'        => 'เพศชาย',
        'female'      => 'เพศหญิง',
        'lgbtq+'      => 'LGBTQ+',
        'unspecified' => 'ไม่ระบุ',
        default       => $grow['gender']
    };
    $gender_labels[] = $lbl;
    $gender_data[]   = (int)$grow['cnt'];
}
$gender_total = max(1, array_sum($gender_data));

// ===== ผู้เข้าชม 7 วัน + % เทียบ 7 วันก่อน =====
$total_visitor_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM visitor_log WHERE DATE(visited_at) BETWEEN '$default_from' AND '$default_to'");
$total_visitor = (int)(mysqli_fetch_assoc($total_visitor_res)['total'] ?? 0);

$prev_from_7 = date('Y-m-d', strtotime('-13 days'));
$prev_to_7   = date('Y-m-d', strtotime('-7 days'));
$v_prev_res  = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM visitor_log WHERE DATE(visited_at) BETWEEN '$prev_from_7' AND '$prev_to_7'");
$v_prev_week = (int)(mysqli_fetch_assoc($v_prev_res)['cnt'] ?? 0);
$visitor_pct_diff = ($v_prev_week > 0) ? round(($total_visitor - $v_prev_week) / $v_prev_week * 100) : ($total_visitor > 0 ? 100 : 0);

// helper badge %
function pct_badge(int $pct, string $label = 'จากช่วงก่อนหน้า'): string {
    if ($pct > 0)  return "<span class='pct-badge pct-up'><i class='fa fa-arrow-up'></i> +{$pct}% {$label}</span>";
    if ($pct < 0)  return "<span class='pct-badge pct-down'><i class='fa fa-arrow-down'></i> {$pct}% {$label}</span>";
    return "<span class='pct-badge pct-flat'><i class='fa fa-minus'></i> เท่าเดิม</span>";
}

// JSON สำหรับ JS
$visitor_labels_json   = json_encode($visitor_labels,   JSON_UNESCAPED_UNICODE);
$visitor_data_json     = json_encode($visitor_data);
$top_place_labels_json = json_encode($top_place_labels, JSON_UNESCAPED_UNICODE);
$top_place_data_json   = json_encode($top_place_data);
$gender_labels_json    = json_encode($gender_labels,    JSON_UNESCAPED_UNICODE);
$gender_data_json      = json_encode($gender_data);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js">
// ─── Thai Date Picker ─────────────────────────────────────
const TH_MONTHS  = ['มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
const TH_MONTHS_SHORT = ['ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
const TH_DOWS    = ['อา','จ','อ','พ','พฤ','ศ','ส'];

class ThaiDatePicker {
    constructor(wrapperId, hiddenId, displayId) {
        this.wrapper  = document.getElementById(wrapperId);
        this.hidden   = document.getElementById(hiddenId);
        this.display  = document.getElementById(displayId);
        this.popup    = null;
        this.mode     = 'day'; // day | month | year
        this.viewYear = 0; this.viewMonth = 0;
        const parts   = this.hidden.value.split('-');
        this.viewYear = parseInt(parts[0]); this.viewMonth = parseInt(parts[1]) - 1;
        this.updateDisplay();
        this.wrapper.addEventListener('click', e => { e.stopPropagation(); this.toggle(); });
    }
    updateDisplay() {
        const v = this.hidden.value;
        if (!v) { this.display.textContent = 'เลือกวัน'; return; }
        const [y,m,d] = v.split('-');
        this.display.textContent = `${parseInt(d)} ${TH_MONTHS_SHORT[parseInt(m)-1]} ${parseInt(y)+543}`;
    }
    toggle() {
        if (this.popup) { this.close(); return; }
        // close others
        document.querySelectorAll('.th-cal-popup').forEach(p => p.remove());
        document.querySelectorAll('.th-date-picker').forEach(p => p.classList.remove('open'));
        this.open();
    }
    open() {
        this.mode = 'day';
        this.popup = document.createElement('div');
        this.popup.className = 'th-cal-popup';
        this.wrapper.appendChild(this.popup);
        this.wrapper.classList.add('open');
        this.render();
        setTimeout(() => document.addEventListener('click', this._outside = e => {
            if (!this.wrapper.contains(e.target)) this.close();
        }), 0);
    }
    close() {
        if (this.popup) { this.popup.remove(); this.popup = null; }
        this.wrapper.classList.remove('open');
        document.removeEventListener('click', this._outside);
    }
    render() {
        if (!this.popup) return;
        if (this.mode === 'day')   this.renderDays();
        if (this.mode === 'month') this.renderMonths();
        if (this.mode === 'year')  this.renderYears();
    }
    renderDays() {
        const y = this.viewYear, m = this.viewMonth;
        const selected = this.hidden.value;
        const today    = new Date(); today.setHours(0,0,0,0);
        const first    = new Date(y, m, 1).getDay();
        const days     = new Date(y, m+1, 0).getDate();
        let html = `<div class="th-cal-header">
            <button onclick="this.closest('.th-cal-popup').__picker.prevMonth()">‹</button>
            <span class="th-cal-title" onclick="this.closest('.th-cal-popup').__picker.setMode('month')">${TH_MONTHS[m]} ${y+543}</span>
            <button onclick="this.closest('.th-cal-popup').__picker.nextMonth()">›</button>
        </div><div class="th-cal-grid">`;
        TH_DOWS.forEach(d => { html += `<div class="th-cal-dow">${d}</div>`; });
        for (let i=0; i<first; i++) html += `<div class="th-cal-day empty"></div>`;
        for (let d=1; d<=days; d++) {
            const iso  = `${y}-${String(m+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
            const isToday = new Date(y,m,d).getTime() === today.getTime();
            const isSel   = iso === selected;
            html += `<div class="th-cal-day${isToday?' today':''}${isSel?' selected':''}" onclick="this.closest('.th-cal-popup').__picker.pick('${iso}')">${d}</div>`;
        }
        html += `</div>`;
        this.popup.innerHTML = html;
        this.popup.__picker = this;
    }
    renderMonths() {
        const cur = this.hidden.value ? parseInt(this.hidden.value.split('-')[1])-1 : -1;
        let html = `<div class="th-cal-header">
            <button onclick="this.closest('.th-cal-popup').__picker.viewYear--;this.closest('.th-cal-popup').__picker.render()">‹</button>
            <span class="th-cal-title" onclick="this.closest('.th-cal-popup').__picker.setMode('year')">${this.viewYear+543}</span>
            <button onclick="this.closest('.th-cal-popup').__picker.viewYear++;this.closest('.th-cal-popup').__picker.render()">›</button>
        </div><div class="th-cal-ym-grid">`;
        TH_MONTHS_SHORT.forEach((mn,i) => {
            html += `<div class="th-cal-ym-item${i===cur&&this.viewYear===parseInt(this.hidden.value.split('-')[0])?' selected':''}" onclick="this.closest('.th-cal-popup').__picker.pickMonth(${i})">${mn}</div>`;
        });
        html += `</div>`;
        this.popup.innerHTML = html;
        this.popup.__picker = this;
    }
    renderYears() {
        const base = Math.floor(this.viewYear/12)*12;
        const curY = this.hidden.value ? parseInt(this.hidden.value.split('-')[0]) : -1;
        let html = `<div class="th-cal-header">
            <button onclick="this.closest('.th-cal-popup').__picker.viewYear-=12;this.closest('.th-cal-popup').__picker.render()">‹</button>
            <span class="th-cal-title">${base+543}–${base+11+543}</span>
            <button onclick="this.closest('.th-cal-popup').__picker.viewYear+=12;this.closest('.th-cal-popup').__picker.render()">›</button>
        </div><div class="th-cal-ym-grid">`;
        for (let i=0; i<12; i++) {
            const yr = base+i;
            html += `<div class="th-cal-ym-item${yr===curY?' selected':''}" onclick="this.closest('.th-cal-popup').__picker.pickYear(${yr})">${yr+543}</div>`;
        }
        html += `</div>`;
        this.popup.innerHTML = html;
        this.popup.__picker = this;
    }
    setMode(m) { this.mode = m; this.render(); }
    prevMonth() { this.viewMonth--; if(this.viewMonth<0){this.viewMonth=11;this.viewYear--;} this.render(); }
    nextMonth() { this.viewMonth++; if(this.viewMonth>11){this.viewMonth=0;this.viewYear++;} this.render(); }
    pickMonth(m) { this.viewMonth = m; this.mode = 'day'; this.render(); }
    pickYear(y)  { this.viewYear  = y; this.mode = 'month'; this.render(); }
    pick(iso) {
        this.hidden.value = iso;
        this.updateDisplay();
        this.close();
        // clear quick-range active
        document.querySelectorAll('.btn-quick-range').forEach(b=>b.classList.remove('active'));
    }
}

// init pickers after DOM ready
document.addEventListener('DOMContentLoaded', () => {
    window._pickerFrom = new ThaiDatePicker('pickerFrom', 'dateFrom', 'displayFrom');
    window._pickerTo   = new ThaiDatePicker('pickerTo',   'dateTo',   'displayTo');
});

// ─── ปุ่มทั้งหมด ──────────────────────────────────────────
function setAllTime(btn) {
    document.querySelectorAll('.btn-quick-range').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');
    // ดึงวันที่เก่าสุดใน DB จาก visitor_log จริงๆ ผ่าน AJAX
    fetch('dashboard.php?ajax=1&get_min_date=1')
        .then(r=>r.json())
        .then(d=>{
            const minDate = d.min_date || '2026-01-01';
            const today   = new Date().toISOString().slice(0,10);
            document.getElementById('dateFrom').value = minDate;
            document.getElementById('dateTo').value   = today;
            if(window._pickerFrom) { window._pickerFrom.hidden.value=minDate; window._pickerFrom.updateDisplay(); }
            if(window._pickerTo)   { window._pickerTo.hidden.value=today;   window._pickerTo.updateDisplay(); }
            applyDateFilter();
        })
        .catch(()=>{
            // fallback
            const today = new Date().toISOString().slice(0,10);
            document.getElementById('dateFrom').value = '2026-01-01';
            document.getElementById('dateTo').value   = today;
            if(window._pickerFrom) { window._pickerFrom.hidden.value='2026-01-01'; window._pickerFrom.updateDisplay(); }
            if(window._pickerTo)   { window._pickerTo.hidden.value=today; window._pickerTo.updateDisplay(); }
            applyDateFilter();
        });
}

</script>

<style>
/* ===== Dashboard Layout ===== */
.dashboard-wrapper { padding: 28px 32px; background: #f0f2f0; min-height: calc(100vh - 64px); }
.dashboard-title   { font-size: 22px; font-weight: 700; color: #1a1a1a; margin-bottom: 24px; }

/* ===== Quick Action Cards ===== */
.quick-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px; margin-bottom: 28px;
}
.quick-card {
    background: #fff; border-radius: 16px; padding: 20px 24px;
    display: flex; align-items: center; gap: 16px;
    box-shadow: 0 2px 10px rgba(0,0,0,.06);
    text-decoration: none; color: inherit;
    transition: transform .18s, box-shadow .18s;
    border: 1.5px solid transparent;
}
.quick-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,.10); border-color: #2d7a3a; }
.quick-card-icon { width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0; }
.icon-green  { background:#e6f4ea; color:#2d7a3a; }
.icon-blue   { background:#e3f0fb; color:#2563eb; }
.icon-amber  { background:#fef9e7; color:#d97706; }
.icon-purple { background:#f3e8ff; color:#7c3aed; }
.quick-card-info p  { margin:0; font-size:12px; color:#888; }
.quick-card-info h3 { margin:2px 0 0; font-size:22px; font-weight:700; color:#1a1a1a; }
.quick-card-info span { font-size:13px; font-weight:600; color:#2d7a3a; }

/* visitor card dynamic */
#visitorCard { cursor: default; }
#totalVisitorNum { transition: all .3s; }

/* ===== Chart Grid ===== */
.chart-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
@media(max-width:900px){ .chart-grid{ grid-template-columns:1fr; } }
.chart-card { background:#fff; border-radius:18px; padding:24px; box-shadow:0 2px 12px rgba(0,0,0,.06); }
.chart-card h4  { margin:0 0 4px; font-size:15px; font-weight:600; color:#1a1a1a; }
.chart-subtitle { font-size:12px; color:#999; margin:0 0 14px; }
.chart-container { position:relative; width:100%; }

/* ===== Age group bars ===== */
.age-bar-list { list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:18px; }
.age-bar-item { display:grid; grid-template-columns:56px 1fr 56px; align-items:center; gap:12px; }
.age-label    { font-size:13px; color:#555; font-weight:500; }
.age-track    { background:#eee; border-radius:99px; height:8px; overflow:hidden; }
.age-fill     { height:100%; border-radius:99px; transition:width 1s ease; }
.age-pct      { font-size:12px; font-weight:600; color:#333; text-align:center; background:#f5f5f5; border:1px solid #e0e0e0; border-radius:6px; padding:2px 6px; }

/* ===== Export buttons ===== */
.export-bar { display:flex; gap:10px; justify-content:flex-end; margin-bottom:20px; }
.btn-export { display:flex; align-items:center; gap:6px; padding:7px 16px; border-radius:8px; font-size:13px; font-weight:600; border:none; cursor:pointer; transition:opacity .15s; }
.btn-export:hover { opacity:.85; }
.btn-excel { background:#1d6f42; color:#fff; }
.btn-pdf   { background:#c0392b; color:#fff; }

/* ===== Empty state ===== */
.empty-state { text-align:center; padding:32px 0; color:#bbb; font-size:13px; }

/* ===== Date Range Picker Bar ===== */
.date-range-bar {
    display:flex; align-items:center; flex-wrap:wrap; gap:10px;
    background:#fff; border-radius:14px; padding:14px 20px;
    margin-bottom:22px; box-shadow:0 2px 10px rgba(0,0,0,.06);
}
.date-range-bar label { font-size:13px; font-weight:600; color:#555; white-space:nowrap; }
.date-range-bar input[type="date"] {
    border:1.5px solid #ddd; border-radius:8px; padding:6px 12px;
    font-size:13px; color:#333; outline:none; transition:border-color .15s; cursor:pointer;
}
.date-range-bar input[type="date"]:focus { border-color:#2d7a3a; }
.date-range-sep { font-size:13px; color:#aaa; }
.btn-filter {
    display:flex; align-items:center; gap:6px; padding:7px 18px;
    background:#2d7a3a; color:#fff; border:none; border-radius:8px;
    font-size:13px; font-weight:600; cursor:pointer; transition:background .15s; white-space:nowrap;
}
.btn-filter:hover    { background:#235f2d; }
.btn-filter:disabled { background:#aaa; cursor:not-allowed; }
.btn-quick-range {
    padding:5px 12px; font-size:12px; border:1.5px solid #ddd;
    border-radius:20px; background:#f7f7f7; color:#555;
    cursor:pointer; transition:all .15s; white-space:nowrap;
}
.btn-quick-range:hover, .btn-quick-range.active {
    border-color:#2d7a3a; background:#e6f4ea; color:#2d7a3a; font-weight:600;
}
.date-range-info { font-size:12px; color:#999; margin-left:auto; white-space:nowrap; }

/* ===== % Comparison Badges ===== */
.pct-badge { display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:600; padding:3px 8px; border-radius:20px; margin-top:5px; }
.pct-up    { background:#e6f4ea; color:#1e6b2b; }
.pct-down  { background:#fdecea; color:#c0392b; }
.pct-flat  { background:#f5f5f5; color:#888; }

/* chart loading */
.chart-wrap { position:relative; }
.chart-loading {
    position:absolute; inset:0; display:flex; align-items:center;
    justify-content:center; background:rgba(255,255,255,.85);
    border-radius:12px; font-size:13px; color:#888; gap:8px; z-index:10;
}

/* ===== Thai Date Picker ===== */
.th-date-picker {
    display:inline-flex; align-items:center; cursor:pointer;
    border:1.5px solid #ddd; border-radius:8px; padding:6px 12px;
    font-size:13px; color:#333; background:#fff; min-width:130px;
    transition:border-color .15s; user-select:none; position:relative;
}
.th-date-picker:hover { border-color:#2d7a3a; }
.th-date-picker.open  { border-color:#2d7a3a; box-shadow:0 0 0 3px rgba(45,122,58,.12); }
.th-cal-popup {
    position:absolute; top:calc(100% + 6px); left:0; z-index:9999;
    background:#fff; border-radius:14px; padding:14px;
    box-shadow:0 8px 32px rgba(0,0,0,.14); width:260px; font-family:inherit;
}
.th-cal-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
.th-cal-header button { background:none; border:none; cursor:pointer; font-size:16px; color:#555; padding:4px 8px; border-radius:6px; }
.th-cal-header button:hover { background:#f0f0f0; }
.th-cal-title { font-size:13px; font-weight:700; color:#1a1a1a; cursor:pointer; padding:4px 8px; border-radius:6px; }
.th-cal-title:hover { background:#f0f0f0; }
.th-cal-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:2px; }
.th-cal-dow { text-align:center; font-size:10px; font-weight:700; color:#888; padding:4px 0; }
.th-cal-day { text-align:center; font-size:12px; padding:6px 2px; border-radius:8px; cursor:pointer; transition:background .1s; }
.th-cal-day:hover { background:#e6f4ea; color:#2d7a3a; }
.th-cal-day.today { font-weight:700; color:#2d7a3a; }
.th-cal-day.selected { background:#2d7a3a; color:#fff !important; font-weight:700; }
.th-cal-day.other-month { color:#ccc; }
.th-cal-day.empty { cursor:default; }
.th-cal-ym-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:6px; margin-top:6px; }
.th-cal-ym-item { text-align:center; padding:7px 4px; border-radius:8px; font-size:12px; cursor:pointer; transition:background .1s; }
.th-cal-ym-item:hover { background:#e6f4ea; color:#2d7a3a; }
.th-cal-ym-item.selected { background:#2d7a3a; color:#fff; font-weight:700; }
</style>

<div class="dashboard-wrapper">

    <div class="export-bar">
        <button class="btn-export btn-excel" onclick="exportExcel()"><i class="fa fa-file-excel"></i> Excel</button>
        <button class="btn-export btn-pdf"   onclick="exportPDF()"><i class="fa fa-file-pdf"></i> PDF</button>
    </div>

    <!-- ===== Summary Cards ===== -->
    <div class="quick-cards">
        <a href="place_manage.php" class="quick-card">
            <div class="quick-card-icon icon-green"><i class="fa fa-map-marker-alt"></i></div>
            <div class="quick-card-info">
                <p>สถานที่ทั้งหมด</p>
                <h3><?= $place_count ?></h3>
                <span>ไปจัดการ →</span>
            </div>
        </a>

        <a href="content_manage.php" class="quick-card">
            <div class="quick-card-icon icon-blue"><i class="fa fa-newspaper"></i></div>
            <div class="quick-card-info">
                <p>คอนเทนต์ทั้งหมด</p>
                <h3><?= $content_count ?></h3>
                <span>ไปจัดการ →</span>
            </div>
        </a>

        <a href="chatbot_manage.php" class="quick-card">
            <div class="quick-card-icon icon-purple"><i class="fa fa-robot"></i></div>
            <div class="quick-card-info">
                <p>เนื้อหาแชทบอท</p>
                <h3><?= $chatbot_count ?></h3>
                <span>ไปจัดการ →</span>
            </div>
        </a>

        <!-- Visitor card — อัปเดตตาม date filter -->
        <div class="quick-card" id="visitorCard">
            <div class="quick-card-icon icon-amber"><i class="fa fa-users"></i></div>
            <div class="quick-card-info">
                <p id="visitorCardLabel">ผู้เข้าชม (7 วันล่าสุด)</p>
                <h3 id="totalVisitorNum"><?= $total_visitor ?></h3>
                <div id="visitorPctBadge"><?= pct_badge($visitor_pct_diff, 'จาก 7 วันก่อน') ?></div>
            </div>
        </div>
    </div>

    <!-- ===== Date Range Picker ===== -->
    <div class="date-range-bar">
        <i class="fa fa-calendar-alt" style="color:#2d7a3a;font-size:15px;"></i>
        <label>ช่วงวันที่:</label>
        <div class="th-date-picker" id="pickerFrom" data-target="dateFrom"><span id="displayFrom"></span><i class="fa fa-calendar-alt" style="margin-left:6px;color:#aaa;font-size:11px;"></i></div><input type="hidden" id="dateFrom" value="<?= $default_from ?>">
        <span class="date-range-sep">—</span>
        <div class="th-date-picker" id="pickerTo" data-target="dateTo"><span id="displayTo"></span><i class="fa fa-calendar-alt" style="margin-left:6px;color:#aaa;font-size:11px;"></i></div><input type="hidden" id="dateTo" value="<?= $default_to ?>">
        <button class="btn-filter" id="btnFilter" onclick="applyDateFilter()">
            <i class="fa fa-filter"></i> กรองข้อมูล
        </button>
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
            <button class="btn-quick-range active" onclick="setQuickRange(7,this)">7 วัน</button>
            <button class="btn-quick-range"        onclick="setQuickRange(30,this)">30 วัน</button>
            <button class="btn-quick-range"        onclick="setAllTime(this)">ทั้งหมด</button>
        </div>
        <span class="date-range-info" id="dateRangeInfo">แสดงข้อมูล 7 วันย้อนหลัง</span>
    </div>

    <!-- ===== Charts ===== -->
    <div class="chart-grid">

        <!-- 1. ผู้เข้าชม (Bar) -->
        <div class="chart-card">
            <h4>ผู้เข้าใช้งานเว็บไซต์</h4>
            <p class="chart-subtitle" id="visitorSubtitle">7 วันย้อนหลัง</p>
            <div class="chart-wrap">
                <div class="chart-container" style="height:220px;"><canvas id="visitorChart"></canvas></div>
                <div class="chart-loading" id="loadingVisitor" style="display:none;"><i class="fa fa-spinner fa-spin"></i> กำลังโหลด...</div>
            </div>
        </div>

        <!-- 2. Top 5 สถานที่ (Horizontal Bar) -->
        <div class="chart-card">
            <h4>สถานที่ที่มีผู้เข้าชมมากที่สุด</h4>
            <p class="chart-subtitle">Top 5</p>
            <div class="chart-wrap">
                <div class="empty-state" id="placeEmpty" <?= array_sum($top_place_data) > 0 ? 'style="display:none;"' : '' ?>>
                    <i class="fa fa-chart-bar" style="font-size:32px;display:block;margin-bottom:8px;"></i>
                    ยังไม่มีข้อมูลการเข้าชม
                </div>
                <div class="chart-container" style="height:220px;" id="placeChartWrap" <?= array_sum($top_place_data) == 0 ? 'style="display:none;"' : '' ?>>
                    <canvas id="placeChart"></canvas>
                </div>
                <div class="chart-loading" id="loadingPlace" style="display:none;"><i class="fa fa-spinner fa-spin"></i> กำลังโหลด...</div>
            </div>
        </div>

        <!-- 3. ช่วงอายุ (Custom bars) -->
        <div class="chart-card">
            <h4>ช่วงอายุของผู้ใช้งานเว็บไซต์</h4>
            <p class="chart-subtitle" id="ageSubtitle">จากแบบสอบถาม (ทั้งหมด <?= $age_total ?> คน)</p>
            <?php
            $age_colors = ['#2d7a3a','#d4a017','#c0796a','#2c3e7a','#e07b30','#5b8de8'];
            $i = 0;
            ?>
            <ul class="age-bar-list" id="ageBarList">
                <?php foreach ($age_ranges as $range):
                    $cnt   = $age_counts[$range];
                    $pct   = ($age_total > 0) ? round($cnt / $age_total * 100) : 0;
                    $color = $age_colors[$i % count($age_colors)];
                    $i++;
                ?>
                <li class="age-bar-item">
                    <span class="age-label"><?= $range ?></span>
                    <div class="age-track"><div class="age-fill" style="width:<?= $pct ?>%;background:<?= $color ?>;"></div></div>
                    <span class="age-pct"><?= $pct ?>%<br><small style="font-weight:400;color:#999;">(<?= $cnt ?>)</small></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- 4. เพศ (Donut) -->
        <div class="chart-card">
            <h4>เพศของผู้ใช้งานเว็บไซต์</h4>
            <p class="chart-subtitle" id="genderSubtitle">จากแบบสอบถาม (ทั้งหมด <?= $gender_total ?> คน)</p>
            <div class="chart-wrap">
                <div class="empty-state" id="genderEmpty" <?= array_sum($gender_data) > 0 ? 'style="display:none;"' : '' ?>>
                    <i class="fa fa-venus-mars" style="font-size:32px;display:block;margin-bottom:8px;"></i>
                    ยังไม่มีข้อมูล
                </div>
                <div class="chart-container" style="height:220px;" id="genderChartWrap" <?= array_sum($gender_data) == 0 ? 'style="display:none;"' : '' ?>>
                    <canvas id="genderChart"></canvas>
                </div>
                <div class="chart-loading" id="loadingGender" style="display:none;"><i class="fa fa-spinner fa-spin"></i> กำลังโหลด...</div>
            </div>
        </div>

    </div>
</div>

<script>
// ===== ข้อมูลเริ่มต้นจาก PHP =====
let visitorLabels = <?= $visitor_labels_json ?>;
let visitorData   = <?= $visitor_data_json ?>;
let placeLabels   = <?= $top_place_labels_json ?>;
let placeData     = <?= $top_place_data_json ?>;
let genderLabels  = <?= $gender_labels_json ?>;
let genderData    = <?= $gender_data_json ?>;

const ageColors      = ['#2d7a3a','#d4a017','#c0796a','#2c3e7a','#e07b30','#5b8de8'];
const genderColorMap = { 'เพศชาย':'#2c3e7a', 'เพศหญิง':'#d4a017', 'LGBTQ+':'#c0796a', 'ไม่ระบุ':'#aaa' };
const barColorPalette = ['#c0392b','#d4a017','#c0796a','#2d7a3a','#e07b30','#5b8de8','#2c3e7a'];

// ─── 1. Visitor Bar ────────────────────────────────────────
const visitorCtx   = document.getElementById('visitorChart').getContext('2d');
const visitorChart = new Chart(visitorCtx, {
    type: 'bar',
    data: {
        labels: visitorLabels,
        datasets: [{ data: visitorData, backgroundColor: visitorLabels.map((_,i)=>barColorPalette[i%barColorPalette.length]), borderRadius:6, borderSkipped:false }]
    },
    options: {
        responsive:true, maintainAspectRatio:false,
        plugins:{ legend:{display:false}, tooltip:{callbacks:{label:ctx=>` ${ctx.parsed.y} คน`}} },
        scales:{
            x:{ grid:{display:false}, ticks:{font:{size:11}} },
            y:{ beginAtZero:true, grid:{color:'#eee'}, ticks:{font:{size:11}, stepSize:1, callback:v=>Number.isInteger(v)?v:null} }
        }
    }
});

// ─── 2. Place Horizontal Bar ───────────────────────────────
let placeChart = null;
const placeCtxEl = document.getElementById('placeChart');
if (placeCtxEl && placeData.some(v=>v>0)) {
    placeChart = new Chart(placeCtxEl.getContext('2d'), {
        type:'bar',
        data:{ labels:placeLabels, datasets:[{ data:placeData, backgroundColor:['#d4a017','#5b8de8','#2c3e7a','#c0796a','#2d7a3a'], borderRadius:5, borderSkipped:false }] },
        options:{
            indexAxis:'y', responsive:true, maintainAspectRatio:false,
            plugins:{ legend:{display:false}, tooltip:{callbacks:{label:ctx=>` ${ctx.parsed.x} ครั้ง`}} },
            scales:{
                x:{ beginAtZero:true, grid:{color:'#eee'}, ticks:{stepSize:1, font:{size:11}, callback:v=>Number.isInteger(v)?v:null} },
                y:{ grid:{display:false}, ticks:{font:{size:12}} }
            }
        }
    });
}

// ─── 4. Gender Donut ───────────────────────────────────────
let genderChart = null;
const genderCtxEl = document.getElementById('genderChart');
if (genderCtxEl && genderData.some(v=>v>0)) {
    genderChart = new Chart(genderCtxEl.getContext('2d'), {
        type:'doughnut',
        data:{
            labels:genderLabels,
            datasets:[{ data:genderData, backgroundColor:genderLabels.map(l=>genderColorMap[l]||'#5b8de8'), borderWidth:genderData.map(v=>v===0?0:3), borderColor:'#fff', hoverOffset:6 }]
        },
        options:{
            responsive:true, maintainAspectRatio:false, cutout:'60%',
            plugins:{
                legend:{ position:'bottom', labels:{padding:16, font:{size:12}, boxWidth:12, boxHeight:12} },
                tooltip:{callbacks:{label:ctx=>` ${ctx.label}: ${ctx.parsed} คน`}}
            }
        }
    });
}

// ─── Date Range Picker ─────────────────────────────────────
function setQuickRange(days, btn) {
    document.querySelectorAll('.btn-quick-range').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');
    const to   = new Date();
    const from = new Date();
    from.setDate(from.getDate() - (days - 1));
    const fromISO = from.toISOString().slice(0,10);
    const toISO   = to.toISOString().slice(0,10);
    document.getElementById('dateFrom').value = fromISO;
    document.getElementById('dateTo').value   = toISO;
    if(window._pickerFrom){ window._pickerFrom.hidden.value=fromISO; window._pickerFrom.updateDisplay(); }
    if(window._pickerTo)  { window._pickerTo.hidden.value=toISO;     window._pickerTo.updateDisplay(); }
    applyDateFilter();
}

async function applyDateFilter() {
    const from = document.getElementById('dateFrom').value;
    const to   = document.getElementById('dateTo').value;
    if (!from || !to || from > to) { alert('กรุณาเลือกช่วงวันที่ให้ถูกต้อง'); return; }

    // Show loaders
    ['loadingVisitor','loadingPlace','loadingGender'].forEach(id=>{ const el=document.getElementById(id); if(el) el.style.display='flex'; });
    const btn = document.getElementById('btnFilter');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> กำลังโหลด...';

    try {
        const res  = await fetch(`dashboard.php?ajax=1&date_from=${from}&date_to=${to}`);
        const data = await res.json();

        // ── Visitor chart ──
        const dynColors = data.visitor_labels.map((_,i)=>barColorPalette[i%barColorPalette.length]);
        visitorChart.data.labels = data.visitor_labels;
        visitorChart.data.datasets[0].data = data.visitor_data;
        visitorChart.data.datasets[0].backgroundColor = dynColors;
        visitorChart.update();

        const days = Math.round((new Date(to)-new Date(from))/86400000)+1;
        document.getElementById('visitorSubtitle').textContent = `${days} วัน (${formatDateTH(from)} – ${formatDateTH(to)})`;

        // ── Summary card: ผู้เข้าชม ──
        document.getElementById('totalVisitorNum').textContent = data.total_visitor.toLocaleString();
        document.getElementById('visitorCardLabel').textContent = `ผู้เข้าชม (${days} วัน)`;
        document.getElementById('visitorPctBadge').innerHTML = pctBadgeHTML(data.visitor_pct, 'จากช่วงก่อนหน้า');

        // ── Place chart ──
        const hasPlace = data.place_data.some(v=>v>0);
        document.getElementById('placeEmpty').style.display     = hasPlace ? 'none' : 'block';
        document.getElementById('placeChartWrap').style.display = hasPlace ? 'block' : 'none';
        if (hasPlace) {
            if (!placeChart) {
                placeChart = new Chart(document.getElementById('placeChart').getContext('2d'), {
                    type:'bar',
                    data:{ labels:data.place_labels, datasets:[{ data:data.place_data, backgroundColor:['#d4a017','#5b8de8','#2c3e7a','#c0796a','#2d7a3a'], borderRadius:5, borderSkipped:false }] },
                    options:{ indexAxis:'y', responsive:true, maintainAspectRatio:false,
                        plugins:{ legend:{display:false}, tooltip:{callbacks:{label:ctx=>` ${ctx.parsed.x} ครั้ง`}} },
                        scales:{ x:{beginAtZero:true,grid:{color:'#eee'},ticks:{stepSize:1,font:{size:11},callback:v=>Number.isInteger(v)?v:null}}, y:{grid:{display:false},ticks:{font:{size:12}}} }
                    }
                });
            } else {
                placeChart.data.labels = data.place_labels;
                placeChart.data.datasets[0].data = data.place_data;
                placeChart.update();
            }
        }

        // ── Age bars ──
        const ageList = document.getElementById('ageBarList');
        ageList.innerHTML = '';
        data.age.forEach((a,idx)=>{
            const color = ageColors[idx%ageColors.length];
            ageList.innerHTML += `
                <li class="age-bar-item">
                    <span class="age-label">${a.range}</span>
                    <div class="age-track"><div class="age-fill" style="width:${a.pct}%;background:${color};"></div></div>
                    <span class="age-pct">${a.pct}%<br><small style="font-weight:400;color:#999;">(${a.count})</small></span>
                </li>`;
        });
        document.getElementById('ageSubtitle').textContent = `จากแบบสอบถาม (ทั้งหมด ${data.age_total} คน)`;

        // ── Gender chart ──
        const hasGender = data.gender_data.some(v=>v>0);
        document.getElementById('genderEmpty').style.display     = hasGender ? 'none' : 'block';
        document.getElementById('genderChartWrap').style.display = hasGender ? 'block' : 'none';
        if (hasGender) {
            const gColors = data.gender_labels.map(l=>genderColorMap[l]||'#5b8de8');
            if (!genderChart) {
                genderChart = new Chart(document.getElementById('genderChart').getContext('2d'), {
                    type:'doughnut',
                    data:{ labels:data.gender_labels, datasets:[{ data:data.gender_data, backgroundColor:gColors, borderWidth:data.gender_data.map(v=>v===0?0:3), borderColor:'#fff', hoverOffset:6 }] },
                    options:{ responsive:true, maintainAspectRatio:false, cutout:'60%',
                        plugins:{ legend:{position:'bottom',labels:{padding:16,font:{size:12},boxWidth:12,boxHeight:12}}, tooltip:{callbacks:{label:ctx=>` ${ctx.label}: ${ctx.parsed} คน`}} }
                    }
                });
            } else {
                genderChart.data.labels = data.gender_labels;
                genderChart.data.datasets[0].data = data.gender_data;
                genderChart.data.datasets[0].backgroundColor = gColors;
                genderChart.data.datasets[0].borderWidth = data.gender_data.map(v=>v===0?0:3);
                genderChart.update();
            }
        }
        document.getElementById('genderSubtitle').textContent = `จากแบบสอบถาม (ทั้งหมด ${data.gender_total} คน)`;

        // ── Info label ──
        document.getElementById('dateRangeInfo').textContent = `แสดงข้อมูล ${formatDateTH(from)} – ${formatDateTH(to)}`;

    } catch(e) {
        console.error(e);
        alert('เกิดข้อผิดพลาดในการโหลดข้อมูล');
    } finally {
        ['loadingVisitor','loadingPlace','loadingGender'].forEach(id=>{ const el=document.getElementById(id); if(el) el.style.display='none'; });
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-filter"></i> กรองข้อมูล';
    }
}

// ─── Helpers ──────────────────────────────────────────────
function pctBadgeHTML(pct, label) {
    if (pct > 0)  return `<span class='pct-badge pct-up'><i class='fa fa-arrow-up'></i> +${pct}% ${label}</span>`;
    if (pct < 0)  return `<span class='pct-badge pct-down'><i class='fa fa-arrow-down'></i> ${pct}% ${label}</span>`;
    return `<span class='pct-badge pct-flat'><i class='fa fa-minus'></i> เท่าเดิม</span>`;
}

function formatDateTH(iso) {
    const [y,m,d] = iso.split('-');
    const months = ['ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
    return `${parseInt(d)} ${months[parseInt(m)-1]} ${parseInt(y)+543}`;
}

// ─── Export Excel ──────────────────────────────────────────
function exportExcel() {
    if (typeof XLSX === 'undefined') {
        const s = document.createElement('script');
        s.src = 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js';
        s.onload = doExportExcel;
        document.head.appendChild(s);
    } else { doExportExcel(); }
}
function doExportExcel() {
    const from = document.getElementById('dateFrom').value;
    const to   = document.getElementById('dateTo').value;
    const wb = XLSX.utils.book_new();
    const vLabels = visitorChart.data.labels;
    const vData   = visitorChart.data.datasets[0].data;
    const visitorRows = [['วัน','จำนวนผู้เข้าชม (คน)']];
    vLabels.forEach((d,i)=>visitorRows.push([d, vData[i]]));
    XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(visitorRows), 'ผู้เข้าชม');
    const placeRows = [['สถานที่','จำนวนการเข้าชม (ครั้ง)']];
    (placeChart ? placeChart.data.labels : []).forEach((p,i)=>placeRows.push([p, placeChart.data.datasets[0].data[i]]));
    XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(placeRows), 'Top 5 สถานที่');
    const ageRows = [['ช่วงอายุ','จำนวน (คน)','เปอร์เซ็นต์']];
    document.querySelectorAll('#ageBarList .age-bar-item').forEach(li=>{
        const range = li.querySelector('.age-label').textContent.trim();
        const txt   = li.querySelector('.age-pct').textContent.trim();
        const pct   = txt.split('%')[0]; const cnt = txt.match(/\((\d+)\)/)?.[1]??'0';
        ageRows.push([range, parseInt(cnt), pct+'%']);
    });
    XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(ageRows), 'ช่วงอายุ');
    const gRows = [['เพศ','จำนวน (คน)']];
    if (genderChart) genderChart.data.labels.forEach((g,i)=>gRows.push([g, genderChart.data.datasets[0].data[i]]));
    XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(gRows), 'เพศ');
    const sumRows = [['รายงาน Dashboard'], ['ช่วงวันที่', `${from} ถึง ${to}`], [], ['หัวข้อ','จำนวน'],
        ['สถานที่ทั้งหมด', <?= (int)$place_count ?>], ['คอนเทนต์ทั้งหมด', <?= (int)$content_count ?>],
        ['ผู้เข้าชม (ช่วงที่เลือก)', parseInt(document.getElementById('totalVisitorNum').textContent.replace(/,/g,''))||0]];
    XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(sumRows), 'สรุป');
    XLSX.writeFile(wb, 'dashboard_report_'+new Date().toISOString().slice(0,10)+'.xlsx');
}

// ─── Export PDF ────────────────────────────────────────────
function exportPDF() {
    const libs = [{id:'jspdf-lib',src:'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js'},
                  {id:'html2canvas-lib',src:'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js'}];
    let loaded = 0;
    libs.forEach(lib=>{
        if (!document.getElementById(lib.id)) {
            const s = document.createElement('script'); s.id = lib.id; s.src = lib.src;
            s.onload = ()=>{ loaded++; if(loaded===libs.length) doExportPDF(); };
            document.head.appendChild(s);
        } else { loaded++; if(loaded===libs.length) doExportPDF(); }
    });
}
async function doExportPDF() {
    const { jsPDF } = window.jspdf;
    const pdf = new jsPDF({ orientation:'portrait', unit:'mm', format:'a4' });
    const btn = document.querySelector('.btn-pdf');
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> กำลังสร้าง...'; btn.disabled = true;
    try {
        const canvas = await html2canvas(document.querySelector('.dashboard-wrapper'), { scale:2, useCORS:true, logging:false, backgroundColor:'#f0f2f0' });
        const imgData = canvas.toDataURL('image/png');
        const pageW=210, pageH=297, margin=10, usableW=pageW-margin*2;
        const imgH = (canvas.height/canvas.width)*usableW;
        pdf.setFillColor(45,122,58); pdf.rect(0,0,pageW,14,'F');
        pdf.setTextColor(255,255,255); pdf.setFontSize(11); pdf.text('รายงาน Dashboard',margin,9.5);
        const from=document.getElementById('dateFrom').value, to=document.getElementById('dateTo').value;
        pdf.setFontSize(8); pdf.text(`ช่วงวันที่: ${from} – ${to}`, pageW-margin, 9.5, {align:'right'});
        let yPos=16, remaining=imgH, srcY=0;
        while(remaining>0){
            const sliceH=Math.min(remaining,pageH-yPos-margin), slicePx=(sliceH/usableW)*canvas.width;
            const sc=document.createElement('canvas'); sc.width=canvas.width; sc.height=slicePx;
            sc.getContext('2d').drawImage(canvas,0,srcY,canvas.width,slicePx,0,0,canvas.width,slicePx);
            pdf.addImage(sc.toDataURL('image/png'),'PNG',margin,yPos,usableW,sliceH);
            remaining-=sliceH; srcY+=slicePx;
            if(remaining>0){ pdf.addPage(); pdf.setFillColor(45,122,58); pdf.rect(0,0,pageW,14,'F'); yPos=16; }
        }
        const total=pdf.internal.getNumberOfPages();
        for(let p=1;p<=total;p++){ pdf.setPage(p); pdf.setFillColor(240,242,240); pdf.rect(0,pageH-8,pageW,8,'F'); pdf.setTextColor(150,150,150); pdf.setFontSize(7); pdf.text(`หน้า ${p} / ${total}`,pageW/2,pageH-3,{align:'center'}); }
        pdf.save('dashboard_report_'+new Date().toISOString().slice(0,10)+'.pdf');
    } catch(e){ console.error(e); alert('เกิดข้อผิดพลาดในการสร้าง PDF'); }
    finally { btn.innerHTML=orig; btn.disabled=false; }
}
</script>
