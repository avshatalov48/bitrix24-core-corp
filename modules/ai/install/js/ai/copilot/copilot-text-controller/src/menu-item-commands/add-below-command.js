import { BaseCommand } from './base-command';

export class AddBelowCommand extends BaseCommand
{
	execute(): void
	{
		this.copilotTextController.emit('add_below', {
			result: this.copilotTextController.getAiResultText(),
			code: this.copilotTextController.getLastCommandCode(),
		});
	}
}
