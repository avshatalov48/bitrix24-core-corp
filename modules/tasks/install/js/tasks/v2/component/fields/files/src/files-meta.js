import { Loc } from 'main.core';
import { TaskField } from 'tasks.v2.const';

export const filesMeta = Object.freeze({
	id: TaskField.Files,
	title: Loc.getMessage('TASKS_V2_FILES_TITLE'),
});
