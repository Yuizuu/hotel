<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (current_user() !== null) {
    redirect('index.php');
}

$errors = [];
$fullName = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');

    if ($fullName === '' || mb_strlen($fullName) > 150) {
        $errors[] = 'กรุณากรอกชื่อและนามสกุล';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $errors[] = 'กรุณากรอกอีเมลให้ถูกต้อง';
    }
    if ($phone === '' || mb_strlen($phone) > 30) {
        $errors[] = 'กรุณากรอกเบอร์โทรศัพท์';
    }
    if (strlen($password) < 8) {
        $errors[] = 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร';
    }
    if ($password !== $passwordConfirmation) {
        $errors[] = 'รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน';
    }

    if ($errors === []) {
        $statement = db()->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
        $statement->execute([$email]);
        if ($statement->fetch()) {
            $errors[] = 'อีเมลนี้มีบัญชีผู้ใช้แล้ว';
        } else {
            try {
                $statement = db()->prepare(
                    "INSERT INTO users (full_name, email, phone, password, role, status)
                     VALUES (?, ?, ?, ?, 'customer', 'active')"
                );
                $statement->execute([$fullName, $email, $phone, password_hash($password, PASSWORD_DEFAULT)]);
                set_flash('success', 'สมัครสมาชิกเรียบร้อยแล้ว กรุณาเข้าสู่ระบบ');
                redirect('login.php');
            } catch (PDOException $exception) {
                if ($exception->getCode() !== '23000') {
                    throw $exception;
                }
                $errors[] = 'อีเมลนี้มีบัญชีผู้ใช้แล้ว';
            }
        }
    }
}

$pageTitle = 'สมัครสมาชิก';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-section">
    <div class="auth-card">
        <p class="eyebrow">JOIN MARTEEN</p>
        <h1>สร้างบัญชีสมาชิก</h1>
        <p class="auth-lead">สมัครสมาชิกเพื่อเริ่มต้นจองช่วงเวลาพักผ่อนของคุณ</p>
        <?php if ($errors !== []): ?><div class="notice notice-error"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
        <form class="stack-form" method="post" action="register.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label>ชื่อและนามสกุล<input type="text" name="full_name" value="<?= e($fullName) ?>" maxlength="150" autocomplete="name" required></label>
            <label>อีเมล<input type="email" name="email" value="<?= e($email) ?>" maxlength="190" autocomplete="email" required></label>
            <label>เบอร์โทรศัพท์<input type="tel" name="phone" value="<?= e($phone) ?>" maxlength="30" autocomplete="tel" required></label>
            <label>รหัสผ่าน<input type="password" name="password" minlength="8" autocomplete="new-password" required><small>อย่างน้อย 8 ตัวอักษร</small></label>
            <label>ยืนยันรหัสผ่าน<input type="password" name="password_confirmation" minlength="8" autocomplete="new-password" required></label>
            <button class="button button-green button-wide" type="submit">สมัครสมาชิก</button>
        </form>
        <p class="auth-switch">มีบัญชีแล้ว? <a href="login.php">เข้าสู่ระบบ</a></p>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
