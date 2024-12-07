/**
 * @module tasks/utils
 */
jn.define('tasks/utils', (require, exports, module) => {
	const { DeadlinePeriod } = require('tasks/enum');

	/**
	 * @param {number|undefined} ts
	 * @param {array} stages
	 * @return {object}
	 */
	function getStageByDeadline(ts, stages = [])
	{
		const stageNoDeadline = stages.find((stage) => stage.statusId === DeadlinePeriod.PERIOD_NO_DEADLINE);
		const stageOverdue = stages.find((stage) => stage.statusId === DeadlinePeriod.PERIOD_OVERDUE);
		const stageOverTwoWeeks = stages.find((stage) => stage.statusId === DeadlinePeriod.PERIOD_OVER_TWO_WEEKS);
		const stagesByDeadline = stages
			.filter((stage) => Boolean(stage.rightBorder))
			.sort((a, b) => a.rightBorder - b.rightBorder);

		if (!ts)
		{
			return stageNoDeadline;
		}

		const now = Date.now();

		if (ts < now)
		{
			return stageOverdue;
		}

		const deadline = Math.round(ts / 1000);
		for (const stage of stagesByDeadline)
		{
			if (deadline <= stage.rightBorder)
			{
				return stage;
			}
		}

		return stageOverTwoWeeks;
	}

	module.exports = {
		getStageByDeadline,
	};
});
