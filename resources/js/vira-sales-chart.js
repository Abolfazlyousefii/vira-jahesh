/** Pure geometry helper. Money remains on the server; JS only plots relative heights. */
export function chartIsEmpty(points) {
    return points.length === 0 || points.every(point => Number(point.amount) === 0);
}

export function chartShape(points) {
    if (chartIsEmpty(points)) return { line: '', area: '', labels: [] };

    const max = Math.max(1, ...points.map(point => Number(point.amount) || 0));
    const labels = points.map((point, index) => ({ label: point.label ?? '', x: points.length === 1 ? 360 : 12 + (696 * index) / (points.length - 1) }));
    const coords = points.map((point, index) => {
        const x = labels[index].x;
        const y = 155 - ((Number(point.amount) || 0) / max) * 125;
        return [Number(x.toFixed(2)), Number(y.toFixed(2))];
    });
    const line = coords.map(([x, y], index) => `${index ? 'L' : 'M'} ${x} ${y}`).join(' ');
    const area = `${line} L ${coords.at(-1)[0]} 160 L ${coords[0][0]} 160 Z`;
    return { line, area, labels };
}
