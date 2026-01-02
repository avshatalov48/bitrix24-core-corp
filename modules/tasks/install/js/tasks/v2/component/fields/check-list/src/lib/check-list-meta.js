import { Loc } from 'main.core';
import { TaskField } from 'tasks.v2.const';

export const checkListMeta = Object.freeze({
	id: TaskField.CheckList,
	title: Loc.getMessage('TASKS_V2_CHECK_LIST_TITLE'),
});
