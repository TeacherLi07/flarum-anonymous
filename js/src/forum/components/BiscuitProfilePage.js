import Page from 'flarum/common/components/Page';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import DiscussionListItem from 'flarum/forum/components/DiscussionListItem';

export default class BiscuitProfilePage extends Page {
    oninit(vnode) {
        super.oninit(vnode);
        this.biscuitString = m.route.param('biscuitString');
        this.discussions = [];
        this.loading = true;
        this.load();
    }

    load() {
        app.store.find('discussions', {
            filter: { biscuit: this.biscuitString },
            sort: '-lastPostedAt',
            include: 'user,lastPostedUser,firstPost,tags',
        }).then(payload => {
            this.discussions = payload;
            this.loading = false;
            m.redraw();
        });
    }

    view() {
        return (
            <div className="BiscuitProfilePage">
                <div className="container">
                    <div className="BiscuitProfilePage-header">
                        <h2>{this.biscuitString}</h2>
                    </div>
                    {this.loading ? <LoadingIndicator /> : this.discussions.length === 0 ? (
                        <p>{app.translator.trans('core.forum.post_stream.empty_text') || 'Nothing here...'}</p>
                    ) : (
                        <div className="DiscussionList">
                            {this.discussions.map(discussion => (
                                <DiscussionListItem discussion={discussion} params={{}} />
                            ))}
                        </div>
                    )}
                </div>
            </div>
        );
    }
}
