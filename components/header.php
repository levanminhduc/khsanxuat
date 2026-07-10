<?php
require_once BASE_PATH . '/includes/security/auth-helper.php';
require_once BASE_PATH . '/includes/security/csrf-helper.php';

if (!isset($header_config) || !is_array($header_config)) {
    $header_config = [];
}

if (empty($header_config['title'])) {
    $header_config['title'] = 'Untitled Page';
    trigger_error('Header component: Required "title" parameter is missing', E_USER_WARNING);
}

$defaults = [
    'title' => 'Untitled Page',
    'title_short' => null,
    'logo_path' => 'img/logoht.png',
    'logo_link' => '/trangchu/',
    'show_search' => false,
    'show_mobile_menu' => true,
    'search_params' => [
        'action' => '',
        'month' => '',
        'year' => '',
        'search_type' => 'xuong',
        'search_value' => '',
        'placeholder' => 'Nhập từ khóa tìm kiếm...',
        'placeholder_suggestions' => []
    ],
    'actions' => [],
    'mobile_actions' => []
];

$config = array_merge($defaults, $header_config);

if (isset($header_config['search_params']) && is_array($header_config['search_params'])) {
    $config['search_params'] = array_merge($defaults['search_params'], $header_config['search_params']);
}

if (empty($config['title_short'])) {
    $config['title_short'] = $config['title'];
}

function header_escape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function header_selected($type, $current) {
    return ($type === $current) ? 'selected' : '';
}

// Viet tat ten user cho badge: chu cai dau moi tu, in hoa, bo dau tieng Viet
// (Đ→D, nhom nguyen am co thanh dieu) — 'Lê Văn Minh Đức' → 'LVMD'.
function header_user_initials($full_name) {
    $words = preg_split('/\s+/u', trim((string)$full_name), -1, PREG_SPLIT_NO_EMPTY);
    if (empty($words)) {
        return 'USER';
    }
    $map = [
        'Á' => 'A', 'À' => 'A', 'Ả' => 'A', 'Ã' => 'A', 'Ạ' => 'A',
        'Ă' => 'A', 'Ắ' => 'A', 'Ằ' => 'A', 'Ẳ' => 'A', 'Ẵ' => 'A', 'Ặ' => 'A',
        'Â' => 'A', 'Ấ' => 'A', 'Ầ' => 'A', 'Ẩ' => 'A', 'Ẫ' => 'A', 'Ậ' => 'A',
        'É' => 'E', 'È' => 'E', 'Ẻ' => 'E', 'Ẽ' => 'E', 'Ẹ' => 'E',
        'Ê' => 'E', 'Ế' => 'E', 'Ề' => 'E', 'Ể' => 'E', 'Ễ' => 'E', 'Ệ' => 'E',
        'Í' => 'I', 'Ì' => 'I', 'Ỉ' => 'I', 'Ĩ' => 'I', 'Ị' => 'I',
        'Ó' => 'O', 'Ò' => 'O', 'Ỏ' => 'O', 'Õ' => 'O', 'Ọ' => 'O',
        'Ô' => 'O', 'Ố' => 'O', 'Ồ' => 'O', 'Ổ' => 'O', 'Ỗ' => 'O', 'Ộ' => 'O',
        'Ơ' => 'O', 'Ớ' => 'O', 'Ờ' => 'O', 'Ở' => 'O', 'Ỡ' => 'O', 'Ợ' => 'O',
        'Ú' => 'U', 'Ù' => 'U', 'Ủ' => 'U', 'Ũ' => 'U', 'Ụ' => 'U',
        'Ư' => 'U', 'Ứ' => 'U', 'Ừ' => 'U', 'Ử' => 'U', 'Ữ' => 'U', 'Ự' => 'U',
        'Ý' => 'Y', 'Ỳ' => 'Y', 'Ỷ' => 'Y', 'Ỹ' => 'Y', 'Ỵ' => 'Y',
        'Đ' => 'D',
    ];
    $initials = '';
    foreach ($words as $word) {
        $first = mb_strtoupper(mb_substr($word, 0, 1, 'UTF-8'), 'UTF-8');
        $initials .= strtr($first, $map);
    }
    return $initials !== '' ? $initials : 'USER';
}

$title = header_escape($config['title']);
$title_short = header_escape($config['title_short']);
$logo_path = header_escape($config['logo_path']);
$logo_link = header_escape($config['logo_link']);
$show_search = (bool)$config['show_search'];
$show_mobile_menu = (bool)$config['show_mobile_menu'];

$search_action = header_escape($config['search_params']['action']);
$search_month = header_escape($config['search_params']['month']);
$search_year = header_escape($config['search_params']['year']);
$search_type = $config['search_params']['search_type'] ?? 'xuong';
$search_value = header_escape($config['search_params']['search_value']);
$search_placeholder = header_escape($config['search_params']['placeholder']);
$search_placeholder_suggestions = [];

if (is_array($config['search_params']['placeholder_suggestions'])) {
    $search_placeholder_suggestions = array_values(array_filter(
        $config['search_params']['placeholder_suggestions'],
        function($suggestion) {
            return trim((string)$suggestion) !== '';
        }
    ));
}

if (empty($search_placeholder_suggestions)) {
    $search_placeholder_suggestions = [];
}

$search_placeholder_json = header_escape(json_encode(
    $search_placeholder_suggestions,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
));

$actions = is_array($config['actions']) ? $config['actions'] : [];
$actions = array_values(array_filter($actions, function($action) {
    return is_array($action) && !empty($action['url']) && !empty($action['icon']);
}));

$mobile_actions = is_array($config['mobile_actions']) ? $config['mobile_actions'] : [];
$mobile_actions = array_values(array_filter($mobile_actions, function($action) {
    return is_array($action)
        && !empty($action['title'])
        && (!empty($action['url']) || !empty($action['onclick']))
        && (!empty($action['icon']) || !empty($action['icon_class']));
}));

$user_logged_in = isLoggedIn();
$user_raw_name = '';
if ($user_logged_in) {
    $user_raw_name = !empty($_SESSION['full_name'])
        ? $_SESSION['full_name']
        : (isset($_SESSION['username']) ? $_SESSION['username'] : '');
}
$user_display_name = header_escape($user_raw_name);
$user_initials = header_escape(header_user_initials($user_raw_name));
$logout_url = $user_logged_in
    ? header_escape(BASE_URL . '/account/logout.php?csrf_token=' . urlencode(generateCsrfToken()))
    : '';
$login_url = header_escape(BASE_URL . '/account/login.php?redirect=' . urlencode(
    isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : BASE_URL . '/index.php'
));

$search_types = [
    'xuong' => 'Xưởng',
    'line' => 'Line',
    'po' => 'PO',
    'style' => 'Style',
    'model' => 'Model'
];
?>
<script>window.BASE_URL = <?php echo json_encode(defined('BASE_URL') ? BASE_URL : ''); ?>;</script>
<div class="header-component">
    <nav class="navbar" role="navigation" aria-label="Main navigation">
        <div class="navbar-left">
            <a href="<?php echo $logo_link; ?>" aria-label="Go to homepage">
                <img src="<?php echo $logo_path; ?>" alt="Logo">
            </a>
        </div>

        <div class="navbar-center">
            <h1 class="navbar-brand">
                <span class="title-full"><?php echo $title; ?></span>
                <span class="title-short"><?php echo $title_short; ?></span>
            </h1>
        </div>

        <?php if ($show_mobile_menu): ?>
        <button
            class="mobile-toggle"
            id="navbar-toggle"
            type="button"
            aria-label="Toggle navigation menu"
            aria-expanded="false"
            aria-controls="navbar-dropdown"
        >
            <div class="hamburger-icon">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </button>
        <?php endif; ?>

        <div class="navbar-right">
            <?php if ($show_search): ?>
            <div class="search-container">
                <form class="search-form" action="<?php echo $search_action; ?>" method="GET">
                    <?php if (!empty($search_month)): ?>
                    <input type="hidden" name="month" value="<?php echo $search_month; ?>">
                    <?php endif; ?>
                    <?php if (!empty($search_year)): ?>
                    <input type="hidden" name="year" value="<?php echo $search_year; ?>">
                    <?php endif; ?>
                    <div class="search-group">
                        <select name="search_type" class="search-select" aria-label="Search type">
                            <?php foreach ($search_types as $type_value => $type_label): ?>
                            <option value="<?php echo header_escape($type_value); ?>" <?php echo header_selected($type_value, $search_type); ?>>
                                <?php echo header_escape($type_label); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($search_placeholder_suggestions)): ?>
                        <div
                            class="search-input-wrap<?php echo $search_value !== '' ? ' has-value' : ''; ?>"
                            data-placeholder-rotator
                            data-placeholder-phrases="<?php echo $search_placeholder_json; ?>"
                        >
                            <input
                                type="text"
                                name="search_value"
                                placeholder=""
                                value="<?php echo $search_value; ?>"
                                class="search-input"
                                aria-label="Search input"
                            >
                            <span class="search-placeholder-text" aria-hidden="true"><?php echo header_escape($search_placeholder_suggestions[0]); ?></span>
                        </div>
                        <?php else: ?>
                        <input
                            type="text"
                            name="search_value"
                            placeholder="<?php echo $search_placeholder; ?>"
                            value="<?php echo $search_value; ?>"
                            class="search-input"
                            aria-label="Search input"
                        >
                        <?php endif; ?>
                        <button type="submit" class="search-button" aria-label="Search">🔍</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <ul class="nav-menu">
                <?php foreach ($actions as $action): ?>
                <?php if (!empty($action['url']) && !empty($action['icon'])): ?>
                <li class="nav-item">
                    <a
                        href="<?php echo header_escape($action['url']); ?>"
                        class="action-btn"
                        <?php if (!empty($action['tooltip'])): ?>
                        title="<?php echo header_escape($action['tooltip']); ?>"
                        <?php endif; ?>
                        <?php if (!empty($action['download'])): ?>
                        data-loading-download data-loading-text="<?php echo header_escape($action['loading_text'] ?? 'Đang chuẩn bị tải xuống...'); ?>"
                        <?php endif; ?>
                    >
                        <img
                            src="<?php echo header_escape($action['icon']); ?>"
                            alt="<?php echo header_escape($action['title'] ?? ''); ?>"
                        >
                    </a>
                </li>
                <?php endif; ?>
                <?php endforeach; ?>
            </ul>

            <div class="user-menu">
                <?php if ($user_logged_in): ?>
                <a href="<?php echo $logout_url; ?>" class="user-badge" title="<?php echo $user_display_name; ?> — Đăng xuất" onclick="return confirm('Đăng xuất?');"><?php echo $user_initials; ?></a>
                <?php else: ?>
                <a href="<?php echo $login_url; ?>" class="login-btn">Đăng nhập</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <?php if ($show_mobile_menu): ?>
    <div class="mobile-menu" id="navbar-dropdown" role="menu">
        <?php if ($show_search): ?>
        <div class="mobile-search-container">
            <form class="search-form" action="<?php echo $search_action; ?>" method="GET">
                <?php if (!empty($search_month)): ?>
                <input type="hidden" name="month" value="<?php echo $search_month; ?>">
                <?php endif; ?>
                <?php if (!empty($search_year)): ?>
                <input type="hidden" name="year" value="<?php echo $search_year; ?>">
                <?php endif; ?>
                <div class="mobile-search-group">
                    <select name="search_type" class="mobile-search-select" aria-label="Search type">
                        <?php foreach ($search_types as $type_value => $type_label): ?>
                        <option value="<?php echo header_escape($type_value); ?>" <?php echo header_selected($type_value, $search_type); ?>>
                            <?php echo header_escape($type_label); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($search_placeholder_suggestions)): ?>
                    <div
                        class="mobile-search-input-wrap<?php echo $search_value !== '' ? ' has-value' : ''; ?>"
                        data-placeholder-rotator
                        data-placeholder-phrases="<?php echo $search_placeholder_json; ?>"
                    >
                        <input
                            type="text"
                            name="search_value"
                            placeholder=""
                            value="<?php echo $search_value; ?>"
                            class="mobile-search-input"
                            aria-label="Search input"
                        >
                        <span class="search-placeholder-text" aria-hidden="true"><?php echo header_escape($search_placeholder_suggestions[0]); ?></span>
                    </div>
                    <?php else: ?>
                    <input
                        type="text"
                        name="search_value"
                        placeholder="<?php echo $search_placeholder; ?>"
                        value="<?php echo $search_value; ?>"
                        class="mobile-search-input"
                        aria-label="Search input"
                    >
                    <?php endif; ?>
                    <button type="submit" class="mobile-search-button" aria-label="Tìm kiếm">🔍 Tìm kiếm</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div class="mobile-nav-items">
            <?php foreach ($actions as $action): ?>
            <?php if (!empty($action['url']) && !empty($action['icon'])): ?>
            <a
                href="<?php echo header_escape($action['url']); ?>"
                class="mobile-nav-item"
                role="menuitem"
                <?php if (!empty($action['download'])): ?>
                data-loading-download data-loading-text="<?php echo header_escape($action['loading_text'] ?? 'Đang chuẩn bị tải xuống...'); ?>"
                <?php endif; ?>
            >
                <img
                    src="<?php echo header_escape($action['icon']); ?>"
                    alt=""
                    aria-hidden="true"
                >
                <?php echo header_escape($action['title'] ?? ''); ?>
            </a>
            <?php endif; ?>
            <?php endforeach; ?>

            <?php foreach ($mobile_actions as $action): ?>
            <?php
            $mobile_item_class = 'mobile-nav-item';
            if (empty($action['url'])) {
                $mobile_item_class .= ' mobile-nav-item--button';
            }
            if (!empty($action['class'])) {
                $mobile_item_class .= ' ' . trim((string)$action['class']);
            }
            ?>
            <?php if (!empty($action['url'])): ?>
            <a
                href="<?php echo header_escape($action['url']); ?>"
                class="<?php echo header_escape($mobile_item_class); ?>"
                role="menuitem"
            >
                <?php if (!empty($action['icon'])): ?>
                <img
                    src="<?php echo header_escape($action['icon']); ?>"
                    alt=""
                    aria-hidden="true"
                >
                <?php else: ?>
                <i class="<?php echo header_escape($action['icon_class']); ?> mobile-nav-item-icon" aria-hidden="true"></i>
                <?php endif; ?>
                <?php echo header_escape($action['title']); ?>
            </a>
            <?php else: ?>
            <button
                type="button"
                class="<?php echo header_escape($mobile_item_class); ?>"
                role="menuitem"
                data-mobile-menu-close="true"
                <?php if (!empty($action['onclick'])): ?>
                onclick="<?php echo header_escape($action['onclick']); ?>"
                <?php endif; ?>
            >
                <?php if (!empty($action['icon'])): ?>
                <img
                    src="<?php echo header_escape($action['icon']); ?>"
                    alt=""
                    aria-hidden="true"
                >
                <?php else: ?>
                <i class="<?php echo header_escape($action['icon_class']); ?> mobile-nav-item-icon" aria-hidden="true"></i>
                <?php endif; ?>
                <?php echo header_escape($action['title']); ?>
            </button>
            <?php endif; ?>
            <?php endforeach; ?>

            <?php if ($user_logged_in): ?>
            <a href="<?php echo $logout_url; ?>" class="mobile-nav-item" role="menuitem" onclick="return confirm('Đăng xuất?');">
                <span class="user-badge user-badge--mobile" aria-hidden="true"><?php echo $user_initials; ?></span>
                Đăng xuất
            </a>
            <?php else: ?>
            <a href="<?php echo $login_url; ?>" class="mobile-nav-item" role="menuitem">
                Đăng nhập
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
