/**
 * @module bizproc/task/tasks-performer
 */
jn.define('bizproc/task/tasks-performer', (require, exports, module) => {
	const { Type } = require('type');
	const { PureComponent } = require('layout/pure-component');
	const { Notifier } = require('bizproc/task/tasks-performer/notifier');
	const { RulesChain } = require('bizproc/task/tasks-performer/rules');

	class TasksPerformer extends PureComponent
	{
		/**
		 * @param {Object} props
		 * @param {Object} props.parentLayout
		 * @param {(tasks: []) => void} props.onTasksCompleted
		 * @param {[]} props.tasks
		 */
		constructor(props)
		{
			super(props);

			this.onTasksCompleted = this.onTasksCompleted.bind(this);
		}

		onTasksCompleted(completedTasks, delegatedTasks)
		{
			if (Type.isFunction(this.props.onTasksCompleted))
			{
				this.props.onTasksCompleted(completedTasks, delegatedTasks);
			}
		}

		get parentLayout()
		{
			return this.props.parentLayout;
		}

		get tasks()
		{
			return BX.prop.getArray(this.props, 'tasks', []);
		}

		render()
		{
			return View(
				{},
				new RulesChain({
					layout: this.parentLayout,
					tasks: this.tasks,
					onFinishRule: this.onTasksCompleted,
					notifier: new Notifier({
						tasks: this.tasks,
					}),
					useInlineDelegation: false, // temporary
				}),
			);
		}
	}

	module.exports = { TasksPerformer };
});
