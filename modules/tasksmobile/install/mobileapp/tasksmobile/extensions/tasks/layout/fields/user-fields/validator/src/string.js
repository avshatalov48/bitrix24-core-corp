/**
 * @module tasks/layout/fields/user-fields/validator/string
 */
jn.define('tasks/layout/fields/user-fields/validator/string', (require, exports, module) => {
	const { BaseValidator } = require('tasks/layout/fields/user-fields/validator/base');

	class StringValidator extends BaseValidator
	{
		isValueValidByRules(value)
		{
			const { regexp = '', minLength = 0, maxLength = 0 } = this.fieldData.settings;
			const regexpObject = regexp ? new RegExp(regexp.slice(1, -1)) : null;

			return (
				(regexpObject === null ? true : regexpObject.test(value))
				&& (minLength === 0 ? true : value.length >= minLength)
				&& (maxLength === 0 ? true : value.length <= maxLength)
			);
		}
	}

	module.exports = { StringValidator };
});
