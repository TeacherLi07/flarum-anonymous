import Page from 'flarum/common/components/Page';
import DiscussionList from 'flarum/forum/components/DiscussionList';
import DiscussionListState from 'flarum/forum/states/DiscussionListState';
import PaginatedListState from 'flarum/common/states/PaginatedListState';

export default class BiscuitProfilePage extends Page {
    oninit(vnode) {
        super.oninit(vnode);
        this.biscuitString = m.route.param('biscuitString');

        this.discussionListState = new DiscussionListState({
            filter: { biscuit: this.biscuitString },
            sort: '-lastPostedAt',
        });

        // Bypass preloaded data by calling parent class loadPage directly
        this.discussionListState.loadPage = PaginatedListState.prototype.loadPage;

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
