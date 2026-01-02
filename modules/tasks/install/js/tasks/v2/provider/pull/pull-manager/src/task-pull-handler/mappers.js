import { Type } from 'main.core';
import { TaskStatus } from 'tasks.v2.const';
import type { TaskModel } from 'tasks.v2.model.tasks';
import type { PushData } from './types';

export function mapPushToModel({ AFTER: after, TASK_ID: id }: PushData): TaskModel
{
	const task: TaskModel = {
		id,
		title: prepareValue(after.TITLE),
		isImportant: prepareValue(after.PRIORITY, after.PRIORITY === 2),
		creatorId: prepareValue(after.CREATED_BY),
		responsibleId: prepareValue(after.RESPONSIBLE_ID),
		deadlineTs: prepareValue(after.DEADLINE, after.DEADLINE * 1000),
		checklist: undefined,
		groupId: prepareValue(after.GROUP_ID),
		stageId: prepareValue(after.STAGE, after.STAGE_INFO?.id ?? 0),
		flowId: prepareValue(after.FLOW_ID),
		status: prepareValue(after.STATUS, mapStatus(after.STATUS)),
		statusChangedTs: prepareValue(after.STATUS, Date.now()),
		accomplicesIds: prepareValue(after.ACCOMPLICES, mapUserIds(after.ACCOMPLICES)),
		auditorsIds: prepareValue(after.AUDITORS, mapUserIds(after.AUDITORS)),
		parentId: prepareValue(after.PARENT_ID, Number(after.PARENT_ID) || 0),
		tags: prepareValue(after.TAGS, after.TAGS ? after.TAGS.split(',') : []),
	};

	return Object.fromEntries(Object.entries(task).filter(([, value]) => !Type.isUndefined(value)));
}

function mapStatus(status: number): string
{
	return {
		2: TaskStatus.Pending,
		3: TaskStatus.InProgress,
		4: TaskStatus.SupposedlyCompleted,
		5: TaskStatus.Completed,
		6: TaskStatus.Deferred,
	}[status] ?? TaskStatus.Pending;
}

function mapUserIds(users: string): number[]
{
	if (!users)
	{
		return [];
	}

	return users.split(',').map((id: string) => Number(id));
}

function prepareValue(value: any, mappedValue: ?any = value): any | undefined
{
	return Type.isUndefined(value) ? undefined : mappedValue;
}
