<?php
// dev-tools/tests/test_auth_helper.php — chay: php dev-tools/tests/test_auth_helper.php
// Helper chi doc $_SESSION (mang superglobal) nen test CLI khong can session_start.
// Phien hop le phai co dau an logged_in_at do authV2 ghi (SessionContract::isLoggedIn).
if (!defined('BASE_URL')) {
    define('BASE_URL', '/khsanxuat');
}
require_once __DIR__ . '/../../../authV2/autoload.php';
require_once __DIR__ . '/../../includes/security/auth-helper.php';

$fails = 0;
function check($name, $cond)
{
    global $fails;
    if ($cond) {
        echo "PASS  $name\n";
    } else {
        echo "FAIL  $name\n";
        $fails++;
    }
}

$_SESSION = [];
check('chua login: isLoggedIn=false', isLoggedIn() === false);
check('chua login: userCan=false', userCan('edit_settings') === false);
check('chua login: currentAppRole=null', currentAppRole() === null);

$_SESSION = ['user_id' => 5];
check('phien ke thua (thieu logged_in_at): isLoggedIn=false', isLoggedIn() === false);

$_SESSION = ['user_id' => 5, 'logged_in_at' => time()];
check('user thuong: isLoggedIn=true', isLoggedIn() === true);
check('user thuong: currentAppRole=null', currentAppRole() === null);
check('user thuong: userCan edit_settings=false', userCan('edit_settings') === false);

$_SESSION = ['user_id' => 5, 'logged_in_at' => time(), 'app_role' => 'admin'];
check('admin: userCan edit_settings=true', userCan('edit_settings') === true);
check('admin: feature khong ton tai=false', userCan('khong_ton_tai') === false);

$_SESSION = ['user_id' => 5, 'logged_in_at' => time(), 'app_role' => 'super_admin'];
check('super_admin: userCan edit_settings=true', userCan('edit_settings') === true);

$_SESSION = ['user_id' => 5, 'logged_in_at' => time(), 'app_role' => 'gia_mao'];
check('role rac: userCan=false', userCan('edit_settings') === false);

echo $fails === 0 ? "ALL PASS\n" : "$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
