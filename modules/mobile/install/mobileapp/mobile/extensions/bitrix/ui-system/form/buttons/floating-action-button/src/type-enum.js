/**
 * @module ui-system/form/buttons/floating-action-button/src/type-enum
 */
jn.define('ui-system/form/buttons/floating-action-button/src/type-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class FloatingActionButtonType
	 * @template TFloatingActionButtonType
	 * @extends {BaseEnum<FloatingActionButtonType>}
	 */
	class FloatingActionButtonType extends BaseEnum
	{
		static COMMON = new FloatingActionButtonType('COMMON', 'common');

		static COPILOT = new FloatingActionButtonType('COPILOT', 'copilot');
	}

	module.exports = { FloatingActionButtonType };
});
