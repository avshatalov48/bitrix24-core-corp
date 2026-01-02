import { Dom, Extension, Tag } from 'main.core';
import { Core } from 'booking.core';
import { Model } from 'booking.const';
import './maximize.css';

type Inset = {
	left: number,
	top: number,
	right: number,
	bottom: number,
};

const isAirTemplate = Extension.getSettings('booking.booking').isAirTemplate;

const duration = 200;
const counterPanelScopeClass = 'ui-counter-panel__scope';
const darkThemeClass = 'bitrix24-dark-theme --ui-context-content-light';

export class Maximize
{
	#slider;
	#overlay: HTMLElement;

	constructor({ onOverlayClick })
	{
		this.onOverlayClick = onOverlayClick;
		this.#slider = new (BX.SidePanel.Manager.getSliderClass())('');
		this.#overlay = this.#renderOverlay();

		if (top.BX)
		{
			top.BX.Event.EventEmitter.subscribe('SidePanel.Slider:onCloseComplete', this.#handleSliderClose);
			top.BX.Event.EventEmitter.subscribe('SidePanel.Slider:onDestroy', this.#handleSliderClose);
		}
	}

	#handleSliderClose = (): void => {
		if (this.#isExpanded)
		{
			this.#slider.applyHacks();
			BX.SidePanel.Instance.disablePageScrollbar();
		}
	};

	#renderOverlay(): HTMLElement
	{
		return Tag.render`
			<div class="booking-booking-overlay" onclick="${this.onOverlayClick}"></div>
		`;
	}

	get #isExpanded(): boolean
	{
		return Core.getStore().getters[`${Model.Interface}/expanded`];
	}

	async maximize(): Promise<void>
	{
		await Core.getStore().dispatch(`${Model.Interface}/setExpanded`, true);

		this.#slider.applyHacks();
		BX.SidePanel.Instance.disablePageScrollbar();

		const start = this.#getInset(this.#appContainer);
		Dom.style(this.#appContainer, 'position', 'fixed');
		Dom.style(this.#appContainer, 'inset', '0 0 0 0');
		const finish = this.#getInset(this.#appContainer);

		this.#applyMaximizedStyles();

		Dom.removeClass(this.#overlay, '--closing');
		Dom.addClass(this.#overlay, '--opening');
		Dom.append(this.#overlay, document.body);
		await this.#animate(start, finish);
	}

	async minimize(): Promise<void>
	{
		await Core.getStore().dispatch(`${Model.Interface}/setExpanded`, false);

		this.#slider.resetHacks();
		BX.SidePanel.Instance.enablePageScrollbar();

		const start = this.#getInset(this.#appContainer);
		Dom.style(this.#appContainer, 'position', null);
		const finish = this.#getInset(this.#appContainer);

		this.#applyMinimizedStyles();

		Dom.removeClass(this.#overlay, '--opening');
		Dom.addClass(this.#overlay, '--closing');
		Dom.style(this.#appContainer, 'position', 'fixed');
		await this.#animate(start, finish);
		Dom.style(this.#appContainer, 'position', null);
		Dom.remove(this.#overlay);
	}

	#getInset(container: HTMLElement): Inset
	{
		const rect = container.getBoundingClientRect();

		return {
			left: rect.left,
			top: rect.top,
			right: window.innerWidth - (rect.left + rect.width),
			bottom: window.innerHeight - (rect.top + rect.height),
		};
	}

	#animate(start: Inset, finish: Inset): Promise<void>
	{
		return new Promise((complete) => new BX.easing({
			duration,
			start,
			finish,
			step: ({ top, right, bottom, left }: Inset) => {
				Dom.style(this.#appContainer, 'inset', `${top}px ${right}px ${bottom}px ${left}px`);
			},
			complete,
		}).animate());
	}

	#applyMaximizedStyles(): void
	{
		Dom.removeClass(this.#counterPanel, counterPanelScopeClass);
		Dom.addClass(this.#appContainer, darkThemeClass);
		Dom.style(this.#appContainer, 'height', 'initial');
		Dom.style(this.#appContainer, 'position', 'fixed');
		Dom.style(this.#appContainer, 'clip-path', `inset(0 ${this.#imBarWidth}px 0 0)`);
		Dom.style(this.#appContainer, 'background', 'var(--ui-color-palette-white-base)');

		if (isAirTemplate)
		{
			Dom.style(this.#appHeader, 'padding-left', '12px');
			Dom.style(this.#appHeader, 'padding-right', '12px');
		}

		Dom.style(this.#appHeader, 'border-bottom', '1px solid var(--ui-color-base-10)');
		Dom.style(this.#appHeader, 'max-width', '100%');
		Dom.style(this.#appHeader.parentElement, 'padding-right', `${this.#imBarWidth}px`);
		Dom.style(this.#appContent, 'margin', 0);
		Dom.style(this.#appContent, 'border-radius', 0);

		if (isAirTemplate)
		{
			Dom.style(this.#contentPaddingElement, 'padding', 0);
		}
		else
		{
			Dom.style(this.#contentPaddingElement, 'padding-bottom', 0);
			Dom.style(this.#contentPaddingElement, 'padding-right', `${this.#imBarWidth}px`);
		}

		Dom.style(this.#overlay, '--right', `${this.#imBarWidth}px`);
		BX.ZIndexManager.register(this.#appContainer, { overlay: this.#overlay });
	}

	#applyMinimizedStyles(): void
	{
		Dom.addClass(this.#counterPanel, counterPanelScopeClass);
		Dom.removeClass(this.#appContainer, darkThemeClass);
		Dom.style(this.#appContainer, 'position', null);
		Dom.style(this.#appContainer, 'clip-path', null);
		Dom.style(this.#appContainer, 'background', null);
		Dom.style(this.#appHeader, 'border-bottom', null);
		Dom.style(this.#appHeader, 'max-width', null);
		Dom.style(this.#appHeader.parentElement, 'padding-right', null);
		Dom.style(this.#appContent, 'margin', null);
		Dom.style(this.#appContent, 'border-radius', null);

		if (isAirTemplate)
		{
			Dom.style(this.#contentPaddingElement, 'padding', null);
		}
		else
		{
			Dom.style(this.#contentPaddingElement, 'padding-bottom', null);
			Dom.style(this.#contentPaddingElement, 'padding-right', null);
		}

		BX.ZIndexManager.unregister(this.#appContainer);
	}

	get #appContainer(): HTMLElement
	{
		if (isAirTemplate)
		{
			return document.querySelector('.app__page');
		}

		return BX('content-table');
	}

	get #appHeader(): HTMLElement
	{
		if (isAirTemplate)
		{
			return document.querySelector('.page__toolbar');
		}

		return document.querySelector('.page-header');
	}

	get #counterPanel(): HTMLElement
	{
		return Core.getParams().counterPanelContainer.firstElementChild;
	}

	get #appContent(): HTMLElement
	{
		if (isAirTemplate)
		{
			return BX('air-workarea-content');
		}

		return BX('workarea-content');
	}

	get #contentPaddingElement(): HTMLElement
	{
		if (isAirTemplate)
		{
			return document.querySelector('.app__page');
		}

		return BX('workarea')?.parentElement;
	}

	get #imBarWidth(): number
	{
		if (isAirTemplate)
		{
			return 0;
		}

		const imBar = isAirTemplate ? BX('right-bar') : BX('bx-im-bar');

		return window.innerWidth - imBar.getBoundingClientRect().left;
	}
}
