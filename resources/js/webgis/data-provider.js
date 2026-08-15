import { mockCases } from './mock-cases.js';

function normalizeNumber(value) {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    const numberValue = Number(value);

    return Number.isFinite(numberValue) ? numberValue : null;
}

function normalizeReference(value, fields) {
    if (!value || typeof value !== 'object') {
        return null;
    }

    const reference = {};

    fields.forEach((field) => {
        if (value[field] !== null && value[field] !== undefined) {
            reference[field] = value[field];
        }
    });

    return Object.keys(reference).length > 0 ? reference : null;
}

function normalizeStatusHistory(history) {
    if (!Array.isArray(history)) {
        return [];
    }

    return history.map((entry) => normalizeReference(entry, ['status', 'note', 'changed_at'])).filter(Boolean);
}

/**
 * Normalize an M2 case read model into the shape consumed by M3.
 * Coordinates are deliberately kept as null when they are absent or invalid;
 * the map decides whether a case is mappable, while monitoring still sees it.
 */
export function normalizeCase(rawCase) {
    const source = rawCase && typeof rawCase === 'object' ? rawCase : {};

    return {
        case_id: source.case_id ?? null,
        case_code: source.case_code ?? null,
        latitude: normalizeNumber(source.latitude),
        longitude: normalizeNumber(source.longitude),
        kelompok_tani: normalizeReference(source.kelompok_tani, ['id', 'nama']),
        komoditas: normalizeReference(source.komoditas, ['id', 'kode', 'nama']),
        penyakit: normalizeReference(source.penyakit, ['id', 'nama']),
        wilayah: normalizeReference(source.wilayah, [
            'kode_kabupaten',
            'kabupaten',
            'kode_kecamatan',
            'kecamatan',
        ]),
        popt: normalizeReference(source.popt, ['id', 'nama']),
        status: source.status ?? null,
        last_note: source.last_note ?? null,
        last_status_at: source.last_status_at ?? null,
        status_history: normalizeStatusHistory(source.status_history),
    };
}

export function normalizeCases(rawCases) {
    if (!Array.isArray(rawCases)) {
        throw new TypeError('Case provider must return an array.');
    }

    return rawCases.map(normalizeCase);
}

/**
 * Development-only provider. Replace this provider implementation with an M2
 * API adapter after the normalized contract has been agreed and delivered.
 */
export class MockCaseProvider {
    async getCases() {
        return normalizeCases(mockCases);
    }
}

const caseDataProvider = new MockCaseProvider();

export async function getCases() {
    return caseDataProvider.getCases();
}
