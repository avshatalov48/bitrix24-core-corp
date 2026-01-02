export type BookingInfoResponse = {
	id: number;
	resources: BookingInfoResource[];
	services: BookingInfoService[];
	client: BookingInfoClient;
	note: ?string;
}

export type BookingInfoResource = {
	id: number;
	type: string;
	name: string;
	permissions: Permissions;
}

export type BookingInfoService = {
	id: number;
	name: string;
	permissions: Permissions;
}

export type BookingInfoClient = {
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
