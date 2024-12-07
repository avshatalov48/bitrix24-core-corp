/**
 * @module bizproc/workflow/list/bottom-panel
 */
jn.define('bizproc/workflow/list/bottom-panel', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { PureComponent } = require('layout/pure-component');
	const { BottomToolbar } = require('layout/ui/bottom-toolbar');

	const { TasksPerformer } = require('bizproc/task/tasks-performer');

	class BottomPanel extends PureComponent
	{
		/**
		 * @param {object} props
		 * @param {[]} props.tasks
		 * @param {object} props.layout
		 * @param {Function} props.onTasksCompleted
		 */
		constructor(props)
		{
			super(props);

			this.state = {
				tasks: Type.isArrayFilled(props.tasks) ? props.tasks : [],
			};

			this.onTasksCompleted = this.onTasksCompleted.bind(this);
		}

		componentWillReceiveProps(props)
		{
			this.setState({ tasks: props.tasks });
		}

		onTasksCompleted(completedTasks, delegatedTasks)
		{
			if (Type.isFunction(this.props.onTasksCompleted))
			{
				this.props.onTasksCompleted(completedTasks, delegatedTasks);
			}
		}

		render()
		{
			return new BottomToolbar({
				style: {
					borderRadius: 0,
					backgroundColor: AppTheme.colors.bgNavigation,
					paddingLeft: 0,
					paddingRight: 0,
					paddingBottom: 12,
				},
				renderContent: this.renderContent.bind(this),
			});
		}

		renderContent()
		{
			return View(
				{ style: { flex: 1, flexDirection: 'column' } },
				Text({
					style: {
						marginLeft: 19,
						marginRight: 18,
						marginTop: 10,
						marginBottom: 13,
						color: AppTheme.colors.base1,
						fontWeight: '400',
						fontSize: 14,
					},
					text: Loc.getMessage(
						'BPMOBILE_WORKFLOW_LIST_SELECTED_TASKS',
						{ '#TASKS_AMOUNT#': this.state.tasks.length },
					),
				}),
				new TasksPerformer({
					parentLayout: this.props.layout,
					tasks: this.state.tasks,
					onTasksCompleted: this.onTasksCompleted,
				}),
			);
		}
	}

	module.exports = { BottomPanel };
});
