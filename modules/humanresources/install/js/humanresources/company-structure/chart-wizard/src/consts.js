import type { StepIdType } from './types';

export const SaveMode: Record<string> = Object.freeze({
	moveUsers: 'moveUsers',
	addUsers: 'addUsers',
});

export const StepIds: Record<StepIdType, StepIdType> = Object.freeze({
	entities: 'entities',
	department: 'department',
	employees: 'employees',
	bindChat: 'bindChat',
	settings: 'settings',
});

export const AuthorityTypes: Record<string> = {
	departmentHead: 'HEAD',
	departmentDeputy: 'DEPUTY_HEAD',
	teamHead: 'TEAM_HEAD',
	teamDeputy: 'TEAM_DEPUTY',
};
