import { memberRoles } from 'humanresources.company-structure.api';
import type { UserData } from 'humanresources.company-structure.utils';

type WizardData = {
	stepIndex: number;
	waiting: boolean;
	isValidStep: boolean;
	departmentData: DepartmentData;
	removedUsers: UserData[];
	employeesIds: number[];
	shouldErrorHighlight: boolean;
	visibleSteps: Array<Step.id>;
	saveMode: 'moveUsers' | 'addUsers';
};

type DepartmentData = {
	id: number;
	name: string;
	description: string;
	parentId: number;
	heads: Array<UserData>;
	employees: Array<UserData>;
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
