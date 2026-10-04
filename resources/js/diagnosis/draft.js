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

/**
 * Validasi + normalisasi draft mentah. Kembalikan draft bersih atau null
 * bila kedaluwarsa / komoditas tidak dikenal / tak ada gejala valid.
 */
export function sanitizeDraft(raw, context = {}) {
    const { komoditas = [], gejala = [], now = Date.now() } = context;

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

    const validIds = new Set(gejala.map((g) => Number(g?.id)).filter((n) => Number.isFinite(n)));
    const selected = (Array.isArray(raw.selected) ? raw.selected : [])
        .map(toNumberId)
        .filter((id) => id !== null && validIds.has(id));

    if (selected.length === 0) {
        return null;
    }

    const confidences = {};
    selected.forEach((id) => {
        const v = Number(raw.confidences?.[id] ?? 0.8);
        confidences[id] = CONF_LEVELS.includes(v) ? v : 0.8;
    });

    const clampStep = (n) => Math.min(Math.max(Number(n) || 1, 1), 4);

    return {
        v: DRAFT_VERSION,
        commodityId: raw.commodityId,
        selected,
        confidences,
        step: clampStep(raw.step),
        maxVisited: clampStep(raw.maxVisited),
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
        if (!state.commodityId && selected.length === 0) {
            storage.removeItem(key);
            return 'cleared';
        }
        storage.setItem(key, JSON.stringify({
            v: DRAFT_VERSION,
            commodityId: state.commodityId,
            selected,
            confidences: state.confidences ?? {},
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
