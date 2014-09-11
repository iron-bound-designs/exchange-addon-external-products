/**
 * Created by timothybjacobs on 9/10/14.
 */

jQuery(document).ready(function ($) {
	$(document).on('click', '#external-add-new', function (e) {
		e.preventDefault();

		var section = $(".external-vendor-section:first-of-type").clone();

		$('input', section).each(function () {
			$(this).val('');
		});

		$("#it-exchange-product-external").append(section);
	});
});