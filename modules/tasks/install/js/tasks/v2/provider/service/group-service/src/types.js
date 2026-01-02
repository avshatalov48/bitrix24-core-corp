export type GroupDto = {
	id: number,
	name: string,
	image: string,
	type: GroupType,
	stages: StageDto[],
};

type GroupType = 'group' | 'project' | 'scrum' | 'collab';

export type StageDto = {
	id: number,
	title: string,
	color: string,
};

export type GroupInfo = {
	ownerId: number,
	ownerName: string,
	dateCreate: string,
	subjectTitle: string,
	numberOfMembers: number,
};
