import { Loc } from 'main.core';
import { TaskField } from 'tasks.v2.const';
import { Actions } from 'ui.icon-set.api.core';
import 'ui.icon-set.actions';

import { relatedTasksDialog } from 'tasks.v2.lib.relation-tasks-dialog';
import { relatedTasksService } from 'tasks.v2.provider.service.relation-service';
import type { RelationMeta } from 'tasks.v2.component.fields.relation-tasks';

export const relatedTasksMeta: RelationMeta = Object.freeze({
	id: TaskField.RelatedTasks,
	icon: Actions.CONNECTION,
	idsField: 'relatedTaskIds',
	containsField: 'containsRelatedTasks',
	title: Loc.getMessage('TASKS_V2_RELATED_TASKS_TITLE'),
	chipTitle: Loc.getMessage('TASKS_V2_RELATED_TASKS_TITLE_CHIP'),
	hint: Loc.getMessage('TASKS_V2_RELATED_TASKS_ADD'),
	countLoc: 'TASKS_V2_RELATED_TASKS_TITLE_COUNT',
	service: relatedTasksService,
	dialog: relatedTasksDialog,
});
