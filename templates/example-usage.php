<?php
// Example usage of layout and form templates

// Set page variables for layout
$page_title = 'Example Page';
$page_styles = ['./src/example.css']; // Optional
$page_scripts = ['./js/example.js']; // Optional

// Include the layout template
include 'layout.php';

// The content will be included in the layout where $content_file is specified
// For this example, we'll define the content inline

$content_file = null; // We'll output content directly below

// If you want to use a separate content file, set $content_file = 'path/to/content.php';
?>

<?php ob_start(); // Start output buffering for content ?>
<div class="example-content">
    <h2>Example Form Usage</h2>
    <p>This demonstrates how to use the form template.</p>

    <?php
    // Define form fields
    $form_title = 'Create New Ticket';
    $form_action = 'process-ticket.php';
    $form_method = 'POST';
    $form_fields = [
        [
            'type' => 'text',
            'name' => 'subject',
            'label' => 'Ticket Subject',
            'required' => true,
            'placeholder' => 'Brief description of the issue'
        ],
        [
            'type' => 'textarea',
            'name' => 'description',
            'label' => 'Detailed Description',
            'required' => true,
            'placeholder' => 'Please provide detailed information about the issue',
            'rows' => 5
        ],
        [
            'type' => 'select',
            'name' => 'priority',
            'label' => 'Priority Level',
            'required' => true,
            'options' => [
                'Low' => 'Low',
                'Medium' => 'Medium',
                'High' => 'High'
            ]
        ],
        [
            'type' => 'select',
            'name' => 'category',
            'label' => 'Category',
            'required' => true,
            'options' => [
                'Hardware' => 'Hardware',
                'Software' => 'Software',
                'Network' => 'Network',
                'Other' => 'Other'
            ]
        ],
        [
            'type' => 'file',
            'name' => 'attachment',
            'label' => 'Attachment (optional)',
            'accept' => '.jpg,.jpeg,.png,.pdf,.doc,.docx',
            'help' => 'Supported formats: JPG, PNG, PDF, DOC, DOCX. Max size: 5MB'
        ]
    ];
    $submit_button_text = 'Submit Ticket';
    $cancel_url = 'dashboard.php';

    // Include the form template
    include 'form-template.php';
    ?>

    <hr>

    <h3>Form Template Features</h3>
    <ul>
        <li>Consistent styling across all forms</li>
        <li>Automatic validation indicators</li>
        <li>Responsive design for mobile devices</li>
        <li>Support for various input types: text, textarea, select, file, checkbox, radio</li>
        <li>Help text for fields</li>
        <li>Custom submit and cancel buttons</li>
    </ul>

    <h3>Layout Template Features</h3>
    <ul>
        <li>Automatic sidebar detection based on user role</li>
        <li>Responsive header with user info</li>
        <li>Collapsible sidebar for IT staff</li>
        <li>Consistent navigation across pages</li>
        <li>Easy inclusion of page-specific styles and scripts</li>
    </ul>
</div>

<style>
.example-content {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.example-content h2 {
    color: #333;
    border-bottom: 2px solid #667eea;
    padding-bottom: 10px;
}

.example-content h3 {
    color: #555;
    margin-top: 30px;
}

.example-content ul {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #667eea;
}

.example-content li {
    margin-bottom: 8px;
}
</style>

<?php
$content = ob_get_clean();

// Now include the layout with the content
// In a real implementation, you'd set $content_file to point to this file
echo $content;
?>
