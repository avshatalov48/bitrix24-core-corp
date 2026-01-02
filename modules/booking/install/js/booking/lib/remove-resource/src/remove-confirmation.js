import { Loc } from 'main.core';
import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';

import { CloseIconSize } from 'main.popup';
import type { ResourceModel } from 'booking.model.resources';

export class RemoveConfirmation
{
	static confirmDelete(): Promise<boolean>
	{
		return new Promise((resolve) => {
			const messageBox = MessageBox.create({
				title: Loc.getMessage('BOOKING_RESOURCE_CONFIRM_DELETE_TITLE'),
				yesCaption: Loc.getMessage('BOOKING_RESOURCE_CONFIRM_DELETE_YES'),
				modal: true,
				buttons: MessageBoxButtons.YES_CANCEL,
				popupOptions: {
					className: 'booking-resource-remove-confirmation',
					minHeight: 135,
					closeByEsc: true,
					closeIcon: true,
					closeIconSize: CloseIconSize.LARGE,
				},
				useAirDesign: true,
				onYes: async (box) => {
					box.close();
					resolve(true);
				},
				onCancel: (box) => {
					box.close();
					resolve(false);
				},
			});

			messageBox.show();
		});
	}

	static confirmMoveFutureBooking(): Promise<boolean>
	{
		return new Promise((resolve) => {
			const messageBox = MessageBox.create({
				title: Loc.getMessage('BOOKING_RESOURCE_CONFIRM_MOVE_FUTURE_BOOKINGS_TITLE'),
				message: Loc.getMessage('BOOKING_RESOURCE_CONFIRM_MOVE_FUTURE_BOOKINGS_TEXT'),
				yesCaption: Loc.getMessage('BOOKING_RESOURCE_CONFIRM_MOVE_FUTURE_BOOKINGS_YES'),
				noCaption: Loc.getMessage('BOOKING_RESOURCE_CONFIRM_MOVE_FUTURE_BOOKINGS_NO'),
				buttons: MessageBoxButtons.YES_NO,
				popupOptions: {
					className: 'booking-resource-remove-confirmation booking-resource-remove-confirmation-move-bookings --air',
					minHeight: 176,
					minWidth: 418,
					closeByEsc: true,
					closeIcon: true,
					closeIconSize: CloseIconSize.LARGE,
				},
				useAirDesign: true,
				onYes: async (box) => {
					box.close();
					resolve(true);
				},
				onNo: (box) => {
					box.close();
					resolve(false);
				},
			});

			messageBox.show();
		});
	}

	static confirmAfterMoveFutureBooking(resource: ResourceModel): Promise<boolean>
	{
		return new Promise((resolve) => {
			const messageBox = MessageBox.create({
				title: Loc.getMessage('BOOKING_RESOURCE_CONFIRM_AFTER_MOVE_FUTURE_BOOKINGS_TITLE'),
				message: Loc.getMessage('BOOKING_RESOURCE_CONFIRM_AFTER_MOVE_FUTURE_BOOKINGS_TEXT'),
				yesCaption: Loc.getMessage('BOOKING_RESOURCE_CONFIRM_AFTER_MOVE_FUTURE_BOOKINGS_YES'),
				noCaption: Loc.getMessage('BOOKING_RESOURCE_CONFIRM_AFTER_MOVE_FUTURE_BOOKINGS_NO'),
				buttons: MessageBoxButtons.YES_NO,
				popupOptions: {
					id: `booking-resource-remove-confirmation-${resource.id}`,
					className: 'booking-resource-remove-confirmation booking-resource-remove-confirmation-after-clear --air',
					minHeight: 176,
					closeByEsc: true,
					closeIcon: true,
					closeIconSize: CloseIconSize.LARGE,
				},
				useAirDesign: true,
				onYes: async (box) => {
					box.close();
					resolve(true);
				},
				onNo: (box) => {
					box.close();
					resolve(false);
				},
			});

			messageBox.show();
		});
	}
}
