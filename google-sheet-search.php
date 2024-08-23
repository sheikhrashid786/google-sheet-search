<?php
/*
Author: Rashid
Plugin Name: Google Sheet Search
Description: This plugin is used to search contracts in Google Sheets.
Version: 1.0
Text Domain: GSS-contract
*/

// Prevent direct file access
defined('ABSPATH') or exit;

class Google_Sheet_Search
{
    private $api_key = 'AIzaSyDRnGCVXcRkgXe2OjeE5pWYX62NmJYicXg';
    private $spreadsheet_id = '1ZhTOYg45o74UYglEjQOEQslDB_gEsUuwBTGpFKGONRw';
    private $sheets = ['Live Projects', 'Refunds', 'Refunded', 'Burns', 'Burned']; // Define your sheets here

    public function __construct()
    {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('gss_search_form', array($this, 'search_form_shortcode'));
        add_action('wp_ajax_gss_search', array($this, 'search_ajax_handler'));
        add_action('wp_ajax_nopriv_gss_search', array($this, 'search_ajax_handler'));
    }

    public function enqueue_scripts()
    {
        wp_enqueue_script('gss-search-js', plugin_dir_url(__FILE__) . 'js/gss-search.js', array('jquery'), null, true);
        wp_localize_script('gss-search-js', 'gss_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
        wp_enqueue_style('gss-style', plugin_dir_url(__FILE__) . 'css/gss-search.css');
    }

    public function search_form_shortcode()
    {
        return '<form id="gss-search-form">
                    <input type="text" id="gss-search-input" name="search_value" placeholder="Enter contract ID to search">
                
                    <div class="bot-container">
                        <img src="'.plugin_dir_url(__FILE__).'images/logo-01.png" class="bot" alt="Bot">
                    </div>
                </form>
                <div id="gss-search-results"></div>';
    }

    public function search_ajax_handler()
    {
        if (isset($_POST['search_value'])) {
            $search_value = sanitize_text_field($_POST['search_value']);

            $found = false;
            $result = '<table border="1" cellpadding="5" cellspacing="0">';
            foreach ($this->sheets as $sheet) {
                // Fetch data from Google Sheets for the current sheet
                $url = "https://sheets.googleapis.com/v4/spreadsheets/{$this->spreadsheet_id}/values/{$sheet}?key={$this->api_key}";

                $response = wp_remote_get($url);

                if (is_wp_error($response)) {
                    echo '<table border="1" cellpadding="5" cellspacing="0"><tr><th>Failed to fetch data from Google Sheets</tr></th></table>';
                    wp_die();
                }

                $data = wp_remote_retrieve_body($response);
                $values = json_decode($data, true);

                if (!isset($values['values']) || empty($values['values'])) {
                    continue; // Skip to the next sheet if no data is found
                }

                $headers = array_shift($values['values']); // Get the headers row
                $contract_address_index = array_search('Contract Address', $headers);

                if ($contract_address_index === false) {
                    continue; // Skip to the next sheet if "Contract Address" column not found
                }

                foreach ($values['values'] as $row) {
                    // Check for exact match in the "Contract Address" column
                    if (isset($row[$contract_address_index]) && $row[$contract_address_index] === $search_value) {
                        $found = true;


                        // Iterate over headers and row data, skipping the "Private Key" header
                        for ($i = 0; $i < count($headers); $i++) {
                            if ($headers[$i] === 'Private Key') {
                                continue; // Skip the "Private Key" header and its value
                            }

                            $result .= '<tr>';
                            // Add the header cell
                            $result .= '<th>' . htmlspecialchars($headers[$i]) . '</th>';
                            // Add the corresponding data cell
                            $result .= '<td>' . htmlspecialchars($row[$i]) . '</td>';
                            $result .= '</tr>';
                        }


                        // Add the Status row with the sheet name
                        $result .= '<tr>';
                        $result .= '<th>Status</th>';
                        $result .= '<td>' . htmlspecialchars($sheet) . '</td>';
                        $result .= '</tr>';


                        break 2; // Stop searching after finding the first exact match in any sheet
                    }
                }
            }

            if (!$found) {
                $result .= '<tr><th>No record found for ' . esc_html($search_value).'</th></tr>';
            }
            $result .= '</table>';

            echo $result;
        }

        wp_die(); // this is required to terminate immediately and return a proper response
    }
}

// Initialize the plugin
new Google_Sheet_Search();
