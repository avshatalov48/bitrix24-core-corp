import { CrmFormResourceModel } from 'booking.model.crm-form';
import type { ResourceDto } from './types';

export function mapDtoToModel(resourceDto: ResourceDto): CrmFormResourceModel
{
	return { ...resourceDto };
}
