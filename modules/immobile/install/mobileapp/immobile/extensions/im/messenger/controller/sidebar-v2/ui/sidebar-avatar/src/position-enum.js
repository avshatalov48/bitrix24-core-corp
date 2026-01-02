/**
 * @module im/messenger/controller/sidebar-v2/ui/sidebar-avatar/src/position-enum
 */
jn.define('im/messenger/controller/sidebar-v2/ui/sidebar-avatar/src/position-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class PositionEnumType
	 * @template TPositionEnumType
	 * @extends {BaseEnum<PositionEnumType>}
	 */
	class PositionEnum extends BaseEnum
	{
		static TOP_RIGHT = new PositionEnum('TOP_RIGHT', {
			top: 0,
			right: 0,
		});

		static BOTTOM_RIGHT = new PositionEnum('BOTTOM_RIGHT', {
			bottom: 0,
			right: 0,
		});
	}

	module.exports = {
		PositionEnum,
	};
});
