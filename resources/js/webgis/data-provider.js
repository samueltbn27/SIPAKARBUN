import axios from 'axios';
import { mockCases } from './mock-cases.js';
import { normalizeHandlingStatus } from './statuses.js';

function normalizeNumber(value) {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    const numberValue = Number(value);

    return Number.isFinite(numberValue) ? numberValue : null;
}

function normalizeCoordinate(value, minimum, maximum) {
    const numberValue = normalizeNumber(value);

    return numberValue !== null && numberValue >= minimum && numberValue <= maximum
        ? numberValue
        : null;
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

function normalizeCommodity(rawCase) {
    const source = rawCase.komoditas ?? rawCase.commodity;
    const commodity = typeof source === 'string'
        ? { nama: source }
        : typeof source === 'number'
            ? { id: source }
            : source;
    const normalized = normalizeReference(commodity, [
        'id',
        'kode',
        'nama',
        'name',
        'commodity_id',
        'commodity_name',
        'source_commodity_id',
        'mapping_status',
    ]) ?? {};

    const reference = {
        id: normalized.id ?? normalized.commodity_id ?? rawCase.commodity_id,
        kode: normalized.kode,
        nama: normalized.nama ?? normalized.name ?? normalized.commodity_name ?? rawCase.commodity_name,
        source_commodity_id: normalized.source_commodity_id ?? rawCase.source_commodity_id,
        mapping_status: normalized.mapping_status ?? rawCase.mapping_status,
    };
    const hasValue = Object.values(reference).some((value) => value !== null && value !== undefined);

    return hasValue ? reference : null;
}

function normalizeDisease(rawCase) {
    const source = rawCase.penyakit;

    if (!source || typeof source !== 'object') {
        return null;
    }

    return {
        id: source.id ?? null,
        kode: source.kode ?? null,
        nama: source.nama ?? source.name ?? null,
    };
}

function normalizeStatusHistory(history) {
    if (!Array.isArray(history)) {
        return [];
    }

    return history.map((entry) => {
        const source = entry && typeof entry === 'object' ? entry : {};
        const status = normalizeHandlingStatus(source.status ?? source.handling_status);
        const note = source.note ?? source.catatan ?? null;
        const changedAt = source.changed_at ?? source.created_at ?? null;

        if (status === null && note === null && changedAt === null) {
            return null;
        }

        return { status, note, changed_at: changedAt };
    }).filter(Boolean);
}

function normalizePopt(rawCase) {
    const source = rawCase.popt ?? rawCase.penugasan_popt;

    if (!source || typeof source !== 'object') {
        return null;
    }

    return normalizeReference({
        id: source.id ?? source.popt_id,
        nama: source.nama ?? source.name ?? source.popt_name,
    }, ['id', 'nama']);
}

/**
 * Normalize an M2 case read model into the shape consumed by M3.
 * Coordinates are deliberately kept as null when they are absent or invalid;
 * the map decides whether a case is mappable, while monitoring still sees it.
 */
export function normalizeCase(rawCase) {
    const source = rawCase && typeof rawCase === 'object' ? rawCase : {};
    const location = source.lokasi_kasus ?? source.location ?? {};
    const request = source.permohonan ?? {};
    const history = source.status_history ?? source.riwayat_status;
    const normalizedHistory = normalizeStatusHistory(history);
    // M2 returns riwayat_status newest-first. The date comparison also keeps
    // the normalizer tolerant of older development fixtures.
    const latestHistory = normalizedHistory.reduce((latest, entry) => {
        if (!latest || !entry.changed_at) {
            return latest ?? entry;
        }

        return new Date(entry.changed_at) > new Date(latest.changed_at) ? entry : latest;
    }, null);

    return {
        case_id: source.case_id ?? source.kasus_id ?? null,
        case_code: source.case_code ?? source.kasus_code ?? null,
        request_id: source.request_id ?? source.permohonan_id ?? request.id ?? null,
        // Only the case coordinate fields are authoritative. There is no
        // fallback to the Poktan reference location.
        latitude: normalizeCoordinate(source.latitude_kasus ?? source.latitude ?? location.latitude, -90, 90),
        longitude: normalizeCoordinate(source.longitude_kasus ?? source.longitude ?? location.longitude, -180, 180),
        kelompok_tani: normalizeReference(source.kelompok_tani ?? request.kelompok_tani, ['id', 'nama']),
        komoditas: normalizeCommodity(source),
        penyakit: normalizeDisease(source),
        wilayah: normalizeReference(source.wilayah ?? request.wilayah, [
            'kode_kabupaten',
            'kabupaten',
            'kode_kecamatan',
            'kecamatan',
        ]),
        popt: normalizePopt(source),
        status: normalizeHandlingStatus(source.handling_status ?? source.current_status ?? source.status),
        request_status: source.request_status ?? request.status ?? null,
        last_note: source.last_note ?? latestHistory?.note ?? null,
        last_status_at: source.last_status_at ?? latestHistory?.changed_at ?? null,
        status_history: normalizedHistory,
    };
}

export function normalizeCases(rawCases) {
    if (!Array.isArray(rawCases)) {
        throw new TypeError('Case provider must return an array.');
    }

    return rawCases.map(normalizeCase);
}

export function hasValidCaseCoordinates(caseData) {
    return Boolean(
        caseData
        && Number.isFinite(caseData.latitude)
        && Number.isFinite(caseData.longitude)
        && caseData.latitude >= -90
        && caseData.latitude <= 90
        && caseData.longitude >= -180
        && caseData.longitude <= 180,
    );
}

export class ApiCaseProvider {
    constructor({ endpoint = null, fetchImpl = null, httpClient = null } = {}) {
        this.endpoint = endpoint;
        this.fetchImpl = fetchImpl;
        this.httpClient = httpClient;
    }

    async getCases() {
        if (!this.endpoint) {
            throw new Error('ApiCaseProvider endpoint is not configured.');
        }

        if (typeof this.fetchImpl !== 'function' && typeof this.httpClient?.get !== 'function') {
            throw new Error('ApiCaseProvider requires a fetch implementation or HTTP client.');
        }

        let payload;

        if (typeof this.fetchImpl === 'function') {
            let response;

            try {
                response = await this.fetchImpl(this.endpoint, {
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
            } catch (error) {
                throw new Error('Case API request failed.', { cause: error });
            }

            if (!response?.ok) {
                throw new Error(`Case API request failed with HTTP ${response?.status ?? 'unknown'}.`);
            }

            try {
                payload = await response.json();
            } catch (error) {
                throw new Error('Case API response is not valid JSON.', { cause: error });
            }
        } else {
            try {
                const response = await this.httpClient.get(this.endpoint, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                payload = response?.data;
            } catch (error) {
                const status = error?.response?.status;
                const suffix = status ? ` with HTTP ${status}` : '';
                throw new Error(`Case API request failed${suffix}.`, { cause: error });
            }
        }

        const rawCases = Array.isArray(payload) ? payload : payload?.data;

        if (!Array.isArray(rawCases)) {
            throw new TypeError('Case API response must contain an array in data.');
        }

        return normalizeCases(rawCases);
    }
}

/**
 * Development-only provider. It remains available for isolated tests and
 * development, but is never selected as the live runtime provider.
 */
export class MockCaseProvider {
    async getCases() {
        return normalizeCases(mockCases);
    }
}

function getRuntimeHttpClient() {
    return globalThis.window?.axios ?? axios;
}

export const activeCaseProvider = new ApiCaseProvider({
    endpoint: '/api/kasus',
    httpClient: getRuntimeHttpClient(),
});

export async function getCases() {
    return activeCaseProvider.getCases();
}
