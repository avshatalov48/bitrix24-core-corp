/**
 * @module tasks/layout/task/fields/isImportant
 */
jn.define('tasks/layout/task/fields/isImportant', (require, exports, module) => {
	const {Loc} = require('loc');
	const {BooleanField, BooleanMode} = require('layout/ui/fields/boolean');

	class IsImportant extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				readOnly: props.readOnly,
				isImportant: props.isImportant,
			};
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				readOnly: props.readOnly,
				isImportant: props.isImportant,
			};
		}

		updateState(newState)
		{
			this.setState({
				readOnly: newState.readOnly,
				isImportant: newState.isImportant,
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
					showEditIcon: !this.state.readOnly,
					showTitle: false,
					value: this.state.isImportant,
					config: {
						deepMergeStyles: this.props.deepMergeStyles,
						mode: BooleanMode.ICON,
						descriptionYes: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_IS_IMPORTANT_YES_MSGVER_1'),
						descriptionNo: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_IS_IMPORTANT_NO_MSGVER_1'),
						iconUri: `${this.props.pathToImages}/tasksmobile-layout-task-is-important.png`,
					},
					testId: 'isImportant',
					onChange: (value) => {
						this.setState({isImportant: value});
						this.props.onChange(value);
					},
				}),
			);
		}
	}

	module.exports = {IsImportant};
});