/**
 * @module ui-system/blocks/avatar/src/enums/accent-gradient-enum
 */
jn.define('ui-system/blocks/avatar/src/enums/accent-gradient-enum', (require, exports, module) => {
	const { Color } = require('tokens');
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class AvatarAccentGradient
	 * @template TAvatarAccentGradient
	 * @extends {BaseEnum<AvatarAccentGradient>}
	 */
	class AvatarAccentGradient extends BaseEnum
	{
		static GREEN = new AvatarAccentGradient('GREEN', ['#1bce42', '#bbed21', '#26d357']);

		static BLUE = new AvatarAccentGradient('BLUE', ['#86ffc7', '#0075ff']);

		static ORANGE = new AvatarAccentGradient('ORANGE', [Color.accentMainWarning.toHex()]);
	}

	module.exports = { AvatarAccentGradient };
});
