import { BaseCommand } from './base-command';

export class RepeatGenerateCommand extends BaseCommand
{
	execute(): void
	{
		this.copilotTextController.adjustMenusPosition();
		this.copilotTextController.generate();
	}
}
