/* Mobilitybase theme scripts: mobile menu, "enquire about this model" links, form submit without reload. */
(function () {
	'use strict';

	// Mobile menu
	var toggle = document.querySelector('.mb-navtoggle');
	var nav = document.getElementById('mb-nav');
	if (toggle && nav) {
		toggle.addEventListener('click', function () {
			var open = toggle.getAttribute('aria-expanded') === 'true';
			toggle.setAttribute('aria-expanded', String(!open));
			nav.classList.toggle('is-open', !open);
		});
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && nav.classList.contains('is-open')) {
				toggle.setAttribute('aria-expanded', 'false');
				nav.classList.remove('is-open');
				toggle.focus();
			}
		});
	}

	// "Enquire about X" buttons fill in the form below with the chosen product / plan.
	document.addEventListener('click', function (e) {
		var link = e.target.closest('[data-mb-enquire]');
		if (!link) return;
		var target = document.querySelector(link.getAttribute('href'));
		if (!target) return;
		var form = target.querySelector('form.mb-form');
		if (!form) return;
		e.preventDefault();
		var value = link.getAttribute('data-mb-enquire');
		var product = form.querySelector('[data-mb-product]');
		if (product) product.value = value;
		var first = value.split(' – ')[1];
		if (first) {
			form.querySelectorAll('input[name="mb_option"]').forEach(function (r) {
				if (first.indexOf(r.value) === 0) r.checked = true;
			});
		}
		var note = form.querySelector('.mb-form__chosen');
		if (!note) {
			note = document.createElement('p');
			note.className = 'mb-form__chosen mb-notice';
			form.insertBefore(note, form.querySelector('.mb-form__grid'));
		}
		note.textContent = 'Enquiring about: ' + value;
		target.scrollIntoView({ behavior: 'smooth', block: 'start' });
		var field = form.querySelector('input:not([type=hidden]):not([name=mb_website])');
		if (field) setTimeout(function () { field.focus({ preventScroll: true }); }, 400);
	});

	// Forms: validate in the browser, then send in the background.
	// Without JavaScript the form still posts normally and the page reloads with a message.
	function setError(field, msg) {
		var wrap = field.closest('.mb-field');
		if (!wrap) return;
		var el = wrap.querySelector('.mb-field__err');
		wrap.classList.toggle('is-invalid', !!msg);
		field.setAttribute('aria-invalid', msg ? 'true' : 'false');
		if (msg) {
			if (!el) {
				el = document.createElement('span');
				el.className = 'mb-field__err';
				el.id = field.id + '-err';
				wrap.appendChild(el);
			}
			el.textContent = msg;
			field.setAttribute('aria-describedby', el.id);
		} else if (el) {
			el.remove();
			field.removeAttribute('aria-describedby');
		}
	}

	function message(form, ok, text) {
		var old = form.querySelector('.mb-form__msg');
		if (old) old.remove();
		var div = document.createElement('div');
		div.className = 'mb-form__msg ' + (ok ? 'mb-form__msg--ok' : 'mb-form__msg--err');
		div.setAttribute('role', ok ? 'status' : 'alert');
		div.tabIndex = -1;
		div.textContent = text;
		form.insertBefore(div, form.firstChild);
		div.focus();
	}

	document.querySelectorAll('form.mb-form').forEach(function (form) {
		var existing = form.querySelector('.mb-form__msg');
		if (existing) existing.focus();

		form.addEventListener('submit', function (e) {
			var firstBad = null;
			form.querySelectorAll('input, textarea').forEach(function (f) {
				if (f.type === 'hidden' || f.name === 'mb_website' || f.type === 'radio') return;
				var msg = '';
				if (f.required && !f.value.trim()) msg = 'This field is required.';
				else if (f.type === 'email' && f.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(f.value)) msg = 'Please enter a valid email address.';
				else if (f.type === 'tel' && f.value && f.value.replace(/\D/g, '').length < 10) msg = 'Please enter a valid phone number.';
				setError(f, msg);
				if (msg && !firstBad) firstBad = f;
			});
			if (firstBad) {
				e.preventDefault();
				firstBad.focus();
				return;
			}
			if (!window.fetch || !window.FormData) return;
			e.preventDefault();
			form.classList.add('is-sending');
			fetch(form.getAttribute('action'), { method: 'POST', body: new FormData(form), headers: { 'X-MB-Ajax': '1' }, credentials: 'same-origin' })
				.then(function (r) { return r.json(); })
				.then(function (res) {
					form.classList.remove('is-sending');
					if (res.ok) {
						form.querySelector('.mb-form__grid').hidden = true;
						form.querySelector('.mb-form__foot').hidden = true;
						var chosen = form.querySelector('.mb-form__chosen');
						if (chosen) chosen.remove();
						message(form, true, 'Thank you — your enquiry has been sent. A member of the Mobilitybase team will be in touch shortly.');
					} else {
						Object.keys(res.errors || {}).forEach(function (name) {
							var f = form.querySelector('[name="' + name + '"]');
							if (f) setError(f, res.errors[name]);
						});
						message(form, false, 'Please check the highlighted fields and try again.');
					}
				})
				.catch(function () {
					form.classList.remove('is-sending');
					form.submit();
				});
		});
	});
})();
