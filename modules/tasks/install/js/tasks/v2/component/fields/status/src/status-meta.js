import { Loc } from 'main.core';
import { TaskField } from 'tasks.v2.const';

export const statusMeta = Object.freeze({
	id: TaskField.Status,
	title: Loc.getMessage('TASKS_V2_STATUS_TITLE'),
});
