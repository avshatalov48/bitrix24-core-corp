/**
 * @module tasks/layout/task/fields/parentTask
 */
jn.define('tasks/layout/task/fields/parentTask', (require, exports, module) => {
	const { Loc } = require('loc');
	const { TaskField } = require('tasks/layout/task/fields/task');

	class ParentTask extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				parentTask: props.parentTask,
			};
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				parentTask: props.parentTask,
			};
		}

		updateState(newState)
		{
			this.setState({
				parentTask: newState.parentTask,
			});
		}

		render()
		{
			if (!this.state.parentTask || !this.state.parentTask.id)
			{
				return View({ style: { display: 'none' } });
			}

			return View(
				{
					style: (this.props.style || {}),
				},
				TaskField({
					readOnly: true,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_PARENT_TASK'),
					multiple: false,
					value: this.state.parentTask.id,
					config: {
						parentWidget: this.props.parentWidget,
						deepMergeStyles: this.props.deepMergeStyles,
						entityList: [this.state.parentTask],
						reloadEntityListFromProps: true,
						canOpenEntity: this.props.canOpenEntity,
					},
					testId: 'parentTask',
				}),
			);
		}
	}

	module.exports = { ParentTask };
});
