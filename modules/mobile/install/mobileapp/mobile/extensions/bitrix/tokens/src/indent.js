/**
 * Attention!
 * This file is generated automatically from the apptheme generator
 * Any manual changes to this file are not allowed.
 */

/**
 * @module tokens/src/indent
 */
jn.define('tokens/src/indent', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class Indent
	 * @extends {BaseEnum<Indent>}
	 */
	class Indent extends BaseEnum
	{}

	Indent.XL4 = new Indent('XL4', AppTheme.styles.indentXL4);
	Indent.XL3 = new Indent('XL3', AppTheme.styles.indentXL3);
	Indent.XL2 = new Indent('XL2', AppTheme.styles.indentXL2);
	Indent.XL = new Indent('XL', AppTheme.styles.indentXL);
	Indent.L = new Indent('L', AppTheme.styles.indentL);
	Indent.M = new Indent('M', AppTheme.styles.indentM);
	Indent.S = new Indent('S', AppTheme.styles.indentS);
	Indent.XS = new Indent('XS', AppTheme.styles.indentXS);
	Indent.XS2 = new Indent('XS2', AppTheme.styles.indent2XS);

	module.exports = { Indent };
});
