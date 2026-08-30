import { getRequestStatusLabel, getStatusConfig } from './statuses';

let drawerElements = null;
let previousFocusedElement = null;
let openCaseId = null;
let isInitialized = false;

export function formatDateTime(value) {
    if (value === null || value === undefined || String(value).trim() === '') {
        return '-';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        timeZone: 'Asia/Jakarta',
    }).format(date);
}

function displayValue(value) {
    if (value === null || value === undefined || String(value).trim() === '') {
        return '-';
    }

    return String(value);
}

function getDrawerElements() {
    return {
        drawer: document.querySelector('[data-case-detail-drawer]'),
        backdrop: document.querySelector('[data-case-detail-backdrop]'),
        closeButton: document.querySelector('[data-case-detail-close]'),
        caseCode: document.querySelector('[data-case-detail="case-code"]'),
        statusBadge: document.querySelector('[data-case-detail="status"]'),
        requestStatus: document.querySelector('[data-case-detail="request-status"]'),
        handlingStatus: document.querySelector('[data-case-detail="handling-status"]'),
        kelompokTani: document.querySelector('[data-case-detail="kelompok-tani"]'),
        commodity: document.querySelector('[data-case-detail="commodity"]'),
        commodityCode: document.querySelector('[data-case-detail="commodity-code"]'),
        disease: document.querySelector('[data-case-detail="disease"]'),
        regency: document.querySelector('[data-case-detail="regency"]'),
        district: document.querySelector('[data-case-detail="district"]'),
        popt: document.querySelector('[data-case-detail="popt"]'),
        latitude: document.querySelector('[data-case-detail="latitude"]'),
        longitude: document.querySelector('[data-case-detail="longitude"]'),
        updatedAt: document.querySelector('[data-case-detail="updated-at"]'),
        lastNote: document.querySelector('[data-case-detail="last-note"]'),
        timeline: document.querySelector('[data-case-detail-timeline]'),
    };
}

function setText(element, value) {
    if (element) {
        element.textContent = displayValue(value);
    }
}

function renderTimeline(history) {
    if (!drawerElements?.timeline) {
        return;
    }

    drawerElements.timeline.replaceChildren();

    if (!Array.isArray(history) || history.length === 0) {
        const emptyItem = document.createElement('li');
        emptyItem.className = 'rounded-lg border border-dashed border-[#d6e0d9] px-4 py-3 text-sm text-[#77847c]';
        emptyItem.textContent = 'Belum ada riwayat penanganan.';
        drawerElements.timeline.append(emptyItem);
        return;
    }

    // The M2 read contract returns history newest-first. Keep that contract
    // as the tie-breaker because SQLite can persist rapid transitions with
    // the same second-level timestamp during UAT seeding.
    const sortedHistory = history
        .map((entry, index) => ({ entry, index }))
        .sort((first, second) => {
            const timeDifference = new Date(first.entry.changed_at).getTime()
                - new Date(second.entry.changed_at).getTime();

            return timeDifference === 0
                ? second.index - first.index
                : timeDifference;
        })
        .map(({ entry }) => entry);

    sortedHistory.forEach((entry, index) => {
        const config = getStatusConfig(entry.status);
        const timelineItem = document.createElement('li');
        timelineItem.className = 'relative flex gap-3 pb-6 last:pb-0';

        const markerColumn = document.createElement('div');
        markerColumn.className = 'relative flex w-8 flex-shrink-0 justify-center';

        const marker = document.createElement('span');
        marker.className = `z-10 flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold shadow-sm ring-2 ring-white ${config.markerClass}`;
        marker.textContent = config.markerSymbol;
        marker.setAttribute('aria-label', config.label);

        if (index < sortedHistory.length - 1) {
            const connector = document.createElement('span');
            connector.className = 'absolute top-8 bottom-[-1.5rem] w-px bg-[#d6e0d9]';
            markerColumn.append(marker, connector);
        } else {
            markerColumn.append(marker);
        }

        const content = document.createElement('div');
        content.className = 'min-w-0 flex-1';

        const heading = document.createElement('div');
        heading.className = 'flex flex-wrap items-center gap-2';

        const statusLabel = document.createElement('h4');
        statusLabel.className = 'text-sm font-bold text-[#173b29]';
        statusLabel.textContent = config.label;
        heading.append(statusLabel);

        if (index === sortedHistory.length - 1) {
            const currentLabel = document.createElement('span');
            currentLabel.className = 'rounded-full bg-[#eef6f1] px-2 py-0.5 text-[10px] font-semibold text-[#2d6b4a]';
            currentLabel.textContent = 'Status terakhir';
            heading.append(currentLabel);
        }

        const changedAt = document.createElement('p');
        changedAt.className = 'mt-1 text-xs text-[#89968e]';
        changedAt.textContent = formatDateTime(entry.changed_at);

        content.append(heading, changedAt);

        if (entry.note) {
            const note = document.createElement('p');
            note.className = 'mt-2 text-sm leading-6 text-[#526159]';
            note.textContent = entry.note;
            content.append(note);
        }

        timelineItem.append(markerColumn, content);
        drawerElements.timeline.append(timelineItem);
    });
}

function renderCaseDetail(caseData) {
    const config = getStatusConfig(caseData.status);

    setText(drawerElements.caseCode, caseData.case_code);
    setText(drawerElements.kelompokTani, caseData.kelompok_tani?.nama);
    setText(drawerElements.commodity, caseData.komoditas?.nama);
    setText(drawerElements.commodityCode, caseData.komoditas?.kode);
    setText(drawerElements.disease, caseData.penyakit?.nama);
    setText(drawerElements.regency, caseData.wilayah?.kabupaten);
    setText(drawerElements.district, caseData.wilayah?.kecamatan);
    setText(drawerElements.popt, caseData.popt?.nama ?? 'Belum ditugaskan');
    setText(drawerElements.latitude, caseData.latitude);
    setText(drawerElements.longitude, caseData.longitude);
    setText(drawerElements.updatedAt, formatDateTime(caseData.last_status_at));
    setText(drawerElements.lastNote, caseData.last_note);

    if (drawerElements.statusBadge) {
        drawerElements.statusBadge.className = `inline-flex w-fit rounded-full px-3 py-1 text-xs font-bold ${config.badgeClass}`;
        drawerElements.statusBadge.textContent = config.label;
    }

    setText(drawerElements.requestStatus, getRequestStatusLabel(caseData.request_status));
    setText(drawerElements.handlingStatus, config.label);

    renderTimeline(caseData.status_history);
}

function handleKeydown(event) {
    if (event.key === 'Escape' && openCaseId !== null) {
        closeCaseDetail();
    }
}

export function initializeCaseDetailDrawer() {
    if (isInitialized) {
        return;
    }

    drawerElements = getDrawerElements();

    if (!drawerElements.drawer) {
        return;
    }

    drawerElements.closeButton?.addEventListener('click', closeCaseDetail);
    drawerElements.backdrop?.addEventListener('click', closeCaseDetail);
    document.addEventListener('keydown', handleKeydown);
    isInitialized = true;
}

export function openCaseDetail(caseData) {
    initializeCaseDetailDrawer();

    if (!drawerElements?.drawer || !caseData) {
        return;
    }

    if (openCaseId === null) {
        previousFocusedElement = document.activeElement instanceof HTMLElement
            ? document.activeElement
            : null;
    }

    openCaseId = caseData.case_id;
    renderCaseDetail(caseData);

    drawerElements.drawer.setAttribute('aria-hidden', 'false');
    drawerElements.drawer.removeAttribute('inert');
    drawerElements.drawer.classList.remove('translate-x-full');
    drawerElements.backdrop?.removeAttribute('hidden');
    document.body.classList.add('overflow-hidden');

    window.requestAnimationFrame(() => drawerElements.closeButton?.focus());
}

export function closeCaseDetail() {
    if (!drawerElements?.drawer) {
        return;
    }

    drawerElements.drawer.setAttribute('aria-hidden', 'true');
    drawerElements.drawer.setAttribute('inert', '');
    drawerElements.drawer.classList.add('translate-x-full');
    drawerElements.backdrop?.setAttribute('hidden', 'hidden');
    document.body.classList.remove('overflow-hidden');
    openCaseId = null;

    if (previousFocusedElement?.focus) {
        previousFocusedElement.focus();
    }

    previousFocusedElement = null;
}

export function getOpenCaseId() {
    return openCaseId;
}
