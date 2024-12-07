/**
 * @module ui-system/blocks/avatar/src/enums/shape-enum
 */
jn.define('ui-system/blocks/avatar/src/enums/shape-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class AvatarShape
	 * @template TAvatarShape
	 * @extends {BaseEnum<AvatarShape>}
	 */
	class AvatarShape extends BaseEnum
	{
		static HEXAGON = new AvatarShape('HEXAGON', 'hexagon');

		static CIRCLE = new AvatarShape('CIRCLE', 'circle');

		static SQUARE = new AvatarShape('SQUARE', 'square');

		isCircle()
		{
			return this.equal(AvatarShape.CIRCLE);
		}

		isHexagon()
		{
			return this.equal(AvatarShape.HEXAGON);
		}
	}

	module.exports = { AvatarShape };
});
