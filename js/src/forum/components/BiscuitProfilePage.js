import Page from 'flarum/common/components/Page';
import DiscussionList from 'flarum/forum/components/DiscussionList';
import DiscussionListState from 'flarum/forum/states/DiscussionListState';

export default class BiscuitProfilePage extends Page {
    oninit(vnode) {
        super.oninit(vnode);
        this.biscuitString = m.route.param('biscuitString');

        this.discussionListState = new DiscussionListState({
            filter: { biscuit: this.biscuitString },
            sort: '-lastPostedAt',
        });

        // Override loadPage to bypass preloaded data
        const originalLoadPage = this.discussionListState.loadPage.bind(this.discussionListState);
        this.discussionListState.loadPage = (page) => {
            return app.store.find('discussions', {
                filter: { biscuit: this.biscuitString },
                sort: '-lastPostedAt',
                page: { offset: 0, limit: 20 },
            }).then(payload => {
                this.discussionListState.pushPayload(payload);
                return payload;
            });
        };

        this.discussionListState.refresh();
    }

    view() {
        return (
            <div className="BiscuitProfilePage">
                <div className="container">
                    <div className="BiscuitProfilePage-header">
                        <h2>{this.biscuitString}</h2>
                    </div>
                    <DiscussionList state={this.discussionListState} />
                </div>
            </div>
        );
    }
}
