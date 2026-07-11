import Component from 'flarum/common/Component';
import SelectDropdown from 'flarum/common/components/SelectDropdown';
import Button from 'flarum/common/components/Button';

export default class BiscuitSelector extends Component {
    oninit(vnode) {
        super.oninit(vnode);
        this.biscuits = [];
        this.selected = null;
        this.loadBiscuits();
    }

    loadBiscuits() {
        app.store.find('biscuits').then(biscuits => {
            this.biscuits = biscuits.filter(b => !b.isFrozen() && !b.isDeleted());
            if (this.biscuits.length > 0) {
                const defaultBiscuit = this.biscuits.find(b => b.isDefault());
                this.selected = defaultBiscuit || this.biscuits[0];
            }
            m.redraw();
        });
    }

    view() {
        const selectedString = this.selected ? this.selected.biscuitString() : '---';

        return (
            <div className="BiscuitSelector Form-group">
                <SelectDropdown
                    buttonClassName="Button Button--text"
                    className="BiscuitSelector-dropdown"
                >
                    <Button
                        className="BiscuitSelector-button"
                        icon="fas fa-user-secret"
                        onclick={this.drawer.bind(this)}
                    >
                        {selectedString}
                    </Button>
                </SelectDropdown>
            </div>
        );
    }

    drawer() {
        const items = this.biscuits.map(biscuit => {
            return Button.component({
                className: 'BiscuitSelector-item',
                icon: biscuit.isDefault() ? 'fas fa-star' : 'fas fa-circle',
                onclick: () => {
                    this.selected = biscuit;
                },
            }, biscuit.biscuitString());
        });

        return items;
    }

    getBiscuitString() {
        return this.selected ? this.selected.biscuitString() : null;
    }
}
