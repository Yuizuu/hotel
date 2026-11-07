<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed');
}

verify_csrf();
$bookingId = filter_var($_POST['booking_id'] ?? null, FILTER_VALIDATE_INT);
if ($bookingId === false || $bookingId === null || $bookingId < 1) {
    set_flash('error', 'ไม่พบรายการจองที่ต้องการยกเลิก');
    redirect('my-bookings.php');
}

$statement = db()->prepare(
    "UPDATE bookings SET booking_status = 'cancelled', updated_at = CURRENT_TIMESTAMP
     WHERE booking_id = ? AND user_id = ?
        AND booking_status IN ('pending', 'confirmed') AND check_in > CURRENT_DATE"
);
$statement->execute([$bookingId, current_user()['user_id']]);

if ($statement->rowCount() > 0) {
    set_flash('success', 'ยกเลิกการจองเรียบร้อยแล้ว');
} else {
    set_flash('error', 'ไม่สามารถยกเลิกรายการนี้ได้ อาจถูกยกเลิกไปแล้วหรือเลยกำหนดวันเข้าพัก');
}

redirect('my-bookings.php');
