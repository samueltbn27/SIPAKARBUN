export const EMPTY_FILTERS = {
    status: '',
    commodity: '',
    regency: '',
    district: '',
    disease: '',
    popt: '',
};

function normalizeFilterValue(value) {
    return value === null || value === undefined ? '' : String(value);
}

function uniqueOptions(cases, getOption) {
    const optionsByValue = new Map();

    cases.forEach((caseData) => {
        const option = getOption(caseData);

        if (!option || !option.value || !option.label) {
            return;
        }

        optionsByValue.set(normalizeFilterValue(option.value), {
            value: normalizeFilterValue(option.value),
            label: option.label,
        });
    });

    return [...optionsByValue.values()].sort((first, second) => first.label.localeCompare(second.label, 'id'));
}

export function createFilterState() {
    return { ...EMPTY_FILTERS };
}

export function getUniqueCommodities(cases) {
    return uniqueOptions(cases, (caseData) => ({
        value: caseData.komoditas?.id,
        label: caseData.komoditas?.nama,
    }));
}

export function getUniqueDiseases(cases) {
    return uniqueOptions(cases, (caseData) => ({
        value: caseData.penyakit?.id,
        label: caseData.penyakit?.nama,
    }));
}

export function getUniquePopts(cases) {
    return uniqueOptions(cases, (caseData) => ({
        value: caseData.popt?.id,
        label: caseData.popt?.nama,
    }));
}

export function getUniqueRegencies(cases) {
    return uniqueOptions(cases, (caseData) => ({
        value: caseData.wilayah?.kode_kabupaten,
        label: caseData.wilayah?.kabupaten,
    }));
}

export function getDistrictsForRegency(cases, regencyCode) {
    const normalizedRegencyCode = normalizeFilterValue(regencyCode);

    return uniqueOptions(
        cases.filter((caseData) => normalizeFilterValue(caseData.wilayah?.kode_kabupaten) === normalizedRegencyCode),
        (caseData) => ({
            value: caseData.wilayah?.kode_kecamatan,
            label: caseData.wilayah?.kecamatan,
        }),
    );
}

export function applyFilters(cases, filters) {
    return cases.filter((caseData) => (
        (!filters.status || caseData.status === filters.status)
        && (!filters.commodity || normalizeFilterValue(caseData.komoditas?.id) === filters.commodity)
        && (!filters.regency || normalizeFilterValue(caseData.wilayah?.kode_kabupaten) === filters.regency)
        && (!filters.district || normalizeFilterValue(caseData.wilayah?.kode_kecamatan) === filters.district)
        && (!filters.disease || normalizeFilterValue(caseData.penyakit?.id) === filters.disease)
        && (!filters.popt || normalizeFilterValue(caseData.popt?.id) === filters.popt)
    ));
}

export function countActiveFilters(filters) {
    return Object.values(filters).filter(Boolean).length;
}
