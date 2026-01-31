<?php
/**
 * Modal Component (Standalone)
 * 
 * Standalone modal dialog component for use outside form-page context.
 * For use within form-page, use the render_modal() from form-page.php.
 * 
 * Usage:
 * require_once 'components/modal.php';
 * 
 * render_modal_standalone([
 *     'id' => 'myModal',
 *     'title' => 'Modal Title',
 *     'body' => '<p>Modal content here</p>',
 *     'type' => 'success'
 * ]);
 * 
 * @version 1.0.0
 */

// Avoid redefinition if included multiple times
if (!function_exists('render_modal_standalone')) {

/**
 * Safely escape output for HTML context
 */
if (!function_exists('modal_escape')) {
    function modal_escape($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Render standalone modal dialog
 * 
 * Renders a modal dialog that can be used outside of the form-page component.
 * Includes its own wrapper class for styling isolation.
 * 
 * @param array $config Configuration array
 *   - id: (required) Unique modal ID
 *   - title: (required) Modal header title
 *   - body: (required) Modal body HTML content
 *   - type: (optional) Modal type (default, success, error, warning)
 *   - show_close: (optional) Show X close button (default: true)
 *   - buttons: (optional) Array of footer buttons
 *   - auto_redirect: (optional) Auto-redirect config
 *   - class: (optional) Additional modal classes
 *   - auto_open: (optional) Auto-open the modal on page load (default: false)
 * @return void Outputs HTML directly
 */
function render_modal_standalone($config) {
    // Set defaults
    $defaults = [
        'id' => 'modal-' . uniqid(),
        'title' => 'Modal',
        'body' => '',
        'type' => 'default',
        'show_close' => true,
        'buttons' => [],
        'auto_redirect' => null,
        'class' => '',
        'auto_open' => false
    ];
    
    $config = array_merge($defaults, $config);
    
    $id = modal_escape($config['id']);
    $title = modal_escape($config['title']);
    $body = $config['body']; // Allow HTML content
    $type = modal_escape($config['type']);
    $show_close = (bool)$config['show_close'];
    $buttons = is_array($config['buttons']) ? $config['buttons'] : [];
    $auto_redirect = $config['auto_redirect'];
    $extra_class = modal_escape($config['class']);
    $auto_open = (bool)$config['auto_open'];
    
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
    
    // Auto-open class
    $open_class = $auto_open ? ' is-open' : '';
    
    ?>
<!-- Modal: <?php echo $id; ?> (Standalone) -->
<div class="form-page-component">
<div class="modal-overlay <?php echo $type_class; ?> <?php echo $extra_class; ?><?php echo $open_class; ?>" id="<?php echo $id; ?>" role="dialog" aria-modal="true" aria-labelledby="<?php echo $id; ?>-title">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="<?php echo $id; ?>-title"><?php echo $title; ?></h2>
            <?php if ($show_close): ?>
            <button type="button" class="modal-close" aria-label="Close modal" onclick="closeModal('<?php echo $id; ?>')">&times;</button>
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
                $btn_text = isset($button['text']) ? modal_escape($button['text']) : 'Button';
                $btn_class = isset($button['class']) ? modal_escape($button['class']) : 'btn-secondary';
                $btn_id = isset($button['id']) ? modal_escape($button['id']) : '';
                $btn_onclick = isset($button['onclick']) ? modal_escape($button['onclick']) : '';
                $btn_dismiss = isset($button['data-dismiss']) && $button['data-dismiss'] === 'modal';
                $btn_attrs = '';
                
                if (!empty($btn_id)) {
                    $btn_attrs .= ' id="' . $btn_id . '"';
                }
                if ($btn_dismiss) {
                    $btn_attrs .= ' onclick="closeModal(\'' . $id . '\')"';
                } elseif (!empty($btn_onclick)) {
                    $btn_attrs .= ' onclick="' . $btn_onclick . '"';
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
</div>

<script>
// Modal utility functions
function openModal(modalId) {
    var modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    var modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('is-open');
        document.body.style.overflow = '';
    }
}

// Close modal on overlay click
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('is-open');
        document.body.style.overflow = '';
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        var openModal = document.querySelector('.modal-overlay.is-open');
        if (openModal) {
            openModal.classList.remove('is-open');
            document.body.style.overflow = '';
        }
    }
});
</script>

<?php if ($auto_redirect): ?>
<?php
    $redirect_url = isset($auto_redirect['url']) ? modal_escape($auto_redirect['url']) : 'index.php';
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
        closeModal('<?php echo $id; ?>');
    }
    
    // Handle stay button (common pattern)
    var stayBtn = document.getElementById('stayHere');
    if (stayBtn) {
        stayBtn.addEventListener('click', stopRedirect);
    }
    
    // Handle redirect now button (common pattern)
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
                    if (countdownInterval) clearInterval(countdownInterval);
                    if (redirectTimeout) clearTimeout(redirectTimeout);
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

/**
 * Helper function to show a simple alert modal
 * 
 * @param string $title Modal title
 * @param string $message Modal message
 * @param string $type Modal type (default, success, error, warning)
 * @param string $id Optional modal ID
 * @return void
 */
function render_alert_modal($title, $message, $type = 'default', $id = null) {
    render_modal_standalone([
        'id' => $id ?: 'alertModal',
        'title' => $title,
        'body' => '<p>' . modal_escape($message) . '</p>',
        'type' => $type,
        'auto_open' => true,
        'buttons' => [
            ['text' => 'OK', 'class' => 'btn-primary', 'data-dismiss' => 'modal']
        ]
    ]);
}

/**
 * Helper function to show a confirm modal
 * 
 * @param string $title Modal title
 * @param string $message Modal message
 * @param string $confirm_text Confirm button text
 * @param string $confirm_action JavaScript action for confirm
 * @param string $id Optional modal ID
 * @return void
 */
function render_confirm_modal($title, $message, $confirm_text = 'Confirm', $confirm_action = '', $id = null) {
    render_modal_standalone([
        'id' => $id ?: 'confirmModal',
        'title' => $title,
        'body' => '<p>' . modal_escape($message) . '</p>',
        'type' => 'warning',
        'buttons' => [
            ['text' => 'Hủy', 'class' => 'btn-secondary', 'data-dismiss' => 'modal'],
            ['text' => $confirm_text, 'class' => 'btn-danger', 'onclick' => $confirm_action]
        ]
    ]);
}

} // End if function_exists check
?>
