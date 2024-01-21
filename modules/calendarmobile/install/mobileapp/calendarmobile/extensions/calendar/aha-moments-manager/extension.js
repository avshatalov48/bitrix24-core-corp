/**
 * @module calendar/aha-moments-manager
 */
jn.define('calendar/aha-moments-manager', (require, exports, module) => {
	const { SyncCalendar } = require('calendar/aha-moments-manager/sync-calendar');
	const { SyncError } = require('calendar/aha-moments-manager/sync-error');

	const availableAhaMoments = {
		syncCalendar: SyncCalendar,
		syncError: SyncError,
	};

	const getAhaMoment = (ahaMomentName) => {
		if (!availableAhaMoments[ahaMomentName])
		{
			console.error(`Unknown aha: ${ahaMomentName}`);

			return null;
		}

		return availableAhaMoments[ahaMomentName];
	};

	module.exports = { getAhaMoment };
});
