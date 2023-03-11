import {EventEmitter} from 'main.core.events';

export type UpdateParams = {
	id: number,
	groupId: number,
	sourceId: number,
	tmpId?: string
}

export class PullItem extends EventEmitter
{
	constructor()
	{
		super();

		this.setEventNamespace('BX.Tasks.Scrum.KanbanManager.PullItem');
	}

	getModuleId(): string
	{
		return 'tasks';
	}

	getMap(): Object
	{
		return {
			itemUpdated: this.onItemUpdated.bind(this),
		};
	}

	onItemUpdated(params: UpdateParams)
	{
		this.emit('itemUpdated', params);
	}
}