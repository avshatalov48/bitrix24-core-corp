import { Tag, Loc, Extension, Type } from 'main.core';
import { Popup, CloseIconSize } from 'main.popup';
import { Button } from 'ui.buttons';
import { UI } from 'ui.notification';
import { Engine } from 'ai.engine';

import './css/copilot-agreement.css';

export type CopilotAgreementOptions = {
	events?: CopilotAgreementEvents;
	moduleId: string;
	contextId: string;
}

export type CopilotAgreementEvents = {
	onAccept: Function;
	onAcceptError: Function;
	onCancel: Function;
	onAgreementPopupShow: Function;
	onAgreementPopupHide: Function;
}

export class CopilotAgreement
{
	#events: CopilotAgreementEvents;
	#wasAccepted: boolean = false;
	#engine: Engine;
	#popup: Popup | null;

	constructor(options: CopilotAgreementOptions)
	{
		this.#validateOptions(options);

		this.#events = options.events || {};

		this.#engine = new Engine();

		this.#engine.setContextId(options.contextId);
		this.#engine.setModuleId(options.moduleId);
	}

	static #checkAgreementResult: boolean | null = null;

	static getFullAgreementLink(): string
	{
		const zone = Extension.getSettings('ai.copilot-agreement').zone;

		const linksByZone = {
			ru: 'https://www.bitrix24.ru/about/terms-of-use-ai.php',
			kz: 'https://www.bitrix24.kz/about/terms-of-use-ai.php',
			by: 'https://www.bitrix24.by/about/terms-of-use-ai.php',
			en: 'https://www.bitrix24.com/terms/bitrix24copilot-rules.php',
		};

		return linksByZone[zone] || linksByZone.en;
	}

	async checkAgreement(): Promise<boolean>
	{
		if (CopilotAgreement.#checkAgreementResult !== null && CopilotAgreement.#checkAgreementResult !== undefined)
		{
			if (CopilotAgreement.#checkAgreementResult === false)
			{
				this.#showAgreementPopup();
			}

			return Promise.resolve(CopilotAgreement.#checkAgreementResult);
		}

		try
		{
			const result = await this.#engine.checkAgreement();

			if (result.data.isAccepted === false)
			{
				this.#showAgreementPopup();
			}

			CopilotAgreement.#checkAgreementResult = result.data.isAccepted;

			return result.data.isAccepted;
		}
		catch (e)
		{
			console.error(e);

			return true;
		}
	}

	#showAgreementPopup(): void
	{
		if (!this.#popup)
		{
			this.#initAgreementPopup();
		}

		this.#popup.show();
	}

	#hideAgreementPopup(): void
	{
		this.#popup?.close();
		this.#popup = null;
	}

	#initAgreementPopup(): void
	{
		this.#popup = new Popup({
			content: this.#renderPopupContent(),
			cacheable: false,
			overlay: true,
			disableScroll: true,
			width: 492,
			minHeight: 448,
			closeByEsc: true,
			autoHide: true,
			closeIcon: true,
			closeIconSize: CloseIconSize.LARGE,
			padding: 20,
			borderRadius: '10px',
			events: {
				onDestroy: () => {
					if (this.#events?.onAgreementPopupHide)
					{
						this.#events?.onAgreementPopupHide();
					}

					if (this.#wasAccepted === false && this.#events?.onCancel)
					{
						this.#events?.onCancel();
					}

					this.#popup = null;
				},
				onPopupShow: () => {
					if (this.#events?.onAgreementPopupShow)
					{
						this.#events?.onAgreementPopupShow();
					}
				},
			},
		});
	}

	#renderPopupContent(): HTMLElement
	{
		return Tag.render`
			<div
				class="ai__copilot-agreement-popup-content"
			>
				<header class="ai__copilot-agreement-popup-content_header">
					<h3 class="ai__copilot-agreement-popup-content_title">
						${Loc.getMessage('COPILOT_AGREEMENT_POPUP_TITLE')}
					</h3>
				</header>
				<main class="ai__copilot-agreement-popup-content_main">
					<div class="ai__copilot-agreement-popup-content_img"></div>
					<p class="ai__copilot-agreement-popup-content_text">
						${Loc.getMessage('COPILOT_AGREEMENT_POPUP_PARAGRAPH_1')}
					</p>
					<p class="ai__copilot-agreement-popup-content_text">
						${Loc.getMessage('COPILOT_AGREEMENT_POPUP_PARAGRAPH_2', {
							'#LINK#': `<a target="_blank" href="${CopilotAgreement.getFullAgreementLink()}">`,
							'#/LINK#': '</a>',
						})}
					</p>
				</main>
				<footer class="ai__copilot-agreement-popup-content_footer">
					<div class="ai__copilot-agreement-popup_footer-content-buttons">
						${this.#renderApplyButton()}
						${this.#renderCancelButton()}
					</div>
				</footer>
			</div>
		`;
	}

	#renderApplyButton(): HTMLElement
	{
		const applyBtn = new Button({
			text: Loc.getMessage('COPILOT_AGREEMENT_POPUP_APPLY_BTN'),
			color: Button.Color.SUCCESS,
			round: true,
			onclick: this.#handleClickOnAcceptBtn.bind(this),
		});

		return applyBtn.render();
	}

	#renderCancelButton(): HTMLElement
	{
		const cancelBtn = new Button({
			text: Loc.getMessage('COPILOT_AGREEMENT_POPUP_CANCEL_BTN'),
			round: true,
			color: Button.Color.LIGHT,
			onclick: () => {
				this.#popup.destroy();
			},
		});

		return cancelBtn.render();
	}

	async #handleClickOnAcceptBtn(button: Button): void
	{
		try
		{
			button.setState(Button.State.WAITING);

			CopilotAgreement.#checkAgreementResult = await this.#acceptAgreement();

			this.#wasAccepted = CopilotAgreement.#checkAgreementResult;

			if (this.#events?.onAccept)
			{
				this.#events.onAccept();
			}

			this.#hideAgreementPopup();
		}
		catch (err)
		{
			if (this.#events?.onAcceptError)
			{
				this.#events?.onAcceptError();
			}

			UI.Notification.Center.notify({
				content: Loc.getMessage('COPILOT_AGREEMENT_POPUP_APPLY_ERROR'),
			});

			console.error(err);
		}
		finally
		{
			button.setState(null);
		}
	}

	async #acceptAgreement(): Promise<boolean>
	{
		const result = await this.#engine.acceptAgreement();

		return result.data.isAccepted;
	}

	#validateOptions(options: CopilotAgreementOptions): void
	{
		if (!options.moduleId || Type.isStringFilled(options.moduleId) === false)
		{
			throw new Error('AI: CopilotAgreement: moduleId option is required and must be the string');
		}

		if (!options.contextId || Type.isStringFilled(options.contextId) === false)
		{
			throw new Error('AI: CopilotAgreement: moduleId option is required and must be the string');
		}

		if (options.events && Type.isObject(options.events) === false)
		{
			throw new Error('AI: CopilotAgreement: events option must be the object');
		}
	}
}
