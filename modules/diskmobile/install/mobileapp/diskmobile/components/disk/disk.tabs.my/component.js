(() => {
	/**
	 * @global {object} layout
	 * @global {object} jn
	 */

	const require = (ext) => jn.require(ext);

	const { MyFilesGrid } = require('disk/file-grid/my-files');

	BX.onViewLoaded(() => {
		layout.showComponent(
			new MyFilesGrid({
				parentWidget: layout,
			}),
		);
	});
})();
