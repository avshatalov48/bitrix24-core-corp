/**
 * @module bizproc/task/tasks-performer/rules/task-hash-rule
 */
jn.define('bizproc/task/tasks-performer/rules/task-hash-rule', (require, exports, module) => {
	const { Loc } = require('loc');

	const { Rule } = require('bizproc/task/tasks-performer/rules/rule');
	const { OneByOneRule } = require('bizproc/task/tasks-performer/rules/one-by-one-rule');
	const { SimilarTasksInformer, TaskListInformer } = require('bizproc/task/tasks-performer/informers');

	class TaskHashRule extends Rule
	{
		/**
		 * @param {[]} tasks
		 * @param {Object} targetTask
		 */
		static isApplicable(tasks, targetTask)
		{
			if (tasks.length <= 0)
			{
				return false;
			}

			const targetHash = targetTask.hash;
			for (const task of tasks)
			{
				if (task.hash === targetHash)
				{
					return true;
				}
			}

			return false;
		}

		constructor(props)
		{
			super(props);

			this.delegateRequest = props.delegateRequest;
			this.doTaskRequest = props.doTaskRequest;
		}

		async start()
		{
			const firstTask = this.tasks[0] ?? null;
			if (!firstTask)
			{
				return Promise.resolve();
			}

			const { applyToAll, seeDetails, doOneByOne, cancel } = await SimilarTasksInformer.open(
				{
					typeName: firstTask.typeName,
					count: this.tasks.length,
					generateExitButton: this.generateExitButton,
				},
				this.layout,
			);

			if (cancel === true)
			{
				return Promise.reject();
			}

			if (applyToAll === true)
			{
				this.applyDecision(this.tasks);

				return Promise.resolve();
			}

			if (doOneByOne === true || (!applyToAll && !seeDetails && !doOneByOne))
			{
				return this.applyOneByOneRule(this.tasks);
			}

			if (seeDetails === true)
			{
				return this.openDetailsWidget();
			}

			return Promise.resolve();
		}

		async openDetailsWidget()
		{
			const { applyToAllTasks, oneByOneTasks, cancel } = await TaskListInformer.open(
				{
					tasks: this.tasks,
					title: Loc.getMessage('MBP_TASK_TASKS_PERFORMER_RULES_ONE_TYPE_TASKS_TITLE'),
					onTasksCompleted: this.onTasksCompleted.bind(this),
					onTasksDelegated: this.onTasksDelegated.bind(this),
					onTaskNotFoundError: this.onTaskNotFoundError.bind(this),
					generateExitButton: this.generateExitButton,
				},
				this.layout,
			);

			if (cancel === true)
			{
				return Promise.reject();
			}

			if (Array.isArray(applyToAllTasks) && applyToAllTasks.length > 0)
			{
				this.applyDecision(applyToAllTasks);
			}

			if (Array.isArray(oneByOneTasks) && oneByOneTasks.length > 0)
			{
				return this.applyOneByOneRule(oneByOneTasks);
			}

			return Promise.resolve();
		}

		applyDecision(tasks)
		{
			if (this.delegateRequest)
			{
				this.delegateTasks(tasks, this.delegateRequest).then(() => {}).catch(() => {});
			}
			else if (this.doTaskRequest)
			{
				this.doTaskCollection(tasks, this.doTaskRequest).then(() => {}).catch(() => {});
			}
		}

		applyOneByOneRule(tasks)
		{
			const oneByOneRule = new OneByOneRule({
				layout: this.layout,
				tasks,
				onTasksCancel: this.onTasksCancel.bind(this),
				onTasksCompleted: this.onTasksCompleted.bind(this),
				onTasksDelegated: this.onTasksDelegated.bind(this),
				onTaskNotFoundError: this.onTaskNotFoundError.bind(this),
				generateExitButton: this.generateExitButton,
			});

			return oneByOneRule.start();
		}
	}

	module.exports = { TaskHashRule };
});
