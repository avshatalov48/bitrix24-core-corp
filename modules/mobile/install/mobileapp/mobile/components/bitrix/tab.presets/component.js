(() => {
	const require = (ext) => jn.require(ext);
	const { TabPresetsComponent } = require('tab.presets');
	const { TabPresetsComponent: TabPresetsComponentNew } = require('tab-presets-new');
	const { Tourist } = require('tourist');
	const { Feature } = require('feature');

	Tourist.ready()
		.then(() => {
			const TabPresetsComponentClass = (
				Feature.isListViewMoveRowToSectionEndSupported() ? TabPresetsComponentNew : TabPresetsComponent
			);

			layout.showComponent(
				new TabPresetsComponentClass({ parentWidget: layout }),
			);
		})
		.catch(console.error)
	;
})();
