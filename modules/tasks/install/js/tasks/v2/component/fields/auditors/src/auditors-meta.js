import { Loc } from 'main.core';
import { TaskField } from 'tasks.v2.const';

export const auditorsMeta = Object.freeze({
	id: TaskField.Auditors,
	title: Loc.getMessage('TASKS_V2_AUDITORS_TITLE'),
});
