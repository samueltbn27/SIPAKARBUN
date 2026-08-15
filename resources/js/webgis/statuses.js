export const STATUS_ORDER = [
    'assigned',
    'under_review',
    'postponed',
    'ready_for_execution',
    'in_progress',
    'completed',
];

export const STATUS_CONFIG = {
    assigned: {
        label: 'Ditugaskan',
        markerSymbol: '●',
        markerClass: 'bg-[#8b6cc7] text-white',
        badgeClass: 'bg-[#f0eafa] text-[#5e4a9a]',
        chartColor: '#8b6cc7',
    },
    under_review: {
        label: 'Sedang Direview',
        markerSymbol: '◉',
        markerClass: 'bg-[#3d8eb9] text-white',
        badgeClass: 'bg-[#e8f3f9] text-[#246384]',
        chartColor: '#3d8eb9',
    },
    postponed: {
        label: 'Ditunda',
        markerSymbol: '◆',
        markerClass: 'bg-[#b8860b] text-white',
        badgeClass: 'bg-[#fbf4df] text-[#80610a]',
        chartColor: '#b8860b',
    },
    ready_for_execution: {
        label: 'Siap Dieksekusi',
        markerSymbol: '▲',
        markerClass: 'bg-[#5a8d6c] text-white',
        badgeClass: 'bg-[#eaf3ed] text-[#3d6e4e]',
        chartColor: '#5a8d6c',
    },
    in_progress: {
        label: 'Dalam Pelaksanaan',
        markerSymbol: '⬢',
        markerClass: 'bg-[#176b45] text-white',
        badgeClass: 'bg-[#e2f0e8] text-[#176b45]',
        chartColor: '#176b45',
    },
    completed: {
        label: 'Selesai',
        markerSymbol: '✓',
        markerClass: 'bg-[#526159] text-white',
        badgeClass: 'bg-[#edf1ee] text-[#526159]',
        chartColor: '#526159',
    },
};

const FALLBACK_STATUS = {
    label: 'Status tidak diketahui',
    markerSymbol: '?',
    markerClass: 'bg-[#526159] text-white',
    badgeClass: 'bg-[#edf1ee] text-[#526159]',
    chartColor: '#526159',
};

export function getStatusConfig(status) {
    return STATUS_CONFIG[status] || FALLBACK_STATUS;
}

export function getStatusOptions() {
    return STATUS_ORDER.map((value) => ({
        value,
        label: STATUS_CONFIG[value].label,
    }));
}
