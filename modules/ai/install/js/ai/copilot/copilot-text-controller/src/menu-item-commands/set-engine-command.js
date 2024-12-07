import type { EngineInfo } from '../types/engine-info';
import type { BaseCommandOptions } from './base-command';
import { BaseCommand } from './base-command';

type SetEngineCommandOptions = {
	engineCode: string;
	engines: EngineInfo[];
} | BaseCommandOptions;
export class SetEngineCommand extends BaseCommand
{
	#engineCode: string;

	constructor(options: SetEngineCommandOptions)
	{
		super(options);

		this.#engineCode = options.engineCode;
	}

	execute()
	{
		this.copilotTextController.setSelectedEngine(this.#engineCode);
	}
}
