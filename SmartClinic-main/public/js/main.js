/* ============================================================
   SmartClinic — main.js
   Navbar scroll, animaciones de entrada, menú móvil
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {

  // ── Navbar: scroll & activo ──────────────────────────────
  const navbar = document.getElementById('sc-navbar');

  function updateNavbar() {
    if (!navbar) return;
    if (window.scrollY > 40) {
      navbar.classList.add('scrolled');
    } else {
      navbar.classList.remove('scrolled');
    }
  }

  window.addEventListener('scroll', updateNavbar, { passive: true });
  updateNavbar(); // al cargar

  // ── Navbar: marcar sección activa al hacer scroll ────────
  const sections  = document.querySelectorAll('section[id]');
  const navLinks  = document.querySelectorAll('.nav-links a');

  function setActiveLink() {
    let current = '';
    sections.forEach(sec => {
      if (window.scrollY >= sec.offsetTop - 100) current = sec.id;
    });
    navLinks.forEach(link => {
      link.classList.remove('active');
      if (link.getAttribute('href').includes(current) && current) {
        link.classList.add('active');
      }
    });
  }

  if (sections.length > 0) {
    window.addEventListener('scroll', setActiveLink, { passive: true });
  }

  // ── Menú móvil ───────────────────────────────────────────
  const toggle = document.getElementById('navToggle');
  const mobile = document.getElementById('navMobile');

  // Inyectar estilos del menú móvil dinámicamente
  const mobileStyle = document.createElement('style');
  mobileStyle.textContent = `
    .nav-mobile {
      display: none;
      background: #fff;
      border-top: 1px solid #E5E5EA;
      padding: 16px 0;
    }
    .nav-mobile.open { display: block; }
    .nav-mobile ul { display: flex; flex-direction: column; }
    .nav-mobile ul li a {
      display: block;
      padding: 12px 24px;
      font-size: 15px; font-weight: 600;
      color: #3A3A3C;
      transition: color .2s, background .2s;
    }
    .nav-mobile ul li a:hover { color: #033B9F; background: #EAF5FD; }
    .nav-mobile ul li a.nav-cta {
      margin: 8px 24px 0;
      text-align: center;
      border-radius: 10px;
    }
  `;
  document.head.appendChild(mobileStyle);

  if (toggle && mobile) {
    toggle.addEventListener('click', () => {
      mobile.classList.toggle('open');
      // Animar hamburger
      const spans = toggle.querySelectorAll('span');
      if (mobile.classList.contains('open')) {
        spans[0].style.transform = 'translateY(7px) rotate(45deg)';
        spans[1].style.opacity   = '0';
        spans[2].style.transform = 'translateY(-7px) rotate(-45deg)';
      } else {
        spans.forEach(s => { s.style.transform = ''; s.style.opacity = ''; });
      }
    });

    // Cerrar al hacer clic en link
    mobile.querySelectorAll('a').forEach(a => {
      a.addEventListener('click', () => {
        mobile.classList.remove('open');
        toggle.querySelectorAll('span').forEach(s => {
          s.style.transform = ''; s.style.opacity = '';
        });
      });
    });
  }

  // ── Reveal on scroll ─────────────────────────────────────
  const reveals = document.querySelectorAll('.reveal');

  const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        revealObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

  reveals.forEach(el => revealObserver.observe(el));

  // ── Scroll suave para anclas ─────────────────────────────
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', e => {
      const target = document.querySelector(anchor.getAttribute('href'));
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

});
