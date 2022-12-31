/**
 * @module tasks/layout/task/fields/isResultRequired
 */
jn.define('tasks/layout/task/fields/isResultRequired', (require, exports, module) => {
	const {Loc} = require('loc');
	const {BooleanField, BooleanMode} = require('layout/ui/fields/boolean');

	class IsResultRequired extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				readOnly: props.readOnly,
				isResultRequired: props.isResultRequired,
			};
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
						description: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_IS_RESULT_REQUIRED'),
						showSwitcher: true,
					},
					testId: 'isResultRequired',
					onChange: (value) => {
						this.setState({isResultRequired: value});
						this.props.onChange(value);
					},
				}),
			);
		}
	}

	module.exports = {IsResultRequired};
});