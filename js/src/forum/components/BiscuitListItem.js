import Button from 'flarum/common/components/Button';

export default function BiscuitListItemActions(biscuit, callbacks) {
    const { onSetDefault, onEditNote, onDelete, onToggleFreeze } = callbacks;

    return [
        !biscuit.isDefault() && !biscuit.deletedAt() && !biscuit.isFrozen() ? (
            <Button className="Button Button--text" icon="fas fa-star" onclick={() => onSetDefault(biscuit)}>
                {app.translator.trans('huihu-anonymous.forum.manager.set_default')}
            </Button>
        ) : null,
        !biscuit.deletedAt() ? (
            <Button className="Button Button--text" icon="fas fa-edit" onclick={() => onEditNote(biscuit)}>
                {app.translator.trans('huihu-anonymous.forum.manager.edit_note')}
            </Button>
        ) : null,
        biscuit.isFrozen() && !biscuit.deletedAt() ? (
            <Button className="Button Button--text" icon="fas fa-lock-open" onclick={() => onToggleFreeze(biscuit, false)}>
                {app.translator.trans('huihu-anonymous.forum.manager.unfreeze')}
            </Button>
        ) : null,
        !biscuit.isFrozen() && !biscuit.deletedAt() ? (
            <Button className="Button Button--text" icon="fas fa-lock" onclick={() => onToggleFreeze(biscuit, true)}>
                {app.translator.trans('huihu-anonymous.forum.manager.freeze')}
            </Button>
        ) : null,
        !biscuit.deletedAt() ? (
            <Button className="Button Button--text Button--danger" icon="fas fa-trash" onclick={() => onDelete(biscuit)}>
                {app.translator.trans('huihu-anonymous.forum.manager.delete')}
            </Button>
        ) : null,
    ];
}
