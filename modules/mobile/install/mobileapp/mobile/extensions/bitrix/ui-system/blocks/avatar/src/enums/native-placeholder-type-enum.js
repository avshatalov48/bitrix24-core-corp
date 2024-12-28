/**
 * @module ui-system/blocks/avatar/src/enums/native-placeholder-type-enum
 */
jn.define('ui-system/blocks/avatar/src/enums/native-placeholder-type-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class AvatarNativePlaceholderType
	 * @template TAvatarNativePlaceholderType
	 * @extends {BaseEnum<AvatarNativePlaceholderType>}
	 */
	class AvatarNativePlaceholderType extends BaseEnum
	{
		static SVG = new AvatarNativePlaceholderType('SVG', 'svg');

		static LETTERS = new AvatarNativePlaceholderType('LETTERS', 'letters');

		static NONE = new AvatarNativePlaceholderType('NONE', 'none');

		isSvg()
		{
			return this.equal(AvatarNativePlaceholderType.SVG);
		}

		isLetters()
		{
			return this.equal(AvatarNativePlaceholderType.LETTERS);
		}
	}

	module.exports = { AvatarNativePlaceholderType };
});
