import Page from 'flarum/common/components/Page';
import Button from 'flarum/common/components/Button';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import FieldSet from 'flarum/common/components/FieldSet';
import DiscussionList from 'flarum/forum/components/DiscussionList';
import BiscuitList from './BiscuitList';
import BiscuitListItemActions from './BiscuitListItem';

export default class BiscuitManagerPage extends Page {
    oninit(vnode) {
        super.oninit(vnode);
        this.biscuits = [];
        this.loading = true;
        this.slots = app.session.user ? app.session.user.biscuitSlots() : 1;
        this.showClaimButton = false;
        this.refresh();
    }

    refresh() {
        this.loading = true;
        Promise.all([
            app.store.find('biscuits'),
            app.store.find('discussions', { sort: '-lastPostedAt', filter: { author: app.session.user.id() } }),
        ]).then(([biscuits]) => {
            this.biscuits = biscuits;
            const activeCount = biscuits.filter(b => !b.deletedAt() && !b.isFrozen()).length;
            this.showClaimButton = activeCount < this.slots;
            this.loading = false;
            m.redraw();
        });
    }

    view() {
        if (this.loading) {
            return <LoadingIndicator />;
        }

        const activeCount = this.biscuits.filter(b => !b.deletedAt() && !b.isFrozen()).length;

        return (
            <div className="BiscuitManagerPage">
                <div className="container">
                    <h2>{app.translator.trans('huihu-anonymous.forum.manager.title')}</h2>

                    <div className="BiscuitManagerPage-slots">
                        <span>{app.translator.trans('huihu-anonymous.forum.manager.slots_used', {
                            used: activeCount,
                            total: this.slots,
                        })}</span>
                        {this.showClaimButton ? (
                            <Button className="Button Button--primary" icon="fas fa-plus" loading={this.claiming} onclick={this.claimBiscuit.bind(this)}>
                                {app.translator.trans('huihu-anonymous.forum.manager.claim_new')}
                            </Button>
                        ) : null}
                    </div>

                    <FieldSet label={app.translator.trans('huihu-anonymous.forum.manager.your_biscuits')}>
                        <BiscuitList
                            biscuits={this.biscuits}
                            renderActions={biscuit => BiscuitListItemActions(biscuit, {
                                onSetDefault: this.setDefault.bind(this),
                                onEditNote: this.editNote.bind(this),
                                onDelete: this.deleteBiscuit.bind(this),
                                onToggleFreeze: this.toggleFreeze.bind(this),
                            })}
                        />
                    </FieldSet>

                    <FieldSet label={app.translator.trans('huihu-anonymous.forum.manager.all_posts')}>
                        <DiscussionList
                            params={{
                                filter: { author: app.session.user.id() },
                                sort: '-lastPostedAt',
                            }}
                        />
                    </FieldSet>
                </div>
            </div>
        );
    }

    claimBiscuit() {
        this.claiming = true;
        app.store.createRecord('biscuits').save({}).then(() => {
            this.claiming = false;
            this.refresh();
        }).catch(e => {
            this.claiming = false;
            app.alerts.show(e.message || 'Failed to claim biscuit');
        });
    }

    setDefault(biscuit) {
        biscuit.save({ isDefault: true }).then(() => this.refresh());
    }

    editNote(biscuit) {
        const note = prompt(app.translator.trans('huihu-anonymous.forum.manager.note_placeholder'), biscuit.note() || '');
        if (note !== null) {
            biscuit.save({ note }).then(() => this.refresh());
        }
    }

    deleteBiscuit(biscuit) {
        if (confirm(app.translator.trans('huihu-anonymous.forum.manager.confirm_delete'))) {
            biscuit.delete().then(() => this.refresh());
        }
    }

    toggleFreeze(biscuit, freeze) {
        biscuit.save({ isFrozen: freeze }).then(() => this.refresh());
    }
}
