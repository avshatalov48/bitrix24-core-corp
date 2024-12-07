/**
 * @module bizproc/task/tasks-performer/rules/sequential-task-rule
 */
jn.define('bizproc/task/tasks-performer/rules/sequential-task-rule', (require, exports, module) => {
	const { Rule } = require('bizproc/task/tasks-performer/rules/rule');
	const { TaskHashRule } = require('bizproc/task/tasks-performer/rules/task-hash-rule');

	class SequentialTaskRule extends Rule
	{
		/**
		 * @param {[]} tasks
		 * @param {Object} targetTask
		 */
		static isApplicable(tasks, targetTask)
		{
			return true;
		}

		async start()
		{
			return this.showNextTask();
		}

		async showNextTask()
		{
			const task = this.tasks.shift();
			if (!task)
			{
				return Promise.resolve();
			}

			const { doTaskRequest, delegateRequest, finishRule } = await this.showTask(task);

			if (finishRule === true)
			{
				return Promise.reject();
			}

			if (!doTaskRequest && !delegateRequest)
			{
				return this.showNextTask();
			}

			if (TaskHashRule.isApplicable(this.tasks, task))
			{
				const { hashTasks, otherTasks } = this.separateTasksByHash(task.hash);

				this.tasks = otherTasks;

				const taskHashRule = new TaskHashRule({
					doTaskRequest,
					delegateRequest,
					layout: this.layout,
					tasks: hashTasks,
					onTasksCancel: this.props.onTasksCancel,
					onTaskNotFoundError: this.props.onTaskNotFoundError,
					onTasksCompleted: this.onTasksCompleted.bind(this),
					onTasksDelegated: this.onTasksDelegated.bind(this),
					generateExitButton: this.generateExitButton,
				});

				let doFinish = false;
				await (
					taskHashRule.start()
						.catch(() => {
							doFinish = true;
						})
				);

				if (doFinish)
				{
					return Promise.reject();
				}
			}

			return this.showNextTask();
		}

		separateTasksByHash(hash)
		{
			const hashTasks = [];
			const otherTasks = [];
			for (const task of this.tasks)
			{
				if (task.hash === hash)
				{
					hashTasks.push(task);
				}
				else
				{
					otherTasks.push(task);
				}
			}

			return { hashTasks, otherTasks };
		}
	}

	module.exports = { SequentialTaskRule };
});
