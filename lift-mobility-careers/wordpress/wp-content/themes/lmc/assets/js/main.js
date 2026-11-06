/* Lift and Mobility Careers – progressive enhancement (no dependencies, no third-party requests). */
(function () {
	var L = window.LMC || {};

	// Mobile navigation.
	var t = document.querySelector('.lmc-nav-toggle'), n = document.getElementById('lmc-nav');
	if (t && n) {
		t.addEventListener('click', function () {
			var open = n.classList.toggle('is-open');
			t.setAttribute('aria-expanded', open ? 'true' : 'false');
		});
	}

	// ---- Privacy-first analytics: counts only, no cookies. ----
	var job = document.querySelector('[data-lmc-job]');
	var jobId = job ? parseInt(job.getAttribute('data-lmc-job'), 10) || 0 : 0;
	function hit(type, extra) {
		if (!L.hit || !navigator.sendBeacon) return;
		var f = new FormData();
		f.append('t', type);
		if (jobId) f.append('job', jobId);
		for (var k in (extra || {})) f.append(k, extra[k]);
		navigator.sendBeacon(L.hit, f);
	}
	function ga(name, params) { if (window.gtag) window.gtag('event', name, params || {}); }

	var utm = (location.search.match(/[?&]utm_source=([^&]+)/) || [])[1] || '';
	function pageHit() {
		var extra = { ref: document.referrer || '', utm: decodeURIComponent(utm), path: location.pathname };
		// Returning visitors: only when the visitor has accepted analytics (device storage needs consent).
		if (window.lmcConsent === 'yes' && L.returning) {
			try {
				var seen = localStorage.getItem('lmc_seen');
				extra.ret = seen ? '1' : '0';
				localStorage.setItem('lmc_seen', String(Date.now()));
			} catch (e) {}
		}
		hit('page', extra);
		if (jobId) ga('view_item', { item_id: 'LMC-' + jobId, item_category: 'job' });
	}
	pageHit();

	document.querySelectorAll('[data-lmc-apply]').forEach(function (a) {
		a.addEventListener('click', function () { hit('click'); ga('apply_click', { job_id: jobId }); });
	});

	// ---- Sharing ----
	document.querySelectorAll('[data-lmc-share]').forEach(function (b) {
		b.addEventListener('click', function (e) {
			var net = b.getAttribute('data-lmc-share');
			hit('share', { net: net });
			ga('share', { method: net, item_id: 'LMC-' + jobId });
			if (net === 'copy') {
				e.preventDefault();
				var url = b.getAttribute('data-url') || location.href;
				(navigator.clipboard ? navigator.clipboard.writeText(url) : Promise.reject()).then(function () {
					b.textContent = 'Link copied ✓';
				}, function () { window.prompt('Copy this link:', url); });
			}
		});
	});
	var native = document.querySelector('[data-lmc-native-share]');
	if (native && navigator.share) {
		native.hidden = false;
		native.addEventListener('click', function () {
			navigator.share({ title: document.title, url: native.getAttribute('data-url') }).then(function () { hit('share', { net: 'native' }); }, function () {});
		});
	}
	document.querySelectorAll('[data-lmc-copy]').forEach(function (b) {
		b.addEventListener('click', function () {
			var ta = b.previousElementSibling;
			if (!ta) return;
			ta.select();
			(navigator.clipboard ? navigator.clipboard.writeText(ta.value) : Promise.reject()).then(function () { b.textContent = 'Copied ✓'; }, function () { document.execCommand('copy'); b.textContent = 'Copied ✓'; });
		});
	});

	// Job alert sign-ups → one GA conversion per form submit (on-site count is recorded server-side).
	// Each form is collected once via its hidden action field, so no form can get two listeners.
	var alertForms = [];
	document.querySelectorAll('input[name="lmc_action"][value="lmc_alert_signup"]').forEach(function (el) {
		if (el.form && alertForms.indexOf(el.form) === -1) alertForms.push(el.form);
	});
	alertForms.forEach(function (f) {
		f.addEventListener('submit', function () { ga('sign_up', { method: 'job_alert' }); });
	});
	window.lmcAlertFormCount = alertForms.length; // exposed for automated tests

	// Submission form: show only the relevant application field.
	var m = document.getElementById('lmc-apply_method');
	if (m) {
		var u = document.getElementById('lmc-apply_url'), e = document.getElementById('lmc-apply_email');
		var sync = function () {
			if (u) u.closest('.lmc-field').style.display = m.value === 'url' ? '' : 'none';
			if (e) e.closest('.lmc-field').style.display = m.value === 'email' ? '' : 'none';
		};
		m.addEventListener('change', sync); sync();
	}
})();
