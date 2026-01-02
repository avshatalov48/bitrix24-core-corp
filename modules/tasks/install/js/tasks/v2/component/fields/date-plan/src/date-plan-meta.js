import { Loc } from 'main.core';
import { TaskField } from 'tasks.v2.const';

export const datePlanMeta = Object.freeze({
	id: TaskField.DatePlan,
	title: Loc.getMessage('TASKS_V2_DATE_PLAN_TITLE'),
});
