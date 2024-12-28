(() => {
	/**
	 * @global {object} layout
	 * @global {object} jn
	 */

	const require = (ext) => jn.require(ext);

	const { SharedFilesGrid } = require('disk/file-grid/shared-files');

	BX.onViewLoaded(() => {
		layout.showComponent(
			new SharedFilesGrid({
				parentWidget: layout,
			}),
		);
	});
})();
