export const STATUS_ORDER = [
    'menunggu_penanganan',
    'dalam_penanganan',
    'ditunda',
    'melewati_batas_waktu',
    'selesai',
];

export const STATUS_CONFIG = {
    menunggu_penanganan: {
        label: 'Menunggu Penanganan',
        description: 'Kasus menunggu penugasan atau penerimaan POPT.',
        iconPath: 'M12 8v4l3 2 M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0',
        markerClass: 'bg-[#c47a16] text-white', badgeClass: 'bg-[#fff3df] text-[#8a560d]', chartColor: '#c47a16',
    },
    dalam_penanganan: {
        label: 'Dalam Penanganan',
        description: 'Kasus sedang ditinjau atau ditangani oleh petugas.',
        iconPath: 'M14.7 6.3a5 5 0 0 0-6.4 6.4l-5 5a2.1 2.1 0 0 0 3 3l5-5a5 5 0 0 0 6.4-6.4l-3 3-3-3z',
        markerClass: 'bg-[#176b45] text-white', badgeClass: 'bg-[#e2f0e8] text-[#176b45]', chartColor: '#176b45',
    },
    ditunda: {
        label: 'Ditunda',
        description: 'Penanganan dijeda dan dapat dilanjutkan oleh petugas.',
        iconPath: 'M7 5h3v14H7z M14 5h3v14h-3z',
        markerClass: 'bg-[#b8860b] text-white', badgeClass: 'bg-[#fbf4df] text-[#80610a]', chartColor: '#b8860b',
    },
    melewati_batas_waktu: {
        label: 'Melewati Batas Waktu',
        description: 'Target penyelesaian telah terlewati.',
        iconPath: 'M12 9v4m0 4h.01 M10.3 3.5 2.7 17a2 2 0 0 0 1.8 3h15a2 2 0 0 0 1.8-3L13.7 3.5a2 2 0 0 0-3.4 0Z',
        markerClass: 'bg-[#a83d32] text-white', badgeClass: 'bg-[#fff0ed] text-[#a83d32]', chartColor: '#a83d32',
    },
    selesai: {
        label: 'Selesai',
        description: 'Penanganan kasus telah diselesaikan.',
        iconPath: 'M12 3l8 3v6c0 5-8 9-8 9s-8-4-8-9V6z M8 12l3 3 5-6',
        markerClass: 'bg-[#526159] text-white', badgeClass: 'bg-[#edf1ee] text-[#526159]', chartColor: '#526159',
    },
};

export const REQUEST_STATUS_LABELS = {
    diajukan: 'Diajukan',
    sedang_direview: 'Sedang Direview',
    diterima: 'Diterima',
    ditolak: 'Ditolak',
};

export function getStatusConfig(status) {
    return STATUS_CONFIG[status] || STATUS_CONFIG.menunggu_penanganan;
}

export function getStatusOptions() {
    return STATUS_ORDER.map((value) => ({ value, label: STATUS_CONFIG[value].label }));
}

export function getRequestStatusLabel(status) {
    return REQUEST_STATUS_LABELS[status] || status || '-';
}
