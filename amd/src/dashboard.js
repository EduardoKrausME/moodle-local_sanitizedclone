/**
 * Administrative operation table enhancement.
 *
 * @module local_sanitizedclone/dashboard
 * @copyright 2026 Eduardo Kraus
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
export const init = () => {
    const rows = document.querySelectorAll('#local-sc-operations tbody tr');
    rows.forEach((row) => {
        const status = row.cells[1]?.textContent?.trim();
        if (status === 'blocked' || status === 'failed') {
            row.classList.add('table-warning');
        }
    });
};
