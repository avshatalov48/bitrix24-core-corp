declare type IntranetDepartmentReduxModel = {
	accessCode: string;
	active: boolean;
	createdBy: number;
	description: string | null;
	globalActive: boolean;
	id: number;
	name: string;
	parentId: number;
	sort: number;
	structureId: number;
	type: string;
	employeeCount: number;
	headIds: array<number>;
};
