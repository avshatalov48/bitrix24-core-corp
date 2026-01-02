/**
 * @module calendar/sync-page/settings/menu
 */
jn.define('calendar/sync-page/settings/menu', (require, exports, module) => {
	const { Loc } = require('loc');
	const { BaseMenu, baseSectionType } = require('calendar/base-menu');

	/**
	 * @class SyncSettingsMenu
	 */
	class SyncSettingsMenu extends BaseMenu
	{
		getItems()
		{
			return [
				{
					id: 'disable_connection',
					testId: 'disable_connection',
					sectionCode: baseSectionType,
					title: Loc.getMessage('M_CALENDAR_SYNC_SETTINGS_MENU_DISABLE_CONNECTION'),
				},
			];
		}
	}

	module.exports = { SyncSettingsMenu };
});
