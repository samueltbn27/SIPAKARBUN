import definitions from '../../../config/monitoring-statuses.json' with { type: 'json' };

// Labels, order, colors and symbols come from the same definitions as PHP.
// Technical-state/deadline mapping is performed exclusively by the backend.
export const STATUS_CONFIG = definitions;
export const STATUS_ORDER = Object.keys(definitions);

export function getStatusConfig(status) {
    return STATUS_CONFIG[status] || {
        label: 'Belum tersedia', description: 'Status belum tersedia.',
        iconPath: 'M12 8v4m0 4h.01', markerClass: 'bg-[#526159] text-white',
        badgeClass: 'bg-[#edf1ee] text-[#526159]', chartColor: '#526159',
    };
}

export function getStatusOptions() {
    return STATUS_ORDER.map((value) => ({ value, label: STATUS_CONFIG[value].label }));
}
