// Shared storefront/admin JavaScript. Theme controls use delegation so they work
// even if a page's navigation is rendered or changed after initial load.
(() => {
    const root = document.documentElement;
    const readSavedTheme = () => {
        try { return localStorage.getItem('bc-theme') === 'dark' ? 'dark' : 'light'; }
        catch (error) { return 'light'; }
    };

    if (!root.dataset.theme) root.dataset.theme = readSavedTheme();

    const updateThemeButtons = () => {
        const dark = root.dataset.theme === 'dark';
        document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
            const label = dark ? 'Switch to light mode' : 'Switch to dark mode';
            const icon = button.querySelector('[aria-hidden="true"]');
            if (icon) icon.textContent = dark ? '☀' : '☾';
            button.setAttribute('aria-label', label);
            button.setAttribute('title', label);
            button.setAttribute('aria-pressed', dark ? 'true' : 'false');
        });
    };

    // Capture phase ensures a theme click still works if another handler stops bubbling.
    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-theme-toggle]');
        if (!button) return;
        event.preventDefault();
        root.dataset.theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
        try { localStorage.setItem('bc-theme', root.dataset.theme); } catch (error) { /* Theme still changes for this page. */ }
        updateThemeButtons();
    }, true);

    const init = () => {
        document.querySelectorAll('[data-confirm]').forEach((element) => {
            element.addEventListener('click', (event) => {
                if (!window.confirm(element.getAttribute('data-confirm') || 'Are you sure?')) event.preventDefault();
            });
        });
        updateThemeButtons();
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
    else init();
})();

// Storefront sliders, product rails and image zoom.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-product-rail]').forEach((rail) => {
        const section = rail.closest('.product-rail-section') || rail.parentElement;
        const prev = section.querySelector('[data-rail-prev]');
        const next = section.querySelector('[data-rail-next]');
        const step = () => Math.max(rail.clientWidth * .82, 220);
        prev?.addEventListener('click', () => rail.scrollBy({ left: -step(), behavior: 'smooth' }));
        next?.addEventListener('click', () => rail.scrollBy({ left: step(), behavior: 'smooth' }));
    });
    document.querySelectorAll('[data-slider]').forEach((slider) => {
        const slides = [...slider.querySelectorAll('.hero-slide')];
        const dots = [...slider.querySelectorAll('[data-slide-dot]')];
        if (slides.length < 2) return;
        let active = 0;
        const show = (index) => {
            active = (index + slides.length) % slides.length;
            slides.forEach((slide, i) => slide.classList.toggle('active', i === active));
            dots.forEach((dot, i) => dot.classList.toggle('active', i === active));
        };
        slider.querySelector('[data-slide-prev]')?.addEventListener('click', () => show(active - 1));
        slider.querySelector('[data-slide-next]')?.addEventListener('click', () => show(active + 1));
        dots.forEach((dot, i) => dot.addEventListener('click', () => show(i)));
        let timer = window.setInterval(() => show(active + 1), 6000);
        slider.addEventListener('mouseenter', () => window.clearInterval(timer));
        slider.addEventListener('mouseleave', () => { window.clearInterval(timer); timer = window.setInterval(() => show(active + 1), 6000); });
    });
    const zoom = document.querySelector('[data-zoom-overlay]');
    const zoomImage = zoom?.querySelector('[data-zoom-image]');
    const detailImage = document.querySelector('[data-image-zoom] img');
    const closeZoom = () => zoom?.classList.remove('open');
    if (zoom && zoomImage && detailImage) {
        const openZoom = () => { zoomImage.src = detailImage.src; zoom.classList.add('open'); };
        document.querySelector('[data-image-zoom]')?.addEventListener('click', openZoom);
        document.querySelector('[data-image-zoom]')?.addEventListener('keydown', (event) => { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); openZoom(); } });
        zoom.querySelector('[data-zoom-close]')?.addEventListener('click', closeZoom);
        zoom.addEventListener('click', (event) => { if (event.target === zoom) closeZoom(); });
        document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeZoom(); });
    }
});
