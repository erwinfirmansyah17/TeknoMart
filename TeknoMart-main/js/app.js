'use strict';
document.addEventListener('DOMContentLoaded', () => {
  const header = document.querySelector('.store-header');
  if (header) {
    let scheduled = false;
    const updateHeader = () => {
      header.classList.toggle('is-scrolled', window.scrollY > 12);
      scheduled = false;
    };
    window.addEventListener('scroll', () => {
      if (!scheduled) { scheduled = true; requestAnimationFrame(updateHeader); }
    }, { passive: true });
    updateHeader();
    const updateHeaderHeight = () => document.documentElement.style.setProperty('--store-header-height', `${header.offsetHeight}px`);
    updateHeaderHeight();
    if ('ResizeObserver' in window) new ResizeObserver(updateHeaderHeight).observe(header);
    const currentLink = document.querySelector('.store-nav [aria-current="page"]');
    if (window.matchMedia('(max-width: 820px)').matches && currentLink) currentLink.scrollIntoView({ block: 'nearest', inline: 'nearest' });
  }
  const storeMenu = document.querySelector('.store-menu-toggle');
  const storeNav = document.querySelector('.store-navigation');
  storeMenu?.addEventListener('click', () => {
    const open = storeMenu.getAttribute('aria-expanded') !== 'true';
    storeMenu.setAttribute('aria-expanded', String(open));
    storeMenu.setAttribute('aria-label', open ? 'Tutup menu' : 'Buka menu');
    storeNav?.classList.toggle('is-open', open);
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && storeMenu) {
      storeMenu.setAttribute('aria-expanded', 'false');
      storeMenu.setAttribute('aria-label', 'Buka menu');
      storeNav?.classList.remove('is-open');
    }
  });
  document.querySelectorAll('[data-carousel]').forEach(carousel => {
    const slides = [...carousel.querySelectorAll('[data-slide]')];
    if (slides.length < 2) return;
    const dots = [...carousel.querySelectorAll('[data-carousel-dot]')];
    let index = Math.max(0, slides.findIndex(slide => slide.classList.contains('active')));
    let timer;
    let touchStart = 0;
    const show = next => {
      index = (next + slides.length) % slides.length;
      slides.forEach((slide, i) => { slide.classList.toggle('active', i === index); slide.setAttribute('aria-hidden', String(i !== index)); });
      dots.forEach((dot, i) => dot.setAttribute('aria-current', String(i === index)));
    };
    const stop = () => window.clearInterval(timer);
    const start = () => {
      stop();
      if (!document.hidden && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) timer = window.setInterval(() => show(index + 1), Number(carousel.dataset.interval) || 6500);
    };
    carousel.querySelector('[data-carousel-prev]')?.addEventListener('click', () => { show(index - 1); start(); });
    carousel.querySelector('[data-carousel-next]')?.addEventListener('click', () => { show(index + 1); start(); });
    dots.forEach((dot, i) => dot.addEventListener('click', () => { show(i); start(); }));
    carousel.addEventListener('mouseenter', stop);
    carousel.addEventListener('mouseleave', start);
    carousel.addEventListener('focusin', stop);
    carousel.addEventListener('focusout', start);
    carousel.addEventListener('touchstart', event => { touchStart = event.changedTouches[0].screenX; stop(); }, { passive: true });
    carousel.addEventListener('touchend', event => {
      const delta = event.changedTouches[0].screenX - touchStart;
      if (Math.abs(delta) > 45) show(index + (delta < 0 ? 1 : -1));
      start();
    }, { passive: true });
    document.addEventListener('visibilitychange', start);
    motionPreference.addEventListener('change', start);
    start();
  });
  const motionPreference = window.matchMedia('(prefers-reduced-motion: reduce)');
  if (!motionPreference.matches && 'IntersectionObserver' in window) {
    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.08 });
    document.querySelectorAll('.product-card, .category-tile, .stat-card, .notification-card, .editorial-banner, .review-grid > .panel').forEach((element, index) => {
      element.classList.add('scroll-reveal');
      element.style.setProperty('--reveal-delay', `${(index % 4) * 55}ms`);
      observer.observe(element);
    });
    motionPreference.addEventListener('change', event => {
      if (event.matches) {
        observer.disconnect();
        document.querySelectorAll('.scroll-reveal').forEach(element => element.classList.add('is-visible'));
      }
    });
  }
  const money = value => 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
  document.querySelectorAll('[data-show-password]').forEach(toggle => {
    toggle.addEventListener('change', () => toggle.form.querySelectorAll('input[name*="password"], input[name="confirm"]').forEach(input => { input.type = toggle.checked ? 'text' : 'password'; }));
  });
  document.querySelectorAll('form[data-confirm]').forEach(form => form.addEventListener('submit', event => {
    if (!window.confirm(form.dataset.confirm)) event.preventDefault();
  }));
  const cartTotal = document.querySelector('[data-cart-total]');
  const updateCart = () => {
    if (!cartTotal) return;
    let total = 0;
    document.querySelectorAll('[data-cart-price]:checked').forEach(item => { total += Number(item.dataset.cartPrice); });
    cartTotal.textContent = money(total);
  };
  document.querySelectorAll('[data-cart-price]').forEach(input => input.addEventListener('change', updateCart));
  updateCart();
  const checkoutTotal = document.querySelector('[data-checkout-total]');
  const updateCheckout = () => {
    if (!checkoutTotal) return;
    let total = Number(checkoutTotal.dataset.checkoutTotal) - Number(checkoutTotal.dataset.discount || 0);
    document.querySelectorAll('[data-checkout-cost]').forEach(select => { total += Number(select.selectedOptions[0]?.dataset.cost || 0); });
    checkoutTotal.textContent = money(total);
    [['jasa_kirim', '[data-shipping-total]', 'Pilih kurir'], ['metode', '[data-payment-fee]', 'Pilih metode']].forEach(([name, target, placeholder]) => {
      const select = document.querySelector(`select[name="${name}"]`);
      const output = document.querySelector(target);
      if (select && output) output.textContent = select.value ? money(Number(select.selectedOptions[0]?.dataset.cost || 0)) : placeholder;
    });
  };
  document.querySelectorAll('[data-checkout-cost]').forEach(select => select.addEventListener('change', updateCheckout));
  updateCheckout();
  const photoInput = document.querySelector('[data-profile-photo]');
  const photoPreview = document.querySelector('[data-profile-preview]');
  let photoUrl;
  photoInput?.addEventListener('change', () => {
    if (photoUrl) URL.revokeObjectURL(photoUrl);
    const file = photoInput.files[0];
    if (!photoPreview) return;
    photoPreview.hidden = !file;
    if (file) { photoUrl = URL.createObjectURL(file); photoPreview.src = photoUrl; }
  });
  const menu = document.querySelector('.mobile-menu');
  menu?.addEventListener('click', () => {
    const open = document.body.classList.toggle('sidebar-open');
    menu.setAttribute('aria-expanded', String(open));
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') { document.body.classList.remove('sidebar-open'); menu?.setAttribute('aria-expanded', 'false'); }
  });
  document.addEventListener('click', event => {
    if (document.body.classList.contains('sidebar-open') && !event.target.closest('.sidebar, .mobile-menu')) {
      document.body.classList.remove('sidebar-open'); menu?.setAttribute('aria-expanded', 'false');
    }
  });
  document.querySelectorAll('img').forEach(img => {
    const fallback = () => {
      if (img.dataset.fallback) return;
      img.dataset.fallback = 'true';
      const script = document.querySelector('script[src$="js/app.js"]');
      if (script) img.src = new URL('../assets/icon/shop.png', script.src).href;
    };
    img.addEventListener('error', fallback);
    if (img.complete && img.naturalWidth === 0) fallback();
  });
});
