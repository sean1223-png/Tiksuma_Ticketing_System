<?php
// Form Template
// This template provides a consistent layout for forms

// Required variables:
// $form_title - The title of the form
// $form_action - The action URL for the form
// $form_method - POST or GET (default POST)
// $form_fields - Array of field definitions
// $submit_button_text - Text for submit button (default 'Submit')
// $cancel_url - URL to redirect on cancel (optional)

// Example usage:
/*
$form_title = 'Add New Ticket';
$form_action = 'add-ticket.php';
$form_method = 'POST';
$form_fields = [
    [
        'type' => 'text',
        'name' => 'subject',
        'label' => 'Subject',
        'required' => true,
        'placeholder' => 'Enter ticket subject'
    ],
    [
        'type' => 'textarea',
        'name' => 'description',
        'label' => 'Description',
        'required' => true,
        'placeholder' => 'Describe the issue'
    ],
    [
        'type' => 'select',
        'name' => 'priority',
        'label' => 'Priority',
        'options' => ['Low', 'Medium', 'High'],
        'required' => true
    ]
];
$submit_button_text = 'Create Ticket';
$cancel_url = 'dashboard.php';
include 'templates/form-template.php';
*/
?>

<div class="form-container">
    <div class="form-card">
        <div class="form-header">
            <h2><?php echo htmlspecialchars($form_title ?? 'Form'); ?></h2>
        </div>

        <form action="<?php echo htmlspecialchars($form_action ?? ''); ?>" method="<?php echo htmlspecialchars($form_method ?? 'POST'); ?>" enctype="multipart/form-data">
            <div class="form-body">
                <?php if (isset($form_fields) && is_array($form_fields)): ?>
                    <?php foreach ($form_fields as $field): ?>
                        <div class="form-group">
                            <label for="<?php echo htmlspecialchars($field['name'] ?? ''); ?>" class="form-label">
                                <?php echo htmlspecialchars($field['label'] ?? ''); ?>
                                <?php if (isset($field['required']) && $field['required']): ?>
                                    <span class="required">*</span>
                                <?php endif; ?>
                            </label>

                            <?php if ($field['type'] === 'textarea'): ?>
                                <textarea
                                    name="<?php echo htmlspecialchars($field['name'] ?? ''); ?>"
                                    id="<?php echo htmlspecialchars($field['name'] ?? ''); ?>"
                                    class="form-control"
                                    rows="<?php echo htmlspecialchars($field['rows'] ?? '4'); ?>"
                                    placeholder="<?php echo htmlspecialchars($field['placeholder'] ?? ''); ?>"
                                    <?php if (isset($field['required']) && $field['required']): ?>required<?php endif; ?>
                                ><?php echo htmlspecialchars($field['value'] ?? ''); ?></textarea>

                            <?php elseif ($field['type'] === 'select'): ?>
                                <select
                                    name="<?php echo htmlspecialchars($field['name'] ?? ''); ?>"
                                    id="<?php echo htmlspecialchars($field['name'] ?? ''); ?>"
                                    class="form-control"
                                    <?php if (isset($field['required']) && $field['required']): ?>required<?php endif; ?>
                                >
                                    <option value="">Select <?php echo htmlspecialchars($field['label'] ?? ''); ?></option>
                                    <?php if (isset($field['options']) && is_array($field['options'])): ?>
                                        <?php foreach ($field['options'] as $value => $label): ?>
                                            <option value="<?php echo htmlspecialchars(is_numeric($value) ? $label : $value); ?>"
                                                    <?php if (isset($field['value']) && $field['value'] == (is_numeric($value) ? $label : $value)): ?>selected<?php endif; ?>>
                                                <?php echo htmlspecialchars($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>

                            <?php elseif ($field['type'] === 'file'): ?>
                                <input
                                    type="file"
                                    name="<?php echo htmlspecialchars($field['name'] ?? ''); ?>"
                                    id="<?php echo htmlspecialchars($field['name'] ?? ''); ?>"
                                    class="form-control"
                                    accept="<?php echo htmlspecialchars($field['accept'] ?? ''); ?>"
                                    <?php if (isset($field['required']) && $field['required']): ?>required<?php endif; ?>
                                />

                            <?php elseif ($field['type'] === 'checkbox'): ?>
                                <div class="checkbox-group">
                                    <?php if (isset($field['options']) && is_array($field['options'])): ?>
                                        <?php foreach ($field['options'] as $value => $label): ?>
                                            <label class="checkbox-label">
                                                <input
                                                    type="checkbox"
                                                    name="<?php echo htmlspecialchars($field['name'] ?? ''); ?>[]"
                                                    value="<?php echo htmlspecialchars(is_numeric($value) ? $label : $value); ?>"
                                                    <?php if (isset($field['value']) && in_array((is_numeric($value) ? $label : $value), (array)$field['value'])): ?>checked<?php endif; ?>
                                                />
                                                <?php echo htmlspecialchars($label); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                            <?php elseif ($field['type'] === 'radio'): ?>
                                <div class="radio-group">
                                    <?php if (isset($field['options']) && is_array($field['options'])): ?>
                                        <?php foreach ($field['options'] as $value => $label): ?>
                                            <label class="radio-label">
                                                <input
                                                    type="radio"
                                                    name="<?php echo htmlspecialchars($field['name'] ?? ''); ?>"
                                                    value="<?php echo htmlspecialchars(is_numeric($value) ? $label : $value); ?>"
                                                    <?php if (isset($field['value']) && $field['value'] == (is_numeric($value) ? $label : $value)): ?>checked<?php endif; ?>
                                                    <?php if (isset($field['required']) && $field['required']): ?>required<?php endif; ?>
                                                />
                                                <?php echo htmlspecialchars($label); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                            <?php else: ?>
                                <input
                                    type="<?php echo htmlspecialchars($field['type'] ?? 'text'); ?>"
                                    name="<?php echo htmlspecialchars($field['name'] ?? ''); ?>"
                                    id="<?php echo htmlspecialchars($field['name'] ?? ''); ?>"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($field['value'] ?? ''); ?>"
                                    placeholder="<?php echo htmlspecialchars($field['placeholder'] ?? ''); ?>"
                                    <?php if (isset($field['required']) && $field['required']): ?>required<?php endif; ?>
                                    <?php if (isset($field['min'])): ?>min="<?php echo htmlspecialchars($field['min']); ?>"<?php endif; ?>
                                    <?php if (isset($field['max'])): ?>max="<?php echo htmlspecialchars($field['max']); ?>"<?php endif; ?>
                                />
                            <?php endif; ?>

                            <?php if (isset($field['help'])): ?>
                                <small class="form-help"><?php echo htmlspecialchars($field['help']); ?></small>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="form-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo htmlspecialchars($submit_button_text ?? 'Submit'); ?>
                </button>

                <?php if (isset($cancel_url)): ?>
                    <a href="<?php echo htmlspecialchars($cancel_url); ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<style>
.form-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.form-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.form-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    text-align: center;
}

.form-header h2 {
    margin: 0;
    font-size: 24px;
    font-weight: 600;
}

.form-body {
    padding: 30px;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.required {
    color: #e74c3c;
}

.form-control {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e1e8ed;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.3s ease;
    box-sizing: border-box;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

textarea.form-control {
    resize: vertical;
    min-height: 100px;
}

select.form-control {
    cursor: pointer;
}

.checkbox-group, .radio-group {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.checkbox-label, .radio-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    padding: 8px 12px;
    border-radius: 6px;
    transition: background-color 0.2s ease;
}

.checkbox-label:hover, .radio-label:hover {
    background-color: #f8f9fa;
}

.checkbox-label input, .radio-label input {
    margin: 0;
}

.form-help {
    display: block;
    margin-top: 4px;
    color: #6c757d;
    font-size: 14px;
}

.form-footer {
    padding: 20px 30px;
    background-color: #f8f9fa;
    border-top: 1px solid #e9ecef;
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .form-container {
        padding: 10px;
    }

    .form-body {
        padding: 20px;
    }

    .form-footer {
        padding: 15px 20px;
        flex-direction: column;
    }

    .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>
