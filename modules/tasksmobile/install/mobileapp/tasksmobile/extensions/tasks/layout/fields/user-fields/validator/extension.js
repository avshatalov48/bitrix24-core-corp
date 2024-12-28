/**
 * @module tasks/layout/fields/user-fields/validator
 */
jn.define('tasks/layout/fields/user-fields/validator', (require, exports, module) => {
	const { showErrorToast } = require('toast');
	const { Loc } = require('tasks/loc');
	const { UserFieldType } = require('tasks/enum');
	const { BaseValidator } = require('tasks/layout/fields/user-fields/validator/base');
	const { DoubleValidator } = require('tasks/layout/fields/user-fields/validator/double');
	const { StringValidator } = require('tasks/layout/fields/user-fields/validator/string');

	const getFieldValidator = (fieldData) => {
		switch (fieldData.type)
		{
			case UserFieldType.STRING:
				return new StringValidator(fieldData);

			case UserFieldType.DOUBLE:
				return new DoubleValidator(fieldData);

			default:
				return new BaseValidator(fieldData);
		}
	};

	const isFieldValid = (fieldData) => getFieldValidator(fieldData).isValid();

	const showFieldsValidationError = (parentWidget) => {
		showErrorToast(
			{
				message: Loc.getMessage('TASKS_FIELDS_USER_FIELDS_VALIDATOR_FIELDS_ERROR'),
			},
			parentWidget,
		);
	};

	module.exports = {
		getFieldValidator,
		isFieldValid,
		showFieldsValidationError,
	};
});
