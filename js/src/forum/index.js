import Component from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import SessionDropdown from 'flarum/forum/components/SessionDropdown';
import LogInModal from 'flarum/forum/components/LogInModal';
import SignUpModal from 'flarum/forum/components/SignUpModal';
import Stream from 'flarum/common/utils/Stream';
import Model from 'flarum/common/Model';
import mixin from 'flarum/common/utils/mixin';

export class AccountBiscuit extends mixin(Model, {
  biscuitString: Model.attribute('biscuitString'),
  note: Model.attribute('note'),
  isActive: Model.attribute('isActive'),
  isFrozen: Model.attribute('isFrozen'),
  canEdit: Model.attribute('canEdit'),
  canDelete: Model.attribute('canDelete'),
  createdAt: Model.attribute('createdAt', Model.transformDate),
}) {}

class BiscuitManagerPage extends Component {
  oninit(vnode) {
    super.oninit(vnode);
    this.biscuits = [];
    this.loading = true;
    this.claiming = false;
    this.reload();
  }

  reload() {
    this.loading = true;
    app.request({
      method: 'GET',
      url: app.forum.attribute('apiUrl') + '/account/biscuits',
    }).then(response => {
      this.biscuits = app.store.pushPayload(response).filter(b => b instanceof AccountBiscuit);
      this.loading = false;
      m.redraw();
    }).catch(err => {
      this.loading = false;
      m.redraw();
    });
  }

  view() {
    const used = this.biscuits.filter(b => !b.isFrozen()).length;
    const total = parseInt(app.forum.attribute('anonymousSlotMax')) || 5;

    return m('div', { className: 'BiscuitManager' }, [
      m('div', { className: 'BiscuitManager-header' }, [
        m('h2', app.translator.trans('teacherli07-anonymous.forum.manager.title')),
        m('p', app.translator.trans('teacherli07-anonymous.forum.manager.slots_used', { used, total })),
      ]),
      m('div', { className: 'BiscuitManager-actions' },
        Button.component({
          className: 'Button Button--primary',
          onclick: () => this.claimBiscuit(),
          loading: this.claiming,
          disabled: used >= total,
        }, app.translator.trans('teacherli07-anonymous.forum.manager.claim_new'))
      ),
      this.loading
        ? m('p', { style: 'text-align:center;padding:20px;' }, 'Loading...')
        : m('div', { className: 'BiscuitList' },
            this.biscuits.length === 0
              ? m('p', { style: 'text-align:center;padding:20px;' },
                  app.translator.trans('teacherli07-anonymous.forum.manager.no_biscuits'))
              : this.biscuits.map(b => this.biscuitItem(b))
          ),
    ]);
  }

  biscuitItem(biscuit) {
    return m('div', {
      className: 'BiscuitListItem' +
        (biscuit.isActive() ? ' is-active' : '') +
        (biscuit.isFrozen() ? ' is-frozen' : ''),
      key: biscuit.id(),
    }, [
      m('span', { className: 'BiscuitListItem-name' }, [
        biscuit.biscuitString(),
        biscuit.isActive() && m('span', { className: 'BiscuitListItem-badge BiscuitListItem-badge--active' },
          'Default'),
        biscuit.isFrozen() && m('span', { className: 'BiscuitListItem-badge BiscuitListItem-badge--frozen' },
          'Frozen'),
      ]),
      m('span', { className: 'BiscuitListItem-actions' }, [
        !biscuit.isActive() && !biscuit.isFrozen() && biscuit.canEdit() && Button.component({
          className: 'Button Button--text',
          icon: 'fas fa-check',
          onclick: () => this.setActive(biscuit),
        }),
        !biscuit.isFrozen() && biscuit.canEdit() && Button.component({
          className: 'Button Button--text',
          icon: 'fas fa-snowflake',
          onclick: () => this.toggleFreeze(biscuit, true),
        }),
        biscuit.isFrozen() && biscuit.canEdit() && Button.component({
          className: 'Button Button--text',
          icon: 'fas fa-sun',
          onclick: () => this.toggleFreeze(biscuit, false),
        }),
        biscuit.canDelete() && Button.component({
          className: 'Button Button--text',
          icon: 'fas fa-trash',
          style: 'color:var(--alert-color)',
          onclick: () => this.deleteBiscuit(biscuit),
        }),
      ]),
    ]);
  }

  claimBiscuit() {
    this.claiming = true;
    app.request({ method: 'POST', url: app.forum.attribute('apiUrl') + '/account/biscuits' }).then(() => {
      this.claiming = false;
      this.reload();
    }).catch(() => { this.claiming = false; m.redraw(); });
  }

  setActive(biscuit) {
    biscuit.save({ isActive: true }).then(() => { window.location.reload(); });
  }

  toggleFreeze(biscuit, freeze) {
    biscuit.save({ isFrozen: freeze }).then(() => this.reload());
  }

  deleteBiscuit(biscuit) {
    if (!confirm(app.translator.trans('teacherli07-anonymous.forum.manager.confirm_delete'))) return;
    biscuit.delete().then(() => this.reload());
  }
}

function sendSms(phone, btnDom) {
  if (!phone || !/^\d{6,20}$/.test(phone)) {
    app.alerts.show({ type: 'error' }, 'Invalid phone number');
    return;
  }
  app.request({ method: 'POST', url: app.forum.attribute('apiUrl') + '/sms/send', body: { phone } }).then(() => {
    let s = 60;
    const orig = btnDom.textContent;
    const id = setInterval(() => {
      if (s <= 0) { clearInterval(id); btnDom.textContent = orig; btnDom.disabled = false; return; }
      btnDom.textContent = s + 's';
      btnDom.disabled = true;
      s--;
    }, 1000);
  });
}

app.initializers.add('teacherli07-anonymous', function () {
  app.store.models['account-biscuits'] = AccountBiscuit;

  // 1. Login form: phone/email label
  extend(LogInModal.prototype, 'fields', function (items) {
    const ident = items.get('identification');
    if (ident && ident.children && ident.children.length) {
      const input = ident.children[0];
      if (input && input.attrs) {
        const label = app.translator.trans('teacherli07-anonymous.forum.login.phone_or_email');
        input.attrs.placeholder = label;
        input.attrs['aria-label'] = label;
      }
    }
  });

  // 2. Signup form: phone + verification code
  extend(SignUpModal.prototype, 'oninit', function () {
    if (!this.phone) this.phone = Stream('');
    if (!this.verificationCode) this.verificationCode = Stream('');
  });

  extend(SignUpModal.prototype, 'fields', function (items) {
    items.remove('username');
    items.remove('email');

    items.add('phone', m('div', { className: 'Form-group' },
      m('input', {
        className: 'FormControl', name: 'phone', type: 'tel',
        placeholder: app.translator.trans('teacherli07-anonymous.forum.phone.label'),
        bidi: this.phone,
        disabled: this.loading,
      })
    ), 25);

    const sendBtn = m('button', {
      className: 'Button Button--primary', type: 'button',
      onclick(e) { sendSms(this.phone(), e.target); },
    }, app.translator.trans('teacherli07-anonymous.forum.phone.send_code'));

    items.add('verificationCode', m('div', { className: 'Form-group' },
      m('div', { style: 'display:flex;gap:8px;' }, [
        m('input', {
          className: 'FormControl', name: 'verificationCode', type: 'text',
          placeholder: app.translator.trans('teacherli07-anonymous.forum.phone.verify_code'),
          bidi: this.verificationCode,
          disabled: this.loading,
          style: 'flex:1;',
        }),
        sendBtn,
      ])
    ), 15);
  });

  extend(SignUpModal.prototype, 'onsubmit', function (original, e) {
    e.preventDefault();
    if (this.loading) return;
    const phone = this.phone ? this.phone() : '';
    const code = this.verificationCode ? this.verificationCode() : '';
    if (!phone || !code) return original(e);

    this.loading = true;
    app.request({
      method: 'POST',
      url: app.forum.attribute('apiUrl') + '/anonymous/register',
      body: { phone, verificationCode: code, password: this.password() },
    }).then(() => window.location.reload())
      .catch(err => { this.loading = false; this.onerror(err); m.redraw(); });
  });

  // 3. /biscuits route
  app.routes.biscuits = { path: '/biscuits', component: BiscuitManagerPage };

  // 4. Session dropdown link
  if (SessionDropdown) {
    extend(SessionDropdown.prototype, 'items', function (items) {
      if (!app.forum.attribute('canManageBiscuits')) return;
      items.add('biscuits',
        Button.component({
          icon: 'fas fa-user-secret',
          onclick() { app.modal.show(BiscuitManagerPage); },
        }, app.translator.trans('teacherli07-anonymous.forum.manager.title')),
        15);
    });
  }
});
