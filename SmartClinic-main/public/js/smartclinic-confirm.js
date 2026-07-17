document.addEventListener('submit', function (event) {
  var form = event.target;
  if (!form || !form.matches('[data-confirm]')) {
    return;
  }

  var message = form.getAttribute('data-confirm') || '¿Confirma esta acción?';
  if (!window.confirm(message)) {
    event.preventDefault();
  }
});

document.addEventListener('click', function (event) {
  var trigger = event.target.closest('[data-confirm-link]');
  if (!trigger) {
    return;
  }

  var message = trigger.getAttribute('data-confirm-link') || '¿Confirma esta acción?';
  if (!window.confirm(message)) {
    event.preventDefault();
  }
});
