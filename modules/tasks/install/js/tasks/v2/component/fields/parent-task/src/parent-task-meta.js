import { Loc } from 'main.core';
import { TaskField } from 'tasks.v2.const';

export const parentTaskMeta = Object.freeze({
	id: TaskField.Parent,
	title: Loc.getMessage('TASKS_V2_PARENT_TASK_TITLE'),
});
