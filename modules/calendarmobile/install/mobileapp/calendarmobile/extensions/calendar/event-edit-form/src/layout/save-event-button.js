/**
 * @module calendar/event-edit-form/layout/save-event-button
 */
jn.define('calendar/event-edit-form/layout/save-event-button', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { Color } = require('tokens');
	const { Haptics } = require('haptics');
	const { showToast } = require('toast');
	const { confirmDefaultAction } = require('alert');
	const { Button, ButtonSize, Icon } = require('ui-system/form/buttons/button');

	const { State } = require('calendar/event-edit-form/state');
	const { RecursionMode } = require('calendar/enums');
	const { LocationAccessibilityManager } = require('calendar/event-edit-form/data-managers/location-accessibility-manager');

	const { dispatch } = require('statemanager/redux/store');
	const { saveEvent, saveThisEvent } = require('calendar/statemanager/redux/slices/events');

	/**
	 * @class SaveEventButton
	 */
	class SaveEventButton extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				buttonPressed: false,
			};
		}

		render()
		{
			return Button({
				testId: 'calendar-event-edit-form-save-event-button',
				text: this.getText(),
				backgroundColor: Color.accentMainPrimary,
				size: ButtonSize.L,
				stretched: true,
				disabled: this.state.buttonPressed,
				onClick: this.onClickHandler,
			});
		}

		getText()
		{
			if (State.isEditForm())
			{
				return Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_SAVE_EVENT');
			}

			return Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_CREATE_EVENT');
		}

		onClickHandler = () => {
			void this.saveEvent();
		};

		async saveEvent()
		{
			const eventId = await this.sendRequest();
			if (eventId)
			{
				this.onAfterEventSave(eventId);
			}
		}

		onAfterEventSave(eventId)
		{
			BX.postComponentEvent('Calendar.EventEditForm::onAfterEventSave', [{
				eventId,
				uuid: State.uuid,
				createChatId: State.createChatId,
				fields: State.getFields(),
			}]);

			Haptics.notifySuccess();

			this.props.layout.close();

			showToast({
				iconName: Icon.CHECK.getIconName(),
				message: State.isEditForm()
					? Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_EDIT_TOAST')
					: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_CREATE_TOAST')
				,
			});
		}

		async sendRequest()
		{
			this.setState({ buttonPressed: true });

			try
			{
				const result = await this.dispatchSave();

				return result?.data?.entryId;
			}
			catch (errorResponse)
			{
				Haptics.notifyFailure();

				this.setState({ buttonPressed: false });

				if (Type.isPlainObject(errorResponse.data?.busyUsersList))
				{
					this.handleBusyUsersError(Object.values(errorResponse.data.busyUsersList));
				}
				else
				{
					this.handleSaveError(errorResponse.errors[0]);
				}

				return false;
			}
		}

		dispatchSave()
		{
			const fields = State.getFields();

			if (fields.rec_edit_mode === RecursionMode.THIS)
			{
				return new Promise((resolve, reject) => {
					dispatch(
						saveThisEvent({
							eventId: State.id,
							data: fields,
							reduxFields: State.getReduxFields(),
							resolve,
							reject,
						}),
					);
				});
			}

			return new Promise((resolve, reject) => {
				dispatch(
					saveEvent({
						eventId: State.id,
						data: fields,
						reduxFields: State.getReduxFields(),
						resolve,
						reject,
					}),
				);
			});
		}

		handleBusyUsersError(busyUsers)
		{
			const confirmAction = () => {
				State.addExcludedUsers(busyUsers);
				void this.saveEvent();
			};

			const confirmClose = () => {
				State.clearExcludedUsers();
			};

			confirmDefaultAction({
				cancelButtonText: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_BUSY_USERS_BACK2EDIT'),
				actionButtonText: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_BUSY_USERS_EXCLUDE_PLURAL'),
				title: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_BUSY_USERS_TITLE'),
				description: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_BUSY_USERS_PLURAL', {
					'#USER_LIST#': busyUsers.map((user) => user.DISPLAY_NAME).join(','),
				}),
				onAction: confirmAction,
				onClose: confirmClose,
			});
		}

		handleSaveError(error)
		{
			if (error.code === 'edit_entry_location_busy')
			{
				LocationAccessibilityManager.deleteLoadedAccessibility({ date: State.selectedDate });
			}

			showToast({
				message: error.message,
				backgroundColor: Color.accentMainAlert.toHex(),
				iconName: Icon.CROSS.getIconName(),
			});
		}
	}

	module.exports = { SaveEventButton };
});
