<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_auth();

$roomTypeId = filter_var($_POST['room_type_id'] ?? $_GET['room_type_id'] ?? null, FILTER_VALIDATE_INT);
$checkInValue = (string) ($_POST['check_in'] ?? $_GET['check_in'] ?? '');
$checkOutValue = (string) ($_POST['check_out'] ?? $_GET['check_out'] ?? '');
$guestCount = filter_var($_POST['guest_count'] ?? $_GET['guest_count'] ?? 2, FILTER_VALIDATE_INT);
$roomQuantity = filter_var($_POST['room_quantity'] ?? $_GET['room_quantity'] ?? 1, FILTER_VALIDATE_INT);
$checkIn = valid_date($checkInValue);
$checkOut = valid_date($checkOutValue);
$errors = [];
$room = null;
$bookingId = null;
$totalNights = 0;
$totalPrice = 0.0;

if ($roomTypeId === false || $roomTypeId === null || $roomTypeId < 1) {
    $errors[] = 'กรุณาเลือกประเภทห้องพัก';
} else {
    $statement = db()->prepare("SELECT * FROM room_types WHERE room_type_id = ? AND status = 'active' LIMIT 1");
    $statement->execute([$roomTypeId]);
    $room = $statement->fetch() ?: null;
    if ($room === null) {
        $errors[] = 'ไม่พบประเภทห้องพักที่เลือก';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $errors === []) {
    verify_csrf();
    $today = new DateTimeImmutable('today');
    if ($checkIn === null || $checkOut === null || $checkIn < $today || $checkOut <= $checkIn) {
        $errors[] = 'กรุณาเลือกวันเข้าพักและวันออกให้ถูกต้อง';
    }
    if ($guestCount === false || $guestCount < 1 || $guestCount > (int) $room['max_guests'] * max(1, (int) ($roomQuantity ?: 1))) {
        $errors[] = 'จำนวนผู้เข้าพักไม่ถูกต้องสำหรับห้องที่เลือก';
    }
    if ($roomQuantity === false || $roomQuantity < 1 || $roomQuantity > (int) $room['total_rooms']) {
        $errors[] = 'จำนวนห้องที่เลือกไม่ถูกต้อง';
    }
    $specialRequest = trim((string) ($_POST['special_request'] ?? ''));
    if (mb_strlen($specialRequest) > 1000) {
        $errors[] = 'คำขอเพิ่มเติมต้องไม่เกิน 1,000 ตัวอักษร';
    }

    if ($errors === []) {
        $totalNights = (int) $checkIn->diff($checkOut)->days;
        $pdo = db();
        try {
            $pdo->beginTransaction();
            $lock = $pdo->prepare("SELECT * FROM room_types WHERE room_type_id = ? AND status = 'active' FOR UPDATE");
            $lock->execute([$roomTypeId]);
            $room = $lock->fetch() ?: null;
            if ($room === null) {
                throw new RuntimeException('ประเภทห้องพักนี้ไม่เปิดให้จองแล้ว');
            }
            if (
                $roomQuantity > (int) $room['total_rooms']
                || $guestCount > (int) $room['max_guests'] * (int) $roomQuantity
            ) {
                $pdo->rollBack();
                $errors[] = 'จำนวนห้องหรือผู้เข้าพักไม่ถูกต้องสำหรับประเภทห้องนี้ กรุณาตรวจสอบข้อมูลอีกครั้ง';
            } else {
                $availability = $pdo->prepare(
                    "SELECT check_in, check_out, room_quantity
                     FROM bookings
                     WHERE room_type_id = ?
                        AND booking_status IN ('pending', 'confirmed', 'checked_in')
                        AND check_in < ? AND check_out > ?"
                );
                $availability->execute([$roomTypeId, $checkOut->format('Y-m-d'), $checkIn->format('Y-m-d')]);
                $bookedRooms = max_booked_rooms(
                    $availability->fetchAll(),
                    $checkIn->format('Y-m-d'),
                    $checkOut->format('Y-m-d')
                );
                if ((int) $room['total_rooms'] - $bookedRooms < $roomQuantity) {
                    $pdo->rollBack();
                    $errors[] = 'ห้องพักไม่เพียงพอสำหรับช่วงวันที่เลือก กรุณาค้นหาใหม่';
                } else {
                    $priceInCents = (int) round((float) $room['price_per_night'] * 100);
                    $totalPrice = number_format($priceInCents * $totalNights * (int) $roomQuantity / 100, 2, '.', '');
                    $bookingCode = 'MH' . date('ymd') . strtoupper(bin2hex(random_bytes(5)));
                    $insert = $pdo->prepare(
                        "INSERT INTO bookings
                            (booking_code, user_id, room_type_id, check_in, check_out, guest_count,
                             room_quantity, total_nights, total_price, special_request, booking_status)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
                    );
                    $insert->execute([
                        $bookingCode,
                        current_user()['user_id'],
                        $roomTypeId,
                        $checkIn->format('Y-m-d'),
                        $checkOut->format('Y-m-d'),
                        $guestCount,
                        $roomQuantity,
                        $totalNights,
                        $totalPrice,
                        $specialRequest !== '' ? $specialRequest : null,
                    ]);
                    $bookingId = (int) $pdo->lastInsertId();
                    $pdo->commit();
                }
            }
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }
}

if ($bookingId !== null) {
    set_flash('success', 'ส่งคำขอจองเรียบร้อยแล้ว เลขที่การจองของคุณคือ ' . $bookingCode);
    redirect('my-bookings.php');
}

$pageTitle = 'จองห้องพัก';
require __DIR__ . '/includes/header.php';
?>
<section class="page-banner page-banner-small"><p class="eyebrow">RESERVE YOUR STAY</p><h1>จองห้องพัก</h1></section>
<section class="wrap page-section booking-layout">
    <?php if ($errors !== []): ?><div class="notice notice-error"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
    <?php if ($room !== null): ?>
        <form class="booking-form stack-form" method="post" action="booking.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="room_type_id" value="<?= (int) $room['room_type_id'] ?>">
            <h2><?= e($room['room_name']) ?></h2>
            <p class="muted"><?= format_money($room['price_per_night']) ?> / ห้อง / คืน</p>
            <div class="form-row">
                <label>วันเข้าพัก<input type="date" name="check_in" min="<?= e(date('Y-m-d')) ?>" value="<?= e($checkIn?->format('Y-m-d') ?? '') ?>" required></label>
                <label>วันออก<input type="date" name="check_out" min="<?= e(date('Y-m-d', strtotime('+1 day'))) ?>" value="<?= e($checkOut?->format('Y-m-d') ?? '') ?>" required></label>
            </div>
            <div class="form-row">
                <label>จำนวนผู้เข้าพัก<input type="number" name="guest_count" min="1" max="<?= (int) $room['max_guests'] * (int) $room['total_rooms'] ?>" value="<?= $guestCount !== false ? (int) $guestCount : 1 ?>" required></label>
                <label>จำนวนห้อง<input type="number" name="room_quantity" min="1" max="<?= (int) $room['total_rooms'] ?>" value="<?= $roomQuantity !== false ? (int) $roomQuantity : 1 ?>" required></label>
            </div>
            <label>คำขอเพิ่มเติม<textarea name="special_request" rows="4" maxlength="1000" placeholder="เช่น ต้องการเตียงเสริม หรือเช็กอินล่วงเวลา"></textarea></label>
            <p class="form-note">ระบบจะแสดงยอดรวมและยืนยันห้องว่างก่อนบันทึกการจอง</p>
            <button class="button button-gold button-wide" type="submit">ยืนยันการจอง</button>
        </form>
        <aside class="booking-aside"><p class="eyebrow">GOOD TO KNOW</p><h2>รายละเอียดการจอง</h2><ul><li>คิดราคาตามจำนวนคืนที่เข้าพัก</li><li>คำขอจองมีสถานะรอตรวจสอบ</li><li>ตรวจสอบสถานะได้ที่เมนู “การจองของฉัน”</li></ul></aside>
    <?php else: ?><div class="empty-state"><h2>ไม่สามารถเปิดการจองได้</h2><a href="rooms.php">กลับไปเลือกห้องพัก</a></div><?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
