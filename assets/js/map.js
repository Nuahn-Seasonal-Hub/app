// assets/js/map.js

// Initialize a map with optional click-to-select behavior
function initMap(mapId, options = {}) {
    const { lat = 5.6037, lng = -0.1870, zoom = 12, selectable = false, onSelect } = options;

    const map = L.map(mapId).setView([lat, lng], zoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    let marker;

    if (selectable) {
        map.on('click', function (e) {
            const selectedLat = e.latlng.lat.toFixed(6);
            const selectedLng = e.latlng.lng.toFixed(6);

            if (marker) map.removeLayer(marker);
            marker = L.marker([selectedLat, selectedLng]).addTo(map);

            if (typeof onSelect === 'function') {
                onSelect(selectedLat, selectedLng);
            }
        });
    }

    return map;
}

// Add a marker to an existing map
function addMarker(map, lat, lng, popupText = null) {
    const marker = L.marker([lat, lng]).addTo(map);
    if (popupText) marker.bindPopup(popupText);
    return marker;
}

// Reverse geocode using Nominatim
function reverseGeocode(lat, lng, callback) {
    fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`)
        .then(res => res.json())
        .then(data => {
            if (typeof callback === 'function') {
                callback(data.display_name || '');
            }
        });
}