export type GroupsModelState = {
	collection: { [groupId: string]: GroupModel },
};

export type GroupModel = {
	id: number,
	name: string,
	image: string,
	url: string,
	type: GroupType,
	stagesIds: number[],
};

type GroupType = 'group' | 'project' | 'scrum' | 'collab';
