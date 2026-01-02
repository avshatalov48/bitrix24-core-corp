/**
 * @module ui-system/blocks/chips/chip-button/src/loader-position-enum
 */
jn.define('ui-system/blocks/chips/chip-button/src/loader-position-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class LoaderPosition
	 * @template TCLoaderPosition
	 * @extends {BaseEnum<LoaderPosition>}
	 */
	class LoaderPosition extends BaseEnum
	{
		static CENTER = new LoaderPosition('CENTER', 'center');

		static LEFT = new LoaderPosition('LEFT', 'left');

		isLeft()
		{
			return this.equal(LoaderPosition.LEFT);
		}

		isCenter()
		{
			return this.equal(LoaderPosition.CENTER);
		}
	}

	module.exports = { LoaderPosition };
});
