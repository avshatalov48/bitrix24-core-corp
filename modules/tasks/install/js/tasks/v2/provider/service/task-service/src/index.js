import { mapModelToDto, mapDtoToModel, mapModelToSliderData } from './mappers';

export { taskService } from './task-service';
export const TaskMappers = { mapModelToDto, mapDtoToModel, mapModelToSliderData };
export type { TaskDto } from './types';
