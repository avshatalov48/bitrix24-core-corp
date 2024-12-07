/**
 * @module tasks/layout/task/fields/canChangeDeadline
 */
jn.define('tasks/layout/task/fields/canChangeDeadline', (require, exports, module) => {
	const { Loc } = require('loc');
	const { BooleanField, BooleanMode } = require('layout/ui/fields/boolean');

	class CanChangeDeadline extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				readOnly: props.readOnly,
				canChangeDeadline: props.canChangeDeadline,
			};

			this.handleOnChange = this.handleOnChange.bind(this);
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				readOnly: props.readOnly,
				canChangeDeadline: props.canChangeDeadline,
			};
		}

		updateState(newState)
		{
			this.setState({
				readOnly: newState.readOnly,
				canChangeDeadline: newState.canChangeDeadline,
			});
		}

		handleOnChange(value)
		{
			this.setState({ canChangeDeadline: value });
			this.props.onChange(value);
		}

		render()
		{
			return View(
				{
					style: (this.props.style || {}),
				},
				BooleanField({
					readOnly: this.state.readOnly,
					showEditIcon: false,
					showTitle: false,
					value: this.state.canChangeDeadline,
					config: {
						deepMergeStyles: this.props.deepMergeStyles,
						mode: BooleanMode.SWITCHER,
						description: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_CAN_CHANGE_DEADLINE_MSGVER_1'),
						showSwitcher: true,
					},
					testId: 'canChangeDeadline',
					onChange: this.handleOnChange,
				}),
			);
		}
	}

	module.exports = { CanChangeDeadline };
});
