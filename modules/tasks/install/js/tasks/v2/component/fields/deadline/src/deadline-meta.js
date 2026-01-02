import { Loc } from 'main.core';
import { TaskField } from 'tasks.v2.const';

export const deadlineMeta = Object.freeze({
	id: TaskField.Deadline,
	title: Loc.getMessage('TASKS_V2_DEADLINE_TITLE'),
});
