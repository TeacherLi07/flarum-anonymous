import Modal from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';
import BiscuitList from './BiscuitList';

export default class FreezeBiscuitModal extends Modal {
    oninit(vnode) {
        super.oninit(vnode);
        this.selectedIds = {};
        this.biscuits = [];
        this.requiredCount = 0;
        this.loadBiscuits();
    }

    className() {
        return 'FreezeBiscuitModal Modal--large';
    }

    title() {
        return app.translator.trans('huihu-anonymous.forum.freeze_modal.title');
    }

    loadBiscuits() {
        app.store.find('biscuits').then(biscuits => {
            this.biscuits = biscuits.filter(b => !b.deletedAt() && !b.isFrozen());
            const slots = app.forum.attribute('slotMax') || 5;
            this.requiredCount = Math.max(0, this.biscuits.length - slots);
            m.redraw();
        });
    }

    content() {
        const selectedCount = Object.values(this.selectedIds).filter(Boolean).length;
        const canSubmit = selectedCount >= this.requiredCount;

        return (
            <div className="Modal-body">
                <p>{app.translator.trans('huihu-anonymous.forum.freeze_modal.description', {
                    old: this.biscuits.length,
                    new: this.biscuits.length - this.requiredCount,
                    count: this.requiredCount,
                })}</p>

                <BiscuitList
                    biscuits={this.biscuits}
                    onSelectForFreeze={(id, selected) => {
                        if (selected) {
                            this.selectedIds[id] = true;
                        } else {
                            delete this.selectedIds[id];
                        }
                        m.redraw();
                    }}
                    selectedIds={Object.keys(this.selectedIds).filter(k => this.selectedIds[k]).map(Number)}
                />

                <div className="FreezeBiscuitModal-footer">
                    {selectedCount < this.requiredCount ? (
                        <span className="FreezeBiscuitModal-hint">
                            {app.translator.trans('huihu-anonymous.forum.freeze_modal.still_need', {
                                count: this.requiredCount - selectedCount,
                            })}
                        </span>
                    ) : (
                        <span className="FreezeBiscuitModal-hint FreezeBiscuitModal-hint--ready">
                            {app.translator.trans('huihu-anonymous.forum.freeze_modal.ready')}
                        </span>
                    )}
                    <Button className="Button Button--primary" disabled={!canSubmit} onclick={this.submit.bind(this)}>
                        {app.translator.trans('huihu-anonymous.forum.freeze_modal.submit')}
                    </Button>
                </div>
            </div>
        );
    }

    submit() {
        const ids = Object.keys(this.selectedIds).filter(k => this.selectedIds[k]).map(Number);
        app.request({
            url: app.forum.attribute('apiUrl') + '/biscuits/freeze',
            method: 'PATCH',
            body: { data: { ids, freezeCount: this.requiredCount } },
        }).then(() => {
            this.hide();
            window.location.reload();
        }).catch(e => {
            app.alerts.show(e.message || 'Failed to freeze biscuits');
        });
    }
}
