import { Loc } from 'main.core';
import { TaskField } from 'tasks.v2.const';

export const flowMeta = Object.freeze({
	id: TaskField.Flow,
	title: Loc.getMessage('TASKS_V2_FLOW_TITLE'),
});
