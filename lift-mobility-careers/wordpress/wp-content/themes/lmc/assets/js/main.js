/* Lift and Mobility Careers – tiny progressive-enhancement script (no dependencies). */
(function () {
	// Mobile navigation.
	var t = document.querySelector('.lmc-nav-toggle'), n = document.getElementById('lmc-nav');
	if (t && n) {
		t.addEventListener('click', function () {
			var open = n.classList.toggle('is-open');
			t.setAttribute('aria-expanded', open ? 'true' : 'false');
		});
	}

	// Anonymous vacancy statistics (counts only – no cookies, no personal data).
	var job = document.querySelector('[data-lmc-job]');
	var id = job ? parseInt(job.getAttribute('data-lmc-job'), 10) : 0;
	function send(e) {
		if (!id || !window.LMC || !navigator.sendBeacon) return;
		var f = new FormData(); f.append('job', id); f.append('e', e);
		navigator.sendBeacon(window.LMC.track, f);
	}
	if (id) {
		send('view');
		document.querySelectorAll('[data-lmc-apply]').forEach(function (a) {
			a.addEventListener('click', function () { send('click'); });
		});
	}

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
