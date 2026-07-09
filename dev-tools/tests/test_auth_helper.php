<?php
// dev-tools/tests/test_auth_helper.php — chay: php dev-tools/tests/test_auth_helper.php
// Helper chi doc $_SESSION (mang superglobal) nen test CLI khong can session_start.
if (!defined('BASE_URL')) {
    define('BASE_URL', '/khsanxuat');
}
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
check('user thuong: isLoggedIn=true', isLoggedIn() === true);
check('user thuong: currentAppRole=null', currentAppRole() === null);
check('user thuong: userCan edit_settings=false', userCan('edit_settings') === false);

$_SESSION = ['user_id' => 5, 'app_role' => 'admin'];
check('admin: userCan edit_settings=true', userCan('edit_settings') === true);
check('admin: feature khong ton tai=false', userCan('khong_ton_tai') === false);

$_SESSION = ['user_id' => 5, 'app_role' => 'super_admin'];
check('super_admin: userCan edit_settings=true', userCan('edit_settings') === true);

$_SESSION = ['user_id' => 5, 'app_role' => 'gia_mao'];
check('role rac: userCan=false', userCan('edit_settings') === false);

echo $fails === 0 ? "ALL PASS\n" : "$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
