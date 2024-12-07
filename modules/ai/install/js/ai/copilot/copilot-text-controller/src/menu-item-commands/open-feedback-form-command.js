import { Runtime, Loc } from 'main.core';
import type { BaseCommandOptions } from './base-command';
import { BaseCommand } from './base-command';
import 'ui.feedback.form';

type OpenFeedbackFormCommandOptions = {
	category: string;
	isBeforeGeneration: boolean;
} | BaseCommandOptions;

export class OpenFeedbackFormCommand extends BaseCommand
{
	#category: string;
	#isBeforeGeneration: boolean;

	constructor(options: OpenFeedbackFormCommandOptions)
	{
		super(options);

		this.#category = options.category;
		this.#isBeforeGeneration = options.isBeforeGeneration;
	}

	async execute(): Promise
	{
		await this.#openFeedbackForm();
	}

	async #openFeedbackForm(): void
	{
		const senderPagePreset = `${this.#category},${this.#isBeforeGeneration ? 'before' : 'after'}`;
		let data = null;

		if (this.#isBeforeGeneration === false)
		{
			data = await this.copilotTextController.getDataForFeedbackForm();
		}

		const contextMessages = data?.context_messages?.length > 0 ? JSON.stringify(data?.context_messages) : undefined;
		const authorMessage = data?.author_message ?? undefined;

		const formIdNumber = Math.round(Math.random() * 1000);

		Runtime.loadExtension(['ui.feedback.form'])
			.then(() => {
				BX.UI.Feedback.Form.open(
					{
						id: `ai.copilot.feedback-${formIdNumber}`,
						forms: [
							{ zones: ['es'], id: 684, lang: 'es', sec: 'svvq1x' },
							{ zones: ['en'], id: 686, lang: 'en', sec: 'tjwodz' },
							{ zones: ['de'], id: 688, lang: 'de', sec: 'nrwksg' },
							{ zones: ['com.br'], id: 690, lang: 'com.br', sec: 'kpte6m' },
							{ zones: ['ru', 'by', 'kz'], id: 692, lang: 'ru', sec: 'jbujn0' },
						],
						presets: {
							sender_page: senderPagePreset,
							prompt_code: data?.prompt?.code,
							user_message: data?.user_message,
							original_message: data?.original_message,
							author_message: authorMessage,
							context_messages: contextMessages,
							last_result0: data?.current_result?.[1],
							language: Loc.getMessage('LANGUAGE_ID'),
							cp_answer: data?.current_result?.[0],
						},
					},
				);
			})
			.catch((err) => {
				console.err(err);
			});
	}
}
