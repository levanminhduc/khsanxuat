<?php
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
    'actions' => []
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

$search_types = [
    'xuong' => 'Xưởng',
    'line' => 'Line',
    'po' => 'PO',
    'style' => 'Style',
    'model' => 'Model'
];
?>
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
                    <button type="submit" class="mobile-search-button">🔍 Tìm kiếm</button>
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
        </div>
    </div>
    <?php endif; ?>
</div>
