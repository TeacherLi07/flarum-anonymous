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

app.initializers.add('teacherli07-anonymous', function (app) {
    app.store.models.biscuits = Biscuit;

    Post.prototype.biscuitString = Model.attribute('biscuitString');
    Post.prototype.biscuitIsDeleted = Model.attribute('biscuitIsDeleted');
    Post.prototype.biscuitIsFrozen = Model.attribute('biscuitIsFrozen');

    Discussion.prototype.biscuitString = Model.attribute('biscuitString');

    User.prototype.displayName = Model.attribute('displayName');
    User.prototype.biscuitSlots = Model.attribute('biscuitSlots');

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

    // Replace PostUser linkChildren to show biscuit avatar + biscuit name
    override(PostUser.prototype, 'linkChildren', function (original, user) {
        const items = original(user);
        const post = this.attrs.post;
        const biscuitString = post && post.biscuitString ? post.biscuitString() : null;

        if (biscuitString) {
            const firstChar = biscuitString.charAt(0).toUpperCase();
            const hash = biscuitString.split('').reduce((h, c) => c.charCodeAt(0) + ((h << 5) - h), 0);
            const hue = Math.abs(hash) % 360;

            items.remove('avatar');
            items.add('avatar', (
                <span className="BiscuitPostAvatar" style={'background-color: hsl(' + hue + ', 45%, 55%); color: #fff;'}>
                    {firstChar}
                </span>
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

        // Find user links and replace with biscuit profile links
        // The DiscussionListItem renders author as a link with href=/u/:username
        // We need to change this to /b/:biscuitString
        const discussion = this.attrs.discussion;
        const biscuitString = discussion && discussion.biscuitString ? discussion.biscuitString() : null;

        if (biscuitString) {
            // Walk the vdom tree and replace /u/* links with /b/* links
            this.replaceUserLinks(vdom, biscuitString);
        }

        return vdom;
    });

    // Helper: recursively replace /u/* links in vdom tree
    DiscussionListItem.prototype.replaceUserLinks = function (vnode, biscuitString) {
        if (!vnode || typeof vnode === 'string' || typeof vnode === 'number') return;

        if (Array.isArray(vnode)) {
            vnode.forEach(child => this.replaceUserLinks(child, biscuitString));
            return;
        }

        if (vnode.children) {
            if (Array.isArray(vnode.children)) {
                vnode.children.forEach(child => this.replaceUserLinks(child, biscuitString));
            } else {
                this.replaceUserLinks(vnode.children, biscuitString);
            }
        }

        if (vnode.attrs && vnode.attrs.href && typeof vnode.attrs.href === 'string' && vnode.attrs.href.startsWith('/u/')) {
            vnode.attrs.href = app.route('biscuitProfile', { biscuitString });
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
        const items = original();
        // Replace identification label to mention phone support
        return items;
    });

    // Freeze modal check on boot
    if (app.forum && app.forum.attribute('needBiscuitFreeze')) {
        setTimeout(function () {
            app.modal.show(FreezeBiscuitModal);
        }, 500);
    }
});
