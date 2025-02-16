import type { ClientModel } from 'booking.model.clients';

export type Item = {
	id: number,
	module: string,
	type: string,
	title: string,
	attributes: {
		phone: Communication[],
		email: Communication[],
	},
};

type Communication = {
	value: string,
};

export type CurrentClient = {
	contact: ?ClientModel,
	company: ?ClientModel,
};
