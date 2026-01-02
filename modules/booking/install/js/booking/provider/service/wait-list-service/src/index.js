import { mapModelToDto, mapDtoToModel } from './mappers';

export { waitListService } from './wait-list-service';
export const WaitListMappers = { mapModelToDto, mapDtoToModel };
export type { WaitListItemDto } from './types';
