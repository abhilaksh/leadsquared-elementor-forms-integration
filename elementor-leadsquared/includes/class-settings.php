<?php
class Class_Settings {
	private $options;

	public function __construct() {
			add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
			add_action( 'admin_init', array( $this, 'page_init' ) );
			add_action('wp_ajax_retrieve_leadsquared_schema', array($this, 'handle_retrieve_leadsquared_schema'));
	}

	public function add_plugin_page() {
			// Add settings page under settings menu
			add_options_page(
					'LeadSquared Settings', 
					'LeadSquared Settings', 
					'manage_options', 
					'leadsquared-setting-admin', 
					array( $this, 'create_admin_page' )
			);
	}

	public function create_admin_page() {
			$this->options = get_option('leadsquared_options');
			?>
			<div class="wrap">
					<h2>LeadSquared Settings</h2>
					<form method="post" action="options.php">
					<?php
							settings_fields('leadsquared_option_group');
							do_settings_sections('leadsquared-setting-admin');
							submit_button();
					?>
					</form>
					<button id="retrieve-leadsquared-schema" class="button button-secondary">Retrieve LeadSquared Schema</button>
					<div id="schema-update-status"></div>
			</div>
			<?php
	}


	public function page_init() {        
			register_setting(
					'leadsquared_option_group', // Option group
					'leadsquared_options', // Option name
					array( $this, 'sanitize' ) // Sanitize
			);

			add_settings_section(
					'setting_section_id', // ID
					'LeadSquared Access Settings', // Title
					array( $this, 'print_section_info' ), // Callback
					'leadsquared-setting-admin' // Page
			);  

			add_settings_field(
					'access_key', // ID
					'Access Key', // Title 
					array( $this, 'access_key_callback' ), // Callback
					'leadsquared-setting-admin', // Page
					'setting_section_id' // Section           
			);      

			add_settings_field(
					'secret_key', 
					'Secret Key', 
					array( $this, 'secret_key_callback' ), 
					'leadsquared-setting-admin', 
					'setting_section_id'
			);

			add_settings_field(
					'api_endpoint', 
					'API Endpoint', 
					array( $this, 'api_endpoint_callback' ), 
					'leadsquared-setting-admin', 
					'setting_section_id'
			);

			add_settings_field(
					'lead_update_behavior',
					'Lead Update Behavior',
					array( $this, 'lead_update_behavior_callback' ),
					'leadsquared-setting-admin',
					'setting_section_id'
			);
	}

	public function sanitize( $input ) {
			$new_input = array();
			if( isset( $input['access_key'] ) )
					$new_input['access_key'] = sanitize_text_field( $input['access_key'] );

			if( isset( $input['secret_key'] ) )
					$new_input['secret_key'] = sanitize_text_field( $input['secret_key'] );

			if( isset( $input['api_endpoint'] ) )
					$new_input['api_endpoint'] = sanitize_text_field( $input['api_endpoint'] );

			return $new_input;
	}

	public function print_section_info() {
			print 'Enter your LeadSquared access details below:';
	}

	public function access_key_callback() {
			printf(
					'<input type="text" id="access_key" name="leadsquared_options[access_key]" value="%s" />',
					isset( $this->options['access_key'] ) ? esc_attr( $this->options['access_key']) : ''
			);
	}

	public function secret_key_callback() {
			printf(
					'<input type="text" id="secret_key" name="leadsquared_options[secret_key]" value="%s" />',
					isset( $this->options['secret_key'] ) ? esc_attr( $this->options['secret_key']) : ''
			);
	}

	public function api_endpoint_callback() {
			printf(
					'<input type="text" id="api_endpoint" name="leadsquared_options[api_endpoint]" value="%s" />',
					isset( $this->options['api_endpoint'] ) ? esc_attr( $this->options['api_endpoint']) : ''
			);
	}

	// Callback for displaying the LeadUpdateBehavior field
	public function lead_update_behavior_callback() {
			$options = get_option('leadsquared_options');
			$behavior = isset($options['lead_update_behavior']) ? $options['lead_update_behavior'] : '';
			echo '<select id="lead_update_behavior" name="leadsquared_options[lead_update_behavior]">
							<option value="DoNotUpdate" '.selected($behavior, 'DoNotUpdate', false).'>Do Not Update</option>
							<option value="UpdateOnlyEmptyFields" '.selected($behavior, 'UpdateOnlyEmptyFields', false).'>Update Only Empty Fields</option>
							<option value="DoNotUpdateUniqueFields" '.selected($behavior, 'DoNotUpdateUniqueFields', false).'>Do Not Update Unique Fields</option>
						</select>';
	}
	public function handle_retrieve_leadsquared_schema() {
	if (!current_user_can('manage_options')) {
			wp_send_json_error('Unauthorized');
			return;
	}

	// Verify nonce for security
	check_ajax_referer('retrieve_schema_nonce', 'security');

	$stored_schema = get_option('leadsquared_fields_schema');
	if (!empty($stored_schema)) {
			wp_send_json_success(json_decode($stored_schema, true));
			return;
	}

	// Fetch access key and secret key from plugin settings
	$leadsquared_settings = get_option('leadsquared_options');
	$access_key = isset($leadsquared_settings['access_key']) ? $leadsquared_settings['access_key'] : '';
	$secret_key = isset($leadsquared_settings['secret_key']) ? $leadsquared_settings['secret_key'] : '';
	$api_endpoint = isset($leadsquared_settings['api_endpoint']) ? $leadsquared_settings['api_endpoint'] : '';

	if (empty($access_key) || empty($secret_key) || empty($api_endpoint)) {
			wp_send_json_error('LeadSquared Access Key or Secret Key is not set.');
			return;
	}

	$response = wp_remote_get("{$api_endpoint}/LeadManagement.svc/LeadsMetaData.Get?accessKey={$access_key}&secretKey={$secret_key}");

	if (is_wp_error($response)) {
			wp_send_json_error($response->get_error_message());
	} else {
			$schema = wp_remote_retrieve_body($response);
			update_option('leadsquared_fields_schema', $schema);
			wp_send_json_success(json_decode($schema, true));
	}
}
}

if ( is_admin() ) {
	$leadsquared_settings = new Class_Settings();
}
