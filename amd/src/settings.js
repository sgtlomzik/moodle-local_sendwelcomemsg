// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Turns each message template heading on the settings page into an accordion toggle.
 *
 * @module     local_sendwelcomemsg/settings
 * @copyright  2026 SgtLomzik <lomzike@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** @type {string} Selector for the clickable template headings. */
const TOGGLE_SELECTOR = '.local-sendwelcomemsg-toggle';

/** @type {string} Selector for the status label inside a heading. */
const STATUS_SELECTOR = '.local-sendwelcomemsg-status';

/** @type {string} Selector for the hidden checkboxes that drive hide_if. */
const EXPANDED_SELECTOR = 'input[id^="id_s_local_sendwelcomemsg_expanded"]';

/**
 * Show whether the template a heading belongs to is switched on.
 *
 * @param {Element} toggle The heading element.
 * @param {string} activeLabel Label shown when the template is enabled.
 * @param {string} inactiveLabel Label shown when the template is disabled.
 */
const updateStatus = (toggle, activeLabel, inactiveLabel) => {
    const enableCheckbox = document.getElementById(toggle.getAttribute('data-enable'));
    const status = toggle.querySelector(STATUS_SELECTOR);

    if (!enableCheckbox || !status) {
        return;
    }

    status.textContent = ' (' + (enableCheckbox.checked ? activeLabel : inactiveLabel) + ')';
};

/**
 * Initialise the accordion behaviour.
 *
 * @param {string} activeLabel Label shown when a template is enabled.
 * @param {string} inactiveLabel Label shown when a template is disabled.
 */
export const init = (activeLabel, inactiveLabel) => {
    document.querySelectorAll(TOGGLE_SELECTOR).forEach((toggle) => {
        updateStatus(toggle, activeLabel, inactiveLabel);

        const onToggle = () => {
            const checkbox = document.getElementById(toggle.getAttribute('data-target'));

            if (!checkbox) {
                return;
            }

            checkbox.checked = !checkbox.checked;
            checkbox.dispatchEvent(new Event('change', {bubbles: true}));
            toggle.setAttribute('aria-expanded', checkbox.checked ? 'true' : 'false');
        };

        toggle.addEventListener('click', onToggle);
        toggle.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                onToggle();
            }
        });

        const enableCheckbox = document.getElementById(toggle.getAttribute('data-enable'));
        if (enableCheckbox) {
            enableCheckbox.addEventListener('change', () => updateStatus(toggle, activeLabel, inactiveLabel));
        }
    });

    // The expanded flags only exist to drive hide_if; they are not something an
    // administrator should have to tick themselves.
    document.querySelectorAll(EXPANDED_SELECTOR).forEach((checkbox) => {
        const formItem = checkbox.closest('.form-item');

        if (formItem) {
            formItem.classList.add('local-sendwelcomemsg-hidden');
        }
    });
};
