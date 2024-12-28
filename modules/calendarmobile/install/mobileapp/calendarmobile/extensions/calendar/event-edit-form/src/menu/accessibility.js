/**
 * @module calendar/event-edit-form/menu/accessibility
 */
jn.define('calendar/event-edit-form/menu/accessibility', (require, exports, module) => {
	const { Loc } = require('loc');
	const { BaseMenu, baseSectionType } = require('calendar/base-menu');
	const { EventAccessibility } = require('calendar/enums');

	/**
	 * @class AccessibilityMenu
	 */
	class AccessibilityMenu extends BaseMenu
	{
		getItems()
		{
			const accessibilityList = [EventAccessibility.BUSY, EventAccessibility.FREE, EventAccessibility.ABSENT];

			return accessibilityList.map((accessibility) => ({
				id: String(accessibility),
				testId: `calendar-event-edit-form-accessibility-menu-${accessibility}`,
				sectionCode: baseSectionType,
				title: Loc.getMessage(`M_CALENDAR_EVENT_EDIT_FORM_ACCESSIBILITY_${accessibility.toUpperCase()}`),
				checked: accessibility === this.props.accessibility,
			}));
		}
	}

	module.exports = { AccessibilityMenu };
});
