import {MessengerCoreStore } from "../../../../core/types/store";
import {DialogEventEmitter} from "../../types/dialog";

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
}

declare interface IServiceLocator<T>
{
	add<U extends keyof T>(serviceName: U, service: T[U]): IServiceLocator<T>;
	get<U extends keyof T>(serviceName: U): T[U] | null;
}