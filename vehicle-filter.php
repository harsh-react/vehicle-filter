<?php

/**

 * Plugin Name: Vehicle Filter

 * Description: A plugin to filter products based on vehicle data

 * Version: 1.0.0

 * Author: Elate

 */



use PhpOffice\PhpSpreadsheet\Calculation\TextData\Trim;



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



    // Log table truncation

    vehicle_filter_log('Truncating tables before import');



    // Temporarily disable foreign key checks

    $wpdb->query('SET FOREIGN_KEY_CHECKS = 0');



    // Truncate tables in correct order

    $truncate_queries = array(

        "TRUNCATE TABLE $vehicle_engine",

        "TRUNCATE TABLE $vehicle_base",

        "TRUNCATE TABLE $engine"

    );



    foreach ($truncate_queries as $query) {

        vehicle_filter_log('Executing truncate query:', $query);

        $wpdb->query($query);
    }



    // Re-enable foreign key checks

    $wpdb->query('SET FOREIGN_KEY_CHECKS = 1');



    // Import CSV data if files exist

    $plugin_dir = plugin_dir_path(__FILE__);

    $data_dir = $plugin_dir . 'data/';

    $vehicle_base_csv = $data_dir . 'vehicle_base.csv';

    $engine_csv = $data_dir . 'engine_table.csv';

    $vehicle_engine_csv = $data_dir . 'vehicle_engine.csv';



    // Log CSV file paths

    vehicle_filter_log('CSV file paths:', array(

        'vehicle_base' => $vehicle_base_csv,

        'engine' => $engine_csv,

        'vehicle_engine' => $vehicle_engine_csv

    ));



    // Import vehicle_base

    if (file_exists($vehicle_base_csv)) {

        vehicle_filter_log('Starting vehicle_base import');

        $handle = fopen($vehicle_base_csv, 'r');

        if ($handle !== FALSE) {

            fgetcsv($handle); // skip header

            $row_count = 0;

            $current_year = date('Y'); // Get current year

            while (($data = fgetcsv($handle)) !== FALSE) {

                $year_to = !empty($data[5]) ? floatval($data[5]) : floatval($current_year); // Use current year if year_to is empty

                $insert_data = array(

                    'vehicle_id' => intval($data[0]),

                    'make' => $data[1],

                    'model' => $data[2],

                    'listing' => $data[3],

                    'year_from' => floatval($data[4]),

                    'year_to' => $year_to

                );

                // Only insert if make is not empty or whitespace

                if (!empty(trim($data[1]))) {

                    $wpdb->insert($vehicle_base, $insert_data);

                    $row_count++;
                }
            }

            fclose($handle);

            vehicle_filter_log("Vehicle base import completed. Imported $row_count rows");
        } else {

            vehicle_filter_log('Error: Could not open vehicle_base.csv file');
        }
    } else {

        vehicle_filter_log('Warning: vehicle_base.csv file not found');
    }



    // Import engine

    if (file_exists($engine_csv)) {

        vehicle_filter_log('Starting engine import');

        $handle = fopen($engine_csv, 'r');

        if ($handle !== FALSE) {

            fgetcsv($handle); // skip header

            $row_count = 0;

            while (($data = fgetcsv($handle)) !== FALSE) {

                $insert_data = array(

                    'engine_id' => intval($data[0]),

                    'engine_code' => $data[1]

                );

                $wpdb->insert($engine, $insert_data);

                $row_count++;
            }

            fclose($handle);

            vehicle_filter_log("Engine import completed. Imported $row_count rows");
        } else {

            vehicle_filter_log('Error: Could not open engine_table.csv file');
        }
    } else {

        vehicle_filter_log('Warning: engine_table.csv file not found');
    }



    // Import vehicle_engine

    if (file_exists($vehicle_engine_csv)) {

        vehicle_filter_log('Starting vehicle_engine import');

        $handle = fopen($vehicle_engine_csv, 'r');

        if ($handle !== FALSE) {

            fgetcsv($handle); // skip header

            $row_count = 0;

            while (($data = fgetcsv($handle)) !== FALSE) {

                $insert_data = array(

                    'vehicle_id' => intval($data[0]),

                    'engine_id' => intval($data[1])

                );

                $wpdb->insert($vehicle_engine, $insert_data);

                $row_count++;
            }

            fclose($handle);

            vehicle_filter_log("Vehicle engine import completed. Imported $row_count rows");
        } else {

            vehicle_filter_log('Error: Could not open vehicle_engine.csv file');
        }
    } else {

        vehicle_filter_log('Warning: vehicle_engine.csv file not found');
    }



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

        
        <div class="fcp1">
            <h2>Find Car Parts</h2>
            <p>ENTER YOUR NUMBER PLATE BELOW</p>
            <div id="reg-search-error" class="error-message" style="display: none; color: #dc3232; margin: 10px 0; padding: 10px; background: #f8f8f8; border-left: 4px solid #dc3232;"></div>
            <form id="reg-search-form" onsubmit="return false;">
                <div class="label_search_number">Search by number plate</div>
                <div class="fcp1_content">
                    <span class="enter_reg_input"><input type="text" id="enter_reg" name="enter_reg" placeholder="Enter REG" class="form-input" /></span>
                    <button type="submit" class="button reg-search-button btn-black">Go</button>
                </div>
            </form>
        </div> 
    

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

                        <button type="submit" class="button btn-primary search-button vehicle-filter-btn">Search

                            Parts</button>

                    </div>

                </div>

            </form>

        </div>

    </div>

    <?php

    return ob_get_clean();
}



// Create the JavaScript file

function create_vehicle_filter_js()

{

    $js_dir = plugin_dir_path(__FILE__) . 'js';

    if (!file_exists($js_dir)) {

        mkdir($js_dir, 0755, true);
    }



    $js_content = <<<EOT

    jQuery(document).ready(function($) {

        // Define saveToLocalStorage function first

        function saveToLocalStorage() {

            const formData = {

                make: $('#make').val(),

                model: $('#model').val(),

                listing: $('#listing').val(),

                year: $('#year').val(),

                engine: $('#engine').val()

            };

            localStorage.setItem('vehicleFilter', JSON.stringify(formData));

        }



        // Load makes on page load

        loadMakes();



        // Make change event

        $('#make').on('change', function() {

            const make = $(this).val();

            if (make) {

                loadModels(make);

            } else {

                resetSelects(['model', 'listing', 'year', 'engine']);

            }

            saveToLocalStorage();

        });



        // Model change event

        $('#model').on('change', function() {

            const make = $('#make').val();

            const model = $(this).val();

            if (model) {

                loadListings(make, model);

            } else {

                resetSelects(['listing', 'year', 'engine']);

            }

            saveToLocalStorage();

        });



        // Listing change event

        $('#listing').on('change', function() {

            const make = $('#make').val();

            const model = $('#model').val();

            const listing = $(this).val();

            if (listing) {

                loadYears(make, model, listing);

            } else {

                resetSelects(['year', 'engine']);

            }

            saveToLocalStorage();

        });



        // Year change event

        $('#year').on('change', function() {

            const make = $('#make').val();

            const model = $('#model').val();

            const listing = $('#listing').val();

            const year = $(this).val();

            if (year) {

                loadEngines(make, model, listing, year);

            } else {

                resetSelects(['engine']);

            }

            saveToLocalStorage();

        });



        // Engine change event

        $('#engine').on('change', function() {

            saveToLocalStorage();

        });



        // Form submit: save to localStorage and apply filter

        $('#vehicle-filter-form').on('submit', function(e) {

            e.preventDefault();

        

            const formData = {

                make: $('#make').val(),

                model: $('#model').val(),

                listing: $('#listing').val(),

                year: $('#year').val(),

                engine: $('#engine').val()

            };

            

            // Validate that all fields are filled

            if (!formData.make || !formData.model || !formData.listing || !formData.year || !formData.engine) {

                console.log('Please fill in all fields');

                alert('Please select all vehicle details before searching for parts.');

                return;

            }

            

            // Get vehicle_id from localStorage

            const vehicle_id = localStorage.getItem('vehicle_id');

            console.log('Using vehicle_id from localStorage:', vehicle_id);

            

            if (!vehicle_id) {

                console.log('No vehicle_id found in localStorage');

                alert('Please select a valid vehicle first.');

                return;

            }

            

            // Save to localStorage

            localStorage.setItem('vehicleFilter', JSON.stringify(formData));

            

            // Apply filters and get vehicle_id

            $.ajax({

                url: vehicleFilterAjax.ajaxurl,

                type: 'POST',

                data: {

                    action: 'filter_products',

                    nonce: vehicleFilterAjax.nonce,

                    vehicle_id: vehicle_id,

                    make: formData.make,

                    model: formData.model,

                    listing: formData.listing,

                    year: formData.year,

                    engine: formData.engine

                },

                success: function(response) {

                    // Logging for debugging redirect

                    console.log('vehicleFilterAjax.shop_url:', vehicleFilterAjax.shop_url);

                    console.log('vehicle_id:', vehicle_id);

                    var redirectUrl = vehicleFilterAjax.shop_url + '?vehicle_id=' + vehicle_id;

                    console.log('Redirecting to:', redirectUrl);

                    window.location.href = vehicleFilterAjax.shop_url + '?vehicle_id=' + vehicle_id;

                    if (response.success) {

                        console.log("Got it");

                        // Update products display

                        if (response.data.products && response.data.products.length > 0) {

                            console.log('Found products:', response.data.products);

                            // Display products here

                            displayProducts(response.data.products);

                        } else {

                            console.log('No products found');

                            // Show no products message

                            displayNoProducts();

                        }

                    }

                },

                error: function(xhr, status, error) {

                    console.error('Error:', error);

                    alert('An error occurred while searching for parts. Please try again.');

                }

            });

        });



        // Load saved data on page load

        const savedData = localStorage.getItem('vehicleFilter');

        const vehicle_id = localStorage.getItem('vehicle_id');

        

        if (savedData && vehicle_id) {

            const data = JSON.parse(savedData);

            if (data.make) {

                $('#make').val(data.make).trigger('change');

                setTimeout(() => {

                    if (data.model) {

                        $('#model').val(data.model).trigger('change');

                        setTimeout(() => {

                            if (data.listing) {

                                $('#listing').val(data.listing).trigger('change');

                                setTimeout(() => {

                                    if (data.year) {

                                        $('#year').val(data.year).trigger('change');

                                        setTimeout(() => {

                                            if (data.engine) {

                                                $('#engine').val(data.engine);

                                            }

                                        }, 500);

                                    }

                                }, 500);

                            }

                        }, 500);

                    }

                }, 500);

            }

        }

    });



    function displayProducts(products) {

        const container = $('.products');

        if (container.length) {

            let html = '<div class="products-grid">';

            products.forEach(product => {

                html += `

                    <div class="product-item">

                        <a href="\${product.link}">

                            <img src="\${product.image}" alt="\${product.title}">

                            <h3>\${product.title}</h3>

                            <div class="price">\${product.price}</div>

                        </a>

                    </div>

                `;

            });

            html += '</div>';

            container.html(html);

        }

    }



    function displayNoProducts() {

        const container = $('.products');

        if (container.length) {

            container.html('<div class="no-products">No products found for the selected vehicle.</div>');

        }

    }



    function loadMakes() {

        $.ajax({

            url: vehicleFilterAjax.ajaxurl,

            type: 'POST',

            data: {

                action: 'get_makes',

                nonce: vehicleFilterAjax.nonce

            },

            success: function(response) {

                if (response.success) {

                    const makes = response.data;

                    const select = $('#make');

                    makes.forEach(make => {

                        select.append(`<option value="\${make}">\${make}</option>`);

                    });

                }

            }

        });

    }



    function loadModels(make) {

        $.ajax({

            url: vehicleFilterAjax.ajaxurl,

            type: 'POST',

            data: {

                action: 'get_models',

                make: make,

                nonce: vehicleFilterAjax.nonce

            },

            success: function(response) {

                if (response.success) {

                    const models = response.data;

                    const select = $('#model');

                    select.empty().append('<option value="">Select Model</option>');

                    models.forEach(model => {

                        select.append(`<option value="\${model}">\${model}</option>`);

                    });

                    select.prop('disabled', false);

                }

            }

        });

    }



    function loadListings(make, model) {

        $.ajax({

            url: vehicleFilterAjax.ajaxurl,

            type: 'POST',

            data: {

                action: 'get_listings',

                make: make,

                model: model,

                nonce: vehicleFilterAjax.nonce

            },

            success: function(response) {

                if (response.success) {

                    const listings = response.data;

                    const select = $('#listing');

                    select.empty().append('<option value="">Select Listing</option>');

                    listings.forEach(listing => {

                        select.append(`<option value="\${listing}">\${listing}</option>`);

                    });

                    select.prop('disabled', false);

                }

            }

        });

    }



    function loadYears(make, model, listing) {

        $.ajax({

            url: vehicleFilterAjax.ajaxurl,

            type: 'POST',

            data: {

                action: 'get_years',

                make: make,

                model: model,

                listing: listing,

                nonce: vehicleFilterAjax.nonce

            },

            success: function(response) {

                if (response.success) {

                    const dateRanges = response.data;

                    const select = $('#year');

                    select.empty().append('<option value="">Select Year</option>');

                    dateRanges.forEach(dateRange => {

                        select.append(`<option value="\${dateRange}">\${dateRange}</option>`);

                    });

                    select.prop('disabled', false);

                }

            }

        });

    }



    function loadEngines(make, model, listing, year) {

        $.ajax({

            url: vehicleFilterAjax.ajaxurl,

            type: 'POST',

            data: {

                action: 'get_engines',

                make: make,

                model: model,

                listing: listing,

                year: year,

                nonce: vehicleFilterAjax.nonce

            },

            success: function(response) {

                if (response.success) {

                    const engines = response.data;

                    const select = $('#engine');

                    select.empty().append('<option value="">Select Engine</option>');

                    engines.forEach(engine => {

                        select.append(`<option value="\${engine}">\${engine}</option>`);

                    });

                    select.prop('disabled', false);

                }

            }

        });

    }



    function resetSelects(selectIds) {

        selectIds.forEach(id => {

            $(`#\${id}`).empty().append(`<option value="">Select \${id.charAt(0).toUpperCase() + id.slice(1)}</option>`).prop('disabled', true);

        });

    }

});

EOT;



    file_put_contents($js_dir . '/vehicle-filter.js', $js_content);
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

$enable_log = true; // Set to true to enable logging



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

    // echo '<pre>';

    error_log(json_encode($makes));

    // echo '</pre>';



    // Debug log

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

        if (isset($_GET['vehicle_id'])) {

            $vehicle_id = sanitize_text_field($_GET['vehicle_id']);

            if (!session_id())

                session_start();

            $_SESSION['vehicle_id'] = $vehicle_id;
        } elseif (isset($_SESSION['vehicle_id'])) {

            $vehicle_id = $_SESSION['vehicle_id'];
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



// Add new AJAX action for vehicle lookup
add_action('wp_ajax_lookup_vehicle_by_reg', 'lookup_vehicle_by_reg');
add_action('wp_ajax_nopriv_lookup_vehicle_by_reg', 'lookup_vehicle_by_reg');

function lookup_vehicle_by_reg() {
    check_ajax_referer('vehicle_filter_nonce', 'nonce');
    
    $reg_number = isset($_POST['reg_number']) ? sanitize_text_field($_POST['reg_number']) : '';
    
    if (empty($reg_number)) {
        wp_send_json_error('Registration number is required');
        return;
    }

    $api_key = '4aa9e04e-038e-4613-a396-9afc471c216d';
    $package_name = 'VehicleDetails';
    
    $url = 'https://uk.api.vehicledataglobal.com/r2/lookup?' . http_build_query([
        'packageName' => $package_name,
        'apikey' => $api_key,
        'vrm' => $reg_number
    ]);

    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        wp_send_json_error('Error making API request: ' . $response->get_error_message());
        return;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!$data || !isset($data['Results']) || !isset($data['Results']['VehicleDetails']) || !isset($data['Results']['ModelDetails'])) {
        wp_send_json_error('Invalid response from vehicle lookup service');
        return;
    }

    wp_send_json_success($data);
}
