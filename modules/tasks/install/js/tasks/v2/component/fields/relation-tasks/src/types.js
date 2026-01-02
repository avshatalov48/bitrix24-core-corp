import type { RelationTasksDialog } from 'tasks.v2.lib.relation-tasks-dialog';
import type { RelationService } from 'tasks.v2.provider.service.relation-service';

export type RelationMeta = {
	id: string,
	icon: string,
	idsField: string,
	containsField: string,
	title: string,
	chipTitle: string,
	hint: string,
	countLoc: string,
	service: RelationService,
	dialog: RelationTasksDialog,
};
