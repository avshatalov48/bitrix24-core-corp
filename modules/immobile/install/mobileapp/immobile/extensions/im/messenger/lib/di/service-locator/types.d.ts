declare type MessengerLocatorServices = {
	'core': CoreApplication,
	'emitter': JNEventEmitter,
	'messenger-init-service'?: MessengerInitService,
	'tab-counters': BaseTabCounters,

	'refresher'?: Refresher,
	'connection-service'?: ConnectionService,
	'sync-service'?: SyncService,
	'read-service'?: ReadMessageService,
	'sending-service'?: SendingService,
	'queue-service'?: QueueService,
	'messenger-header-controller'?: MessengerHeaderController,
	'dialog-manager'?: DialogManager,
	'push-manager'?: PushManager,
	'recent-manager'?: RecentManager,
	'quick-recent'?: QuickRecentLoader,
	'dialog-creator'?: DialogCreator,
	'navigation-controller'?: NavigationController,
}

export interface IServiceLocator<T>
{
	add<U extends keyof T>(serviceName: U, service: T[U]): IServiceLocator<T>;
	get<U extends keyof T>(serviceName: U): T[U] | null;
	has<U extends keyof T>(serviceName: U): T[U] | null;
}

declare type MessengerLocator = IServiceLocator<MessengerLocatorServices>;
