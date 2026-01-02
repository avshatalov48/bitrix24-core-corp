/**
 * @module calendar/aha-moments-manager/sync-error
 */
jn.define('calendar/aha-moments-manager/sync-error', (require, exports, module) => {
	const { Loc } = require('loc');
	const { AhaMoment } = require('ui-system/popups/aha-moment');

	/**
	 * @class SyncError
	 */
	class SyncError
	{
		static show(onHide)
		{
			AhaMoment.show({
				onHide,
				targetRef: 'calendar-more',
				title: Loc.getMessage('M_CALENDAR_AHA_SYNC_ERROR_TITLE'),
				description: Loc.getMessage('M_CALENDAR_AHA_SYNC_ERROR_DESC'),
				testId: 'calendar-aha-moment-sync-error',
				fadeInDuration: 300,
				closeButton: false,
				disableHideByOutsideClick: false,
			});
		}
	}

	module.exports = { SyncError };
});
