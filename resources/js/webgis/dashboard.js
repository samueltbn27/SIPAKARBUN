import Chart from 'chart.js/auto';
import { getCases } from './data-provider';
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
import { getStatusOptions } from './statuses';
import {
    calculateSummary,
    groupByCommodity,
    groupByDisease,
    groupByRegency,
    groupByStatus,
} from './statistics';

const CHART_PALETTE = ['#176b45', '#3d8eb9', '#8b6cc7', '#b8860b', '#5a8d6c', '#526159', '#9b6b4d', '#7289a8'];

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

function getDashboardControls() {
    return {
        status: document.querySelector('[data-dashboard-filter="status"]'),
        commodity: document.querySelector('[data-dashboard-filter="commodity"]'),
        regency: document.querySelector('[data-dashboard-filter="regency"]'),
        district: document.querySelector('[data-dashboard-filter="district"]'),
        disease: document.querySelector('[data-dashboard-filter="disease"]'),
        popt: document.querySelector('[data-dashboard-filter="popt"]'),
    };
}

function refreshDistrictOptions(controls, filters, cases) {
    if (!filters.regency) {
        filters.district = '';
        controls.district.disabled = true;
        setSelectOptions(controls.district, [], 'Pilih Kabupaten terlebih dahulu');
        return;
    }

    const options = getDistrictsForRegency(cases, filters.regency);
    const selectedDistrict = filters.district;

    setSelectOptions(controls.district, options, 'Semua Kecamatan');
    controls.district.disabled = false;

    if (options.some((option) => option.value === selectedDistrict)) {
        controls.district.value = selectedDistrict;
    } else {
        filters.district = '';
        controls.district.value = '';
    }
}

function updateKpis(summary) {
    const kpis = {
        total: summary.total,
        active: summary.active,
        completed: summary.completed,
        postponed: summary.postponed,
    };

    Object.entries(kpis).forEach(([key, value]) => {
        const element = document.querySelector(`[data-dashboard-kpi="${key}"]`);

        if (element) {
            element.textContent = String(value);
        }
    });
}

function chartSummary(key, entries) {
    const summary = document.querySelector(`[data-dashboard-chart-summary="${key}"]`);

    if (!summary) {
        return;
    }

    const nonEmptyEntries = entries.filter((entry) => entry.count > 0);
    summary.textContent = nonEmptyEntries.length === 0
        ? 'Belum ada data pada kombinasi filter ini.'
        : nonEmptyEntries.map((entry) => `${entry.label}: ${entry.count}`).join(' · ');
}

function createChartConfig(key, entries) {
    const isStatus = key === 'status';
    const labels = entries.map((entry) => entry.label);
    const values = entries.map((entry) => entry.count);
    const colors = isStatus
        ? entries.map((entry) => entry.color)
        : entries.map((_, index) => CHART_PALETTE[index % CHART_PALETTE.length]);

    return {
        type: isStatus ? 'doughnut' : 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Jumlah kasus',
                data: values,
                backgroundColor: colors,
                borderColor: isStatus ? '#ffffff' : colors,
                borderWidth: isStatus ? 2 : 1,
                borderRadius: isStatus ? 0 : 6,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: isStatus,
                    position: 'bottom',
                    labels: { usePointStyle: true, padding: 16 },
                },
                tooltip: {
                    callbacks: {
                        label: (context) => `${context.label}: ${context.raw} kasus`,
                    },
                },
            },
            scales: isStatus ? {} : {
                x: {
                    ticks: { color: '#6f7d74' },
                    grid: { display: false },
                },
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0, color: '#6f7d74' },
                    grid: { color: '#e6eee8' },
                },
            },
        },
    };
}

function updateCharts(chartInstances, groupedData) {
    Object.entries(groupedData).forEach(([key, entries]) => {
        const canvas = document.querySelector(`[data-dashboard-chart="${key}"]`);

        if (!canvas) {
            return;
        }

        const config = createChartConfig(key, entries);

        if (!chartInstances[key]) {
            chartInstances[key] = new Chart(canvas, config);
        } else {
            chartInstances[key].data = config.data;
            chartInstances[key].options = config.options;
            chartInstances[key].update();
        }

        chartSummary(key, entries);
    });
}

export function initializeMonitoringDashboard(cases) {
    const controls = getDashboardControls();
    const resetButton = document.querySelector('[data-dashboard-reset]');
    const activeFilterCount = document.querySelector('[data-dashboard-active-filter-count]');
    const emptyState = document.querySelector('[data-dashboard-empty]');
    const datasetEmptyState = document.querySelector('[data-dashboard-dataset-empty]');
    const filters = createFilterState();
    const chartInstances = {};

    setSelectOptions(controls.status, getStatusOptions(), 'Semua Status');
    setSelectOptions(controls.commodity, getUniqueCommodities(cases), 'Semua Komoditas');
    setSelectOptions(controls.disease, getUniqueDiseases(cases), 'Semua Penyakit');
    setSelectOptions(controls.popt, getUniquePopts(cases), 'Semua POPT');
    setSelectOptions(controls.regency, getUniqueRegencies(cases), 'Semua Kabupaten/Kota');
    refreshDistrictOptions(controls, filters, cases);

    const renderDashboard = () => {
        const filteredCases = applyFilters(cases, filters);
        const summary = calculateSummary(filteredCases);

        updateKpis(summary);
        updateCharts(chartInstances, {
            status: groupByStatus(filteredCases),
            commodity: groupByCommodity(filteredCases),
            regency: groupByRegency(filteredCases),
            disease: groupByDisease(filteredCases),
        });

        if (activeFilterCount) {
            activeFilterCount.textContent = `Filter aktif: ${countActiveFilters(filters)}`;
        }

        if (emptyState) {
            emptyState.hidden = cases.length === 0 || filteredCases.length !== 0;
        }

        if (datasetEmptyState) {
            datasetEmptyState.hidden = cases.length !== 0;
        }
    };

    Object.entries(controls).forEach(([filterName, control]) => {
        control?.addEventListener('change', () => {
            filters[filterName] = control.value;

            if (filterName === 'regency') {
                filters.district = '';
                refreshDistrictOptions(controls, filters, cases);
            }

            renderDashboard();
        });
    });

    resetButton?.addEventListener('click', () => {
        Object.assign(filters, createFilterState());

        Object.entries(controls).forEach(([filterName, control]) => {
            if (control && filterName !== 'district') {
                control.value = '';
            }
        });

        refreshDistrictOptions(controls, filters, cases);
        renderDashboard();
    });

    renderDashboard();
}

async function loadMonitoringDashboard() {
    const loadingState = document.querySelector('[data-dashboard-loading]');
    const errorState = document.querySelector('[data-dashboard-error]');

    try {
        const cases = await getCases();

        if (loadingState) {
            loadingState.hidden = true;
        }

        initializeMonitoringDashboard(cases);
    } catch (error) {
        console.error('Monitoring case data could not be loaded.', error);

        if (loadingState) {
            loadingState.hidden = true;
        }

        if (errorState) {
            errorState.hidden = false;
        }
    }
}

document.querySelectorAll('[data-monitoring-dashboard]').forEach(() => loadMonitoringDashboard());
