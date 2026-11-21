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

// Admin sidebar collapse preference
document.addEventListener('click', (event) => { const btn=event.target.closest('[data-sidebar-toggle]'); if(!btn)return; document.body.classList.toggle('sidebar-collapsed'); try{localStorage.setItem('bc-admin-sidebar',document.body.classList.contains('sidebar-collapsed')?'collapsed':'expanded');}catch(e){} });


// Responsive storefront and admin navigation.
document.addEventListener('DOMContentLoaded', () => {
  const storeToggle=document.querySelector('[data-store-menu-toggle]');
  const storeNav=document.getElementById('storeCategoryNav');
  storeToggle?.addEventListener('click',()=>{const open=storeNav?.classList.toggle('mobile-nav-open')||false;storeToggle.setAttribute('aria-expanded',open?'true':'false');storeToggle.setAttribute('aria-label',open?'Close menu':'Open menu');storeToggle.innerHTML=open?'<i class="bi bi-x-lg"></i>':'<i class="bi bi-list"></i>';});
  document.querySelector('[data-mobile-search-toggle]')?.addEventListener('click',()=>{const form=document.querySelector('[data-header-search]');form?.classList.toggle('mobile-search-open');if(form?.classList.contains('mobile-search-open'))form.querySelector('input')?.focus();});
  document.querySelectorAll('.nav-dropdown>.nav-parent').forEach(link=>link.addEventListener('click',event=>{if(window.matchMedia('(max-width: 767px)').matches){event.preventDefault();link.parentElement.classList.toggle('mobile-submenu-open');}}));
  const adminToggle=document.querySelector('[data-admin-menu-toggle]'),sidebar=document.getElementById('adminSidebar');
  adminToggle?.addEventListener('click',()=>{const open=sidebar?.classList.toggle('mobile-sidebar-open')||false;adminToggle.setAttribute('aria-expanded',open?'true':'false');adminToggle.setAttribute('aria-label',open?'Close admin menu':'Open admin menu');adminToggle.innerHTML=open?'<i class="bi bi-x-lg"></i>':'<i class="bi bi-list"></i>';});
  document.querySelectorAll('.admin-main form').forEach(form=>{const input=form.querySelector('input[name="q"],input[type="search"]');if(!input)return;form.classList.add('admin-search-form');const btn=form.querySelector('button[type="submit"],button:not([type])');if(btn){btn.classList.add('admin-search-submit');const label=btn.textContent.trim()||'Search';btn.innerHTML='<i class="bi bi-search admin-search-icon" aria-hidden="true"></i><span class="admin-search-label"></span>';btn.querySelector('.admin-search-label').textContent=label;btn.addEventListener('click',event=>{if(window.matchMedia('(max-width: 800px)').matches&&!form.classList.contains('mobile-search-open')){event.preventDefault();form.classList.add('mobile-search-open');input.focus();}});}});
});


// Product quantity controls and compact mobile shop search.
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-quantity-stepper]').forEach((wrap) => {
    const input=wrap.querySelector('input[name="quantity"]');
    const min=Number(input?.min||1), max=Number(input?.max||999999);
    const clamp=()=>{let n=Number.parseInt(input.value,10);if(!Number.isFinite(n))n=min;n=Math.max(min,Math.min(max,n));input.value=String(n);};
    wrap.querySelector('[data-quantity-minus]')?.addEventListener('click',()=>{clamp();input.value=String(Math.max(min,Number(input.value)-1));});
    wrap.querySelector('[data-quantity-plus]')?.addEventListener('click',()=>{clamp();input.value=String(Math.min(max,Number(input.value)+1));});
    input?.addEventListener('change',clamp);input?.addEventListener('input',()=>{if(input.value!==''&&Number(input.value)<min)input.value=String(min);});
    input?.form?.addEventListener('submit',clamp);
  });
  document.querySelector('[data-shop-search-toggle]')?.addEventListener('click',()=>{const form=document.querySelector('.shop-search-toolbar');form?.classList.toggle('mobile-shop-search-open');if(form?.classList.contains('mobile-shop-search-open'))form.querySelector('input[type="search"]')?.focus();});
});
