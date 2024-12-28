(() => {
	/**
	 * @global {object} layout
	 * @global {object} jn
	 */

	const require = (ext) => jn.require(ext);

	const { GroupFilesGrid } = require('disk/file-grid/group-files');

	BX.onViewLoaded(() => {
		layout.showComponent(
			new GroupFilesGrid({
				parentWidget: layout,
				groupId: BX.componentParameters.get('GROUP_ID', null),
			}),
		);
	});
})();
