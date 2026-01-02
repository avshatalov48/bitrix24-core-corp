(() => {
	/**
	 * @global {object} layout
	 * @global {object} jn
	 */

	const require = (ext) => jn.require(ext);

	const { MyFilesGrid } = require('disk/file-grid/my-files');
	const { CollabAccessService } = require('collab/service/access');

	BX.onViewLoaded(async () => {
		const isCollabToolEnabled = await CollabAccessService.checkAccess();

		layout.showComponent(
			new MyFilesGrid({
				parentWidget: layout,
				isCollabToolEnabled,
			}),
		);
	});
})();
