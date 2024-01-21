/**
 * @module tasks/layout/task/fields/isTaskControl
 */
jn.define('tasks/layout/task/fields/isTaskControl', (require, exports, module) => {
	const { Loc } = require('loc');
	const { BooleanField, BooleanMode } = require('layout/ui/fields/boolean');

	class IsTaskControl extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				readOnly: props.readOnly,
				isTaskControl: props.isTaskControl,
			};

			this.handleOnChange = this.handleOnChange.bind(this);
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				readOnly: props.readOnly,
				isTaskControl: props.isTaskControl,
			};
		}

		updateState(newState)
		{
			this.setState({
				readOnly: newState.readOnly,
				isTaskControl: newState.isTaskControl,
			});
		}

		handleOnChange(value)
		{
			this.setState({ isTaskControl: value });
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
					value: this.state.isTaskControl,
					config: {
						deepMergeStyles: this.props.deepMergeStyles,
						mode: BooleanMode.SWITCHER,
						description: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_IS_TASK_CONTROL_MSGVER_1'),
						showSwitcher: true,
					},
					testId: 'isTaskControl',
					onChange: this.handleOnChange,
				}),
			);
		}
	}

	module.exports = { IsTaskControl };
});
