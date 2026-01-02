import { GroupMappers } from 'tasks.v2.provider.service.group-service';
import { FlowMappers } from 'tasks.v2.provider.service.flow-service';
import { UserMappers, type UserDto } from 'tasks.v2.provider.service.user-service';
import type { FlowModel } from 'tasks.v2.model.flows';
import type { GroupModel } from 'tasks.v2.model.groups';
import type { StageModel } from 'tasks.v2.model.stages';
import type { TaskModel } from 'tasks.v2.model.tasks';
import type { UserModel } from 'tasks.v2.model.users';

import { mapDtoToModel } from './mappers';
import type { TaskDto } from './types';

export class TaskGetExtractor
{
	#data: TaskDto;

	constructor(data: TaskDto)
	{
		this.#data = data;
	}

	getTask(): TaskModel
	{
		return mapDtoToModel(this.#data);
	}

	getFlow(): FlowModel | null
	{
		return this.#data.flow ? FlowMappers.mapDtoToModel(this.#data.flow) : null;
	}

	getGroup(): GroupModel | null
	{
		return this.#data.group ? GroupMappers.mapDtoToModel(this.#data.group) : null;
	}

	getStages(): StageModel[]
	{
		return this.#data.stage ? [GroupMappers.mapStageDtoToModel(this.#data.stage)] : [];
	}

	getUsers(): UserModel[]
	{
		return [
			this.#data.creator ?? [],
			this.#data.responsible ?? [],
			...this.#data?.accomplices ?? [],
			...this.#data?.auditors ?? [],
		].map((userDto: UserDto): UserModel => UserMappers.mapDtoToModel(userDto));
	}
}
