jQuery(function () {
	jQuery('.drafty .clipboard').on('click', function () {
		var key = jQuery(this).data('key'),
			td_id = '#td-' + key;

		jQuery(td_id + ' span').hide();
		jQuery(td_id + ' a').hide();
		jQuery(td_id + ' input').show().select();
	});
	jQuery('.drafty a.delete').on('click', function () {
		var key = jQuery(this).data('key');

		jQuery('#delete-' + key).click();
	});
	jQuery('.drafty a.extend').on('click', function () {
		var key = jQuery(this).data('key');

		jQuery('.drafty #extend-' + key).hide();
		jQuery('.drafty #extend-form-' + key).show();
	});
	jQuery('.drafty a.extend-cancel').on('click', function () {
		var key = jQuery(this).data('key');

		jQuery('.drafty #extend-' + key).show();
		jQuery('.drafty #extend-form-' + key).hide();
	});
});
