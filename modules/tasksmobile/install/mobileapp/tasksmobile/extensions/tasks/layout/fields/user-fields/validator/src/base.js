/**
 * @module tasks/layout/fields/user-fields/validator/base
 */
jn.define('tasks/layout/fields/user-fields/validator/base', (require, exports, module) => {
	const { Loc } = require('tasks/loc');
	const { showErrorToast } = require('toast');

	class BaseValidator
	{
		constructor(fieldData)
		{
			this.fieldData = { ...fieldData };
		}

		setValue(value)
		{
			this.fieldData = {
				...this.fieldData,
				value,
			};
		}

		isValid()
		{
			const { isMultiple, value } = this.fieldData;

			return (isMultiple ? value.every((val) => this.isValueValid(val)) : this.isValueValid(value));
		}

		isValidByRequired()
		{
			const { isMandatory, isMultiple, value } = this.fieldData;

			if (isMandatory)
			{
				return (isMultiple ? value.some((val) => val !== '') : value !== '');
			}

			return true;
		}

		isValueValid(value)
		{
			return this.isValidByRequired() && this.isValueValidByRules(value);
		}

		isValueValidByRules(value)
		{
			return true;
		}

		getValidationError(value)
		{
			if (!this.isValidByRequired())
			{
				return Loc.getMessage('TASKS_FIELDS_USER_FIELDS_VALIDATOR_REQUIRED_ERROR');
			}

			if (!this.isValueValidByRules(value))
			{
				return Loc.getMessage('TASKS_FIELDS_USER_FIELDS_VALIDATOR_RULES_ERROR');
			}

			return null;
		}

		showValueValidationError(value, parentWidget)
		{
			const message = this.getValidationError(value);
			if (message)
			{
				showErrorToast({ message }, parentWidget);
			}
		}
	}

	module.exports = { BaseValidator };
});
