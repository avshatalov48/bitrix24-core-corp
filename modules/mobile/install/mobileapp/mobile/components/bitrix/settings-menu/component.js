(() => {
	const require = (ext) => jn.require(ext);
	const { SettingsMenu } = require('settings/menu');

	BX.onViewLoaded(() => {
		layout.showComponent(new SettingsMenu());
	});
})();
