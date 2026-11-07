<?php
require_once __DIR__ . '/includes/bootstrap.php';

$today = new DateTimeImmutable('today');
$checkInValue = $_GET['check_in'] ?? $today->format('Y-m-d');
$checkOutValue = $_GET['check_out'] ?? $today->modify('+1 day')->format('Y-m-d');
$guestCount = filter_var($_GET['guest_count'] ?? 2, FILTER_VALIDATE_INT);
$checkIn = is_string($checkInValue) ? valid_date($checkInValue) : null;
$checkOut = is_string($checkOutValue) ? valid_date($checkOutValue) : null;
$errors = [];

if ($checkIn === null || $checkOut === null || $checkIn < $today || $checkOut <= $checkIn) {
    $errors[] = 'กรุณาเลือกวันเข้าพักและวันออกให้ถูกต้อง โดยวันเข้าพักต้องไม่ใช่วันย้อนหลัง';
}
if ($guestCount === false || $guestCount < 1 || $guestCount > 20) {
    $errors[] = 'จำนวนผู้เข้าพักไม่ถูกต้อง';
    $guestCount = 2;
}

$rooms = [];
if ($errors === []) {
    $statement = db()->prepare(
        "SELECT * FROM room_types
         WHERE status = 'active'
         ORDER BY price_per_night"
    );
    $statement->execute();
    $rooms = $statement->fetchAll();

    if ($rooms !== []) {
        $bookings = db()->prepare(
            "SELECT room_type_id, check_in, check_out, room_quantity
             FROM bookings
             WHERE booking_status IN ('pending', 'confirmed', 'checked_in')
                AND check_in < ? AND check_out > ?"
        );
        $bookings->execute([$checkOut->format('Y-m-d'), $checkIn->format('Y-m-d')]);
        $bookingsByType = [];
        foreach ($bookings->fetchAll() as $booking) {
            $bookingsByType[(int) $booking['room_type_id']][] = $booking;
        }

        $availableRooms = [];
        foreach ($rooms as $room) {
            $bookedRooms = max_booked_rooms(
                $bookingsByType[(int) $room['room_type_id']] ?? [],
                $checkIn->format('Y-m-d'),
                $checkOut->format('Y-m-d')
            );
            $room['available_rooms'] = (int) $room['total_rooms'] - $bookedRooms;
            if (
                (int) $room['available_rooms'] > 0
                && (int) $room['available_rooms'] * (int) $room['max_guests'] >= (int) $guestCount
            ) {
                $availableRooms[] = $room;
            }
        }
        $rooms = $availableRooms;
    }
}

$pageTitle = 'ห้องพัก';
$activePage = 'rooms';
require __DIR__ . '/includes/header.php';
?>
<section class="page-banner">
    <p class="eyebrow">STAY YOUR WAY</p>
    <h1>ห้องพักของเรา</h1>
    <p>เลือกพื้นที่พักผ่อนที่เหมาะกับการเดินทางของคุณ</p>
</section>
<section class="wrap page-section">
    <form class="search-form search-form-compact" action="rooms.php" method="get">
        <label>วันเข้าพัก<input type="date" name="check_in" min="<?= e($today->format('Y-m-d')) ?>" value="<?= e($checkIn?->format('Y-m-d') ?? $today->format('Y-m-d')) ?>" required></label>
        <label>วันออก<input type="date" name="check_out" min="<?= e($today->modify('+1 day')->format('Y-m-d')) ?>" value="<?= e($checkOut?->format('Y-m-d') ?? $today->modify('+1 day')->format('Y-m-d')) ?>" required></label>
        <label>ผู้เข้าพัก<select name="guest_count"><?php for ($count = 1; $count <= 6; $count++): ?><option value="<?= $count ?>" <?= (int) $guestCount === $count ? 'selected' : '' ?>><?= $count ?> ท่าน</option><?php endfor; ?></select></label>
        <button class="button button-green" type="submit">ค้นหาห้องว่าง</button>
    </form>

    <?php if ($errors !== []): ?>
        <div class="notice notice-error"><?= e(implode(' ', $errors)) ?></div>
    <?php elseif ($rooms === []): ?>
        <div class="empty-state"><h2>ยังไม่มีห้องพักที่ตรงกับการค้นหา</h2><p>ลองเปลี่ยนวันเข้าพักหรือจำนวนผู้เข้าพัก</p></div>
    <?php else: ?>
        <div class="room-grid">
            <?php foreach ($rooms as $room): ?>
                <article class="room-card">
                    <div class="room-art room-art-<?= (int) $room['room_type_id'] % 3 + 1 ?>">
                        <span class="room-art-label">MARTEEN HOTEL</span>
                        <span class="room-art-name"><?= e($room['room_name']) ?></span>
                    </div>
                    <div class="room-card-body">
                        <div class="room-card-title"><h2><?= e($room['room_name']) ?></h2><span><?= (int) $room['max_guests'] ?> ท่าน</span></div>
                        <p><?= e($room['description']) ?></p>
                        <div class="room-card-meta"><span><?= e($room['bed_type']) ?> · <?= e((string) $room['room_size']) ?> ตร.ม.</span>
                            <?php if ((int) $room['available_rooms'] > 0): ?><span class="availability"><?= (int) $room['available_rooms'] ?> ห้องว่าง</span><?php else: ?><span class="unavailable">เต็ม</span><?php endif; ?>
                        </div>
                        <div class="room-card-bottom"><p><strong><?= format_money($room['price_per_night']) ?></strong> / คืน</p>
                            <?php if ((int) $room['available_rooms'] > 0): ?>
                                <a class="button button-outline" href="booking.php?room_type_id=<?= (int) $room['room_type_id'] ?>&amp;check_in=<?= e($checkIn->format('Y-m-d')) ?>&amp;check_out=<?= e($checkOut->format('Y-m-d')) ?>&amp;guest_count=<?= (int) $guestCount ?>&amp;room_quantity=<?= (int) ceil((int) $guestCount / (int) $room['max_guests']) ?>">จองห้องนี้</a>
                            <?php else: ?><button class="button button-disabled" type="button" disabled>ไม่มีห้องว่าง</button><?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
