import type { BaseEvent } from 'main.core.events';

export type UserSelectorOptions = {
	container: HTMLElement,
	excludedEntityList: ?[],
	preselectedIds: ?[],
	events?: {
		[key: string]: (event: BaseEvent) => void
	},
	multiple: boolean,
	roleEnabled: boolean,
	cacheable?: boolean,
	context?: string,
}
