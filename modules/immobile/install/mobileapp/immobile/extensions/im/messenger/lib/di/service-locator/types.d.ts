declare type MessengerLocatorServices = {
	'core': CoreApplication,
	'emitter': JNEventEmitter,
	'messenger-init-service': MessengerInitService,
}

export interface IServiceLocator<T>
{
	add<U extends keyof T>(serviceName: U, service: T[U]): IServiceLocator<T>;
	get<U extends keyof T>(serviceName: U): T[U] | null;
	has<U extends keyof T>(serviceName: U): T[U] | null;
}