import { Base } from '../ui/base';
import { ImageLoader } from '../ui/image-loader';
import { bind, Dom, Tag, Type } from 'main.core';

export const HistoryImageGroupItemState = Object.freeze({
	EMPTY: 'empty',
	ERROR: 'error',
	GENERATING: 'generating',
	IN_LINE_FOR_GENERATING: 'in_line_for_generating',
	IMAGE_LOADING: 'image_loading',
	IMAGE_LOADING_SUCCESS: 'image_loading_success',
	IMAGE_LOADING_ERROR: 'image_loading_error',
});

type HistoryImageGroupItemProps = {
	image: string;
	state: string;
	onSelect: Function;
}

export class HistoryImageGroupItem extends Base
{
	#image: string | null;
	#state: string;

	#loader: ImageLoader;
	#imageElement: HTMLElement | null;
	#itemElement: HTMLElement | null;
	#onSelect: Function;

	constructor(props: HistoryImageGroupItemProps) {
		super(props);

		this.#onSelect = Type.isFunction(props.onSelect) ? props.onSelect : null;
		this.#state = props.state;
		this.setImage(props.image);

		this.#loader = new ImageLoader();
		this.#itemElement = null;
		this.#imageElement = null;
	}

	render(): HTMLElement
	{
		this.#imageElement = this.#renderImageElement();

		this.#itemElement = Tag.render`
			<div class="ai__picker_history-image-group-item --empty">
				<div class="ai__picker_history-image-group-item-controls">
					${this.#renderActionButton()}
				</div>
				${this.#renderLoader()}
				${this.#imageElement}
			</div>
		`;

		return this.#itemElement;
	}

	setImage(image: string)
	{
		this.#image = image;

		if (this.#imageElement)
		{
			this.#state = this.#image
				? HistoryImageGroupItemState.IMAGE_LOADING
				: HistoryImageGroupItemState.IMAGE_LOADING_ERROR
			;
			if (this.#state === HistoryImageGroupItemState.IMAGE_LOADING_ERROR)
			{
				Dom.removeClass(this.#itemElement, '--empty');
				Dom.addClass(this.#itemElement, '--error');
			}
			this.#imageElement.setAttribute('src', this.#image);
		}
	}

	getImage(): string
	{
		return this.#image;
	}

	getState(): string
	{
		return this.#state;
	}

	isEmpty(): boolean
	{
		return this.#state === HistoryImageGroupItemState.EMPTY;
	}

	isInQueue(): boolean
	{
		return this.#state === HistoryImageGroupItemState.IN_LINE_FOR_GENERATING;
	}

	isGenerating(): boolean
	{
		return this.#state === HistoryImageGroupItemState.GENERATING;
	}

	setGeneratingState()
	{
		this.#state = HistoryImageGroupItemState.GENERATING;

		this.#loader.start();
	}

	#renderActionButton(): HTMLElement
	{
		const actionUseBtnClassname = 'ai__picker_text-history-item-action-btn --paste --accent';

		const useBtn = Tag.render`
			<button
				class="${actionUseBtnClassname}"
			>
				<span class="ai__picker_text-history-item-action-icon"></span>
				${this.getMessage('action_use')}
			</button>
		`;

		bind(useBtn, 'click', async () => {
			try
			{
				this.#onSelect(this.#prepareImageToSelect(this.#image));
			}
			catch (err)
			{
				console.error(err);
			}
		});

		return useBtn;
	}

	/**
	 * Prevent CORS error, see 180894
	 * @param image
	 * @return {*}
	 */
	#prepareImageToSelect(image: string)
	{
		const url = new URL(image);
		url.searchParams.set('t', Date.now());

		return url.href;
	}

	#renderLoader(): HTMLElement
	{
		if (this.#state === HistoryImageGroupItemState.GENERATING)
		{
			this.#loader.start();
		}

		return Tag.render`
			<div class="ai__picker_history-image-group-item-loader">
				${this.#loader.render()}
			</div>
		`;
	}

	#renderImageElement(): HTMLElement
	{
		const imageElement = Tag.render`
			<img
				loading="lazy" 
				class="ai__picker_history-image-group-item-image"
			/>
		`;

		if (this.#state !== HistoryImageGroupItemState.GENERATING)
		{
			imageElement.setAttribute('src', this.#image);
		}

		imageElement.onload = () => {
			this.#state = HistoryImageGroupItemState.IMAGE_LOADING_SUCCESS;
			Dom.removeClass(this.#itemElement, '--empty');
			Dom.removeClass(this.#itemElement, '--error');
			this.#loader.getLayout().remove();
		};

		imageElement.onerror = () => {
			if (this.#state === HistoryImageGroupItemState.GENERATING || this.#state === HistoryImageGroupItemState.EMPTY)
			{
				this.#state = HistoryImageGroupItemState.IMAGE_LOADING_ERROR;
				Dom.removeClass(this.#itemElement, '--empty');
				Dom.addClass(this.#itemElement, '--error');
				this.#loader.getLayout().remove();
			}
		};

		return imageElement;
	}
}
