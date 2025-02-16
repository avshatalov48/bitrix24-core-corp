export type ClientsState = {
	providerModuleId: string | null,
	contactCollection: { [id: number]: ClientModel },
	companyCollection: { [id: number]: ClientModel },
};

export type ClientModel = {
	id: number,
	name: string,
	image: string,
	type: {
		module: string,
		code: string,
	},
	contactId: number | null,
	phones: string[],
	emails: string[],
	isReturning: boolean,
};

export type ClientData = {
	id: number,
	type: {
		module: string,
		code: string,
	},
};
