/**
 * @module apptheme/extended
 */
jn.define('apptheme/extended', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { transparent } = require('utils/color');
	const { Theme } = require('apptheme/extended/src/theme-enum');
	const { AppTheme: NativeAppTheme } = require('native/apptheme');

	AppTheme.extend('shadow', {
		Primary: [
			transparent(AppTheme.colors.baseBlackFixed, 0.07),
			transparent(AppTheme.colors.baseBlackFixed, 0.22),
		],
	});

	/**
	 * @param {'light' | 'dark'} themeId
	 * @return {string}
	 */
	AppTheme.getColorByThemeId = (themeId = 'light') => AppTheme.colors[themeId];

	/**
	 *
	 * @param {'light' | 'dark' | 'newlight' | 'newdark'} id
	 */
	AppTheme.setId = (id) => {
		const themeId = id.startsWith('new') ? id : `new${id}`;

		NativeAppTheme.setId(themeId);
	};

	module.exports = { AppTheme, Theme };
});
