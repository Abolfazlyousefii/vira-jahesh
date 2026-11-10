import test from 'node:test';
import assert from 'node:assert/strict';
import { chartShape, chartIsEmpty } from '../../resources/js/vira-sales-chart.js';

test('all zero days produce an empty chart instead of fake activity', () => {
    assert.equal(chartIsEmpty([{amount: 0}, {amount: 0}]), true);
    assert.equal(chartShape([{amount: 0}, {amount: 0}]).line, '');
});

test('chart stays inside viewbox and preserves the order of daily points', () => {
    const chart = chartShape([{amount: 0}, {amount: 50}, {amount: 100}]);
    assert.match(chart.line, /^M 12 155 L 360 92\.5 L 708 30$/);
    assert.match(chart.area, /Z$/);
    assert.equal(chart.labels.length, 3);
});

test('one day series renders without zero division or nonfinite points', () => {
    const chart = chartShape([{amount: 10}]);
    assert.doesNotMatch(chart.line, /NaN|Infinity/);
});
