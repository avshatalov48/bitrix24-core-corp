/**
 * @module utils/enums
 */
jn.define('utils/enums', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	const isEnum = (value) => value instanceof BaseEnum;

	module.exports = {
		isEnum,
	};
});
