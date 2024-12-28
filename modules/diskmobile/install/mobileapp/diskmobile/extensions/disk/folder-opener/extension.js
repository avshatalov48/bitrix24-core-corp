/**
 * @module disk/folder-opener
 */
jn.define('disk/folder-opener', (require, exports, module) => {
	const { selectById } = require('disk/statemanager/redux/slices/files/selector');
	const store = require('statemanager/redux/store');

	async function openFolder(folderId, context = null, parentWidget = PageManager)
	{
		const { FolderFilesGrid } = await requireLazy('disk:file-grid/folder-files');

		const folder = selectById(store.getState(), folderId);

		parentWidget.openWidget(
			'layout',
			{
				title: folder.name,
				onReady: (layoutWidget) => {
					layoutWidget.showComponent(new FolderFilesGrid({
						parentWidget: layout,
						folderId,
						context,
					}));
				},
				onError: (error) => console.error(error),
			},
		);
	}

	module.exports = { openFolder };
});
