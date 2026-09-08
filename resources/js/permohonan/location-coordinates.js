export function parseCoordinate(value, minimum, maximum) {
    if (value === null || value === undefined || String(value).trim() === '') {
        return null;
    }

    const number = Number(value);

    return Number.isFinite(number) && number >= minimum && number <= maximum ? number : null;
}

export function parseCaseLocation(latitude, longitude) {
    const lat = parseCoordinate(latitude, -90, 90);
    const lng = parseCoordinate(longitude, -180, 180);

    return lat === null || lng === null ? null : { lat, lng };
}

export function parseReferenceLocation(latitude, longitude) {
    const location = parseCaseLocation(latitude, longitude);

    if (location === null || (location.lat === 0 && location.lng === 0)) {
        return null;
    }

    return location;
}
