<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_auth();

$statement = db()->prepare(
    "SELECT b.*, rt.room_name
     FROM bookings b
     JOIN room_types rt ON rt.room_type_id = b.room_type_id
     WHERE b.user_id = ?
     ORDER BY b.created_at DESC"
);
$statement->execute([current_user()['user_id']]);
$bookings = $statement->fetchAll();
$statuses = [
    'pending' => 'รอตรวจสอบ',
    'confirmed' => 'ยืนยันแล้ว',
    'checked_in' => 'เข้าพักแล้ว',
    'checked_out' => 'เช็กเอาต์แล้ว',
    'cancelled' => 'ยกเลิกแล้ว',
];

$pageTitle = 'การจองของฉัน';
$activePage = 'bookings';
require __DIR__ . '/includes/header.php';
?>
<section class="page-banner page-banner-small"><p class="eyebrow">YOUR RESERVATIONS</p><h1>การจองของฉัน</h1></section>
<section class="wrap page-section">
    <?php if ($bookings === []): ?>
        <div class="empty-state"><h2>ยังไม่มีรายการจอง</h2><p>เริ่มวางแผนวันพักผ่อนของคุณได้เลย</p><a class="button button-green" href="rooms.php">เลือกห้องพัก</a></div>
    <?php else: ?>
        <div class="booking-list">
            <?php foreach ($bookings as $booking): ?>
                <article class="booking-card">
                    <div class="booking-card-top"><div><p class="eyebrow">BOOKING CODE</p><h2><?= e($booking['booking_code']) ?></h2></div><span class="status status-<?= e($booking['booking_status']) ?>"><?= e($statuses[$booking['booking_status']] ?? $booking['booking_status']) ?></span></div>
                    <div class="booking-details">
                        <div><span>ประเภทห้อง</span><strong><?= e($booking['room_name']) ?> · <?= (int) $booking['room_quantity'] ?> ห้อง</strong></div>
                        <div><span>วันเข้าพัก</span><strong><?= e(date('d/m/Y', strtotime($booking['check_in']))) ?></strong></div>
                        <div><span>วันออก</span><strong><?= e(date('d/m/Y', strtotime($booking['check_out']))) ?></strong></div>
                        <div><span>จำนวนคืน</span><strong><?= (int) $booking['total_nights'] ?> คืน</strong></div>
                    </div>
                    <div class="booking-card-bottom"><strong>ยอดรวม <?= format_money($booking['total_price']) ?></strong>
                        <?php if (in_array($booking['booking_status'], ['pending', 'confirmed'], true) && $booking['check_in'] > date('Y-m-d')): ?>
                            <form method="post" action="cancel-booking.php" onsubmit="return confirm('ยืนยันยกเลิกการจองนี้หรือไม่?')">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="booking_id" value="<?= (int) $booking['booking_id'] ?>">
                                <button class="button button-outline button-danger" type="submit">ยกเลิกการจอง</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
