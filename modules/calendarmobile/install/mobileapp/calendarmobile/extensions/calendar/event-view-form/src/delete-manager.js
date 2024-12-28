/**
 * @module calendar/event-view-form/delete-manager
 */
jn.define('calendar/event-view-form/delete-manager', (require, exports, module) => {
	const { showToast } = require('toast');
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { Color } = require('tokens');
	const { Icon } = require('ui-system/blocks/icon');
	const { Haptics } = require('haptics');

	const { RecursionMode } = require('calendar/enums');

	const { dispatch } = require('statemanager/redux/store');
	const { deleteEvent, deleteThisEvent, deleteNextEvent } = require('calendar/statemanager/redux/slices/events');

	/**
	 * @class DeleteManager
	 */
	class DeleteManager
	{
		async delete({ eventId, parentId, recursionMode = false })
		{
			try
			{
				await this.#dispatchedDelete(eventId, parentId, recursionMode);

				this.handleDeleteSuccess();

				return true;
			}
			catch (errorResponse)
			{
				this.handleDeleteError(errorResponse);

				return false;
			}
		}

		#dispatchedDelete(eventId, parentId, recursionMode)
		{
			const requestUid = `event-delete-${eventId}${Date.now()}`;

			const data = {
				entryId: parentId,
				requestUid,
				recursionMode,
			};

			return new Promise((resolve, reject) => {
				dispatch(
					deleteEvent({
						data,
						eventId,
						resolve,
						reject,
					}),
				);
			});
		}

		async deleteThis({ eventId, parentId, excludeDate, isRecurrence, recurrenceId })
		{
			if (isRecurrence)
			{
				try
				{
					await this.#dispatchedDeleteThis(eventId, parentId, excludeDate);

					this.handleDeleteSuccess();

					return true;
				}
				catch (errorResponse)
				{
					this.handleDeleteError(errorResponse);

					return false;
				}
			}
			else if (recurrenceId)
			{
				const recursionMode = RecursionMode.THIS;

				return this.delete({ eventId, parentId, recursionMode });
			}

			return false;
		}

		#dispatchedDeleteThis(eventId, parentId, excludeDate)
		{
			const data = {
				excludeDate,
				entryId: parentId,
				recursionMode: RecursionMode.THIS,
			};

			return new Promise((resolve, reject) => {
				dispatch(
					deleteThisEvent({
						data,
						eventId,
						excludeDate,
						resolve,
						reject,
					}),
				);
			});
		}

		async deleteNext({ eventId, parentId, untilDate, untilDateTs })
		{
			try
			{
				await this.#dispatchedDeleteNext(eventId, parentId, untilDate, untilDateTs);

				this.handleDeleteSuccess();

				return true;
			}
			catch (errorResponse)
			{
				this.handleDeleteError(errorResponse);

				return false;
			}
		}

		#dispatchedDeleteNext(eventId, parentId, untilDate, untilDateTs)
		{
			const data = {
				untilDate,
				untilDateTs,
				entryId: parentId,
				recursionMode: RecursionMode.NEXT,
			};

			return new Promise((resolve, reject) => {
				dispatch(
					deleteNextEvent({
						data,
						eventId,
						untilDate,
						resolve,
						reject,
					}),
				);
			});
		}

		deleteAll({ eventId, parentId })
		{
			const recursionMode = RecursionMode.ALL;

			return this.delete({ eventId, parentId, recursionMode });
		}

		handleDeleteSuccess()
		{
			Haptics.notifySuccess();

			showToast({
				message: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_EVENT_DELETED'),
				iconName: Icon.TRASHCAN.getIconName(),
			});
		}

		handleDeleteError(errorResponse)
		{
			Haptics.notifyFailure();

			if (Type.isArrayFilled(errorResponse?.errors))
			{
				const error = errorResponse.errors[0];

				showToast({
					message: error.message,
					backgroundColor: Color.accentMainAlert.toHex(),
					iconName: Icon.CROSS.getIconName(),
				});
			}
		}
	}

	module.exports = { DeleteManager: new DeleteManager() };
});
