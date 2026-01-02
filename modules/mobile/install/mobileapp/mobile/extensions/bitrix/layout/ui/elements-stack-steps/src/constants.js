/**
 * @module layout/ui/elements-stack-steps/constants
 */
jn.define('layout/ui/elements-stack-steps/constants', (require, exports, module) => {
	const { Indent, Color } = require('tokens');

	const ELEMENT_SIZE = 30;
	const ELEMENT_BORDER = Indent.XS2;
	const DEFAULT_TEXT_COLOR = Color.base4;
	const DEFAULT_TEXT_HEIGHT = 16;

	module.exports = {
		ELEMENT_SIZE,
		ELEMENT_BORDER,
		DEFAULT_TEXT_COLOR,
		DEFAULT_TEXT_HEIGHT,
	};
});
