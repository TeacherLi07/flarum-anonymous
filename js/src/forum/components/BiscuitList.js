import Component from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';

export default class BiscuitList extends Component {
    oninit(vnode) {
        super.oninit(vnode);
        this.biscuits = [];
        this.loading = true;
        this.slots = app.session.user ? app.session.user.biscuitSlots() : 1;
        this.load();
    }

    load() {
        this.loading = true;
        app.store.find('biscuits').then(biscuits => {
            this.biscuits = biscuits;
            this.loading = false;
            m.redraw();
        });
    }

    view() {
        const { onSelectForFreeze, selectedIds } = this.attrs;

        if (this.loading) {
            return <LoadingIndicator />;
        }

        return (
            <div className="BiscuitList">
                {this.biscuits.map(biscuit => {
                    const isActive = !biscuit.isDeleted() && !biscuit.isFrozen();
                    const isDeleted = !!biscuit.isDeleted();
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
                            {!onSelectForFreeze ? (
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
