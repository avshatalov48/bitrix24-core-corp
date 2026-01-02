export type UsersModelState = {
	collection: { [userId: string]: UserModel },
};

export const UserTypes = Object.freeze({
	Employee: 'employee',
	Collaber: 'collaber',
	Extranet: 'extranet',
});

export type UserModel = {
	id: number,
	name: string,
	image: string,
	type: string,
};
