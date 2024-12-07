import type { CopilotImageController } from 'ai.copilot.copilot-image-controller';

export type BaseCommandOptions = {
	copilotImageController: CopilotImageController;
}

export class BaseCommand
{
	copilotImageController: CopilotImageController;
	constructor(options: BaseCommandOptions)
	{
		this.copilotImageController = options.copilotImageController;
	}

	execute(): void
	{
		throw new Error('You must implement this method!');
	}
}
