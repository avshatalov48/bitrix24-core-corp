/**
 * @module text-editor/internal/parser
 */
jn.define('text-editor/internal/parser', (require, exports, module) => {
	const { BBCodeParser } = require('bbcode/parser');
	const { scheme } = require('text-editor/internal/scheme');

	const parser = new BBCodeParser({
		scheme,
	});

	module.exports = {
		parser,
	};
});
