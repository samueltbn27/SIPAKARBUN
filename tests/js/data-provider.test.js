import assert from 'node:assert/strict';
import { test } from 'node:test';
import {
    ApiCaseProvider,
    hasValidCaseCoordinates,
    MockCaseProvider,
    normalizeCase,
    normalizeCases,
} from '../../resources/js/webgis/data-provider.js';
import { providerFixtures } from './fixtures/case-provider-fixtures.js';
import { getStatusConfig, getStatusOptions } from '../../resources/js/webgis/statuses.js';
import { calculateSummary, groupByStatus } from '../../resources/js/webgis/statistics.js';
import { applyFilters, createFilterState } from '../../resources/js/webgis/filters.js';

test('MockCaseProvider remains available for isolated development and tests', async () => {
    const cases = await new MockCaseProvider().getCases();

    assert.equal(cases.length, 10);
    assert.equal(typeof cases[0].latitude, 'number');
    assert.ok(cases.every(({ status }) => ['menunggu_review', 'ditolak', 'dalam_penanganan', 'selesai'].includes(status)));
});

for (const fixture of providerFixtures) {
    test(`normalizeCase: ${fixture.name}`, () => {
        const normalized = normalizeCase(fixture.raw);

        fixture.assertCase(normalized, assert);
    });
}

test('invalid and missing coordinates are not mappable but remain in normalized dataset', () => {
    const cases = normalizeCases([
        { case_id: 'valid', latitude: -6, longitude: 107 },
        { case_id: 'missing' },
        { case_id: 'invalid', latitude: -91, longitude: 181 },
    ]);

    assert.equal(cases.length, 3);
    assert.equal(cases.filter(hasValidCaseCoordinates).length, 1);
    assert.equal(hasValidCaseCoordinates(cases[1]), false);
    assert.equal(hasValidCaseCoordinates(cases[2]), false);
});

test('normalizeCase keeps explicit case latitude and longitude in Leaflet order', () => {
    const normalized = normalizeCase({
        kasus_id: 19,
        latitude_kasus: '-6.850000',
        longitude_kasus: '107.920000',
        kelompok_tani: { latitude: -6.90, longitude: 107.80 },
    });

    assert.equal(normalized.latitude, -6.85);
    assert.equal(normalized.longitude, 107.92);
});

test('normalizeCases rejects a non-array provider result', () => {
    assert.throws(() => normalizeCases(null), TypeError);
});

test('ApiCaseProvider does not have an implicit network configuration', async () => {
    await assert.rejects(new ApiCaseProvider().getCases(), /endpoint is not configured/);
    await assert.rejects(
        new ApiCaseProvider({ endpoint: '/configured-only' }).getCases(),
        /requires a fetch implementation/,
    );
});

test('ApiCaseProvider normalizes an envelope response without making a real request', async () => {
    let requestedEndpoint = null;
    const provider = new ApiCaseProvider({
        endpoint: '/configured-only',
        fetchImpl: async (endpoint) => {
            requestedEndpoint = endpoint;

            return {
                ok: true,
                status: 200,
                async json() {
                    return { data: [{ case_id: 'api-1', latitude: '-6', longitude: '107' }] };
                },
            };
        },
    });

    const cases = await provider.getCases();

    assert.equal(requestedEndpoint, '/configured-only');
    assert.equal(cases.length, 1);
    assert.equal(cases[0].latitude, -6);
});

test('ApiCaseProvider treats an empty data array as an empty dataset', async () => {
    const provider = new ApiCaseProvider({
        endpoint: '/configured-only',
        fetchImpl: async () => ({ ok: true, status: 200, async json() { return { data: [] }; } }),
    });

    assert.deepEqual(await provider.getCases(), []);
});

test('ApiCaseProvider adapts the actual M2 case resource shape', async () => {
    const provider = new ApiCaseProvider({
        endpoint: '/api/kasus',
        fetchImpl: async () => ({
            ok: true,
            status: 200,
            async json() {
                return {
                    data: [{
                        kasus_id: 17,
                        kasus_code: 'KS-20260820-0001',
                        permohonan_id: 12,
                        request_status: 'diterima',
                        latitude_kasus: '-6.9123',
                        longitude_kasus: '107.6123',
                        status: 'dalam_pelaksanaan',
                        monitoring_status: 'dalam_penanganan',
                        monitoring_status_label: 'Dalam Penanganan',
                        kelompok_tani: { id: 9, nama: 'Tani Makmur' },
                        komoditas: { id: 5, kode: 'KP-045', nama: 'Karet' },
                        penyakit: { id: 4, kode: null, nama: 'Jamur Akar Putih' },
                        wilayah: { kode_kabupaten: '3201', kabupaten: 'Bogor' },
                        penugasan_popt: {
                            popt_id: 33,
                            popt_name: 'Budi',
                        },
                        riwayat_status: [{
                            status: 'ditugaskan',
                            catatan: 'POPT ditugaskan.',
                            created_at: '2026-08-19T08:00:00+07:00',
                        }],
                    }],
                };
            },
        }),
    });

    const [caseData] = await provider.getCases();

    assert.equal(caseData.case_id, 17);
    assert.equal(caseData.case_code, 'KS-20260820-0001');
    assert.equal(caseData.request_id, 12);
    assert.equal(caseData.request_status, 'diterima');
    assert.equal(caseData.status, 'dalam_penanganan');
    assert.equal(caseData.latitude, -6.9123);
    assert.equal(caseData.longitude, 107.6123);
    assert.deepEqual(caseData.kelompok_tani, { id: 9, nama: 'Tani Makmur' });
    assert.deepEqual(caseData.wilayah, { kode_kabupaten: '3201', kabupaten: 'Bogor' });
    assert.equal(caseData.penyakit.kode, null);
    assert.deepEqual(caseData.popt, { id: 33, nama: 'Budi' });
    assert.deepEqual(caseData.status_history, [{
        status: 'ditugaskan',
        note: 'POPT ditugaskan.',
        changed_at: '2026-08-19T08:00:00+07:00',
    }]);
});

test('ApiCaseProvider preserves backend review warning for incomplete accepted history', async () => {
    const provider = new ApiCaseProvider({
        endpoint: '/api/kasus',
        fetchImpl: async () => ({
            ok: true,
            status: 200,
            async json() {
                return {
                    data: [{
                        kasus_id: 18,
                        request_status: 'diterima',
                        handling_status: 'diterima',
                        monitoring_status: 'menunggu_review',
                        penugasan_popt: null,
                        history_incomplete: true, history_note: 'Data historis perlu ditinjau Operator.',
                        riwayat_status: [],
                    }],
                };
            },
        }),
    });

    const [caseData] = await provider.getCases();

    assert.equal(caseData.status, 'menunggu_review');
    assert.equal(caseData.request_status, 'diterima');
    assert.equal(caseData.popt, null);
    assert.deepEqual(caseData.status_history, []);
    assert.equal(caseData.history_incomplete, true);
    assert.equal(caseData.history_note, 'Data historis perlu ditinjau Operator.');
    assert.equal(getStatusConfig('menunggu_review').label, 'Menunggu Review');
    assert.ok(getStatusOptions().some((option) => option.value === 'menunggu_review'));
});

test('unknown handling status is retained as a safe unknown status', () => {
    assert.equal(normalizeCase({ handling_status: 'status-baru' }).status, null);
});

test('ApiCaseProvider exposes HTTP and invalid response errors', async () => {
    const httpErrorProvider = new ApiCaseProvider({
        endpoint: '/configured-only',
        fetchImpl: async () => ({ ok: false, status: 503 }),
    });
    const invalidResponseProvider = new ApiCaseProvider({
        endpoint: '/configured-only',
        fetchImpl: async () => ({ ok: true, status: 200, async json() { return { data: null }; } }),
    });

    await assert.rejects(httpErrorProvider.getCases(), /HTTP 503/);
    await assert.rejects(invalidResponseProvider.getCases(), /must contain an array/);
});

test('four primary statuses partition the dataset while overdue overlaps only active handling', () => {
    const cases = normalizeCases([
        { monitoring_status: 'menunggu_review', is_overdue: false },
        { monitoring_status: 'ditolak', is_overdue: false },
        { monitoring_status: 'dalam_penanganan', is_overdue: true },
        { monitoring_status: 'dalam_penanganan', is_overdue: true },
        { monitoring_status: 'dalam_penanganan', current_status: 'ditunda', is_overdue: false },
        { monitoring_status: 'selesai', is_overdue: false },
    ]);
    assert.deepEqual(calculateSummary(cases), { total: 6, review: 1, active: 3, completed: 1, overdue: 2 });
    const groups = groupByStatus(cases);
    assert.deepEqual(groups.map(({ label }) => label), ['Menunggu Review', 'Ditolak', 'Dalam Penanganan', 'Selesai']);
    assert.equal(groups.reduce((sum, entry) => sum + entry.count, 0), cases.length);
    assert.deepEqual(groups.map(({ count }) => count), [1, 1, 3, 1]);
    assert.deepEqual(getStatusOptions().map(({ value }) => value), groups.map(({ key }) => key));
    const filters = { ...createFilterState(), status: 'dalam_penanganan', overdue: '1' };
    assert.equal(applyFilters(cases, filters).length, 2);
    assert.equal(calculateSummary(applyFilters(cases, filters)).active, 2);
    assert.equal(getStatusConfig('melewati_batas_waktu').label, 'Belum tersedia');
});

test('the additional overdue metric never counts completed or rejected entries as active', () => {
    assert.deepEqual(calculateSummary([
        { status: 'menunggu_review', is_overdue: true },
        { status: 'ditolak', is_overdue: true },
        { status: 'selesai', is_overdue: true },
    ]), { total: 3, review: 1, active: 0, completed: 1, overdue: 0 });
});

test('pre-case monitoring records preserve their identity, location and rejection reason', () => {
    const record = normalizeCase({
        monitoring_id: 'permohonan-12', permohonan_id: 12, permohonan_code: 'PM-12',
        kasus_id: null, latitude_kasus: -7.1, longitude_kasus: 107.8,
        kelompok_tani: { latitude: -6.9, longitude: 107.6 },
        monitoring_status: 'ditolak', monitoring_status_label: 'Ditolak',
        rejection_reason: 'Bukti belum sesuai.', is_overdue: false,
    });
    assert.equal(record.case_id, null);
    assert.equal(record.monitoring_id, 'permohonan-12');
    assert.equal(record.case_code, 'PM-12');
    assert.equal(record.request_id, 12);
    assert.equal(record.latitude, -7.1);
    assert.equal(record.longitude, 107.8);
    assert.equal(record.rejection_reason, 'Bukti belum sesuai.');
    assert.equal(getStatusConfig(record.status).label, record.monitoring_status_label);
});

test('ApiCaseProvider loads every page so KPIs do not stop at the first 100 cases', async () => {
    const calls = [];
    const provider = new ApiCaseProvider({
        endpoint: '/api/monitoring/kasus?per_page=100',
        fetchImpl: async (url) => {
            calls.push(url);
            const page = calls.length;
            return { ok: true, async json() {
                return { data: Array.from({ length: page === 1 ? 100 : 2 }, (_, index) => ({
                    monitoring_id: `request-${(page - 1) * 100 + index}`,
                    monitoring_status: page === 1 ? 'menunggu_review' : 'dalam_penanganan',
                })), links: { next: page === 1 ? '/api/monitoring/kasus?per_page=100&page=2' : null } };
            } };
        },
    });
    const records = await provider.getCases();
    assert.equal(calls.length, 2);
    assert.deepEqual(calculateSummary(records), { total: 102, review: 100, active: 2, completed: 0, overdue: 0 });
});

test('ApiCaseProvider rejects repeated, cross-origin and unrelated pagination links', async () => {
    for (const next of ['/api/monitoring/kasus', 'https://example.invalid/api/monitoring/kasus?page=2', '/api/other?page=2']) {
        const provider = new ApiCaseProvider({
            endpoint: '/api/monitoring/kasus',
            fetchImpl: async () => ({ ok: true, async json() { return { data: [], links: { next } }; } }),
        });
        await assert.rejects(provider.getCases(), /pagination link is invalid or repeated/);
    }
});

test('ApiCaseProvider surfaces a later-page failure instead of returning partial KPIs', async () => {
    let page = 0;
    const provider = new ApiCaseProvider({
        endpoint: '/api/monitoring/kasus',
        httpClient: { async get() {
            if (++page === 2) throw new Error('Second page unavailable');
            return { data: { data: [{ monitoring_status: 'selesai' }], links: { next: '/api/monitoring/kasus?page=2' } } };
        } },
    });
    await assert.rejects(provider.getCases(), /Case API request failed/);
});
