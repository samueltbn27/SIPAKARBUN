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

const doughnutCenterLabel = {
    id: 'doughnutCenterLabel',
    afterDraw(chart) {
        if (chart.config.type !== 'doughnut' || !chart.chartArea) {
            return;
        }

        const total = chart.data.datasets[0].data.reduce((sum, value) => sum + Number(value || 0), 0);
        const { ctx, chartArea } = chart;
        const centerX = (chartArea.left + chartArea.right) / 2;
        const centerY = (chartArea.top + chartArea.bottom) / 2;

        ctx.save();
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillStyle = '#173b29';
        ctx.font = '800 26px "Plus Jakarta Sans", sans-serif';
        ctx.fillText(String(total), centerX, centerY - 7);
        ctx.fillStyle = '#89968e';
        ctx.font = '600 10px "Plus Jakarta Sans", sans-serif';
        ctx.fillText('TOTAL KASUS', centerX, centerY + 16);
        ctx.restore();
    },
};

function hexToRgba(hex, alpha) {
    const value = hex.replace('#', '');
    const red = Number.parseInt(value.slice(0, 2), 16);
    const green = Number.parseInt(value.slice(2, 4), 16);
    const blue = Number.parseInt(value.slice(4, 6), 16);

    return `rgba(${red}, ${green}, ${blue}, ${alpha})`;
}

function truncateLabel(label, maxLength = 25) {
    return label.length > maxLength ? `${label.slice(0, maxLength - 1)}…` : label;
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
    if (!controls.district) {
        return;
    }

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

    const nonEmptyEntries = entries
        .filter((entry) => entry.count > 0)
        .sort((first, second) => second.count - first.count || first.label.localeCompare(second.label, 'id'));

    summary.textContent = nonEmptyEntries.length === 0
        ? 'Belum ada data pada kombinasi filter ini.'
        : key === 'status'
            ? `${nonEmptyEntries[0].label} menjadi status terbanyak (${nonEmptyEntries[0].count} kasus).`
            : `Terbanyak: ${nonEmptyEntries.slice(0, 3).map((entry) => `${entry.label} (${entry.count})`).join(' · ')}`;
}

function createChartConfig(key, entries) {
    const isStatus = key === 'status';
    const chartEntries = isStatus
        ? entries
        : [...entries].sort((first, second) => second.count - first.count || first.label.localeCompare(second.label, 'id'));
    const labels = chartEntries.map((entry) => entry.label);
    const values = chartEntries.map((entry) => entry.count);
    const colors = isStatus
        ? chartEntries.map((entry) => entry.color)
        : chartEntries.map((_, index) => CHART_PALETTE[index % CHART_PALETTE.length]);

    return {
        type: isStatus ? 'doughnut' : 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Jumlah kasus',
                data: values,
                backgroundColor: isStatus
                    ? colors
                    : (context) => {
                        const chartArea = context.chart.chartArea;

                        if (!chartArea) {
                            return colors[context.dataIndex];
                        }

                        const gradient = context.chart.ctx.createLinearGradient(chartArea.left, 0, chartArea.right, 0);
                        const color = colors[context.dataIndex];
                        gradient.addColorStop(0, hexToRgba(color, .35));
                        gradient.addColorStop(1, hexToRgba(color, .95));

                        return gradient;
                    },
                borderColor: isStatus ? '#ffffff' : colors,
                borderWidth: isStatus ? 3 : 0,
                borderRadius: isStatus ? 0 : 7,
                hoverOffset: isStatus ? 5 : 0,
                spacing: isStatus ? 3 : 0,
                maxBarThickness: isStatus ? undefined : 20,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: isStatus ? 'x' : 'y',
            animation: {
                duration: 650,
                easing: 'easeOutQuart',
            },
            interaction: {
                intersect: false,
                mode: 'index',
            },
            layout: {
                padding: isStatus ? 10 : { top: 4, right: 12, bottom: 4, left: 4 },
            },
            plugins: {
                legend: {
                    display: isStatus,
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 16,
                        color: '#526159',
                        font: { family: 'Plus Jakarta Sans', size: 11, weight: '600' },
                    },
                },
                tooltip: {
                    backgroundColor: '#173b29',
                    borderColor: 'rgba(255,255,255,.15)',
                    borderWidth: 1,
                    cornerRadius: 10,
                    padding: 12,
                    titleFont: { family: 'Plus Jakarta Sans', size: 11, weight: '700' },
                    bodyFont: { family: 'Plus Jakarta Sans', size: 11, weight: '500' },
                    callbacks: {
                        label: (context) => `${context.label}: ${context.raw} kasus`,
                    },
                },
            },
            scales: isStatus ? {} : {
                x: {
                    beginAtZero: true,
                    ticks: { precision: 0, color: '#6f7d74', font: { size: 10, weight: '600' } },
                    grid: { color: '#e6eee8', drawBorder: false },
                },
                y: {
                    ticks: {
                        color: '#526159',
                        font: { size: 10, weight: '600' },
                        callback: (value) => truncateLabel(labels[value] ?? ''),
                    },
                    grid: { display: false, drawBorder: false },
                },
            },
        },
        plugins: isStatus ? [doughnutCenterLabel] : [],
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

export function initializeMonitoringDashboard(cases, {
    controls = null,
    filters = null,
    manageControls = true,
} = {}) {
    const resolvedControls = controls ?? getDashboardControls();
    const resetButton = manageControls ? document.querySelector('[data-dashboard-reset]') : null;
    const activeFilterCount = manageControls
        ? document.querySelector('[data-dashboard-active-filter-count]')
        : null;
    const emptyState = document.querySelector('[data-dashboard-empty]');
    const datasetEmptyState = document.querySelector('[data-dashboard-dataset-empty]');
    const resolvedFilters = filters ?? createFilterState();
    const chartInstances = {};

    if (manageControls) {
        setSelectOptions(resolvedControls.status, getStatusOptions(), 'Semua Status');
        setSelectOptions(resolvedControls.commodity, getUniqueCommodities(cases), 'Semua Komoditas');
        setSelectOptions(resolvedControls.disease, getUniqueDiseases(cases), 'Semua Penyakit');
        setSelectOptions(resolvedControls.popt, getUniquePopts(cases), 'Semua POPT');
        setSelectOptions(resolvedControls.regency, getUniqueRegencies(cases), 'Semua Kabupaten/Kota');
        refreshDistrictOptions(resolvedControls, resolvedFilters, cases);
    }

    const renderDashboard = () => {
        const filteredCases = applyFilters(cases, resolvedFilters);
        const summary = calculateSummary(filteredCases);

        updateKpis(summary);
        updateCharts(chartInstances, {
            status: groupByStatus(filteredCases),
            commodity: groupByCommodity(filteredCases),
            regency: groupByRegency(filteredCases),
            disease: groupByDisease(filteredCases),
        });

        if (activeFilterCount) {
            activeFilterCount.textContent = `Filter aktif: ${countActiveFilters(resolvedFilters)}`;
        }

        if (emptyState) {
            emptyState.hidden = cases.length === 0 || filteredCases.length !== 0;
        }

        if (datasetEmptyState) {
            datasetEmptyState.hidden = cases.length !== 0;
        }
    };

    if (manageControls) {
        Object.entries(resolvedControls).forEach(([filterName, control]) => {
            control?.addEventListener('change', () => {
                resolvedFilters[filterName] = control.value;

                if (filterName === 'regency') {
                    resolvedFilters.district = '';
                    refreshDistrictOptions(resolvedControls, resolvedFilters, cases);
                }

                renderDashboard();
            });
        });

        resetButton?.addEventListener('click', () => {
            Object.assign(resolvedFilters, createFilterState());

            Object.entries(resolvedControls).forEach(([filterName, control]) => {
                if (control && filterName !== 'district') {
                    control.value = '';
                }
            });

            refreshDistrictOptions(resolvedControls, resolvedFilters, cases);
            renderDashboard();
        });
    }

    renderDashboard();

    const loadingState = document.querySelector('[data-dashboard-loading]');
    if (loadingState) {
        loadingState.hidden = true;
    }

    return { render: renderDashboard };
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
