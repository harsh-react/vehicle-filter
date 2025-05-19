jQuery(document).ready(function($) {
    // Define debugLog FIRST
    function debugLog(message, data) {
        console.log('Vehicle Filter Debug:', message, data);
    }

    // Helper: Add vehicle_id to URL and reload
    function applyVehicleFilter(vehicle_id) {
        debugLog('Applying vehicle filter:', vehicle_id);
        const currentUrl = new URL(window.location.href);
        if (currentUrl.searchParams.get('vehicle_id') !== vehicle_id) {
            currentUrl.searchParams.set('vehicle_id', vehicle_id);
            window.location.href = currentUrl.toString();
        }
    }

    // On page load: sync vehicle_id between URL and localStorage
    const urlParams = new URLSearchParams(window.location.search);
    const urlVehicleId = urlParams.get('vehicle_id');
    const storedVehicleId = localStorage.getItem('vehicle_id');
    debugLog('Initial vehicle IDs:', { urlVehicleId, storedVehicleId });

    if (urlVehicleId) {
        localStorage.setItem('vehicle_id', urlVehicleId);
        debugLog('Using vehicle_id from URL:', urlVehicleId);
    } else if (storedVehicleId) {
        debugLog('Applying stored vehicle_id:', storedVehicleId);
        applyVehicleFilter(storedVehicleId);
        return; // Stop further execution, page will reload
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
        debugLog('Fetch API get_makes response:', data);
        var select = document.getElementById('make');
        if (data.success && Array.isArray(data.data) && select) {
            select.innerHTML = '<option value="">Select Make</option>';
            data.data.forEach(function(make) {
                var opt = document.createElement('option');
                opt.value = make;
                opt.text = make;
                select.appendChild(opt);
            });
            select.disabled = false;
        }
    })
    .catch(error => {
        debugLog('Fetch API get_makes error:', error);
    });

    // Event listeners for cascading dropdowns
    document.getElementById('make').addEventListener('change', function() {
        var make = this.value;
        var modelSelect = document.getElementById('model');
        var listingSelect = document.getElementById('listing');
        var yearSelect = document.getElementById('year');
        var engineSelect = document.getElementById('engine');
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
            debugLog('Fetch API get_models response:', data);
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

    document.getElementById('model').addEventListener('change', function() {
        var make = document.getElementById('make').value;
        var model = this.value;
        var listingSelect = document.getElementById('listing');
        var yearSelect = document.getElementById('year');
        var engineSelect = document.getElementById('engine');
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
            debugLog('Fetch API get_listings response:', data);
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

    document.getElementById('listing').addEventListener('change', function() {
        var make = document.getElementById('make').value;
        var model = document.getElementById('model').value;
        var listing = this.value;
        var yearSelect = document.getElementById('year');
        var engineSelect = document.getElementById('engine');
        // Reset next selects
        yearSelect.innerHTML = '<option value="">Select Year</option>';
        yearSelect.disabled = true;
        engineSelect.innerHTML = '<option value="">Select Engine</option>';
        engineSelect.disabled = true;
        if (!listing) return;
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
            debugLog('Fetch API get_years response:', data);
            if (data.success && Array.isArray(data.data)) {
                yearSelect.innerHTML = '<option value="">Select Year</option>';
                data.data.forEach(function(year) {
                    var opt = document.createElement('option');
                    opt.value = year;
                    opt.text = year;
                    yearSelect.appendChild(opt);
                });
                yearSelect.disabled = false;
            }
        });
    });

    document.getElementById('year').addEventListener('change', function() {
        var make = document.getElementById('make').value;
        var model = document.getElementById('model').value;
        var listing = document.getElementById('listing').value;
        var year = this.value;
        var engineSelect = document.getElementById('engine');
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
            debugLog('Fetch API get_engines response:', data);
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

    // Form submit: get vehicle_id, then reload with filter
    document.getElementById('vehicle-filter-form').addEventListener('submit', function(e) {
        e.preventDefault();
        var make = document.getElementById('make').value;
        var model = document.getElementById('model').value;
        var listing = document.getElementById('listing').value;
        var year = document.getElementById('year').value;
        var engine = document.getElementById('engine').value;
        if (!make || !model || !listing || !year || !engine) {
            alert('Please select all vehicle details before searching for parts.');
            return;
        }
        // Step 1: Get vehicle_id from backend
        fetch(vehicleFilterAjax.ajaxurl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
            body: new URLSearchParams({
                action: 'get_vehicle_id',
                make: make,
                model: model,
                listing: listing,
                year: year,
                engine: engine,
                nonce: vehicleFilterAjax.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            debugLog('Fetch API get_vehicle_id response:', data);
            var vehicle_id = data.success && data.data && data.data.vehicle_id ? data.data.vehicle_id : null;
            if (!vehicle_id) {
                alert('No vehicle found for the selected criteria. Please try different options.');
                return;
            }
            // Store vehicle_id in localStorage
            localStorage.setItem('vehicle_id', vehicle_id);
            applyVehicleFilter(vehicle_id); // Reload with vehicle_id in URL
        });
    });

    // Load saved data on page load
    const savedData = localStorage.getItem('vehicleFilter');
    const savedVehicleId = localStorage.getItem('vehicle_id');
    if (savedData && savedVehicleId) {
        const data = JSON.parse(savedData);
        if (data.make) {
            document.getElementById('make').value = data.make;
            // Optionally trigger change event if needed
        }
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
    debugLog('Loading makes...');
        $.ajax({
            url: vehicleFilterAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_makes',
                nonce: vehicleFilterAjax.nonce
            },
            success: function(response) {
            debugLog('Makes response:', response);
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
    debugLog('Loading models for make:', make);
        $.ajax({
            url: vehicleFilterAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_models',
                make: make,
                nonce: vehicleFilterAjax.nonce
            },
            success: function(response) {
            debugLog('Models response:', response);
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
    debugLog('Loading listings for make/model:', {make, model});
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
            debugLog('Listings response:', response);
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
    debugLog('Loading years for make/model/listing:', {make, model, listing});
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
            debugLog('Years response:', response);
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
    debugLog('Loading engines for make/model/listing/year:', {make, model, listing, year});
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
            debugLog('Engines response:', response);
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
    debugLog('Resetting selects:', selectIds);
        selectIds.forEach(id => {
            $(`#${id}`).empty().append(`<option value="">Select ${id.charAt(0).toUpperCase() + id.slice(1)}</option>`).prop('disabled', true);
        });
    }

    function saveToLocalStorage() {
        const formData = {
            make: $('#make').val(),
            model: $('#model').val(),
            listing: $('#listing').val(),
            year: $('#year').val(),
            engine: $('#engine').val()
        };
    debugLog('Saving to localStorage:', formData);
        localStorage.setItem('vehicleFilter', JSON.stringify(formData));
    }

// Show a message below the form
function showVehicleFoundMessage() {
    var form = document.getElementById('vehicle-filter-form');
    if (form) {
        var msg = document.createElement('div');
        msg.className = 'vehicle-found-message';
        msg.style.margin = '20px 0';
        msg.style.color = '#0c7c2c';
        msg.style.fontWeight = 'bold';
        msg.textContent = 'We have found your vehicle.';
        // Remove any previous message
        var prev = document.querySelector('.vehicle-found-message');
        if (prev) prev.remove();
        form.parentNode.insertBefore(msg, form.nextSibling);
    }
}
});