import { BaseCommand, type BaseCommandOptions } from './base-command';

type GenerateWithoutRequiredUserMessageOptions = {
	commandCode: string;
	prompts: Array;
} | BaseCommandOptions;

export class GenerateWithoutRequiredUserMessage extends BaseCommand
{
	#commandCode: string;
	#prompts: Array[] = [];

	constructor(options: GenerateWithoutRequiredUserMessageOptions)
	{
		super(options);

		this.#prompts = options.prompts;
		this.#commandCode = options.commandCode;
	}

	execute()
	{
		this.copilotTextController.generateWithoutRequiredUserMessage(this.#commandCode, this.#prompts);
	}
}
