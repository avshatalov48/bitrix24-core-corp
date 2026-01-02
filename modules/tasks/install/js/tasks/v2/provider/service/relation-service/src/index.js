import { SubTasksService } from './sub-tasks-service';
import { RelationService } from './relation-service';
import { relatedTasksMeta, subTasksMeta } from './meta';

export const subTasksService = new SubTasksService(subTasksMeta);
export const relatedTasksService = new RelationService(relatedTasksMeta);
export type { RelationService };
