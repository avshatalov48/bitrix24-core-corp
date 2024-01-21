/**
 * @module apptheme/extended
 */
jn.define('apptheme/extended', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { transparent } = require('utils/color');

	AppTheme.extend('shadow', {
		Primary: [
			transparent(AppTheme.colors.baseBlackFixed, 0.07),
			transparent(AppTheme.colors.baseBlackFixed, 0.22),
		],
	});

	module.exports = { AppTheme };
});
