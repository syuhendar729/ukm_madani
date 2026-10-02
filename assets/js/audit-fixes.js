/* Shared interaction fixes for the UI/UX audit. */
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('personModal');

    if (modal) {
        const syncModalState = () => {
            document.body.classList.toggle('modal-open', modal.classList.contains('show'));
        };
        new MutationObserver(syncModalState).observe(modal, { attributes: true, attributeFilter: ['class'] });
        syncModalState();
    }
});
