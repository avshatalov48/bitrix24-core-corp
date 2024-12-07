import { ajax as Ajax, Dom, Loc, Tag, Text, Type } from 'main.core';
import { MenuManager, PopupManager, Menu } from 'main.popup';
import { ActionParams, Base } from '../base';
import ConfigurableItem from 'crm.timeline.item';
import SharingSlotsList from '../../components/content-blocks/calendar/sharing-slots-list';
import { Router } from 'crm.router';
import { DialogQr } from 'calendar.sharing.interface';
import { UI } from 'ui.notification';

export class Sharing extends Base
{
	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{
		const { action, actionType, actionData } = actionParams;

		if (actionType !== 'jsEvent')
		{
			return;
		}

		if (action === 'CalendarSharingInvitationSent:ShowMembers' || action === 'Activity:CalendarSharing:ShowMembers')
		{
			this.#openMembersPopup(item, Object.values(actionData.members));
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
			window.open(actionData.url);
		}

		if (action === 'CalendarSharingInvitationSent:ShowQr')
		{
			const dialogQr = new DialogQr({
				sharingUrl: actionData.url,
				context: 'crm',
			});
			dialogQr.show();
		}

		if (action === 'Activity:CalendarSharing:CopyLink')
		{
			const isSuccess = BX.clipboard.copy(actionData.url);
			if (isSuccess)
			{
				UI.Notification.Center.notify({
					content: Loc.getMessage('CRM_TIMELINE_ITEM_LINK_IS_COPIED_SHORT'),
					autoHideDelay: 5000,
				});
			}
		}
	}

	#openCalendarEvent(item, actionData): void
	{
		return Router.Instance.openCalendarEventSlider(actionData.eventId, actionData.isSharing);
	}

	async #startVideoconference(item, actionData): Promise<void>
	{
		let response = null;

		try
		{
			response = await Ajax.runAction('crm.timeline.calendar.sharing.getConferenceChatId', {
				data: {
					eventId: actionData.eventId,
					ownerId: actionData.ownerId,
					ownerTypeId: actionData.ownerTypeId,
				},
			});
		}
		catch (responseWithError)
		{
			console.error(responseWithError);

			return;
		}

		const chatId = response.data.chatId;
		if (top.window.BXIM && chatId)
		{
			top.window.BXIM.openMessenger(`chat${parseInt(chatId, 10)}`);
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

	#openMembersPopup(item: ConfigurableItem, members: Array): void
	{
		const moreButton = item.getContainer().querySelector('[data-id="sharing_member_more_button"]');
		if (!moreButton)
		{
			return;
		}

		const existingPopup = PopupManager.getPopupById(`sharing_members_popup_${item.getId()}`);
		if (existingPopup)
		{
			return;
		}

		const menu: Menu = MenuManager.create({
			id: `sharing_members_popup_${item.getId()}`,
			bindElement: moreButton,
			cacheable: false,
			className: 'crm-timeline-sharing-members-popup',
			maxHeight: 500,
			maxWidth: 300,
			animation: 'fading-slide',
			closeByEsc: true,
			items: members.map((member) => ({
				html: this.#renderMemberMenuItem(member),
				onclick: () => menu.close(),
			})),
		});

		menu.show();
	}

	#renderMemberMenuItem(member)
	{
		const { root, icon } = Tag.render`
			<a class="crm-timeline-sharing-members-popup-item" href="${member.SHOW_URL}" target="_blank">
				<div class="ui-icon ui-icon-common-user crm-timeline-sharing-members-popup-item-image">
					<i ref="icon"></i>
				</div>
				<span class="crm-timeline-sharing-members-popup-item-title">
					${Text.encode(member.FORMATTED_NAME)}
				</span>
			</a>
		`;

		if (Type.isStringFilled(member.PHOTO_URL))
		{
			Dom.style(icon, 'background-image', `url('${encodeURI(Text.encode(member.PHOTO_URL))}')`);
		}

		return root;
	}
}
