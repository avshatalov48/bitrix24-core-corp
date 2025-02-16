export type ClientDto = {
	id: number,
	type: {
		module: string,
		code: string,
	},
	data: {
		name: string,
		image: string,
		phones: string[],
		emails: string[],
	},
	isReturning: boolean,
};
