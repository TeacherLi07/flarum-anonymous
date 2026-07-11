import Component from 'flarum/common/Component';

export default class BiscuitIdenticon extends Component {
    view() {
        const { biscuitString, size } = this.attrs;
        const s = size || 36;
        const hash = this.hashCode(biscuitString || '');
        const hue = Math.abs(hash) % 360;
        const fg = 'hsl(' + hue + ', 50%, 45%)';
        const bg = 'hsl(' + hue + ', 25%, 88%)';

        const grid = this.generateGrid(hash);

        const cellSize = s / 5;
        const rects = [];

        for (let row = 0; row < 5; row++) {
            for (let col = 0; col < 5; col++) {
                if (grid[row][col]) {
                    const x = col * cellSize;
                    const y = row * cellSize;
                    rects.push(
                        <rect key={row + '-' + col} x={x} y={y} width={cellSize} height={cellSize} fill={fg} rx={cellSize * 0.15} />
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
        let hash = 5381;
        for (let i = 0; i < str.length; i++) {
            hash = ((hash << 5) + hash) + str.charCodeAt(i);
            hash = hash & hash;
        }
        return hash;
    }

    generateGrid(seed) {
        const grid = [];
        let s = Math.abs(seed);

        for (let row = 0; row < 5; row++) {
            grid[row] = [];
            for (let col = 0; col < 3; col++) {
                s = (s * 1103515245 + 12345) & 0x7fffffff;
                grid[row][col] = (s & 1) === 1;
                grid[row][4 - col] = grid[row][col];
            }
        }

        return grid;
    }
}
