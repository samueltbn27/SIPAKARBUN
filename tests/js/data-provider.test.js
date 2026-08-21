import assert from 'node:assert/strict';
import { test } from 'node:test';
import {
    ApiCaseProvider,
    getCases,
    hasValidCaseCoordinates,
    normalizeCase,
    normalizeCases,
} from '../../resources/js/webgis/data-provider.js';
import { providerFixtures } from './fixtures/case-provider-fixtures.js';

test('active MockCaseProvider returns the current mock dataset through the provider boundary', async () => {
    const cases = await getCases();

    assert.equal(cases.length, 10);
    assert.equal(typeof cases[0].latitude, 'number');
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
                        status: 'dalam_pelaksanaan',
                        komoditas: { id: 5, kode: 'KP-045', nama: 'Karet' },
                        penyakit: { id: 4, nama: 'Jamur Akar Putih' },
                        lokasi_kasus: {
                            latitude: '-6.9123',
                            longitude: '107.6123',
                        },
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
    assert.equal(caseData.status, 'in_progress');
    assert.equal(caseData.latitude, -6.9123);
    assert.equal(caseData.longitude, 107.6123);
    assert.deepEqual(caseData.popt, { id: 33, nama: 'Budi' });
    assert.deepEqual(caseData.status_history, [{
        status: 'assigned',
        note: 'POPT ditugaskan.',
        changed_at: '2026-08-19T08:00:00+07:00',
    }]);
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
