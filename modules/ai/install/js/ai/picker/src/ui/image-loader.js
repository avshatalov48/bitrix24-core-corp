import { Tag, Type, Dom } from 'main.core';

import { Base } from './base';

import '../css/ui/image-loader.css';

type ImageLoaderProps = {
	text: string;
}

export class ImageLoader extends Base
{
	#text: string;
	#textElem: HTMLElement | null;
	#container: HTMLElement | null;

	#isAnimate: boolean;

	constructor(props: ImageLoaderProps = {}) {
		super(props);

		this.#text = Type.isString(props?.text) ? props.text : '';
		this.#textElem = null;
		this.#container = null;
		this.#isAnimate = false;
	}

	render(): HTMLElement
	{
		this.#textElem = this.#renderTextContainer();

		this.#container = Tag.render`
			<div class="ai__picker_image-loader-container ${this.#isAnimate ? '--animating' : ''}">
				<div class="ai__picker_image-loader">
					<div class="ai__picker_image-loader-star ai__picker_image-loader-right-star --pulse"></div>
					<div class="ai__picker_image-loader-left-star-container">
						<div class="ai__picker_image-loader-left-star ai__picker_image-loader-star --pulse"></div>
					</div>
					<div class="ai__picker_image-loader-square">
						<div class="ai__picker_image-loader-square-image"></div>
						<div class="ai__picker_image-loader-square-star ai__picker_image-loader-star --pulse"></div>
						<div class="ai__picker_image-loader-square-loader-line"></div>
					</div>
				</div>
				<div class="ai__picker_image-loader-text-container">
					${this.#textElem}
				</div>
			</div>
		`;

		return this.#container;
	}

	getLayout(): HTMLElement
	{
		return this.#container;
	}

	setText(text: string): void
	{
		this.#text = text;

		if (this.#textElem)
		{
			this.#textElem.innerText = text;
		}
	}

	start(): void
	{
		this.#isAnimate = true;
		if (this.#container)
		{
			Dom.addClass(this.#container, '--animating');
		}
	}

	stop(): void
	{
		this.#isAnimate = false;
		if (this.#container)
		{
			Dom.removeClass(this.#container, '--animating');
		}
	}

	#renderTextContainer(): HTMLElement
	{
		return Tag.render`<div class="ai__picker_image-loader-text">${this.#text}</div>`;
	}
}
