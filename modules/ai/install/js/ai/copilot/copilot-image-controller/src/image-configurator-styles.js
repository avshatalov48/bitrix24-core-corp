import type { ImageCopilotStyle } from 'ai.engine';
import { Tag, Loc, Event, Dom } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';

import './css/image-configurator-styles.css';

const ImageConfiguratorStylesEvents = Object.freeze({
	select: 'select',
});

type ImageConfiguratorStylesOptions = {
	styles: ImageCopilotStyle[];
}

export class ImageConfiguratorStyles extends EventEmitter
{
	#mainStylesCount: number = 0;
	#currentMainStylesCount: number = 9;
	#selectedStyle: string | null = null;
	#isExpanded: boolean = false;
	#styleList: HTMLElement | null = null;
	#container: HTMLElement | null = null;
	#styles: ImageCopilotStyle[];

	constructor(options: ImageConfiguratorStylesOptions)
	{
		super(options);

		this.#styles = options.styles;

		this.#mainStylesCount = this.#styles.length;

		this.#selectedStyle = this.#styles[0].code;
		this.setEventNamespace('AI.Copilot.ImageConfiguratorStyles');
	}

	getSelectedStyle(): string
	{
		return this.#selectedStyle;
	}

	render(): HTMLElement
	{
		this.#container = Tag.render`
			<div class="ai__image-configurator-styles">
				${this.#renderHeader()}
				${this.#renderStylesList()}
			</div>
		`;

		requestAnimationFrame(() => {
			const styleListStyles = getComputedStyle(this.#styleList);
			const paddingTop = styleListStyles.getPropertyValue('padding-top');
			const paddingBottom = styleListStyles.getPropertyValue('padding-bottom');
			const padding = parseFloat(paddingTop) + parseFloat(paddingBottom);

			Dom.style(this.#styleList, 'height', `${this.#styleList.offsetHeight - padding + 4}px`);
		});

		return this.#container;
	}

	#renderHeader(): HTMLElement
	{
		const expandListBtn = this.#isShowExpandBtn() ? this.#renderExpandListBtn() : null;

		return Tag.render`
			<header class="ai__image-configurator-styles_header">
				<div
					class="ai__image-configurator-styles_title"
					title="${Loc.getMessage('AI_COPILOT_IMAGE_POPULAR_STYLES')}"
				>
					${Loc.getMessage('AI_COPILOT_IMAGE_POPULAR_STYLES')}
				</div>
				${expandListBtn}
			</header>
		`;
	}

	#isShowExpandBtn(): boolean
	{
		return this.#mainStylesCount > this.#currentMainStylesCount;
	}

	#renderExpandListBtn(): HTMLElement
	{
		const expandListBtn = Tag.render`
			<div
				class="ai__image-configurator-styles_all-styles"
				title="${Loc.getMessage('AI_COPILOT_IMAGE_ALL_STYLES')}"
			>
				${Loc.getMessage('AI_COPILOT_IMAGE_ALL_STYLES')}
			</div>
		`;

		Event.bind(expandListBtn, 'click', () => {
			this.#isExpanded = !this.#isExpanded;

			if (this.#isExpanded)
			{
				Dom.addClass(this.#styleList, '--expanded');
				this.#currentMainStylesCount = Object.values(this.#styles).length;
				this.#styleList.innerHTML = '';
				this.#styleList.append(...this.#renderStyleItems());
			}
			else
			{
				Dom.removeClass(this.#styleList, '--expanded');
			}
		});

		return expandListBtn;
	}

	#renderStylesList(): HTMLElement
	{
		this.#styleList = Tag.render`
			<div class="ai__image-configurator-styles_list">
				${this.#renderStyleItems()}
			</div>
		`;

		return this.#styleList;
	}

	#renderStyleItems(): HTMLElement[]
	{
		return this.#styles.slice(0, this.#currentMainStylesCount).map((styleItem) => {
			return this.#renderStyleItem(styleItem);
		});
	}

	#renderStyleItem(style: ImageCopilotStyle): HTMLElement
	{
		const radioButton = Tag.render`
			<input
				${style.code === this.#selectedStyle ? 'checked' : ''}
				id="${style.code}"
				name="ai__image-configurator-style"
				type="radio"
				class="ai__image-configurator-style_item-radio-btn"
			/>
		`;
		const item = Tag.render`
			<div
				title="${style.name}"
				class="ai__image-configurator-styles_item"
			>
				${radioButton}
				<label
					for="${style.code}"
					class="ai__image-configurator-styles_item-inner"
					style="background-image: url(${style.preview})"
				>
					<div class="ai__image-configurator-styles_item-title">${style.name}</div>
				</label>
			</div>
		`;

		Event.bind(radioButton, 'input', () => {
			this.#selectedStyle = style.code;
			this.emit(ImageConfiguratorStylesEvents.select, new BaseEvent({
				data: style.code,
			}));
		});

		return item;
	}
}
