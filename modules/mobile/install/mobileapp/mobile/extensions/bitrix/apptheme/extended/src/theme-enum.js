/**
 * @module apptheme/extended/src/theme-enum
 */
jn.define('apptheme/extended/src/theme-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class Theme
	 * @template TTheme
	 * @extends {BaseEnum<Theme>}
	 */
	class Theme extends BaseEnum
	{
		static DARK = new Theme('DARK', 'dark');

		static LIGHT = new Theme('LIGHT', 'light');

		static NEW_DARK = new Theme('NEW_DARK', 'newdark');

		static NEW_LIGHT = new Theme('NEW_LIGHT', 'newlight');

		isDark()
		{
			return this.equal(Theme.DARK);
		}

		isLight()
		{
			return this.equal(Theme.LIGHT);
		}
	}

	module.exports = { Theme };
});
