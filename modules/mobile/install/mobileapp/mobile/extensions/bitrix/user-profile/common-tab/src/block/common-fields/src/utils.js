/**
 * @module user-profile/common-tab/src/block/common-fields/src/utils
 */
jn.define('user-profile/common-tab/src/block/common-fields/src/utils', (require, exports, module) => {
	const { Type } = require('type');

	const isFieldValueEmpty = (field) => {
		const { value, isMultiple } = field;

		return isMultiple
			? !Type.isArrayFilled(value)
			: Type.isNil(value) || value === '' || value === '0';
	};

	module.exports = {
		isFieldValueEmpty,
	};
});
