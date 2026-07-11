import Component from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';

export default class BiscuitList extends Component {
    oninit(vnode) {
        super.oninit(vnode);
    }

    view() {
        const { onSelectForFreeze, selectedIds, biscuits } = this.attrs;

        if (!biscuits && this.attrs.loading) {
            return <LoadingIndicator />;
        }

        return (
            <div className="BiscuitList">
                {(biscuits || []).map(biscuit => {
                    const isActive = !biscuit.isDeleted() && !biscuit.isFrozen();
                    const isDeleted = biscuit.isDeleted();
                    const isSelected = selectedIds && selectedIds.indexOf(biscuit.id()) !== -1;

                    return (
                        <div className={'BiscuitListItem' + (isSelected ? ' selected' : '')} key={biscuit.id()}>
                            <div className="BiscuitListItem-main">
                                {onSelectForFreeze && isActive ? (
                                    <label className="checkbox">
                                        <input
                                            type="checkbox"
                                            checked={isSelected}
                                            onchange={() => onSelectForFreeze(biscuit.id(), !isSelected)}
                                        />
                                    </label>
                                ) : null}
                                <span className={'BiscuitListItem-string' +
                                    (isDeleted ? ' BiscuitListItem--deleted' : '') +
                                    (biscuit.isFrozen() ? ' BiscuitListItem--frozen' : '')}>
                                    {biscuit.biscuitString()}
                                </span>
                                {biscuit.isDefault() ? (
                                    <span className="BiscuitListItem-badge">
                                        {app.translator.trans('teacherli07-anonymous.forum.biscuit_label.default')}
                                    </span>
                                ) : null}
                                {biscuit.isFrozen() ? (
                                    <span className="BiscuitListItem-badge BiscuitListItem-badge--frozen">
                                        {app.translator.trans('teacherli07-anonymous.forum.biscuit_label.frozen')}
                                    </span>
                                ) : null}
                                {isDeleted ? (
                                    <span className="BiscuitListItem-badge BiscuitListItem-badge--deleted">
                                        {app.translator.trans('teacherli07-anonymous.forum.biscuit_label.deleted')}
                                    </span>
                                ) : null}
                            </div>
                            {!onSelectForFreeze && this.attrs.renderActions ? (
                                <div className="BiscuitListItem-actions">
                                    {this.attrs.renderActions(biscuit)}
                                </div>
                            ) : null}
                        </div>
                    );
                })}
            </div>
        );
    }
}
