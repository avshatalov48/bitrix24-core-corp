import { Loc } from 'main.core';
import { TaskField } from 'tasks.v2.const';
import { relatedTasksService, subTasksService } from 'tasks.v2.provider.service.relation-service';
import type { RelationMeta } from './types';

export const subTasksMeta: RelationMeta = Object.freeze({
	id: TaskField.SubTasks,
	idsField: 'subTaskIds',
	relationToField: 'parentId',
	footerText: Loc.getMessage('TASKS_V2_SUB_TASKS_CREATE'),
	service: subTasksService,
});

export const relatedTasksMeta: RelationMeta = Object.freeze({
	id: TaskField.RelatedTasks,
	idsField: 'relatedTaskIds',
	relationToField: 'relatedToTaskId',
	footerText: Loc.getMessage('TASKS_V2_RELATED_TASKS_CREATE'),
	service: relatedTasksService,
});
