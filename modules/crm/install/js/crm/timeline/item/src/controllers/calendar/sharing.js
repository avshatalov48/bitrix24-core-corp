import {ajax as Ajax} from 'main.core';
import {ActionParams, Base} from '../base';
import ConfigurableItem from 'crm.timeline.item';
import SharingSlotsList from "../../components/content-blocks/calendar/sharing-slots-list";
import {Router} from 'crm.router';
import { DialogQr } from 'calendar.sharing.interface';


export class Sharing extends Base
{
	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{
		const { action, actionType, actionData } = actionParams;

		if (actionType !== 'jsEvent')
		{
			return;
		}

		if (action === 'Activity:CalendarSharing:OpenCalendarEvent')
		{
			this.#openCalendarEvent(item, actionData);
		}

		if (action === 'Activity:CalendarSharing:StartVideoconference')
		{
			this.#startVideoconference(item, actionData);
		}

		if (action === 'CalendarSharingLinkCopied:OpenPublicPageInNewTab')
		{
			window.open(actionData.url)
		}

		if (action === 'CalendarSharingInvitationSent:ShowQr')
		{
			const dialogQr = new DialogQr({
				sharingUrl: actionData.url,
				context: "crm",
			});
			dialogQr.show();
		}
	}

	#openCalendarEvent(item, actionData): void
	{
		return Router.Instance.openCalendarEventSlider(actionData.eventId, actionData.isSharing);
	}

	async #startVideoconference(item ,actionData): Promise<void>
	{
		let response;

		try
		{
			response = await Ajax.runAction('crm.timeline.calendar.sharing.getConferenceChatId', {
				data: {
					eventId: actionData.eventId,
					ownerId: actionData.ownerId,
					ownerTypeId: actionData.ownerTypeId,
				}
			})
		}
		catch (responseWithError)
		{
			console.error(responseWithError);
			return;
		}

		const chatId = response.data.chatId;
		if (top.window.BXIM && chatId)
		{
			top.window.BXIM.openMessenger('chat' + parseInt(chatId));
		}
	}

	getContentBlockComponents(Item: ConfigurableItem): Object
	{
		return {
			SharingSlotsList,
		};
	}

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return item.getType() === 'CalendarSharingInvitationSent'
			|| item.getType() === 'CalendarSharing'
			|| item.getType() === 'Activity:CalendarSharing'
			|| item.getType() === 'CalendarSharingLinkCopied'
		;
	}
}