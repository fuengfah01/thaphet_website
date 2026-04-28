<?php
session_start();

if (isset($_SESSION['survey_done'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ก่อนเข้าชมเว็บไซต์</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&family=Mitr:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #2d8a4e 0%, #1a6b3a 50%, #3aa068 100%);
            font-family: 'Sarabun', sans-serif;
            padding: 20px;
            overflow: hidden;
        }

        body::before, body::after {
            content: '';
            position: fixed;
            border-radius: 50%;
            background: rgba(255,255,255,0.08);
            pointer-events: none;
        }
        body::before { width: 300px; height: 300px; top: -80px; left: -80px; }
        body::after  { width: 180px; height: 180px; bottom: 40px; left: 60px; }

        /* ===== CARD ===== */
        .survey-card {
            background: #fff;
            border-radius: 28px;
            padding: 2rem 2.2rem 1.8rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.18);
            position: relative;
            z-index: 5;
        }

        .survey-title {
            text-align: center;
            font-family: 'Mitr', sans-serif;
            font-size: 1.15rem;
            font-weight: 600;
            color: #1a6b3a;
            margin-bottom: 1.4rem;
            line-height: 1.5;
        }

        .group-label {
            display: block;
            font-family: 'Mitr', sans-serif;
            font-size: 0.75rem;
            font-weight: 500;
            color: #5f5f5f;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        /* ===== GENDER ===== */
        .gender-row { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 1.2rem; }
        .gender-btn { flex: 1; min-width: 72px; }
        .gender-btn input[type="radio"] { display: none; }

        .gender-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 10px 6px;
            border-radius: 16px;
            border: 2px solid #e0f0e7;
            background: #f6fbf8;
            font-family: 'Sarabun', sans-serif;
            font-size: 0.85rem;
            font-weight: 700;
            color: #3a3a3a;
            cursor: pointer;
            transition: all 0.18s ease;
            gap: 4px;
        }
        .gender-emoji { font-size: 1.2rem; line-height: 1; }
        .gender-btn input:checked + .gender-label {
            border-color: #2d8a4e; background: #e6f4ec; color: #1a6b3a;
            box-shadow: 0 4px 12px rgba(45,138,78,0.18); transform: translateY(-2px);
        }
        .gender-label:hover { border-color: #6fc98f; background: #eef8f2; }

        /* ===== DIVIDER ===== */
        .divider { height: 1px; background: #eaf4ee; margin: 0.9rem 0 1.1rem; }

        /* ===== AGE ===== */
        .age-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 7px; margin-bottom: 1.4rem; }
        .age-btn { cursor: pointer; }
        .age-btn input[type="radio"] { display: none; }

        .age-label {
            display: block;
            text-align: center;
            padding: 9px 4px;
            border-radius: 14px;
            border: 2px solid #e0f0e7;
            background: #f6fbf8;
            font-family: 'Sarabun', sans-serif;
            font-size: 0.85rem;
            font-weight: 700;
            color: #3a3a3a;
            cursor: pointer;
            transition: all 0.18s ease;
        }
        .age-btn input:checked + .age-label {
            border-color: #2d8a4e; background: #e6f4ec; color: #1a6b3a;
            box-shadow: 0 4px 12px rgba(45,138,78,0.18); transform: translateY(-2px);
        }
        .age-label:hover { border-color: #6fc98f; background: #eef8f2; }

        /* ===== ERROR ===== */
        .error-msg { color: #c0392b; font-size: 0.78rem; margin-top: 5px; display: none; font-family: 'Mitr', sans-serif; }
        .error-msg.show { display: block; }

        /* ===== SUBMIT ===== */
        .btn-submit-wrap { text-align: center; }

        .btn-submit {
            font-family: 'Mitr', sans-serif;
            font-size: 0.95rem;
            font-weight: 500;
            color: #fff;
            background: linear-gradient(135deg, #2d8a4e, #1a6b3a);
            border: none;
            border-radius: 50px;
            padding: 12px 40px;
            cursor: pointer;
            letter-spacing: 0.03em;
            transition: all 0.2s ease;
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(45,138,78,0.35); }
        .btn-submit:active { transform: scale(0.97); }

        /* ===== MASCOT — fixed ติดขอบล่างขวา ===== */
        .mascot-wrap {
            position: fixed;
            bottom: 0;
            right: 0;
            width: 500px;
            pointer-events: none;
            z-index: 200;
            animation: float 3s ease-in-out infinite;
        }

        .mascot-wrap img { width: 100%; display: block; }

        .speech-bubble {
            position: absolute;
            top: 30px;
            left: 20px;
            background: white;
            border-radius: 16px 16px 4px 16px;
            padding: 8px 16px;
            font-family: 'Mitr', sans-serif;
            font-size: 0.85rem;
            color: #1a6b3a;
            font-weight: 600;
            white-space: nowrap;
            box-shadow: 0 4px 14px rgba(0,0,0,0.15);
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50%       { transform: translateY(-12px); }
        }

        /* Tablet: ย่อ mascot ให้เล็กลง ไม่บัง form */
        @media (max-width: 900px) {
            .mascot-wrap { width: 220px; }
            .speech-bubble { font-size: 0.75rem; padding: 6px 12px; top: 14px; left: 12px; }
        }

        /* Mobile: ซ่อน mascot ทั้งหมด ให้ form ใช้พื้นที่เต็ม */
        @media (max-width: 600px) {
            .mascot-wrap { display: none; }
            .survey-card { padding: 1.6rem 1.4rem; border-radius: 20px; }
            body { padding: 16px; }
        }
    </style>
</head>
<body>

<!-- ฟอร์ม (กลางหน้า) -->
<div class="survey-card">
    <h1 class="survey-title">🌿 ก่อนเข้าชมเว็บไซต์ <br>ช่วยตอบคำถามสั้นๆ หน่อยนะคะ 💬✨</h1>

    <form action="survey_process.php" method="post" id="surveyForm">

        <div class="survey-group">
            <span class="group-label">เพศ</span>
            <div class="gender-row">
                <label class="gender-btn">
                    <input type="radio" name="gender" value="male">
                    <span class="gender-label"><span class="gender-emoji">👦</span>ชาย</span>
                </label>
                <label class="gender-btn">
                    <input type="radio" name="gender" value="female">
                    <span class="gender-label"><span class="gender-emoji">👧</span>หญิง</span>
                </label>
                <label class="gender-btn">
                    <input type="radio" name="gender" value="unspecified">
                    <span class="gender-label"><span class="gender-emoji">🌈</span>ไม่ระบุ</span>
                </label>
            </div>
            <p class="error-msg" id="err-gender">* กรุณาเลือกเพศ</p>
        </div>

        <div class="divider"></div>

        <div class="survey-group">
            <span class="group-label">อายุ</span>
            <div class="age-grid">
                <label class="age-btn"><input type="radio" name="age_range" value="15-25"><span class="age-label">15–25</span></label>
                <label class="age-btn"><input type="radio" name="age_range" value="26-35"><span class="age-label">26–35</span></label>
                <label class="age-btn"><input type="radio" name="age_range" value="36-45"><span class="age-label">36–45</span></label>
                <label class="age-btn"><input type="radio" name="age_range" value="46-55"><span class="age-label">46–55</span></label>
                <label class="age-btn"><input type="radio" name="age_range" value="56-65"><span class="age-label">56–65</span></label>
                <label class="age-btn"><input type="radio" name="age_range" value="65+"><span class="age-label">65+</span></label>
            </div>
            <p class="error-msg" id="err-age">* กรุณาเลือกช่วงอายุ</p>
        </div>

        <div class="btn-submit-wrap">
            <button type="submit" class="btn-submit">ส่งเลย! 🎉</button>
        </div>

    </form>
</div>

<!-- น้องปลา — fixed ติดขอบจอขวาล่าง ไม่เกี่ยวกับ layout ฟอร์มเลย -->
<div class="mascot-wrap">
    <div class="speech-bubble" id="bubble">สวัสดีครับ! 👋</div>
    <img src="assets/image/np.png" alt="mascot น้องปลา">
</div>

<script>
    const bubbles = ["สวัสดีค่ะ! 👋", "ยินดีต้อนรับค่ะ 😊", "ตอบได้เลยค่ะ!", "ไม่นานหรอกค่ะ 🐟"];
    let bi = 0;
    setInterval(() => {
        bi = (bi + 1) % bubbles.length;
        document.getElementById('bubble').textContent = bubbles[bi];
    }, 3000);

    document.querySelectorAll('input[type=radio]').forEach(r => {
        r.addEventListener('change', () => {
            const g = document.querySelector('input[name="gender"]:checked');
            const a = document.querySelector('input[name="age_range"]:checked');
            if (g && a) document.getElementById('bubble').textContent = "พร้อมส่งแล้ว! ✅";
        });
    });

    document.getElementById('surveyForm').addEventListener('submit', function (e) {
        let valid = true;
        const gender = document.querySelector('input[name="gender"]:checked');
        const age    = document.querySelector('input[name="age_range"]:checked');
        const errGender = document.getElementById('err-gender');
        const errAge    = document.getElementById('err-age');

        if (!gender) { errGender.classList.add('show'); valid = false; }
        else { errGender.classList.remove('show'); }

        if (!age) { errAge.classList.add('show'); valid = false; }
        else { errAge.classList.remove('show'); }

        if (!valid) {
            e.preventDefault();
            document.getElementById('bubble').textContent = "อย่าลืมเลือกด้วยนะ! 😅";
        }
    });
</script>

</body>
</html>