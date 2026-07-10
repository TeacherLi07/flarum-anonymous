import app from 'flarum/admin/app';

app.initializers.add('huihu-anonymous', function (app) {
    app.extensionData
        .for('huihu-anonymous')
        .registerSetting({
            setting: 'anonymous.sms_access_key_id',
            label: app.translator.trans('huihu-anonymous.admin.sms_access_key_id'),
            type: 'text',
        })
        .registerSetting({
            setting: 'anonymous.sms_access_secret',
            label: app.translator.trans('huihu-anonymous.admin.sms_access_secret'),
            type: 'password',
        })
        .registerSetting({
            setting: 'anonymous.sms_sign_name',
            label: app.translator.trans('huihu-anonymous.admin.sms_sign_name'),
            type: 'text',
        })
        .registerSetting({
            setting: 'anonymous.sms_template_code',
            label: app.translator.trans('huihu-anonymous.admin.sms_template_code'),
            type: 'text',
        })
        .registerSetting({
            setting: 'anonymous.slot_days_required',
            label: app.translator.trans('huihu-anonymous.admin.slot_days_required'),
            type: 'number',
            min: 1,
        })
        .registerSetting({
            setting: 'anonymous.slot_posts_required',
            label: app.translator.trans('huihu-anonymous.admin.slot_posts_required'),
            type: 'number',
            min: 1,
        })
        .registerSetting({
            setting: 'anonymous.slot_max',
            label: app.translator.trans('huihu-anonymous.admin.slot_max'),
            type: 'number',
            min: 1,
        });
});
