/**
 * @module tasks/layout/task/fields/isResultRequired
 */
jn.define('tasks/layout/task/fields/isResultRequired', (require, exports, module) => {
	const { Loc } = require('loc');
	const { BooleanField, BooleanMode } = require('layout/ui/fields/boolean');

	class IsResultRequired extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				readOnly: props.readOnly,
				isResultRequired: props.isResultRequired,
			};

			this.handleOnChange = this.handleOnChange.bind(this);
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				readOnly: props.readOnly,
				isResultRequired: props.isResultRequired,
			};
		}

		updateState(newState)
		{
			this.setState({
				readOnly: newState.readOnly,
				isResultRequired: newState.isResultRequired,
			});
		}

		handleOnChange(value)
		{
			this.setState({ isResultRequired: value });
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
					value: this.state.isResultRequired,
					config: {
						deepMergeStyles: this.props.deepMergeStyles,
						mode: BooleanMode.SWITCHER,
						description: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_IS_RESULT_REQUIRED_MSGVER_1'),
						showSwitcher: true,
					},
					testId: 'isResultRequired',
					onChange: this.handleOnChange,
				}),
			);
		}
	}

	module.exports = { IsResultRequired };
});
