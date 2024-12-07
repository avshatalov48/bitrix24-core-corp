import { Communication } from 'crm.client-selector';

declare type Config = {
	channels: Channel[],
	communications: Communication[],
	contactCenterUrl: string,
	marketplaceUrl: string,
	currentChannelId: string,
	currentSender: string,
	openLineItems: OpenLinesList,
	region?: string,
	services: {[key: string]: string},
}

declare type Channel = {
	canUse: boolean,
	'default': boolean,
	fromList: FromPhone[],
	id: string,
	name: string,
	shortName: string,
	toList: Object[],
}

declare type FromPhone = {
	'default': boolean,
	description: ?string,
	id: string,
	name: string,
}

declare type Phone = {
	id: string,
	type: string,
	typeLabel: string,
	value: string,
	valueFormatted: string,
}

declare type OpenLinesList = {
	[key: string]: OpenLineItem;
}

declare type OpenLineItem = {
	name: string,
	selected: boolean,
	url: string,
}

declare type Entity = {
	entityId: number,
	entityTypeId: number,
}

declare type ChatService = {
	id: string,
	connectorId: string,
	connectLabel: string,
	inviteLabel: string,
	soonLabel?: string,
	region?: ChatServiceRegion,
	title: string,
	commonClass: string,
	iconClass: string,
	iconColor: string,
	checkServiceId?: string,
}

declare type ChatServiceRegion = {
	region: 'ru' | '!ru',
}

export {
	Channel,
	ChatService,
	Config,
	Entity,
	FromPhone,
	OpenLinesList,
	OpenLineItem,
};
