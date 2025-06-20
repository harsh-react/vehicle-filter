<?php

/**
 * Plugin Name: Vehicle Filter
 * Description: A plugin to filter products based on vehicle data
 * Version: 1.0.0
 * Author: Elate
 */

use PhpOffice\PhpSpreadsheet\Calculation\TextData\Trim;

// Add admin menu
add_action('admin_menu', 'vehicle_filter_admin_menu');

// Register settings for admin messages
add_action('admin_init', 'vehicle_filter_admin_init');

function vehicle_filter_admin_init() {
    // Register settings for admin messages
    register_setting('vehicle_filter', 'vehicle_filter_messages');
}

// Enqueue custom admin CSS for the vehicle filter page
add_action('admin_head', function () {
    global $pagenow;
    if (isset($_GET['page']) && $_GET['page'] === 'vehicle-filter') {
        echo '<style>
        .vehicle-filter-flex-row {
            display: flex;
            flex-direction: column;
            gap: 32px;
            margin: 20px 0px;
            flex-wrap: wrap;
            align-items: stretch;
            justify-content: center;
            max-width: 1200px;
        }
        .vehicle-filter-flex-row .vehicle-filter-card {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            height: 100%;
            box-sizing: border-box;
        }
        .vehicle-filter-card {
            background: #fff;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            box-shadow: none;
            padding: 32px 24px 24px 24px;
            margin-bottom: 0;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            height: 100%;
        }
        .vehicle-filter-card h2 {
            margin-top: 0;
            font-size: 1.4em;
        }
        .vehicle-filter-flex-row .vehicle-filter-card form {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
        }
        .vehicle-filter-form-row {
            display: flex;
            align-items: center;
            margin-bottom: 16px;
        }
        .vehicle-filter-form-row label {
            min-width: 120px;
            font-weight: 500;
            margin-right: 18px;
            margin-bottom: 0;
        }
        .vehicle-filter-form-row > div {
            flex: 1;
        }
        .vehicle-filter-form-row input[type="text"],
        .vehicle-filter-form-row input[type="number"] {
            width: 100%;
            padding: 8px 10px;
            border-radius: 4px;
            border: 1px solid #ccd0d4;
            margin-bottom: 0;
            font-size: 1em;
        }
        .vehicle-filter-form-row input[type="file"] {
            font-size: 1em;
            margin-bottom: 0;
        }
        .vehicle-filter-card .submit {
            margin-top: 16px;
            padding: 0;
        }
        .vehicle-filter-card-manual {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            height: 100%;
            box-sizing: border-box;
            background: #fff;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            box-shadow: none;
            padding: 32px 24px 24px 24px;
            margin-bottom: 0;
        }
        .vehicle-filter-card-manual form {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }
        .vehicle-filter-card-manual .vehicle-filter-form-row label {
            min-width: 60px;
            font-weight: 500;
            margin-right: 18px;
            margin-bottom: 0;
        }
        .vehicle-filter-card-manual .submit {
            padding: 0;
            margin: 0;
        }
        .vehicle-filter-card input[type="submit"] {
            background: #2271b1;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 8px 24px;
            font-size: 1em;
            cursor: pointer;
            transition: background 0.2s;
        }
        .vehicle-filter-card input[type="submit"]:hover {
            background: #135e96;
        }
        .vehicle-filter-flex-row .vehicle-filter-card form .submit {
            grid-column: 1 / -1;   /* Span both columns */
            // justify-self: center;  /* Center the button horizontally */
            margin: 0;
        }
        .vehicle-filter-form-row-engines {
            grid-column: 1 / -1;
            display: flex;
            flex-direction: column;
            justify-content: start;
            align-items: start;
            gap: 10px;
        }
        /* Make engine fields more compact */
        .vehicle-filter-form-row label[for^="engine"] {
            align-self: flex-start;
        }
        .vehicle-filter-form-row label + div > input[placeholder^="Engine"] {
            margin-bottom: 6px;
        }
        // .vehicle-filter-form-row label + div > input[placeholder^="Engine"]:last-child {
        //     margin-bottom: 0;
        // }
        @media (max-width: 900px) {
            .vehicle-filter-flex-row {
                flex-direction: column;
                gap: 24px;
                max-width: 98vw;
            }
            .vehicle-filter-flex-row .vehicle-filter-card {
                min-width: 0;
                max-width: 100%;
            }
        }
        .vehicle-filter-feedback { margin-bottom: 24px; padding: 12px 18px; border-radius: 5px; font-size: 1.1em; }
        .vehicle-filter-feedback-success { background: #e7f7e2; color: #217a00; border: 1px solid #b6e2b1; }
        .vehicle-filter-feedback-error { background: #fbeaea; color: #a00; border: 1px solid #f5c2c7; }
        </style>';
    }
});

function vehicle_filter_admin_menu()
{
    add_menu_page(
        'Vehicle Filter',
        'Vehicle Filter',
        'manage_options',
        'vehicle-filter',
        'vehicle_filter_admin_page',
        'dashicons-car',
        30
    );
}

function vehicle_filter_admin_page()
{
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }

    // Handle form submissions first
    $message = '';
    $message_type = '';

    // Save form data if submitted
    if (isset($_POST['vehicle_filter_submit'])) {
        check_admin_referer('vehicle_filter_nonce');
        $result = handle_vehicle_form_submission();
        if (isset($result['message'])) {
            $message = $result['message'];
            $message_type = $result['type'];
        }
    }

    // Handle CSV imports if submitted
    if (isset($_POST['vehicle_filter_csv_import'])) {
        check_admin_referer('vehicle_filter_nonce');
        $result = handle_csv_import();
        if (isset($result['message'])) {
            $message = $result['message'];
            $message_type = $result['type'];
        }
    }

    // Display admin notices
    settings_errors('vehicle_filter');

?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <?php if (!empty($message)): ?>
            <div class="vehicle-filter-feedback vehicle-filter-feedback-<?php echo esc_attr($message_type); ?>">
                <?php echo esc_html($message); ?>
            </div>
        <?php endif; ?>

        <div class="vehicle-filter-flex-row">
            <!-- CSV Import Section -->
            <div class="vehicle-filter-card">
                <h2>Import Vehicle Data</h2>
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('vehicle_filter_nonce'); ?>
                    <div class="vehicle-filter-form-row">
                        <label for="vehicle_base_csv">Vehicle Base CSV</label>
                        <div><input type="file" name="vehicle_base_csv" id="vehicle_base_csv" accept=".csv"></div>
                    </div>
                    <div class="vehicle-filter-form-row">
                        <label for="engine_table_csv">Engine Table CSV</label>
                        <div><input type="file" name="engine_table_csv" id="engine_table_csv" accept=".csv"></div>
                    </div>
                    <div class="vehicle-filter-form-row">
                        <label for="vehicle_engine_csv">Vehicle Engine CSV</label>
                        <div><input type="file" name="vehicle_engine_csv" id="vehicle_engine_csv" accept=".csv"></div>
                    </div>
                    <div class="submit">
                        <input type="submit" name="vehicle_filter_csv_import" class="button button-primary" value="Import CSV Files">
                    </div>
                </form>
            </div>

            <!-- Manual Entry Form -->
            <div class="vehicle-filter-card-manual ">
                <h2>Add New Vehicle</h2>
                <form method="post">
                    <?php wp_nonce_field('vehicle_filter_nonce'); ?>
                    <div class="vehicle-filter-form-row">
                        <label for="make">Make</label>
                        <div><input type="text" name="make" id="make" class="regular-text" required></div>
                    </div>
                    <div class="vehicle-filter-form-row">
                        <label for="model">Model</label>
                        <div><input type="text" name="model" id="model" class="regular-text" required></div>
                    </div>
                    <div class="vehicle-filter-form-row">
                        <label for="listing">Listing</label>
                        <div><input type="text" name="listing" id="listing" class="regular-text" required></div>
                    </div>
                    <div class="vehicle-filter-form-row">
                        <label for="year_from">Year From</label>
                        <div><input type="number" name="year_from" id="year_from" class="regular-text" required></div>
                    </div>
                    <div class="vehicle-filter-form-row">
                        <label for="year_to">Year To</label>
                        <div><input type="number" name="year_to" id="year_to" class="regular-text"></div>
                    </div>
                    <div class="vehicle-filter-form-row 
                    vehicle-filter-form-row-engines">
                        <label>Engines</label>
                        <div style="display: grid;grid-template-columns: repeat(4, 1fr);gap: 20px;">
                            <input type="text" name="engine[]" class="regular-text" placeholder="Engine 1" required>
                            <input type="text" name="engine[]" class="regular-text" placeholder="Engine 2">
                            <input type="text" name="engine[]" class="regular-text" placeholder="Engine 3">
                            <input type="text" name="engine[]" class="regular-text" placeholder="Engine 4">
                        </div>
                    </div>
                    <div class="submit">
                        <input type="submit" name="vehicle_filter_submit" class="button button-primary" value="Add Vehicle">
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php
}

function handle_vehicle_form_submission()
{
    global $wpdb;

    // Get form data
    $make = sanitize_text_field($_POST['make']);
    $model = sanitize_text_field($_POST['model']);
    $listing = sanitize_text_field($_POST['listing']);
    $year_from = intval($_POST['year_from']);
    $year_to = !empty($_POST['year_to']) ? intval($_POST['year_to']) : date('Y');
    $engines = isset($_POST['engine']) ? array_map('sanitize_text_field', $_POST['engine']) : array();
    $engines = array_filter($engines); // Remove empty values

    // Validate required fields
    if (empty($make) || empty($model) || empty($listing) || empty($year_from) || empty($engines)) {
        return array(
            'message' => 'Please fill in all required fields.',
            'type' => 'error'
        );
    }

    // Start transaction
    $wpdb->query('START TRANSACTION');

    try {
        // Get next vehicle_id
        $last_vehicle_id = $wpdb->get_var("SELECT MAX(vehicle_id) FROM {$wpdb->prefix}vehicle_base");
        $new_vehicle_id = $last_vehicle_id ? $last_vehicle_id + 1 : 1;

        // Insert into vehicle_base
        $vehicle_inserted = $wpdb->insert(
            $wpdb->prefix . 'vehicle_base',
            array(
                'vehicle_id' => $new_vehicle_id,
                'make' => $make,
                'model' => $model,
                'listing' => $listing,
                'year_from' => $year_from,
                'year_to' => $year_to
            ),
            array('%d', '%s', '%s', '%s', '%d', '%d')
        );

        if (!$vehicle_inserted) {
            throw new Exception('Failed to insert vehicle data');
        }

        $engine_ids = array();
        // Process each engine
        foreach ($engines as $engine_code) {
            // Get next engine_id
            $last_engine_id = $wpdb->get_var("SELECT MAX(engine_id) FROM {$wpdb->prefix}engine");
            $new_engine_id = $last_engine_id ? $last_engine_id + 1 : 1;

            // Insert into engine table
            $engine_inserted = $wpdb->insert(
                $wpdb->prefix . 'engine',
                array(
                    'engine_id' => $new_engine_id,
                    'engine_code' => $engine_code
                ),
                array('%d', '%s')
            );

            if (!$engine_inserted) {
                throw new Exception('Failed to insert engine data');
            }

            $engine_ids[] = $new_engine_id;

            // Create vehicle-engine mapping
            $mapping_inserted = $wpdb->insert(
                $wpdb->prefix . 'vehicle_engine',
                array(
                    'vehicle_id' => $new_vehicle_id,
                    'engine_id' => $new_engine_id
                ),
                array('%d', '%d')
            );

            if (!$mapping_inserted) {
                throw new Exception('Failed to create vehicle-engine mapping');
            }
        }

        $wpdb->query('COMMIT');

        // Show success message with details
        $message = sprintf(
            'Vehicle added successfully! Vehicle ID: %d, Engines added: %d',
            $new_vehicle_id,
            count($engine_ids)
        );
        
        return array(
            'message' => $message,
            'type' => 'success'
        );
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        return array(
            'message' => 'Error adding vehicle: ' . $e->getMessage(),
            'type' => 'error'
        );
    }
}

function handle_csv_import()
{
    global $wpdb;

    // Log the start of import process
    vehicle_filter_log('Starting CSV import process');

    // Validate that files were uploaded
    if (
        empty($_FILES['vehicle_base_csv']['tmp_name']) ||
        empty($_FILES['engine_table_csv']['tmp_name']) ||
        empty($_FILES['vehicle_engine_csv']['tmp_name'])
    ) {
        $error_message = 'Please upload all three required CSV files.';
        vehicle_filter_log('Missing required CSV files');
        return array(
            'message' => $error_message,
            'type' => 'error'
        );
    }

    // Start transaction
    $wpdb->query('START TRANSACTION');
    vehicle_filter_log('Started database transaction');

    try {
        // Disable foreign key checks
        $wpdb->query('SET FOREIGN_KEY_CHECKS = 0');
        vehicle_filter_log('Disabled foreign key checks');

        // Truncate tables in correct order
        $vehicle_engine = $wpdb->prefix . 'vehicle_engine';
        $vehicle_base = $wpdb->prefix . 'vehicle_base';
        $engine = $wpdb->prefix . 'engine';

        // Log the truncation process
        vehicle_filter_log('Starting table truncation process');

        // Truncate all tables
        $tables = array($vehicle_engine, $vehicle_base, $engine);
        foreach ($tables as $table) {
            $result = $wpdb->query("TRUNCATE TABLE $table");
            if ($result === false) {
                $error_message = "Failed to truncate $table: " . $wpdb->last_error;
                vehicle_filter_log($error_message);
                throw new Exception($error_message);
            }
            vehicle_filter_log("Successfully truncated $table");
        }

        // Re-enable foreign key checks
        $wpdb->query('SET FOREIGN_KEY_CHECKS = 1');
        vehicle_filter_log('Re-enabled foreign key checks');

        $current_year = date('Y');
        $imported_count = array(
            'vehicle_base' => 0,
            'engine' => 0,
            'vehicle_engine' => 0
        );

        // Process vehicle_base.csv
        if (!empty($_FILES['vehicle_base_csv']['tmp_name'])) {
            vehicle_filter_log('Processing vehicle_base.csv');
            $handle = fopen($_FILES['vehicle_base_csv']['tmp_name'], 'r');
            if ($handle === FALSE) {
                throw new Exception("Failed to open vehicle_base.csv file");
            }

            // Read and validate header
            $header = fgetcsv($handle);
            if ($header === FALSE) {
                throw new Exception("Failed to read header from vehicle_base.csv");
            }
            vehicle_filter_log('Vehicle base CSV header: ' . implode(', ', $header));

            while (($data = fgetcsv($handle)) !== FALSE) {
                // Log raw data for debugging
                vehicle_filter_log('Processing vehicle_base row: ' . implode(', ', $data));

                // Always process the row, even with missing fields
                $year_to = !empty($data[5]) ? floatval($data[5]) : floatval($current_year);
                $result = $wpdb->insert(
                    $wpdb->prefix . 'vehicle_base',
                    array(
                        'vehicle_id' => intval($data[0]),
                        'make' => isset($data[1]) ? trim($data[1]) : '',
                        'model' => isset($data[2]) ? trim($data[2]) : '',
                        'listing' => isset($data[3]) ? trim($data[3]) : '',
                        'year_from' => isset($data[4]) ? floatval($data[4]) : 0,
                        'year_to' => $year_to
                    ),
                    array('%d', '%s', '%s', '%s', '%f', '%f')
                );
                if ($result === false) {
                    $error_message = "Failed to insert vehicle_base data: " . $wpdb->last_error . "\nData: " . implode(', ', $data);
                    vehicle_filter_log($error_message);
                    throw new Exception($error_message);
                }
                $imported_count['vehicle_base']++;
            }
            fclose($handle);
            vehicle_filter_log('Completed processing vehicle_base.csv. Imported: ' . $imported_count['vehicle_base'] . ' records');
        }

        // Process engine_table.csv
        if (!empty($_FILES['engine_table_csv']['tmp_name'])) {
            vehicle_filter_log('Processing engine_table.csv');
            $handle = fopen($_FILES['engine_table_csv']['tmp_name'], 'r');
            if ($handle === FALSE) {
                throw new Exception("Failed to open engine_table.csv file");
            }

            // Read and validate header
            $header = fgetcsv($handle);
            if ($header === FALSE) {
                throw new Exception("Failed to read header from engine_table.csv");
            }
            vehicle_filter_log('Engine table CSV header: ' . implode(', ', $header));

            while (($data = fgetcsv($handle)) !== FALSE) {
                // Log raw data for debugging
                vehicle_filter_log('Processing engine_table row: ' . implode(', ', $data));

                if (count($data) >= 2 && !empty(trim($data[1]))) {
                    $result = $wpdb->insert(
                        $wpdb->prefix . 'engine',
                        array(
                            'engine_id' => intval($data[0]),
                            'engine_code' => trim($data[1])
                        ),
                        array('%d', '%s')
                    );
                    if ($result === false) {
                        $error_message = "Failed to insert engine data: " . $wpdb->last_error . "\nData: " . implode(', ', $data);
                        vehicle_filter_log($error_message);
                        throw new Exception($error_message);
                    }
                    $imported_count['engine']++;
                } else {
                    vehicle_filter_log('Skipping invalid engine_table row: ' . implode(', ', $data));
                }
            }
            fclose($handle);
            vehicle_filter_log('Completed processing engine_table.csv. Imported: ' . $imported_count['engine'] . ' records');
        }

        // Process vehicle_engine.csv
        if (!empty($_FILES['vehicle_engine_csv']['tmp_name'])) {
            vehicle_filter_log('Processing vehicle_engine.csv');
            $handle = fopen($_FILES['vehicle_engine_csv']['tmp_name'], 'r');
            if ($handle === FALSE) {
                throw new Exception("Failed to open vehicle_engine.csv file");
            }

            // Read and validate header
            $header = fgetcsv($handle);
            if ($header === FALSE) {
                throw new Exception("Failed to read header from vehicle_engine.csv");
            }
            vehicle_filter_log('Vehicle engine CSV header: ' . implode(', ', $header));

            while (($data = fgetcsv($handle)) !== FALSE) {
                // Log raw data for debugging
                vehicle_filter_log('Processing vehicle_engine row: ' . implode(', ', $data));

                if (count($data) >= 2) {
                    $result = $wpdb->insert(
                        $wpdb->prefix . 'vehicle_engine',
                        array(
                            'vehicle_id' => intval($data[0]),
                            'engine_id' => intval($data[1])
                        ),
                        array('%d', '%d')
                    );
                    if ($result === false) {
                        $error_message = "Failed to insert vehicle_engine data: " . $wpdb->last_error . "\nData: " . implode(', ', $data);
                        vehicle_filter_log($error_message);
                        throw new Exception($error_message);
                    }
                    $imported_count['vehicle_engine']++;
                } else {
                    vehicle_filter_log('Skipping invalid vehicle_engine row: ' . implode(', ', $data));
                }
            }
            fclose($handle);
            vehicle_filter_log('Completed processing vehicle_engine.csv. Imported: ' . $imported_count['vehicle_engine'] . ' records');
        }

        $wpdb->query('COMMIT');
        vehicle_filter_log('Transaction committed successfully');

        // Show success message with import counts
        $success_message = sprintf(
            'Import completed successfully! Imported: %d vehicles, %d engines, %d vehicle-engine mappings.',
            $imported_count['vehicle_base'],
            $imported_count['engine'],
            $imported_count['vehicle_engine']
        );
        vehicle_filter_log($success_message);
        
        return array(
            'message' => $success_message,
            'type' => 'success'
        );
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        // Re-enable foreign key checks in case of error
        $wpdb->query('SET FOREIGN_KEY_CHECKS = 1');

        $error_message = 'Error during import: ' . $e->getMessage();
        vehicle_filter_log($error_message);
        
        return array(
            'message' => $error_message,
            'type' => 'error'
        );
    }
}

if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}
if (!function_exists('plugin_dir_path')) {
    require_once(ABSPATH . 'wp-includes/plugin.php');
}

// Test logging
vehicle_filter_log('Plugin loaded - Testing logging functionality');

// Include WordPress core files
require_once(ABSPATH . 'wp-includes/pluggable.php');
require_once(ABSPATH . 'wp-includes/formatting.php');
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

// Plugin activation hook
register_activation_hook(__FILE__, 'vehicle_filter_activate');

function vehicle_filter_activate()
{
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $vehicle_base = $wpdb->prefix . 'vehicle_base';
    $engine = $wpdb->prefix . 'engine';
    $vehicle_engine = $wpdb->prefix . 'vehicle_engine';

    // Log activation start
    vehicle_filter_log('Plugin activation started');

    // Create vehicle_base table
    $sql1 = "CREATE TABLE IF NOT EXISTS $vehicle_base (
        vehicle_id INT NOT NULL,
        make VARCHAR(100) NOT NULL,
        model VARCHAR(100) NOT NULL,
        listing VARCHAR(255) NOT NULL,
        year_from FLOAT NOT NULL,
        year_to FLOAT NOT NULL,
        PRIMARY KEY (vehicle_id)
    ) $charset_collate;";

    // Create engine table
    $sql2 = "CREATE TABLE IF NOT EXISTS $engine (
        engine_id INT NOT NULL,
        engine_code VARCHAR(100) NOT NULL,
        PRIMARY KEY (engine_id)
    ) $charset_collate;";

    // Create vehicle_engine table
    $sql3 = "CREATE TABLE IF NOT EXISTS $vehicle_engine (
        vehicle_id INT NOT NULL,
        engine_id INT NOT NULL,
        PRIMARY KEY (vehicle_id, engine_id),
        FOREIGN KEY (vehicle_id) REFERENCES $vehicle_base(vehicle_id) ON DELETE CASCADE,
        FOREIGN KEY (engine_id) REFERENCES $engine(engine_id) ON DELETE CASCADE
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Log table creation
    vehicle_filter_log('Creating/updating tables');
    vehicle_filter_log('SQL for vehicle_base:', $sql1);
    vehicle_filter_log('SQL for engine:', $sql2);
    vehicle_filter_log('SQL for vehicle_engine:', $sql3);

    dbDelta($sql1);
    dbDelta($sql2);
    dbDelta($sql3);

    // The following code for truncating and importing CSV data is now removed as per new requirements.
    // vehicle_filter_log('Truncating tables before import');
    // ... (truncate and import logic removed) ...

    vehicle_filter_log('Plugin activation completed');
}

// Enqueue scripts
add_action('wp_enqueue_scripts', 'vehicle_filter_enqueue_scripts');

function vehicle_filter_enqueue_scripts()
{
    wp_enqueue_script('jquery');
    wp_enqueue_script(
        'vehicle-filter-script',
        plugins_url('js/vehicle-filter.js', __FILE__),
        array('jquery'),
        time(),
        true
    );

    wp_localize_script(
        'vehicle-filter-script',
        'vehicleFilterAjax',
        array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vehicle_filter_nonce'),
            'shop_url' => get_permalink(wc_get_page_id('shop'))
        )
    );
}

// Add shortcode for the filter form
add_shortcode('vehicle_filter_form', 'vehicle_filter_form_shortcode');

function vehicle_filter_form_shortcode()
{
    ob_start();
?>
    <div class="find_car_parts" id="find_car_parts">
        <div class="find_car_parts_form">
            <form id="vehicle-filter-form">
                <div class="fcp2" id="fcp2"><span>Search your vehicle</span></div>

                <div class="fcp-row">
                    <div class="fcp-col">
                        <label for="make" class="fcp-label">Select car maker</label>
                        <select name="make" id="make">
                            <option value="">Select Make</option>
                        </select>
                    </div>

                    <div class="fcp-col">
                        <label for="model" class="fcp-label">Select car model</label>
                        <select name="model" id="model" disabled>
                            <option value="">Select Model</option>
                        </select>
                    </div>

                    <div class="fcp-col">
                        <label for="listing" class="fcp-label">Select listing</label>
                        <select name="listing" id="listing" disabled>
                            <option value="">Select Listing</option>
                        </select>
                    </div>

                    <div class="fcp-col">
                        <label for="year" class="fcp-label">Select year</label>
                        <select name="year" id="year" disabled>
                            <option value="">Select Year</option>
                        </select>
                    </div>

                    <div class="fcp-col fcp-col-12">
                        <label for="engine" class="fcp-label">Select engine type</label>
                        <select name="engine" id="engine" disabled>
                            <option value="">Select Engine</option>
                        </select>
                    </div>

                    <div class="fcp-col fcp-col-12">
                        <button type="submit" class="button btn-primary search-button vehicle-filter-btn">
                            Search Parts
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php

    return ob_get_clean();
}

// Register AJAX actions
add_action('wp_ajax_filter_products', 'filter_products_ajax');
add_action('wp_ajax_nopriv_filter_products', 'filter_products_ajax');

// Register other AJAX actions
add_action('wp_ajax_get_makes', 'get_makes');
add_action('wp_ajax_nopriv_get_makes', 'get_makes');

add_action('wp_ajax_get_models', 'get_models');
add_action('wp_ajax_nopriv_get_models', 'get_models');

add_action('wp_ajax_get_listings', 'get_listings');
add_action('wp_ajax_nopriv_get_listings', 'get_listings');

add_action('wp_ajax_get_years', 'get_years');
add_action('wp_ajax_nopriv_get_years', 'get_years');

add_action('wp_ajax_get_engines', 'get_engines');
add_action('wp_ajax_nopriv_get_engines', 'get_engines');

add_action('wp_ajax_get_vehicle_id', 'get_vehicle_id');
add_action('wp_ajax_nopriv_get_vehicle_id', 'get_vehicle_id');

// Add logging function
// Enable or disable logging
global $enable_log;
$enable_log = false; // Set to true to enable logging

function vehicle_filter_log($message, $data = null)
{
    global $enable_log;

    if (!$enable_log) {
        error_log('Vehicle Filter: Logging is disabled');
        return;
    }

    try {
        // Get plugin directory path
        $plugin_dir = dirname(__FILE__);
        $log_dir = $plugin_dir . '/logs';

        // Log directory path for debugging
        error_log('Vehicle Filter: Log directory path: ' . $log_dir);

        // Create logs directory if it doesn't exist
        if (!file_exists($log_dir)) {
            error_log('Vehicle Filter: Creating logs directory');
            if (!wp_mkdir_p($log_dir)) {
                error_log('Vehicle Filter: Failed to create logs directory at ' . $log_dir);
                return;
            }
        }

        // Ensure directory is writable
        if (!is_writable($log_dir)) {
            error_log('Vehicle Filter: Logs directory is not writable at ' . $log_dir);
            return;
        }

        $log_file = $log_dir . '/vehicle-filter-debug.log';
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[$timestamp] $message";

        if ($data !== null) {
            $log_message .= "\nData: " . print_r($data, true);
        }

        $log_message .= "\n" . str_repeat('-', 80) . "\n";

        // Write to log file
        if (file_put_contents($log_file, $log_message, FILE_APPEND) === false) {
            error_log('Vehicle Filter: Failed to write to log file at ' . $log_file);
            error_log('Vehicle Filter: PHP error: ' . error_get_last()['message']);
        } else {
            error_log('Vehicle Filter: Successfully wrote to log file');
        }
    } catch (Exception $e) {
        error_log('Vehicle Filter: Error in logging - ' . $e->getMessage());
        error_log('Vehicle Filter: Stack trace: ' . $e->getTraceAsString());
    }
}

// Add function to ensure terms exist and are assigned
function ensure_vehicle_terms($product_id, $make, $model, $listing, $year, $engine)
{
    vehicle_filter_log('Ensuring terms for product', array(
        'product_id' => $product_id,
        'make' => $make,
        'model' => $model,
        'listing' => $listing,
        'year' => $year,
        'engine' => $engine
    ));

    // Create and assign make term
    $make_term = term_exists($make, 'make');
    if (!$make_term) {
        $make_term = wp_insert_term($make, 'make');
    }
    if (!is_wp_error($make_term)) {
        wp_set_object_terms($product_id, $make_term['term_id'], 'make');
    }

    // Create and assign model term
    $model_term = term_exists($model, 'model');
    if (!$model_term) {
        $model_term = wp_insert_term($model, 'model');
    }
    if (!is_wp_error($model_term)) {
        wp_set_object_terms($product_id, $model_term['term_id'], 'model');
    }

    // Create and assign listing term
    $listing_term = term_exists($listing, 'listing');
    if (!$listing_term) {
        $listing_term = wp_insert_term($listing, 'listing');
    }
    if (!is_wp_error($listing_term)) {
        wp_set_object_terms($product_id, $listing_term['term_id'], 'listing');
    }

    // Create and assign year term
    $year_term = term_exists($year, 'date_range');
    if (!$year_term) {
        $year_term = wp_insert_term($year, 'date_range');
    }
    if (!is_wp_error($year_term)) {
        wp_set_object_terms($product_id, $year_term['term_id'], 'date_range');
    }

    // Create and assign engine term
    $engine_term = term_exists($engine, 'engine');
    if (!$engine_term) {
        $engine_term = wp_insert_term($engine, 'engine');
    }
    if (!is_wp_error($engine_term)) {
        wp_set_object_terms($product_id, $engine_term['term_id'], 'engine');
    }

    // Log the assigned terms
    $assigned_terms = array(
        'make' => wp_get_post_terms($product_id, 'make'),
        'model' => wp_get_post_terms($product_id, 'model'),
        'listing' => wp_get_post_terms($product_id, 'listing'),
        'date_range' => wp_get_post_terms($product_id, 'date_range'),
        'engine' => wp_get_post_terms($product_id, 'engine')
    );
    vehicle_filter_log('Assigned terms for product', $assigned_terms);
}

// Add function to process all products
function process_all_products()
{
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $product_id = get_the_ID();

            // Get product attributes
            $product = wc_get_product($product_id);
            $attributes = $product->get_attributes();

            // Extract vehicle attributes
            $make = isset($attributes['make']) ? $attributes['make']->get_options()[0] : '';
            $model = isset($attributes['model']) ? $attributes['model']->get_options()[0] : '';
            $listing = isset($attributes['listing']) ? $attributes['listing']->get_options()[0] : '';
            $year = isset($attributes['date_range']) ? $attributes['date_range']->get_options()[0] : '';
            $engine = isset($attributes['engine']) ? $attributes['engine']->get_options()[0] : '';

            if ($make && $model && $listing && $year && $engine) {
                ensure_vehicle_terms($product_id, $make, $model, $listing, $year, $engine);
            }
        }
    }

    wp_reset_postdata();
}

// Add action to process products on plugin activation
register_activation_hook(__FILE__, 'process_all_products');

// Update filter_products_ajax to use term IDs
function filter_products_ajax()
{
    check_ajax_referer('vehicle_filter_nonce', 'nonce');

    // Get all filter values from POST
    $make = isset($_POST['make']) ? sanitize_text_field($_POST['make']) : '';
    $model = isset($_POST['model']) ? sanitize_text_field($_POST['model']) : '';
    $listing = isset($_POST['listing']) ? sanitize_text_field($_POST['listing']) : '';
    $year = isset($_POST['year']) ? intval($_POST['year']) : 0;
    $engine = isset($_POST['engine']) ? sanitize_text_field($_POST['engine']) : '';

    vehicle_filter_log('Filter values:', array(
        'make' => $make,
        'model' => $model,
        'listing' => $listing,
        'year' => $year,
        'engine' => $engine
    ));

    // Get vehicle_id using the new table structure
    global $wpdb;
    $vehicle_base = $wpdb->prefix . 'vehicle_base';
    $vehicle_engine = $wpdb->prefix . 'vehicle_engine';
    $engine = $wpdb->prefix . 'engine';

    $vehicle_query = $wpdb->prepare(
        "SELECT vb.vehicle_id
        FROM $vehicle_base AS vb
        JOIN $vehicle_engine AS ve ON ve.vehicle_id = vb.vehicle_id
        JOIN $engine AS e ON e.engine_id = ve.engine_id
        WHERE vb.make = %s
        AND vb.model = %s
        AND vb.listing = %s
        AND %d BETWEEN vb.year_from AND vb.year_to
        AND e.engine_code = %s
        LIMIT 1",
        $make,
        $model,
        $listing,
        $year,
        $engine
    );

    vehicle_filter_log('Vehicle ID query:', $vehicle_query);

    $vehicle_id = $wpdb->get_var($vehicle_query);

    vehicle_filter_log('Found vehicle_id:', $vehicle_id);

    if (!$vehicle_id) {
        vehicle_filter_log('No vehicle found with the selected criteria');
        wp_send_json_success(array(
            'vehicle_id' => null,
            'products' => array()
        ));
        return;
    }

    // Query products that have this vehicle_id in their vehicle_no attribute
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'tax_query' => array(
            array(
                'taxonomy' => 'pa_vehicle_no',
                'field' => 'name',
                'terms' => $vehicle_id
            )
        )
    );

    try {
        $query = new WP_Query($args);
        $sql = $query->request;
        vehicle_filter_log('Generated SQL query:', $sql);

        $filtered_products = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $product_id = get_the_ID();
                $product = wc_get_product($product_id);

                $filtered_products[] = array(
                    'id' => $product_id,
                    'title' => get_the_title(),
                    'link' => get_permalink(),
                    'image' => get_the_post_thumbnail_url($product_id, 'thumbnail'),
                    'price' => $product->get_price_html()
                );
            }
            wp_reset_postdata();
        } else {
            vehicle_filter_log('No products found with vehicle_no attribute:', $vehicle_id);
        }

        vehicle_filter_log('Filtered products count:', count($filtered_products));

        wp_send_json_success(array(
            'vehicle_id' => $vehicle_id,
            'products' => $filtered_products
        ));
    } catch (Exception $e) {
        vehicle_filter_log('Error in filter_products_ajax:', $e->getMessage());
        wp_send_json_error(array(
            'message' => 'An error occurred while filtering products',
            'error' => $e->getMessage()
        ));
    }
}

// Register custom taxonomies
add_action('init', 'register_vehicle_taxonomies');
function register_vehicle_taxonomies()
{
    $taxonomies = array(
        'make' => 'Make',
        'model' => 'Model',
        'listing' => 'Listing',
        'date_range' => 'Year',
        'engine' => 'Engine'
    );

    foreach ($taxonomies as $taxonomy => $label) {
        if (!taxonomy_exists($taxonomy)) {
            register_taxonomy(
                $taxonomy,
                'product',
                array(
                    'label' => $label,
                    'hierarchical' => true,
                    'show_ui' => true,
                    'show_in_menu' => false,
                    'query_var' => true,
                    'rewrite' => array('slug' => $taxonomy),
                    'show_admin_column' => true,
                    'update_count_callback' => '_update_post_term_count'
                )
            );
        }
    }

    // Register pa_vehicle_no taxonomy
    if (!taxonomy_exists('pa_vehicle_no')) {
        register_taxonomy(
            'pa_vehicle_no',
            'product',
            array(
                'label' => 'Vehicle No',
                'hierarchical' => false,
                'show_ui' => true,
                'show_in_menu' => false,
                'query_var' => true,
                'rewrite' => array('slug' => 'vehicle-no'),
                'show_admin_column' => true,
                'update_count_callback' => '_update_post_term_count'
            )
        );
    }
}

// Remove the old pre_get_posts filter since we're using AJAX now
remove_action('pre_get_posts', 'apply_vehicle_filters');

// Add debug information
add_action('wp_footer', 'debug_vehicle_filters');
function debug_vehicle_filters()
{
    if (is_shop() || is_product_category()) {
        if (current_user_can('administrator')) {
            echo '<div style="display:none;">';
            echo '<h3>Debug Information:</h3>';
            echo '<pre>';
            print_r($_GET);
            echo '</pre>';

            global $wp_query;
            echo '<h3>Query Vars:</h3>';
            echo '<pre>';
            print_r($wp_query->query_vars);
            echo '</pre>';

            echo '<h3>Tax Query:</h3>';
            echo '<pre>';
            print_r($wp_query->tax_query);
            echo '</pre>';
            echo '</div>';
        }
    }
}

// Add function to update CSV file
function update_vehicle_csv()
{
    $plugin_dir = plugin_dir_path(__FILE__);
    $csv_file = $plugin_dir . 'demo-sheet-cleaned.csv';

    // Create the CSV file if it doesn't exist
    if (!file_exists($csv_file)) {
        $csv_content = "vehicle_id,make,model,listing,date_range,engine\n";
        file_put_contents($csv_file, $csv_content);
    }

    // Update the CSV file with new data
    $csv_content = file_get_contents($csv_file);
    if ($csv_content === false) {
        return false;
    }

    return true;
}

// Add action to update CSV on plugin activation
register_activation_hook(__FILE__, 'update_vehicle_csv');

// Add function to get CSV data
function get_vehicle_csv_data()
{
    $csv_file = plugin_dir_path(__FILE__) . 'demo-sheet-cleaned.csv';
    $data = array();

    if (file_exists($csv_file)) {
        $handle = fopen($csv_file, 'r');
        if ($handle !== FALSE) {
            // Skip header row
            fgetcsv($handle);

            while (($row = fgetcsv($handle)) !== FALSE) {
                $data[] = array(
                    'vehicle_id' => $row[0],
                    'make' => $row[1],
                    'model' => $row[2],
                    'listing' => $row[3],
                    'date_range' => $row[4],
                    'engine' => $row[5]
                );
            }
            fclose($handle);
        }
    }

    return $data;
}

function get_makes()
{
    vehicle_filter_log('get_makes function called');
    check_ajax_referer('vehicle_filter_nonce', 'nonce');
    global $wpdb;
    $table_name = $wpdb->prefix . 'vehicle_base';

    // Debug log
    vehicle_filter_log('Getting makes from table: ' . $table_name);

    $makes = $wpdb->get_col("SELECT DISTINCT make FROM $table_name ORDER BY make");
    // Filter out null, empty, and 'null' string values
    $makes = array_filter($makes, function ($make) {
        return $make !== null && $make !== '' && strtolower($make) !== 'null';
    });
    $makes = array_values($makes); // Re-index array

    vehicle_filter_log('Found makes:', $makes);

    wp_send_json_success($makes);
}

function get_models()
{
    check_ajax_referer('vehicle_filter_nonce', 'nonce');
    global $wpdb;
    $table_name = $wpdb->prefix . 'vehicle_base';
    $make = trim($_POST['make']);

    // Debug log
    vehicle_filter_log('Getting models for make: ' . $make);

    $models = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT model FROM $table_name WHERE make = %s ORDER BY model",
        $make
    ));

    // Debug log
    vehicle_filter_log('Found models:', $models);

    wp_send_json_success($models);
}

function get_listings()
{
    check_ajax_referer('vehicle_filter_nonce', 'nonce');
    global $wpdb;
    $table_name = $wpdb->prefix . 'vehicle_base';
    $make = trim($_POST['make']);
    $model = trim($_POST['model']);

    // Debug log
    vehicle_filter_log('Getting listings for make: ' . $make . ', model: ' . $model);

    $listings = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT listing FROM $table_name WHERE make = %s AND model = %s ORDER BY listing",
        $make,
        $model
    ));

    // Debug log
    vehicle_filter_log('Found listings:', $listings);

    wp_send_json_success($listings);
}

function get_years()
{
    check_ajax_referer('vehicle_filter_nonce', 'nonce');
    global $wpdb;
    $table_name = $wpdb->prefix . 'vehicle_base';
    $make = Trim($_POST['make']);
    $model = Trim($_POST['model']);
    $listing = Trim($_POST['listing']);

    // Debug log
    vehicle_filter_log('Getting years for make: ' . $make . ', model: ' . $model . ', listing: ' . $listing);

    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT year_from, year_to FROM $table_name WHERE make = %s AND model = %s AND listing = %s",
        $make,
        $model,
        $listing
    ));

    // Debug log
    vehicle_filter_log('Found year ranges:', $rows);

    $years = array();
    foreach ($rows as $row) {
        $from = intval($row->year_from);
        $to = intval($row->year_to);
        for ($y = $from; $y <= $to; $y++) {
            $years[$y] = true;
        }
    }
    $years = array_keys($years);
    sort($years);

    // Debug log
    vehicle_filter_log('Generated years array:', $years);

    wp_send_json_success($years);
}

function get_engines()
{
    check_ajax_referer('vehicle_filter_nonce', 'nonce');
    global $wpdb;
    $vehicle_base = $wpdb->prefix . 'vehicle_base';
    $vehicle_engine = $wpdb->prefix . 'vehicle_engine';
    $engine = $wpdb->prefix . 'engine';

    $make = trim($_POST['make']);
    $model = trim($_POST['model']);
    $listing = trim($_POST['listing']);
    $year = intval($_POST['year']);

    // Debug log
    vehicle_filter_log('Getting engines for:', array(
        'make' => $make,
        'model' => $model,
        'listing' => $listing,
        'year' => $year
    ));

    // Find all vehicle_ids matching make/model/listing/year
    $vehicle_query = $wpdb->prepare(
        "SELECT vehicle_id FROM $vehicle_base WHERE make = %s AND model = %s AND listing = %s AND year_from <= %d AND year_to >= %d",
        $make,
        $model,
        $listing,
        $year,
        $year
    );

    vehicle_filter_log('Vehicle ID query:', $vehicle_query);
    $vehicle_ids = $wpdb->get_col($vehicle_query);
    vehicle_filter_log('Found vehicle_ids:', $vehicle_ids);

    if (empty($vehicle_ids)) {
        vehicle_filter_log('No vehicle IDs found');
        wp_send_json_success([]);
        return;
    }

    // Find all engine_ids for these vehicle_ids
    $in_ids = implode(',', array_map('intval', $vehicle_ids));
    $engine_query = "SELECT DISTINCT engine_id FROM $vehicle_engine WHERE vehicle_id IN ($in_ids)";
    vehicle_filter_log('Engine ID query:', $engine_query);
    $engine_ids = $wpdb->get_col($engine_query);
    vehicle_filter_log('Found engine_ids:', $engine_ids);

    if (empty($engine_ids)) {
        vehicle_filter_log('No engine IDs found');
        wp_send_json_success([]);
        return;
    }

    $in_engines = implode(',', array_map('intval', $engine_ids));
    $engine_code_query = "SELECT engine_code FROM $engine WHERE engine_id IN ($in_engines)";
    vehicle_filter_log('Engine code query:', $engine_code_query);
    $engine_codes = $wpdb->get_col($engine_code_query);
    vehicle_filter_log('Found engine codes:', $engine_codes);

    wp_send_json_success($engine_codes);
}

// Add pre_get_posts hook to filter products
add_action('pre_get_posts', 'filter_products_by_vehicle');
function filter_products_by_vehicle($query)
{
    if (!is_admin() && $query->is_main_query() && (is_shop() || is_product_category())) {
        $vehicle_id = '';
        if (isset($_COOKIE['vehicle_id'])) {
            $vehicle_id = sanitize_text_field($_COOKIE['vehicle_id']);
        }
        if ($vehicle_id) {
            $tax_query = $query->get('tax_query');
            if (!is_array($tax_query))
                $tax_query = array();
            $tax_query[] = array(
                'taxonomy' => 'pa_vehicle_no',
                'field' => 'name',
                'terms' => $vehicle_id,
                'operator' => 'IN'
            );
            $query->set('tax_query', $tax_query);
        }
    }
}

// Add loading state to shop page
add_action('wp_head', 'add_vehicle_filter_loading_state');
function add_vehicle_filter_loading_state()
{
    if (is_shop() || is_product_category()) {
    ?>
        <style>
            .products {
                opacity: 0;
                transition: opacity 0.3s ease-in-out;
            }

            .products.loaded {
                opacity: 1;
            }
        </style>
    <?php
    }
}

// Add script to handle loading state
add_action('wp_footer', 'add_vehicle_filter_loading_script');
function add_vehicle_filter_loading_script()
{
    if (is_shop() || is_product_category()) {
    ?>
        <script>
            jQuery(document).ready(function($) {
                // Show products once they're loaded
                $('.products').addClass('loaded');
            });
        </script>
        <?php
    }
}

// Add debug output to footer
add_action('wp_footer', 'debug_vehicle_filter_output');
function debug_vehicle_filter_output()
{
    if (is_shop() || is_product_category()) {
        if (current_user_can('administrator')) {
            global $wp_query;
            echo '<div style="display:none;" id="vehicle-filter-debug">';
            echo '<h3>Vehicle Filter Debug Info:</h3>';
            echo '<pre>';
            echo 'URL vehicle_id: ' . (isset($_GET['vehicle_id']) ? $_GET['vehicle_id'] : 'none') . "\n";
            echo 'Query vars: ' . print_r($wp_query->query_vars, true) . "\n";
            echo 'Tax query: ' . print_r($wp_query->tax_query, true) . "\n";

            // Add product visibility debug info
            if (isset($_GET['vehicle_id'])) {
                global $wpdb;
                $vehicle_id = sanitize_text_field($_GET['vehicle_id']);
                $product_ids = $wpdb->get_col($wpdb->prepare(
                    "SELECT tr.object_id 
                    FROM {$wpdb->term_relationships} tr 
                    INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id 
                    INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id 
                    WHERE tt.taxonomy = 'pa_vehicle_no' 
                    AND t.name = %s",
                    $vehicle_id
                ));
                echo 'Products with vehicle_id ' . $vehicle_id . ': ' . count($product_ids) . "\n";
                echo 'Product IDs: ' . implode(', ', $product_ids) . "\n";
            }

            echo '</pre>';
            echo '</div>';

            // Add JavaScript to show debug info
        ?>
            <!-- <script>
                jQuery(document).ready(function ($) {
                    console.log('Vehicle Filter Debug Info:', $('#vehicle-filter-debug').text());
                });
            </script> -->
        <?php
        }
    }
}

function get_vehicle_id()
{
    check_ajax_referer('vehicle_filter_nonce', 'nonce');
    global $wpdb;
    $make = isset($_POST['make']) ? trim($_POST['make']) : '';
    $model = isset($_POST['model']) ? trim($_POST['model']) : '';
    $listing = isset($_POST['listing']) ? trim($_POST['listing']) : '';
    $year = isset($_POST['year']) ? intval($_POST['year']) : 0;
    $engine = isset($_POST['engine']) ? trim($_POST['engine']) : '';
    $vehicle_base = $wpdb->prefix . 'vehicle_base';
    $vehicle_engine = $wpdb->prefix . 'vehicle_engine';
    $engine_table = $wpdb->prefix . 'engine';

    // Log the input parameters
    vehicle_filter_log('Getting vehicle_id for:', array(
        'make' => $make,
        'model' => $model,
        'listing' => $listing,
        'year' => $year,
        'engine' => $engine
    ));

    $vehicle_query = $wpdb->prepare(
        "SELECT vb.vehicle_id
        FROM $vehicle_base AS vb
        JOIN $vehicle_engine AS ve ON ve.vehicle_id = vb.vehicle_id
        JOIN $engine_table AS e ON e.engine_id = ve.engine_id
        WHERE vb.make = %s
        AND vb.model = %s
        AND vb.listing = %s
        AND %d BETWEEN vb.year_from AND vb.year_to
        AND e.engine_code = %s
        LIMIT 1",
        $make,
        $model,
        $listing,
        $year,
        $engine
    );

    // Log the query
    vehicle_filter_log('Vehicle ID query:', $vehicle_query);

    $vehicle_id = $wpdb->get_var($vehicle_query);

    // Log the result
    vehicle_filter_log('Found vehicle_id:', $vehicle_id);

    if ($vehicle_id) {
        wp_send_json_success(['vehicle_id' => $vehicle_id]);
    } else {
        wp_send_json_success(['vehicle_id' => null]);
    }
}

// Add filter for product titles
add_filter('the_title', 'modify_product_title_with_vehicle', 10, 2);

function modify_product_title_with_vehicle($title, $post_id)
{
    // Only modify product titles
    if (get_post_type($post_id) !== 'product') {
        return $title;
    }
    // No JS injection here!
    return $title;
}

// Add JS to footer to modify product titles based on vehicleFilter
add_action('wp_footer', function () {
    if (is_shop() || is_product_category() || is_product()) { ?>
        <script>
            (function() {
                const vehicleFilter = localStorage.getItem('vehicleFilter');
                if (vehicleFilter) {
                    try {
                        const vehicleData = JSON.parse(vehicleFilter);
                        const vehicleInfo =
                            `${vehicleData.make}, ${vehicleData.model}, ${vehicleData.listing}, ${vehicleData.year}, ${vehicleData.engine}`;
                        document.querySelectorAll('.product_title').forEach(
                            function(element) {
                                if (!element.hasAttribute('data-original-title')) {
                                    element.setAttribute('data-original-title', element.textContent);
                                }
                                element.textContent =
                                    `${element.getAttribute('data-original-title')} for ${vehicleInfo}`;
                            });
                    } catch (e) {
                        console.error('Error parsing vehicleFilter:', e);
                    }
                }
            })();
        </script>
    <?php }
});

// --- WooCommerce: Attach vehicle info to cart, show in cart/checkout, and pass to order ---

// Store vehicle info in cart item meta
add_filter('woocommerce_add_cart_item_data', function ($cart_item_data, $product_id, $variation_id) {
    foreach (['make', 'model', 'listing', 'year', 'engine'] as $key) {
        if (isset($_POST['vehicle_' . $key])) {
            $cart_item_data['vehicle_' . $key] = sanitize_text_field($_POST['vehicle_' . $key]);
        }
    }
    return $cart_item_data;
}, 10, 3);

// Show vehicle info in cart/checkout
add_filter('woocommerce_get_item_data', function ($item_data, $cart_item) {
    $vehicle_keys = ['make', 'model', 'listing', 'year', 'engine'];
    $vehicle_data = [];
    foreach ($vehicle_keys as $key) {
        if (!empty($cart_item['vehicle_' . $key])) {
            //$vehicle_data[] = ucfirst($key) . ': ' . esc_html($cart_item['vehicle_' . $key]);
            $vehicle_data[] = '<p><span class="vehicle-label">' . ucfirst($key) . ' : </span> ' .
                '<span class="vehicle-value">' . esc_html($cart_item['vehicle_' . $key]) . '</span></p>';
        }
    }

    if (!empty($vehicle_data)) {
        $item_data[] = [
            'name' => __('Compatible with ', 'vehicle-filter'),
            'value' => implode('', $vehicle_data),
            'display' => implode('', $vehicle_data),
        ];
    }

    return $item_data;
}, 10, 2);

// Copy vehicle info to order item meta
add_action('woocommerce_checkout_create_order_line_item', function ($item, $cart_item_key, $values, $order) {
    foreach (['make', 'model', 'listing', 'year', 'engine'] as $key) {
        if (!empty($values['vehicle_' . $key])) {
            $item->add_meta_data('Vehicle ' . ucfirst($key), $values['vehicle_' . $key], true);
        }
    }
}, 10, 4);

// Enqueue JS to add vehicle info to add-to-cart form
add_action('wp_footer', function () {
    if (is_product() || is_shop() || is_product_category()) { ?>
        <script>
            jQuery(function($) {
                // Intercept add to cart form submit
                $('body').on('submit', 'form.cart', function(e) {
                    var $form = $(this);
                    var vehicleFilter = localStorage.getItem('vehicleFilter');
                    if (vehicleFilter) {
                        try {
                            var vehicleData = JSON.parse(vehicleFilter);
                            ['make', 'model', 'listing', 'year', 'engine'].forEach(function(key) {
                                if ($form.find('input[name="vehicle_' + key + '"]').length === 0) {
                                    $('<input>')
                                        .attr('type', 'hidden')
                                        .attr('name', 'vehicle_' + key)
                                        .val(vehicleData[key] || '')
                                        .appendTo($form);
                                }
                            });
                        } catch (e) {
                            console.error('Error parsing vehicleFilter:', e);
                        }
                    }
                });
            });
        </script>
<?php }
});
