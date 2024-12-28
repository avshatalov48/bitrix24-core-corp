/**
 * @module calendar/event-edit-form/menu/importance
 */
jn.define('calendar/event-edit-form/menu/importance', (require, exports, module) => {
	const { Loc } = require('loc');
	const { BaseMenu, baseSectionType } = require('calendar/base-menu');
	const { EventImportance } = require('calendar/enums');

	/**
	 * @class ImportanceMenu
	 */
	class ImportanceMenu extends BaseMenu
	{
		getItems()
		{
			const importanceList = [EventImportance.NORMAL, EventImportance.HIGH];

			return importanceList.map((importance) => ({
				id: String(importance),
				testId: `calendar-event-edit-form-importance-menu-${importance}`,
				sectionCode: baseSectionType,
				title: Loc.getMessage(`M_CALENDAR_EVENT_EDIT_FORM_IMPORTANCE_${importance.toUpperCase()}`),
				checked: importance === this.props.importance,
			}));
		}
	}

	module.exports = { ImportanceMenu };
});
