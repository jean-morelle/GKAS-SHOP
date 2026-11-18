// Interactions légères, sans framework.

// Confirmation avant les opérations destructrices : <form data-confirm="…">
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (form instanceof HTMLFormElement && form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
        event.preventDefault();
    }
});

// Évite les doubles envois (ex. double clic sur « Valider la commande »)
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || event.defaultPrevented || !form.hasAttribute('data-once')) return;
    setTimeout(() => form.querySelectorAll('button[type="submit"]').forEach((b) => (b.disabled = true)), 0);
});

document.addEventListener('click', async (event) => {
    // Afficher / masquer : <button data-toggle="#id">
    const toggle = event.target.closest('[data-toggle]');
    if (toggle) {
        const target = document.querySelector(toggle.dataset.toggle);
        if (target) {
            target.hidden = !target.hidden;
            toggle.setAttribute('aria-expanded', String(!target.hidden));
        }
    }

    // Copier dans le presse-papiers : <button data-copy="texte">
    const copy = event.target.closest('[data-copy]');
    if (copy) {
        try {
            await navigator.clipboard.writeText(copy.dataset.copy);
            const label = copy.querySelector('[data-copy-label]');
            if (label) {
                const original = label.textContent;
                label.textContent = 'Copié !';
                setTimeout(() => (label.textContent = original), 1800);
            }
        } catch {
            window.prompt('Copiez ce lien :', copy.dataset.copy);
        }
    }

    // Partage natif sur mobile : <button data-share="url" data-share-title="…">
    const share = event.target.closest('[data-share]');
    if (share && navigator.share) {
        event.preventDefault();
        navigator.share({ title: share.dataset.shareTitle || document.title, url: share.dataset.share }).catch(() => {});
    }

    // Galerie produit : <button data-gallery-src="url">
    const thumb = event.target.closest('[data-gallery-src]');
    if (thumb) {
        const main = document.querySelector('[data-gallery-main]');
        if (main) main.src = thumb.dataset.gallerySrc;
    }
});

// Champs dépendants d'un choix radio : <div data-show-when="name=value">
function refreshConditional() {
    document.querySelectorAll('[data-show-when]').forEach((el) => {
        const [name, value] = el.dataset.showWhen.split('=');
        const checked = document.querySelector(`input[name="${name}"]:checked`);
        el.hidden = !checked || checked.value !== value;
    });
}
document.addEventListener('change', refreshConditional);
document.addEventListener('DOMContentLoaded', refreshConditional);

// Synchronise un sélecteur de couleur et son champ texte
document.addEventListener('input', (event) => {
    const el = event.target;
    if (el.dataset && el.dataset.colorSync) {
        const other = document.getElementById(el.dataset.colorSync);
        if (other && /^#[0-9a-fA-F]{6}$/.test(el.value)) other.value = el.value;
        document.querySelectorAll('[data-color-preview]').forEach((p) => p.style.setProperty('--shop-primary', el.value));
    }
});
