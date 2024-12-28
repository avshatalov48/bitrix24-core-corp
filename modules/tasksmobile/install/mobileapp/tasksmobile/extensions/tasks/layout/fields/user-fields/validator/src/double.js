/**
 * @module tasks/layout/fields/user-fields/validator/double
 */
jn.define('tasks/layout/fields/user-fields/validator/double', (require, exports, module) => {
	const { BaseValidator } = require('tasks/layout/fields/user-fields/validator/base');

	class DoubleValidator extends BaseValidator
	{
		isValueValidByRules(value)
		{
			const { minValue = 0, maxValue = 0 } = this.fieldData.settings;

			return (
				(minValue === 0 ? true : value >= minValue)
				&& (maxValue === 0 ? true : value <= maxValue)
			);
		}
	}

	module.exports = { DoubleValidator };
});
