import { Loc, Runtime } from 'main.core';
import { Main } from 'ui.icon-set.api.core';
import 'ui.icon-set.main';

import { BaseMenuItem, type BaseMenuItemOptions } from '../../../src/copilot-menu/copilot-menu-item';
import { CopilotTextControllerEngine } from '../copilot-text-controller-engine';

export type FeedbackMenuItemOptions = BaseMenuItemOptions & {
	onClick: Function;
	engine: CopilotTextControllerEngine;
	isBeforeGeneration: boolean;
}

export class FeedbackMenuItem extends BaseMenuItem
{
	#isOpenBeforeGeneration: boolean;
	#engine: CopilotTextControllerEngine;

	constructor(options: FeedbackMenuItemOptions)
	{
		super({
			code: 'feedback',
			icon: Main.FEEDBACK,
			text: Loc.getMessage('AI_COPILOT_MENU_ITEM_AI_FEEDBACK'),
			onClick: async () => {
				return this.#openFeedbackForm();
			},
			...options,
		});

		this.#isOpenBeforeGeneration = options.isBeforeGeneration;
		this.#engine = options.engine;
	}

	async #openFeedbackForm(): void
	{
		const senderPagePreset = `${this.#engine.getCategory()},${this.#isOpenBeforeGeneration ? 'before' : 'after'}`;
		let data = null;

		if (this.#isOpenBeforeGeneration === false)
		{
			data = await this.#engine.getDataForFeedbackForm();
		}

		const contextMessages = data?.context_messages?.length > 0 ? data?.context_messages : undefined;
		const authorMessage = data?.author_message ?? undefined;

		try
		{
			await Runtime.loadExtension(['ui.feedback.form']);

			BX.UI.Feedback.Form.open(
				{
					id: 'ai.copilot.feedback',
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
		}
		catch (err)
		{
			console.error(err);
		}
	}
}
