import type { BaseEvent } from 'main.core.events';

export type UserSelectorOptions = {
	container: HTMLElement,
	preselectedIds: ?[],
	events?: {
		[key: string]: (event: BaseEvent) => void
	},
	multiple: boolean,
	context?: string,
}
