/**
 * @module bizproc/task/tasks-performer/rules/one-by-one-rule
 */
jn.define('bizproc/task/tasks-performer/rules/one-by-one-rule', (require, exports, module) => {
	const { Rule } = require('bizproc/task/tasks-performer/rules/rule');

	class OneByOneRule extends Rule
	{
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

			const { finishRule } = await this.showTask(task);
			if (finishRule === true)
			{
				return Promise.reject();
			}

			return this.showNextTask();
		}
	}

	module.exports = { OneByOneRule };
});
