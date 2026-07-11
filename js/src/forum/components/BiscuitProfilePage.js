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
        const params = {
            filter: { biscuit: this.biscuitString },
            sort: '-lastPostedAt',
        };

        const queryString = Object.entries(params.filter)
            .map(([k, v]) => `filter[${encodeURIComponent(k)}]=${encodeURIComponent(v)}`)
            .join('&');

        app.request({
            url: app.forum.attribute('apiUrl') + '/discussions?' + queryString + '&sort=-lastPostedAt',
            method: 'GET',
        }).then(response => {
            const data = app.store.pushPayload(response);
            this.discussions = Array.isArray(data) ? data : [];
            this.loading = false;
            m.redraw();
        }).catch(() => {
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
