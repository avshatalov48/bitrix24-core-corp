/**
 * @module bizproc/task/tasks-performer/rules/inline-task-rule
 */
jn.define('bizproc/task/tasks-performer/rules/inline-task-rule', (require, exports, module) => {
	const { clone } = require('utils/object');
	const { useCallback } = require('utils/function');

	const { DefaultButton } = require('bizproc/task/task-constants');
	const { TaskButtons } = require('bizproc/task/buttons');
	const { Rule } = require('bizproc/task/tasks-performer/rules/rule');

	class InlineTaskRule extends Rule
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

			const activity = tasks[0].activity;
			for (const task of tasks)
			{
				if (!task.isInline || task.activity !== activity)
				{
					return false;
				}
			}

			return true;
		}

		constructor(props)
		{
			super(props);

			this.onTaskButtonClick = this.onTaskButtonClick.bind(this);
		}

		renderEntryPoint()
		{
			return new TaskButtons({
				testId: 'MBP_TASKS_PERFORMER_INLINE_TASK_RULE',
				shouldUseEvents: false,
				task: this.taskToShow,
				isInline: false,
				onTaskButtonClick: useCallback(this.onTaskButtonClick),
			});
		}

		calculateEntryPointButtons()
		{
			return this.taskToShow?.buttons.length || 0;
		}

		get taskToShow()
		{
			const firstTask = this.tasks ? clone(this.tasks[0]) : null;

			if (firstTask)
			{
				firstTask.buttons = firstTask.buttons ? clone(firstTask.buttons) : [];
				if (firstTask.buttons.length === 2)
				{
					firstTask.buttons = [DefaultButton.APPROVE, DefaultButton.NON_APPROVE];
				}
				else if (firstTask.buttons.length === 1)
				{
					firstTask.buttons = [DefaultButton.REVIEW];
				}
			}

			return firstTask;
		}

		onTaskButtonClick({ taskRequest })
		{
			this.doTaskCollection(this.tasks, taskRequest)
				.then((response) => {})
				.catch((response) => {})
			;

			this.onFinishRule();
		}
	}

	module.exports = { InlineTaskRule };
});
