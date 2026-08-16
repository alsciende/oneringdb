import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect(what) {
        new bootstrap.Tooltip(this.element)
    }
}
