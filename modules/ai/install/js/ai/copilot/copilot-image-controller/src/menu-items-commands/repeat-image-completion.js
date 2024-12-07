import { BaseCommand } from './base-command';

export class RepeatImageCompletion extends BaseCommand
{
	execute()
	{
		this.copilotImageController.completions();
	}
}
