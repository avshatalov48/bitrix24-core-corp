/**
 * @module tasks/layout/task/fields/canChangeDeadline
 */
jn.define('tasks/layout/task/fields/canChangeDeadline', (require, exports, module) => {
	const {Loc} = require('loc');
	const {BooleanField, BooleanMode} = require('layout/ui/fields/boolean');

	class CanChangeDeadline extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				readOnly: props.readOnly,
				canChangeDeadline: props.canChangeDeadline,
			};
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
						description: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_CAN_CHANGE_DEADLINE'),
						showSwitcher: true,
					},
					testId: 'canChangeDeadline',
					onChange: (value) => {
						this.setState({canChangeDeadline: value});
						this.props.onChange(value);
					},
				}),
			);
		}
	}

	module.exports = {CanChangeDeadline};
});