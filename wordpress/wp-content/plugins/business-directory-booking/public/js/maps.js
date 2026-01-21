/* Google Maps Integration JavaScript */
(function($) {
    'use strict';

    // Initialize map on archive page
    window.initArchiveMap = function() {
        const mapElement = document.getElementById('businessesMap');
        if (!mapElement) return;

        const map = new google.maps.Map(mapElement, {
            center: { lat: 37.7749, lng: -122.4194 }, // Default: San Francisco
            zoom: 12,
            mapTypeControl: true,
            streetViewControl: true,
            fullscreenControl: true
        });

        const bounds = new google.maps.LatLngBounds();
        const markers = [];

        // Get all business markers from data attribute
        const businessesData = mapElement.dataset.businesses;
        if (!businessesData) return;

        const businesses = JSON.parse(businessesData);

        businesses.forEach(function(business) {
            const position = { lat: parseFloat(business.latitude), lng: parseFloat(business.longitude) };
            
            const marker = new google.maps.Marker({
                position: position,
                map: map,
                title: business.title,
                animation: google.maps.Animation.DROP
            });

            bounds.extend(position);
            markers.push(marker);

            const infoWindow = new google.maps.InfoWindow({
                content: `
                    <div style="max-width: 200px; padding: 10px;">
                        ${business.thumbnail ? '<img src="' + business.thumbnail + '" style="width: 100%; height: 120px; object-fit: cover; border-radius: 4px; margin-bottom: 10px;">' : ''}
                        <strong style="font-size: 16px;">${business.title}</strong><br>
                        <div style="margin: 8px 0;">
                            <a href="${business.url}" style="color: #2271b1; text-decoration: none; font-weight: 600;">View Details →</a>
                        </div>
                    </div>
                `
            });

            marker.addListener('click', function() {
                infoWindow.open(map, marker);
            });
        });

        if (businesses.length > 0) {
            map.fitBounds(bounds);
        }
    };

    // Near Me functionality
    $('#nearMeBtn').on('click', function() {
        const button = $(this);
        
        if (!navigator.geolocation) {
            alert('Geolocation is not supported by your browser');
            return;
        }

        button.prop('disabled', true).text('Getting location...');

        navigator.geolocation.getCurrentPosition(
            function(position) {
                const latitude = position.coords.latitude;
                const longitude = position.coords.longitude;
                
                // Redirect to archive with location params
                window.location.href = '<?php echo home_url('/business/'); ?>?latitude=' + latitude + '&longitude=' + longitude + '&near_me=1';
            },
            function(error) {
                button.prop('disabled', false).text('📍 Near Me');
                alert('Unable to get your location: ' + error.message);
            }
        );
    });

    // Distance filter
    $('#distanceFilter').on('change', function() {
        const distance = $(this).val();
        const urlParams = new URLSearchParams(window.location.search);
        
        if (distance) {
            urlParams.set('distance', distance);
        } else {
            urlParams.delete('distance');
        }
        
        window.location.search = urlParams.toString();
    });

    // Toggle map/list view
    $('#toggleMapView').on('click', function() {
        const mapContainer = $('#mapViewContainer');
        const listContainer = $('#listViewContainer');
        const button = $(this);

        if (mapContainer.is(':visible')) {
            mapContainer.hide();
            listContainer.show();
            button.text('🗺️ Show Map');
        } else {
            mapContainer.show();
            listContainer.hide();
            button.text('📋 Show List');
            
            // Initialize map if not already done
            if (typeof google !== 'undefined' && !mapContainer.data('initialized')) {
                initArchiveMap();
                mapContainer.data('initialized', true);
            }
        }
    });

})(jQuery);
