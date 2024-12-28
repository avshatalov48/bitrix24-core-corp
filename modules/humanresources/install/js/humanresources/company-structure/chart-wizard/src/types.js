import { memberRoles } from 'humanresources.company-structure.api';

type WizardData = {
	shown: boolean;
	stepIndex: number;
	waiting: boolean;
	isValidStep: boolean;
};

type User = {
	id: number;
	avatar: ?string;
	name: string;
	role: string;
	url: string;
	workPosition: ?string;
};

type DepartmentData = {
	title: string;
	description: string;
	parentId: number;
	heads: Array<User>;
	employees: Array<User>;
	userCount: number;
};

type Step = {
	id: string;
	title: string;
};

type DepartmentUserIds = {
	[memberRoles.head]: number[],
	[memberRoles.deputyHead]: number[],
	[memberRoles.employee]: number[],
};

export type { WizardData, DepartmentData, Step, DepartmentUserIds };
