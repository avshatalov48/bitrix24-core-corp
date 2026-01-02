/**
 * @module disk/opener/collab-files
 */
jn.define('disk/opener/collab-files', (require, exports, module) => {
	const { Color } = require('tokens');
	const { Loc } = require('loc');
	const { CollabRecentFiles } = require('disk/file-grid/collab-recent-files');
	const { CollabAccessService } = require('collab/service/access');

	/**
	 * @param {{
	 * 	collabId: number,
	 * 	title: string,
	 * 	onStorageLoadFailure: (apiResponse: DiskStorageResponse, fileGrid: BaseFileGrid) => void,
	 * }} props
	 * @param {PageManager} parentWidget
	 * @return {Promise<LayoutWidget>}
	 */
	const openCollabFiles = async (props = {}, parentWidget = PageManager) => {
		try
		{
			const isCollabToolEnabled = await CollabAccessService.checkAccess();
			const layoutWidget = await parentWidget.openWidget('layout', {
				backgroundColor: Color.bgPrimary.toHex(),
				titleParams: {
					text: props.title ?? Loc.getMessage('M_DISK_OPENER_COLLAB_FILES_DEFAULT_COMPONENT_TITLE'),
					type: 'section',
				},
			});

			layoutWidget.showComponent(new CollabRecentFiles({
				...props,
				parentWidget: layoutWidget,
				isCollabToolEnabled,
			}));

			return layoutWidget;
		}
		catch (err)
		{
			console.error(err);
			throw err;
		}
	};

	module.exports = { openCollabFiles };
});
