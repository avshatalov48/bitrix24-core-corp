import { RelationTasksDialog } from './relation-tasks-dialog';
import { subTasksMeta, relatedTasksMeta } from './meta';

export const subTasksDialog = new RelationTasksDialog(subTasksMeta);
export const relatedTasksDialog = new RelationTasksDialog(relatedTasksMeta);
export type { RelationTasksDialog };
