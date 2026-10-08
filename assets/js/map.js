// assets/js/map.js — Leaflet helpers shared by the job pages.

var NUAHN_TILES = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';

// Initialize a map with optional click-to-select behavior
function initMap(mapId, options = {}) {
    const { lat = 5.6037, lng = -0.1870, zoom = 12, selectable = false, onSelect } = options;

    const map = L.map(mapId, { scrollWheelZoom: false }).setView([lat, lng], zoom);

    L.tileLayer(NUAHN_TILES, {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
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
        })
        .catch(() => {});
}

// Create a small read-only map on demand from data-lat/data-lng/data-title attributes.
function lazyMap(mapId) {
    const el = document.getElementById(mapId);
    if (!el || el._nuahnMap || typeof L === 'undefined') {
        if (el && el._nuahnMap) setTimeout(() => el._nuahnMap.invalidateSize(), 50);
        return el ? el._nuahnMap : null;
    }
    const lat = parseFloat(el.dataset.lat), lng = parseFloat(el.dataset.lng);
    if (isNaN(lat) || isNaN(lng)) return null;
    const map = initMap(mapId, { lat, lng, zoom: 13 });
    addMarker(map, lat, lng, el.dataset.title || null);
    el._nuahnMap = map;
    setTimeout(() => map.invalidateSize(), 360);
    return map;
}
