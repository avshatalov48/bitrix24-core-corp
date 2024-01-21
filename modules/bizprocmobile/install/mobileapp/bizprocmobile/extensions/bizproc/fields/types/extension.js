/**
 * @module bizproc/fields/types
 */
jn.define('bizproc/fields/types', (require, exports, module) => {

	const { BaseTypeMap } = require('bizproc/fields/types/base');
	const { IblockTypeMap } = require('bizproc/fields/types/iblock');

	const propertyToField = {
		...BaseTypeMap,
		...IblockTypeMap,
	};

	module.exports = { propertyToField };
});
