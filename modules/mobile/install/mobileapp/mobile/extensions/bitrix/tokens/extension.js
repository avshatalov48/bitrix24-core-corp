/**
 * @module tokens
 * @return {Object}
 */
jn.define('tokens', (require, exports, module) => {
	const { Color } = require('tokens/src/color');
	const { CornerTypes, Corner } = require('tokens/src/corner');
	const { IndentTypes, Indent } = require('tokens/src/indent');

	module.exports = { CornerTypes, Corner, IndentTypes, Indent, Color };
});
