import { memberRoles, teamMemberRoles } from 'humanresources.company-structure.api';
import { type DepartmentData } from 'humanresources.company-structure.chart-store';
import { NodeSettingsTypes } from 'humanresources.company-structure.utils';

type WizardData = {
	stepIndex: number;
	waiting: boolean;
	isValidStep: boolean;
	departmentData: {
		...DepartmentData,
		settings: {
			[NodeSettingsTypes.businessProcAuthority]: Set,
			[NodeSettingsTypes.reportsAuthority]: Set,
		},
	};
	removedUsers: UserData[];
	employeesIds: number[];
	shouldErrorHighlight: boolean;
	visibleSteps: Array<Step.id>;
	saveMode: 'moveUsers' | 'addUsers';
};

export type StepIdType = 'entities' | 'department' | 'employees' | 'bindChat' | 'settings';

type Step = {
	id: StepIdType;
	title?: string;
	breadcrumbsTitle?: string;
	breadcrumbsTitleDepartment: string;
	breadcrumbsTitleTeam: string;
	hasBreadcrumbs: boolean;
	hasTreePreview: boolean;
	isEditPermitted?: boolean;
	isPermitted?: boolean;
	dataTestIdPart: string,
};

type DepartmentUserIds = {
	[memberRoles.head]: number[],
	[memberRoles.deputyHead]: number[],
	[memberRoles.employee]: number[],
} | {
	[teamMemberRoles.head]: number[],
	[teamMemberRoles.deputyHead]: number[],
	[teamMemberRoles.employee]: number[],
};

export type { WizardData, Step, DepartmentUserIds };
