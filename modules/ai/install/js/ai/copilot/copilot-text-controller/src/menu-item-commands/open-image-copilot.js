import { BaseCommand } from './base-command';

export class OpenImageCopilot extends BaseCommand
{
	execute()
	{
		this.copilotTextController.emit('image');
	}
}
