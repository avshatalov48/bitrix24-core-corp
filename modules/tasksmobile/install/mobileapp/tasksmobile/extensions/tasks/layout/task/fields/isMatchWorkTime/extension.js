/**
 * @module tasks/layout/task/fields/isMatchWorkTime
 */
jn.define('tasks/layout/task/fields/isMatchWorkTime', (require, exports, module) => {
	const { Loc } = require('loc');
	const { BooleanField, BooleanMode } = require('layout/ui/fields/boolean');

	class IsMatchWorkTime extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				readOnly: props.readOnly,
				isMatchWorkTime: props.isMatchWorkTime,
			};

			this.handleOnChange = this.handleOnChange.bind(this);
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				readOnly: props.readOnly,
				isMatchWorkTime: props.isMatchWorkTime,
			};
		}

		updateState(newState)
		{
			this.setState({
				readOnly: newState.readOnly,
				isMatchWorkTime: newState.isMatchWorkTime,
			});
		}

		handleOnChange(value)
		{
			this.setState({ isMatchWorkTime: value });
			this.props.onChange(value);
			this.props.datesResolver.setIsMatchWorkTime(value);
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
					value: this.state.isMatchWorkTime,
					config: {
						deepMergeStyles: this.props.deepMergeStyles,
						mode: BooleanMode.SWITCHER,
						description: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_IS_MATCH_WORK_TIME'),
						showSwitcher: true,
					},
					testId: 'isMatchWorkTime',
					onChange: this.handleOnChange,
				}),
			);
		}
	}

	module.exports = { IsMatchWorkTime };
});
