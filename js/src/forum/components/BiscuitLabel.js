import Component from 'flarum/common/Component';
import Link from 'flarum/common/components/Link';

export default class BiscuitLabel extends Component {
    view() {
        const { biscuitString, isDeleted, isFrozen } = this.attrs;
        const firstChar = biscuitString ? biscuitString.charAt(0).toUpperCase() : '?';
        const bgColor = this.stringToColor(biscuitString || '');
        const fgColor = this.contrastColor(bgColor);

        let className = 'BiscuitAvatar';
        let style = 'background-color: ' + bgColor + '; color: ' + fgColor + ';';

        return (
            <span className="BiscuitLabel">
                <Link href={app.route('biscuitProfile', { biscuitString })} className="BiscuitAvatarLink">
                    <span className={'BiscuitAvatar' + (isDeleted ? ' BiscuitAvatar--deleted' : '') + (isFrozen ? ' BiscuitAvatar--frozen' : '')} style={style}>
                        {firstChar}
                    </span>
                    <span className={'BiscuitLabel-string' + (isDeleted ? ' BiscuitLabel--deleted' : '') + (isFrozen ? ' BiscuitLabel--frozen' : '')}>
                        {biscuitString}
                    </span>
                </Link>
            </span>
        );
    }

    stringToColor(str) {
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            hash = str.charCodeAt(i) + ((hash << 5) - hash);
        }
        const h = Math.abs(hash) % 360;
        return 'hsl(' + h + ', 45%, 55%)';
    }

    contrastColor(hslColor) {
        // Extract lightness from HSL
        const match = hslColor.match(/(\d+)%/);
        if (match && parseInt(match[1]) > 55) return '#333';
        return '#fff';
    }
}
