/**
 * @module ui-system/blocks/file/icon/src/size-enum
 */
jn.define('ui-system/blocks/file/icon/src/size-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class FileIconSize
	 * @template TFileIconSize
	 * @extends {BaseEnum<FileIconSize>}
	 */
	class FileIconSize extends BaseEnum
	{
		static NORMAL = new FileIconSize('NORMAL', {
			size: 40,
			backgroundIconSize: { height: 36, width: 28 },
			iconSize: { height: 14, width: 30 },
			mediaIconSize: 24,
			iconCoordinates: { bottom: 12, left: 0 },
			mediaIconCoordinates: { top: 10, left: 8 },
		});

		static SMALL = new FileIconSize('SMALL', {
			size: 24,
			backgroundIconSize: { height: 22, width: 16 },
			iconSize: { height: 8, width: 18 },
			mediaIconSize: 14,
			iconCoordinates: { bottom: 7, left: 0 },
			mediaIconCoordinates: { top: 6, left: 5 },
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
		getBackgroundIconSize()
		{
			return this.getValue().backgroundIconSize;
		}

		/**
		 * @return number
		 */
		getMediaIconSize() {
			return this.getValue().mediaIconSize;
		}

		/**
		 * @return number
		 */
		getIconCoordinates()
		{
			return this.getValue().iconCoordinates;
		}

		/**
		 * @return number
		 */
		getMediaIconCoordinates()
		{
			return this.getValue().mediaIconCoordinates;
		}
	}

	module.exports = { FileIconSize };
});
