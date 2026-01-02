import type { StageDto } from 'tasks.v2.provider.service.group-service';

export type PushData = {
	USER_ID: number,
	TASK_ID: number,
	AFTER: TaskPush,
	BEFORE: TaskPush,
};

export type TaskPush = {
	TITLE: string,
	PRIORITY: number,
	CREATED_BY: number,
	RESPONSIBLE_ID: number,
	DEADLINE: number,
	GROUP_ID: number,
	STAGE: number,
	STAGE_INFO: StageDto,
	STATUS: number,
	ACCOMPLICES: string,
	AUDITORS: string,
	PARENT_ID: number,
};
