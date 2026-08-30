import { STATUS_ORDER, getStatusConfig } from './statuses';

export const SUMMARY_RULES = {
    activeExcludes: ['completed'],
    completed: 'completed',
    postponed: 'postponed',
};

function displayLabel(value) {
    return value === null || value === undefined || String(value).trim() === '' ? '-' : String(value);
}

function groupByLabel(cases, getLabel) {
    const counts = new Map();

    cases.forEach((caseData) => {
        const label = displayLabel(getLabel(caseData));
        counts.set(label, (counts.get(label) || 0) + 1);
    });

    return [...counts.entries()]
        .map(([label, count]) => ({ label, count }))
        .sort((first, second) => first.label.localeCompare(second.label, 'id'));
}

export function calculateSummary(cases) {
    return {
        total: cases.length,
        active: cases.filter((caseData) => !SUMMARY_RULES.activeExcludes.includes(caseData.status)).length,
        completed: cases.filter((caseData) => caseData.status === SUMMARY_RULES.completed).length,
        postponed: cases.filter((caseData) => caseData.status === SUMMARY_RULES.postponed).length,
    };
}

export function groupByStatus(cases) {
    const counts = new Map();

    cases.forEach((caseData) => {
        counts.set(caseData.status, (counts.get(caseData.status) || 0) + 1);
    });

    return STATUS_ORDER.map((status) => ({
        key: status,
        label: getStatusConfig(status).label,
        count: counts.get(status) || 0,
        color: getStatusConfig(status).chartColor,
    }));
}

export function groupByCommodity(cases) {
    return groupByLabel(cases, (caseData) => caseData.komoditas?.nama);
}

export function groupByDisease(cases) {
    return groupByLabel(cases, (caseData) => caseData.penyakit?.nama);
}

export function groupByRegency(cases) {
    return groupByLabel(cases, (caseData) => caseData.wilayah?.kabupaten);
}
