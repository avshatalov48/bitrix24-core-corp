/**
 * @module tokens/src/color
 * @return {Object}
 */
jn.define('tokens/src/color', (require, exports, module) => {
	const AppTheme = require('apptheme');

	const Color = { ...AppTheme.colors };

	module.exports = { Color };
});
