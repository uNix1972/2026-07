document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('contactoForm');
  const success = document.getElementById('alertSuccess');
  const error = document.getElementById('alertError');
  const faqItems = document.querySelectorAll('.faq-item');

  faqItems.forEach((item) => {
    const button = item.querySelector('.faq-question');
    if (!button) return;
    button.addEventListener('click', () => item.classList.toggle('open'));
  });

  if (!form) return;

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const required = ['nombre', 'email', 'asunto', 'mensaje'];
    const isValid = required.every((name) => {
      const field = form.elements.namedItem(name);
      return field && String(field.value || '').trim() !== '';
    });

    if (!isValid) {
      success.style.display = 'none';
      error.textContent = 'Por favor completa todos los campos requeridos.';
      error.style.display = 'block';
      return;
    }

    try {
      const response = await fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      const data = await response.json();
      if (data.ok) {
        success.textContent = data.message || 'Mensaje recibido correctamente.';
        success.style.display = 'block';
        error.style.display = 'none';
        form.reset();
      } else {
        success.style.display = 'none';
        error.textContent = data.message || 'No fue posible enviar el mensaje.';
        error.style.display = 'block';
      }
    } catch (ex) {
      success.style.display = 'none';
      error.textContent = 'No fue posible enviar el mensaje. Intenta nuevamente.';
      error.style.display = 'block';
    }
  });
});
