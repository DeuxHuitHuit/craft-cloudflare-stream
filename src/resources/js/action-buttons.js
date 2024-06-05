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
	document.querySelectorAll('.cfstream-video').forEach((video) => {
		const button = video.closest('.cfstream-ctx').querySelector('.cfstream-action.cfstream-action-thumbnail');
		if (!button) {
			return;
		}
		const updateTime = function (e) {
			button.dataset.time = video.currentTime;
			button.dataset.duration = video.duration;
			button.dataset.event = e.type;
		};
		video.addEventListener('timeupdate', updateTime);
		video.addEventListener('pause', updateTime);
		video.addEventListener('play', updateTime);
		video.addEventListener('canplay', updateTime);
		video.addEventListener('durationchange', updateTime);
	});
});
