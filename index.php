<?php
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'ยินดีต้อนรับ';
$activePage = 'home';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div class="hero-content">
        <p class="eyebrow">YOUR PRIVATE GETAWAY</p>
        <h1>พักผ่อนให้เต็มที่<br><span>ในแบบที่เป็นคุณ</span></h1>
        <p class="hero-copy">เติมเต็มวันพักผ่อนด้วยบรรยากาศอบอุ่น ห้องพักที่ใส่ใจทุกรายละเอียด และการต้อนรับที่เป็นกันเอง</p>
        <a class="button button-gold" href="#booking-search">ค้นหาห้องพัก <span aria-hidden="true">→</span></a>
    </div>
    <div class="hero-caption"><span></span> A stay to remember</div>
</section>

<section class="search-panel wrap" id="booking-search">
    <div class="section-intro">
        <p class="eyebrow">PLAN YOUR STAY</p>
        <h2>ค้นหาห้องพักที่ใช่</h2>
    </div>
    <form class="search-form" action="rooms.php" method="get">
        <label>วันเข้าพัก<input type="date" name="check_in" min="<?= e(date('Y-m-d')) ?>" value="<?= e(date('Y-m-d')) ?>" required></label>
        <label>วันออก<input type="date" name="check_out" min="<?= e(date('Y-m-d', strtotime('+1 day'))) ?>" value="<?= e(date('Y-m-d', strtotime('+1 day'))) ?>" required></label>
        <label>ผู้เข้าพัก<select name="guest_count"><option value="1">1 ท่าน</option><option value="2" selected>2 ท่าน</option><option value="3">3 ท่าน</option><option value="4">4 ท่าน</option><option value="5">5 ท่าน</option><option value="6">6 ท่าน</option></select></label>
        <button class="button button-green" type="submit">ค้นหาห้องว่าง</button>
    </form>
</section>

<section class="intro-section wrap">
    <div>
        <p class="eyebrow">WELCOME TO MARTEEN</p>
        <h2>พื้นที่แห่งการพักใจ<br>ท่ามกลางความเรียบง่าย</h2>
    </div>
    <p>ไม่ว่าจะเป็นทริปสั้น ๆ หรือวันหยุดที่รอคอย เราพร้อมให้ทุกช่วงเวลาของคุณเต็มไปด้วยความสบายและความทรงจำดี ๆ</p>
</section>

<section class="amenities">
    <div class="wrap amenities-inner">
        <div><span class="amenity-icon">✦</span><h3>ใส่ใจทุกรายละเอียด</h3><p>ห้องพักสะอาด พร้อมสิ่งอำนวยความสะดวกครบครัน</p></div>
        <div><span class="amenity-icon">⌂</span><h3>พักผ่อนอย่างเป็นส่วนตัว</h3><p>บรรยากาศสงบ เหมาะกับทุกช่วงเวลาที่อยากพัก</p></div>
        <div><span class="amenity-icon">♡</span><h3>บริการด้วยความอบอุ่น</h3><p>ทีมงานพร้อมดูแลให้การเข้าพักเป็นเรื่องง่าย</p></div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
