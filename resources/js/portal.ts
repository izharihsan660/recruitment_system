document.querySelectorAll<HTMLButtonElement>('[data-portal-menu-button]').forEach((button) => {
    button.addEventListener('click', () => {
        document.querySelector<HTMLElement>('[data-portal-menu]')?.classList.toggle('hidden');
    });
});
