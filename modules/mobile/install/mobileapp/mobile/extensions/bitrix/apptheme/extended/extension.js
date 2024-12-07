/**
 * @module apptheme/extended
 */
jn.define('apptheme/extended', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { transparent } = require('utils/color');
	const { colors } = require('apptheme/list');

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
	AppTheme.getColorByThemeId = (themeId = 'light') => colors[themeId];

	module.exports = { AppTheme };
});
