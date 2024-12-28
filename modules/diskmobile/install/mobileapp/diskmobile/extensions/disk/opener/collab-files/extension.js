/**
 * @module disk/opener/collab-files
 */
jn.define('disk/opener/collab-files', (require, exports, module) => {
	const { Color } = require('tokens');
	const { Loc } = require('loc');
	const { CollabRecentFiles } = require('disk/file-grid/collab-recent-files');

	/**
	 * @param {{
	 * 	collabId: number,
	 * 	title: string,
	 * 	onStorageLoadFailure: (apiResponse: DiskStorageResponse, fileGrid: BaseFileGrid) => void,
	 * }} props
	 * @param {PageManager} parentWidget
	 * @return {Promise<LayoutWidget>}
	 */
	const openCollabFiles = (props = {}, parentWidget = PageManager) => new Promise((resolve, reject) => {
		parentWidget.openWidget('layout', {
			backgroundColor: Color.bgPrimary.toHex(),
			titleParams: {
				text: props.title ?? Loc.getMessage('M_DISK_OPENER_COLLAB_FILES_DEFAULT_COMPONENT_TITLE'),
				type: 'section',
			},
		}).then((layoutWidget) => {
			layoutWidget.showComponent(new CollabRecentFiles({
				...props,
				parentWidget: layoutWidget,
			}));
			resolve(layoutWidget);
		}).catch((err) => {
			console.error(err);
			reject(err);
		});
	});

	module.exports = { openCollabFiles };
});
