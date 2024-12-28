/**
 * @module calendar/event-edit-form/menu/boolean
 */
jn.define('calendar/event-edit-form/menu/boolean', (require, exports, module) => {
	const { Loc } = require('loc');
	const { BaseMenu, baseSectionType } = require('calendar/base-menu');
	const { BooleanParams } = require('calendar/enums');

	/**
	 * @class BooleanMenu
	 */
	class BooleanMenu extends BaseMenu
	{
		getItems()
		{
			return [BooleanParams.YES, BooleanParams.NO].map((item) => ({
				id: String(item),
				testId: `calendar-event-edit-form-boolean-menu-${item}`,
				sectionCode: baseSectionType,
				title: Loc.getMessage(`M_CALENDAR_EVENT_EDIT_FORM_BOOLEAN_${item}`),
				checked: item === this.props.selected,
			}));
		}
	}

	module.exports = { BooleanMenu };
});
