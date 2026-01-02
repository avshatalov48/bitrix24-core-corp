import { RelationMeta } from './types';

export const subTasksMeta: RelationMeta = Object.freeze({
	idsField: 'subTaskIds',
	containsField: 'containsSubTasks',
	relationToField: 'parentId',
	controller: 'Task.Relation.Child',
});

export const relatedTasksMeta: RelationMeta = Object.freeze({
	idsField: 'relatedTaskIds',
	containsField: 'containsRelatedTasks',
	relationToField: 'relatedToTaskId',
	controller: 'Task.Relation.Related',
});
