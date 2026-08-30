export const providerFixtures = [
    {
        name: 'normal case with valid coordinates',
        raw: {
            case_id: 'normal-1',
            case_code: 'KAS-NORMAL-001',
            latitude: '-6.595',
            longitude: 106.8167,
            komoditas: { id: 1, kode: 'KP-001', nama: 'Kopi Arabika' },
            status: 'assigned',
        },
        assertCase: (testCase, assert) => {
            assert.equal(testCase.latitude, -6.595);
            assert.equal(testCase.longitude, 106.8167);
            assert.equal(testCase.komoditas.id, 1);
        },
    },
    {
        name: 'case without coordinates',
        raw: { case_id: 'missing-coordinates', status: 'under_review' },
        assertCase: (testCase, assert) => {
            assert.equal(testCase.latitude, null);
            assert.equal(testCase.longitude, null);
        },
    },
    {
        name: 'case with invalid coordinates',
        raw: { case_id: 'invalid-coordinates', latitude: 91, longitude: 181, status: 'assigned' },
        assertCase: (testCase, assert) => {
            assert.equal(testCase.latitude, null);
            assert.equal(testCase.longitude, null);
        },
    },
    {
        name: 'case without POPT',
        raw: { case_id: 'without-popt', latitude: -6, longitude: 107, popt: null, status: 'in_progress' },
        assertCase: (testCase, assert) => assert.equal(testCase.popt, null),
    },
    {
        name: 'postponed handling status',
        raw: { case_id: 'postponed', status: 'postponed' },
        assertCase: (testCase, assert) => assert.equal(testCase.status, 'postponed'),
    },
    {
        name: 'completed handling status',
        raw: { case_id: 'completed', status: 'completed' },
        assertCase: (testCase, assert) => assert.equal(testCase.status, 'completed'),
    },
    {
        name: 'request status remains separate',
        raw: { case_id: 'request-status', request_status: 'approved', status: 'under_review' },
        assertCase: (testCase, assert) => {
            assert.equal(testCase.request_status, 'approved');
            assert.equal(testCase.status, 'under_review');
        },
    },
    {
        name: 'empty status history',
        raw: { case_id: 'empty-history', status: 'assigned', status_history: [] },
        assertCase: (testCase, assert) => assert.deepEqual(testCase.status_history, []),
    },
    {
        name: 'multiple status history entries',
        raw: {
            case_id: 'multiple-history',
            status: 'completed',
            status_history: [
                { status: 'assigned', note: 'Assigned', changed_at: '2026-08-15T08:00:00+07:00' },
                { status: 'completed', note: 'Completed', changed_at: '2026-08-15T10:00:00+07:00' },
            ],
        },
        assertCase: (testCase, assert) => assert.equal(testCase.status_history.length, 2),
    },
    {
        name: 'commodity as string',
        raw: { case_id: 'commodity-string', komoditas: 'Karet' },
        assertCase: (testCase, assert) => assert.equal(testCase.komoditas.nama, 'Karet'),
    },
    {
        name: 'commodity as object with mapping metadata',
        raw: {
            case_id: 'commodity-object',
            komoditas: {
                id: 4,
                nama: 'Kopi',
                source_commodity_id: 'disbun-44',
                mapping_status: 'mapped',
            },
        },
        assertCase: (testCase, assert) => {
            assert.equal(testCase.komoditas.id, 4);
            assert.equal(testCase.komoditas.source_commodity_id, 'disbun-44');
            assert.equal(testCase.komoditas.mapping_status, 'mapped');
        },
    },
    {
        name: 'optional fields with null values',
        raw: {
            case_id: 'optional-null',
            kelompok_tani: null,
            komoditas: null,
            penyakit: null,
            wilayah: null,
            popt: null,
            last_note: null,
            last_status_at: null,
            status_history: null,
        },
        assertCase: (testCase, assert) => {
            assert.equal(testCase.kelompok_tani, null);
            assert.equal(testCase.komoditas, null);
            assert.equal(testCase.status_history.length, 0);
        },
    },
];
