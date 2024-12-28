import {DialogId} from "../../../types/common";
import {DialoguesModelState} from "../../../model/types/dialogues";
import {MessengerCoreStore } from "../../../../core/types/store";
import {IServiceLocator} from "../../../lib/di/service-locator/types";
import { ForwardMessageIds} from "../lib/reply-manager/types/reply-manager";

declare type DialogOpenOptions = {
	dialogId: string,
	messageId?: string | number,
	withMessageHighlight?: boolean,
	dialogTitleParams?: DialogTitleParams,
	forwardMessageIds?: ForwardMessageIds,
	chatType?: string,
	userCode?: string, // for openlines dialog only
	fallbackUrl?: string, // for openlines dialog only

	/**
	 * the context of opening a chat
	 * @see OpenDialogContextType
	 */
	context: string,
}

declare type DialogTitleParams = {
	name?: string,
	description?: string,
	avatar?: string,
	color?: string,
	chatType?: DialogTitleParamsChatType,
}

declare type DialogTitleParamsChatType = 'lines' | 'open'

declare type DialogEvents = {
	chatLoad: [DialoguesModelState],
	beforeFirstPageRenderFromServer: [DialoguesModelState],
	afterFirstPageRenderFromServer: {},
}

declare interface IDialogEmitter
{
	on<T extends keyof DialogEvents>(eventName: T, handler: (...args: DialogEvents[T]) => void): this;
	emit<T extends keyof DialogEvents>(eventName: T,...args: DialogEvents[T]): Promise<void>;
}

declare type DialogLocatorServices = {
	'context-manager': ContextManager,
	'chat-service': ChatService,
	'disk-service': DiskService,
	'mention-manager': MentionManager,
	'message-renderer': MessageRenderer,
	'message-service': MessageService,
	'reply-manager': ReplyManager,
	'store': MessengerCoreStore,
	'view': DialogView,
	'emitter': DialogEmitter,
	'dialogId': DialogId,
}

declare type DialogLocator = IServiceLocator<DialogLocatorServices>;
