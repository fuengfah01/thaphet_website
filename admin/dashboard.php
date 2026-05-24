<?php
// ============================================================
// PATCH: แทนที่ส่วน "Date Range Bar" ใน dashboard.php เดิม
// และแก้ไข applyDateFilter() ให้รับค่า gender / age / place
// ============================================================
?>

<!-- ════════════════════════════════════════════════════════
     CSS เพิ่มเติม (ใส่ต่อท้าย <style> เดิม หรือก่อน </style>)
     ════════════════════════════════════════════════════════ -->
<style>
/* ── Advanced Filter Dropdown ── */
.filter-dropdown-wrap {
    position: relative;
}

.btn-adv-filter {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    background: #fff;
    color: #2d7a3a;
    border: 1.5px solid #2d7a3a;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s;
    white-space: nowrap;
    position: relative;
}
.btn-adv-filter:hover { background: #e6f4ea; }
.btn-adv-filter .badge-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 17px; height: 17px;
    background: #2d7a3a;
    color: #fff;
    border-radius: 50%;
    font-size: 10px;
    font-weight: 700;
    line-height: 1;
}

.adv-filter-panel {
    display: none;
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    z-index: 9999;
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 8px 32px rgba(0,0,0,.14);
    border: 1px solid #e4e8e4;
    width: 420px;
    padding: 0;
    overflow: hidden;
}
.adv-filter-panel.open { display: block; }

.afp-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px 10px;
    border-bottom: 1px solid #f0f0f0;
}
.afp-header h5 {
    margin: 0;
    font-size: 13px;
    font-weight: 700;
    color: #1a1a1a;
}
.afp-reset {
    font-size: 12px;
    color: #888;
    background: none;
    border: none;
    cursor: pointer;
    padding: 3px 8px;
    border-radius: 6px;
}
.afp-reset:hover { background: #f5f5f5; color: #333; }

.afp-body { padding: 14px 18px; display: flex; flex-direction: column; gap: 16px; }

.afp-section-label {
    font-size: 11px;
    font-weight: 700;
    color: #999;
    text-transform: uppercase;
    letter-spacing: .05em;
    margin-bottom: 8px;
}

/* Checkbox Pills */
.pill-group { display: flex; flex-wrap: wrap; gap: 6px; }
.pill-check {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 5px 11px;
    border-radius: 20px;
    border: 1.5px solid #ddd;
    font-size: 12px;
    color: #555;
    cursor: pointer;
    user-select: none;
    transition: all .12s;
    background: #fafafa;
}
.pill-check input { display: none; }
.pill-check:hover { border-color: #2d7a3a; color: #2d7a3a; background: #f0faf2; }
.pill-check.checked { border-color: #2d7a3a; color: #2d7a3a; background: #e6f4ea; font-weight: 600; }
.pill-check.checked-pink  { border-color: #c0796a; color: #c0796a; background: #fdf0ee; font-weight: 600; }
.pill-check.checked-blue  { border-color: #2c3e7a; color: #2c3e7a; background: #eceffa; font-weight: 600; }
.pill-check.checked-purple{ border-color: #7c3aed; color: #7c3aed; background: #f5f0ff; font-weight: 600; }
.pill-check.checked-gray  { border-color: #888; color: #555; background: #f5f5f5; font-weight: 600; }

/* Age checkbox list */
.age-check-list { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px; }

/* Place search + list */
.afp-place-search {
    display: flex;
    align-items: center;
    gap: 6px;
    border: 1.5px solid #e0e0e0;
    border-radius: 8px;
    padding: 5px 10px;
    margin-bottom: 8px;
}
.afp-place-search i { color: #aaa; font-size: 13px; }
.afp-place-search input {
    border: none; outline: none; font-size: 12px; width: 100%; background: transparent; color: #333;
}
.place-check-list { display: flex; flex-wrap: wrap; gap: 5px; max-height: 72px; overflow-y: auto; }

/* Visitor range */
.vis-range-row {
    display: flex;
    align-items: center;
    gap: 10px;
}
.vis-range-row input[type="number"] {
    width: 70px;
    padding: 5px 8px;
    border: 1.5px solid #ddd;
    border-radius: 7px;
    font-size: 12px;
    text-align: center;
    outline: none;
}
.vis-range-row input[type="number"]:focus { border-color: #2d7a3a; }
.vis-range-sep { font-size: 12px; color: #aaa; }

.afp-footer {
    padding: 10px 18px 14px;
    border-top: 1px solid #f0f0f0;
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}
.afp-cancel {
    padding: 7px 16px; border-radius: 8px; border: 1.5px solid #ddd;
    background: #fff; color: #555; font-size: 13px; font-weight: 600; cursor: pointer;
}
.afp-cancel:hover { background: #f5f5f5; }
.afp-apply {
    padding: 7px 18px; border-radius: 8px; border: none;
    background: #2d7a3a; color: #fff; font-size: 13px; font-weight: 600; cursor: pointer;
}
.afp-apply:hover { background: #235f2d; }

/* Active filter chips (แถบด้านล่าง date bar) */
.active-filter-bar {
    display: none;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: -14px;
    margin-bottom: 16px;
    padding: 8px 16px;
    background: #f7faf7;
    border-radius: 0 0 14px 14px;
    border: 1.5px solid #d4e8d6;
    border-top: none;
}
.active-filter-bar.has-filters { display: flex; }
.filter-chip {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 11px; font-weight: 600; padding: 3px 9px;
    border-radius: 20px; background: #e6f4ea; color: #2d7a3a;
    border: 1px solid #b2d9b8;
}
.filter-chip .chip-x {
    font-size: 10px; color: #5a9960; cursor: pointer; margin-left: 2px; font-weight: 700;
}
.filter-chip .chip-x:hover { color: #c0392b; }
.clear-all-chip {
    font-size: 11px; color: #c0392b; cursor: pointer; font-weight: 600;
    padding: 3px 8px; border-radius: 20px; border: 1px solid #f5c6c6; background: #fdf0f0;
}
.clear-all-chip:hover { background: #fde8e8; }
</style>

<!-- ════════════════════════════════════════════════════════
     HTML: แทนที่ส่วน Date Range Bar เดิม
     ════════════════════════════════════════════════════════ -->

<!-- Date Range Bar -->
<div class="date-range-bar" style="border-radius: 14px 14px 14px 14px; margin-bottom: 6px;">
    <i class="fa fa-calendar-alt" style="color:#2d7a3a; font-size:15px;"></i>
    <label>ช่วงวันที่:</label>

    <!-- Date Picker: วันเริ่มต้น -->
    <div class="th-date-picker" id="pickerFrom">
        <i class="fa fa-calendar" style="color:#2d7a3a; font-size:12px;"></i>
        <span id="displayFrom">โหลด...</span>
    </div>
    <input type="hidden" id="dateFrom" value="<?= $default_from ?>">

    <span class="date-range-sep">—</span>

    <!-- Date Picker: วันสิ้นสุด -->
    <div class="th-date-picker" id="pickerTo">
        <i class="fa fa-calendar" style="color:#2d7a3a; font-size:12px;"></i>
        <span id="displayTo">โหลด...</span>
    </div>
    <input type="hidden" id="dateTo" value="<?= $default_to ?>">

    <button class="btn-filter" id="btnFilter" onclick="applyDateFilter()">
        <i class="fa fa-filter"></i> กรองข้อมูล
    </button>

    <!-- ปุ่มเลือกช่วงด่วน -->
    <div style="display:flex; gap:6px; flex-wrap:wrap;">
        <button class="btn-quick-range active" id="btn7"   onclick="setQuickRange(7, this)">7 วัน</button>
        <button class="btn-quick-range"        id="btn30"  onclick="setQuickRange(30, this)">30 วัน</button>
        <button class="btn-quick-range"        id="btnAll" onclick="setAllTime(this)">ทั้งหมด</button>
    </div>

    <!-- ★ ปุ่ม ตัวกรองเพิ่มเติม ★ -->
    <div class="filter-dropdown-wrap" id="advFilterWrap">
        <button class="btn-adv-filter" id="btnAdvFilter" onclick="toggleAdvFilter(event)">
            <i class="fa fa-sliders-h"></i>
            ตัวกรอง
            <span class="badge-count" id="advBadge" style="display:none;">0</span>
        </button>

        <!-- Dropdown Panel -->
        <div class="adv-filter-panel" id="advFilterPanel">
            <div class="afp-header">
                <h5><i class="fa fa-filter" style="color:#2d7a3a; margin-right:6px;"></i>กรองข้อมูลเพิ่มเติม</h5>
                <button class="afp-reset" onclick="resetAdvFilter()">รีเซ็ตทั้งหมด</button>
            </div>

            <div class="afp-body">

                <!-- เพศ -->
                <div>
                    <div class="afp-section-label">เพศ</div>
                    <div class="pill-group" id="genderPills">
                        <label class="pill-check" data-color="checked">
                            <input type="checkbox" name="gender" value="female">
                            <i class="fa fa-venus" style="font-size:11px;"></i> เพศหญิง
                        </label>
                        <label class="pill-check" data-color="checked-blue">
                            <input type="checkbox" name="gender" value="male">
                            <i class="fa fa-mars" style="font-size:11px;"></i> เพศชาย
                        </label>
                        <label class="pill-check" data-color="checked-pink">
                            <input type="checkbox" name="gender" value="lgbtq+">
                            <i class="fa fa-heart" style="font-size:11px;"></i> LGBTQ+
                        </label>
                        <label class="pill-check" data-color="checked-gray">
                            <input type="checkbox" name="gender" value="unspecified">
                            <i class="fa fa-minus" style="font-size:11px;"></i> ไม่ระบุ
                        </label>
                    </div>
                </div>

                <!-- ช่วงอายุ -->
                <div>
                    <div class="afp-section-label">ช่วงอายุ</div>
                    <div class="age-check-list" id="agePills">
                        <?php foreach (['15-25','26-35','36-45','46-55','56-65','65+'] as $ar): ?>
                        <label class="pill-check" data-color="checked">
                            <input type="checkbox" name="age" value="<?= $ar ?>">
                            <?= $ar ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- สถานที่ -->
                <div>
                    <div class="afp-section-label">สถานที่</div>
                    <div class="afp-place-search">
                        <i class="fa fa-search"></i>
                        <input type="text" id="placeSearchInput" placeholder="ค้นหาสถานที่..." oninput="filterPlaceList(this.value)">
                    </div>
                    <div class="place-check-list" id="placePills">
                        <?php
                        // ดึงชื่อสถานที่ทั้งหมดจาก DB มาแสดง
                        $all_places = mysqli_query($conn, "SELECT place_id, place_name FROM place ORDER BY place_name");
                        while ($p = mysqli_fetch_assoc($all_places)):
                        ?>
                        <label class="pill-check" data-color="checked" data-place="<?= htmlspecialchars($p['place_name']) ?>">
                            <input type="checkbox" name="place" value="<?= $p['place_id'] ?>">
                            <?= htmlspecialchars($p['place_name']) ?>
                        </label>
                        <?php endwhile; ?>
                    </div>
                </div>

                <!-- จำนวนผู้เข้าชม -->
                <div>
                    <div class="afp-section-label">จำนวนผู้เข้าชม (คน)</div>
                    <div class="vis-range-row">
                        <input type="number" id="visMin" min="0" value="0" placeholder="ต่ำสุด">
                        <span class="vis-range-sep">–</span>
                        <input type="number" id="visMax" min="0" value="" placeholder="สูงสุด">
                        <span style="font-size:12px; color:#aaa;">คน/วัน</span>
                    </div>
                </div>

            </div>

            <div class="afp-footer">
                <button class="afp-cancel" onclick="closeAdvFilter()">ยกเลิก</button>
                <button class="afp-apply" onclick="applyAdvFilter()">
                    <i class="fa fa-check"></i> ใช้ตัวกรอง
                </button>
            </div>
        </div>
    </div>
    <!-- /advFilterWrap -->

    <span class="date-range-info" id="dateRangeInfo">แสดงข้อมูล 7 วันย้อนหลัง</span>
</div>

<!-- Active filter chips bar (แสดงเมื่อมี filter ที่เลือก) -->
<div class="active-filter-bar" id="activeFilterBar">
    <!-- chips inject ด้วย JS -->
</div>


<!-- ════════════════════════════════════════════════════════
     JavaScript เพิ่มเติม (ใส่ก่อน </script> ปิดท้าย หรือต่อท้าย script เดิม)
     ════════════════════════════════════════════════════════ -->
<script>
// ── State ตัวกรองเพิ่มเติม ──
const advState = {
    gender: [],   // ['female','male',...]
    age:    [],   // ['15-25','26-35',...]
    place:  [],   // [place_id, ...]
    placeNames: [], // ชื่อสถานที่ (เพื่อแสดง chip)
    visMin: 0,
    visMax: null,
};

// เปิด/ปิด dropdown
function toggleAdvFilter(e) {
    e.stopPropagation();
    document.getElementById('advFilterPanel').classList.toggle('open');
}
function closeAdvFilter() {
    document.getElementById('advFilterPanel').classList.remove('open');
}

// ปิดเมื่อคลิกนอก
document.addEventListener('click', e => {
    const wrap = document.getElementById('advFilterWrap');
    if (wrap && !wrap.contains(e.target)) closeAdvFilter();
});

// Toggle pill สี
document.querySelectorAll('.pill-check').forEach(lbl => {
    lbl.querySelector('input').addEventListener('change', function() {
        const color = lbl.dataset.color || 'checked';
        if (this.checked) lbl.classList.add(color);
        else              lbl.classList.remove(color);
    });
});

// ค้นหาสถานที่
function filterPlaceList(q) {
    document.querySelectorAll('#placePills .pill-check').forEach(lbl => {
        const name = lbl.dataset.place || '';
        lbl.style.display = name.includes(q) ? '' : 'none';
    });
}

// รีเซ็ตตัวกรอง
function resetAdvFilter() {
    document.querySelectorAll('#advFilterPanel input[type="checkbox"]').forEach(cb => {
        cb.checked = false;
        cb.closest('.pill-check').className = 'pill-check';
    });
    document.getElementById('visMin').value = 0;
    document.getElementById('visMax').value = '';
    document.getElementById('placeSearchInput').value = '';
    filterPlaceList('');
    advState.gender = [];
    advState.age    = [];
    advState.place  = [];
    advState.placeNames = [];
    advState.visMin = 0;
    advState.visMax = null;
    updateAdvBadge();
    renderActiveChips();
}

// บันทึกค่าและปิด
function applyAdvFilter() {
    advState.gender = [...document.querySelectorAll('#advFilterPanel input[name="gender"]:checked')]
                        .map(cb => cb.value);
    advState.age    = [...document.querySelectorAll('#advFilterPanel input[name="age"]:checked')]
                        .map(cb => cb.value);
    const placeCbs  = [...document.querySelectorAll('#advFilterPanel input[name="place"]:checked')];
    advState.place  = placeCbs.map(cb => cb.value);
    advState.placeNames = placeCbs.map(cb => cb.closest('.pill-check').dataset.place);
    advState.visMin = parseInt(document.getElementById('visMin').value) || 0;
    advState.visMax = document.getElementById('visMax').value !== ''
                        ? parseInt(document.getElementById('visMax').value)
                        : null;
    closeAdvFilter();
    updateAdvBadge();
    renderActiveChips();
    applyDateFilter(); // ← เรียก filter หลักอีกครั้ง
}

// นับจำนวน filter ที่ active
function updateAdvBadge() {
    const n = advState.gender.length + advState.age.length + advState.place.length
              + (advState.visMax !== null ? 1 : 0);
    const badge = document.getElementById('advBadge');
    if (n > 0) { badge.style.display = 'inline-flex'; badge.textContent = n; }
    else        { badge.style.display = 'none'; }
}

// แสดง chip แถบกรอง
function renderActiveChips() {
    const bar = document.getElementById('activeFilterBar');
    bar.innerHTML = '';
    let chips = [];

    const gMap = { female:'เพศหญิง', male:'เพศชาย', 'lgbtq+':'LGBTQ+', unspecified:'ไม่ระบุ' };
    if (advState.gender.length)
        chips.push({ label: 'เพศ: ' + advState.gender.map(g=>gMap[g]||g).join(', '), key:'gender' });
    if (advState.age.length)
        chips.push({ label: 'อายุ: ' + advState.age.join(', '), key:'age' });
    if (advState.placeNames.length)
        chips.push({ label: 'สถานที่: ' + (advState.placeNames.length > 2
            ? advState.placeNames.slice(0,2).join(', ') + ' +' + (advState.placeNames.length-2)
            : advState.placeNames.join(', ')), key:'place' });
    if (advState.visMax !== null)
        chips.push({ label: `ผู้เข้าชม: ${advState.visMin}–${advState.visMax} คน/วัน`, key:'vis' });

    if (!chips.length) { bar.classList.remove('has-filters'); return; }
    bar.classList.add('has-filters');

    chips.forEach(c => {
        const el = document.createElement('span');
        el.className = 'filter-chip';
        el.innerHTML = `${c.label} <span class="chip-x" data-key="${c.key}" onclick="removeAdvChip('${c.key}')">✕</span>`;
        bar.appendChild(el);
    });

    const clearAll = document.createElement('span');
    clearAll.className = 'clear-all-chip';
    clearAll.textContent = 'ล้างทั้งหมด';
    clearAll.onclick = () => { resetAdvFilter(); applyDateFilter(); };
    bar.appendChild(clearAll);
}

// ลบ filter ทีละกลุ่ม
function removeAdvChip(key) {
    if (key === 'gender') {
        advState.gender = [];
        document.querySelectorAll('#advFilterPanel input[name="gender"]').forEach(cb => {
            cb.checked = false;
            cb.closest('.pill-check').className = 'pill-check';
        });
    } else if (key === 'age') {
        advState.age = [];
        document.querySelectorAll('#advFilterPanel input[name="age"]').forEach(cb => {
            cb.checked = false;
            cb.closest('.pill-check').className = 'pill-check';
        });
    } else if (key === 'place') {
        advState.place = [];
        advState.placeNames = [];
        document.querySelectorAll('#advFilterPanel input[name="place"]').forEach(cb => {
            cb.checked = false;
            cb.closest('.pill-check').className = 'pill-check';
        });
    } else if (key === 'vis') {
        advState.visMax = null;
        advState.visMin = 0;
        document.getElementById('visMin').value = 0;
        document.getElementById('visMax').value = '';
    }
    updateAdvBadge();
    renderActiveChips();
    applyDateFilter();
}

// ════════════════════════════════════════════════════════════
// แก้ไข applyDateFilter() ให้ส่ง advState ไปกับ AJAX
// (แทนที่ฟังก์ชันเดิมทั้งหมด)
// ════════════════════════════════════════════════════════════
async function applyDateFilter() {
    const from = document.getElementById('dateFrom').value;
    const to   = document.getElementById('dateTo').value;

    if (!from || !to || from > to) {
        alert('กรุณาเลือกช่วงวันที่ให้ถูกต้อง');
        return;
    }

    ['loadingVisitor', 'loadingPlace', 'loadingGender'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'flex';
    });

    const btn = document.getElementById('btnFilter');
    btn.disabled  = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> กำลังโหลด...';

    // สร้าง query string พร้อม filter เพิ่มเติม
    let qs = `dashboard.php?ajax=1&date_from=${from}&date_to=${to}`;
    if (advState.gender.length)
        qs += '&gender=' + advState.gender.map(encodeURIComponent).join(',');
    if (advState.age.length)
        qs += '&age=' + advState.age.map(encodeURIComponent).join(',');
    if (advState.place.length)
        qs += '&place_id=' + advState.place.join(',');
    if (advState.visMin > 0)
        qs += '&vis_min=' + advState.visMin;
    if (advState.visMax !== null)
        qs += '&vis_max=' + advState.visMax;

    try {
        const res  = await fetch(qs);
        const data = await res.json();

        // -- อัปเดตกราฟผู้เข้าชมรายวัน --
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

        // -- อัปเดตกราฟ Top 5 สถานที่ --
        const hasPlace = data.place_data.some(v => v > 0);
        document.getElementById('placeEmpty').style.display     = hasPlace ? 'none'  : 'block';
        document.getElementById('placeChartWrap').style.display = hasPlace ? 'block' : 'none';

        if (hasPlace) {
            if (!placeChart) {
                placeChart = new Chart(
                    document.getElementById('placeChart').getContext('2d'),
                    {
                        type: 'bar',
                        data: {
                            labels: data.place_labels,
                            datasets: [{ data: data.place_data,
                                backgroundColor: ['#d4a017','#5b8de8','#2c3e7a','#c0796a','#2d7a3a'],
                                borderRadius: 5, borderSkipped: false }],
                        },
                        options: {
                            indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                            plugins: { legend: { display: false },
                                       tooltip: { callbacks: { label: c => ` ${c.parsed.x} ครั้ง` } } },
                            scales: {
                                x: { beginAtZero: true, grid: { color: '#eee' },
                                     ticks: { stepSize:1, font:{size:11},
                                              callback: v => Number.isInteger(v)?v:null } },
                                y: { grid: { display: false }, ticks: { font:{size:12} } },
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

        // -- อัปเดต Age Bar --
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

        // -- อัปเดตกราฟเพศ --
        const hasGender = data.gender_data.some(v => v > 0);
        document.getElementById('genderEmpty').style.display    = hasGender ? 'none'  : 'block';
        document.getElementById('genderChartWrap').style.display = hasGender ? 'block' : 'none';

        if (hasGender) {
            const gc = data.gender_labels.map(l => gColorMap[l] || '#5b8de8');
            if (!genderChart) {
                genderChart = new Chart(
                    document.getElementById('genderChart').getContext('2d'),
                    {
                        type: 'doughnut',
                        data: {
                            labels: data.gender_labels,
                            datasets: [{ data: data.gender_data, backgroundColor: gc,
                                borderWidth: data.gender_data.map(v=>v===0?0:3),
                                borderColor: '#fff', hoverOffset: 6 }],
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false, cutout: '60%',
                            plugins: {
                                legend: { position:'bottom',
                                          labels:{padding:16, font:{size:12}, boxWidth:12, boxHeight:12} },
                                tooltip: { callbacks: { label: c => ` ${c.label}: ${c.parsed} คน` } },
                            },
                        },
                    }
                );
            } else {
                genderChart.data.labels                      = data.gender_labels;
                genderChart.data.datasets[0].data            = data.gender_data;
                genderChart.data.datasets[0].backgroundColor = gc;
                genderChart.data.datasets[0].borderWidth     = data.gender_data.map(v=>v===0?0:3);
                genderChart.update();
            }
        }

        document.getElementById('genderSubtitle').textContent =
            `จากแบบสอบถาม (ทั้งหมด ${data.gender_total} คน)`;
        document.getElementById('dateRangeInfo').textContent =
            `แสดงข้อมูล ${fmtTH(from)} – ${fmtTH(to)}`;

    } catch (e) {
        console.error(e);
        alert('เกิดข้อผิดพลาดในการโหลดข้อมูล');
    } finally {
        ['loadingVisitor', 'loadingPlace', 'loadingGender'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.display = 'none';
        });
        btn.disabled  = false;
        btn.innerHTML = '<i class="fa fa-filter"></i> กรองข้อมูล';
    }
}
</script>

<!-- ════════════════════════════════════════════════════════
     PHP AJAX: เพิ่ม filter เพิ่มเติมใน AJAX block เดิม
     (ในส่วน "ดึงข้อมูลทั้งหมดตามช่วงวันที่")

     เพิ่มหลัง $date_to = ... ของ AJAX block:
     ════════════════════════════════════════════════════════ -->
<?php
/*
// ── ตัวกรองเพิ่มเติม ──
$filter_gender  = isset($_GET['gender'])   ? explode(',', $_GET['gender'])   : [];
$filter_age     = isset($_GET['age'])      ? explode(',', $_GET['age'])      : [];
$filter_place   = isset($_GET['place_id']) ? explode(',', $_GET['place_id']) : [];
$filter_vis_min = isset($_GET['vis_min'])  ? (int)$_GET['vis_min']          : 0;
$filter_vis_max = isset($_GET['vis_max'])  ? (int)$_GET['vis_max']          : null;

// สร้าง WHERE clause สำหรับ visitor_log
$where_extra = '';
if (!empty($filter_gender)) {
    $gs = implode("','", array_map(fn($g) => mysqli_real_escape_string($conn,$g), $filter_gender));
    $where_extra .= " AND gender IN ('{$gs}')";
}
if (!empty($filter_age)) {
    $as = implode("','", array_map(fn($a) => mysqli_real_escape_string($conn,$a), $filter_age));
    $where_extra .= " AND age_range IN ('{$as}')";
}

// จากนั้นใช้ $where_extra ต่อท้าย WHERE DATE(visited_at) ใน query ผู้เข้าชม, ช่วงอายุ, เพศ เช่น:
// WHERE DATE(visited_at) = '$d' $where_extra
// WHERE DATE(visited_at) BETWEEN '$date_from' AND '$date_to' $where_extra

// สำหรับ Top 5 สถานที่ + place_id filter:
$where_place = '';
if (!empty($filter_place)) {
    $ps = implode(',', array_map('intval', $filter_place));
    $where_place = " AND pvl.place_id IN ({$ps})";
}
// ใช้ใน query place_view_log:
// WHERE DATE(pvl.viewed_at) BETWEEN '$date_from' AND '$date_to' $where_place
*/
?>
