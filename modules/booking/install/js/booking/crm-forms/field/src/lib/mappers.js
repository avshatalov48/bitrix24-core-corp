import { DayIndexDict } from '../const/const';
import type { Resource } from '../types';

export function mapDtoToResource(resourcesDto: ResourceDto[]): Resource[]
{
	return resourcesDto.map((dto) => {
		return {
			...dto,
			slotRanges: dto.slotRanges.map((slotRange) => {
				return {
					...slotRange,
					weekDays: slotRange.weekDays.map((weekDay) => DayIndexDict[weekDay]),
				};
			}),
		};
	});
}

type ResourceDto = {
	id: number,
	name: string,
	typeName: string,
	slotRanges: {
		id: number,
		from: number,
		to: number,
		timezone: string,
		weekDays: string[],
		slotSize: number,
	}[];
}
