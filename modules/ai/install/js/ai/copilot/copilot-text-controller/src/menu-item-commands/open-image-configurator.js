import { BaseCommand } from './base-command';

export class OpenImageConfigurator extends BaseCommand
{
	execute(): void
	{
		this.copilotTextController.emit('show-image-configurator');
	}
}
