import { Loc } from 'main.core';
import { TaskField } from 'tasks.v2.const';

export const crmMeta = Object.freeze({
	id: TaskField.Crm,
	title: Loc.getMessage('TASKS_V2_CRM_TITLE'),
});
