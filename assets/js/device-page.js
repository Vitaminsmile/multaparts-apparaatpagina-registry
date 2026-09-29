(function () {
	'use strict';
	var select = document.querySelector('.mapr-device-page [data-mapr-sort]');
	if (select) {
		select.addEventListener('change', function () { select.form.submit(); });
	}
}());
