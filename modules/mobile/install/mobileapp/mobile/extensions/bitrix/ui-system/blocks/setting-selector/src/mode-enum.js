/**
 * @module ui-system/blocks/setting-selector/src/mode-enum
 */
jn.define('ui-system/blocks/setting-selector/src/mode-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class SettingMode
	 * @template TSettingMode
	 * @extends {BaseEnum<SettingMode>}
	 */
	class SettingMode extends BaseEnum
	{
		static PARAMETER = new SettingMode('PARAMETER', 'PARAMETER');

		static TOGGLE = new SettingMode('TOGGLE', 'TOGGLE');

		static RIGHT_ICON = new SettingMode('RIGHT_ICON', 'RIGHT_ICON');

		static resolveValue(value)
		{
			if (typeof value === 'string')
			{
				return SettingMode.getEnum(value.toUpperCase()) || null;
			}

			return null;
		}

		getStyle()
		{
			return this.getValue();
		}
	}

	module.exports = {
		SettingMode,
	};
});
