export const STATUS_ORDER = [
    'accepted',
    'assigned',
    'under_review',
    'postponed',
    'ready_for_execution',
    'in_progress',
    'completed',
    'unknown',
];

// M2 handling statuses are normalized here once for every M3 consumer.
export const M2_STATUS_MAP = {
    diterima: 'accepted',
    ditugaskan: 'assigned',
    sedang_direview: 'under_review',
    ditunda: 'postponed',
    siap_dieksekusi: 'ready_for_execution',
    dalam_pelaksanaan: 'in_progress',
    selesai: 'completed',
};

export const REQUEST_STATUS_LABELS = {
    diajukan: 'Diajukan',
    sedang_direview: 'Sedang Direview',
    diterima: 'Diterima',
    ditolak: 'Ditolak',
};

export const STATUS_CONFIG = {
    accepted: {
        label: 'Diterima — Menunggu Penugasan',
        description: 'Kasus diterima dan menunggu penugasan POPT.',
        iconPath: 'M12 8v4l3 2 M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0',
        markerClass: 'bg-[#c47a16] text-white',
        badgeClass: 'bg-[#fff3df] text-[#8a560d]',
        chartColor: '#c47a16',
    },
    assigned: {
        label: 'Ditugaskan',
        description: 'POPT sudah ditunjuk untuk menangani kasus.',
        iconPath: 'M15 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0 M3 21v-2a6 6 0 0 1 10-4 M16 18l2 2 4-5',
        markerClass: 'bg-[#8b6cc7] text-white',
        badgeClass: 'bg-[#f0eafa] text-[#5e4a9a]',
        chartColor: '#8b6cc7',
    },
    under_review: {
        label: 'Sedang Direview',
        description: 'Kasus sedang ditinjau oleh petugas.',
        iconPath: 'M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0 M15 15l6 6 M7 10h6',
        markerClass: 'bg-[#3d8eb9] text-white',
        badgeClass: 'bg-[#e8f3f9] text-[#246384]',
        chartColor: '#3d8eb9',
    },
    postponed: {
        label: 'Ditunda',
        description: 'Penanganan dijeda. Lihat alasan pada riwayat kasus.',
        iconPath: 'M7 5h3v14H7z M14 5h3v14h-3z',
        markerClass: 'bg-[#b8860b] text-white',
        badgeClass: 'bg-[#fbf4df] text-[#80610a]',
        chartColor: '#b8860b',
    },
    ready_for_execution: {
        label: 'Siap Dieksekusi',
        description: 'Kasus siap memasuki tahap pelaksanaan.',
        iconPath: 'M8 4l12 8-12 8z',
        markerClass: 'bg-[#5a8d6c] text-white',
        badgeClass: 'bg-[#eaf3ed] text-[#3d6e4e]',
        chartColor: '#5a8d6c',
    },
    in_progress: {
        label: 'Dalam Pelaksanaan',
        description: 'Kegiatan penanganan sedang berlangsung.',
        iconPath: 'M14.7 6.3a5 5 0 0 0-6.4 6.4l-5 5a2.1 2.1 0 0 0 3 3l5-5a5 5 0 0 0 6.4-6.4l-3 3-3-3z',
        markerClass: 'bg-[#176b45] text-white',
        badgeClass: 'bg-[#e2f0e8] text-[#176b45]',
        chartColor: '#176b45',
    },
    completed: {
        label: 'Selesai',
        description: 'Penanganan kasus telah diselesaikan.',
        iconPath: 'M12 3l8 3v6c0 5-8 9-8 9s-8-4-8-9V6z M8 12l3 3 5-6',
        markerClass: 'bg-[#526159] text-white',
        badgeClass: 'bg-[#edf1ee] text-[#526159]',
        chartColor: '#526159',
    },
    unknown: {
        label: 'Status tidak diketahui',
        description: 'Status belum tersedia atau belum dikenali.',
        iconPath: 'M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0 M9.1 9a3 3 0 0 1 5.8 1c0 2-3 2-3 4 M12 17h.01',
        markerClass: 'bg-[#526159] text-white',
        badgeClass: 'bg-[#edf1ee] text-[#526159]',
        chartColor: '#526159',
    },
};

export function getStatusConfig(status) {
    return STATUS_CONFIG[status] || STATUS_CONFIG.unknown;
}

export function normalizeHandlingStatus(status) {
    if (status === null || status === undefined || status === '') {
        return null;
    }

    if (Object.prototype.hasOwnProperty.call(M2_STATUS_MAP, status)) {
        return M2_STATUS_MAP[status];
    }

    return STATUS_ORDER.includes(status) ? status : 'unknown';
}

export function getStatusOptions() {
    return STATUS_ORDER.map((value) => ({
        value,
        label: STATUS_CONFIG[value].label,
    }));
}

export function getRequestStatusLabel(status) {
    return REQUEST_STATUS_LABELS[status] || status || '-';
}
