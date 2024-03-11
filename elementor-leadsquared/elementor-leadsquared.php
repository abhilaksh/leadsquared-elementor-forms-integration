<?php
/**
 * Plugin Name: LeadSquared Integration for Elementor Forms
 * Description: Send Elementor Form data to LeadSquared.
 * Version: 0.1
 * Author: Abhilaksh Lalwani
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Include the settings class file
require_once __DIR__ . '/includes/class-settings.php';

/**
 * Registers the LeadSquared form action with Elementor Pro upon initialization.
 */
function register_leadsquared_form_action( $form_actions_registrar ) {
    // Ensure the class exists to prevent errors if Elementor Pro is not active
    if ( class_exists( '\ElementorPro\Modules\Forms\Classes\Action_Base' ) ) {
        require_once __DIR__ . '/includes/class-form-action-leadsquared.php';
        $form_actions_registrar->register( new Class_Form_Action_LeadSquared() );
    }
}
add_action( 'elementor_pro/forms/actions/register', 'register_leadsquared_form_action' );

/**
 * Enqueues custom JavaScript for the Elementor editor.
 */
function enqueue_custom_elementor_editor_script() {
    wp_enqueue_script(
        'leadsquared-elementor-extension-dynamic-fields', // Handle for the script.
        plugins_url( '/js/dynamic-fields.js', __FILE__ ), // Path to the dynamic-fields.js file.
        array( 'jquery' ), // Dependencies, Elementor's editor uses jQuery.
        '1.0.0', // Version number for the script.
        true // Specify whether to enqueue the script in the footer.
    );
}
add_action( 'elementor/editor/before_enqueue_scripts', 'enqueue_custom_elementor_editor_script' );


function my_plugin_admin_scripts($hook) {
    wp_enqueue_script('my-plugin-admin-script', plugins_url('/js/admin-script.js', __FILE__), array('jquery'), '1.0.0', true);
    wp_localize_script('my-plugin-admin-script', 'myPluginAjax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'security' => wp_create_nonce('retrieve_schema_nonce'),
    ));
}
add_action('admin_enqueue_scripts', 'my_plugin_admin_scripts');