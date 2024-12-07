import { BaseCommand } from './index';

export class CloseCommand extends BaseCommand
{
	execute()
	{
		this.copilotTextController.emit('close');
	}
}
