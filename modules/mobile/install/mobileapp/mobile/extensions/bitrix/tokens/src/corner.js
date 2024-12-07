/**
 * Attention!
 * This file is generated automatically from the apptheme generator
 * Any manual changes to this file are not allowed.
 */

/**
 * @module tokens/src/corner
 */
jn.define('tokens/src/corner', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class Corner
	 * @extends {BaseEnum<Corner>}
	 */
	class Corner extends BaseEnum
	{}

	Corner.XL = new Corner('XL', AppTheme.styles.cornerXL);
	Corner.L = new Corner('L', AppTheme.styles.cornerL);
	Corner.M = new Corner('M', AppTheme.styles.cornerM);
	Corner.S = new Corner('S', AppTheme.styles.cornerS);
	Corner.XS = new Corner('XS', AppTheme.styles.cornerXS);

	module.exports = { Corner };
});
