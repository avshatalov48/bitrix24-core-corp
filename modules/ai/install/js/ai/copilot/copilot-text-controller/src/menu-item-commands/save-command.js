import { BaseEvent } from 'main.core.events';
import { BaseCommand } from './base-command';

export class SaveCommand extends BaseCommand
{
	execute(): void
	{
		this.copilotTextController.emit('save', new BaseEvent({
			data: {
				result: this.copilotTextController.getAiResultText(),
				code: this.copilotTextController.getLastCommandCode(),
			},
		}));

		this.copilotTextController.getAnalytics().sendEventSave();
	}
}
