const menuToggle = document.querySelector('[data-menu-toggle]');
const navigation = document.querySelector('#navigation');
menuToggle?.addEventListener('click', () => {
    const open = menuToggle.getAttribute('aria-expanded') !== 'true';
    menuToggle.setAttribute('aria-expanded', String(open));
    navigation.classList.toggle('is-open', open);
});

navigation?.querySelectorAll('a').forEach(link => link.addEventListener('click', () => {
    navigation.classList.remove('is-open');
    menuToggle?.setAttribute('aria-expanded', 'false');
}));

const megaMenu = document.querySelector('.mega-menu');
document.addEventListener('click', event => {
    if (megaMenu && !megaMenu.contains(event.target)) megaMenu.open = false;
});
document.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
        if (megaMenu?.open) { megaMenu.open = false; megaMenu.querySelector('summary').focus(); }
        if (navigation?.classList.contains('is-open')) {
            navigation.classList.remove('is-open');
            menuToggle?.setAttribute('aria-expanded', 'false');
            menuToggle?.focus();
        }
    }
});

document.querySelectorAll('[data-open-dialog]').forEach(button => {
    button.addEventListener('click', () => {
        const dialog = document.getElementById(button.dataset.openDialog);
        if (!dialog) return;
        dialog.showModal();
        document.body.classList.add('dialog-open');
    });
});

document.querySelectorAll('dialog').forEach(dialog => {
    dialog.querySelector('[data-close-dialog]')?.addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', event => {
        const rect = dialog.getBoundingClientRect();
        if (event.target === dialog && (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom)) dialog.close();
    });
    dialog.addEventListener('close', () => document.body.classList.remove('dialog-open'));
    dialog.querySelector('[data-inquire]')?.addEventListener('click', event => {
        const subject = document.querySelector('#inquiry-subject');
        if (subject) subject.value = `Inquiry: ${event.currentTarget.dataset.inquire}`;
        dialog.close();
        window.setTimeout(() => subject?.focus({ preventScroll: true }), 100);
    });
});

document.querySelector('[data-product-filter]')?.addEventListener('change', event => {
    document.querySelectorAll('[data-category]').forEach(card => {
        card.hidden = event.target.value !== 'all' && card.dataset.category !== event.target.value;
    });
});

document.querySelectorAll('form[data-confirm]').forEach(form => {
    form.addEventListener('submit', event => {
        if (!window.confirm(form.dataset.confirm)) event.preventDefault();
    });
});
