<?php
if (!function_exists('render_form_input')) {

if (!function_exists('form_page_escape')) {
    function form_page_escape($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

function render_form_input($config) {
    $defaults = [
        'type' => 'text',
        'name' => '',
        'id' => null,
        'label' => '',
        'value' => '',
        'placeholder' => '',
        'required' => false,
        'disabled' => false,
        'readonly' => false,
        'min' => null,
        'max' => null,
        'step' => null,
        'hint' => '',
        'error' => '',
        'class' => '',
        'wrapper_class' => '',
        'attributes' => []
    ];

    $config = array_merge($defaults, $config);

    if (empty($config['name'])) {
        trigger_error('Form input: Required "name" parameter is missing', E_USER_WARNING);
        return;
    }

    $type = form_page_escape($config['type']);
    $name = form_page_escape($config['name']);
    $id = $config['id'] ? form_page_escape($config['id']) : $name;
    $label = form_page_escape($config['label']);
    $value = form_page_escape($config['value']);
    $placeholder = form_page_escape($config['placeholder']);
    $required = (bool)$config['required'];
    $disabled = (bool)$config['disabled'];
    $readonly = (bool)$config['readonly'];
    $min = $config['min'];
    $max = $config['max'];
    $step = $config['step'];
    $hint = form_page_escape($config['hint']);
    $error = form_page_escape($config['error']);
    $extra_class = form_page_escape($config['class']);
    $wrapper_class = form_page_escape($config['wrapper_class']);
    $attributes = is_array($config['attributes']) ? $config['attributes'] : [];

    $input_classes = ['form-input'];
    if (!empty($extra_class)) {
        $input_classes[] = $extra_class;
    }
    if (!empty($error)) {
        $input_classes[] = 'has-error';
    }
    $input_class_str = implode(' ', $input_classes);

    $wrapper_classes = ['form-group'];
    if (!empty($wrapper_class)) {
        $wrapper_classes[] = $wrapper_class;
    }
    $wrapper_class_str = implode(' ', $wrapper_classes);

    $label_classes = ['form-label'];
    if ($required) {
        $label_classes[] = 'form-label-required';
    }
    $label_class_str = implode(' ', $label_classes);

    $attr_str = '';
    foreach ($attributes as $attr_name => $attr_value) {
        $attr_str .= ' ' . form_page_escape($attr_name) . '="' . form_page_escape($attr_value) . '"';
    }

    if ($type === 'hidden') {
        ?>
<input type="hidden" name="<?php echo $name; ?>" id="<?php echo $id; ?>" value="<?php echo $value; ?>"<?php echo $attr_str; ?>>
        <?php
        return;
    }

    $is_date = strpos($extra_class, 'date-picker') !== false || $type === 'date';

    ?>
<div class="<?php echo $wrapper_class_str; ?>">
    <?php if (!empty($label)): ?>
    <label for="<?php echo $id; ?>" class="<?php echo $label_class_str; ?>"><?php echo $label; ?></label>
    <?php endif; ?>

    <?php if ($is_date && $type !== 'date'): ?>
    <div class="date-input-container">
    <?php endif; ?>

    <input
        type="<?php echo $type; ?>"
        name="<?php echo $name; ?>"
        id="<?php echo $id; ?>"
        value="<?php echo $value; ?>"
        class="<?php echo $input_class_str; ?>"
        <?php if (!empty($placeholder)): ?>placeholder="<?php echo $placeholder; ?>"<?php endif; ?>
        <?php if ($required): ?>required<?php endif; ?>
        <?php if ($disabled): ?>disabled<?php endif; ?>
        <?php if ($readonly): ?>readonly<?php endif; ?>
        <?php if ($min !== null): ?>min="<?php echo form_page_escape($min); ?>"<?php endif; ?>
        <?php if ($max !== null): ?>max="<?php echo form_page_escape($max); ?>"<?php endif; ?>
        <?php if ($step !== null): ?>step="<?php echo form_page_escape($step); ?>"<?php endif; ?>
        <?php echo $attr_str; ?>
        aria-describedby="<?php echo $id; ?>-hint <?php echo $id; ?>-error"
    >

    <?php if ($is_date && $type !== 'date'): ?>
        <span class="date-input-icon">📅</span>
    </div>
    <?php endif; ?>

    <?php if (!empty($hint) && empty($error)): ?>
    <span class="form-hint" id="<?php echo $id; ?>-hint"><?php echo $hint; ?></span>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
    <span class="form-error" id="<?php echo $id; ?>-error" role="alert"><?php echo $error; ?></span>
    <?php endif; ?>
</div>
    <?php
}

function render_form_select($config) {
    $defaults = [
        'name' => '',
        'id' => null,
        'label' => '',
        'options' => [],
        'value' => '',
        'placeholder' => '',
        'required' => false,
        'disabled' => false,
        'hint' => '',
        'error' => '',
        'class' => '',
        'wrapper_class' => ''
    ];

    $config = array_merge($defaults, $config);

    if (empty($config['name'])) {
        trigger_error('Form select: Required "name" parameter is missing', E_USER_WARNING);
        return;
    }

    $name = form_page_escape($config['name']);
    $id = $config['id'] ? form_page_escape($config['id']) : $name;
    $label = form_page_escape($config['label']);
    $options = is_array($config['options']) ? $config['options'] : [];
    $current_value = $config['value'];
    $placeholder = form_page_escape($config['placeholder']);
    $required = (bool)$config['required'];
    $disabled = (bool)$config['disabled'];
    $hint = form_page_escape($config['hint']);
    $error = form_page_escape($config['error']);
    $extra_class = form_page_escape($config['class']);
    $wrapper_class = form_page_escape($config['wrapper_class']);

    $select_classes = ['form-select'];
    if (!empty($extra_class)) {
        $select_classes[] = $extra_class;
    }
    if (!empty($error)) {
        $select_classes[] = 'has-error';
    }
    $select_class_str = implode(' ', $select_classes);

    $wrapper_classes = ['form-group'];
    if (!empty($wrapper_class)) {
        $wrapper_classes[] = $wrapper_class;
    }
    $wrapper_class_str = implode(' ', $wrapper_classes);

    $label_classes = ['form-label'];
    if ($required) {
        $label_classes[] = 'form-label-required';
    }
    $label_class_str = implode(' ', $label_classes);

    ?>
<div class="<?php echo $wrapper_class_str; ?>">
    <?php if (!empty($label)): ?>
    <label for="<?php echo $id; ?>" class="<?php echo $label_class_str; ?>"><?php echo $label; ?></label>
    <?php endif; ?>

    <select
        name="<?php echo $name; ?>"
        id="<?php echo $id; ?>"
        class="<?php echo $select_class_str; ?>"
        <?php if ($required): ?>required<?php endif; ?>
        <?php if ($disabled): ?>disabled<?php endif; ?>
        aria-describedby="<?php echo $id; ?>-hint <?php echo $id; ?>-error"
    >
        <?php if (!empty($placeholder)): ?>
        <option value=""><?php echo $placeholder; ?></option>
        <?php endif; ?>

        <?php foreach ($options as $opt_value => $opt_data): ?>
        <?php
            if (is_array($opt_data)) {
                $opt_label = isset($opt_data['label']) ? form_page_escape($opt_data['label']) : '';
                $opt_val = isset($opt_data['value']) ? $opt_data['value'] : $opt_value;
                $opt_disabled = isset($opt_data['disabled']) && $opt_data['disabled'];
            } else {
                $opt_label = form_page_escape($opt_data);
                $opt_val = $opt_value;
                $opt_disabled = false;
            }
            $selected = ($opt_val == $current_value) ? 'selected' : '';
        ?>
        <option value="<?php echo form_page_escape($opt_val); ?>" <?php echo $selected; ?> <?php if ($opt_disabled): ?>disabled<?php endif; ?>>
            <?php echo $opt_label; ?>
        </option>
        <?php endforeach; ?>
    </select>

    <?php if (!empty($hint) && empty($error)): ?>
    <span class="form-hint" id="<?php echo $id; ?>-hint"><?php echo $hint; ?></span>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
    <span class="form-error" id="<?php echo $id; ?>-error" role="alert"><?php echo $error; ?></span>
    <?php endif; ?>
</div>
    <?php
}

function render_form_textarea($config) {
    $defaults = [
        'name' => '',
        'id' => null,
        'label' => '',
        'value' => '',
        'placeholder' => '',
        'required' => false,
        'disabled' => false,
        'readonly' => false,
        'rows' => 4,
        'hint' => '',
        'error' => '',
        'class' => '',
        'wrapper_class' => ''
    ];

    $config = array_merge($defaults, $config);

    if (empty($config['name'])) {
        trigger_error('Form textarea: Required "name" parameter is missing', E_USER_WARNING);
        return;
    }

    $name = form_page_escape($config['name']);
    $id = $config['id'] ? form_page_escape($config['id']) : $name;
    $label = form_page_escape($config['label']);
    $value = form_page_escape($config['value']);
    $placeholder = form_page_escape($config['placeholder']);
    $required = (bool)$config['required'];
    $disabled = (bool)$config['disabled'];
    $readonly = (bool)$config['readonly'];
    $rows = (int)$config['rows'];
    $hint = form_page_escape($config['hint']);
    $error = form_page_escape($config['error']);
    $extra_class = form_page_escape($config['class']);
    $wrapper_class = form_page_escape($config['wrapper_class']);

    $textarea_classes = ['form-textarea'];
    if (!empty($extra_class)) {
        $textarea_classes[] = $extra_class;
    }
    if (!empty($error)) {
        $textarea_classes[] = 'has-error';
    }
    $textarea_class_str = implode(' ', $textarea_classes);

    $wrapper_classes = ['form-group'];
    if (!empty($wrapper_class)) {
        $wrapper_classes[] = $wrapper_class;
    }
    $wrapper_class_str = implode(' ', $wrapper_classes);

    $label_classes = ['form-label'];
    if ($required) {
        $label_classes[] = 'form-label-required';
    }
    $label_class_str = implode(' ', $label_classes);

    ?>
<div class="<?php echo $wrapper_class_str; ?>">
    <?php if (!empty($label)): ?>
    <label for="<?php echo $id; ?>" class="<?php echo $label_class_str; ?>"><?php echo $label; ?></label>
    <?php endif; ?>

    <textarea
        name="<?php echo $name; ?>"
        id="<?php echo $id; ?>"
        class="<?php echo $textarea_class_str; ?>"
        rows="<?php echo $rows; ?>"
        <?php if (!empty($placeholder)): ?>placeholder="<?php echo $placeholder; ?>"<?php endif; ?>
        <?php if ($required): ?>required<?php endif; ?>
        <?php if ($disabled): ?>disabled<?php endif; ?>
        <?php if ($readonly): ?>readonly<?php endif; ?>
        aria-describedby="<?php echo $id; ?>-hint <?php echo $id; ?>-error"
    ><?php echo $value; ?></textarea>

    <?php if (!empty($hint) && empty($error)): ?>
    <span class="form-hint" id="<?php echo $id; ?>-hint"><?php echo $hint; ?></span>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
    <span class="form-error" id="<?php echo $id; ?>-error" role="alert"><?php echo $error; ?></span>
    <?php endif; ?>
</div>
    <?php
}

}
?>
