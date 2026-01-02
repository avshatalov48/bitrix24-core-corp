import { Loc } from 'main.core';
import { TaskField } from 'tasks.v2.const';

export const tagsMeta = Object.freeze({
	id: TaskField.Tags,
	title: Loc.getMessage('TASKS_V2_TAGS_TITLE'),
});
