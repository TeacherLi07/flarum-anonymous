import Page from 'flarum/common/components/Page';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import DiscussionList from 'flarum/forum/components/DiscussionList';

export default class BiscuitProfilePage extends Page {
    oninit(vnode) {
        super.oninit(vnode);
        this.biscuitString = m.route.param('biscuitString');
        this.loading = true;
    }

    view() {
        return (
            <div className="BiscuitProfilePage">
                <div className="container">
                    <div className="BiscuitProfilePage-header">
                        <h2>{this.biscuitString}</h2>
                    </div>
                    <DiscussionList
                        params={{
                            filter: { biscuit: this.biscuitString },
                            sort: '-lastPostedAt',
                        }}
                    />
                </div>
            </div>
        );
    }
}
