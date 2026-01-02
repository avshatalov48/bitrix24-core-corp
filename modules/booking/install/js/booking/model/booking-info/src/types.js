export type BookingInfoState = BookingInfoModel;

export type BookingInfoModel = {
	id: number;
	resources: ResourceInfo[];
	services: ServiceInfo[];
	client: ClientInfo;
	note: string;
}

export type ResourceInfo = {
	id: number;
	type: string;
	name: string;
	permissions: Permissions;
}

export type ServiceInfo = {
	id: number;
	name: string;
	permissions: Permissions;
}

export type ClientInfo = {
	id: number;
	name: string;
	phones: string[];
	image: ?string;
	type: 'CONTACT';
	permissions: Permissions;
}

type Permissions = {
	read?: boolean;
}
