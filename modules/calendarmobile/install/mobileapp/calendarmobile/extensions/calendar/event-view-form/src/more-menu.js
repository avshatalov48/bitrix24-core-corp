/**
 * @module calendar/event-view-form/more-menu
 */
jn.define('calendar/event-view-form/more-menu', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color } = require('tokens');
	const { Icon } = require('assets/icons');
	const { BaseMenu, baseSectionType } = require('calendar/base-menu');
	const { EventPermissionActions, EventMeetingStatus } = require('calendar/enums');

	const menuEditItemId = 'calendar-event-view-form-more-menu-edit';
	const menuCopyUrlItemId = 'calendar-event-view-form-more-menu-copy-url';
	const menuDeleteItemId = 'calendar-event-view-form-more-menu-delete';

	class MoreMenu extends BaseMenu
	{
		getItems()
		{
			const items = [];

			if (this.canEditEvent())
			{
				items.push(this.getEditItem());
			}

			items.push(this.getCopyUrlItem());

			if (this.canDeleteEvent())
			{
				items.push(this.getDeleteItem());
			}

			return items;
		}

		getEditItem()
		{
			return {
				id: menuEditItemId,
				testId: menuEditItemId,
				sectionCode: baseSectionType,
				title: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_MORE_MENU_EDIT'),
				iconName: Icon.EDIT.getIconName(),
			};
		}

		getCopyUrlItem()
		{
			return {
				id: menuCopyUrlItemId,
				testId: menuCopyUrlItemId,
				sectionCode: baseSectionType,
				title: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_MORE_MENU_COPY_URL'),
				iconName: Icon.LINK.getIconName(),
			};
		}

		getDeleteItem()
		{
			const color = Color.accentMainAlert.toHex();

			return {
				id: menuDeleteItemId,
				testId: menuDeleteItemId,
				sectionCode: baseSectionType,
				title: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_MORE_MENU_DELETE'),
				iconName: Icon.TRASHCAN.getIconName(),
				styles: {
					title: {
						font: { color },
					},
					icon: { color },
				},
			};
		}

		canEditEvent()
		{
			if (this.props.permissions?.[EventPermissionActions.EDIT])
			{
				return true;
			}

			return this.props.meetingStatus === EventMeetingStatus.ATTENDED
				&& (
					this.props.permissions?.[EventPermissionActions.EDIT_ATTENDEES]
					|| this.props.permissions?.[EventPermissionActions.EDIT_LOCATION]
				)
			;
		}

		canDeleteEvent()
		{
			return this.props.parentId > 0 && this.props.permissions?.[EventPermissionActions.DELETE];
		}
	}

	module.exports = {
		MoreMenu,
		menuCopyUrlItemId,
		menuEditItemId,
		menuDeleteItemId,
	};
});
