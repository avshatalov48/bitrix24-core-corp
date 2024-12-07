/**
 * @module ui-system/blocks/folder/icon/src/mode-enum
 */
jn.define('ui-system/blocks/folder/icon/src/mode-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');
	const { DiskIcon } = require('assets/icons');

	/**
	 * @class FolderIconMode
	 * @template TFolderIconMode
	 * @extends {BaseEnum<FolderIconMode>}
	 */
	class FolderIconMode extends BaseEnum
	{
		static BASIC = new FolderIconMode('BASIC', {
			backgroundIcon: DiskIcon.DISK_FOLDER_BLUE,
			icon: null,
		});

		static GROUP = new FolderIconMode('GROUP', {
			backgroundIcon: DiskIcon.DISK_FOLDER_BLUE,
			icon: DiskIcon.GROUP,
		});

		static SHARED = new FolderIconMode('SHARED', {
			backgroundIcon: DiskIcon.DISK_FOLDER_BLUE,
			icon: DiskIcon.LINK,
		});

		static COLLAB = new FolderIconMode('COLLAB', {
			backgroundIcon: DiskIcon.DISK_FOLDER_GREEN,
			icon: DiskIcon.COLLAB,
		});

		/**
		 * @return {DiskIcon}
		 */
		getIcon()
		{
			return this.getValue().icon;
		}

		/**
		 * @return {DiskIcon}
		 */
		getBackgroundIcon()
		{
			return this.getValue().backgroundIcon;
		}
	}

	module.exports = { FolderIconMode };
});
