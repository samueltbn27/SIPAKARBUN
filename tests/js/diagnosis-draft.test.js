import assert from 'node:assert/strict';
import { describe, it } from 'node:test';
import {
    CONF_LEVELS,
    DRAFT_EXPIRY_MS,
    DRAFT_VERSION,
    clearDraft,
    draftStorageKey,
    formatDraftDateTime,
    loadDraft,
    sanitizeDraft,
    saveDraft,
} from '../../resources/js/diagnosis/draft.js';

function memoryStorage(initial = {}) {
    const store = new Map(Object.entries(initial));
    return {
        getItem: (k) => (store.has(k) ? store.get(k) : null),
        setItem: (k, v) => { store.set(k, String(v)); },
        removeItem: (k) => { store.delete(k); },
    };
}

const KOMODITAS = [{ id: 1, nama: 'Kopi Arabika' }, { id: 2, nama: 'Kakao' }];
const GEJALA = [{ id: 10, nama: 'Bercak kuning' }, { id: 11, nama: 'Serbuk jingga' }];
const GEJALA_MAP = { 1: [10, 11], 2: [11] };

describe('diagnosis draft autosave', () => {
    it('membuat key per user', () => {
        assert.equal(draftStorageKey(7), 'sipakarbun.diagnosis.v1:7');
    });

    it('menyimpan lalu memuat kembali draft yang valid', () => {
        const storage = memoryStorage();
        const key = draftStorageKey(1);
        assert.equal(saveDraft(storage, key, {
            commodityId: 1,
            selected: [10, 11],
            confidences: { 10: 0.8, 11: 1.0 },
            step: 3,
            maxVisited: 3,
        }), 'saved');

        const draft = loadDraft(storage, key, { komoditas: KOMODITAS, gejala: GEJALA });
        assert.equal(draft.commodityId, 1);
        assert.deepEqual(draft.selected, [10, 11]);
        assert.deepEqual(draft.confidences, { 10: 0.8, 11: 1.0 });
        assert.equal(draft.step, 3);
    });

    it('membuang gejala yang sudah tidak dikenal + menormalkan keyakinan', () => {
        const cleaned = sanitizeDraft({
            v: 1,
            commodityId: 1,
            selected: [10, 999, '11'],
            confidences: { 10: 0.5, 11: 0.6 },
            step: 9,
            maxVisited: 0,
            updatedAt: Date.now(),
        }, { komoditas: KOMODITAS, gejala: GEJALA });

        assert.deepEqual(cleaned.selected, [10, 11]);
        assert.deepEqual(cleaned.confidences, { 10: 0.8, 11: 0.6 });
        assert.equal(cleaned.step, 4);
        assert.equal(cleaned.maxVisited, 1);
    });

    it('menolak draft kedaluwarsa', () => {
        const draft = sanitizeDraft({
            v: 1, commodityId: 1, selected: [10],
            confidences: {}, step: 2, maxVisited: 2,
            updatedAt: Date.now() - DRAFT_EXPIRY_MS - 1000,
        }, { komoditas: KOMODITAS, gejala: GEJALA });
        assert.equal(draft, null);
    });

    it('menolak komoditas tak dikenal, versi beda, dan JSON rusak', () => {
        const now = Date.now();
        assert.equal(sanitizeDraft({ v: 2, commodityId: 1, selected: [10], updatedAt: now }, { komoditas: KOMODITAS, gejala: GEJALA }), null);
        assert.equal(sanitizeDraft({ v: 1, commodityId: 99, selected: [10], updatedAt: now }, { komoditas: KOMODITAS, gejala: GEJALA }), null);

        const storage = memoryStorage({ k: '{bukan json' });
        assert.equal(loadDraft(storage, 'k', { komoditas: KOMODITAS, gejala: GEJALA }), null);
        assert.equal(loadDraft(memoryStorage(), 'kosong', { komoditas: KOMODITAS, gejala: GEJALA }), null);
    });

    it('draft komoditas-saja (belum pilih gejala) tetap pulih', () => {
        const storage = memoryStorage();
        const key = draftStorageKey(1);
        assert.equal(saveDraft(storage, key, {
            commodityId: 1,
            selected: [],
            confidences: {},
            step: 1,
            maxVisited: 1,
        }), 'saved');

        const draft = loadDraft(storage, key, { komoditas: KOMODITAS, gejala: GEJALA });
        assert.equal(draft.commodityId, 1);
        assert.deepEqual(draft.selected, []);
        assert.deepEqual(draft.confidences, {});
        assert.equal(draft.step, 1);
    });

    it('step dijepit ke Step 2 bila gejala tersaring habis', () => {
        const draft = sanitizeDraft({
            v: 1, commodityId: 1, selected: [999],
            confidences: {}, step: 4, maxVisited: 4,
            updatedAt: Date.now(),
        }, { komoditas: KOMODITAS, gejala: GEJALA });

        assert.deepEqual(draft.selected, []);
        assert.equal(draft.step, 2);
        assert.equal(draft.maxVisited, 2);
    });

    it('menyimpan dan memulihkan pilihan per komoditas', () => {
        const storage = memoryStorage();
        const key = draftStorageKey(1);
        saveDraft(storage, key, {
            commodityId: 1,
            selected: [10],
            confidences: { 10: 0.6 },
            step: 3,
            maxVisited: 3,
            perKomoditas: {
                2: { selected: [11], confidences: { 11: 1.0 } },
                99: { selected: [], confidences: {} },
            },
        });

        const draft = loadDraft(storage, key, { komoditas: KOMODITAS, gejala: GEJALA, gejalaMap: GEJALA_MAP });
        assert.deepEqual(draft.selected, [10]);
        assert.deepEqual(draft.perKomoditas[2], { selected: [11], confidences: { 11: 1.0 } });
        assert.equal(draft.perKomoditas[99], undefined);
    });

    it('memvalidasi pilihan per komoditas terhadap gejalaMap', () => {
        const draft = sanitizeDraft({
            v: 1, commodityId: 1, selected: [10, 11],
            confidences: {}, step: 3, maxVisited: 3,
            updatedAt: Date.now(),
            perKomoditas: {
                2: { selected: [10, 11], confidences: { 10: 0.2, 11: 0.5 } },
            },
        }, { komoditas: KOMODITAS, gejala: GEJALA, gejalaMap: GEJALA_MAP });

        assert.deepEqual(draft.selected, [10, 11]);
        // Gejala 10 tidak valid untuk komoditas 2 → tinggal 11 (0.5 bukan level valid → 0.8).
        assert.deepEqual(draft.perKomoditas[2].selected, [11]);
        assert.deepEqual(draft.perKomoditas[2].confidences, { 11: 0.8 });
    });

    it('menghapus draft saat state kosong dan via clearDraft', () => {
        const storage = memoryStorage();
        const key = draftStorageKey(1);
        saveDraft(storage, key, { commodityId: 1, selected: [10], confidences: {} });
        assert.equal(saveDraft(storage, key, { commodityId: '', selected: [] }), 'cleared');
        assert.equal(loadDraft(storage, key, { komoditas: KOMODITAS, gejala: GEJALA }), null);

        saveDraft(storage, key, { commodityId: 1, selected: [10], confidences: {} });
        clearDraft(storage, key);
        assert.equal(loadDraft(storage, key, { komoditas: KOMODITAS, gejala: GEJALA }), null);
    });

    it('level keyakinan dan format waktu tersedia', () => {
        assert.deepEqual(CONF_LEVELS, [0.2, 0.4, 0.6, 0.8, 1.0]);
        assert.equal(DRAFT_VERSION, 1);
        const text = formatDraftDateTime(new Date('2026-09-20T10:30:00').getTime());
        assert.match(text, /30/);
    });

    it('tulis sinkron langsung terbaca tanpa menunggu debounce (simulasi pindah halaman cepat)', () => {
        const storage = memoryStorage();
        const key = draftStorageKey(1);
        // Mensimulasikan tulisDraftSegera(): saveDraft sinkron tepat sebelum pagehide.
        assert.equal(saveDraft(storage, key, {
            commodityId: 1,
            selected: [10],
            confidences: { 10: 0.6 },
            step: 2,
            maxVisited: 2,
        }), 'saved');

        // Mensimulasikan init() saat kembali: tidak ada old() -> muatDraft -> terapkan langsung.
        const draft = loadDraft(storage, key, { komoditas: KOMODITAS, gejala: GEJALA, gejalaMap: GEJALA_MAP });
        assert.equal(draft.commodityId, 1);
        assert.deepEqual(draft.selected, [10]);
        assert.deepEqual(draft.confidences, { 10: 0.6 });
        assert.equal(draft.step, 2);
    });

    it('menormalisasi commodityId string menjadi number agar lolos validasi saat kembali', () => {
        const storage = memoryStorage();
        const key = draftStorageKey(1);
        // Mensimulasikan old('commodity_id') dari Blade yang berupa string.
        assert.equal(saveDraft(storage, key, {
            commodityId: '1',
            selected: [10],
            confidences: { 10: 0.6 },
            step: 2,
            maxVisited: 2,
        }), 'saved');

        const stored = JSON.parse(storage.getItem(key));
        assert.strictEqual(stored.commodityId, 1);

        const draft = loadDraft(storage, key, { komoditas: KOMODITAS, gejala: GEJALA, gejalaMap: GEJALA_MAP });
        assert.equal(draft.commodityId, 1);
        assert.deepEqual(draft.selected, [10]);
    });

    it('setiap mutasi tersimpan sinkron tanpa menunggu (klik gejala lalu langsung pindah halaman)', () => {
        const storage = memoryStorage();
        const key = draftStorageKey(1);
        // Mensimulasikan simpanDraft() sinkron baru: tiap toggleGejala /
        // setConf / pilihKomoditas langsung tulis tanpa debounce 400ms.
        const mutations = [
            { commodityId: 1, selected: [], confidences: {}, step: 2, maxVisited: 2 },
            { commodityId: 1, selected: [10], confidences: { 10: 0.8 }, step: 2, maxVisited: 2 },
            { commodityId: 1, selected: [10], confidences: { 10: 0.6 }, step: 3, maxVisited: 3 },
        ];
        for (const state of mutations) {
            assert.equal(saveDraft(storage, key, state), 'saved');
        }

        // Tanpa jeda apa pun, draft terakhir langsung terbaca (simulasi kembali ke halaman).
        const draft = loadDraft(storage, key, { komoditas: KOMODITAS, gejala: GEJALA, gejalaMap: GEJALA_MAP });
        assert.equal(draft.commodityId, 1);
        assert.deepEqual(draft.selected, [10]);
        assert.deepEqual(draft.confidences, { 10: 0.6 });
        assert.equal(draft.step, 3);
    });

    it('state wizard penuh pulih persis untuk auto-restore (komoditas + gejala + keyakinan + step)', () => {
        const storage = memoryStorage();
        const key = draftStorageKey(1);
        saveDraft(storage, key, {
            commodityId: 1,
            selected: [10, 11],
            confidences: { 10: 0.8, 11: 1.0 },
            perKomoditas: { 2: { selected: [11], confidences: { 11: 0.4 } } },
            step: 4,
            maxVisited: 4,
        });

        const draft = loadDraft(storage, key, { komoditas: KOMODITAS, gejala: GEJALA, gejalaMap: GEJALA_MAP });
        assert.equal(draft.commodityId, 1);
        assert.deepEqual(draft.selected, [10, 11]);
        assert.deepEqual(draft.confidences, { 10: 0.8, 11: 1.0 });
        assert.deepEqual(draft.perKomoditas[2], { selected: [11], confidences: { 11: 0.4 } });
        assert.equal(draft.step, 4);
        assert.equal(draft.maxVisited, 4);
    });

    it('lampiran laporan gejala ikut tersimpan dan pulih saat pindah halaman', () => {
        const storage = memoryStorage();
        const key = draftStorageKey(1);
        saveDraft(storage, key, {
            commodityId: 1,
            selected: [10],
            confidences: { 10: 0.6 },
            laporanTerkait: [{ id: 5, report_code: 'LG-001' }, { id: '6', report_code: 'LG-002' }],
            step: 2,
            maxVisited: 2,
        });

        const draft = loadDraft(storage, key, { komoditas: KOMODITAS, gejala: GEJALA, gejalaMap: GEJALA_MAP });
        assert.deepEqual(draft.laporanTerkait, [{ id: 5, report_code: 'LG-001' }, { id: 6, report_code: 'LG-002' }]);
    });

    it('lampiran laporan tidak valid dibuang tanpa merusak draft', () => {
        const draft = sanitizeDraft({
            v: 1, commodityId: 1, selected: [10],
            confidences: { 10: 0.6 }, step: 2, maxVisited: 2,
            updatedAt: Date.now(),
            laporanTerkait: [{ id: 5, report_code: 'LG-001' }, { report_code: 'tanpa-id' }, { id: 5, report_code: 'duplikat' }, 'bukan-objek'],
        }, { komoditas: KOMODITAS, gejala: GEJALA, gejalaMap: GEJALA_MAP });

        assert.deepEqual(draft.laporanTerkait, [{ id: 5, report_code: 'LG-001' }]);
        assert.deepEqual(draft.selected, [10]);
    });

    it('draft lama tanpa laporanTerkait tetap terbaca (kompatibel mundur)', () => {
        const draft = sanitizeDraft({
            v: 1, commodityId: 1, selected: [10],
            confidences: {}, step: 2, maxVisited: 2,
            updatedAt: Date.now(),
        }, { komoditas: KOMODITAS, gejala: GEJALA, gejalaMap: GEJALA_MAP });

        assert.deepEqual(draft.laporanTerkait, []);
    });
});
