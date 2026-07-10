import Component from 'flarum/common/Component';
import FieldSet from 'flarum/common/components/FieldSet';
import Button from 'flarum/common/components/Button';

export default class PhoneRegisterForm extends Component {
    static fields() {
        return [
            <div className="Form-group">
                <input className="FormControl" name="phone" type="tel"
                    placeholder={app.translator.trans('huihu-anonymous.forum.phone.label')}
                    bidi={this.phone} />
            </div>,
            <div className="Form-group">
                <div className="PhoneVerifyRow">
                    <input className="FormControl" name="verificationCode" type="text"
                        placeholder={app.translator.trans('huihu-anonymous.forum.phone.verify_code')}
                        bidi={this.code} />
                    <Button className="Button Button--primary PhoneVerifyRow-send"
                        loading={this.sending} disabled={this.countdown > 0}
                        onclick={this.sendCode.bind(this)}>
                        {this.countdown > 0
                            ? app.translator.trans('huihu-anonymous.forum.phone.resend', { seconds: this.countdown })
                            : app.translator.trans('huihu-anonymous.forum.phone.send_code')}
                    </Button>
                </div>
            </div>,
        ];
    }

    oninit(vnode) {
        super.oninit(vnode);
        this.phone = '';
        this.code = '';
        this.countdown = 0;
        this.sending = false;
    }

    sendCode() {
        if (!this.phone) return;
        this.sending = true;
        app.request({
            url: app.forum.attribute('apiUrl') + '/sms/send',
            method: 'POST',
            body: { phone: this.phone },
        }).then(() => {
            this.sending = false;
            this.countdown = 60;
            const timer = setInterval(() => {
                this.countdown--;
                if (this.countdown <= 0) {
                    clearInterval(timer);
                }
                m.redraw();
            }, 1000);
        }).catch(e => {
            this.sending = false;
            app.alerts.show(e.message || 'Failed to send code');
        });
    }

    getPhone() { return this.phone; }
    getCode() { return this.code; }
}
