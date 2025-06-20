jQuery(document).ready(function($) {
    // Define debugLog FIRST
    function debugLog(message, data) {
        console.log('Vehicle Filter Debug:', message, data);
    }

    // Define saveToLocalStorage function
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

    // On page load: do NOT sync vehicle_id between URL and localStorage

    // Only proceed with form handling if we're on a page with the form
    const makeSelect = document.getElementById('make');
    if (!makeSelect) {
        // debugLog('Make select not found, skipping form initialization');
        return;
    }

    // Populate #make dropdown on page load
    fetch(vehicleFilterAjax.ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
        },
        body: new URLSearchParams({
            action: 'get_makes',
            nonce: vehicleFilterAjax.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        // debugLog('Fetch API get_makes response:', data);
        if (data.success && Array.isArray(data.data) && makeSelect) {
            makeSelect.innerHTML = '<option value="">Select Make</option>';
            data.data.forEach(function(make) {
                var opt = document.createElement('option');
                opt.value = make;
                opt.text = make;
                makeSelect.appendChild(opt);
            });
            makeSelect.disabled = false;
        }
    })
    .catch(error => {
        // debugLog('Fetch API get_makes error:', error);
    });

    // Event listeners for cascading dropdowns
    const modelSelect = document.getElementById('model');
    const listingSelect = document.getElementById('listing');
    const yearSelect = document.getElementById('year');
    const engineSelect = document.getElementById('engine');
    const filterForm = document.getElementById('vehicle-filter-form');

    if (makeSelect) {
        makeSelect.addEventListener('change', function() {
            var make = this.value;
            if (!modelSelect || !listingSelect || !yearSelect || !engineSelect) return;
            
            // Reset all next selects
            modelSelect.innerHTML = '<option value="">Select Model</option>';
            modelSelect.disabled = true;
            listingSelect.innerHTML = '<option value="">Select Listing</option>';
            listingSelect.disabled = true;
            yearSelect.innerHTML = '<option value="">Select Year</option>';
            yearSelect.disabled = true;
            engineSelect.innerHTML = '<option value="">Select Engine</option>';
            engineSelect.disabled = true;
            
            if (!make) return;
            
            fetch(vehicleFilterAjax.ajaxurl, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
                body: new URLSearchParams({
                    action: 'get_models',
                    make: make,
                    nonce: vehicleFilterAjax.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                // debugLog('Fetch API get_models response:', data);
                if (data.success && Array.isArray(data.data)) {
                    modelSelect.innerHTML = '<option value="">Select Model</option>';
                    data.data.forEach(function(model) {
                        var opt = document.createElement('option');
                        opt.value = model;
                        opt.text = model;
                        modelSelect.appendChild(opt);
                    });
                    modelSelect.disabled = false;
                }
            });
        });
    }

    if (modelSelect) {
        modelSelect.addEventListener('change', function() {
            var make = makeSelect ? makeSelect.value : '';
            var model = this.value;
            if (!listingSelect || !yearSelect || !engineSelect) return;
            
            // Reset next selects
            listingSelect.innerHTML = '<option value="">Select Listing</option>';
            listingSelect.disabled = true;
            yearSelect.innerHTML = '<option value="">Select Year</option>';
            yearSelect.disabled = true;
            engineSelect.innerHTML = '<option value="">Select Engine</option>';
            engineSelect.disabled = true;
            
            if (!model) return;
            
            fetch(vehicleFilterAjax.ajaxurl, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
                body: new URLSearchParams({
                    action: 'get_listings',
                    make: make,
                    model: model,
                    nonce: vehicleFilterAjax.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                // debugLog('Fetch API get_listings response:', data);
                if (data.success && Array.isArray(data.data)) {
                    listingSelect.innerHTML = '<option value="">Select Listing</option>';
                    data.data.forEach(function(listing) {
                        var opt = document.createElement('option');
                        opt.value = listing;
                        opt.text = listing;
                        listingSelect.appendChild(opt);
                    });
                    listingSelect.disabled = false;
                }
            });
        });
    }

    if (listingSelect) {
        listingSelect.addEventListener('change', function() {
            var make = makeSelect ? makeSelect.value : '';
            var model = modelSelect ? modelSelect.value : '';
            var listing = this.value;
            if (!yearSelect || !engineSelect) return;
            
            // Clean up listing value before sending
            listing = listing.replace(/\{[a-f0-9]{32,}\}/gi, '').trim();
            
            // Reset next selects
            yearSelect.innerHTML = '<option value="">Select Year</option>';
            yearSelect.disabled = true;
            engineSelect.innerHTML = '<option value="">Select Engine</option>';
            engineSelect.disabled = true;
            
            if (!listing) return;
            
            // debugLog('get_years AJAX - make/model/listing', {make, model, listing});
            fetch(vehicleFilterAjax.ajaxurl, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
                body: new URLSearchParams({
                    action: 'get_years',
                    make: make,
                    model: model,
                    listing: listing,
                    nonce: vehicleFilterAjax.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                // debugLog('get_years AJAX response', data);
                if (data.success && Array.isArray(data.data)) {
                    yearSelect.innerHTML = '<option value="">Select Year</option>';
                    data.data.forEach(function(year) {
                        // debugLog('Populating year option', year);
                        var opt = document.createElement('option');
                        opt.value = year;
                        opt.text = year;
                        yearSelect.appendChild(opt);
                    });
                    yearSelect.disabled = false;
                }
            });
        });
    }

    if (yearSelect) {
        yearSelect.addEventListener('change', function() {
            var make = makeSelect ? makeSelect.value : '';
            var model = modelSelect ? modelSelect.value : '';
            var listing = listingSelect ? listingSelect.value : '';
            var year = this.value;
            if (!engineSelect) return;
            
            // Reset next select
            engineSelect.innerHTML = '<option value="">Select Engine</option>';
            engineSelect.disabled = true;
            
            if (!year) return;
            
            fetch(vehicleFilterAjax.ajaxurl, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
                body: new URLSearchParams({
                    action: 'get_engines',
                    make: make,
                    model: model,
                    listing: listing,
                    year: year,
                    nonce: vehicleFilterAjax.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                // debugLog('Fetch API get_engines response:', data);
                if (data.success && Array.isArray(data.data)) {
                    engineSelect.innerHTML = '<option value="">Select Engine</option>';
                    data.data.forEach(function(engine) {
                        var opt = document.createElement('option');
                        opt.value = engine;
                        opt.text = engine;
                        engineSelect.appendChild(opt);
                    });
                    engineSelect.disabled = false;
                }
            });
        });
    }

    // Add message display function
    function showFormMessage(message, type = 'error') {
        const messageDiv = $('#form-message');
        if (messageDiv.length === 0) {
            $('#vehicle-filter-form').prepend('<div id="form-message" style="margin-bottom: 15px; padding: 10px; border-radius: 4px;"></div>');
        }
        const messageElement = $('#form-message');
        messageElement.html(message)
            .removeClass('error-message success-message')
            .addClass(type + '-message')
            .css({
                'background-color': type === 'error' ? '#ffebee' : '#e8f5e9',
                'color': type === 'error' ? '#c62828' : '#2e7d32',
                'border': `1px solid ${type === 'error' ? '#ef9a9a' : '#a5d6a7'}`
            })
            .show();
    }

    // Form submit: get vehicle_id, then reload with filter
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!makeSelect || !modelSelect || !listingSelect || !yearSelect || !engineSelect) return;
            
            var make = makeSelect.value;
            var model = modelSelect.value;
            var listing = listingSelect.value;
            var year = yearSelect.value;
            var engine = engineSelect.value;
            
            if (!make || !model || !listing || !year || !engine) {
                showFormMessage('Please select all vehicle details before searching for parts.');
                return;
            }

            // Disable submit button and show loading state
            const submitButton = this.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = 'Searching...';
            
            // Get vehicle_id from backend using jQuery AJAX
            $.ajax({
                url: vehicleFilterAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_vehicle_id',
                    make: make,
                    model: model,
                    listing: listing,
                    year: year,
                    engine: engine,
                    nonce: vehicleFilterAjax.nonce
                },
                success: function(response) {
                    if (response.success && response.data && response.data.vehicle_id) {
                        // Store form values in localStorage
                        localStorage.setItem('vehicleFilter', JSON.stringify({
                            make: make,
                            model: model,
                            listing: listing,
                            year: year,
                            engine: engine
                        }));

                        // Store vehicle_id in localStorage
                        localStorage.setItem('vehicle_id', response.data.vehicle_id);
                        setVehicleIdCookie(response.data.vehicle_id);
                        window.location.href = vehicleFilterAjax.shop_url;
                    } else {
                        showFormMessage('No vehicle found for the selected criteria. Please try different options.');
                        // Reset button state
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalButtonText;
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    showFormMessage('An error occurred while searching for parts. Please try again.');
                    // Reset button state
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonText;
                }
            });
        });
    }

    // Load saved data from localStorage on page load
    const savedData = localStorage.getItem('vehicleFilter');
    const savedVehicleId = localStorage.getItem('vehicle_id');
    
    if (savedData && savedVehicleId) {
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

    // On product listing page load, auto-filter if vehicle_id is present
    /*
    if (['/shop/', '/category/', '/products/'].some(path => window.location.pathname.includes(path))) {
        const persistedVehicleId = localStorage.getItem('vehicle_id');
        if (persistedVehicleId) {
            fetch(vehicleFilterAjax.ajaxurl, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
                body: new URLSearchParams({
                    action: 'filter_products',
                    vehicle_id: persistedVehicleId,
                    nonce: vehicleFilterAjax.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data && Array.isArray(data.data.products) && data.data.products.length > 0) {
                    displayProducts(data.data.products);
                } else {
                    displayNoProducts();
                }
            });
        }
    }
    */

    function displayProducts(products) {
        const container = $('.products');
        if (container.length) {
            let html = '<div class="products-grid">';
            products.forEach(product => {
                html += `
                    <div class="product-item">
                        <a href="${product.link}">
                            <img src="${product.image}" alt="${product.title}">
                            <h3>${product.title}</h3>
                            <div class="price">${product.price}</div>
                        </a>
                    </div>
                `;
            });
            html += '</div>';
            container.html(html);
        }
    }

    function displayNoProducts() {
    var container = document.querySelector('.wp-block-woocommerce-product-template, .wc-block-product-template');
    if (container) {
        container.innerHTML = '<li class="no-products" style="width:100%;text-align:center;padding:2em 0;color:#e85c0c;font-size:1.2em;">There are no products available for current vehicle.</li>';
    } else {
        var form = document.getElementById('vehicle-filter-form');
        if (form) {
            var msg = document.createElement('div');
            msg.className = 'no-products';
            msg.style.margin = '20px 0';
            msg.style.color = '#e85c0c';
            msg.textContent = 'There are no products available for current vehicle.';
            var prev = document.querySelector('.no-products');
            if (prev) prev.remove();
            form.parentNode.insertBefore(msg, form.nextSibling);
        }
        }
    }

    function loadMakes() {
    // debugLog('Loading makes...');
        $.ajax({
            url: vehicleFilterAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_makes',
                nonce: vehicleFilterAjax.nonce
            },
            success: function(response) {
            // debugLog('Makes response:', response);
                if (response.success) {
                    const makes = response.data;
                    const select = $('#make');
                select.empty().append('<option value="">Select Make</option>');
                    makes.forEach(make => {
                        select.append(`<option value="${make}">${make}</option>`);
                    });
                select.prop('disabled', false);
            } else {
                debugLog('Error loading makes:', response);
            }
        },
        error: function(xhr, status, error) {
            debugLog('AJAX error loading makes:', {xhr, status, error});
            }
        });
    }

    function loadModels(make) {
    // debugLog('Loading models for make:', make);
        $.ajax({
            url: vehicleFilterAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_models',
                make: make,
                nonce: vehicleFilterAjax.nonce
            },
            success: function(response) {
            // debugLog('Models response:', response);
                if (response.success) {
                    const models = response.data;
                    const select = $('#model');
                    select.empty().append('<option value="">Select Model</option>');
                    models.forEach(model => {
                        select.append(`<option value="${model}">${model}</option>`);
                    });
                    select.prop('disabled', false);
            } else {
                debugLog('Error loading models:', response);
            }
        },
        error: function(xhr, status, error) {
            debugLog('AJAX error loading models:', {xhr, status, error});
            }
        });
    }

    function loadListings(make, model) {
    // debugLog('Loading listings for make/model:', {make, model});
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
            // debugLog('Listings response:', response);
                if (response.success) {
                    const listings = response.data;
                    const select = $('#listing');
                    select.empty().append('<option value="">Select Listing</option>');
                    listings.forEach(listing => {
                        select.append(`<option value="${listing}">${listing}</option>`);
                    });
                    select.prop('disabled', false);
            } else {
                debugLog('Error loading listings:', response);
            }
        },
        error: function(xhr, status, error) {
            debugLog('AJAX error loading listings:', {xhr, status, error});
            }
        });
    }

    function loadYears(make, model, listing) {
    // debugLog('Loading years for make/model/listing:', {make, model, listing});
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
            // debugLog('Years response:', response);
                if (response.success) {
                    const years = response.data;
                    const select = $('#year');
                    select.empty().append('<option value="">Select Year</option>');
                    years.forEach(year => {
                        select.append(`<option value="${year}">${year}</option>`);
                    });
                    select.prop('disabled', false);
            } else {
                debugLog('Error loading years:', response);
            }
        },
        error: function(xhr, status, error) {
            debugLog('AJAX error loading years:', {xhr, status, error});
            }
        });
    }

    function loadEngines(make, model, listing, year) {
    // debugLog('Loading engines for make/model/listing/year:', {make, model, listing, year});
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
            // debugLog('Engines response:', response);
                if (response.success) {
                    const engines = response.data;
                    const select = $('#engine');
                    select.empty().append('<option value="">Select Engine</option>');
                    engines.forEach(engine => {
                        select.append(`<option value="${engine}">${engine}</option>`);
                    });
                    select.prop('disabled', false);
            } else {
                debugLog('Error loading engines:', response);
            }
        },
        error: function(xhr, status, error) {
            debugLog('AJAX error loading engines:', {xhr, status, error});
            }
        });
    }

    function resetSelects(selectIds) {
    // debugLog('Resetting selects:', selectIds);
        selectIds.forEach(id => {
            $(`#${id}`).empty().append(`<option value="">Select ${id.charAt(0).toUpperCase() + id.slice(1)}</option>`).prop('disabled', true);
        });
    }

    // Load makes on page load
    loadMakes();

    // Check for cookie data on page load
    if (typeof vehicleCookieData !== 'undefined' && vehicleCookieData) {
        // Restore form state from cookie
        $('#make').val(vehicleCookieData.make).trigger('change');
        setTimeout(() => {
            $('#model').val(vehicleCookieData.model).trigger('change');
            setTimeout(() => {
                $('#listing').val(vehicleCookieData.listing).trigger('change');
                setTimeout(() => {
                    $('#year').val(vehicleCookieData.year).trigger('change');
                    setTimeout(() => {
                        $('#engine').val(vehicleCookieData.engine);
                    }, 500);
                }, 500);
            }, 500);
        }, 500);
    }

    // Make change event
    $('#make').on('change', function() {
        const make = $(this).val();
        if (make) {
            loadModels(make);
        } else {
            resetSelects(['model', 'listing', 'year', 'engine']);
        }
        // saveToLocalStorage();
    });

    // Remove the old form submit handler that was causing the issue
    $('#vehicle-filter-form').off('submit').on('submit', function(e) {
        e.preventDefault();
        // The form submission is now handled by the filterForm event listener above
    });

    // Add vehicle summary box above the form if not present
    if ($('#vehicle-summary').length === 0) {
        $('#vehicle-filter-form').before('<div id="vehicle-summary" style="display:none;"></div>');
    }

    function showVehicleSummary() {
        const vehicleData = JSON.parse(localStorage.getItem('vehicleFilter'));
        if (!vehicleData) return;
        // Build summary string
        const summary = `${vehicleData.make}, ${vehicleData.model}, ${vehicleData.listing},${vehicleData.year}, ${vehicleData.engine}`;
        const html = `
            <div class="vehicle-summary-box">
            <div class="vehicle-summary-title-action">
                <div class="vehicle_b1">
                    <h4>We have found your vehicle</h4>
                </div>
                <div class="vehicle-summary-header">
                    <div class="vehicle_b">
                        <div class="vs_h">Vehicle Make: </div>
                        <div class="vs_t">${vehicleData.make}</div>
                    </div>
                    <div class="vehicle_b">
                        <div class="vs_h">Model: </div>
                        <div class="vs_t">${vehicleData.model}</div>
                    </div>
                    <div class="vehicle_b">
                        <div class="vs_h">Listing: </div>
                        <div class="vs_t">${vehicleData.listing}</div>
                    </div>
                    <div class="vehicle_b">
                        <div class="vs_h">Year: </div>
                        <div class="vs_t">${vehicleData.year}</div>
                    </div>
                    <div class="vehicle_b">
                        <div class="vs_h">Engine: </div>
                        <div class="vs_t">${vehicleData.engine}</div>
                    </div>
                </div>
                </div>
                <div class="vehicle-summary-actions">
                        <a href="#" id="change-vehicle-btn">Change</a>
                        <a href="#" id="reset-vehicle-btn">Reset</a>
                    </div>
            </div>
        `;
        $('#vehicle-summary').html(html).show();
        $('#vehicle-filter-form').hide();
        // Change button handler
        $('#change-vehicle-btn').on('click', function(e) {
            e.preventDefault();
            // Only show the form, do not clear storage
            $('#vehicle-summary').hide();
            $('#vehicle-filter-form').show();
        });
        // Reset button handler
        $('#reset-vehicle-btn').on('click', function(e) {
            e.preventDefault();
            localStorage.removeItem('vehicle_id');
            localStorage.removeItem('vehicleFilter');
            clearVehicleIdCookie();
            window.location.href = '/shop';
        });
    }

    // On page load, if vehicle_id and vehicleFilter exist, show summary instead of form
    if (localStorage.getItem('vehicle_id') && localStorage.getItem('vehicleFilter')) {
        showVehicleSummary();
    }

    // After successful form submission, show summary (if not reloading)
    // If you want to show summary without reloading, call showVehicleSummary() after storing in localStorage and before reload
    // ... existing code ...

    // Set vehicle_id in cookie (expires in 7 days)
    function setVehicleIdCookie(vehicle_id) {
        var d = new Date();
        d.setTime(d.getTime() + (7*24*60*60*1000)); // 7 days
        var expires = "expires="+ d.toUTCString();
        document.cookie = "vehicle_id=" + vehicle_id + ";" + expires + ";path=/";
    }

    // Clear vehicle_id cookie
    function clearVehicleIdCookie() {
        document.cookie = "vehicle_id=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
    }
});