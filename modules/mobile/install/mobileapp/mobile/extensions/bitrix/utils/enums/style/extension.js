/**
 * @module utils/enums/style
 */
jn.define('utils/enums/style', (require, exports, module) => {
	const { Ellipsize } = require('utils/enums/style/src/ellipsize');
	const { Align } = require('utils/enums/style/src/align');

	module.exports = {
		Ellipsize,
		Align,
	};
});
