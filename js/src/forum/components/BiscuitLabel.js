import Component from 'flarum/common/Component';
import Link from 'flarum/common/components/Link';

export default class BiscuitLabel extends Component {
    view() {
        const { biscuitString, isDeleted, isFrozen } = this.attrs;

        let className = 'BiscuitLabel';
        if (isDeleted) className += ' BiscuitLabel--deleted';
        if (isFrozen) className += ' BiscuitLabel--frozen';

        return (
            <Link href={app.route('biscuitProfile', { biscuitString })} className={className}>
                {biscuitString}
            </Link>
        );
    }
}
