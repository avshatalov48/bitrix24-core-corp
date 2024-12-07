import { Tag, Loc, Extension, Type } from 'main.core';
import { Popup, CloseIconSize } from 'main.popup';
import { Button } from 'ui.buttons';
import { UI } from 'ui.notification';

import './css/copilot-agreement-popup.css';

export type CopilotAgreementPopupOptions = {
	onApply?: Function;
	onCancel?: Function;
}

export class CopilotAgreementPopup
{
	#onApply: Function;
	#onCancel: Function;
	#wasApplied: boolean = false;
	#popup: Popup | null;

	constructor(options: CopilotAgreementPopupOptions)
	{
		if (options?.onApply)
		{
			this.setOnApply(options.onApply);
		}

		this.#onCancel = Type.isFunction(options.onCancel) ? options.onCancel : null;
	}

	show(): void
	{
		if (!this.#popup)
		{
			this.#initPopup();
		}

		this.#popup.show();
	}

	hide(): void
	{
		this.#popup?.close();
		this.#popup = null;
	}

	setOnApply(onApply: Function): void
	{
		this.#onApply = onApply;
	}

	setOnCancel(onCancel: Function): void
	{
		this.#onCancel = onCancel;
	}

	#initPopup(): void
	{
		this.#popup = new Popup({
			content: this.#renderPopupContent(),
			cacheable: false,
			overlay: true,
			disableScroll: true,
			width: 492,
			minHeight: 448,
			closeByEsc: true,
			closeIcon: true,
			closeIconSize: CloseIconSize.LARGE,
			padding: 20,
			borderRadius: '10px',
			events: {
				onDestroy: () => {
					if (this.#wasApplied === false && this.#onCancel)
					{
						this.#onCancel();
					}

					this.#popup = null;
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
							'#LINK#': `<a target="_blank" href="${this.#getFullAgreementLink()}">`,
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
			onclick: async (button: Button) => {
				try
				{
					button.setState(Button.State.WAITING);
					await this.#onApply();
					this.hide();
				}
				catch (err)
				{
					UI.Notification.Center.notify({
						content: Loc.getMessage('COPILOT_AGREEMENT_POPUP_APPLY_ERROR'),
					});

					console.error(err);
				}
				finally
				{
					button.setState(null);
				}
			},
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

	#getFullAgreementLink(): string
	{
		const zone = Extension.getSettings('ai.copilot-agreement-popup').zone;

		const linksByZone = {
			ru: 'https://www.bitrix24.ru/about/terms-of-use-ai.php',
			kz: 'https://www.bitrix24.kz/about/terms-of-use-ai.php',
			by: 'https://www.bitrix24.by/about/terms-of-use-ai.php',
			en: 'https://www.bitrix24.com/terms/bitrix24copilot-rules.php',
		};

		return linksByZone[zone] || linksByZone.en;
	}
}
