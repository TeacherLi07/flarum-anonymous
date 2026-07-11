import Component from 'flarum/common/Component';

export default class BiscuitIdenticon extends Component {
    view() {
        const { biscuitString, size } = this.attrs;
        const s = size || 36;
        const hash = this.hashCode(biscuitString || '');
        const hue = Math.abs(hash) % 360;
        const fg = 'hsl(' + hue + ', 55%, 40%)';
        const bg = 'hsl(' + hue + ', 20%, 90%)';

        const cellSize = s / 5;
        const rects = [];

        for (let row = 0; row < 5; row++) {
            for (let col = 0; col < 3; col++) {
                const idx = row * 3 + col;
                const byte = (hash >> (idx * 2)) & 3;
                if (byte >= 1) {
                    const x = col * cellSize;
                    const y = row * cellSize;
                    rects.push(
                        <rect key={row + '-' + col} x={x} y={y} width={cellSize} height={cellSize} fill={fg} />
                    );
                    rects.push(
                        <rect key={row + '-' + (4-col)} x={(4 - col) * cellSize} y={y} width={cellSize} height={cellSize} fill={fg} />
                    );
                }
            }
        }

        return (
            <svg className="BiscuitIdenticon" width={s} height={s} viewBox={'0 0 ' + s + ' ' + s} xmlns="http://www.w3.org/2000/svg">
                <rect width={s} height={s} fill={bg} rx={s * 0.15} />
                {rects}
            </svg>
        );
    }

    hashCode(str) {
        let h1 = 0xdeadbeef ^ str.length;
        let h2 = 0x41c6ce57 ^ str.length;
        for (let i = 0; i < str.length; i++) {
            const ch = str.charCodeAt(i);
            h1 = Math.imul(h1 ^ ch, 2654435761);
            h2 = Math.imul(h2 ^ ch, 1597334677);
        }
        h1 = Math.imul(h1 ^ (h1 >>> 16), 2246822507);
        h1 ^= Math.imul(h2 ^ (h2 >>> 13), 3266489909);
        h2 = Math.imul(h2 ^ (h2 >>> 16), 2246822507);
        h2 ^= Math.imul(h1 ^ (h1 >>> 13), 3266489909);
        return (h2 >>> 0) * 4096 + (h1 >>> 0);
    }
}
