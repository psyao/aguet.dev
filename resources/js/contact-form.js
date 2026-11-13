/* aguet.dev — submits the contact modal's Kami Form without a reload, through
   the Form route's JSON answers (Kami's Forms guide, "Without a reload"), so
   the modal stays open over the errors or the confirmation. Built by Vite into
   the app bundle: no inline script and no eval, so the front's CSP holds.
   Without JavaScript the form posts a full page, and the page shows the result. */

export function initContactForm() {
  const form = document.getElementById('contact_form');
  const status = document.getElementById('contact_form-status');
  const button = form && form.querySelector('[type="submit"]');
  // The page after `?sent=` holds no form: nothing to submit.
  if (!form || !status || !button) return;

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    clearErrors(form);
    status.textContent = '';
    status.classList.remove('is-error');
    button.disabled = true;

    try {
      const response = await fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: { Accept: 'application/json' },
      });
      // The status decides: a 200 whose body cannot be read is still a
      // Submission stored, never one to send again.
      const body = await response.json().catch(() => ({}));

      if (response.status === 200) {
        form.hidden = true;
        status.textContent = body.message ?? '';
        document.querySelector('[data-cf-done]').hidden = false;
        // A modal closed and opened again comes back to the confirmation.
        status.setAttribute('data-contact-focus', '');
        status.focus();
      } else if (body.errors) {
        showErrors(form, body.errors);
      } else {
        showFailure(status, form.dataset.error);
      }
    } catch {
      showFailure(status, form.dataset.error);
    } finally {
      button.disabled = false;
    }
  });
}

// A 403, a 429, a server error or no network: the input stays, the status says so.
function showFailure(status, message) {
  status.textContent = message;
  status.classList.add('is-error');
}

// The markup the page renders for a refused message without JavaScript
// (resources/views/partials/contact-modal.blade.php): a summary that takes the
// focus and links to each field, and each field's message below its box.
function showErrors(form, errors) {
  const summary = document.createElement('div');
  summary.className = 'kami-form-errors cf-alert';
  summary.setAttribute('role', 'alert');
  summary.tabIndex = -1;
  // A modal closed and opened again comes back to the summary, as on the page.
  summary.setAttribute('data-contact-focus', '');
  const heading = summary.appendChild(document.createElement('h3'));
  heading.className = 'kami-form-errors-heading';
  heading.textContent = form.dataset.errorsHeading;
  const list = summary.appendChild(document.createElement('ul'));

  for (const [name, [message]] of Object.entries(errors)) {
    const link = list.appendChild(document.createElement('li')).appendChild(document.createElement('a'));
    link.href = `#${name}`;
    link.textContent = message;

    const field = form.elements.namedItem(name);
    const box = field instanceof Element ? field.closest('.cf-field') : null;
    if (box) {
      const error = document.createElement('p');
      error.className = 'cf-error';
      error.id = `${name}-error`;
      error.textContent = message;
      box.after(error);
      box.classList.add('is-invalid');
      field.setAttribute('aria-invalid', 'true');
      field.setAttribute('aria-describedby', error.id);
    }
  }

  form.prepend(summary);
  summary.focus();
}

// Removes the errors of an earlier attempt, the page's own included.
function clearErrors(form) {
  form.querySelectorAll('.kami-form-errors, .cf-error').forEach((el) => el.remove());
  form.querySelectorAll('.cf-field.is-invalid').forEach((box) => box.classList.remove('is-invalid'));
  form.querySelectorAll('[aria-invalid]').forEach((field) => {
    field.removeAttribute('aria-invalid');
    field.removeAttribute('aria-describedby');
  });
}
