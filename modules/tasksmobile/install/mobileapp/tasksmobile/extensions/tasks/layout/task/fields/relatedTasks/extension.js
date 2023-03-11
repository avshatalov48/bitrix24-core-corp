/**
 * @module tasks/layout/task/fields/relatedTasks
 */
jn.define('tasks/layout/task/fields/relatedTasks', (require, exports, module) => {
	const {Loc} = require('loc');
	const {TaskField} = require('tasks/layout/task/fields/tasks');

	class RelatedTasks extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				relatedTasks: props.relatedTasks,
			};
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				relatedTasks: props.relatedTasks,
			};
		}

		updateState(newState)
		{
			this.setState({
				relatedTasks: newState.relatedTasks,
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
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_RELATED_TASKS_MSGVER_1'),
					multiple: true,
					value: Object.keys(this.state.relatedTasks),
					config: {
						parentWidget: this.props.parentWidget,
						deepMergeStyles: this.props.deepMergeStyles,
						entityList: Object.entries(this.state.relatedTasks).map(([id, title]) => ({id, title})),
						reloadEntityListFromProps: true,
					},
					testId: 'relatedTasks',
				}),
			);
		}
	}

	module.exports = {RelatedTasks};
});