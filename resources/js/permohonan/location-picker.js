import L from 'leaflet';
import { parseCaseLocation, parseReferenceLocation } from './location-coordinates.js';

const DEFAULT_VIEW = [-6.9175, 107.6191];
const DEFAULT_ZOOM = 9;
const POKTAN_ZOOM = 13;
const LOCATION_HELP = 'Lokasi kasus otomatis mengikuti lokasi Kelompok Tani yang dipilih. Geser marker atau klik peta jika lokasi serangan berada di titik yang berbeda.';

function formatCoordinate(value) {
    return Number(value).toFixed(6);
}

function initializeLocationPicker(container) {
    const latitudeInput = document.querySelector('#latitude_kasus');
    const longitudeInput = document.querySelector('#longitude_kasus');
    const poktanSelect = document.querySelector('#kelompok-tani');
    const status = document.querySelector('[data-case-location-status]');
    const helper = document.querySelector('[data-case-location-help]');

    if (!latitudeInput || !longitudeInput) {
        return;
    }

    const map = L.map(container).setView(DEFAULT_VIEW, DEFAULT_ZOOM);
    let caseMarker = null;

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19,
    }).addTo(map);

    const showStatus = (message) => {
        if (!status) return;
        status.textContent = message;
        status.classList.remove('hidden');
    };

    const hideStatus = () => status?.classList.add('hidden');

    const setHelper = (message) => {
        if (helper) helper.textContent = message;
    };

    const setCaseLocation = (latitude, longitude, origin = 'user') => {
        const location = parseCaseLocation(latitude, longitude);
        if (location === null) {
            return;
        }

        const { lat, lng } = location;
        latitudeInput.value = formatCoordinate(lat);
        longitudeInput.value = formatCoordinate(lng);
        latitudeInput.dispatchEvent(new Event('input', { bubbles: true }));
        longitudeInput.dispatchEvent(new Event('input', { bubbles: true }));

        if (caseMarker === null) {
            caseMarker = L.marker([lat, lng], {
                draggable: true,
                title: 'Lokasi kasus',
            }).addTo(map);
            caseMarker.on('dragend', () => {
                const position = caseMarker.getLatLng();
                setCaseLocation(position.lat, position.lng);
            });
        } else {
            caseMarker.setLatLng([lat, lng]);
        }

        showStatus(origin === 'poktan'
            ? 'Lokasi awal mengikuti lokasi Poktan. Geser marker atau klik peta jika lokasi kasus berbeda.'
            : 'Lokasi kasus telah dipilih.');
        map.invalidateSize();
        map.setView([lat, lng], origin === 'poktan' ? POKTAN_ZOOM : map.getZoom());
    };

    const clearCaseLocation = () => {
        latitudeInput.value = '';
        longitudeInput.value = '';
        latitudeInput.dispatchEvent(new Event('input', { bubbles: true }));
        longitudeInput.dispatchEvent(new Event('input', { bubbles: true }));
        caseMarker?.remove();
        caseMarker = null;
        hideStatus();
        map.invalidateSize();
    };

    const initialLocation = parseCaseLocation(latitudeInput.value, longitudeInput.value);
    const hasInitialCaseLocation = initialLocation !== null;
    if (initialLocation !== null) {
        setCaseLocation(initialLocation.lat, initialLocation.lng);
        setHelper('Lokasi kasus tersimpan. Geser marker atau klik peta jika ingin mengubahnya.');
        map.setView([initialLocation.lat, initialLocation.lng], POKTAN_ZOOM);
    }

    const focusPoktan = (poktan = null) => {
        const option = poktanSelect?.selectedOptions?.[0];
        const source = poktan === null
            ? { latitude: option?.dataset.latitude, longitude: option?.dataset.longitude }
            : poktan;
        const location = parseReferenceLocation(source?.latitude, source?.longitude);

        if (location === null) {
            clearCaseLocation();
            setHelper('Koordinat Kelompok Tani belum tersedia. Silakan tentukan lokasi kasus pada peta.');
            map.setView(DEFAULT_VIEW, DEFAULT_ZOOM);
            return;
        }

        setCaseLocation(location.lat, location.lng, 'poktan');
        setHelper(LOCATION_HELP);
    };

    window.addEventListener('poktan-location-selected', (event) => focusPoktan(event.detail));
    map.on('click', (event) => setCaseLocation(event.latlng.lat, event.latlng.lng));
    window.requestAnimationFrame(() => {
        map.invalidateSize();
        if (!hasInitialCaseLocation && poktanSelect?.value) focusPoktan();
    });
}

document.querySelectorAll('[data-case-location-map]').forEach(initializeLocationPicker);
