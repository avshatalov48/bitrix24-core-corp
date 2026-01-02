import { Type } from 'main.core';

import { CheckListMappers } from 'tasks.v2.provider.service.check-list-service';
import type { TaskModel } from 'tasks.v2.model.tasks';
import type { CheckListModel } from 'tasks.v2.model.check-list';
import type { TagDto, TaskDto, TaskSliderData } from './types';

export function mapModelToDto(task: TaskModel): TaskDto
{
	return {
		id: task.id,
		title: prepareValue(task.title),
		description: prepareValue(task.description, mapDescription(task.description)),
		creator: prepareValue(task.creatorId, { id: task.creatorId }),
		createdTs: prepareValue(task.createdTs, Math.floor(task.createdTs / 1000)),
		responsible: prepareValue(task.responsibleId, { id: task.responsibleId }),
		deadlineTs: prepareValue(task.deadlineTs, Math.floor(task.deadlineTs / 1000)),
		needsControl: prepareValue(task.needsControl),
		startPlanTs: prepareValue(task.startPlanTs, Math.floor(task.startPlanTs / 1000)),
		endPlanTs: prepareValue(task.endPlanTs, Math.floor(task.endPlanTs / 1000)),
		fileIds: prepareValue(task.fileIds),
		checklist: prepareValue(task.checklist),
		parent: prepareValue(task.parentId, { id: task.parentId }),
		dependsOn: prepareValue(task.relatedTaskIds),
		group: prepareValue(task.groupId, { id: task.groupId }),
		stage: prepareValue(task.stageId, { id: task.stageId }),
		flow: prepareValue(task.flowId, { id: task.flowId }),
		priority: prepareValue(task.isImportant, task.isImportant ? 'high' : 'low'),
		status: prepareValue(task.status),
		statusChangedTs: prepareValue(task.statusChangedTs, Math.floor(task.statusChangedTs / 1000)),
		accomplices: prepareValue(task.accomplicesIds, task.accomplicesIds?.map((id) => ({ id }))),
		auditors: prepareValue(task.auditorsIds, task.auditorsIds?.map((id) => ({ id }))),
		tags: prepareValue(task.tags, mapTags(task.tags ?? [])),
		chatId: prepareValue(task.chatId),
		crmItemIds: prepareValue(task.crmItemIds),
		allowsChangeDeadline: prepareValue(task.allowsChangeDeadline),
		allowsChangeDatePlan: prepareValue(task.allowsChangeDatePlan),
		matchesWorkTime: prepareValue(task.matchesWorkTime),
		matchesSubTasksTime: prepareValue(task.matchesSubTasksTime),
		source: prepareValue(task.source),
	};
}

export function mapDtoToModel(taskDto: TaskDto): TaskModel
{
	const task: TaskModel = {
		id: taskDto.id,
		title: taskDto.title,
		isImportant: taskDto.priority === 'high',
		description: taskDto.description,
		creatorId: taskDto.creator?.id,
		createdTs: taskDto.createdTs * 1000,
		responsibleId: taskDto.responsible?.id,
		deadlineTs: taskDto.deadlineTs * 1000,
		needsControl: taskDto.needsControl,
		startPlanTs: taskDto.startPlanTs * 1000,
		endPlanTs: taskDto.endPlanTs * 1000,
		fileIds: taskDto.fileIds,
		checklist: taskDto.checklist ?? [],
		containsChecklist: taskDto.containsChecklist,
		parentId: taskDto.parentId ?? 0,
		containsSubTasks: taskDto.containsSubTasks,
		containsRelatedTasks: taskDto.containsRelatedTasks,
		groupId: taskDto.group?.id,
		stageId: taskDto.stage?.id ?? 0,
		flowId: taskDto.flow?.id,
		status: taskDto.status,
		statusChangedTs: taskDto.statusChangedTs * 1000,
		accomplicesIds: taskDto.accomplices?.map(({ id }) => id) ?? [],
		auditorsIds: taskDto.auditors?.map(({ id }) => id) ?? [],
		chatId: taskDto.chatId,
		crmItemIds: prepareValue(taskDto.crmItemIds),
		allowsChangeDeadline: prepareValue(taskDto.allowsChangeDeadline),
		allowsChangeDatePlan: prepareValue(taskDto.allowsChangeDatePlan),
		matchesWorkTime: prepareValue(taskDto.matchesWorkTime),
		matchesSubTasksTime: prepareValue(taskDto.matchesSubTasksTime),
		rights: prepareValue(taskDto.rights),
		tags: prepareValue(taskDto.tags, taskDto.tags?.map(({ name }) => name)),
		inFavorite: taskDto.inFavorite || [],
		inMute: taskDto.inMute || [],
		archiveLink: prepareValue(taskDto.archiveLink),
	};

	return Object.fromEntries(Object.entries(task).filter(([, value]) => value));
}

export function mapModelToSliderData(task: TaskModel, checkLists: CheckListModel[]): TaskSliderData
{
	const accomplices = CheckListMappers.getUserIdsFromChecklists(checkLists, 'accomplices');
	const auditors = CheckListMappers.getUserIdsFromChecklists(checkLists, 'auditors');

	const data = {
		TITLE: prepareValue(task.title),
		DESCRIPTION: prepareValue(task.description, mapDescription(task.description)),
		RESPONSIBLE_ID: prepareValue(task.responsibleId),
		GROUP_ID: prepareValue(task.groupId),
		DEADLINE_TS: prepareValue(task.deadlineTs, Math.floor(task.deadlineTs / 1000)),
		IS_IMPORTANT: prepareValue(task.isImportant, task.isImportant ? 'Y' : null),
		FILE_IDS: prepareValue(task.fileIds, task.fileIds?.length > 0 ? task.fileIds : null),
		CHECKLIST: CheckListMappers.mapModelToSliderData(checkLists),
		ACCOMPLICES: prepareValue(accomplices, accomplices.length > 0 ? accomplices : null),
		AUDITORS: prepareValue(auditors, auditors.length > 0 ? auditors : null),
	};

	return Object.fromEntries(Object.entries(data).filter(([, value]) => value));
}

function prepareValue(value: any, mappedValue: any = value): any | undefined
{
	return Type.isUndefined(value) ? undefined : mappedValue;
}

// TODO: Temporary. Remove when removing old full card
function mapDescription(description: ?string): ?string
{
	return description?.replaceAll(/\[p]\n|\[p]\[\/p]|\[\/p]/gi, '').trim();
}

function mapTags(tags: number[]): TagDto[]
{
	return tags.map((name) => ({ name }));
}
