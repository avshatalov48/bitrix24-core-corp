/**
 * @module tasks/utils/stages
 */
jn.define('tasks/utils/stages', (require, exports, module) => {
	const { DeadlinePeriod } = require('tasks/enum');

	/**
	 * @param {number|undefined} ts
	 * @param {array} stages
	 * @return {object}
	 */
	function getStageByDeadline(ts, stages = [])
	{
		const stageNoDeadline = stages.find((stage) => stage.statusId === DeadlinePeriod.PERIOD_NO_DEADLINE);
		if (!ts)
		{
			return stageNoDeadline;
		}

		const now = Date.now();
		const stageOverdue = stages.find((stage) => stage.statusId === DeadlinePeriod.PERIOD_OVERDUE);
		if (ts < now)
		{
			return stageOverdue;
		}

		const deadline = Math.round(ts / 1000);
		const stagesByDeadline = (
			stages
				.filter((stage) => Boolean(stage.rightBorder))
				.sort((a, b) => a.rightBorder - b.rightBorder)
		);
		for (const stage of stagesByDeadline)
		{
			if (deadline <= stage.rightBorder)
			{
				return stage;
			}
		}

		return stages.find((stage) => stage.statusId === DeadlinePeriod.PERIOD_OVER_TWO_WEEKS);
	}

	module.exports = {
		getStageByDeadline,
	};
});
