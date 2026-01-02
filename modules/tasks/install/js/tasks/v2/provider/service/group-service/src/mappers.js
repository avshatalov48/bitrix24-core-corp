import type { GroupModel } from 'tasks.v2.model.groups';
import type { StageModel } from 'tasks.v2.model.stages';
import type { GroupDto, StageDto } from './types';

export function mapDtoToModel(groupDto: GroupDto): GroupModel
{
	return {
		id: groupDto.id,
		name: groupDto.name,
		image: groupDto.image?.src,
		type: groupDto.type,
		stagesIds: groupDto.stages?.map(({ id }) => id),
	};
}

export function mapStageDtoToModel(stageDto: StageDto): StageModel
{
	return {
		id: stageDto.id,
		title: stageDto.title,
		color: stageDto.color,
	};
}
