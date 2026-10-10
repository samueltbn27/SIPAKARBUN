import { getStatusConfig } from './statuses';

// SVG geometry comes only from the local status catalog, never from case data.
export function createStatusSymbol(status, { pin = false } = {}) {
    const config = getStatusConfig(status);
    const symbol = document.createElement('span');
    symbol.className = `webgis-status-symbol${pin ? ' webgis-status-symbol--pin' : ''}`;
    symbol.style.setProperty('--status-color', config.chartColor);
    symbol.setAttribute('aria-hidden', 'true');

    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('viewBox', '0 0 24 24');
    svg.setAttribute('width', '22');
    svg.setAttribute('height', '22');
    svg.setAttribute('fill', 'none');
    svg.setAttribute('stroke', 'currentColor');
    svg.setAttribute('stroke-width', '2');
    svg.setAttribute('stroke-linecap', 'round');
    svg.setAttribute('stroke-linejoin', 'round');
    svg.setAttribute('focusable', 'false');
    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    path.setAttribute('d', config.iconPath);
    svg.append(path);
    symbol.append(svg);
    return symbol;
}
