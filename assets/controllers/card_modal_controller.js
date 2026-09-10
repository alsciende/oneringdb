import { Controller } from '@hotwired/stimulus';
import { Modal } from 'bootstrap';

export default class extends Controller {
    static targets = ['modal', 'title', 'content', 'cardPageLink'];

    open(event) {
        event.preventDefault();

        this.titleTarget.textContent = event.currentTarget.textContent;
        this.cardPageLinkTarget.href = event.currentTarget.href;
        this.contentTarget.innerHTML = '<div class="text-center py-4"><span class="spinner-border"></span></div>';

        Modal.getOrCreateInstance(this.modalTarget).show();

        fetch(event.params.modalUrl)
            .then(response => response.text())
            .then(html => { this.contentTarget.innerHTML = html; });
    }

    closeBeforeNavigating(event) {
        event.preventDefault();

        const url = event.currentTarget.href;
        this.modalTarget.addEventListener('hidden.bs.modal', () => {
            window.location.href = url;
        }, { once: true });

        Modal.getInstance(this.modalTarget)?.hide();
    }
}
