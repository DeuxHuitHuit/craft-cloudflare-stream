document.addEventListener('DOMContentLoaded', function () {
	document.querySelectorAll('.cfstream-action').forEach((btn) => {
		const actionUrl = btn.dataset.actionUrl;
		btn.addEventListener('click', function () {
			Craft.postActionRequest(actionUrl, { ...btn.dataset }, function (result, status) {
				if (status === 'success' && result.success) {
					// Refresh UI, show notices, etc.
					window.location.reload();
					return;
				}
			});
		});
	});
});
