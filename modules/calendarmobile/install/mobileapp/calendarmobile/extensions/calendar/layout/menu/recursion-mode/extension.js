/**
 * @module calendar/layout/menu/recursion-mode
 */
jn.define('calendar/layout/menu/recursion-mode', (require, exports, module) => {
	const { Loc } = require('loc');
	const { BaseMenu, baseSectionType } = require('calendar/base-menu');

	class RecursionModeMenu extends BaseMenu
	{
		getItems()
		{
			const recursionModeList = ['this', 'next', 'all'];

			return recursionModeList.map((recursionMode) => ({
				id: recursionMode,
				test: `calendar-recursion-mode-${recursionMode}`,
				sectionCode: baseSectionType,
				title: Loc.getMessage(`M_CALENDAR_RECURSION_MODE_${recursionMode.toUpperCase()}`),
			}));
		}
	}

	module.exports = { RecursionModeMenu };
});
