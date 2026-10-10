/**
 * Draft autosave untuk wizard Diagnosis (localStorage, per user).
 *
 * Modul ini murni (tanpa Alpine/DOM) supaya bisa diuji via
 * `npm run test:provider`. Komponen Alpine di
 * `resources/views/diagnosis/create.blade.php` memakai modul ini lewat
 * `window.SipakarbunDiagnosisDraft` (didaftarkan di `resources/js/app.js`).
 */
export const DRAFT_VERSION = 1;
export const DRAFT_EXPIRY_MS = 7 * 24 * 3600 * 1000;
export const CONF_LEVELS = [0.2, 0.4, 0.6, 0.8, 1.0];

export function draftStorageKey(userId) {
    return `sipakarbun.diagnosis.v1:${userId}`;
}

function toNumberId(value) {
    const n = Number(value);
    return Number.isFinite(n) ? n : null;
}

function toConfidences(selected, rawConfidences) {
    const confidences = {};
    selected.forEach((id) => {
        const v = Number(rawConfidences?.[id] ?? 0.8);
        confidences[id] = CONF_LEVELS.includes(v) ? v : 0.8;
    });
    return confidences;
}

/**
 * Id gejala yang valid untuk satu komoditas. Bila context membawa
 * `gejalaMap` (commodityId → daftar id), pakai itu; bila tidak, pakai
 * daftar gejala global (kompatibel mundur).
 */
function validIdsForCommodity(commodityId, context) {
    const { gejala = [], gejalaMap = null } = context;
    const scoped = gejalaMap?.[commodityId] ?? gejalaMap?.[Number(commodityId)];

    if (Array.isArray(scoped)) {
        return new Set(scoped.map((g) => Number(g?.id ?? g)).filter((n) => Number.isFinite(n)));
    }

    return new Set(gejala.map((g) => Number(g?.id)).filter((n) => Number.isFinite(n)));
}

function cleanSelection(rawSelected, validIds) {
    return (Array.isArray(rawSelected) ? rawSelected : [])
        .map(toNumberId)
        .filter((id) => id !== null && validIds.has(id));
}

/**
 * Normalisasi lampiran laporan gejala baru. Disimpan sebagai
 * [{id, report_code}] agar ikut pulih saat pindah halaman.
 * Entri tanpa id valid dibuang; report_code boleh kosong.
 */
function cleanLaporanTerkait(rawLaporan) {
    if (!Array.isArray(rawLaporan)) {
        return [];
    }
    const seen = new Set();
    const cleaned = [];
    rawLaporan.forEach((item) => {
        const id = toNumberId(item?.id);
        if (id === null || seen.has(id)) {
            return;
        }
        seen.add(id);
        const code = typeof item?.report_code === 'string' ? item.report_code.slice(0, 64) : '';
        cleaned.push({ id, report_code: code });
    });
    return cleaned.slice(0, 50);
}

/**
 * Validasi + normalisasi draft mentah. Kembalikan draft bersih atau null
 * bila kedaluwarsa / komoditas tidak dikenal / versi beda.
 *
 * Draft komoditas-saja (belum pilih gejala) TETAP valid: `selected`
 * boleh kosong, dan step/maxVisited dijepit maksimal ke Step 2 agar
 * pengguna dilanjutkan ke pemilihan gejala, bukan ke keyakinan/proses.
 *
 * `perKomoditas` menyimpan pilihan per komoditas ({[id]: {selected,
 * confidences}}) agar ganti komoditas tidak menghapus pilihan lama
 * diam-diam — pilihan dipulihkan saat pengguna kembali ke komoditas itu.
 */
export function sanitizeDraft(raw, context = {}) {
    const { komoditas = [], now = Date.now() } = context;

    if (!raw || typeof raw !== 'object' || raw.v !== DRAFT_VERSION) {
        return null;
    }

    if (!raw.commodityId) {
        return null;
    }

    if (now - (Number(raw.updatedAt) || 0) > DRAFT_EXPIRY_MS) {
        return null;
    }

    const commodityKnown = komoditas.some((k) => Number(k?.id) === Number(raw.commodityId));
    if (!commodityKnown) {
        return null;
    }

    const selected = cleanSelection(raw.selected, validIdsForCommodity(raw.commodityId, context));
    const confidences = toConfidences(selected, raw.confidences);

    const perKomoditas = {};
    const rawPerKomoditas = raw.perKomoditas;
    if (rawPerKomoditas && typeof rawPerKomoditas === 'object') {
        Object.entries(rawPerKomoditas).forEach(([cmdId, entry]) => {
            const id = toNumberId(cmdId);
            if (id === null || entry === null || typeof entry !== 'object') {
                return;
            }
            const entrySelected = cleanSelection(entry.selected, validIdsForCommodity(id, context));
            if (entrySelected.length === 0) {
                return;
            }
            perKomoditas[id] = {
                selected: entrySelected,
                confidences: toConfidences(entrySelected, entry.confidences),
            };
        });
    }

    const clampStep = (n) => Math.min(Math.max(Number(n) || 1, 1), 4);
    // Tanpa gejala terpilih, langkah terjauh yang masuk akal adalah Step 2.
    const maxStep = selected.length === 0 ? 2 : 4;

    return {
        v: DRAFT_VERSION,
        commodityId: raw.commodityId,
        selected,
        confidences,
        perKomoditas,
        laporanTerkait: cleanLaporanTerkait(raw.laporanTerkait),
        step: Math.min(clampStep(raw.step), maxStep),
        maxVisited: Math.min(clampStep(raw.maxVisited), maxStep),
        updatedAt: Number(raw.updatedAt),
    };
}

export function loadDraft(storage, key, context = {}) {
    try {
        const rawText = storage.getItem(key);
        if (!rawText) {
            return null;
        }
        return sanitizeDraft(JSON.parse(rawText), context);
    } catch {
        return null;
    }
}

export function saveDraft(storage, key, state) {
    try {
        const selected = Array.isArray(state.selected) ? state.selected.map(Number) : [];
        const rawCommodityId = state.commodityId;
        // Normalisasi ke Number agar lolos pemeriksaan `commodityKnown`
        // di sanitizeDraft saat draft dimuat ulang. Nilai kosong
        // (''/null/undefined) dibiarkan apa adanya supaya aturan
        // "state kosong → hapus draft" tetap berlaku.
        const commodityId = rawCommodityId === '' || rawCommodityId === null || rawCommodityId === undefined
            ? rawCommodityId
            : (toNumberId(rawCommodityId) ?? rawCommodityId);
        if (!commodityId && selected.length === 0) {
            storage.removeItem(key);
            return 'cleared';
        }
        const perKomoditas = {};
        const rawPerKomoditas = state.perKomoditas;
        if (rawPerKomoditas && typeof rawPerKomoditas === 'object') {
            Object.entries(rawPerKomoditas).forEach(([cmdId, entry]) => {
                const id = toNumberId(cmdId);
                const entrySelected = Array.isArray(entry?.selected)
                    ? entry.selected.map(Number).filter((n) => Number.isFinite(n))
                    : [];
                if (id === null || entrySelected.length === 0) {
                    return;
                }
                perKomoditas[id] = {
                    selected: entrySelected,
                    confidences: entry?.confidences ?? {},
                };
            });
        }
        storage.setItem(key, JSON.stringify({
            v: DRAFT_VERSION,
            commodityId,
            selected,
            confidences: state.confidences ?? {},
            perKomoditas,
            laporanTerkait: cleanLaporanTerkait(state.laporanTerkait),
            step: state.step ?? 1,
            maxVisited: state.maxVisited ?? 1,
            updatedAt: Date.now(),
        }));
        return 'saved';
    } catch {
        return 'failed';
    }
}

export function clearDraft(storage, key) {
    try {
        storage.removeItem(key);
    } catch {
        // abaikan (mode privat dsb.)
    }
}

export function formatDraftDateTime(timestamp, locale = 'id-ID') {
    return new Date(timestamp).toLocaleString(locale, {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
}
