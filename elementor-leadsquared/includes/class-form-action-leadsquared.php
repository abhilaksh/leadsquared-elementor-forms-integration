<?php

class Class_Form_Action_LeadSquared extends \ElementorPro\Modules\Forms\Classes\Action_Base {

		public function get_name() {
				return 'leadsquared';
		}

		public function get_label() {
				return __('LeadSquared', 'text-domain');
		}

		public function register_settings_section($widget) {
				$widget->start_controls_section(
						'section_leadsquared',
						[
								'label' => __('LeadSquared', 'text-domain'),
								'condition' => [
										'submit_actions' => $this->get_name(),
								],
						]
				);

				$widget->add_control(
						'leadsquared_search_by',
						[
								'label' => __('Search By', 'text-domain'),
								'type' => \Elementor\Controls_Manager::SELECT,
								'options' => [
										'' => __('None', 'text-domain'),
										'Phone' => __('Phone', 'text-domain'),
										'EmailAddress' => __('Email Address', 'text-domain'),
								],
								'description' => __('Define how to match existing leads.', 'text-domain'),
						]
				);

				$repeater = new \Elementor\Repeater();

				$repeater->add_control(
								'elementor_field_id',
								[
												'label' => __('Field/Tag/Text', 'text-domain'),
												'type' => \Elementor\Controls_Manager::TEXT,
												'label_block' => true,
												'description' => __('Enter an Elementor field ID, dynamic tag, form name, or any text.', 'text-domain'),
								]
				);

				$repeater->add_control(
						'leadsquared_field_id',
						[
								'label' => __('LeadSquared Field ID', 'text-domain'),
								'type' => \Elementor\Controls_Manager::SELECT,
								'options' => $this->get_leadsquared_fields_options(),
								'label_block' => true,
								'classes' => 'elementor-leadquared-select',
						]
				);

				$widget->add_control(
						'leadsquared_field_mappings',
						[
								'label' => __('Field Mappings', 'text-domain'),
								'type' => \Elementor\Controls_Manager::REPEATER,
								'fields' => $repeater->get_controls(),
								'title_field' => '{{{ elementor_field_id }}} => {{{ leadsquared_field_id }}}',
						]
				);

				$widget->end_controls_section();
		}

		private function get_leadsquared_fields_options() {
				$schema = get_option('leadsquared_fields_schema', '');
				$schema = !empty($schema) ? json_decode($schema, true) : [];
				$options = [];

				foreach ($schema as $field) {
						if (isset($field['SchemaName'], $field['DisplayName'], $field['DataType'])) {
								$displayName = $field['DisplayName'];
								$schemaName = $field['SchemaName'];
								$dataType = $field['DataType'];
								$options[$schemaName] = "{$displayName} [{$schemaName} - {$dataType}]";
						}
				}
				asort($options);
				return $options;
		}

		public function run($record, $ajax_handler) {
				error_log('Form submission started.');
				$settings = $record->get( 'form_settings' );
				$raw_fields = $record->get( 'fields' );
				$mapped_fields = [];

				foreach ($settings['leadsquared_field_mappings'] as $mapping) {
								error_log('Processing mapping: ' . print_r($mapping, true));
								$input = $mapping['elementor_field_id']; // The generalized input from the user
								$resolved_value = $this->resolve_input_value($input, $record);

								if (!empty($resolved_value)) {
									// Log the resolved value
									error_log('Resolved value: ' . $resolved_value);
												$mapped_fields[] = [
																'Attribute' => $mapping['leadsquared_field_id'],
																'Value' => sanitize_text_field($resolved_value)
												];
								}
				}
				if (!empty($settings['leadsquared_search_by'])) {
						$mapped_fields[] = [
								'Attribute' => 'SearchBy',
								'Value' => $settings['leadsquared_search_by']
						];
				}

				$data_string = json_encode($mapped_fields);
				$leadsquared_settings = get_option('leadsquared_options');
				$url = sprintf(
						"%s?accessKey=%s&secretKey=%s",
						$leadsquared_settings['api_endpoint'],
						"LeadManagement.svc/Lead.Capture",
						urlencode($leadsquared_settings['access_key']),
						urlencode($leadsquared_settings['secret_key'])
				);

				if (!empty($leadsquared_settings['lead_update_behavior'])) {
						$url .= '&LeadUpdateBehavior=' . urlencode($leadsquared_settings['lead_update_behavior']);
				}

				$curl = curl_init($url);
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
				curl_setopt($curl, CURLOPT_HTTPHEADER, array(
						"Content-Type: application/json",
						"Content-Length: " . strlen($data_string)
				));

				// Before the API call...
				error_log('Preparing to call API with data: ' . print_r($mapped_fields, true));
			$response = curl_exec($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $response_data = json_decode($response, true); // Decode the JSON response
        
            if ($http_code == 200 && $response_data['Status'] == 'Success') {
                // Handle successful submission
            } else {
                // Handle errors, including 500 Internal Server Error
                $error_message = 'An error occurred while submitting the form:';
                
                if (!empty($response_data['ExceptionMessage'])) {
                    // Use the specific exception message from the API if available
                    $error_message .= ' ' . $response_data['ExceptionMessage'];
                } elseif ($http_code == 500) {
                    // Generic message for 500 errors without a specific exception message
                    $error_message .= ' Internal server error. Please try again later.';
                } else {
                    // Handling for other HTTP status codes or general errors
                    switch ($http_code) {
                        case 400:
                            $error_message .= ' Bad request. Please check the submitted data.';
                            break;
                        case 401:
                            $error_message .= ' Unauthorized access. Please check your API keys.';
                            break;
                        case 404:
                            $error_message .= ' API endpoint not found.';
                            break;
                        case 429:
                            $error_message .= ' Too many requests. Please try again later.';
                            break;
                        // Add more cases as needed
                    }
                }
        
                // Use Elementor's method to add an error message that will be displayed on the form
                $ajax_handler->add_error_message($error_message);
            }
        
            curl_close($curl);
			}

            private function resolve_input_value($input, $record) {
                    $raw_fields = $record->get('fields');
                
                    // First, handle the specific shortcode format for field IDs.
                    if (preg_match('/\[field id="([^"]+)"\]/', $input, $matches)) {
                        $field_id = $matches[1];
                        foreach ($raw_fields as $field_key => $field) {
                            if ($field_key == $field_id && isset($field['value'])) {
                                // Decode HTML entities before returning the value
                                return html_entity_decode($field['value'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                            }
                        }
                        return ''; // Return an empty string if no matching field is found
                    }
                
                    // Handling other types of shortcodes or dynamic tags.
                    if (strpos($input, '[') !== false) {
                        return html_entity_decode(do_shortcode($input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    }
                
                    // Direct match for form field IDs.
                    if (isset($raw_fields[$input]) && isset($raw_fields[$input]['value'])) {
                        // Decode HTML entities before returning the value
                        return html_entity_decode($raw_fields[$input]['value'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    }
                
                    // Handling specific form settings like form name.
                    $form_settings = $record->get('form_settings');
                    if ($input === 'FORM_NAME') {
                        return isset($form_settings['form_name']) ? html_entity_decode($form_settings['form_name'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : 'Default Form Name';
                    }
                
                    // Fallback for plain text or any input that doesn't match the above cases.
                    return html_entity_decode($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }





	
	/**
		* Handle form action export data.
		* 
		* This method is called when a form is exported from Elementor.
		* Implement any necessary data handling or modifications for the export process.
		*
		* @param array $element The form element being exported.
		*/
	public function on_export($element) {
			// If your action doesn't need to modify or add data during export,
			// you can leave this method empty.

			// Example implementation:
			// Add a custom setting value to the form export data
			// if (!empty($element['settings']['custom_setting'])) {
			//     $element['settings']['custom_setting_exported'] = $element['settings']['custom_setting'];
			// }
	}
}
