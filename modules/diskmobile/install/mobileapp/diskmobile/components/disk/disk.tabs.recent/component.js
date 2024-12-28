(() => {
	/**
	 * @global {object} layout
	 * @global {object} jn
	 */

	const require = (ext) => jn.require(ext);

	const { RecentFilesGrid } = require('disk/file-grid/recent-files');

	BX.onViewLoaded(() => {
		layout.showComponent(
			new RecentFilesGrid({
				parentWidget: layout,
			}),
		);
	});
})();
