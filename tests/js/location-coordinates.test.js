import assert from 'node:assert/strict';
import { test } from 'node:test';
import {
    parseCaseLocation,
    parseCoordinate,
    parseReferenceLocation,
} from '../../resources/js/permohonan/location-coordinates.js';

test('parseCoordinate does not coerce missing reference values to zero', () => {
    for (const value of [null, undefined, '', '   ']) {
        assert.equal(parseCoordinate(value, -90, 90), null);
    }
});

test('parseCoordinate accepts numeric strings within the coordinate bounds', () => {
    assert.equal(parseCoordinate('-7.0029856', -90, 90), -7.0029856);
    assert.equal(parseCoordinate('108.1337070', -180, 180), 108.133707);
    assert.equal(parseCoordinate('0', -90, 90), 0);
    assert.equal(parseCoordinate('91', -90, 90), null);
    assert.equal(parseCoordinate('181', -180, 180), null);
});

test('reference location rejects an empty pair and the fake zero coordinate', () => {
    assert.equal(parseReferenceLocation(null, null), null);
    assert.equal(parseReferenceLocation('', ''), null);
    assert.equal(parseReferenceLocation('0', '0'), null);
    assert.deepEqual(parseReferenceLocation('-7.0029856', '108.1337070'), {
        lat: -7.0029856,
        lng: 108.133707,
    });
});

test('case location preserves a valid explicit zero coordinate when supplied', () => {
    assert.deepEqual(parseCaseLocation('0', '107.5'), { lat: 0, lng: 107.5 });
    assert.equal(parseCaseLocation('', '107.5'), null);
});

test('reselecting a Poktan returns the latest coordinate and clears an invalid replacement', () => {
    const anugrah = parseReferenceLocation('-7.1719511', '107.8224774');
    const arosta = parseReferenceLocation('-7.0029856', '108.1337070');
    const unavailable = parseReferenceLocation('', '');

    assert.deepEqual(anugrah, { lat: -7.1719511, lng: 107.8224774 });
    assert.deepEqual(arosta, { lat: -7.0029856, lng: 108.133707 });
    assert.notDeepEqual(arosta, anugrah);
    assert.equal(unavailable, null);
});
