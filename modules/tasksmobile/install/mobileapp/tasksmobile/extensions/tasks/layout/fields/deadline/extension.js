/**
 * @module tasks/layout/fields/deadline
*/
jn.define('tasks/layout/fields/deadline', (require, exports, module) => {
	const { DateTimeFieldClass } = require('layout/ui/fields/datetime');
	const { DeadlinePicker } = require('tasks/deadline-picker');

	/**
	 * @class DeadlineField
	 */
	class DeadlineField extends DateTimeFieldClass
	{
		showTitle()
		{
			return BX.prop.getBoolean(this.props, 'showTitle', false);
		}

		getDefaultReadOnlyEmptyValue()
		{
			return BX.message('TASKS_FIELDS_DEADLINE_FIELD_EMPTY_VALUE');
		}

		getDefaultEmptyEditableValue()
		{
			return BX.message('TASKS_FIELDS_DEADLINE_FIELD_EMPTY_VALUE');
		}

		handleAdditionalFocusActions()
		{
			const currentDeadline = (this.props.deadline ? this.props.deadline * 1000 : null);

			(new DeadlinePicker()).show(currentDeadline)
				.then((deadline) => {
					this.removeFocus()
						.then(() => {
							const timeInSeconds = DeadlineField.getTimeInSeconds(deadline);
							const timeWith00Seconds = timeInSeconds - (timeInSeconds % 60);
							this.handleChange(timeWith00Seconds);
						})
						.catch(console.error);
				})
				.catch(console.error);

			return Promise.resolve();
		}
	}

	DeadlineField.propTypes = {
		...DateTimeFieldClass.propTypes,
	};

	DeadlineField.defaultProps = {
		...DateTimeFieldClass.defaultProps,
		showTitle: false,
	};

	module.exports = {
		DeadlineField,
	};
});
