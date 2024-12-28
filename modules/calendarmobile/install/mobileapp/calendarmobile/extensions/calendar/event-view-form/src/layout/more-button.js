/**
 * @module calendar/event-view-form/layout/more-button
 */
jn.define('calendar/event-view-form/layout/more-button', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { Icon } = require('assets/icons');
	const { copyToClipboard } = require('utils/copy');
	const { withCurrentDomain } = require('utils/url');
	const { showToast } = require('toast');
	const { confirmDefaultAction, confirmDestructiveAction } = require('alert');

	const { MoreMenu, menuEditItemId, menuDeleteItemId, menuCopyUrlItemId } = require('calendar/event-view-form/more-menu');
	const { DateHelper, Moment } = require('calendar/date-helper');
	const { EventEditForm } = require('calendar/event-edit-form');
	const { SettingsManager } = require('calendar/data-managers/settings-manager');
	const { RecursionModeMenu } = require('calendar/layout/menu/recursion-mode');
	const { RecursionMode, EventPermissionActions, EventTypes } = require('calendar/enums');
	const { DeleteManager } = require('calendar/event-view-form/delete-manager');

	const menuId = 'calendar-event-view-form-more-menu';

	/**
	 * @class MoreButton
	 */
	class MoreButton
	{
		constructor(props)
		{
			this.props = props;
			this.menu = null;

			this.parentId = 0;
			this.permissions = {};
			this.recurrenceId = 0;
			this.recurrenceRuleDescription = '';
			this.dateFromTs = null;
			this.dateToTs = null;
			this.meetingStatus = null;
			this.eventType = null;
		}

		get eventId()
		{
			return this.props.eventId;
		}

		setEventParams(event)
		{
			this.parentId = event?.parentId;
			this.permissions = event?.permissions;
			this.recurrenceId = event?.recurrenceId;
			this.recurrenceRuleDescription = event?.recurrenceRuleDescription;
			this.dateFromTs = event?.dateFromTs;
			this.dateToTs = event?.dateToTs;
			this.isFullDay = event?.isFullDay;
			this.meetingStatus = event?.meetingStatus;
			this.eventType = event?.eventType;
		}

		getButton()
		{
			return {
				id: menuId,
				testId: menuId,
				type: Icon.MORE.getIconName(),
				callback: this.showMenu,
			};
		}

		showMenu = () => {
			this.menu = new MoreMenu({
				layoutWidget: this.props.layout,
				onItemSelected: this.onItemSelected,
				parentId: this.parentId,
				permissions: this.permissions,
				meetingStatus: this.meetingStatus,
			});

			this.menu.show();
		};

		onItemSelected = (item) => {
			switch (item.id)
			{
				case menuEditItemId:
					void this.openEditForm();
					break;
				case menuCopyUrlItemId:
					this.copyEventUrl();
					break;
				case menuDeleteItemId:
					this.deleteEvent();
					break;
				default:
					break;
			}
		};

		async openEditForm(recursionMode = null)
		{
			if (!this.isSameDate() || this.isFullDay)
			{
				this.showEditUnableInfo(Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_EVENT_EDIT_LONG_UNABLE'));

				return;
			}

			if (this.hasPassed())
			{
				this.showEditUnableInfo(Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_EVENT_EDIT_PAST_UNABLE'));

				return;
			}

			if (this.isRecurrence() && recursionMode === null)
			{
				this.showConfirmRecurrenceEdit();

				return;
			}

			void EventEditForm.open({
				eventId: this.eventId,
				dateFromTs: this.props.dateFromTs,
				ownerId: this.props.ownerId,
				calType: this.props.calType,
				parentLayout: this.props.layout,
				sectionId: SettingsManager.getMeetSectionId(),
				firstWeekday: SettingsManager.getFirstWeekday(),
				editAttendeesMode: this.isEditAttendeesMode(),
				recursionMode,
			});
		}

		showEditUnableInfo(message)
		{
			showToast({
				message,
				iconName: Icon.INFO_CIRCLE.getIconName(),
			});
		}

		showConfirmRecurrenceEdit()
		{
			confirmDefaultAction({
				title: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_EVENT_EDIT_RECURRENCE_CONFIRM_TITLE'),
				description: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_EVENT_EDIT_RECURRENCE_CONFIRM_DESCRIPTION'),
				actionButtonText: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_EDIT'),
				onAction: () => {
					void this.openEditForm(RecursionMode.THIS);
				},
			});
		}

		isEditAttendeesMode()
		{
			if ([EventTypes.SHARED, EventTypes.SHARED_CRM, EventTypes.SHARED_COLLAB].includes(this.eventType))
			{
				return true;
			}

			return !this.permissions[EventPermissionActions.EDIT]
				&& (
					this.permissions[EventPermissionActions.EDIT_ATTENDEES]
					|| this.permissions[EventPermissionActions.EDIT_LOCATION]
				)
			;
		}

		copyEventUrl()
		{
			const pathToCalendar = withCurrentDomain(SettingsManager.getPathToUserCalendar());
			const params = {
				EVENT_ID: this.props.eventId,
				EVENT_DATE: DateHelper.formatDate(new Date(this.props.dateFromTs)),
			};

			const eventUrl = this.addParams(pathToCalendar, params);

			copyToClipboard(eventUrl, Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_LINK_COPIED'));
		}

		addParams(url, params)
		{
			const paramsString = Object.entries(params)
				.map(([param, value]) => `${param}=${encodeURIComponent(value)}`)
				.join('&')
			;

			return `${url}?${paramsString}`;
		}

		deleteEvent()
		{
			if (this.wasEverRecursive())
			{
				this.showRecursionModeMenu();

				return;
			}

			this.confirmDeleteEvent(this.deleteSingleEvent);
		}

		confirmDeleteEvent(onDestruct)
		{
			confirmDestructiveAction({
				title: '',
				description: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_MORE_MENU_DELETE_CONFIRM'),
				onDestruct,
			});
		}

		deleteSingleEvent = () => {
			void DeleteManager.delete({
				eventId: this.eventId,
				parentId: this.parentId,
			});

			this.afterDeleteEvent();
		};

		deleteThisEvent = () => {
			void DeleteManager.deleteThis({
				eventId: this.eventId,
				parentId: this.parentId,
				excludeDate: DateHelper.formatDate(new Date(this.dateFromTs)),
				isRecurrence: this.isRecurrence(),
				recurrenceId: this.recurrenceId,
			});

			this.afterDeleteEvent();
		};

		deleteNextEvent = () => {
			const untilDateTs = this.dateFromTs - DateHelper.dayLength;

			void DeleteManager.deleteNext({
				eventId: this.eventId,
				parentId: this.parentId,
				untilDate: DateHelper.formatDate(new Date(untilDateTs)),
				untilDateTs,
			});

			this.afterDeleteEvent();
		};

		deleteAllEvent = () => {
			void DeleteManager.deleteAll({
				eventId: this.eventId,
				parentId: this.parentId,
			});

			this.afterDeleteEvent();
		};

		afterDeleteEvent()
		{
			this.props.layout.back();
		}

		isRecurrence()
		{
			return Type.isStringFilled(this.recurrenceRuleDescription);
		}

		wasEverRecursive()
		{
			return this.isRecurrence() || this.recurrenceId > 0;
		}

		isSameDate()
		{
			const minusSecond = this.isFullDay ? 1000 : 0;
			const eventFrom = new Date(this.dateFromTs);
			const eventTo = new Date(this.dateToTs - minusSecond);

			return DateHelper.getDayCode(eventFrom) === DateHelper.getDayCode(eventTo);
		}

		hasPassed()
		{
			const eventTo = new Moment(new Date(this.dateToTs));

			return eventTo.hasPassed;
		}

		showRecursionModeMenu()
		{
			this.recursionMenu = new RecursionModeMenu({
				layoutWidget: this.props.layout,
				onItemSelected: this.onRecursionMenuItemSelected,
			});

			this.recursionMenu.show();
		}

		onRecursionMenuItemSelected = (item) => {
			switch (item.id)
			{
				case RecursionMode.THIS:
					this.confirmDeleteEvent(this.deleteThisEvent);
					break;
				case RecursionMode.NEXT:
					this.confirmDeleteEvent(this.deleteNextEvent);
					break;
				case RecursionMode.ALL:
					this.confirmDeleteEvent(this.deleteAllEvent);
					break;
				default:
					break;
			}
		};
	}

	module.exports = { MoreButton };
});
