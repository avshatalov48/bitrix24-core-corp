import { Loc } from 'main.core';
import { userType } from '../enum/user-type';

export const headOfDepartment = Object.freeze({
	id: 1,
	entityId: userType.divisionHead,
	title: Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CONTROL_HEAD_OF_DEPARTMENT'),
	tabs: 'users',
});
