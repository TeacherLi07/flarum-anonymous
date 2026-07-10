import Model from 'flarum/common/Model';
import mixin from 'flarum/common/utils/mixin';

export default class Biscuit extends mixin(Model, {
    biscuitString: Model.attribute('biscuitString'),
    note:          Model.attribute('note'),
    isDefault:     Model.attribute('isDefault'),
    isFrozen:      Model.attribute('isFrozen'),
    canDelete:     Model.attribute('canDelete'),
    canEdit:       Model.attribute('canEdit'),
    createdAt:     Model.attribute('createdAt', Model.transformDate),
    updatedAt:     Model.attribute('updatedAt', Model.transformDate),
}) {}
