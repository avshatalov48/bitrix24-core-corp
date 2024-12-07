import type { CopilotTextController } from 'ai.copilot.copilot-text-controller';

export type BaseCommandOptions = {
	copilotTextController: CopilotTextController;
}

export class BaseCommand
{
	copilotTextController: CopilotTextController;

	constructor(options: BaseCommandOptions)
	{
		this.copilotTextController = options?.copilotTextController;
	}
}
