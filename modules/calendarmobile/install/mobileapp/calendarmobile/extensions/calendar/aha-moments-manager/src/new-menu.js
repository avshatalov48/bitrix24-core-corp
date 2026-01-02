/**
 * @module calendar/aha-moments-manager/new-menu
 */
jn.define('calendar/aha-moments-manager/new-menu', (require, exports, module) => {
	const { Loc } = require('loc');
	const { AhaMoment } = require('ui-system/popups/aha-moment');

	/**
	 * @class NewMenu
	 */
	class NewMenu
	{
		static show()
		{
			AhaMoment.show({
				targetRef: 'calendar-more',
				title: Loc.getMessage('M_CALENDAR_AHA_NEW_MENU_TITLE'),
				description: Loc.getMessage('M_CALENDAR_AHA_NEW_MENU_DESC'),
				testId: 'calendar-aha-moment-new-menu',
				fadeInDuration: 300,
				closeButton: false,
				disableHideByOutsideClick: false,
			});
		}
	}

	module.exports = { NewMenu };
});
