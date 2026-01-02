(() => {
	const require = (ext) => jn.require(ext);
	const { TabPresetsComponent } = require('tab-presets');
	const { Tourist } = require('tourist');

	Tourist.ready()
		.then(() => {
			layout.showComponent(
				new TabPresetsComponent({ parentWidget: layout }),
			);
		})
		.catch(console.error)
	;
})();
