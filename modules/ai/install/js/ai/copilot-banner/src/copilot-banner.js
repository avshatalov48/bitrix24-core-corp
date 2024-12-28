import { bind, Extension, Loc, Tag } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Popup } from 'main.popup';
import { Icon, Main as MainIconSet } from 'ui.icon-set.api.core';
import 'ui.hint';

import './css/copilot-banner.css';

export const CopilotBannerEvents = Object.freeze({
	actionStart: 'action-start',
	actionFinishSuccess: 'action-finish-success',
	actionFinishFailed: 'action-finish-failed',
});

export type CopilotBannerOptions = {
	isWestZone: boolean;
	buttonClickHandler: Function;
}

export class CopilotBanner extends EventEmitter
{
	#popup: Popup = null;
	#isWestZone: boolean;
	#buttonClickHandler: Function;

	constructor(options: CopilotBannerOptions)
	{
		super(options);

		const settings = Extension.getSettings('ai.copilot-banner');

		this.#isWestZone = settings.get('isWestZone');
		this.#buttonClickHandler = options.buttonClickHandler ?? (() => {});

		this.setEventNamespace('AI:CopilotBanner');
	}

	show(): void
	{
		this.#getPopup().show();
	}

	hide(): void
	{
		this.#getPopup().close();
	}

	#getPopup(): Popup
	{
		if (!this.#popup)
		{
			return this.#createPopup();
		}

		return this.#popup;
	}

	#createPopup(): Popup
	{
		this.#popup = new Popup({
			maxWidth: 854,
			minWidth: 700,
			minHeight: 520,
			content: this.#renderPopupContent(),
			padding: 0,
			borderRadius: '18px',
			overlay: {
				backgroundColor: '#000',
				opacity: 70,
			},
			animation: 'fading',
			disableScroll: false,
			className: 'ai__copilot-banner_popup',
		});

		return this.#popup;
	}

	#renderPopupContent(): HTMLElement
	{
		return Tag.render`
			<div class="ai__copilot-banner_content">
				${this.#renderCopilotBannerIcon()}
				<div class="ai__copilot-banner_content-inner">
					<div class="ai__copilot-banner_starlight"></div>
					${this.#renderPlatesByZone()}
					<div class="ai__copilot-banner_main">
						<p class="ai__copilot-banner_text">${this.#getTextWithAccents('AI_COPILOT_BANNER_TEXT_1')}</p>
						<p class="ai__copilot-banner_text">${this.#getTextWithAccents('AI_COPILOT_BANNER_TEXT_2')}</p>
						<p class="ai__copilot-banner_text">${this.#getTextWithAccents('AI_COPILOT_BANNER_TEXT_3')}</p>
					</div>
					<footer class="ai__copilot-banner_footer">
					<div class="ai__copilot-banner_footer-text">
						${this.#renderTitle()}
					</div>
					${this.#renderButton()}
				</footer>
				</div>
			</div>
		`;
	}

	#renderPlatesByZone(): HTMLElement
	{
		if (this.#isWestZone)
		{
			return Tag.render`
				<div class="ai__copilot-banner_plates">
					<div class="ai__copilot-banner_plate --google"></div>
					<div class="ai__copilot-banner_plate --open-ai"></div>
					<div class="ai__copilot-banner_plate --market"></div>
					<div class="ai__copilot-banner_plate --meta"></div>
				</div>
			`;
		}

		return Tag.render`
			<div class="ai__copilot-banner_plates">
				<div class="ai__copilot-banner_plate --ygpt"></div>
				<div class="ai__copilot-banner_plate --its"></div>
				<div class="ai__copilot-banner_plate --market"></div>
				<div class="ai__copilot-banner_plate --giga-chat"></div>
			</div>
		`;
	}

	#renderCopilotBannerIcon(): HTMLElement
	{
		const icon = new Icon({
			size: 88,
			color: '#fff',
			icon: MainIconSet.COPILOT_AI,
		});

		return Tag.render`
			<div class="ai__copilot-banner_icon-wrapper">
				<div class="ai__copilot-banner_icon-bg"></div>
				${icon.render()}
			</div>
		`;
	}

	#getTextWithAccents(phraseCode: string): string
	{
		return Loc.getMessage(phraseCode, {
			'#accent#': '<span class="--accent">',
			'#/accent#': '</span>',
		});
	}

	#renderTitle(): HTMLElement
	{
		const titleText = Loc.getMessage(
			'AI_COPILOT_BANNER_TITLE',
			{
				'#hint-start#': '<span class="ai__copilot-banner_title-hint">',
				'#hint-end#': '</span>',
			},
		);

		const title = Tag.render`
			<h4 class="ai__copilot-banner_title">
				${titleText}
			</h4>
		`;

		const titlePartWithHint = title.querySelector('.ai__copilot-banner_title-hint');

		const hintContent = `<div>${Loc.getMessage('AI_COPILOT_BANNER_TITLE_HINT')}</div>`;

		const hint = BX.UI.Hint.createInstance({
			popupParameters: {
				className: 'ai__copilot-banner-hint-popup',
				borderRadius: '3px',
			},
		});

		bind(titlePartWithHint, 'mouseenter', () => {
			hint.show(titlePartWithHint, hintContent, true);
		});

		bind(titlePartWithHint, 'mouseleave', () => {
			hint.hide(titlePartWithHint);
		});

		return title;
	}

	#renderButton(): HTMLElement
	{
		const btn = Tag.render`
			<button class="ai__copilot-banner_btn">
				${Loc.getMessage('AI_COPILOT_START_USING_BUTTON')}
			</button>
		`;

		bind(btn, 'click', this.#handleButtonClick.bind(this));

		return btn;
	}

	async #handleButtonClick(): void
	{
		this.emit(CopilotBannerEvents.actionStart);
		try
		{
			await this.#buttonClickHandler();
			this.emit(CopilotBannerEvents.actionFinishSuccess);
		}
		catch (e)
		{
			console.error(e);
			this.emit(CopilotBannerEvents.actionFinishFailed);
		}
		finally
		{
			this.hide();
		}
	}
}
