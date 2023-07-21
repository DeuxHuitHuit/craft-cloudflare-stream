document.addEventListener('DOMContentLoaded', function () {
	document.querySelectorAll('.cfstream-action').forEach((btn) => {
		const actionUrl = btn.dataset.actionUrl;
		btn.addEventListener('click', function () {
			Craft.postActionRequest(actionUrl, { ...btn.dataset }, function () {
				window.location.reload();
			});
		});
	});
});
