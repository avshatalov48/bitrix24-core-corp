import type { Resource } from '../types';

export const ALL_RESOURCES_ID = -1;

export const AllResource: Resource = {
	id: ALL_RESOURCES_ID,
	name: '',
	typeName: '',
	slotRanges: [],
};

export const DayIndexDict = Object.freeze({
	Sun: 0,
	Mon: 1,
	Tue: 2,
	Wed: 3,
	Thu: 4,
	Fri: 5,
	Sat: 6,
});
