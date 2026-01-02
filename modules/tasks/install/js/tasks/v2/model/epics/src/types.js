export type EpicsModelState = {
	collection: { [epicId: string]: EpicModel },
};

export type EpicModel = {
	id: number,
	title: string,
	color: string,
};
