import { Tag, Loc, Event } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Popup } from 'main.popup';
import { Lottie } from 'ui.lottie';

import './css/copilot-context-menu-loader.css';
import copilotLottieIcon from '../copilot-input-field/lottie/copilot-icon-1.json';

type CopilotReadonlyLoaderOptions = {
	bindElement: HTMLElement;
}

export const CopilotContextMenuLoaderEvents = {
	CANCEL: 'cancel',
};

export class CopilotContextMenuLoader extends EventEmitter
{
	#popup: Popup | null = null;
	#bindElement: HTMLElement;
	#lottieLoaderIcon;

	constructor(options: CopilotReadonlyLoaderOptions)
	{
		super(options);

		this.setEventNamespace('AI.CopilotContextMenu:Loader');
		this.#bindElement = options.bindElement;
	}

	show(): void
	{
		if (!this.#popup)
		{
			this.#initPopup();
			this.#popup.setOffset({
				offsetTop: 6,
			});
		}

		this.#popup.show();
	}

	destroy(): void
	{
		this.#popup.destroy();
		this.#popup = null;
	}

	isShown(): boolean
	{
		return Boolean(this.#popup?.isShown());
	}

	adjustPosition(): void
	{
		this.#popup?.adjustPosition();
	}

	setBindElement(bindElement): void
	{
		this.#popup?.setBindElement(bindElement);
		this.adjustPosition();
	}

	#initPopup(): void
	{
		this.#popup = new Popup({
			content: this.#getPopupContent(),
			bindElement: this.#bindElement,
			cacheable: false,
			minWidth: 282,
			minHeight: 42,
			padding: 6,
			className: 'ai__copilot-scope ai__copilot-context-menu_loader-popup',
			events: {
				onPopupShow: () => {
					this.#lottieLoaderIcon.play();
				},

				onPopupClose: () => {
					this.#lottieLoaderIcon.stop();
				},
			},
		});
	}

	#getPopupContent(): HTMLElement
	{
		const size = 21.5;

		const loaderIcon = Tag.render`
			<div class="" style="width: ${size}px; height: ${size}px;"></div>
		`;

		this.#lottieLoaderIcon = Lottie.loadAnimation({
			container: loaderIcon,
			renderer: 'svg',
			animationData: copilotLottieIcon,
			autoplay: false,
		});

		const cancelBtn = Tag.render`
			<button style="opacity: 1;" class="ai__copilot_loader-cancel-btn">
				${Loc.getMessage('AI_COPILOT_INPUT_LOADER_CANCEL')}
			</button>
		`;

		Event.bind(cancelBtn, 'click', () => {
			this.emit(CopilotContextMenuLoaderEvents.CANCEL);
		});

		return Tag.render`
			<div class="ai__copilot-context-menu-loader-content">
				<div class="ai__copilot_loader-left">
					<div class="ai__copilot-context-menu-loader-icon">
						${loaderIcon}
					</div>
					<div class="ai__copilot-context-menu-loader-text-with-dots">
						<span class="ai__copilot_loader-text">${Loc.getMessage('AI_COPILOT_INPUT_LOADER_TEXT')}</span>
						<div class="ai__copilot-context-menu-loader_dots">
							<div class="dot-flashing"></div>
						</div>
					</div>
				</div>
				${cancelBtn}
			</div>
		`;
	}
}
