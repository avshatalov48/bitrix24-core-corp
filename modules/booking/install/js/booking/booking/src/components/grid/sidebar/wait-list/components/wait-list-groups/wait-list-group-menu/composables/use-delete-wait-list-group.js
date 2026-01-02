import { Loc } from 'main.core';
import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';

import { waitListService } from 'booking.provider.service.wait-list-service';

export const useDeleteWaitListGroup = () => {
	const confirmDelete = async (): Promise<boolean> => {
		return new Promise((resolve): boolean => {
			const messageBox = MessageBox.create({
				message: Loc.getMessage('BOOKING_BOOKING_WAIT_LIST_GROUP_CONFIRM_DELETE'),
				yesCaption: Loc.getMessage('BOOKING_BOOKING_WAIT_LIST_GROUP_CONFIRM_DELETE_YES'),
				modal: true,
				buttons: MessageBoxButtons.YES_CANCEL,
				onYes: () => {
					messageBox.close();
					resolve(true);
				},
				onCancel: () => {
					messageBox.close();
					resolve(false);
				},
			});

			messageBox.show();
		});
	};

	const deleteWaitListGroup = async (waitListItemsIds: number[]): Promise<void> => {
		// eslint-disable-next-line @bitrix24/bitrix24-rules/no-native-dialogs
		if (await confirmDelete())
		{
			await waitListService.deleteList(waitListItemsIds);
		}
	};

	return {
		deleteWaitListGroup,
	};
};
