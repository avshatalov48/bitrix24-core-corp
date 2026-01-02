import type { CrmItemDto } from 'tasks.v2.provider.service.crm-service';
import type { GroupDto, StageDto } from 'tasks.v2.provider.service.group-service';
import type { FlowDto } from 'tasks.v2.provider.service.flow-service';
import type { UserDto } from 'tasks.v2.provider.service.user-service';

export type TaskDto = {
	id: number,
	title: string,
	description: string,
	creator: UserDto,
	createdTs: number,
	responsible: UserDto,
	deadlineTs: number,
	needsControl: boolean,
	startPlanTs: number,
	endPlanTs: number,
	fileIds: number[],
	checklist: number[] | string[],
	containsChecklist: boolean,
	parent: TaskDto,
	parentId: number,
	subTaskIds: number[],
	relatedTaskIds: number[],
	containsSubTasks: boolean,
	containsRelatedTasks: boolean,
	group: GroupDto,
	stage: StageDto,
	flow: FlowDto,
	priority: Priority,
	status: Status,
	statusChangedTs: number,
	accomplices: UserDto[],
	auditors: UserDto[],
	tags: TagDto[],
	chatId: number,
	crmItemIds: string[],
	crmItems: CrmItemDto[],
	allowsChangeDeadline: boolean,
	allowsChangeDatePlan: boolean,
	matchesWorkTime: boolean,
	matchesSubTasksTime: boolean,
	rights: { [right: string]: boolean },
	inFavorite: number[],
	inMute: number[],
	archiveLink: ?string,
};

export type TaskSliderData = {
	TITLE: string,
	DESCRIPTION: string,
	RESPONSIBLE_ID: number,
	GROUP_ID: number,
	DEADLINE_TS: number,
	IS_IMPORTANT: string,
	FILE_IDS: string[],
	CHECKLIST: CheckList,
};

type Priority = 'low' | 'average' | 'high';

type Status = 'pending' | 'in_progress' | 'supposedly_completed' | 'completed' | 'deferred' | 'declined';

type CheckList = {
	any: any,
};

export type TagDto = {
	id: number,
	name: string,
	owner: UserDto,
	group: GroupDto,
};
