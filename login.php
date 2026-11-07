<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (current_user() !== null) {
    redirect('index.php');
}

$error = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $statement = db()->prepare("SELECT user_id, full_name, email, password, role FROM users WHERE email = ? AND status = 'active' LIMIT 1");
    $statement->execute([$email]);
    $user = $statement->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'user_id' => (int) $user['user_id'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
        $destination = $_SESSION['intended_url'] ?? 'index.php';
        unset($_SESSION['intended_url']);
        if (!is_string($destination) || !str_starts_with($destination, '/') || str_starts_with($destination, '//')) {
            $destination = 'index.php';
        }
        redirect($destination);
    }

    $error = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';
}

$pageTitle = 'เข้าสู่ระบบ';
$activePage = 'login';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-section">
    <div class="auth-card">
        <p class="eyebrow">WELCOME BACK</p>
        <h1>เข้าสู่ระบบ</h1>
        <p class="auth-lead">เข้าสู่บัญชีเพื่อจัดการการจองของคุณ</p>
        <?php if ($error !== ''): ?><div class="notice notice-error"><?= e($error) ?></div><?php endif; ?>
        <form class="stack-form" method="post" action="login.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label>อีเมล<input type="email" name="email" value="<?= e($email) ?>" autocomplete="email" required></label>
            <label>รหัสผ่าน<input type="password" name="password" autocomplete="current-password" required></label>
            <button class="button button-green button-wide" type="submit">เข้าสู่ระบบ</button>
        </form>
        <p class="auth-switch">ยังไม่มีบัญชี? <a href="register.php">สมัครสมาชิก</a></p>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
