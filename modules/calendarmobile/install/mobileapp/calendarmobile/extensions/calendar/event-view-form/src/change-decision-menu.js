/**
 * @module calendar/event-view-form/change-decision-menu
 */

jn.define('calendar/event-view-form/change-decision-menu', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color } = require('tokens');
	const { Icon } = require('ui-system/blocks/icon');

	const { EventMeetingStatus } = require('calendar/enums');
	const { BaseMenu, baseSectionType } = require('calendar/base-menu');

	const acceptedItemProps = {
		title: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_DECLINE'),
		iconName: Icon.CROSS.getIconName(),
	};

	const declinedItemProps = {
		title: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_ACCEPT'),
		iconName: Icon.CHECK.getIconName(),
	};

	class ChangeDecisionMenu extends BaseMenu
	{
		getItems()
		{
			const color = this.props.meetingStatus === EventMeetingStatus.ATTENDED
				? Color.accentMainAlert.toHex()
				: Color.accentMainPrimary.toHex()
			;

			return [
				{
					id: 'calendar-event-view-form-change-decision-menu-item',
					testId: 'calendar-event-view-form-change-decision-menu-item',
					sectionCode: baseSectionType,
					...(this.props.meetingStatus === EventMeetingStatus.ATTENDED ? acceptedItemProps : {}),
					...(this.props.meetingStatus === EventMeetingStatus.DECLINED ? declinedItemProps : {}),
					styles: {
						title: {
							font: { color },
						},
						icon: { color },
					},
				},
			];
		}

		getSections()
		{
			return [
				{
					id: baseSectionType,
					title: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_CHANGE_DECISION'),
					styles: {
						title: {
							font: {
								color: Color.base4.toHex(),
							},
						},
					},
				},
			];
		}
	}

	module.exports = { ChangeDecisionMenu };
});
