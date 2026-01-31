<?php
/**
 * Form Page Component
 * 
 * Reusable form page wrapper for all form-based pages in the application.
 * Provides consistent structure, styling, and responsive design.
 * 
 * Usage:
 * require_once 'components/form-page.php';
 * 
 * render_form_page_start([
 *     'title' => 'Page Title',
 *     'back_url' => 'index.php',
 *     'alerts' => [['type' => 'success', 'message' => 'Saved!']]
 * ]);
 * 
 * // Your form content here
 * 
 * render_form_page_end();
 * 
 * @version 1.0.0
 */

// ============================================
// Helper Functions
// ============================================

/**
 * Safely escape output for HTML context
 * @param mixed $value The value to escape
 * @return string Escaped value
 */
function form_page_escape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// ============================================
// Form Page Start Function
// ============================================

/**
 * Render form page start wrapper
 * 
 * Opens the form page wrapper with optional header and alerts.
 * 
 * @param array $config Configuration array
 *   - title: (required) Main page title
 *   - subtitle: (optional) Subtitle text
 *   - show_back_button: (optional) Show back navigation (default: true)
 *   - back_url: (optional) Back button URL (default: 'index.php')
 *   - alerts: (optional) Array of alert messages
 * @return void Outputs HTML directly
 */
function render_form_page_start($config) {
    // Set defaults
    $defaults = [
        'title' => 'Untitled Page',
        'subtitle' => '',
        'show_back_button' => true,
        'back_url' => 'index.php',
        'alerts' => []
    ];
    
    // Merge with defaults
    $config = array_merge($defaults, $config);
    
    // Validate required fields
    if (empty($config['title'])) {
        $config['title'] = 'Untitled Page';
        trigger_error('Form page component: Required "title" parameter is missing', E_USER_WARNING);
    }
    
    // Escape values
    $title = form_page_escape($config['title']);
    $subtitle = form_page_escape($config['subtitle']);
    $back_url = form_page_escape($config['back_url']);
    $show_back = (bool)$config['show_back_button'];
    $alerts = is_array($config['alerts']) ? $config['alerts'] : [];
    
    ?>
<!-- Form Page Component Start -->
<div class="form-page-component">
    <div class="form-page-container">
        <div class="form-page-card">
            <div class="form-page-header">
                <?php if ($show_back): ?>
                <a href="<?php echo $back_url; ?>" class="form-page-back-link">Quay lại</a>
                <?php endif; ?>
                
                <h1 class="form-page-title"><?php echo $title; ?></h1>
                
                <?php if (!empty($subtitle)): ?>
                <p class="form-page-subtitle"><?php echo $subtitle; ?></p>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($alerts)): ?>
            <div class="form-page-alerts" style="padding: var(--form-page-spacing-md); padding-bottom: 0;">
                <?php foreach ($alerts as $alert): ?>
                <?php
                    $alert_type = isset($alert['type']) ? $alert['type'] : 'info';
                    $alert_message = isset($alert['message']) ? form_page_escape($alert['message']) : '';
                    if (empty($alert_message)) continue;
                ?>
                <div class="alert alert-<?php echo form_page_escape($alert_type); ?>">
                    <span class="alert-icon"></span>
                    <span class="alert-content"><?php echo $alert_message; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <div class="form-page-body">
    <?php
}

// ============================================
// Form Page End Function
// ============================================

/**
 * Render form page end wrapper
 * 
 * Closes the form page wrapper opened by render_form_page_start().
 * 
 * @return void Outputs HTML directly
 */
function render_form_page_end() {
    ?>
            </div><!-- /.form-page-body -->
        </div><!-- /.form-page-card -->
    </div><!-- /.form-page-container -->
</div><!-- /.form-page-component -->
<!-- Form Page Component End -->
    <?php
}

// ============================================
// Info Table Function
// ============================================

/**
 * Render responsive info table/card
 * 
 * Renders an info table that transforms to cards on mobile.
 * 
 * @param array $config Configuration array
 *   - rows: (required) Array of row data with 'label' and 'value' keys
 *   - class: (optional) Additional wrapper classes
 *   - columns: (optional) Number of columns on desktop (default: 2)
 * @return void Outputs HTML directly
 */
function render_form_info_table($config) {
    // Set defaults
    $defaults = [
        'rows' => [],
        'class' => '',
        'columns' => 2
    ];
    
    $config = array_merge($defaults, $config);
    $rows = is_array($config['rows']) ? $config['rows'] : [];
    $extra_class = form_page_escape($config['class']);
    
    if (empty($rows)) {
        return;
    }
    
    ?>
<!-- Info Table (Desktop) -->
<table class="info-table <?php echo $extra_class; ?>">
    <tbody>
        <?php foreach ($rows as $row): ?>
        <?php
            $label = isset($row['label']) ? form_page_escape($row['label']) : '';
            $value = isset($row['value']) ? form_page_escape($row['value']) : '';
            $row_class = isset($row['class']) ? form_page_escape($row['class']) : '';
        ?>
        <tr>
            <th><?php echo $label; ?></th>
            <td class="<?php echo $row_class; ?>"><?php echo $value; ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Info Card (Mobile) -->
<div class="info-card <?php echo $extra_class; ?>">
    <?php foreach ($rows as $row): ?>
    <?php
        $label = isset($row['label']) ? form_page_escape($row['label']) : '';
        $value = isset($row['value']) ? form_page_escape($row['value']) : '';
        $row_class = isset($row['class']) ? form_page_escape($row['class']) : '';
    ?>
    <div class="info-row">
        <span class="info-label"><?php echo $label; ?></span>
        <span class="info-value <?php echo $row_class; ?>"><?php echo $value; ?></span>
    </div>
    <?php endforeach; ?>
</div>
    <?php
}

// ============================================
// Modal Function
// ============================================

/**
 * Render modal dialog
 * 
 * Renders a modal dialog with configurable content and buttons.
 * 
 * @param array $config Configuration array
 *   - id: (required) Unique modal ID
 *   - title: (required) Modal header title
 *   - body: (required) Modal body HTML content
 *   - type: (optional) Modal type (default, success, error, warning)
 *   - show_close: (optional) Show X close button (default: true)
 *   - buttons: (optional) Array of footer buttons
 *   - auto_redirect: (optional) Auto-redirect config with 'url', 'delay', 'show_countdown'
 *   - class: (optional) Additional modal classes
 * @return void Outputs HTML directly
 */
function render_modal($config) {
    // Set defaults
    $defaults = [
        'id' => 'modal-' . uniqid(),
        'title' => 'Modal',
        'body' => '',
        'type' => 'default',
        'show_close' => true,
        'buttons' => [],
        'auto_redirect' => null,
        'class' => ''
    ];
    
    $config = array_merge($defaults, $config);
    
    $id = form_page_escape($config['id']);
    $title = form_page_escape($config['title']);
    $body = $config['body']; // Allow HTML content
    $type = form_page_escape($config['type']);
    $show_close = (bool)$config['show_close'];
    $buttons = is_array($config['buttons']) ? $config['buttons'] : [];
    $auto_redirect = $config['auto_redirect'];
    $extra_class = form_page_escape($config['class']);
    
    // Determine modal type class
    $type_class = '';
    if (in_array($type, ['success', 'error', 'warning'])) {
        $type_class = 'modal-' . $type;
    }
    
    // Icon based on type
    $icon = '';
    switch ($type) {
        case 'success':
            $icon = '&#10004;'; // Checkmark
            break;
        case 'error':
            $icon = '&#10006;'; // X mark
            break;
        case 'warning':
            $icon = '&#9888;'; // Warning triangle
            break;
    }
    
    ?>
<!-- Modal: <?php echo $id; ?> -->
<div class="modal-overlay <?php echo $type_class; ?> <?php echo $extra_class; ?>" id="<?php echo $id; ?>" role="dialog" aria-modal="true" aria-labelledby="<?php echo $id; ?>-title">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="<?php echo $id; ?>-title"><?php echo $title; ?></h2>
            <?php if ($show_close): ?>
            <button type="button" class="modal-close" aria-label="Close modal" data-modal-close="<?php echo $id; ?>">&times;</button>
            <?php endif; ?>
        </div>
        <div class="modal-body">
            <?php if (!empty($icon)): ?>
            <div class="modal-icon"><?php echo $icon; ?></div>
            <?php endif; ?>
            
            <?php echo $body; ?>
            
            <?php if ($auto_redirect && isset($auto_redirect['show_countdown']) && $auto_redirect['show_countdown']): ?>
            <?php $delay = isset($auto_redirect['delay']) ? (int)$auto_redirect['delay'] : 3; ?>
            <div class="modal-countdown">
                Tự động chuyển hướng sau <span class="modal-countdown-number" id="<?php echo $id; ?>-countdown"><?php echo $delay; ?></span> giây...
            </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($buttons)): ?>
        <div class="modal-footer">
            <?php foreach ($buttons as $button): ?>
            <?php
                $btn_text = isset($button['text']) ? form_page_escape($button['text']) : 'Button';
                $btn_class = isset($button['class']) ? form_page_escape($button['class']) : 'btn-secondary';
                $btn_id = isset($button['id']) ? form_page_escape($button['id']) : '';
                $btn_dismiss = isset($button['data-dismiss']) && $button['data-dismiss'] === 'modal';
                $btn_attrs = '';
                
                if (!empty($btn_id)) {
                    $btn_attrs .= ' id="' . $btn_id . '"';
                }
                if ($btn_dismiss) {
                    $btn_attrs .= ' data-modal-close="' . $id . '"';
                }
            ?>
            <button type="button" class="btn <?php echo $btn_class; ?>"<?php echo $btn_attrs; ?>>
                <?php echo $btn_text; ?>
            </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($auto_redirect): ?>
<?php
    $redirect_url = isset($auto_redirect['url']) ? form_page_escape($auto_redirect['url']) : 'index.php';
    $delay = isset($auto_redirect['delay']) ? (int)$auto_redirect['delay'] : 3;
    $show_countdown = isset($auto_redirect['show_countdown']) ? (bool)$auto_redirect['show_countdown'] : true;
?>
<script>
(function() {
    var modal = document.getElementById('<?php echo $id; ?>');
    var countdownEl = document.getElementById('<?php echo $id; ?>-countdown');
    var redirectUrl = '<?php echo $redirect_url; ?>';
    var delay = <?php echo $delay; ?>;
    var countdownInterval = null;
    var redirectTimeout = null;
    
    function startCountdown() {
        var remaining = delay;
        if (countdownEl) {
            countdownInterval = setInterval(function() {
                remaining--;
                if (countdownEl) countdownEl.textContent = remaining;
                if (remaining <= 0) {
                    clearInterval(countdownInterval);
                }
            }, 1000);
        }
        redirectTimeout = setTimeout(function() {
            window.location.href = redirectUrl;
        }, delay * 1000);
    }
    
    function stopRedirect() {
        if (countdownInterval) clearInterval(countdownInterval);
        if (redirectTimeout) clearTimeout(redirectTimeout);
        if (modal) modal.classList.remove('is-open');
    }
    
    // Handle stay button
    var stayBtn = document.getElementById('stayHere');
    if (stayBtn) {
        stayBtn.addEventListener('click', stopRedirect);
    }
    
    // Handle redirect now button
    var redirectBtn = document.getElementById('redirectNow');
    if (redirectBtn) {
        redirectBtn.addEventListener('click', function() {
            window.location.href = redirectUrl;
        });
    }
    
    // Start countdown when modal is shown
    if (modal && modal.classList.contains('is-open')) {
        startCountdown();
    }
    
    // Observe modal for class changes
    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'class') {
                if (modal.classList.contains('is-open')) {
                    startCountdown();
                } else {
                    stopRedirect();
                }
            }
        });
    });
    
    if (modal) {
        observer.observe(modal, { attributes: true });
    }
})();
</script>
<?php endif; ?>
    <?php
}

// ============================================
// Form Note Function
// ============================================

/**
 * Render form note/info box
 * 
 * Renders an informational note box with optional list items.
 * 
 * @param array $config Configuration array
 *   - type: (required) Note type (info, warning, tip)
 *   - title: (optional) Note title
 *   - items: (required) Array of note items
 *   - footer: (optional) Footer text
 * @return void Outputs HTML directly
 */
function render_form_note($config) {
    // Set defaults
    $defaults = [
        'type' => 'info',
        'title' => '',
        'items' => [],
        'footer' => ''
    ];
    
    $config = array_merge($defaults, $config);
    
    $type = form_page_escape($config['type']);
    $title = form_page_escape($config['title']);
    $items = is_array($config['items']) ? $config['items'] : [];
    $footer = form_page_escape($config['footer']);
    
    $type_class = 'form-note-' . $type;
    
    ?>
<div class="form-note <?php echo $type_class; ?>">
    <?php if (!empty($title)): ?>
    <div class="form-note-title"><?php echo $title; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($items)): ?>
    <ul class="form-note-list">
        <?php foreach ($items as $item): ?>
        <li><?php echo form_page_escape($item); ?></li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
    
    <?php if (!empty($footer)): ?>
    <div class="form-note-footer"><?php echo $footer; ?></div>
    <?php endif; ?>
</div>
    <?php
}

// ============================================
// Form Input Component Include
// ============================================

// Include form input component if not already included
$form_input_path = __DIR__ . '/form-input.php';
if (file_exists($form_input_path) && !function_exists('render_form_input')) {
    require_once $form_input_path;
}

// Include modal component if not already included (for backward compatibility)
$modal_path = __DIR__ . '/modal.php';
if (file_exists($modal_path) && !function_exists('render_modal_standalone')) {
    require_once $modal_path;
}
?>
