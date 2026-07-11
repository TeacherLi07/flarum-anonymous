import Page from 'flarum/common/components/Page';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import DiscussionListItem from 'flarum/forum/components/DiscussionListItem';
import listItems from 'flarum/common/helpers/listItems';
import BiscuitIdenticon from './BiscuitIdenticon';

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
        const bPromise = app.request({
            url: app.forum.attribute('apiUrl') + '/biscuits',
            method: 'GET',
            background: true,
            errorHandler: () => {},
        }).then(response => {
            const biscuits = app.store.pushPayload(response);
            const match = Array.isArray(biscuits) ? biscuits.find(b => b.biscuitString() === bs) : null;
            if (match) {
                this.birthDate = match.createdAt();
            }
        }).catch(() => {});

        Promise.all([dPromise, bPromise]).then(([response]) => {
            const data = app.store.pushPayload(response);
            this.discussions = Array.isArray(data) ? data : [];
            // Count: each matching discussion counts as 1, plus count the biscuit's replies
            // We count each discussion the biscuit participated in = 1 per discussion
            this.postCount = this.discussions.length;
            // Also add extra replies within discussions (commentCount - 1 if biscuit started it, else just reply count)
            // Simple approach: count discussions where biscuit appears
            this.loading = false;
            m.redraw();
        }).catch(() => {
            this.loading = false;
            m.redraw();
        });
    }

    view() {
        const bs = this.biscuitString;
        const hash = bs.split('').reduce((h, c) => c.charCodeAt(0) + ((h << 5) - h), 0);
        const hue = Math.abs(hash) % 360;

        return (
            <div className="BiscuitProfilePage">
                <div className="Hero BiscuitHero" style={'--hero-bg: hsl(' + hue + ', 45%, 35%)'}>
                    <div className="darkenBackground">
                        <div className="container">
                            <div className="BiscuitHero-identity">
                                <BiscuitIdenticon biscuitString={bs} size={64} />
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
                                <div className="DiscussionList">{app.translator.trans('core.forum.discussion_list.empty_text')}</div>
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
