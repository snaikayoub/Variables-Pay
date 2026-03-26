import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['master', 'item'];

    connect() {
        this.refreshMaster();
    }

    toggleAll(event) {
        const checked = !!event.target.checked;

        this.itemTargets.forEach((cb) => {
            cb.checked = checked;
        });

        this.refreshMaster();
    }

    itemChanged() {
        this.refreshMaster();
    }

    refreshMaster() {
        if (!this.hasMasterTarget) {
            return;
        }

        const items = this.itemTargets;

        if (items.length === 0) {
            this.masterTarget.checked = false;
            this.masterTarget.indeterminate = false;
            return;
        }

        const checkedCount = items.filter((cb) => cb.checked).length;
        this.masterTarget.checked = checkedCount === items.length;
        this.masterTarget.indeterminate = checkedCount > 0 && checkedCount < items.length;
    }
}
