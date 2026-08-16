import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    initialize() {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            const storedTheme = this.getStoredTheme()
            if (storedTheme !== 'light' && storedTheme !== 'dark') {
                this.setTheme(this.getPreferredTheme())
            }
        })

        window.addEventListener('DOMContentLoaded', () => {
            this.showActiveTheme(this.getPreferredTheme())

            document.querySelectorAll('[data-bs-theme-value]')
                .forEach(toggle => {
                    toggle.addEventListener('click', () => {

                    })
                })
        })
    }
    connect() {
        const theme = this.getPreferredTheme()
        this.setTheme(theme)
        this.showActiveTheme(theme)
    }

    getStoredTheme() {
        return localStorage.getItem('theme')
    }

    setStoredTheme(theme) {
        localStorage.setItem('theme', theme);
    }

    getPreferredTheme() {
        const storedTheme = this.getStoredTheme()
        if (storedTheme) {
            return storedTheme
        }

        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
    }

    setTheme(theme) {
        if (theme === 'auto') {
            document.documentElement.setAttribute('data-bs-theme', (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'))
        } else {
            document.documentElement.setAttribute('data-bs-theme', theme)
        }
    }

    showActiveTheme(theme, focus = false) {
        const themeSwitcher = document.querySelector('#bd-theme')

        if (!themeSwitcher) {
            return
        }

        const themeSwitcherText = document.querySelector('#bd-theme-text')
        const activeThemeIcon = document.querySelector('.theme-icon-active')
        const btnToActive = document.querySelector(`[data-bs-theme-value="${theme}"]`)
        const classOfActiveBtn = btnToActive.querySelector('i[data-bs-theme-icon]').getAttribute('data-bs-theme-icon')

        document.querySelectorAll('[data-bs-theme-value]').forEach(element => {
            element.classList.remove('active')
            element.setAttribute('aria-pressed', 'false')
        })

        btnToActive.classList.add('active')
        btnToActive.setAttribute('aria-pressed', 'true')
        activeThemeIcon.setAttribute('class', 'theme-icon-active fa fa-' + classOfActiveBtn)
        const themeSwitcherLabel = `${themeSwitcherText.textContent} (${btnToActive.dataset.bsThemeValue})`
        themeSwitcher.setAttribute('aria-label', themeSwitcherLabel)

        if (focus) {
            themeSwitcher.focus()
        }
    }

    changeTheme(event) {
        const theme = event.target.getAttribute('data-bs-theme-value')
        this.setStoredTheme(theme)
        this.setTheme(theme)
        this.showActiveTheme(theme, true)
    }
}