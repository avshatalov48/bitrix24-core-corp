import type { FlowModel } from 'tasks.v2.model.flows';
import type { FlowDto } from './types';

export function mapDtoToModel(flowDto: FlowDto): FlowModel
{
	return {
		id: flowDto.id,
		name: flowDto.name,
		efficiency: flowDto.efficiency,
	};
}
