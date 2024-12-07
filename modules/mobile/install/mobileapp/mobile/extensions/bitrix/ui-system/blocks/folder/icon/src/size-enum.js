/**
 * @module ui-system/blocks/folder/icon/src/size-enum
 */
jn.define('ui-system/blocks/folder/icon/src/size-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class FolderIconSize
	 * @template TFolderIconSize
	 * @extends {BaseEnum<FolderIconSize>}
	 */
	class FolderIconSize extends BaseEnum
	{
		static NORMAL = new FolderIconSize('NORMAL', {
			size: 40,
			iconSize: 21,
			iconCoordinates: { bottom: 5, left: 15 },
		});

		static SMALL = new FolderIconSize('SMALL', {
			size: 24,
			iconSize: 13,
			iconCoordinates: { bottom: 2, left: 10 },
		});

		/**
		 * @return number
		 */
		getSize()
		{
			return this.getValue().size;
		}

		/**
		 * @return number
		 */
		getIconSize()
		{
			return this.getValue().iconSize;
		}

		/**
		 * @return number
		 */
		getIconCoordinates()
		{
			return this.getValue().iconCoordinates;
		}
	}

	module.exports = { FolderIconSize };
});
