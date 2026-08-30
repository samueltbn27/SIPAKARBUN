import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerIconRetina from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';
import { getCases, hasValidCaseCoordinates } from './data-provider';
import {
    closeCaseDetail,
    formatDateTime,
    getOpenCaseId,
    initializeCaseDetailDrawer,
    openCaseDetail,
} from './case-detail';
import {
    applyFilters,
    countActiveFilters,
    createFilterState,
    getDistrictsForRegency,
    getUniqueCommodities,
    getUniqueDiseases,
    getUniquePopts,
    getUniqueRegencies,
} from './filters';
import { getStatusConfig, getStatusOptions } from './statuses';

L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIconRetina,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

const WEST_JAVA_VIEW = [-6.9175, 107.6191];
const WEST_JAVA_ZOOM = 9;

function appendPopupField(list, label, value) {
    const row = document.createElement('div');
    row.className = 'flex items-start justify-between gap-4 border-t border-[#eef3ef] pt-2';

    const fieldLabel = document.createElement('dt');
    fieldLabel.className = 'text-[10px] uppercase tracking-wide text-[#89968e]';
    fieldLabel.textContent = label;

    const fieldValue = document.createElement('dd');
    fieldValue.className = 'text-right text-xs font-semibold text-[#526159]';
    fieldValue.textContent = value || '-';

    row.append(fieldLabel, fieldValue);
    list.append(row);
}

function createCasePopup(caseData) {
    const popup = document.createElement('div');
    popup.className = 'min-w-[210px] text-[#173b29]';
    const statusConfig = getStatusConfig(caseData.status);

    const title = document.createElement('h3');
    title.className = 'text-sm font-bold text-[#173b29]';
    title.textContent = caseData.case_code;

    const farmerGroup = document.createElement('p');
    farmerGroup.className = 'mt-1 text-xs font-medium text-[#526159]';
    farmerGroup.textContent = caseData.kelompok_tani?.nama || '-';

    const statusBadge = document.createElement('span');
    statusBadge.className = `mt-2 inline-flex w-fit rounded-full px-2.5 py-1 text-[10px] font-bold ${statusConfig.badgeClass}`;
    statusBadge.textContent = statusConfig.label;

    const fields = document.createElement('dl');
    fields.className = 'mt-3 space-y-2';
    appendPopupField(fields, 'Komoditas', caseData.komoditas?.nama);
    appendPopupField(fields, 'Penyakit', caseData.penyakit?.nama);
    appendPopupField(fields, 'POPT', caseData.popt?.nama);
    appendPopupField(fields, 'Status', statusConfig.label);
    appendPopupField(fields, 'Update terakhir', formatDateTime(caseData.last_status_at));

    const detailButton = document.createElement('button');
    detailButton.type = 'button';
    detailButton.className = 'mt-4 w-full rounded-lg bg-[#176b45] px-3 py-2 text-xs font-bold text-white transition hover:bg-[#125437] focus:outline-none focus:ring-2 focus:ring-[#b8d7c3]';
    detailButton.textContent = 'Lihat Detail';
    detailButton.addEventListener('click', () => openCaseDetail(caseData));

    popup.append(title, farmerGroup, statusBadge, fields, detailButton);

    return popup;
}

function createStatusIcon(status) {
    const config = getStatusConfig(status);

    return L.divIcon({
        className: 'webgis-status-marker',
        html: `<span class="inline-flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold shadow-md ring-2 ring-white ${config.markerClass}" aria-hidden="true">${config.markerSymbol}</span>`,
        iconSize: [32, 32],
        iconAnchor: [16, 16],
        popupAnchor: [0, -14],
    });
}

function renderCaseMarker(caseLayer, caseData) {
    if (!hasValidCaseCoordinates(caseData)) {
        return null;
    }

    return L.marker([caseData.latitude, caseData.longitude], {
        alt: caseData.case_code || 'Lokasi kasus',
        icon: createStatusIcon(caseData.status),
        title: caseData.case_code || 'Lokasi kasus',
    })
        .addTo(caseLayer)
        .bindPopup(createCasePopup(caseData));
}

function clearMarkers(caseLayer) {
    caseLayer.clearLayers();
}

function fitMapToMarkers(map, markers) {
    if (markers.length > 1) {
        const bounds = L.latLngBounds(markers.map((marker) => marker.getLatLng()));
        map.fitBounds(bounds, { padding: [24, 24], maxZoom: 12 });
    } else if (markers.length === 1) {
        map.setView(markers[0].getLatLng(), 12);
    }
}

function renderCases(map, caseLayer, cases) {
    clearMarkers(caseLayer);

    const markers = cases
        .map((caseData) => renderCaseMarker(caseLayer, caseData))
        .filter(Boolean);

    fitMapToMarkers(map, markers);

    return markers.length;
}

function initializeMap(container) {
    const map = L.map(container).setView(WEST_JAVA_VIEW, WEST_JAVA_ZOOM);
    const caseLayer = L.layerGroup().addTo(map);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19,
    }).addTo(map);

    return { map, caseLayer };
}

function setSelectOptions(select, options, defaultLabel) {
    if (!select) {
        return;
    }

    select.replaceChildren();

    const defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.textContent = defaultLabel;
    select.append(defaultOption);

    options.forEach(({ value, label }) => {
        const option = document.createElement('option');
        option.value = value;
        option.textContent = label;
        select.append(option);
    });
}

function setFilterErrorState() {
    document.querySelectorAll('[data-webgis-filter]').forEach((select) => {
        setSelectOptions(select, [], 'Data tidak tersedia');
        select.disabled = true;
        select.setAttribute('aria-invalid', 'true');
    });

    const resetButton = document.querySelector('[data-webgis-reset]');

    if (resetButton) {
        resetButton.disabled = true;
    }
}

function renderStatusLegend() {
    const legend = document.querySelector('[data-webgis-status-legend]');

    if (!legend) {
        return;
    }

    legend.replaceChildren();

    getStatusOptions().forEach(({ value, label }) => {
        const config = getStatusConfig(value);
        const item = document.createElement('div');
        item.className = 'flex items-center gap-3 rounded-lg border border-[#eef3ef] bg-[#f7faf8] px-3 py-2.5';

        const marker = document.createElement('span');
        marker.className = `flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full text-xs font-bold ring-2 ring-white ${config.markerClass}`;
        marker.textContent = config.markerSymbol;
        marker.setAttribute('aria-hidden', 'true');

        const labelElement = document.createElement('span');
        labelElement.className = 'text-sm font-medium text-[#526159]';
        labelElement.textContent = label;

        item.append(marker, labelElement);
        legend.append(item);
    });
}

function getFilterControls() {
    return {
        status: document.querySelector('[data-webgis-filter="status"]'),
        commodity: document.querySelector('[data-webgis-filter="commodity"]'),
        regency: document.querySelector('[data-webgis-filter="regency"]'),
        district: document.querySelector('[data-webgis-filter="district"]'),
        disease: document.querySelector('[data-webgis-filter="disease"]'),
        popt: document.querySelector('[data-webgis-filter="popt"]'),
    };
}

function refreshDistrictOptions(controls, filters, cases) {
    if (!filters.regency) {
        filters.district = '';
        controls.district.disabled = true;
        setSelectOptions(controls.district, [], 'Pilih Kabupaten terlebih dahulu');
        return;
    }

    const districtOptions = getDistrictsForRegency(cases, filters.regency);
    const selectedDistrict = filters.district;

    setSelectOptions(controls.district, districtOptions, 'Semua Kecamatan');
    controls.district.disabled = false;

    if (districtOptions.some((option) => option.value === selectedDistrict)) {
        controls.district.value = selectedDistrict;
    } else {
        filters.district = '';
        controls.district.value = '';
    }
}

function updateActiveFilterSummary(activeFilterCount) {
    const summary = document.querySelector('[data-webgis-active-filter-count]');

    if (summary) {
        summary.textContent = `Filter aktif: ${activeFilterCount}`;
    }
}

export function initializeWebGIS(container, cases) {
    const { map, caseLayer } = initializeMap(container);
    initializeCaseDetailDrawer();
    const controls = getFilterControls();
    const resetButton = document.querySelector('[data-webgis-reset]');
    const caseCount = document.querySelector('[data-webgis-case-count]');
    const emptyMessage = document.querySelector('[data-webgis-empty]');
    const datasetEmptyMessage = document.querySelector('[data-webgis-dataset-empty]');
    const filterState = createFilterState();
    const mappableCases = cases.filter(hasValidCaseCoordinates);

    setSelectOptions(controls.status, getStatusOptions(), 'Semua Status');
    setSelectOptions(controls.commodity, getUniqueCommodities(mappableCases), 'Semua Komoditas');
    setSelectOptions(controls.disease, getUniqueDiseases(mappableCases), 'Semua Penyakit');
    setSelectOptions(controls.popt, getUniquePopts(mappableCases), 'Semua POPT');
    setSelectOptions(controls.regency, getUniqueRegencies(mappableCases), 'Semua Kabupaten/Kota');
    refreshDistrictOptions(controls, filterState, mappableCases);
    renderStatusLegend();

    const renderFilteredCases = () => {
        const filteredCases = applyFilters(mappableCases, filterState);
        const markerCount = renderCases(map, caseLayer, filteredCases);
        const currentOpenCaseId = getOpenCaseId();

        if (
            currentOpenCaseId !== null
            && !filteredCases.some((caseData) => String(caseData.case_id) === String(currentOpenCaseId))
        ) {
            closeCaseDetail();
        }

        if (caseCount) {
            caseCount.textContent = `Menampilkan ${markerCount} dari ${mappableCases.length} kasus`;
        }

        if (emptyMessage) {
            emptyMessage.hidden = mappableCases.length === 0 || markerCount !== 0;
        }

        if (datasetEmptyMessage) {
            datasetEmptyMessage.hidden = mappableCases.length !== 0;
        }

        updateActiveFilterSummary(countActiveFilters(filterState));
    };

    Object.entries(controls).forEach(([filterName, control]) => {
        control?.addEventListener('change', () => {
            filterState[filterName] = control.value;

            if (filterName === 'regency') {
                filterState.district = '';
                refreshDistrictOptions(controls, filterState, mappableCases);
            }

            renderFilteredCases();
        });
    });

    resetButton?.addEventListener('click', () => {
        Object.assign(filterState, createFilterState());

        Object.entries(controls).forEach(([filterName, control]) => {
            if (control && filterName !== 'district') {
                control.value = '';
            }
        });

        refreshDistrictOptions(controls, filterState, mappableCases);
        renderFilteredCases();
    });

    renderFilteredCases();

    window.requestAnimationFrame(() => map.invalidateSize());
}

async function loadWebGIS(container) {
    const loadingState = document.querySelector('[data-webgis-loading]');
    const errorState = document.querySelector('[data-webgis-error]');

    try {
        const cases = await getCases();

        if (loadingState) {
            loadingState.hidden = true;
        }

        initializeWebGIS(container, cases);
    } catch (error) {
        console.error('WebGIS case data could not be loaded.', error);
        setFilterErrorState();

        if (loadingState) {
            loadingState.hidden = true;
        }

        if (errorState) {
            errorState.hidden = false;
        }
    }
}

document.querySelectorAll('[data-webgis-map]').forEach((container) => loadWebGIS(container));
