import { Loc } from 'main.core';
import { MenuItemDesign } from 'ui.system.menu';

import { Messenger } from 'im.public';
import { Core } from 'im.v2.application.core';
import { ActionByRole, ChatType, RestMethod } from 'im.v2.const';
import { PermissionManager } from 'im.v2.lib.permission';
import { BaseMenu } from 'im.v2.lib.menu';
import { CallManager } from 'im.v2.lib.call';
import { runAction } from 'im.v2.lib.rest';
import { Feature, FeatureManager } from 'im.v2.lib.feature';
import { Analytics } from 'call.lib.analytics';

import { CallTypes } from 'call.const';

import type { ImModelChat, ImModelUser } from 'im.v2.model';
import type { MenuItemOptions, MenuOptions, MenuSectionOptions } from 'ui.system.menu';

const MenuSectionCode = Object.freeze({
	main: 'main',
	phone: 'phone',
});

export class CallMenu extends BaseMenu
{
	context: ImModelChat;

	static events = {
		onMenuItemClick: 'onMenuItemClick',
	};

	constructor()
	{
		super();

		this.id = 'bx-im-chat-header-call-menu';
	}

	getMenuOptions(): MenuOptions
	{
		return {
			...super.getMenuOptions(),
			className: this.getMenuClassName(),
			angle: true,
			offsetLeft: 4,
			offsetTop: 5,
		};
	}

	getMenuClassName(): String
	{
		return 'bx-im-messenger__scope bx-call-chat-header-call-button__scope';
	}

	getMenuItems(): MenuItemOptions | null[]
	{
		return [
			...this.groupItems([
				this.#getVideoCallItem(),
				this.#getAudioCallItem(),
				this.#getZoomItem(),
			], MenuSectionCode.main),
			...this.groupItems([
				this.#getPersonalPhoneItem(),
				this.#getWorkPhoneItem(),
				this.#getInnerPhoneItem(),
			], MenuSectionCode.phone),
		];
	}

	getMenuGroups(): MenuSectionOptions[]
	{
		return [
			{ code: MenuSectionCode.main },
			{ code: MenuSectionCode.phone },
		];
	}

	#getVideoCallItem(): MenuItemOptions
	{
		const isAvailable = this.#isCallAvailable(this.context.dialogId);

		return {
			title: Loc.getMessage('CALL_CONTENT_CHAT_HEADER_VIDEOCALL'),
			design: isAvailable ? MenuItemDesign.Default : MenuItemDesign.Disabled,
			onClick: () => {
				if (!isAvailable)
				{
					return;
				}

				this.#analyticsOnStartCallClick(CallTypes.video.id);

				CallTypes.video.start(this.context.dialogId);
				this.emit(CallMenu.events.onMenuItemClick, CallTypes.video);
				this.menuInstance.close();
			},
		};
	}

	#getAudioCallItem(): MenuItemOptions
	{
		const isAvailable = this.#isCallAvailable(this.context.dialogId);

		return {
			title: Loc.getMessage('CALL_CONTENT_CHAT_HEADER_CALL_MENU_AUDIO'),
			design: isAvailable ? MenuItemDesign.Default : MenuItemDesign.Disabled,
			onClick: () => {
				if (!isAvailable)
				{
					return;
				}

				this.#analyticsOnStartCallClick(CallTypes.audio.id);
				CallTypes.audio.start(this.context.dialogId);

				this.emit(CallMenu.events.onMenuItemClick, CallTypes.audio);
				this.menuInstance.close();
			},
		};
	}

	#analyticsOnStartCallClick(callType)
	{
		Analytics.getInstance().onContextMenuStartCallClick({
			context: this.context,
			callType,
		});
	}

	#getPersonalPhoneItem(): ?MenuItemOptions
	{
		if (!this.#isUser())
		{
			return null;
		}

		const { phones } = this.#getUser();
		if (!phones.personalMobile)
		{
			return null;
		}

		const title = Loc.getMessage('CALL_CONTENT_CHAT_HEADER_CALL_MENU_PERSONAL_PHONE');

		return {
			title,
			subtitle: phones.personalMobile,
			onClick: () => {
				void Messenger.startPhoneCall(phones.personalMobile);
				this.menuInstance.close();
			},
		};
	}

	#getWorkPhoneItem(): ?MenuItemOptions
	{
		if (!this.#isUser())
		{
			return null;
		}

		const { phones } = this.#getUser();
		if (!phones.workPhone)
		{
			return null;
		}

		return {
			title: Loc.getMessage('CALL_CONTENT_CHAT_HEADER_CALL_MENU_WORK_PHONE'),
			subtitle: phones.workPhone,
			onClick: () => {
				void Messenger.startPhoneCall(phones.workPhone);
				this.menuInstance.close();
			},
		};
	}

	#getInnerPhoneItem(): ?MenuItemOptions
	{
		if (!this.#isUser())
		{
			return null;
		}

		const { phones } = this.#getUser();
		if (!phones.innerPhone)
		{
			return null;
		}

		return {
			title: Loc.getMessage('CALL_CONTENT_CHAT_HEADER_CALL_MENU_INNER_PHONE_MSGVER_1'),
			subtitle: phones.innerPhone,
			onClick: () => {
				void Messenger.startPhoneCall(phones.innerPhone);
				this.menuInstance.close();
			},
		};
	}

	#getZoomItem(): ?MenuItemOptions
	{
		const isActive = FeatureManager.isFeatureAvailable(Feature.zoomActive);
		if (!isActive)
		{
			return null;
		}

		const isFeatureAvailable = FeatureManager.isFeatureAvailable(Feature.zoomAvailable);

		return {
			title: Loc.getMessage('CALL_CONTENT_CHAT_HEADER_CALL_MENU_ZOOM'),
			design: isFeatureAvailable ? MenuItemDesign.Default : MenuItemDesign.Disabled,
			onClick: () => {
				if (!isFeatureAvailable)
				{
					BX.UI.InfoHelper.show('limit_video_conference_zoom');

					return;
				}

				this.#requestCreateZoomConference(this.context.dialogId);
				this.menuInstance.close();
			},
		};
	}

	#isCallAvailable(dialogId: String): boolean
	{
		if (CallManager.getInstance().hasActiveCurrentCall(dialogId))
		{
			return true;
		}

		if (CallManager.getInstance().hasActiveAnotherCall())
		{
			return false;
		}

		const chatCanBeCalled = CallManager.getInstance().chatCanBeCalled(dialogId);
		const chatIsAllowedToCall = PermissionManager.getInstance().canPerformActionByRole(ActionByRole.call, dialogId);

		return chatCanBeCalled && chatIsAllowedToCall;
	}

	#getUser(): ?ImModelUser
	{
		if (!this.#isUser())
		{
			return null;
		}

		return Core.getStore().getters['users/get'](this.context.dialogId);
	}

	#isUser(): true
	{
		return this.context.type === ChatType.user;
	}

	#requestCreateZoomConference(dialogId: string)
	{
		runAction(RestMethod.imV2CallZoomCreate, { data: { dialogId } })
			.catch((errors) => {
				let errorText = Loc.getMessage('CALL_CONTENT_CHAT_HEADER_CALL_MENU_ZOOM_CREATE_ERROR');

				const notConnected = errors.some((error) => error.code === 'ZOOM_CONNECTED_ERROR');
				if (notConnected)
				{
					const userProfileUri = `/company/personal/user/${Core.getUserId()}/social_services/`;
					errorText = Loc.getMessage('CALL_CONTENT_CHAT_HEADER_CALL_MENU_ZOOM_CONNECT_ERROR')
						.replace('#HREF_START#', `<a href=${userProfileUri}>`)
						.replace('#HREF_END#', '</>');
				}

				BX.UI.Notification.Center.notify({
					content: errorText,
				});
			});
	}
}
