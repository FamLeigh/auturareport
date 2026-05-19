// ─── Nav scroll effect ─────────────────────────────────────────────────────
const header = document.getElementById('site-header');
if (header) {
  const onScroll = () => {
    header.classList.toggle('scrolled', window.scrollY > 20);
  };
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
}

// ─── Mobile nav toggle ─────────────────────────────────────────────────────
const toggle = document.getElementById('nav-toggle');
const navLinks = document.getElementById('nav-links');
if (toggle && navLinks) {
  toggle.addEventListener('click', () => {
    const open = navLinks.classList.toggle('open');
    toggle.classList.toggle('open', open);
    toggle.setAttribute('aria-expanded', open);
    document.body.style.overflow = open ? 'hidden' : '';
  });

  // Close on link click
  navLinks.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => {
      navLinks.classList.remove('open');
      toggle.classList.remove('open');
      toggle.setAttribute('aria-expanded', false);
      document.body.style.overflow = '';
    });
  });

  // Close on outside click
  document.addEventListener('click', e => {
    if (!header.contains(e.target)) {
      navLinks.classList.remove('open');
      toggle.classList.remove('open');
      toggle.setAttribute('aria-expanded', false);
      document.body.style.overflow = '';
    }
  });
}

// ─── Category filter (Writing page) ────────────────────────────────────────
const filterBtns = document.querySelectorAll('.filter-btn');
if (filterBtns.length) {
  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const cat = btn.dataset.cat;
      filterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      document.querySelectorAll('.post-card[data-cat]').forEach(card => {
        if (cat === 'all' || card.dataset.cat === cat) {
          card.style.display = '';
        } else {
          card.style.display = 'none';
        }
      });
    });
  });
}

// ─── Email capture ──────────────────────────────────────────────────────────
document.querySelectorAll('.ec-form').forEach(form => {
  form.addEventListener('submit', async e => {
    e.preventDefault();
    const msg = form.querySelector('.ec-msg');
    const btn = form.querySelector('button[type="submit"]');
    const originalText = btn.textContent;
    btn.textContent = 'Sending...';
    btn.disabled = true;
    msg.className = 'ec-msg';
    msg.textContent = '';

    const fd = new FormData(form);
    fd.append('source', form.dataset.source || 'unknown');

    try {
      const res = await fetch('/api/subscribe.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.ok) {
        msg.className = 'ec-msg success';
        msg.textContent = data.message;
        form.reset();
        if (data.download) {
          window.location.href = data.download;
        }
      } else {
        msg.className = 'ec-msg error';
        msg.textContent = data.message || 'Something went wrong.';
      }
    } catch {
      msg.className = 'ec-msg error';
      msg.textContent = 'Network error. Please try again.';
    } finally {
      btn.textContent = originalText;
      btn.disabled = false;
    }
  });
});

// ─── Contact form ───────────────────────────────────────────────────────────
const contactForm = document.getElementById('contact-form');
if (contactForm) {
  const msgEl = document.getElementById('form-message');

  contactForm.addEventListener('submit', async e => {
    e.preventDefault();
    const submitBtn = contactForm.querySelector('[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Sending...';
    submitBtn.disabled = true;

    try {
      const res = await fetch('/api/contact.php', {
        method: 'POST',
        body: new FormData(contactForm),
      });
      const data = await res.json();
      msgEl.textContent = data.message;
      msgEl.className = 'form-message ' + (data.ok ? 'success' : 'error');
      msgEl.style.display = 'block';
      if (data.ok) contactForm.reset();
    } catch {
      msgEl.textContent = 'Something went wrong. Please try emailing directly.';
      msgEl.className = 'form-message error';
      msgEl.style.display = 'block';
    } finally {
      submitBtn.textContent = originalText;
      submitBtn.disabled = false;
      msgEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
  });
}
