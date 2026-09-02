<?php
/**
 * PHP version 5.6
 *
 * Forms integration handler for KeyCRM
 *
 * @category Integration
 * @package  WC_Keycrm_Client
 * @author   KeyCRM <dev@keycrm.app>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://docs.keycrm.app/#/
 * @since    1.0.21
 */

if (!class_exists('WC_Keycrm_Forms')) {
    /**
     * Class WC_Keycrm_Forms
     */
    class WC_Keycrm_Forms
    {
        /**
         * API client instance
         *
         * @var WC_Keycrm_Client_V6|WC_Keycrm_Proxy|bool
         */
        protected $apiClient;

        /**
         * Plugin settings
         *
         * @var array
         */
        protected $settings;

        /**
         * Constructor
         *
         * @param WC_Keycrm_Client_V6|WC_Keycrm_Proxy|bool $apiClient API client instance
         * @param array                                    $settings  Plugin settings
         */
        public function __construct($apiClient, $settings)
        {
            $this->apiClient = $apiClient;
            $this->settings = $settings;
        }

        /**
         * Initialize WordPress hooks
         *
         * @return void
         */
        public function init_hooks()
        {
            if (!isset($this->settings['forms_enabled']) || $this->settings['forms_enabled'] !== 'yes') {
                return;
            }

            if (class_exists('WPCF7_ContactForm')) {
                add_action('wpcf7_mail_sent', array($this, 'handle_contact_form_7'), 10, 1);
            }

            if (class_exists('WPForms')) {
                add_action('wpforms_process_complete', array($this, 'handle_wpforms'), 10, 4);
            }
        }

        /**
         * Handle Contact Form 7 submissions
         *
         * @param WPCF7_ContactForm $contact_form Contact form instance
         * @return void
         */
        public function handle_contact_form_7($contact_form)
        {
            if (!$this->apiClient) {
                return;
            }

            $submission = WPCF7_Submission::get_instance();
            if (!$submission) {
                return;
            }

            $posted_data = $submission->get_posted_data();
            $form_title = $contact_form->title();
            $form_id = $contact_form->id();

            WC_Keycrm_Logger::debug('CF7 Form submission', array(
                'form_id' => $form_id,
                'form_title' => $form_title,
                'form_data' => $posted_data
            ));

            $this->send_to_keycrm($posted_data, $form_title, 'contact_form_7', $form_id);
        }

        /**
         * Handle WPForms submissions
         *
         * @param array $fields   Form fields data
         * @param array $entry    Form entry data
         * @param array $form_data Form configuration data
         * @param int   $entry_id Entry ID
         * @return void
         */
        public function handle_wpforms($fields, $entry, $form_data, $entry_id)
        {
            if (!$this->apiClient) {
                return;
            }

            $form_title = isset($form_data['settings']['form_title']) ? $form_data['settings']['form_title'] : 'WPForm';
            $form_id = isset($form_data['id']) ? $form_data['id'] : 0;

            $posted_data = array();
            foreach ($fields as $field) {
                if (isset($field['name']) && isset($field['value'])) {
                    $field_name = $field['name'];
                    $posted_data[$field_name] = $field['value'];
                }
            }

            WC_Keycrm_Logger::debug('WPForms submission', array(
                'form_id' => $form_id,
                'form_title' => $form_title,
                'form_data' => $posted_data
            ));

            $this->send_to_keycrm($posted_data, $form_title, 'wpforms', $form_id);
        }

        /**
         * Send form data to KeyCRM API
         *
         * @param array  $form_data Form submitted data
         * @param string $form_title Form title
         * @param string $form_type Form type identifier
         * @param int    $form_id   Form ID
         * @return void
         */
        private function send_to_keycrm($form_data, $form_title, $form_type, $form_id = 0)
        {
            $contact = $this->extract_contact_info($form_data);

            if (empty($contact['full_name']) && empty($contact['email']) && empty($contact['phone'])) {
                $contact = array(
                    'full_name' => 'Form Submission: ' . $form_title,
                    'email' => 'no-email@example.com'
                );
            }

            $manager_comment = "=== FORM SUBMISSION ===\n";
            $manager_comment .= "Form: " . $form_title . "\n";
            $manager_comment .= "Type: " . $form_type . "\n";
            $manager_comment .= "ID: " . $form_id . "\n";
            $manager_comment .= "Date: " . current_time('Y-m-d H:i:s') . "\n";
            $manager_comment .= "URL: " . get_site_url() . "\n\n";
            $manager_comment .= "=== ALL FORM FIELDS ===\n";

            foreach ($form_data as $field_name => $field_value) {
                if (empty($field_value)) {
                    continue;
                }

                if (is_array($field_value)) {
                    $field_value = implode(', ', array_filter($field_value));
                }

                $manager_comment .= $field_name . ": " . $field_value . "\n";
            }

            $manager_comment .= "\n=== CONTACT INFO ===\n";
            $manager_comment .= "Name: " . $contact['full_name'] . "\n";
            $manager_comment .= "Email: " . $contact['email'] . "\n";
            $manager_comment .= "Phone: " . $contact['phone'] . "\n";

            $card_data = array(
                'title' => 'Form: ' . $form_title . ' (' . date('d.m.Y H:i') . ')',
                'source_id' => isset($this->settings['forms_source_id']) ? $this->settings['forms_source_id'] : (isset($this->settings['order_methods']) ? $this->settings['order_methods'] : ''),
                'contact' => $contact,
                'manager_comment' => $manager_comment,
                'custom_fields' => array(
                    array(
                        'uuid' => 'form_title',
                        'value' => $form_title
                    ),
                    array(
                        'uuid' => 'form_type',
                        'value' => $form_type
                    ),
                    array(
                        'uuid' => 'form_id',
                        'value' => $form_id
                    ),
                    array(
                        'uuid' => 'submission_date',
                        'value' => current_time('Y-m-d H:i:s')
                    )
                )
            );

            if (isset($this->settings['forms_pipeline_id']) && !empty($this->settings['forms_pipeline_id'])) {
                $card_data['pipeline_id'] = intval($this->settings['forms_pipeline_id']);
            }

            $utm_fields = array('utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content');
            foreach ($utm_fields as $utm_field) {
                if (isset($form_data[$utm_field]) && !empty($form_data[$utm_field])) {
                    $card_data[$utm_field] = $form_data[$utm_field];
                }
            }

            try {
                WC_Keycrm_Logger::debug('Sending form data to KeyCRM', array('card_data' => $card_data));

                $response = $this->apiClient->createPipelineCard($card_data);

                if ($response && method_exists($response, 'isSuccessful') && $response->isSuccessful()) {
                    WC_Keycrm_Logger::add('Form card created successfully: ' . $form_title);
                } else {
                    WC_Keycrm_Logger::error($response->getRawResponse());
                }
            } catch (Exception $e) {
                WC_Keycrm_Logger::error('Forms Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            }
        }

        /**
         * Extract contact information from form data
         *
         * @param array $form_data Form submitted data
         * @return array Contact information
         */
        private function extract_contact_info($form_data)
        {
            $contact = array(
                'full_name' => '',
                'email' => '',
                'phone' => ''
            );

            foreach ($form_data as $field_name => $field_value) {
                if (empty($field_value) || is_array($field_value)) {
                    continue;
                }

                $lower_field = strtolower($field_name);

                if (empty($contact['full_name']) && strpos($lower_field, 'name') !== false) {
                    $contact['full_name'] = $field_value;
                }

                if (empty($contact['email']) && (strpos($lower_field, 'email') !== false || filter_var($field_value, FILTER_VALIDATE_EMAIL))) {
                    $contact['email'] = $field_value;
                }

                if (empty($contact['phone']) && (strpos($lower_field, 'phone') !== false || strpos($lower_field, 'tel') !== false)) {
                    $contact['phone'] = $field_value;
                }
            }

            if (empty($contact['full_name']) && !empty($contact['email'])) {
                $email_parts = explode('@', $contact['email']);
                $contact['full_name'] = ucfirst($email_parts[0]);
            }

            if (empty($contact['full_name'])) {
                $contact['full_name'] = 'Form Submission';
            }

            return $contact;
        }
    }
}