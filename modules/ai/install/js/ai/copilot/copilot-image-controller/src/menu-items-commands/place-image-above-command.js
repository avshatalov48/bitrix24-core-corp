import { BaseCommand } from './base-command';

export class PlaceImageAboveCommand extends BaseCommand
{
	execute()
	{
		this.copilotImageController.emit('place-above');
	}
}
