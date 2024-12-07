/**
 * @module bizproc/task/tasks-performer/notifier
 */
jn.define('bizproc/task/tasks-performer/notifier', (require, exports, module) => {
	const { Loc } = require('loc');
	const { showToast, Position } = require('toast');

	class Notifier
	{
		/**
		 * @param {Object} props
		 * @param {[]} props.tasks
		 */
		constructor(props = {})
		{
			this.amount = Array.isArray(props.tasks) ? props.tasks.length : 0;
			this.completedAmount = 0;
		}

		get notCompletedAmount()
		{
			return this.amount - this.completedAmount;
		}

		increaseCompletedTasks(count)
		{
			this.completedAmount += count;

			return this;
		}

		decreaseAmountTasks(count)
		{
			this.amount -= count;

			return this;
		}

		/**
		 * @param {Number} count
		 */
		showTasksCompletedToast(count)
		{
			const message = (
				count > 1
					? Loc.getMessage(
						'MBP_TASK_TASKS_PERFORMER_NOTIFIER_COMPLETED_TEXT',
						{ '#COUNT#': Number.parseInt(count, 10) },
					)
					: Loc.getMessage('MBP_TASK_TASKS_PERFORMER_NOTIFIER_COMPLETED_TEXT_SINGLE')
			);

			this.showToast(`${message}\r\n${this.getRemainingToCompleteMessage()}`);
		}

		/**
		 * @param {Number} count
		 */
		showTasksCanceledToast(count)
		{
			const message = (
				count > 1
					? Loc.getMessage(
						'MBP_TASK_TASKS_PERFORMER_NOTIFIER_CANCEL_TEXT',
						{ '#COUNT#': Number.parseInt(count, 10) },
					)
					: Loc.getMessage('MBP_TASK_TASKS_PERFORMER_NOTIFIER_CANCEL_TEXT_SINGLE')
			);

			this.showToast(`${message}\r\n${this.getRemainingToCompleteMessage()}`);
		}

		/**
		 * @param {Number} count
		 */
		showTasksDelegatedToast(count)
		{
			const message = (
				count > 1
					? Loc.getMessage(
						'MBP_TASK_TASKS_PERFORMER_NOTIFIER_DELEGATED_TEXT',
						{ '#COUNT#': Number.parseInt(count, 10) },
					)
					: Loc.getMessage('MBP_TASK_TASKS_PERFORMER_NOTIFIER_DELEGATED_TEXT_SINGLE')
			);

			this.showToast(`${message}\r\n${this.getRemainingToCompleteMessage()}`);
		}

		showEarlyFinishToast()
		{
			this.showToast(Loc.getMessage('MBP_TASK_TASKS_PERFORMER_NOTIFIER_EARLY_FINISH_TEXT'));
		}

		showToast(message)
		{
			showToast({ message, position: Position.TOP, time: 2 });
		}

		getRemainingToCompleteMessage()
		{
			return Loc.getMessage(
				'MBP_TASK_TASKS_PERFORMER_NOTIFIER_REMAINING_TO_COMPLETE',
				{ '#AMOUNT#': this.notCompletedAmount },
			);
		}
	}

	module.exports = { Notifier };
});
