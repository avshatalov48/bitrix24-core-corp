import { BaseCommand } from './base-command';

export class RepeatCommand extends BaseCommand
{
	execute(): void
	{
		this.copilotTextController.generate();
	}
}
