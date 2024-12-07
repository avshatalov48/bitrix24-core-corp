import { ajax } from 'main.core';
import { BaseCommand, type BaseCommandOptions } from './base-command';

type GenerateWithRequiredUserMessageCommandOptions = {
	commandCode: string;
} | BaseCommandOptions;

export class GenerateWithRequiredUserMessageCommand extends BaseCommand
{
	#commandCode: string;

	constructor(options: GenerateWithRequiredUserMessageCommandOptions)
	{
		super(options);

		this.#commandCode = options.commandCode;
	}

	async execute(): void
	{
		const data = new FormData();
		data.append('promptCode', this.#commandCode);

		try
		{
			const res = await ajax.runAction('ai.prompt.getTextByCode', {
				data,
			});

			this.copilotTextController.generateWithRequiredUserMessage(this.#commandCode, res.data.text);
		}
		catch (e)
		{
			console.error(e);
		}
	}
}
