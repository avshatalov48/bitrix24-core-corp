(() => {

	const { AppUpdateNotifier } = jn.require('app-update-notifier');

	layout.showComponent(new AppUpdateNotifier({ layout }));

})();
