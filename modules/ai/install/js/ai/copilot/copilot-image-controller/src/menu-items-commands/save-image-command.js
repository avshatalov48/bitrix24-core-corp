import { BaseEvent } from 'main.core.events';
import { BaseCommand } from './base-command';

export type saveEventData = {
	imageUrl: string | null;
}

export class SaveImageCommand extends BaseCommand
{
	execute()
	{
		this.copilotImageController.emit('save', new BaseEvent({
			data: {
				imageUrl: this.copilotImageController.getResultImageUrl(),
			},
		}));

		this.copilotImageController.getAnalytics().sendEventSave();
	}
}
