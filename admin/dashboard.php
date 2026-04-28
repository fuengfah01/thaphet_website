<?php
include 'check_login.php';
include '../config.php';
include 'header.php';

// ===== จำนวนสถานที่ทั้งหมด =====
$place_count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM place");
$place_count = mysqli_fetch_assoc($place_count_result)['total'] ?? 0;

// ===== จำนวนคอนเทนต์ทั้งหมด =====
$content_count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM content");
$content_count = mysqli_fetch_assoc($content_count_result)['total'] ?? 0;

// ===== จำนวนผู้เข้าชม 7 วันย้อนหลัง (visitor_log) =====
$visitor_data = [];
$visitor_labels = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));

    // label วันภาษาไทย
    $day_th = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัส', 'ศุกร์', 'เสาร์'];
    $dow = (int)date('w', strtotime($date));
    $visitor_labels[] = $day_th[$dow];

    $res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM visitor_log WHERE DATE(visited_at) = '$date'");
    $row = mysqli_fetch_assoc($res);
    $visitor_data[] = (int)($row['cnt'] ?? 0);
}

// ===== Top 5 สถานที่ที่กดชมมากที่สุด =====
$top_places_result = mysqli_query($conn, "
    SELECT p.place_name, COUNT(pvl.view_id) AS view_count
    FROM place_view_log pvl
    JOIN place p ON p.place_id = pvl.place_id
    GROUP BY pvl.place_id
    ORDER BY view_count DESC
    LIMIT 5
");

$top_place_labels = [];
$top_place_data   = [];
while ($row = mysqli_fetch_assoc($top_places_result)) {
    $top_place_labels[] = $row['place_name'];
    $top_place_data[]   = (int)$row['view_count'];
}

// ถ้ายังไม่มีข้อมูลเลย แสดงสถานที่ทั้งหมด (view = 0)
if (empty($top_place_labels)) {
    $fallback = mysqli_query($conn, "SELECT place_name FROM place ORDER BY place_id DESC LIMIT 5");
    while ($row = mysqli_fetch_assoc($fallback)) {
        $top_place_labels[] = $row['place_name'];
        $top_place_data[]   = 0;
    }
}

// ===== ช่วงอายุ (visitor_log) =====
$age_ranges  = ['15-25', '26-35', '36-45', '46-55', '56-65', '65+'];
$age_counts  = [];
$age_total_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM visitor_log");
$age_total   = (int)(mysqli_fetch_assoc($age_total_res)['total'] ?? 1);
if ($age_total == 0) $age_total = 1; // กันหาร 0

foreach ($age_ranges as $range) {
    $res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM visitor_log WHERE age_range = '$range'");
    $cnt = (int)(mysqli_fetch_assoc($res)['cnt'] ?? 0);
    $age_counts[$range] = $cnt;
}

// ===== เพศ (visitor_log) =====
$gender_map = ['male' => 'เพศชาย', 'female' => 'เพศหญิง', 'unspecified' => 'ไม่ระบุ'];
$gender_data   = [];
$gender_labels = [];
$gender_total_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM visitor_log");
$gender_total = (int)(mysqli_fetch_assoc($gender_total_res)['total'] ?? 1);
if ($gender_total == 0) $gender_total = 1;

foreach ($gender_map as $val => $label) {
    $res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM visitor_log WHERE gender = '$val'");
    $cnt = (int)(mysqli_fetch_assoc($res)['cnt'] ?? 0);
    $gender_labels[] = $label;
    $gender_data[]   = $cnt;
}

// ===== จำนวน visitor ทั้งหมด =====
$total_visitor_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM visitor_log");
$total_visitor = mysqli_fetch_assoc($total_visitor_res)['total'] ?? 0;

// แปลงเป็น JSON สำหรับ JS
$visitor_labels_json   = json_encode($visitor_labels, JSON_UNESCAPED_UNICODE);
$visitor_data_json     = json_encode($visitor_data);
$top_place_labels_json = json_encode($top_place_labels, JSON_UNESCAPED_UNICODE);
$top_place_data_json   = json_encode($top_place_data);
$gender_labels_json    = json_encode($gender_labels, JSON_UNESCAPED_UNICODE);
$gender_data_json      = json_encode($gender_data);


// ===== จำนวนเนื้อหาแชทบอท =====
$chatbot_count_res = mysqli_query($conn, "
SELECT
(SELECT COUNT(*) FROM place) +
(SELECT COUNT(*) FROM restaurant) +
(SELECT COUNT(*) FROM activity) +
(SELECT COUNT(*) FROM souvenir_shop) +
(SELECT COUNT(*) FROM about_us) AS total
");
$chatbot_count = mysqli_fetch_assoc($chatbot_count_res)['total'] ?? 0;
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>

<style>
    /* ===== Dashboard Layout ===== */
    .dashboard-wrapper {
        padding: 28px 32px;
        background: #f0f2f0;
        min-height: calc(100vh - 64px);
    }

    .dashboard-title {
        font-size: 22px;
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 24px;
    }

    /* ===== Quick Action Cards ===== */
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
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
        text-decoration: none;
        color: inherit;
        transition: transform 0.18s, box-shadow 0.18s;
        border: 1.5px solid transparent;
    }

    .quick-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.10);
        border-color: #2d7a3a;
    }

    .quick-card-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }

    .icon-green {
        background: #e6f4ea;
        color: #2d7a3a;
    }

    .icon-blue {
        background: #e3f0fb;
        color: #2563eb;
    }

    .icon-amber {
        background: #fef9e7;
        color: #d97706;
    }

    .icon-purple {
        background: #f3e8ff;
        color: #7c3aed;
    }

    .quick-card-info p {
        margin: 0;
        font-size: 12px;
        color: #888;
    }

    .quick-card-info h3 {
        margin: 2px 0 0;
        font-size: 22px;
        font-weight: 700;
        color: #1a1a1a;
    }

    .quick-card-info span {
        font-size: 13px;
        font-weight: 600;
        color: #2d7a3a;
    }

    /* ===== Chart Grid ===== */
    .chart-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    @media (max-width: 900px) {
        .chart-grid {
            grid-template-columns: 1fr;
        }
    }

    .chart-card {
        background: #fff;
        border-radius: 18px;
        padding: 24px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
    }

    .chart-card h4 {
        margin: 0 0 4px;
        font-size: 15px;
        font-weight: 600;
        color: #1a1a1a;
    }

    .chart-subtitle {
        font-size: 12px;
        color: #999;
        margin: 0 0 14px;
    }

    .chart-container {
        position: relative;
        width: 100%;
    }

    /* ===== Age group custom bars ===== */
    .age-bar-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .age-bar-item {
        display: grid;
        grid-template-columns: 56px 1fr 56px;
        align-items: center;
        gap: 12px;
    }

    .age-label {
        font-size: 13px;
        color: #555;
        font-weight: 500;
    }

    .age-track {
        background: #eee;
        border-radius: 99px;
        height: 8px;
        overflow: hidden;
    }

    .age-fill {
        height: 100%;
        border-radius: 99px;
        transition: width 1s ease;
    }

    .age-pct {
        font-size: 12px;
        font-weight: 600;
        color: #333;
        text-align: center;
        background: #f5f5f5;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        padding: 2px 6px;
    }

    /* ===== Export buttons ===== */
    .export-bar {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-bottom: 20px;
    }

    .btn-export {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 7px 16px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: opacity 0.15s;
    }

    .btn-export:hover {
        opacity: 0.85;
    }

    .btn-excel {
        background: #1d6f42;
        color: #fff;
    }

    .btn-pdf {
        background: #c0392b;
        color: #fff;
    }

    /* ===== Empty state ===== */
    .empty-state {
        text-align: center;
        padding: 32px 0;
        color: #bbb;
        font-size: 13px;
    }
</style>

<div class="dashboard-wrapper">

    <div class="export-bar">
        <button class="btn-export btn-excel" onclick="exportExcel()">
            <i class="fa fa-file-excel"></i> Excel
        </button>
        <button class="btn-export btn-pdf" onclick="exportPDF()">
            <i class="fa fa-file-pdf"></i> PDF
        </button>
    </div>

    <!-- ===== Quick Action Cards ===== -->
    <div class="quick-cards">
        <a href="place_manage.php" class="quick-card">
            <div class="quick-card-icon icon-green">
                <i class="fa fa-map-marker-alt"></i>
            </div>
            <div class="quick-card-info">
                <p>สถานที่ทั้งหมด</p>
                <h3><?= $place_count ?></h3>
                <span>ไปจัดการ →</span>
            </div>
        </a>

        <a href="content_manage.php" class="quick-card">
            <div class="quick-card-icon icon-blue">
                <i class="fa fa-newspaper"></i>
            </div>
            <div class="quick-card-info">
                <p>คอนเทนต์ทั้งหมด</p>
                <h3><?= $content_count ?></h3>
                <span>ไปจัดการ →</span>
            </div>
        </a>

        <!-- กล่องใหม่: เนื้อหาแชทบอท -->
        <a href="chatbot_manage.php" class="quick-card">
            <div class="quick-card-icon icon-purple">
                <i class="fa fa-robot"></i>
            </div>
            <div class="quick-card-info">
                <p>เนื้อหาแชทบอท</p>
                <h3><?= $chatbot_count ?></h3>
                <span>ไปจัดการ →</span>
            </div>
        </a>

        <div class="quick-card" style="cursor:default;">
            <div class="quick-card-icon icon-amber">
                <i class="fa fa-users"></i>
            </div>
            <div class="quick-card-info">
                <p>ผู้เข้าชมทั้งหมด</p>
                <h3><?= $total_visitor ?></h3>
                <span>ผู้เข้าชม</span>
            </div>
        </div>
    </div>

    <!-- ===== Charts ===== -->
    <div class="chart-grid">

        <!-- 1. ผู้เข้าใช้งานเว็บไซต์ 7 วัน (Bar) -->
        <div class="chart-card">
            <h4>ผู้เข้าใช้งานเว็บไซต์</h4>
            <p class="chart-subtitle">7 วันย้อนหลัง</p>
            <div class="chart-container" style="height:220px;">
                <canvas id="visitorChart"></canvas>
            </div>
        </div>

        <!-- 2. สถานที่ที่มีผู้เข้าชมมากที่สุด Top 5 (Horizontal Bar) -->
        <div class="chart-card">
            <h4>สถานที่ที่มีผู้เข้าชมมากที่สุด</h4>
            <p class="chart-subtitle">Top 5</p>
            <?php if (array_sum($top_place_data) == 0): ?>
                <div class="empty-state">
                    <i class="fa fa-chart-bar" style="font-size:32px;display:block;margin-bottom:8px;"></i>
                    ยังไม่มีข้อมูลการเข้าชม
                </div>
            <?php else: ?>
                <div class="chart-container" style="height:220px;">
                    <canvas id="placeChart"></canvas>
                </div>
            <?php endif; ?>
        </div>

        <!-- 3. ช่วงอายุ (Custom bars จาก DB) -->
        <div class="chart-card">
            <h4>ช่วงอายุของผู้ใช้งานเว็บไซต์</h4>
            <p class="chart-subtitle">จากข้อมูลแบบสอบถาม (ทั้งหมด <?= $age_total ?> คน)</p>
            <?php
            $age_colors = ['#2d7a3a', '#d4a017', '#c0796a', '#2c3e7a', '#e07b30', '#5b8de8'];
            $i = 0;
            ?>
            <ul class="age-bar-list">
                <?php foreach ($age_ranges as $range):
                    $cnt = $age_counts[$range];
                    $pct = ($age_total > 0) ? round($cnt / $age_total * 100) : 0;
                    $color = $age_colors[$i % count($age_colors)];
                    $i++;
                ?>
                    <li class="age-bar-item">
                        <span class="age-label"><?= $range ?></span>
                        <div class="age-track">
                            <div class="age-fill" style="width:<?= $pct ?>%;background:<?= $color ?>;"></div>
                        </div>
                        <span class="age-pct"><?= $pct ?>%<br><small style="font-weight:400;color:#999;">(<?= $cnt ?>)</small></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- 4. เพศ (Donut จาก DB) -->
        <div class="chart-card">
            <h4>เพศของผู้ใช้งานเว็บไซต์</h4>
            <p class="chart-subtitle">จากข้อมูลแบบสอบถาม (ทั้งหมด <?= $gender_total ?> คน)</p>
            <?php if (array_sum($gender_data) == 0): ?>
                <div class="empty-state">
                    <i class="fa fa-venus-mars" style="font-size:32px;display:block;margin-bottom:8px;"></i>
                    ยังไม่มีข้อมูล
                </div>
            <?php else: ?>
                <div class="chart-container" style="height:220px;">
                    <canvas id="genderChart"></canvas>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
    // ===== ข้อมูลจาก PHP =====
    const visitorLabels = <?= $visitor_labels_json ?>;
    const visitorData = <?= $visitor_data_json ?>;
    const placeLabels = <?= $top_place_labels_json ?>;
    const placeData = <?= $top_place_data_json ?>;
    const genderLabels = <?= $gender_labels_json ?>;
    const genderData = <?= $gender_data_json ?>;

    // ===== 1. Visitor Bar Chart =====
    const visitorCtx = document.getElementById('visitorChart').getContext('2d');
    new Chart(visitorCtx, {
        type: 'bar',
        data: {
            labels: visitorLabels,
            datasets: [{
                data: visitorData,
                backgroundColor: ['#c0392b', '#d4a017', '#c0796a', '#2d7a3a', '#e07b30', '#5b8de8', '#2c3e7a'],
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: ctx => ` ${ctx.parsed.y} คน`
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 12
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#eee'
                    },
                    ticks: {
                        font: {
                            size: 11
                        },
                        stepSize: 1,
                        callback: v => Number.isInteger(v) ? v : null
                    }
                }
            }
        }
    });

    // ===== 2. Place Horizontal Bar =====
    const placeCtxEl = document.getElementById('placeChart');
    if (placeCtxEl) {
        const placeCtx = placeCtxEl.getContext('2d');
        new Chart(placeCtx, {
            type: 'bar',
            data: {
                labels: placeLabels,
                datasets: [{
                    data: placeData,
                    backgroundColor: ['#d4a017', '#5b8de8', '#2c3e7a', '#c0796a', '#2d7a3a'],
                    borderRadius: 5,
                    borderSkipped: false,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` ${ctx.parsed.x} ครั้ง`
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            color: '#eee'
                        },
                        ticks: {
                            stepSize: 1,
                            font: {
                                size: 11
                            },
                            callback: v => Number.isInteger(v) ? v : null
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });
    }

    // ===== 4. Gender Donut =====
    // ===== 4. Gender Donut =====
    const genderCtxEl = document.getElementById('genderChart');
    if (genderCtxEl) {
        const genderCtx = genderCtxEl.getContext('2d');
        new Chart(genderCtx, {
            type: 'doughnut',
            data: {
                labels: genderLabels,
                datasets: [{
                    data: genderData,
                    backgroundColor: ['#c0392b', '#d4a017', '#2c3e7a'],
                    borderWidth: genderData.map(v => v === 0 ? 0 : 3),
                    borderColor: '#fff',
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 16,
                            font: {
                                size: 12
                            },
                            boxWidth: 12,
                            boxHeight: 12,
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` ${ctx.label}: ${ctx.parsed} คน`
                        }
                    }
                }
            }
        });
    }

    // ===== Export Excel (SheetJS) =====
    function exportExcel() {
        // โหลด SheetJS ถ้ายังไม่ได้โหลด
        if (typeof XLSX === 'undefined') {
            const s = document.createElement('script');
            s.src = 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js';
            s.onload = doExportExcel;
            document.head.appendChild(s);
        } else {
            doExportExcel();
        }
    }

    function doExportExcel() {
        const wb = XLSX.utils.book_new();

        // ---- Sheet 1: ผู้เข้าชม 7 วัน ----
        const visitorRows = [
            ['วัน', 'จำนวนผู้เข้าชม (คน)']
        ];
        visitorLabels.forEach((d, i) => visitorRows.push([d, visitorData[i]]));
        XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(visitorRows), 'ผู้เข้าชม 7 วัน');

        // ---- Sheet 2: Top 5 สถานที่ ----
        const placeRows = [
            ['สถานที่', 'จำนวนการเข้าชม (ครั้ง)']
        ];
        placeLabels.forEach((p, i) => placeRows.push([p, placeData[i]]));
        XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(placeRows), 'Top 5 สถานที่');

        // ---- Sheet 3: ช่วงอายุ ----
        const ageData = <?= json_encode(array_map(function ($r) use ($age_counts, $age_total) {
                            $cnt = $age_counts[$r];
                            $pct = ($age_total > 0) ? round($cnt / $age_total * 100) : 0;
                            return ['range' => $r, 'count' => $cnt, 'pct' => $pct];
                        }, $age_ranges), JSON_UNESCAPED_UNICODE) ?>;
        const ageRows = [
            ['ช่วงอายุ', 'จำนวน (คน)', 'เปอร์เซ็นต์']
        ];
        ageData.forEach(a => ageRows.push([a.range, a.count, a.pct + '%']));
        XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(ageRows), 'ช่วงอายุ');

        // ---- Sheet 4: เพศ ----
        const genderRows = [
            ['เพศ', 'จำนวน (คน)']
        ];
        genderLabels.forEach((g, i) => genderRows.push([g, genderData[i]]));
        XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(genderRows), 'เพศ');

        // ---- Sheet 5: สรุป ----
        const today = new Date().toLocaleDateString('th-TH');
        const summaryRows = [
            ['รายงานสรุปข้อมูล Dashboard'],
            ['วันที่ออกรายงาน', today],
            [],
            ['หัวข้อ', 'จำนวน'],
            ['จำนวนสถานที่ทั้งหมด', <?= (int)$place_count ?>],
            ['จำนวนคอนเทนต์ทั้งหมด', <?= (int)$content_count ?>],
            ['จำนวนผู้เข้าชมทั้งหมด', <?= (int)$total_visitor ?>],
        ];
        XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(summaryRows), 'สรุป');

        const filename = 'dashboard_report_' + new Date().toISOString().slice(0, 10) + '.xlsx';
        XLSX.writeFile(wb, filename);
    }

    // ===== Export PDF (jsPDF + html2canvas) =====
    function exportPDF() {
        // โหลด library ที่ต้องการ
        const libs = [{
                id: 'jspdf-lib',
                src: 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js'
            },
            {
                id: 'html2canvas-lib',
                src: 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js'
            }
        ];
        let loaded = 0;
        libs.forEach(lib => {
            if (!document.getElementById(lib.id)) {
                const s = document.createElement('script');
                s.id = lib.id;
                s.src = lib.src;
                s.onload = () => {
                    loaded++;
                    if (loaded === libs.length) doExportPDF();
                };
                document.head.appendChild(s);
            } else {
                loaded++;
                if (loaded === libs.length) doExportPDF();
            }
        });
    }

    async function doExportPDF() {
        const {
            jsPDF
        } = window.jspdf;
        const pdf = new jsPDF({
            orientation: 'portrait',
            unit: 'mm',
            format: 'a4'
        });

        // ฝัง font รองรับภาษาไทยผ่าน html2canvas (render เป็นภาพ)
        const wrapper = document.querySelector('.dashboard-wrapper');

        // แสดง loading
        const btn = document.querySelector('.btn-pdf');
        const origText = btn.innerHTML;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> กำลังสร้าง PDF...';
        btn.disabled = true;

        try {
            const canvas = await html2canvas(wrapper, {
                scale: 2,
                useCORS: true,
                logging: false,
                backgroundColor: '#f0f2f0'
            });

            const imgData = canvas.toDataURL('image/png');
            const pageW = 210; // A4 width mm
            const pageH = 297; // A4 height mm
            const margin = 10;
            const usableW = pageW - margin * 2;
            const imgH = (canvas.height / canvas.width) * usableW;

            // หัวกระดาษ
            pdf.setFillColor(45, 122, 58);
            pdf.rect(0, 0, pageW, 14, 'F');
            pdf.setTextColor(255, 255, 255);
            pdf.setFontSize(11);
            pdf.text('รายงาน Dashboard', margin, 9.5);
            const today = new Date().toLocaleDateString('th-TH', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            pdf.setFontSize(8);
            pdf.text('วันที่: ' + today, pageW - margin, 9.5, {
                align: 'right'
            });

            // วางภาพ (รองรับหลายหน้า)
            let yPos = 16;
            let remaining = imgH;
            let srcY = 0;

            while (remaining > 0) {
                const sliceH = Math.min(remaining, pageH - yPos - margin);
                const slicePx = (sliceH / usableW) * canvas.width;

                // ตัดภาพ
                const sliceCanvas = document.createElement('canvas');
                sliceCanvas.width = canvas.width;
                sliceCanvas.height = slicePx;
                const ctx = sliceCanvas.getContext('2d');
                ctx.drawImage(canvas, 0, srcY, canvas.width, slicePx, 0, 0, canvas.width, slicePx);

                pdf.addImage(sliceCanvas.toDataURL('image/png'), 'PNG', margin, yPos, usableW, sliceH);

                remaining -= sliceH;
                srcY += slicePx;

                if (remaining > 0) {
                    pdf.addPage();
                    // หัวกระดาษหน้าถัดไป
                    pdf.setFillColor(45, 122, 58);
                    pdf.rect(0, 0, pageW, 14, 'F');
                    yPos = 16;
                }
            }

            // เท้ากระดาษ
            const totalPages = pdf.internal.getNumberOfPages();
            for (let p = 1; p <= totalPages; p++) {
                pdf.setPage(p);
                pdf.setFillColor(240, 242, 240);
                pdf.rect(0, pageH - 8, pageW, 8, 'F');
                pdf.setTextColor(150, 150, 150);
                pdf.setFontSize(7);
                pdf.text('หน้า ' + p + ' / ' + totalPages, pageW / 2, pageH - 3, {
                    align: 'center'
                });
            }

            const filename = 'dashboard_report_' + new Date().toISOString().slice(0, 10) + '.pdf';
            pdf.save(filename);
        } catch (err) {
            console.error(err);
            alert('เกิดข้อผิดพลาดในการสร้าง PDF กรุณาลองใหม่อีกครั้ง');
        } finally {
            btn.innerHTML = origText;
            btn.disabled = false;
        }
    }
</script>
