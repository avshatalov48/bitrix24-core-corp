import { Loc } from 'main.core';
import { Engine } from 'ai.engine';
import { AgreementPopup } from './agreement-popup';
import 'ui.notification';

type AgreementOptions = {
	agreement: EngineAgreement;
	engine: Engine;
	type: 'text' | 'image';
	engineCode: string;
}

export type EngineAgreement = {
	title: string;
	text: string;
	accepted: boolean;
}

export class Agreement
{
	#engine: Engine;
	#agreement: EngineAgreement;
	#type: 'text' | 'image';
	#engineCode: string;

	constructor(options: AgreementOptions) {
		this.#agreement = options.agreement;
		this.#engine = options.engine;
		this.#type = options.type;
		this.#engineCode = options.engineCode;
	}

	showAgreementPopup(onApply: Function): void
	{
		const agreement = this.#agreement;

		const popup = new AgreementPopup({
			title: agreement.title,
			content: agreement.text,
			onApply: () => {
				return new Promise((resolve, reject) => {
					this.#acceptAgreement()
						.then(() => {
							agreement.accepted = true;
							onApply();
							resolve();
						})
						.catch(() => {
							BX.UI.Notification.Center.notify({
								content: Loc.getMessage('AI_COPILOT_AGREE_WITH_TERMS_SERVER_ERROR'),
							});
							reject();
						});
				});
			},
		});

		popup.show();
	}

	#acceptAgreement(): Promise
	{
		if (this.#type === 'text')
		{
			return this.#engine.acceptTextAgreement(this.#engineCode);
		}

		if (this.#type === 'image')
		{
			return this.#engine.acceptImageAgreement(this.#engineCode);
		}

		throw new Error('AI: Agreement: acceptAgreement: Type can be "text" or "image"');
	}
}
