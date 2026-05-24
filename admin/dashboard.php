<?php
// ============================================================
//  AJAX: ดึงวันที่เริ่มต้นที่มีข้อมูลเก่าที่สุด
// ============================================================
if (isset($_GET['ajax']) && $_GET['ajax'] === '1' && isset($_GET['get_min_date'])) {
    include '../config.php';
    header('Content-Type: application/json; charset=utf-8');

    $res = mysqli_query($conn, "SELECT MIN(DATE(visited_at)) AS min_date FROM visitor_log");
    $row = mysqli_fetch_assoc($res);

    echo json_encode([
        'min_date' => $row['min_date'] ?? date('Y-m-d')
    ]);
    exit;
}

// ============================================================
//  AJAX: ดึงข้อมูลทั้งหมดตามช่วงวันที่ + ตัวกรองเพิ่มเติม
// ============================================================
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    include '../config.php';
    header('Content-Type: application/json; charset=utf-8');

    $date_from = isset($_GET['date_from'])
        ? mysqli_real_escape_string($conn, $_GET['date_from'])
        : date('Y-m-d', strtotime('-6 days'));

    $date_to = isset($_GET['date_to'])
        ? mysqli_real_escape_string($conn, $_GET['date_to'])
        : date('Y-m-d');

    $filter_ages    = isset($_GET['age'])    && $_GET['age']    !== '' ? array_map('trim', explode(',', $_GET['age']))    : [];
    $filter_genders = isset($_GET['gender']) && $_GET['gender'] !== '' ? array_map('trim', explode(',', $_GET['gender'])) : [];
    $filter_places  = isset($_GET['place'])  && $_GET['place']  !== '' ? array_map('trim', explode(',', $_GET['place']))  : [];
    $vis_min        = isset($_GET['vis_min']) ? (int)$_GET['vis_min'] : 0;
    $vis_max        = isset($_GET['vis_max']) ? (int)$_GET['vis_max'] : 999999;

    $extra_visitor_where = "";
    if (!empty($filter_ages)) {
        $safe_ages = array_map(fn($a) => "'" . mysqli_real_escape_string($conn, $a) . "'", $filter_ages);
        $extra_visitor_where .= " AND age_range IN (" . implode(',', $safe_ages) . ")";
    }
    if (!empty($filter_genders)) {
        $gender_map = ['เพศชาย' => 'male', 'เพศหญิง' => 'female', 'LGBTQ+' => 'lgbtq+', 'ไม่ระบุ' => 'unspecified'];
        $safe_genders = array_map(function($g) use ($conn, $gender_map) {
            $db_val = $gender_map[$g] ?? $g;
            return "'" . mysqli_real_escape_string($conn, $db_val) . "'";
        }, $filter_genders);
        $extra_visitor_where .= " AND gender IN (" . implode(',', $safe_genders) . ")";
    }

    $v_labels = [];
    $v_data   = [];
    $day_th   = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัส', 'ศุกร์', 'เสาร์'];
    $cur      = strtotime($date_from);
    $end      = strtotime($date_to);

    while ($cur <= $end) {
        $d          = date('Y-m-d', $cur);
        $dow        = (int) date('w', $cur);
        $v_labels[] = $day_th[$dow] . ' ' . date('d/m', $cur);

        $res      = mysqli_query($conn, "
            SELECT COUNT(*) AS cnt
            FROM visitor_log
            WHERE DATE(visited_at) = '$d'
            $extra_visitor_where
        ");
        $day_cnt  = (int) (mysqli_fetch_assoc($res)['cnt'] ?? 0);

        if ($day_cnt >= $vis_min && $day_cnt <= $vis_max) {
            $v_data[] = $day_cnt;
        } else {
            $v_data[] = 0;
        }
        $cur = strtotime('+1 day', $cur);
    }

    $place_where = "";
    if (!empty($filter_places)) {
        $safe_places = array_map(fn($p) => "'" . mysqli_real_escape_string($conn, $p) . "'", $filter_places);
        $place_where = " AND p.place_name IN (" . implode(',', $safe_places) . ")";
    }

    $top_res  = mysqli_query($conn, "
        SELECT p.place_name, COUNT(*) AS view_count
        FROM place_view_log pvl
        JOIN place p ON p.place_id = pvl.place_id
        WHERE DATE(pvl.viewed_at) BETWEEN '$date_from' AND '$date_to'
        $place_where
        GROUP BY pvl.place_id
        ORDER BY view_count DESC
        LIMIT 5
    ");
    $tp_labels = [];
    $tp_data   = [];

    while ($row = mysqli_fetch_assoc($top_res)) {
        $tp_labels[] = $row['place_name'];
        $tp_data[]   = (int) $row['view_count'];
    }

    if (empty($tp_labels)) {
        $fb_where = !empty($filter_places) ? "WHERE place_name IN (" . implode(',', array_map(fn($p) => "'" . mysqli_real_escape_string($conn, $p) . "'", $filter_places)) . ")" : "";
        $fb = mysqli_query($conn, "SELECT place_name FROM place $fb_where ORDER BY place_id DESC LIMIT 5");
        while ($row = mysqli_fetch_assoc($fb)) {
            $tp_labels[] = $row['place_name'];
            $tp_data[]   = 0;
        }
    }

    $age_ranges  = ['15-25', '26-35', '36-45', '46-55', '56-65', '65+'];
    $age_tot_res = mysqli_query($conn, "
        SELECT COUNT(*) AS total
        FROM visitor_log
        WHERE DATE(visited_at) BETWEEN '$date_from' AND '$date_to'
        $extra_visitor_where
    ");
    $age_total = max(1, (int) (mysqli_fetch_assoc($age_tot_res)['total'] ?? 0));
    $age_arr   = [];

    foreach ($age_ranges as $r) {
        $res = mysqli_query($conn, "
            SELECT COUNT(*) AS cnt
            FROM visitor_log
            WHERE age_range = '$r'
              AND DATE(visited_at) BETWEEN '$date_from' AND '$date_to'
              $extra_visitor_where
        ");
        $cnt       = (int) (mysqli_fetch_assoc($res)['cnt'] ?? 0);
        $age_arr[] = [
            'range' => $r,
            'count' => $cnt,
            'pct'   => round($cnt / $age_total * 100),
        ];
    }

    $g_labels = [];
    $g_data   = [];
    $g_res    = mysqli_query($conn, "
        SELECT gender, COUNT(*) AS cnt
        FROM visitor_log
        WHERE DATE(visited_at) BETWEEN '$date_from' AND '$date_to'
        $extra_visitor_where
        GROUP BY gender
        ORDER BY cnt DESC
    ");

    while ($grow = mysqli_fetch_assoc($g_res)) {
        $lbl = match (strtolower($grow['gender'])) {
            'male'        => 'เพศชาย',
            'female'      => 'เพศหญิง',
            'lgbtq+'      => 'LGBTQ+',
            'unspecified' => 'ไม่ระบุ',
            default       => $grow['gender'],
        };
        $g_labels[] = $lbl;
        $g_data[]   = (int) $grow['cnt'];
    }

    $total_v_res = mysqli_query($conn, "
        SELECT COUNT(*) AS total
        FROM visitor_log
        WHERE DATE(visited_at) BETWEEN '$date_from' AND '$date_to'
        $extra_visitor_where
    ");
    $total_v = (int) (mysqli_fetch_assoc($total_v_res)['total'] ?? 0);

    $range_days = max(1, (int) ((strtotime($date_to) - strtotime($date_from)) / 86400) + 1);
    $prev_to    = date('Y-m-d', strtotime($date_from . ' -1 day'));
    $prev_from  = date('Y-m-d', strtotime($prev_to . ' -' . ($range_days - 1) . ' days'));

    $prev_v_res = mysqli_query($conn, "
        SELECT COUNT(*) AS cnt
        FROM visitor_log
        WHERE DATE(visited_at) BETWEEN '$prev_from' AND '$prev_to'
        $extra_visitor_where
    ");
    $prev_v      = (int) (mysqli_fetch_assoc($prev_v_res)['cnt'] ?? 0);
    $visitor_pct = ($prev_v > 0)
        ? round(($total_v - $prev_v) / $prev_v * 100)
        : ($total_v > 0 ? 100 : 0);

    echo json_encode([
        'visitor_labels' => $v_labels,
        'visitor_data'   => $v_data,
        'place_labels'   => $tp_labels,
        'place_data'     => $tp_data,
        'age'            => $age_arr,
        'age_total'      => $age_total,
        'gender_labels'  => $g_labels,
        'gender_data'    => $g_data,
        'gender_total'   => array_sum($g_data),
        'total_visitor'  => $total_v,
        'visitor_pct'    => $visitor_pct,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================================
//  โหลดหน้าปกติ
// ============================================================
include 'check_login.php';
include '../config.php';
include 'header.php';

$place_count = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM place")
)['total'] ?? 0;

$content_count = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM content")
)['total'] ?? 0;

$chatbot_count = mysqli_fetch_assoc(
    mysqli_query($conn, "
        SELECT (SELECT COUNT(*) FROM place)
             + (SELECT COUNT(*) FROM restaurant)
             + (SELECT COUNT(*) FROM activity)
             + (SELECT COUNT(*) FROM souvenir_shop)
             + (SELECT COUNT(*) FROM about_us) AS total
    ")
)['total'] ?? 0;

$default_from = date('Y-m-d', strtotime('-6 days'));
$default_to   = date('Y-m-d');

$visitor_data   = [];
$visitor_labels = [];
$day_th_arr     = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัส', 'ศุกร์', 'เสาร์'];

for ($i = 6; $i >= 0; $i--) {
    $date             = date('Y-m-d', strtotime("-$i days"));
    $dow              = (int) date('w', strtotime($date));
    $visitor_labels[] = $day_th_arr[$dow] . ' ' . date('d/m', strtotime($date));

    $res            = mysqli_query($conn, "
        SELECT COUNT(*) AS cnt
        FROM visitor_log
        WHERE DATE(visited_at) = '$date'
    ");
    $visitor_data[] = (int) (mysqli_fetch_assoc($res)['cnt'] ?? 0);
}

$top_places_result = mysqli_query($conn, "
    SELECT p.place_name, COUNT(*) AS view_count
    FROM place_view_log pvl
    JOIN place p ON p.place_id = pvl.place_id
    WHERE DATE(pvl.viewed_at) BETWEEN '$default_from' AND '$default_to'
    GROUP BY pvl.place_id
    ORDER BY view_count DESC
    LIMIT 5
");
$top_place_labels = [];
$top_place_data   = [];

while ($row = mysqli_fetch_assoc($top_places_result)) {
    $top_place_labels[] = $row['place_name'];
    $top_place_data[]   = (int) $row['view_count'];
}

if (empty($top_place_labels)) {
    $fb = mysqli_query($conn, "SELECT place_name FROM place ORDER BY place_id DESC LIMIT 5");
    while ($row = mysqli_fetch_assoc($fb)) {
        $top_place_labels[] = $row['place_name'];
        $top_place_data[]   = 0;
    }
}

$all_places_res = mysqli_query($conn, "SELECT place_name FROM place ORDER BY place_name ASC");
$all_place_names = [];
while ($row = mysqli_fetch_assoc($all_places_res)) {
    $all_place_names[] = $row['place_name'];
}

$age_ranges = ['15-25', '26-35', '36-45', '46-55', '56-65', '65+'];
$age_counts = [];
$age_total  = max(1, (int) (mysqli_fetch_assoc(
    mysqli_query($conn, "
        SELECT COUNT(*) AS total
        FROM visitor_log
        WHERE DATE(visited_at) BETWEEN '$default_from' AND '$default_to'
    ")
)['total'] ?? 1));

foreach ($age_ranges as $range) {
    $res               = mysqli_query($conn, "
        SELECT COUNT(*) AS cnt
        FROM visitor_log
        WHERE age_range = '$range'
          AND DATE(visited_at) BETWEEN '$default_from' AND '$default_to'
    ");
    $age_counts[$range] = (int) (mysqli_fetch_assoc($res)['cnt'] ?? 0);
}

$gender_data   = [];
$gender_labels = [];
$gender_res    = mysqli_query($conn, "
    SELECT gender, COUNT(*) AS cnt
    FROM visitor_log
    WHERE DATE(visited_at) BETWEEN '$default_from' AND '$default_to'
    GROUP BY gender
    ORDER BY cnt DESC
");

while ($grow = mysqli_fetch_assoc($gender_res)) {
    $lbl = match (strtolower($grow['gender'])) {
        'male'        => 'เพศชาย',
        'female'      => 'เพศหญิง',
        'lgbtq+'      => 'LGBTQ+',
        'unspecified' => 'ไม่ระบุ',
        default       => $grow['gender'],
    };
    $gender_labels[] = $lbl;
    $gender_data[]   = (int) $grow['cnt'];
}
$gender_total = max(1, array_sum($gender_data));

$total_visitor = (int) (mysqli_fetch_assoc(
    mysqli_query($conn, "
        SELECT COUNT(*) AS total
        FROM visitor_log
        WHERE DATE(visited_at) BETWEEN '$default_from' AND '$default_to'
    ")
)['total'] ?? 0);

$v_prev = (int) (mysqli_fetch_assoc(
    mysqli_query($conn, "
        SELECT COUNT(*) AS cnt
        FROM visitor_log
        WHERE DATE(visited_at) BETWEEN '"
            . date('Y-m-d', strtotime('-13 days')) . "'
          AND '" . date('Y-m-d', strtotime('-7 days')) . "'
    ")
)['cnt'] ?? 0);

$visitor_pct_diff = ($v_prev > 0)
    ? round(($total_visitor - $v_prev) / $v_prev * 100)
    : ($total_visitor > 0 ? 100 : 0);

function pct_badge(int $pct, string $label = 'จากช่วงก่อนหน้า'): string
{
    if ($pct > 0) {
        return "<span class='pct-badge pct-up'>
                    <i class='fa fa-arrow-up'></i> +{$pct}% {$label}
                </span>";
    }
    if ($pct < 0) {
        return "<span class='pct-badge pct-down'>
                    <i class='fa fa-arrow-down'></i> {$pct}% {$label}
                </span>";
    }
    return "<span class='pct-badge pct-flat'>
                <i class='fa fa-minus'></i> เท่าเดิม
            </span>";
}

$visitor_labels_json   = json_encode($visitor_labels,   JSON_UNESCAPED_UNICODE);
$visitor_data_json     = json_encode($visitor_data);
$top_place_labels_json = json_encode($top_place_labels, JSON_UNESCAPED_UNICODE);
$top_place_data_json   = json_encode($top_place_data);
$gender_labels_json    = json_encode($gender_labels,    JSON_UNESCAPED_UNICODE);
$gender_data_json      = json_encode($gender_data);
$all_place_names_json  = json_encode($all_place_names,  JSON_UNESCAPED_UNICODE);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>

<style>
/* ── Layout หลัก ── */
.dashboard-wrapper {
    padding: 28px 32px;
    background: #f0f2f0;
    min-height: calc(100vh - 64px);
}

/* ── Quick Cards ── */
.quick-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 28px;
}
.quick-card {
    background: #fff;
    border-radius: 16px;
    padding: 20px 24px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 2px 10px rgba(0,0,0,.06);
    text-decoration: none;
    color: inherit;
    transition: transform .18s, box-shadow .18s;
    border: 1.5px solid transparent;
}
.quick-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0,0,0,.10);
    border-color: #2d7a3a;
}
.quick-card-icon {
    width: 48px; height: 48px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; flex-shrink: 0;
}
.icon-green  { background: #e6f4ea; color: #2d7a3a; }
.icon-blue   { background: #e3f0fb; color: #2563eb; }
.icon-amber  { background: #fef9e7; color: #d97706; }
.icon-purple { background: #f3e8ff; color: #7c3aed; }

.quick-card-info p  { margin: 0; font-size: 12px; color: #888; }
.quick-card-info h3 { margin: 2px 0 0; font-size: 22px; font-weight: 700; color: #1a1a1a; }
.quick-card-info span { font-size: 13px; font-weight: 600; color: #2d7a3a; }

/* ── Filter Navbar ── */
.filter-navbar {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    background: #fff;
    border-radius: 16px;
    padding: 10px 16px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,.06);
    border: 1.5px solid #e8ede8;
    position: relative;
    z-index: 100;
}

.fn-dropdown { position: relative; }

.fn-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    font-size: 13px;
    font-weight: 500;
    padding: 7px 13px;
    border-radius: 20px;
    border: 1.5px solid #ddd;
    background: #f7f7f7;
    color: #444;
    cursor: pointer;
    font-family: inherit;
    transition: all .15s;
    white-space: nowrap;
    user-select: none;
}
.fn-btn:hover { border-color: #2d7a3a; color: #2d7a3a; background: #f0f8f1; }
.fn-btn.active { border-color: #2d7a3a; background: #e6f4ea; color: #1e6b2b; }
.fn-btn .fn-badge {
    background: #2d7a3a;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    padding: 1px 6px;
    border-radius: 20px;
    min-width: 18px;
    text-align: center;
}
.fn-btn .fn-chevron { font-size: 10px; opacity: .5; transition: transform .2s; }
.fn-btn.open .fn-chevron { transform: rotate(180deg); }

.fn-panel {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    background: #fff;
    border: 1.5px solid #e0e0e0;
    border-radius: 14px;
    box-shadow: 0 8px 28px rgba(0,0,0,.12);
    padding: 16px;
    min-width: 280px;
    z-index: 999;
    display: none;
}
.fn-panel.show { display: block; }
.fn-panel-label {
    font-size: 11px;
    font-weight: 700;
    color: #888;
    text-transform: uppercase;
    letter-spacing: .07em;
    margin-bottom: 10px;
}

.fn-quick-dates { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 10px; }

.fn-custom-date { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-top: 8px; }
.fn-date-wrap {
    display: flex;
    align-items: center;
    gap: 6px;
    border: 1.5px solid #ddd;
    border-radius: 8px;
    padding: 5px 9px;
    font-size: 12px;
    background: #f7f7f7;
}
.fn-date-wrap input[type=date] {
    border: none;
    background: transparent;
    font-size: 12px;
    color: #333;
    outline: none;
    font-family: inherit;
}

.fn-chips { display: flex; flex-wrap: wrap; gap: 6px; }
.fn-chip {
    font-size: 12px;
    padding: 4px 12px;
    border-radius: 20px;
    border: 1.5px solid #ddd;
    background: #f7f7f7;
    color: #555;
    cursor: pointer;
    transition: all .15s;
    user-select: none;
}
.fn-chip:hover { border-color: #2d7a3a; color: #2d7a3a; }
.fn-chip.sel { background: #e6f4ea; border-color: #2d7a3a; color: #1e6b2b; font-weight: 600; }

.fn-place-search { position: relative; margin-bottom: 8px; }
.fn-place-search input {
    width: 100%;
    box-sizing: border-box;
    font-size: 12px;
    padding: 7px 10px 7px 28px;
    border-radius: 8px;
    border: 1.5px solid #ddd;
    background: #f7f7f7;
    outline: none;
    font-family: inherit;
    transition: border-color .15s;
}
.fn-place-search input:focus { border-color: #2d7a3a; background: #fff; }
.fn-search-icon {
    position: absolute;
    left: 9px;
    top: 50%;
    transform: translateY(-50%);
    color: #aaa;
    font-size: 12px;
    pointer-events: none;
}
.fn-place-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    max-height: 96px;
    overflow-y: auto;
}


.fn-panel-footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 14px;
    padding-top: 12px;
    border-top: 1px solid #f0f0f0;
}
.fn-footer-reset {
    font-size: 12px;
    padding: 5px 12px;
    border-radius: 8px;
    border: 1.5px solid #ddd;
    background: #fff;
    color: #888;
    cursor: pointer;
    font-family: inherit;
}
.fn-footer-reset:hover { border-color: #c0392b; color: #c0392b; }
.fn-footer-done {
    font-size: 12px;
    font-weight: 600;
    padding: 5px 14px;
    border-radius: 8px;
    border: none;
    background: #2d7a3a;
    color: #fff;
    cursor: pointer;
    font-family: inherit;
}
.fn-footer-done:hover { background: #235f2d; }

.fn-apply-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    font-size: 13px;
    font-weight: 600;
    padding: 7px 18px;
    border-radius: 20px;
    border: none;
    background: #2d7a3a;
    color: #fff;
    cursor: pointer;
    font-family: inherit;
    transition: background .15s;
    margin-left: auto;
}
.fn-apply-btn:hover { background: #235f2d; }
.fn-apply-btn:disabled { background: #aaa; cursor: not-allowed; }

.fn-sep { width: 1px; height: 24px; background: #e0e0e0; margin: 0 2px; flex-shrink: 0; }

/* ── Date Range Bar ── */
.date-range-bar {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    background: #fff;
    border-radius: 14px;
    padding: 12px 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,.06);
    border: 1px solid #e8ede8;
}
.date-range-bar label { font-size: 13px; font-weight: 600; color: #555; white-space: nowrap; }
.date-range-info { font-size: 12px; color: #999; margin-left: auto; white-space: nowrap; }

/* ── Grid กราฟ ── */
.chart-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
@media (max-width: 900px) { .chart-grid { grid-template-columns: 1fr; } }

.chart-card {
    background: #fff;
    border-radius: 18px;
    padding: 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,.06);
}
.chart-card h4     { margin: 0 0 4px; font-size: 15px; font-weight: 600; color: #1a1a1a; }
.chart-subtitle    { font-size: 12px; color: #999; margin: 0 0 14px; }
.chart-container   { position: relative; width: 100%; }

.age-bar-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 18px; }
.age-bar-item { display: grid; grid-template-columns: 56px 1fr 56px; align-items: center; gap: 12px; }
.age-label    { font-size: 13px; color: #555; font-weight: 500; }
.age-track    { background: #eee; border-radius: 99px; height: 8px; overflow: hidden; }
.age-fill     { height: 100%; border-radius: 99px; transition: width 1s ease; }
.age-pct      { font-size: 12px; font-weight: 600; color: #333; text-align: center; background: #f5f5f5; border: 1px solid #e0e0e0; border-radius: 6px; padding: 2px 6px; }

.export-bar  { display: flex; gap: 10px; justify-content: flex-end; margin-bottom: 20px; }
.btn-export  { display: flex; align-items: center; gap: 6px; padding: 7px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; border: none; cursor: pointer; transition: opacity .15s; }
.btn-export:hover { opacity: .85; }
.btn-excel { background: #1d6f42; color: #fff; }
.btn-pdf   { background: #c0392b; color: #fff; }

.empty-state { text-align: center; padding: 32px 0; color: #bbb; font-size: 13px; }

.chart-wrap { position: relative; }
.chart-loading {
    position: absolute; inset: 0;
    display: flex; align-items: center; justify-content: center;
    background: rgba(255,255,255,.85);
    border-radius: 12px;
    font-size: 13px; color: #888; gap: 8px;
    z-index: 10;
}

.pct-badge { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 20px; margin-top: 5px; }
.pct-up   { background: #e6f4ea; color: #1e6b2b; }
.pct-down { background: #fdecea; color: #c0392b; }
.pct-flat { background: #f5f5f5; color: #888; }
</style>

<div class="dashboard-wrapper">

  <!-- Export Bar -->
  <div class="export-bar">
    <button class="btn-export btn-excel" onclick="exportExcel()">
      <i class="fa fa-file-excel"></i> Excel
    </button>
    <button class="btn-export btn-pdf" onclick="exportPDF()">
      <i class="fa fa-file-pdf"></i> PDF
    </button>
  </div>

  <!-- Quick Cards -->
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
    <div class="quick-card" style="cursor:default;">
      <div class="quick-card-icon icon-amber"><i class="fa fa-users"></i></div>
      <div class="quick-card-info">
        <p id="visitorCardLabel">ผู้เข้าชม (7 วันล่าสุด)</p>
        <h3 id="totalVisitorNum"><?= $total_visitor ?></h3>
        <div id="visitorPctBadge"><?= pct_badge($visitor_pct_diff, 'จาก 7 วันก่อน') ?></div>
      </div>
    </div>
  </div>

  <!-- ═══════════════════════════════════════════
       Filter Navbar
  ════════════════════════════════════════════ -->
  <div class="filter-navbar" id="filterNavbar">

    <!-- ① ช่วงเวลา -->
    <div class="fn-dropdown" id="dd-time">
      <button class="fn-btn active" id="btn-time" onclick="toggleDropdown('time')">
        <i class="fa fa-calendar-alt" style="font-size:12px;"></i>
        <span id="btn-time-label">ช่วงเวลา</span>
        <span class="fn-chevron">▾</span>
      </button>
      <div class="fn-panel" id="panel-time">
        <div class="fn-panel-label">ช่วงเวลา</div>
        <div class="fn-quick-dates">
          <button class="fn-chip sel" data-range="7d"       onclick="setFnRange('7d',       this)">7 วัน</button>
          <button class="fn-chip"    data-range="today"     onclick="setFnRange('today',     this)">วันนี้</button>
          <button class="fn-chip"    data-range="yesterday" onclick="setFnRange('yesterday', this)">เมื่อวาน</button>
          <button class="fn-chip"    data-range="30d"       onclick="setFnRange('30d',       this)">30 วัน</button>
          <button class="fn-chip"    data-range="thismonth" onclick="setFnRange('thismonth', this)">เดือนนี้</button>
          <button class="fn-chip"    data-range="lastmonth" onclick="setFnRange('lastmonth', this)">เดือนที่แล้ว</button>
          <button class="fn-chip"    data-range="alltime"   onclick="setFnRangeAllTime(this)">ทั้งหมด</button>
          <button class="fn-chip"    data-range="custom"    onclick="setFnRange('custom',    this)">กำหนดเอง</button>
        </div>
        <div class="fn-custom-date" id="fnCustomDateRow" style="display:none;">
          <div class="fn-date-wrap">
            <i class="fa fa-calendar" style="color:#2d7a3a; font-size:11px;"></i>
            <input type="date" id="fnDateFrom" value="<?= $default_from ?>">
          </div>
          <span style="color:#bbb; font-size:13px;">—</span>
          <div class="fn-date-wrap">
            <i class="fa fa-calendar" style="color:#2d7a3a; font-size:11px;"></i>
            <input type="date" id="fnDateTo" value="<?= $default_to ?>">
          </div>
        </div>
        <div class="fn-panel-footer">
          <button class="fn-footer-done" onclick="closeDropdown('time')">เสร็จสิ้น</button>
        </div>
      </div>
    </div>

    <!-- ② เพศ -->
    <div class="fn-dropdown" id="dd-gender">
      <button class="fn-btn" id="btn-gender" onclick="toggleDropdown('gender')">
        <i class="fa fa-venus-mars" style="font-size:12px;"></i>
        <span id="btn-gender-label">เพศ</span>
        <span class="fn-chevron">▾</span>
      </button>
      <div class="fn-panel" id="panel-gender">
        <div class="fn-panel-label">เพศ (เลือกได้หลายเพศ)</div>
        <div class="fn-chips" id="fnGenderChips">
          <span class="fn-chip" onclick="toggleFnChip(this,'gender')">เพศชาย</span>
          <span class="fn-chip" onclick="toggleFnChip(this,'gender')">เพศหญิง</span>
          <span class="fn-chip" onclick="toggleFnChip(this,'gender')">LGBTQ+</span>
          <span class="fn-chip" onclick="toggleFnChip(this,'gender')">ไม่ระบุ</span>
        </div>
        <div class="fn-panel-footer">
          <button class="fn-footer-reset" onclick="clearFnGroup('gender')">ล้าง</button>
          <button class="fn-footer-done" onclick="closeDropdown('gender')">เสร็จสิ้น</button>
        </div>
      </div>
    </div>

    <!-- ③ ช่วงอายุ -->
    <div class="fn-dropdown" id="dd-age">
      <button class="fn-btn" id="btn-age" onclick="toggleDropdown('age')">
        <i class="fa fa-users" style="font-size:12px;"></i>
        <span id="btn-age-label">ช่วงอายุ</span>
        <span class="fn-chevron">▾</span>
      </button>
      <div class="fn-panel" id="panel-age">
        <div class="fn-panel-label">ช่วงอายุ (เลือกได้หลายช่วง)</div>
        <div class="fn-chips" id="fnAgeChips">
          <?php foreach (['15-25','26-35','36-45','46-55','56-65','65+'] as $ar): ?>
          <span class="fn-chip" onclick="toggleFnChip(this,'age')"><?= $ar ?></span>
          <?php endforeach; ?>
        </div>
        <div class="fn-panel-footer">
          <button class="fn-footer-reset" onclick="clearFnGroup('age')">ล้าง</button>
          <button class="fn-footer-done" onclick="closeDropdown('age')">เสร็จสิ้น</button>
        </div>
      </div>
    </div>

    <!-- ④ สถานที่ -->
    <div class="fn-dropdown" id="dd-place">
      <button class="fn-btn" id="btn-place" onclick="toggleDropdown('place')">
        <i class="fa fa-map-marker-alt" style="font-size:12px;"></i>
        <span id="btn-place-label">สถานที่</span>
        <span class="fn-chevron">▾</span>
      </button>
      <div class="fn-panel" id="panel-place" style="min-width:300px;">
        <div class="fn-panel-label">สถานที่ (เลือกได้หลายแห่ง)</div>
        <div class="fn-place-search">
          <i class="fa fa-search fn-search-icon"></i>
          <input type="text" id="fnPlaceSearch" placeholder="ค้นหาสถานที่..."
                 oninput="filterFnPlaces(this.value)">
        </div>
        <div class="fn-place-chips" id="fnPlaceChips"></div>
        <div class="fn-panel-footer">
          <button class="fn-footer-reset" onclick="clearFnGroup('place')">ล้าง</button>
          <button class="fn-footer-done" onclick="closeDropdown('place')">เสร็จสิ้น</button>
        </div>
      </div>
    </div>

    <div class="fn-sep"></div>

    <!-- ⑤ ปุ่มกรอง -->
    <button class="fn-apply-btn" id="btnFnApply" onclick="applyAdvFilter()">
      <i class="fa fa-filter"></i> กรอง
      <span class="fn-badge" id="fnCountBadge" style="display:none;">0</span>
    </button>

  </div><!-- /.filter-navbar -->

  <!-- Date Range Info Bar -->
  <div class="date-range-bar">
    <i class="fa fa-calendar-alt" style="color:#2d7a3a; font-size:14px;"></i>
    <label>กำลังแสดง:</label>
    <span id="dateRangeInfo" style="font-size:13px; color:#333; font-weight:500;">แสดงข้อมูล 7 วันย้อนหลัง</span>
    <span class="date-range-info" id="activeFilterSummary"></span>
  </div>

  <!-- Chart Grid 2×2 -->
  <div class="chart-grid">

    <div class="chart-card">
      <h4>ผู้เข้าใช้งานเว็บไซต์</h4>
      <p class="chart-subtitle" id="visitorSubtitle">7 วันย้อนหลัง</p>
      <div class="chart-wrap">
        <div class="chart-container" style="height:220px;">
          <canvas id="visitorChart"></canvas>
        </div>
        <div class="chart-loading" id="loadingVisitor" style="display:none;">
          <i class="fa fa-spinner fa-spin"></i> กำลังโหลด...
        </div>
      </div>
    </div>

    <div class="chart-card">
      <h4>สถานที่ที่มีผู้เข้าชมมากที่สุด</h4>
      <p class="chart-subtitle">Top 5</p>
      <div class="chart-wrap">
        <div class="empty-state" id="placeEmpty"
             <?= array_sum($top_place_data) > 0 ? 'style="display:none;"' : '' ?>>
          <i class="fa fa-chart-bar" style="font-size:32px; display:block; margin-bottom:8px;"></i>
          ยังไม่มีข้อมูลการเข้าชม
        </div>
        <div class="chart-container" style="height:220px;" id="placeChartWrap"
             <?= array_sum($top_place_data) == 0 ? 'style="display:none;"' : '' ?>>
          <canvas id="placeChart"></canvas>
        </div>
        <div class="chart-loading" id="loadingPlace" style="display:none;">
          <i class="fa fa-spinner fa-spin"></i> กำลังโหลด...
        </div>
      </div>
    </div>

    <div class="chart-card">
      <h4>ช่วงอายุของผู้ใช้งานเว็บไซต์</h4>
      <p class="chart-subtitle" id="ageSubtitle">
        จากแบบสอบถาม (ทั้งหมด <?= $age_total ?> คน)
      </p>
      <?php
        $age_colors = ['#2d7a3a','#d4a017','#c0796a','#2c3e7a','#e07b30','#5b8de8'];
        $i = 0;
      ?>
      <!-- ▼ จุดที่ 1: ห่อด้วย chart-wrap และเพิ่ม loadingAge -->
      <div class="chart-wrap">
        <ul class="age-bar-list" id="ageBarList">
          <?php foreach ($age_ranges as $range):
            $cnt   = $age_counts[$range];
            $pct   = ($age_total > 0) ? round($cnt / $age_total * 100) : 0;
            $color = $age_colors[$i % count($age_colors)];
            $i++;
          ?>
          <li class="age-bar-item">
            <span class="age-label"><?= $range ?></span>
            <div class="age-track">
              <div class="age-fill" style="width:<?= $pct ?>%; background:<?= $color ?>;"></div>
            </div>
            <span class="age-pct">
              <?= $pct ?>%<br>
              <small style="font-weight:400; color:#999;">(<?= $cnt ?>)</small>
            </span>
          </li>
          <?php endforeach; ?>
        </ul>
        <div class="chart-loading" id="loadingAge" style="display:none;">
          <i class="fa fa-spinner fa-spin"></i> กำลังโหลด...
        </div>
      </div>
      <!-- ▲ จุดที่ 1 จบ -->
    </div>

    <div class="chart-card">
      <h4>เพศของผู้ใช้งานเว็บไซต์</h4>
      <p class="chart-subtitle" id="genderSubtitle">
        จากแบบสอบถาม (ทั้งหมด <?= $gender_total ?> คน)
      </p>
      <div class="chart-wrap">
        <div class="empty-state" id="genderEmpty"
             <?= array_sum($gender_data) > 0 ? 'style="display:none;"' : '' ?>>
          <i class="fa fa-venus-mars" style="font-size:32px; display:block; margin-bottom:8px;"></i>
          ยังไม่มีข้อมูล
        </div>
        <div class="chart-container" style="height:220px;" id="genderChartWrap"
             <?= array_sum($gender_data) == 0 ? 'style="display:none;"' : '' ?>>
          <canvas id="genderChart"></canvas>
        </div>
        <div class="chart-loading" id="loadingGender" style="display:none;">
          <i class="fa fa-spinner fa-spin"></i> กำลังโหลด...
        </div>
      </div>
    </div>

  </div>
</div>

<script>
const visitorLabels0 = <?= $visitor_labels_json ?>;
const visitorData0   = <?= $visitor_data_json ?>;
const placeLabels0   = <?= $top_place_labels_json ?>;
const placeData0     = <?= $top_place_data_json ?>;
const genderLabels0  = <?= $gender_labels_json ?>;
const genderData0    = <?= $gender_data_json ?>;
const ALL_PLACES     = <?= $all_place_names_json ?>;

const DAY_COLOR = {
    'อาทิตย์': '#e53e3e', 'จันทร์': '#ecc94b', 'อังคาร': '#d53f8c',
    'พุธ': '#38a169', 'พฤหัส': '#dd6b20', 'ศุกร์': '#3182ce', 'เสาร์': '#805ad5',
};
function dayColor(label) { return DAY_COLOR[label.split(' ')[0]] || '#aaa'; }
const ageColors = ['#2d7a3a','#d4a017','#c0796a','#2c3e7a','#e07b30','#5b8de8'];
const gColorMap = { 'เพศชาย':'#2c3e7a','เพศหญิง':'#d4a017','LGBTQ+':'#c0796a','ไม่ระบุ':'#aaa' };

// ── Filter State ──
let advCurRange      = '7d';
let selAges          = new Set();
let selGenders       = new Set();
let selPlaces        = new Set();
let filteredFnPlaces = [...ALL_PLACES];
let openDD           = null;

const RANGE_LABELS = {
    today:'วันนี้', yesterday:'เมื่อวาน', '7d':'7 วัน',
    '30d':'30 วัน', thismonth:'เดือนนี้', lastmonth:'เดือนที่แล้ว',
    alltime:'ทั้งหมด', custom:'กำหนดเอง'
};

let visitorChart, placeChart = null, genderChart = null;

document.addEventListener('DOMContentLoaded', () => {
    visitorChart = new Chart(
        document.getElementById('visitorChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: visitorLabels0,
                datasets: [{ data: visitorData0, backgroundColor: visitorLabels0.map(dayColor), borderRadius: 6, borderSkipped: false }],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => ` ${c.parsed.y} คน` } } },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                    y: { beginAtZero: true, grid: { color: '#eee' }, ticks: { font: { size: 11 }, stepSize: 1, callback: v => Number.isInteger(v) ? v : null } },
                },
            },
        }
    );

    if (placeData0.some(v => v > 0)) {
        placeChart = new Chart(
            document.getElementById('placeChart').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: placeLabels0,
                    datasets: [{ data: placeData0, backgroundColor: ['#d4a017','#5b8de8','#2c3e7a','#c0796a','#2d7a3a'], borderRadius: 5, borderSkipped: false }],
                },
                options: {
                    indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => ` ${c.parsed.x} ครั้ง` } } },
                    scales: {
                        x: { beginAtZero: true, grid: { color: '#eee' }, ticks: { stepSize: 1, font: { size: 11 }, callback: v => Number.isInteger(v) ? v : null } },
                        y: { grid: { display: false }, ticks: { font: { size: 12 } } },
                    },
                },
            }
        );
    }

    if (genderData0.some(v => v > 0)) {
        genderChart = new Chart(
            document.getElementById('genderChart').getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: genderLabels0,
                    datasets: [{ data: genderData0, backgroundColor: genderLabels0.map(l => gColorMap[l] || '#5b8de8'), borderWidth: genderData0.map(v => v === 0 ? 0 : 3), borderColor: '#fff', hoverOffset: 6 }],
                },
                options: {
                    responsive: true, maintainAspectRatio: false, cutout: '60%',
                    plugins: {
                        legend: { position: 'bottom', labels: { padding: 16, font: { size: 12 }, boxWidth: 12, boxHeight: 12 } },
                        tooltip: { callbacks: { label: c => ` ${c.label}: ${c.parsed} คน` } },
                    },
                },
            }
        );
    }

    renderFnPlaceChips();
});

// ── Dropdown ──
function toggleDropdown(name) {
    if (openDD && openDD !== name) {
        document.getElementById('panel-' + openDD).classList.remove('show');
        document.getElementById('btn-' + openDD).classList.remove('open');
    }
    const panel = document.getElementById('panel-' + name);
    const btn   = document.getElementById('btn-' + name);
    const isOpen = panel.classList.contains('show');
    panel.classList.toggle('show', !isOpen);
    btn.classList.toggle('open', !isOpen);
    openDD = isOpen ? null : name;
}

function closeDropdown(name) {
    document.getElementById('panel-' + name).classList.remove('show');
    document.getElementById('btn-' + name).classList.remove('open');
    if (openDD === name) openDD = null;
    updateFnBadge();
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.fn-dropdown')) {
        document.querySelectorAll('.fn-panel.show').forEach(p => p.classList.remove('show'));
        document.querySelectorAll('.fn-btn.open').forEach(b => b.classList.remove('open'));
        openDD = null;
    }
});

// ── Date Range ──
function setFnRange(range, btn) {
    advCurRange = range;
    document.querySelectorAll('#panel-time .fn-chip').forEach(b => b.classList.remove('sel'));
    btn.classList.add('sel');
    document.getElementById('fnCustomDateRow').style.display = range === 'custom' ? 'flex' : 'none';
    updateFnBadge();
}

function setFnRangeAllTime(btn) {
    advCurRange = 'alltime';
    document.querySelectorAll('#panel-time .fn-chip').forEach(b => b.classList.remove('sel'));
    btn.classList.add('sel');
    document.getElementById('fnCustomDateRow').style.display = 'none';
    fetch('dashboard.php?ajax=1&get_min_date=1')
        .then(r => r.json())
        .then(d => {
            document.getElementById('fnDateFrom').value = d.min_date || '2026-01-01';
            document.getElementById('fnDateTo').value   = new Date().toISOString().slice(0, 10);
        })
        .catch(() => {
            document.getElementById('fnDateFrom').value = '2026-01-01';
            document.getElementById('fnDateTo').value   = new Date().toISOString().slice(0, 10);
        });
    updateFnBadge();
}

function getDateRangeFromMode() {
    const today    = new Date();
    const todayStr = today.toISOString().slice(0, 10);
    const yd       = new Date(today); yd.setDate(yd.getDate() - 1);
    const ydStr    = yd.toISOString().slice(0, 10);
    switch (advCurRange) {
        case 'today':     return { from: todayStr, to: todayStr };
        case 'yesterday': return { from: ydStr, to: ydStr };
        case '7d': { const d = new Date(today); d.setDate(d.getDate()-6); return { from: d.toISOString().slice(0,10), to: todayStr }; }
        case '30d': { const d = new Date(today); d.setDate(d.getDate()-29); return { from: d.toISOString().slice(0,10), to: todayStr }; }
        case 'thismonth': { const d = new Date(today.getFullYear(), today.getMonth(), 1); return { from: d.toISOString().slice(0,10), to: todayStr }; }
        case 'lastmonth': { const f = new Date(today.getFullYear(), today.getMonth()-1, 1); const l = new Date(today.getFullYear(), today.getMonth(), 0); return { from: f.toISOString().slice(0,10), to: l.toISOString().slice(0,10) }; }
        case 'custom':  return { from: document.getElementById('fnDateFrom').value, to: document.getElementById('fnDateTo').value };
        case 'alltime': return { from: document.getElementById('fnDateFrom').value || '2020-01-01', to: todayStr };
        default: return { from: todayStr, to: todayStr };
    }
}

// ── Chip Toggle ──
function toggleFnChip(el, group) {
    const val = el.textContent.trim();
    const set = group === 'age' ? selAges : selGenders;
    if (set.has(val)) { set.delete(val); el.classList.remove('sel'); }
    else              { set.add(val);    el.classList.add('sel');    }
    updateFnBadge();
}

function clearFnGroup(group) {
    if (group === 'age') {
        selAges.clear();
        document.querySelectorAll('#fnAgeChips .fn-chip').forEach(c => c.classList.remove('sel'));
    } else if (group === 'gender') {
        selGenders.clear();
        document.querySelectorAll('#fnGenderChips .fn-chip').forEach(c => c.classList.remove('sel'));
    } else if (group === 'place') {
        selPlaces.clear();
        renderFnPlaceChips();
    }
    updateFnBadge();
}

// ── Place Chips ──
function renderFnPlaceChips() {
    const wrap = document.getElementById('fnPlaceChips');
    wrap.innerHTML = '';
    filteredFnPlaces.forEach(p => {
        const span = document.createElement('span');
        span.className = 'fn-chip' + (selPlaces.has(p) ? ' sel' : '');
        span.textContent = p;
        span.onclick = () => {
            if (selPlaces.has(p)) { selPlaces.delete(p); span.classList.remove('sel'); }
            else                  { selPlaces.add(p);    span.classList.add('sel');    }
            updateFnBadge();
        };
        wrap.appendChild(span);
    });
}

function filterFnPlaces(q) {
    filteredFnPlaces = q
        ? ALL_PLACES.filter(p => p.toLowerCase().includes(q.toLowerCase()))
        : [...ALL_PLACES];
    renderFnPlaceChips();
}

// ── Badge & Button Labels ──
function updateFnBadge() {
    let count = 0;
    if (advCurRange !== '7d') count++;
    count += selAges.size + selGenders.size + selPlaces.size;

    const badge = document.getElementById('fnCountBadge');
    badge.textContent = count;
    badge.style.display = count > 0 ? 'inline-block' : 'none';

    const timeBtn = document.getElementById('btn-time');
    document.getElementById('btn-time-label').textContent =
        advCurRange !== '7d' ? (RANGE_LABELS[advCurRange] || 'ช่วงเวลา') : 'ช่วงเวลา';
    timeBtn.classList.toggle('active', advCurRange !== '7d');

    document.getElementById('btn-gender-label').textContent =
        selGenders.size > 0 ? `เพศ (${selGenders.size})` : 'เพศ';
    document.getElementById('btn-gender').classList.toggle('active', selGenders.size > 0);

    document.getElementById('btn-age-label').textContent =
        selAges.size > 0 ? `ช่วงอายุ (${selAges.size})` : 'ช่วงอายุ';
    document.getElementById('btn-age').classList.toggle('active', selAges.size > 0);

    document.getElementById('btn-place-label').textContent =
        selPlaces.size > 0 ? `สถานที่ (${selPlaces.size})` : 'สถานที่';
    document.getElementById('btn-place').classList.toggle('active', selPlaces.size > 0);
}

// ── Reset All ──
function resetAdvFilter() {
    advCurRange = '7d';
    document.querySelectorAll('#panel-time .fn-chip').forEach(b => b.classList.remove('sel'));
    document.querySelector('#panel-time [data-range="7d"]').classList.add('sel');
    document.getElementById('fnCustomDateRow').style.display = 'none';
    clearFnGroup('age');
    clearFnGroup('gender');
    clearFnGroup('place');
    document.getElementById('fnPlaceSearch').value = '';
    filteredFnPlaces = [...ALL_PLACES];
    renderFnPlaceChips();
    updateFnBadge();
}

// ── Apply Filter ──
async function applyAdvFilter() {
    const { from, to } = getDateRangeFromMode();

    if (!from || !to || from > to) {
        alert('กรุณาเลือกช่วงวันที่ให้ถูกต้อง');
        return;
    }

    const btn = document.getElementById('btnFnApply');
    btn.disabled  = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> กำลังโหลด...';

    // ▼ จุดที่ 2: เพิ่ม loadingAge ตอน show
    ['loadingVisitor', 'loadingPlace', 'loadingGender', 'loadingAge'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'flex';
    });

    try {
        const ageParam    = [...selAges].join(',');
        const genderParam = [...selGenders].join(',');
        const placeParam  = [...selPlaces].join(',');

        const url = `dashboard.php?ajax=1&date_from=${from}&date_to=${to}`
                  + `&age=${encodeURIComponent(ageParam)}`
                  + `&gender=${encodeURIComponent(genderParam)}`
                  + `&place=${encodeURIComponent(placeParam)}`;

        const res  = await fetch(url);
        const data = await res.json();

        visitorChart.data.labels                      = data.visitor_labels;
        visitorChart.data.datasets[0].data            = data.visitor_data;
        visitorChart.data.datasets[0].backgroundColor = data.visitor_labels.map(dayColor);
        visitorChart.update();

        const days = Math.round((new Date(to) - new Date(from)) / 86400000) + 1;
        document.getElementById('visitorSubtitle').textContent =
            `${days} วัน (${fmtTH(from)} – ${fmtTH(to)})`;
        document.getElementById('totalVisitorNum').textContent =
            data.total_visitor.toLocaleString();
        document.getElementById('visitorCardLabel').textContent =
            `ผู้เข้าชม (${days} วัน)`;
        document.getElementById('visitorPctBadge').innerHTML =
            pctHTML(data.visitor_pct, 'จากช่วงก่อนหน้า');

        const hasPlace = data.place_data.some(v => v > 0);
        document.getElementById('placeEmpty').style.display     = hasPlace ? 'none'  : 'block';
        document.getElementById('placeChartWrap').style.display = hasPlace ? 'block' : 'none';

        if (hasPlace) {
            if (!placeChart) {
                placeChart = new Chart(
                    document.getElementById('placeChart').getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: data.place_labels,
                            datasets: [{ data: data.place_data, backgroundColor: ['#d4a017','#5b8de8','#2c3e7a','#c0796a','#2d7a3a'], borderRadius: 5, borderSkipped: false }],
                        },
                        options: {
                            indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                            plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => ` ${c.parsed.x} ครั้ง` } } },
                            scales: {
                                x: { beginAtZero: true, grid: { color: '#eee' }, ticks: { stepSize: 1, font: { size: 11 }, callback: v => Number.isInteger(v) ? v : null } },
                                y: { grid: { display: false }, ticks: { font: { size: 12 } } },
                            },
                        },
                    }
                );
            } else {
                placeChart.data.labels           = data.place_labels;
                placeChart.data.datasets[0].data = data.place_data;
                placeChart.update();
            }
        }

        const ageList = document.getElementById('ageBarList');
        ageList.innerHTML = '';
        data.age.forEach((a, i) => {
            const color = ageColors[i % ageColors.length];
            ageList.innerHTML += `
                <li class="age-bar-item">
                    <span class="age-label">${a.range}</span>
                    <div class="age-track">
                        <div class="age-fill" style="width:${a.pct}%; background:${color};"></div>
                    </div>
                    <span class="age-pct">
                        ${a.pct}%<br>
                        <small style="font-weight:400; color:#999;">(${a.count})</small>
                    </span>
                </li>`;
        });
        document.getElementById('ageSubtitle').textContent =
            `จากแบบสอบถาม (ทั้งหมด ${data.age_total} คน)`;

        const hasGender = data.gender_data.some(v => v > 0);
        document.getElementById('genderEmpty').style.display     = hasGender ? 'none'  : 'block';
        document.getElementById('genderChartWrap').style.display = hasGender ? 'block' : 'none';

        if (hasGender) {
            const gc = data.gender_labels.map(l => gColorMap[l] || '#5b8de8');
            if (!genderChart) {
                genderChart = new Chart(
                    document.getElementById('genderChart').getContext('2d'), {
                        type: 'doughnut',
                        data: {
                            labels: data.gender_labels,
                            datasets: [{ data: data.gender_data, backgroundColor: gc, borderWidth: data.gender_data.map(v => v === 0 ? 0 : 3), borderColor: '#fff', hoverOffset: 6 }],
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false, cutout: '60%',
                            plugins: {
                                legend: { position: 'bottom', labels: { padding: 16, font: { size: 12 }, boxWidth: 12, boxHeight: 12 } },
                                tooltip: { callbacks: { label: c => ` ${c.label}: ${c.parsed} คน` } },
                            },
                        },
                    }
                );
            } else {
                genderChart.data.labels                      = data.gender_labels;
                genderChart.data.datasets[0].data            = data.gender_data;
                genderChart.data.datasets[0].backgroundColor = gc;
                genderChart.data.datasets[0].borderWidth     = data.gender_data.map(v => v === 0 ? 0 : 3);
                genderChart.update();
            }
        }

        document.getElementById('genderSubtitle').textContent =
            `จากแบบสอบถาม (ทั้งหมด ${data.gender_total} คน)`;

        document.getElementById('dateRangeInfo').textContent =
            `${fmtTH(from)} – ${fmtTH(to)}`;

        const filterParts = [];
        if (selAges.size)    filterParts.push(`อายุ: ${[...selAges].join(', ')}`);
        if (selGenders.size) filterParts.push(`เพศ: ${[...selGenders].join(', ')}`);
        if (selPlaces.size)  filterParts.push(`สถานที่: ${selPlaces.size} แห่ง`);
        document.getElementById('activeFilterSummary').textContent =
            filterParts.length > 0 ? '| ' + filterParts.join(' · ') : '';

    } catch (e) {
        console.error(e);
        alert('เกิดข้อผิดพลาดในการโหลดข้อมูล');
    } finally {
        // ▼ จุดที่ 3: เพิ่ม loadingAge ตอน hide
        ['loadingVisitor', 'loadingPlace', 'loadingGender', 'loadingAge'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.display = 'none';
        });
        btn.disabled  = false;
        btn.innerHTML = '<i class="fa fa-filter"></i> กรอง <span class="fn-badge" id="fnCountBadge" style="display:none;">0</span>';
        updateFnBadge();
    }
}

// ── Helpers ──
function pctHTML(p, l) {
    if (p > 0) return `<span class='pct-badge pct-up'><i class='fa fa-arrow-up'></i> +${p}% ${l}</span>`;
    if (p < 0) return `<span class='pct-badge pct-down'><i class='fa fa-arrow-down'></i> ${p}% ${l}</span>`;
    return `<span class='pct-badge pct-flat'><i class='fa fa-minus'></i> เท่าเดิม</span>`;
}

function fmtTH(iso) {
    const [y, m, d] = iso.split('-');
    const ms = ['ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
    return `${parseInt(d)} ${ms[parseInt(m) - 1]} ${parseInt(y) + 543}`;
}

// ── Export Excel ──
function exportExcel() {
    if (typeof XLSX === 'undefined') {
        const s = document.createElement('script');
        s.src = 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js';
        s.onload = doExcel;
        document.head.appendChild(s);
    } else {
        doExcel();
    }
}

function doExcel() {
    const { from, to } = getDateRangeFromMode();
    const wb = XLSX.utils.book_new();

    const vRows = [['วัน', 'ผู้เข้าชม (คน)']];
    visitorChart.data.labels.forEach((l, i) => vRows.push([l, visitorChart.data.datasets[0].data[i]]));
    XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(vRows), 'ผู้เข้าชม');

    const pRows = [['สถานที่', 'จำนวน (ครั้ง)']];
    if (placeChart) placeChart.data.labels.forEach((l, i) => pRows.push([l, placeChart.data.datasets[0].data[i]]));
    XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(pRows), 'Top 5 สถานที่');

    const aRows = [['ช่วงอายุ', 'จำนวน (คน)', '%']];
    document.querySelectorAll('#ageBarList .age-bar-item').forEach(li => {
        const range = li.querySelector('.age-label').textContent.trim();
        const txt   = li.querySelector('.age-pct').textContent.trim();
        const pct   = txt.split('%')[0];
        const cnt   = txt.match(/\((\d+)\)/)?.[1] ?? '0';
        aRows.push([range, parseInt(cnt), pct + '%']);
    });
    XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(aRows), 'ช่วงอายุ');

    const gRows = [['เพศ', 'จำนวน (คน)']];
    if (genderChart) genderChart.data.labels.forEach((l, i) => gRows.push([l, genderChart.data.datasets[0].data[i]]));
    XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(gRows), 'เพศ');

    const filterInfo = [];
    if (selAges.size)    filterInfo.push('อายุ: ' + [...selAges].join(', '));
    if (selGenders.size) filterInfo.push('เพศ: ' + [...selGenders].join(', '));
    if (selPlaces.size)  filterInfo.push('สถานที่: ' + [...selPlaces].join(', '));

    const sRows = [
        ['รายงาน Dashboard'],
        ['ช่วงวันที่', `${from} ถึง ${to}`],
        ['ตัวกรอง', filterInfo.join(' | ') || 'ไม่มี'],
        [],
        ['หัวข้อ', 'จำนวน'],
        ['สถานที่',  <?= (int)$place_count ?>],
        ['คอนเทนต์', <?= (int)$content_count ?>],
        ['ผู้เข้าชม', parseInt(document.getElementById('totalVisitorNum').textContent.replace(/,/g, '')) || 0],
    ];
    XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(sRows), 'สรุป');

    XLSX.writeFile(wb, 'dashboard_report_' + new Date().toISOString().slice(0, 10) + '.xlsx');
}

// ── Export PDF ──
function exportPDF() {
    const libs = [
        { id: 'jspdf-lib', src: 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js' },
        { id: 'h2c-lib',   src: 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js' },
    ];
    let loaded = 0;
    libs.forEach(l => {
        if (!document.getElementById(l.id)) {
            const s = document.createElement('script');
            s.id = l.id; s.src = l.src;
            s.onload = () => { loaded++; if (loaded === libs.length) doPDF(); };
            document.head.appendChild(s);
        } else { loaded++; if (loaded === libs.length) doPDF(); }
    });
}

async function doPDF() {
    const { jsPDF } = window.jspdf;
    const pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
    const btn = document.querySelector('.btn-pdf');
    const orig = btn.innerHTML;

    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> กำลังสร้าง...';
    btn.disabled  = true;

    try {
        const cv = await html2canvas(document.querySelector('.dashboard-wrapper'), {
            scale: 2, useCORS: true, logging: false, backgroundColor: '#f0f2f0',
        });

        const pw = 210, ph = 297, mg = 10;
        const uw = pw - mg * 2;
        const ih = (cv.height / cv.width) * uw;

        pdf.setFillColor(45, 122, 58);
        pdf.rect(0, 0, pw, 14, 'F');
        pdf.setTextColor(255, 255, 255);
        pdf.setFontSize(11);
        pdf.text('รายงาน Dashboard', mg, 9.5);

        const { from, to } = getDateRangeFromMode();
        pdf.setFontSize(8);
        pdf.text(`${from} – ${to}`, pw - mg, 9.5, { align: 'right' });

        let yp = 16, rem = ih, sy = 0;
        while (rem > 0) {
            const sh = Math.min(rem, ph - yp - mg);
            const sp = (sh / uw) * cv.width;
            const sc = document.createElement('canvas');
            sc.width = cv.width; sc.height = sp;
            sc.getContext('2d').drawImage(cv, 0, sy, cv.width, sp, 0, 0, cv.width, sp);
            pdf.addImage(sc.toDataURL('image/png'), 'PNG', mg, yp, uw, sh);
            rem -= sh; sy += sp;
            if (rem > 0) {
                pdf.addPage();
                pdf.setFillColor(45, 122, 58);
                pdf.rect(0, 0, pw, 14, 'F');
                yp = 16;
            }
        }

        const tp = pdf.internal.getNumberOfPages();
        for (let p = 1; p <= tp; p++) {
            pdf.setPage(p);
            pdf.setFillColor(240, 242, 240);
            pdf.rect(0, ph - 8, pw, 8, 'F');
            pdf.setTextColor(150, 150, 150);
            pdf.setFontSize(7);
            pdf.text(`หน้า ${p}/${tp}`, pw / 2, ph - 3, { align: 'center' });
        }

        pdf.save('dashboard_report_' + new Date().toISOString().slice(0, 10) + '.pdf');

    } catch (e) {
        console.error(e);
        alert('เกิดข้อผิดพลาดในการสร้าง PDF');
    } finally {
        btn.innerHTML = orig;
        btn.disabled  = false;
    }
}
</script>
