import {MessengerCoreStore } from "../../../../core/types/store";

declare type DialogLocatorServices = {
	'disk-service': DiskService,
	'mention-manager': MentionManager,
	'message-service': MessageService
	'reply-manager': ReplyManager,
	'store': MessengerCoreStore,
	'view': DialogView,
}

declare interface IServiceLocator<T>
{
	add<U extends keyof T>(serviceName: U, service: T[U]): IServiceLocator<T>;
	get<U extends keyof T>(serviceName: U): T[U] | null;
}