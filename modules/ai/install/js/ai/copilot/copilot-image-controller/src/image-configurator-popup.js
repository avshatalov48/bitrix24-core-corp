import { Tag, Loc, Event, Dom } from 'main.core';
import { Popup } from 'main.popup';
import { Loader } from 'main.loader';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { Icon, Actions } from 'ui.icon-set.api.core';
import { Button } from 'ui.buttons';
import type { ImageConfiguratorOptions } from './image-configurator';

import { ImageConfigurator } from './image-configurator';
import './css/image-configurator-popup.css';

type ImageConfiguratorPopupOffset = {
	top: number;
	left: number;
}

type ImageConfiguratorPopupOptions = {
	bindElement: HTMLElement,
	popupId: string;
	popupOffset?: ImageConfiguratorPopupOffset;
	withoutBackBtn: boolean;
	imageConfiguratorOptions: ImageConfiguratorOptions;
}

type ImageConfiguration = {
	style: string;
	format: string;
	engine: string;
}

export const ImageConfiguratorPopupEvents = Object.freeze({
	completions: 'completions',
	back: 'back',
	selectEngine: 'selectEngine',
});

export class ImageConfiguratorPopup extends EventEmitter
{
	#bindElement: HTMLElement | null = null;
	#popup: Popup | null = null;
	#popupOffset: ImageConfiguratorPopupOffset;
	#popupId: string;
	#imageConfigurator: ImageConfigurator;
	#withoutBackBtn: boolean;
	#submitButton: Button;
	#loader: Loader;
	#loaderOverlay: HTMLElement;

	constructor(options: ImageConfiguratorPopupOptions)
	{
		super(options);

		this.#popupId = options.popupId || String(Math.random());
		this.#bindElement = options.bindElement;
		this.#popupOffset = options.popupOffset;
		this.#withoutBackBtn = options.withoutBackBtn === true;
		this.#imageConfigurator = new ImageConfigurator({
			formats: options.imageConfiguratorOptions.formats,
			styles: options.imageConfiguratorOptions.styles,
			engines: options.imageConfiguratorOptions.engines,
		});

		this.#imageConfigurator.subscribe('change-parameter', (event) => {
			const data = event.getData();

			if (data.parameter === 'engine')
			{
				this.emit(ImageConfiguratorPopupEvents.selectEngine, data.value);
			}
		});

		this.#initSubmitButton();

		this.setEventNamespace('AI.Copilot:ImagePopup');
	}

	getPopupId(): string
	{
		return this.#popupId;
	}

	getPopup(): Popup
	{
		return this.#popup;
	}

	show(): void
	{
		if (this.#popup === null)
		{
			this.#createPopup();
		}

		this.#popup.show();
	}

	hide(): void
	{
		this.#popup?.close();
	}

	destroy(): void
	{
		this.#popup?.destroy();

		this.#popup = null;
	}

	isShown(): boolean
	{
		return this.#popup.isShown();
	}

	isContainsTarget(target: HTMLElement): boolean
	{
		return this.#popup?.getPopupContainer()?.contains(target) || this.#imageConfigurator?.isContainsTarget(target);
	}

	adjustPosition(): void
	{
		this.#popup?.adjustPosition({
			forceBindPosition: true,
		});
	}

	getImageConfiguration(): ImageConfiguration
	{
		return this.#imageConfigurator.getParams();
	}

	setFormats(formats): void
	{
		this.#imageConfigurator.setFormats(formats);
	}

	setSelectedEngine(engineCode: string): void
	{
		this.#imageConfigurator.setSelectedEngine(engineCode);
	}

	disableSubmitButton(): void
	{
		this.#submitButton.setDisabled(true);
	}

	enableSubmitButton(): void
	{
		this.#submitButton.setDisabled(false);
	}

	showLoader(): void
	{
		this.#loaderOverlay = Tag.render`
			<div class="ai__copilot-image-configurator-popup-loader-overlay"></div>
		`;

		Dom.append(this.#loaderOverlay, this.#popup?.getPopupContainer());

		this.#loader = new Loader({
			size: 110,
			color: getComputedStyle(document.body).getPropertyValue('--ui-color-copilot-primary'),
			target: this.#loaderOverlay,
		});

		this.#loader.show(this.#loaderOverlay);
	}

	hideLoader(): void
	{
		this.#loader.destroy();
		Dom.remove(this.#loaderOverlay);

		this.#loader = null;
		this.#loaderOverlay = null;
	}

	#createPopup(): Popup
	{
		this.#popup = new Popup({
			id: this.#popupId,
			bindElement: this.#bindElement,
			cacheable: true,
			width: 278,
			padding: 0,
			content: this.#renderPopupContent(),
		});

		this.#popup.setOffset({
			offsetTop: this.#popupOffset?.top,
			offsetLeft: this.#popupOffset?.left,
		});
	}

	#renderPopupContent(): HTMLElement
	{
		return Tag.render`
			<div class="ai__copilot-image-configurator-popup-content">
				<header class="ai__copilot-image-configurator-popup-content_header">
					${this.#renderBackBtnIfNeeded()}
					<div class="ai__copilot-image-configurator-popup-content_title">
						${Loc.getMessage('AI_COPILOT_IMAGE_POPUP_TITLE')}
					</div>
				</header>
				<div class="ai__copilot-image-configurator-popup-content_params">
					${this.#imageConfigurator.render()}
				</div>
				<div class="ai__copilot-image-configurator-popup-content_footer">
					${this.#submitButton.render()}
				</div>
			</div>
		`;
	}

	#renderBackBtnIfNeeded(): HTMLElement
	{
		if (this.#withoutBackBtn)
		{
			return null;
		}

		const backBtnIcon = new Icon({
			size: 24,
			color: getComputedStyle(document.body).getPropertyValue('--ui-color-base-90'),
			icon: Actions.CHEVRON_LEFT,
		});

		const backBtnIconElem = backBtnIcon.render();

		Event.bind(backBtnIconElem, 'click', () => {
			this.emit(ImageConfiguratorPopupEvents.back, new BaseEvent());
		});

		return Tag.render`
			<div class="ai__copilot-image-configurator-popup-content_back-btn">
				${backBtnIconElem}
			</div>
		`;
	}

	#initSubmitButton(): void
	{
		this.#submitButton = new Button({
			color: Button.Color.AI,
			text: Loc.getMessage('AI_COPILOT_IMAGE_POPUP_GENERATE_BTN'),
			round: true,
			noCaps: true,
			onclick: () => {
				this.emit(ImageConfiguratorPopupEvents.completions, new BaseEvent({
					data: this.#imageConfigurator.getParams(),
				}));
			},
		});
	}
}
