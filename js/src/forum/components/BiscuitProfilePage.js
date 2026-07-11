import Page from 'flarum/common/components/Page';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import DiscussionListItem from 'flarum/forum/components/DiscussionListItem';
import listItems from 'flarum/common/helpers/listItems';

export default class BiscuitProfilePage extends Page {
    oninit(vnode) {
        super.oninit(vnode);
        this.biscuitString = m.route.param('biscuitString');
        this.discussions = [];
        this.loading = true;
        this.postCount = 0;
        this.birthDate = null;
        this.load();
    }

    load() {
        const bs = this.biscuitString;
        const dPromise = app.request({
            url: app.forum.attribute('apiUrl') + '/biscuits/0/discussions?filter[biscuit]=' + encodeURIComponent(bs),
            method: 'GET',
        });
        const bPromise = app.store.find('biscuits').then(biscuits => {
            const match = biscuits.find(b => b.biscuitString() === bs);
            if (match) {
                this.birthDate = match.createdAt();
                this.postCount = 0;
            }
        }).catch(() => {});

        Promise.all([dPromise, bPromise]).then(([response]) => {
            const data = app.store.pushPayload(response);
            this.discussions = Array.isArray(data) ? data : [];
            this.postCount = this.discussions.reduce((sum, d) => sum + (d.commentCount ? d.commentCount() : 0), 0);
            this.loading = false;
            m.redraw();
        }).catch(() => {
            this.loading = false;
            m.redraw();
        });
    }

    view() {
        const bs = this.biscuitString;
        const firstChar = bs.charAt(0).toUpperCase();
        const hash = bs.split('').reduce((h, c) => c.charCodeAt(0) + ((h << 5) - h), 0);
        const hue = Math.abs(hash) % 360;

        return (
            <div className="BiscuitProfilePage">
                <div className="Hero BiscuitHero" style={'--hero-bg: hsl(' + hue + ', 45%, 35%)'}>
                    <div className="darkenBackground">
                        <div className="container">
                            <div className="BiscuitHero-identity">
                                <span className="BiscuitHero-avatar" style={'background-color: hsl(' + hue + ', 45%, 55%); color: #fff'}>
                                    {firstChar}
                                </span>
                                <h2 className="BiscuitHero-name">{bs}</h2>
                            </div>
                            {!this.loading ? (
                                <ul className="BiscuitHero-stats">
                                    {this.birthDate ? (
                                        <li className="BiscuitHero-stat">
                                            <span className="BiscuitHero-stat-label">{app.translator.trans('teacherli07-anonymous.forum.profile.birthday')}</span>
                                            <span className="BiscuitHero-stat-value">{dayjs(this.birthDate).format('YYYY-MM-DD')}</span>
                                        </li>
                                    ) : null}
                                    <li className="BiscuitHero-stat">
                                        <span className="BiscuitHero-stat-label">{app.translator.trans('teacherli07-anonymous.forum.profile.posts_count')}</span>
                                        <span className="BiscuitHero-stat-value">{this.postCount}</span>
                                    </li>
                                </ul>
                            ) : null}
                        </div>
                    </div>
                </div>
                <div className="container">
                    <div className="sideNavContainer">
                        <nav className="sideNav">
                            <ul>{listItems([
                                <h3 className="BiscuitProfilePage-navTitle">{app.translator.trans('teacherli07-anonymous.forum.profile.discussions')}</h3>
                            ])}</ul>
                        </nav>
                        <div className="sideNavOffset">
                            {this.loading ? <LoadingIndicator /> : this.discussions.length === 0 ? (
                                <div className="DiscussionList">{app.translator.trans('core.forum.post_stream.empty_text')}</div>
                            ) : (
                                <div className="DiscussionList">
                                    {this.discussions.map(discussion => (
                                        <DiscussionListItem discussion={discussion} params={{}} />
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        );
    }
}
