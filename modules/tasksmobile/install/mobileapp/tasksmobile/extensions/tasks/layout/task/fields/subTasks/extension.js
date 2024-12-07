/**
 * @module tasks/layout/task/fields/subTasks
 */
jn.define('tasks/layout/task/fields/subTasks', (require, exports, module) => {
	const { Loc } = require('loc');
	const { TaskField } = require('tasks/layout/task/fields/task');

	class SubTasks extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				subTasks: props.subTasks,
			};
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				subTasks: props.subTasks,
			};
		}

		updateState(newState)
		{
			this.setState({
				subTasks: newState.subTasks,
			});
		}

		render()
		{
			return View(
				{
					style: (this.props.style || {}),
				},
				TaskField({
					readOnly: true,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_SUB_TASKS'),
					multiple: true,
					value: Object.keys(this.state.subTasks),
					config: {
						parentWidget: this.props.parentWidget,
						deepMergeStyles: this.props.deepMergeStyles,
						entityList: Object.entries(this.state.subTasks).map(([id, title]) => ({ id, title })),
						reloadEntityListFromProps: true,
					},
					testId: 'subTasks',
				}),
			);
		}
	}

	module.exports = { SubTasks };
});
