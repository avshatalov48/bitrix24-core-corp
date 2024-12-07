import { BaseCommand } from './base-command';

export class PlaceImageUnderCommand extends BaseCommand
{
	execute()
	{
		this.copilotImageController.emit('place-under');
	}
}
