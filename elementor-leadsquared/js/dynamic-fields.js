jQuery(document).ready(function($) {
    // Listen for Elementor editor events
    elementor.channels.editor.on('section:activated', function(sectionName, editor) {
        const model = editor.getOption('editedElementView').getEditModel();
        const settings = model.get('settings');

        // Check if the current section is your custom LeadSquared section
        if (sectionName === 'section_leadsquared') {
            const formFields = settings.get('form_fields');
            let options = {};

            if (formFields) {
                formFields.each(function(fieldModel) {
                    const fieldId = fieldModel.get('_id');
                    const fieldLabel = fieldModel.get('field_label') || fieldId;
                    options[fieldId] = fieldLabel;
                });
            }

            // Now, set the options for your 'elementor_field_id' control
            const repeaterControl = editor.controlManager.getControlModel('leadsquared_field_mappings');
            if (repeaterControl) {
                repeaterControl.attributes.fields.models.forEach(function(model) {
                    if (model.get('name') === 'elementor_field_id') {
                        model.set('options', options);
                    }
                });
            }
        }
    });
});
