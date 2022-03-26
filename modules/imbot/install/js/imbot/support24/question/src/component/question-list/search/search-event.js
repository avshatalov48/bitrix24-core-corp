import { BaseEvent } from 'main.core.events';

export class SearchEvent extends BaseEvent
{
	data: {
		searchQuery: string,
	};
}