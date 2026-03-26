import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        message: String,
    };

    submit(event) {
        const message = this.hasMessageValue ? this.messageValue : 'Confirmer ?';

        if (!window.confirm(message)) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }
    }
}
