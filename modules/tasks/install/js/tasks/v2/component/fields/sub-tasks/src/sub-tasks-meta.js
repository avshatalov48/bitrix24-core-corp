import { Loc } from 'main.core';
import { Outline } from 'ui.icon-set.api.core';
import 'ui.icon-set.outline';

import { TaskField } from 'tasks.v2.const';
import { subTasksDialog } from 'tasks.v2.lib.relation-tasks-dialog';
import { subTasksService } from 'tasks.v2.provider.service.relation-service';
import type { RelationMeta } from 'tasks.v2.component.fields.relation-tasks';

export const subTasksMeta: RelationMeta = Object.freeze({
	id: TaskField.SubTasks,
	icon: Outline.RELATED_TASKS,
	idsField: 'subTaskIds',
	containsField: 'containsSubTasks',
	title: Loc.getMessage('TASKS_V2_SUB_TASKS_TITLE'),
	chipTitle: Loc.getMessage('TASKS_V2_SUB_TASKS_TITLE_CHIP'),
	hint: Loc.getMessage('TASKS_V2_SUB_TASKS_ADD'),
	countLoc: 'TASKS_V2_SUB_TASKS_TITLE_COUNT',
	service: subTasksService,
	dialog: subTasksDialog,
});
