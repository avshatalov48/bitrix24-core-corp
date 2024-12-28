(() => {
	/**
	 * @global {object} layout
	 * @global {object} jn
	 */

	const require = (ext) => jn.require(ext);

	const { FolderFilesGrid } = require('disk/file-grid/folder-files');

	BX.onViewLoaded(() => {
		layout.showComponent(
			new FolderFilesGrid({
				parentWidget: layout,
				context: BX.componentParameters.get('context', null),
				folderId: BX.componentParameters.get('folderId', null),
			}),
		);
	});
})();
