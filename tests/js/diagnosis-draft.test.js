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
        assert.equal(sanitizeDraft({ v: 1, commodityId: 1, selected: [999], updatedAt: now }, { komoditas: KOMODITAS, gejala: GEJALA }), null);

        const storage = memoryStorage({ k: '{bukan json' });
        assert.equal(loadDraft(storage, 'k', { komoditas: KOMODITAS, gejala: GEJALA }), null);
        assert.equal(loadDraft(memoryStorage(), 'kosong', { komoditas: KOMODITAS, gejala: GEJALA }), null);
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
});
