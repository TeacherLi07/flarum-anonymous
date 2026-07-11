import { extend, override } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import PostUser from 'flarum/forum/components/PostUser';
import DiscussionListItem from 'flarum/forum/components/DiscussionListItem';
import UserCard from 'flarum/forum/components/UserCard';
import ComposerBody from 'flarum/forum/components/ComposerBody';
import SignUpModal from 'flarum/forum/components/SignUpModal';
import LogInModal from 'flarum/forum/components/LogInModal';
import IndexPage from 'flarum/forum/components/IndexPage';
import username from 'flarum/common/helpers/username';
import avatar from 'flarum/common/helpers/avatar';
import icon from 'flarum/common/helpers/icon';
import Link from 'flarum/common/components/Link';
import Button from 'flarum/common/components/Button';
import Model from 'flarum/common/Model';
import Post from 'flarum/common/models/Post';
import Discussion from 'flarum/common/models/Discussion';
import User from 'flarum/common/models/User';

import Biscuit from './models/Biscuit';
import BiscuitManagerPage from './components/BiscuitManagerPage';
import BiscuitProfilePage from './components/BiscuitProfilePage';
import BiscuitLabel from './components/BiscuitLabel';
import BiscuitSelector from './components/BiscuitSelector';
import FreezeBiscuitModal from './components/FreezeBiscuitModal';
import PhoneRegisterForm from './components/PhoneRegisterForm';
import BiscuitIdenticon from './components/BiscuitIdenticon';

app.initializers.add('teacherli07-anonymous', function (app) {
    app.store.models.biscuits = Biscuit;

    Post.prototype.biscuitString = Model.attribute('biscuitString');
    Post.prototype.biscuitIsDeleted = Model.attribute('biscuitIsDeleted');
    Post.prototype.biscuitIsFrozen = Model.attribute('biscuitIsFrozen');

    Discussion.prototype.biscuitString = Model.attribute('biscuitString');
    Discussion.prototype.lastPostedBiscuitString = Model.attribute('lastPostedBiscuitString');

    User.prototype.displayName = Model.attribute('displayName');

    app.routes.biscuits = { path: '/biscuits', component: BiscuitManagerPage };
    app.routes.biscuitProfile = { path: '/b/:biscuitString', component: BiscuitProfilePage };

    // Replace username helper - show biscuit string instead
    override(username, function (original, user) {
        if (user && user.displayName && user.displayName()) {
            const biscuitString = user.displayName();
            return (
                <Link href={app.route('biscuitProfile', { biscuitString })} className="BiscuitUsername">
                    {biscuitString}
                </Link>
            );
        }
        return original(user);
    });

    // Replace PostUser linkChildren to show biscuit identicon + biscuit name
    override(PostUser.prototype, 'linkChildren', function (original, user) {
        const items = original(user);
        const post = this.attrs.post;
        const biscuitString = post && post.biscuitString ? post.biscuitString() : null;

        if (biscuitString) {
            items.remove('avatar');
            items.add('avatar', (
                <BiscuitIdenticon biscuitString={biscuitString} size={36} />
            ), 100);

            items.remove('username');
            items.add('username', (
                <span className="BiscuitPostName">{biscuitString}</span>
            ), 80);
        }

        return items;
    });

    // Replace PostUser userViewItems to link to biscuit profile + remove user card
    override(PostUser.prototype, 'userViewItems', function (original, user, post) {
        const items = original(user, post);
        const biscuitString = post && post.biscuitString ? post.biscuitString() : null;

        if (biscuitString) {
            const nameItem = items.get('postUser-name');
            if (nameItem) {
                items.setContent('postUser-name', (
                    <h3 className="PostUser-name">
                        <Link href={app.route('biscuitProfile', { biscuitString })}>
                            {this.linkChildren(user).toArray()}
                        </Link>
                    </h3>
                ), 100);
            }
        }

        items.remove('postUser-card');
        return items;
    });

    // Replace DiscussionListItem author link to point to biscuit profile
    override(DiscussionListItem.prototype, 'view', function (original) {
        const vdom = original();
        const discussion = this.attrs.discussion;
        const opBiscuit = discussion && discussion.biscuitString ? discussion.biscuitString() : null;
        const lastBiscuit = discussion && discussion.lastPostedBiscuitString ? discussion.lastPostedBiscuitString() : null;

        if (opBiscuit || lastBiscuit) {
            this.replaceBiscuitLinks(vdom, opBiscuit, lastBiscuit);
        }

        return vdom;
    });

    // Helper: replace /u/* links with biscuit-specific /b/* links in DiscussionListItem
    DiscussionListItem.prototype.replaceBiscuitLinks = function (vnode, opBiscuit, lastBiscuit) {
        if (!vnode || typeof vnode === 'string' || typeof vnode === 'number') return;

        if (Array.isArray(vnode)) {
            vnode.forEach(child => this.replaceBiscuitLinks(child, opBiscuit, lastBiscuit));
            return;
        }

        if (vnode.children) {
            if (Array.isArray(vnode.children)) {
                vnode.children.forEach(child => this.replaceBiscuitLinks(child, opBiscuit, lastBiscuit));
            } else {
                this.replaceBiscuitLinks(vnode.children, opBiscuit, lastBiscuit);
            }
        }

        if (vnode.attrs && vnode.attrs.href && typeof vnode.attrs.href === 'string') {
            const href = vnode.attrs.href;
            if (href.startsWith('/u/') && lastBiscuit) {
                vnode.attrs.href = app.route('biscuitProfile', { biscuitString: lastBiscuit });
            }
        }
    };

    // Add biscuit manager link to user dropdown
    extend(IndexPage.prototype, 'sidebarItems', function (items) {
        if (app.session.user) {
            items.add('biscuits',
                Link.component({
                    href: app.route('biscuits'),
                    icon: 'fas fa-user-secret',
                }, app.translator.trans('teacherli07-anonymous.forum.manager.title')),
                -10
            );
        }
    });

    // Add biscuit selector to composer
    extend(ComposerBody.prototype, 'headerItems', function (items) {
        if (app.session.user) {
            items.add('biscuitSelector', BiscuitSelector.component(), 5);
        }
    });

    // Replace SignUpModal with phone registration
    extend(SignUpModal.prototype, 'fields', function (items) {
        // Replace the username field with phone fields
        items.remove('username');
        items.add('phone',
            <div className="Form-group">
                <input className="FormControl" name="phone" type="tel"
                    placeholder={app.translator.trans('teacherli07-anonymous.forum.phone.label')} />
            </div>,
            10
        );
        items.add('verificationCode',
            <div className="Form-group">
                <input className="FormControl" name="verificationCode" type="text"
                    placeholder={app.translator.trans('teacherli07-anonymous.forum.phone.verify_code')} />
            </div>,
            15
        );
    });

    // Replace login modal to accept phone
    override(LogInModal.prototype, 'fields', function (original) {
        return [items]; // Keep default fields
    });

    // Replace login identification placeholder to instruct phone/email
    extend(LogInModal.prototype, 'oninit', function () {
        // No-op: just ensure the component is properly extended
    });

    // Freeze modal check on boot
    if (app.forum && app.forum.attribute('needBiscuitFreeze')) {
        setTimeout(function () {
            app.modal.show(FreezeBiscuitModal);
        }, 500);
    }
});
