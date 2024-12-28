/**
 * @module tasks/layout/fields/user-fields/field/base
 */
jn.define('tasks/layout/fields/user-fields/field/base', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { getFieldValidator } = require('tasks/layout/fields/user-fields/validator');

	/**
	 * @abstract
	 */
	class BaseField extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.validator = getFieldValidator(props);

			this.state = {
				value: props.value,
				shouldShowErrors: Boolean(props.shouldShowErrors),
			};
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				value: props.value,
				shouldShowErrors: Boolean(props.shouldShowErrors),
			};
		}

		render()
		{
			console.error('Method render should be overridden');
		}

		isValid()
		{
			this.validator.setValue(this.state.value);

			return this.validator.isValid();
		}

		isValidByRequired()
		{
			this.validator.setValue(this.state.value);

			return this.validator.isValidByRequired();
		}

		isValueValid(value)
		{
			this.validator.setValue(this.state.value);

			return this.validator.isValueValid(value);
		}

		showValueValidationError(value)
		{
			this.validator.showValueValidationError(value, this.parentWidget);
		}

		get shouldShowErrors()
		{
			return this.state.shouldShowErrors;
		}

		get parentWidget()
		{
			return this.props.layout;
		}

		get isEmpty()
		{
			if (this.isMultiple)
			{
				return this.state.value.every((value) => value === '');
			}

			return this.state.value === '';
		}

		get isReadOnly()
		{
			return !this.props.isEditable;
		}

		get isMandatory()
		{
			return this.props.isMandatory;
		}

		get isMultiple()
		{
			return this.props.isMultiple;
		}

		get settings()
		{
			return this.props.settings;
		}

		get testId()
		{
			return this.props.testId;
		}

		get icon()
		{
			return '';
		}
	}

	module.exports = { BaseField };
});
