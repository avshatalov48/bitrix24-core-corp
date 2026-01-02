import { Loc } from 'main.core';
import { MessageBox } from 'ui.dialogs.messagebox';
import { ServiceLocator } from '../service-locator';

declare type PhoneCommunicationOptions = {
	phone: string,
	ownerTypeId: number,
	ownerId: number,
	entityTypeId: number,
	entityId: number,
};

declare type EmailCommunicationOptions = {
	email: string,
	ownerTypeId: number,
	ownerId: number,
	entityTypeId: number,
	entityId: number,
	activityEditorContainer: HTMLElement,
};

declare type IMCommunicationOptions = {
	dialogId: string,
};

export class CommunicationService
{
	communicateByPhone(options: PhoneCommunicationOptions): void
	{
		if (!window.top.BXIM)
		{
			MessageBox.alert(Loc.getMessage('CRM_MINI_CARD_COMMUNICATION_PHONE_NOT_SUPPORTS'));

			return;
		}

		const phoneToParams = {
			ENTITY_TYPE_NAME: BX.CrmEntityType.resolveName(options.entityTypeId),
			ENTITY_ID: options.entityId,
		};

		if (
			options.ownerTypeId !== options.entityTypeId
			&& options.ownerId !== options.entityId
		)
		{
			phoneToParams.BINDINGS = {
				OWNER_TYPE_NAME: BX.CrmEntityType.resolveName(options.ownerTypeId),
				OWNER_TYPE_ID: options.ownerTypeId,
			};
		}

		window.top.BXIM.phoneTo(options.phone, phoneToParams);
	}

	communicateByEmail(options: EmailCommunicationOptions): Promise<void>
	{
		const error = () => {
			MessageBox.alert(Loc.getMessage('CRM_MINI_CARD_COMMUNICATION_EMAIL_NOT_SUPPORTS'));
		};

		return new Promise((resolve, reject) => {
			ServiceLocator
				.getInstance()
				.getActivityEditorService()
				.loadActivityEditor(
					options.ownerTypeId,
					options.ownerId,
					options.activityEditorContainer,
				)
				.then((editor: ?BX.CrmActivityEditor) => {
					if (!editor)
					{
						error();

						reject();
					}

					const isEmailAdded = editor.addEmail({
						ownerID: options.ownerId,
						ownerType: BX.CrmEntityType.resolveName(options.ownerTypeId),
						communicationsLoaded: true,
						communications: [{
							type: 'EMAIL',
							entityType: BX.CrmEntityType.resolveName(options.entityTypeId),
							entityId: options.entityId,
							value: options.email,
						}],
					});

					if (!isEmailAdded)
					{
						window.location.href = `mailto:${options.email}`;
					}

					resolve();
				})
				.catch(() => {
					reject();
				});
		});
	}

	communicateByIM(options: IMCommunicationOptions): void
	{
		if (!window.top.BXIM)
		{
			MessageBox.alert(Loc.getMessage('CRM_MINI_CARD_COMMUNICATION_IM_NOT_SUPPORTS'));

			return;
		}

		window.top.BXIM.openMessengerSlider(options.dialogId, {
			RECENT: 'N',
			MENU: 'N',
		});
	}
}
