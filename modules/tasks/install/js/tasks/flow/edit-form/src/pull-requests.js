import { EventEmitter } from 'main.core.events';

export class PullRequests extends EventEmitter
{
	#userId: number;

	constructor(userId: number)
	{
		super();

		this.setEventNamespace('BX.Tasks.Flow.EditForm.PullRequests');

		this.#userId = parseInt(userId, 10);
	}

	getModuleId(): string
	{
		return 'tasks';
	}

	getMap(): Object
	{
		return {
			template_add: this.#onTemplateAdded.bind(this),
			template_update: this.#onTemplateUpdated.bind(this),
		};
	}

	#onTemplateAdded(data): void
	{
		this.emit('templateAdded', {
			template: {
				id: data.TEMPLATE_ID,
				title: data.TEMPLATE_TITLE,
			},
		});
	}

	#onTemplateUpdated(data): void
	{
		this.emit('templateUpdated', {
			template: {
				id: data.TEMPLATE_ID,
				title: data.TEMPLATE_TITLE,
			},
		});
	}
}
