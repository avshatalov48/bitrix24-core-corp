import { Dom, Loc, Tag, Text as TextFormat, Type } from 'main.core';
import { Document } from 'sign.document';
import { Master } from	'./index';

type PositionType = {
	top: number|string,
	left: number|string,
	width: number|string,
	height: number|string
};

type BlockItem = {
	id?: number,
	code: string,
	part: number,
	data?: any,
	position?: PositionType,
	style?: {[key: string]: string},
	document: Document,
	onClick?: () => {},
	onRemove?: () => {},
};

export type PreviewItem = {
	page: number,
	name: string,
	width: number,
	height: number,
	hash: string,
	path: string
};

export type PreviewOptions = {
	documentHash?: string,
	readonly?: boolean,
	items?: Array<PreviewItem>,
	blocks?: Array<BlockItem>
};

export class Preview
{
	readonly: boolean = true;
	imageIndex: number = 0;
	imageTotal: number = 0;
	imageTag: HTMLImageElement;
	imageCollection: Array<PreviewItem>;
	blockCollection: Array<BlockItem>;
	blockTagCollection: Array<HTMLElement> = [];
	navigationTag: HTMLElement;
	containerTag: HTMLElement;
	currentZoomValue: number = 100;
	defaultZoomValue: number;
	zoomValue: number;
	firstRender: boolean = false;

	constructor(options: PreviewOptions)
	{
		this.containerTag = document.querySelector('[data-role="sign-master__preview"]');

		if (!this.containerTag)
		{
			return;
		}

		this.imageCollection = options.items;
		this.blockCollection = options.blocks;
		this.imageTotal = this.imageCollection.length;

		if (Type.isBoolean(options.readonly))
		{
			this.readonly = options.readonly;
		}

		if (this.imageCollection.length > 0)
		{
			this.buildPreview();
		}
		else if (options.documentHash)
		{
			const interval = setInterval(() => {
				BX.Sign.Backend.controller({
						command: 'document.getLayout',
						postData: {
							documentHash: options.documentHash
						}
					})
					.then((result) => {
						const layout = result?.layout;
						if (Type.isArray(layout) && layout.length > 0)
						{
							this.imageCollection = layout;
							this.imageTotal = this.imageCollection.length;
							this.buildPreview();

							clearInterval(interval);
						}
					});
			}, 2000);
		}
	}

	getBottomContainer()
	{
		if (!this.bottomContainer)
		{
			this.bottomContainer = Tag.render`
				<div class="sign-master__preview-container_bottom"></div>
			`;
		}

		return this.bottomContainer;
	}

	/**
	 * Builds preview area.
	 */
	buildPreview()
	{
		Dom.clean(this.containerTag);

		Dom.append(
			this.buildImage(this.imageCollection[0]),
			this.containerTag
		);
		Dom.append(
			this.buildNavigation(),
			this.getBottomContainer()
		);
		Dom.append(
			this.buildZoom(),
			this.getBottomContainer()
		);
		Dom.append(
			this.getBottomContainer(),
			this.containerTag
		);
		/*Dom.append(
			this.buildRemoveButton(),
			this.containerTag
		);*/
	}

	/**
	 * Draws block element.
	 * @return {HTMLElement}
	 */
	drawBlock(blockData: {text?: string, base64?: string}): HTMLElement
	{
		if (blockData.text)
		{
			const content = blockData.text;
			return Tag.render`<div class="sign-master__block">${TextFormat.encode(content)}</div>`;
		}
		else if (blockData.base64)
		{
			const src = 'data:image;base64,' + blockData.base64;
			const style = `background: url(${src}) no-repeat top; background-size: cover;`;
			return Tag.render`<div class="sign-master__block" style="${style}"></div>`;
		}

		return Tag.render`<div class="sign-master__block"></div>`;
	}

	/**
	 * Draws blocks on preview by page number.
	 * @param {number} pageNumber
	 */
	drawBlocks(pageNumber: number)
	{
		const blocks = [];

		this.blockCollection.map(block => {
			if (pageNumber === parseInt(block.position.page))
			{
				const tag = this.drawBlock(block.data);
				const style = Type.isArray(block.style) || Type.isPlainObject(block.style)
					? {...block.style}
					: {}
				;

				style.top = block.position.top + '%';
				style.left = block.position.left + '%';
				style.width = block.position.width + 14 + '%';
				style.height = block.position.height + 14 + '%';

				Dom.style(tag, style);

				blocks.push(tag);
			}
		});

		setTimeout(() => {
			[...document.querySelectorAll('.sign-master__block')].map(tag => Dom.remove(tag));
			blocks.map(tag => {
				this.imageTagContainer.appendChild(tag);
			});
		}, 0);
	}

	/**
	 * Builds preview image.
	 */
	buildImageTag()
	{
		const preview = this.imageCollection[this.imageIndex];
		
		if (!preview)
		{
			return;
		}

		this.imageTag = Tag.render`<img src="${preview.path}" alt="${preview.name}" class="sign-master__preview-image">`;
		this.imageTagContainer = Tag.render`<div class="sign-master__preview-container_image"></div>`;

		this.imageTag.onload = () => {
			Master.unLockContent();
			Dom.clean(this.imageTagContainer);
			Dom.append(this.imageTag, this.imageTagContainer)
			this.drawBlocks(this.imageIndex + 1);
		};

	}

	/**
	 * Build preview image container.
	 * @return {HTMLElement}
	 */
	buildImage(): HTMLElement
	{
		if (!this.imageTagWrapper)
		{
			this.buildImageTag();

			this.imageTagWrapper = Tag.render`
				<div class="sign-master__preview-container">
					${this.imageTagContainer}
				</div>
			`;
		}

		return this.imageTagWrapper;
	}

	/**
	 * Fires on prev navigation click.
	 */
	onPrevClick()
	{
		if (this.imageIndex > 0)
		{
			this.imageIndex--;
			this.btnNextTag.classList.remove('--disabled');
		}

		if (this.imageIndex === 0)
		{
			this.btnPrevTag.classList.add('--disabled');
		}

		this.navigationTag.classList.add('--lock');

		this.buildImageTag();
		this.buildNavigation();
	}

	/**
	 * Fires on next navigation click.
	 */
	onNextClick()
	{
		if (this.imageIndex < this.imageTotal - 1)
		{
			this.imageIndex++;
			this.btnPrevTag.classList.remove('--disabled');
		}

		if (this.imageIndex === this.imageTotal - 1)
		{
			this.btnNextTag.classList.add('--disabled');
		}

		this.navigationTag.classList.add('--lock');

		this.buildImageTag();
		this.buildNavigation();
	}

	/**
	 * Builds page navigation between images.
	 * @return {HTMLElement}
	 */
	buildNavigation(): HTMLElement
	{
		if (!this.navigationTag)
		{
			this.btnPrevTag = Tag.render`
				<span class="sign-master__preview-nav--btn --prev --disabled" onclick="${this.onPrevClick.bind(this)}"></span>
			`;

			this.btnNextTag = Tag.render`
				<span class="sign-master__preview-nav--btn --next" onclick="${this.onNextClick.bind(this)}"></span>
			`;

			this.navigationTag = Tag.render`
				<div class="sign-master__preview-nav">
					${this.btnPrevTag}
					<div class="sign-master__preview-nav--info">
						<div class="sign-master__preview-nav--info-dark">
							${Loc.getMessage('SIGN_CMP_MASTER_TPL_PREVIEW_PAGE')} 
							<span class="sign-master__preview-nav--current">0</span>
						</div>
						<span>/</span>
						<span class="sign-master__preview-nav--total">0</span>
					</div>
					${this.btnNextTag}
				</div>
			`;
		}

		this.navigationTag.querySelector('.sign-master__preview-nav--current').innerHTML = this.imageIndex + 1;
		this.navigationTag.querySelector('.sign-master__preview-nav--total').innerHTML = this.imageTotal;

		Dom.removeClass(this.navigationTag.querySelector('.sign-master__preview-nav--prev'), 'sign-master__preview-nav--active');
		Dom.removeClass(this.navigationTag.querySelector('.sign-master__preview-nav--next'), 'sign-master__preview-nav--active');

		if (this.imageIndex > 0)
		{
			Dom.addClass(this.navigationTag.querySelector('.sign-master__preview-nav--prev'), 'sign-master__preview-nav--active');
		}
		if (this.imageIndex < this.imageTotal - 1)
		{
			Dom.addClass(this.navigationTag.querySelector('.sign-master__preview-nav--next'), 'sign-master__preview-nav--active');
		}

		return this.navigationTag;
	}

	/**
	 * Fires on zoom page click.
	 */
	onZoomClick()
	{
		const preview = this.imageCollection[this.imageIndex];
		if (!preview)
		{
			return;
		}

		console.log('image path to show: ', preview.path);
	}

	adjustZoomStatus()
	{
		this.zoomValue = this.defaultZoomValue / 100 * this.currentZoomValue;
		this.imageTagContainer.style.setProperty('zoom', this.zoomValue + '%');

		this.zoomLayout.value.innerText = this.currentZoomValue;

		switch (true)
		{
			case this.currentZoomValue > 100 :
				this.imageTagWrapper.classList.add('--scroll');
				break;

			default :
				this.imageTagWrapper.classList.remove('--scroll');
		}

		switch (true)
		{
			case this.currentZoomValue === 100 :
				this.imageTagContainer.style.setProperty('left', 0);
				this.imageTagContainer.style.setProperty('top', 0);
				this.zoomLayout.minus.classList.add('--hold');
				break;

			case this.currentZoomValue === 200 :
				this.zoomLayout.plus.classList.add('--hold');
				break;

			case this.currentZoomValue === 25 :
				this.zoomLayout.minus.classList.add('--hold');
				break;

			default :
				this.zoomLayout.plus.classList.remove('--hold');
				this.zoomLayout.minus.classList.remove('--hold');
		}
	}

	zoomPlus()
	{
		if (this.imageTag)
		{
			this.currentZoomValue += 25;
			this.adjustZoomStatus();
		}
	}

	zoomMinus()
	{
		if (this.imageTag)
		{
			this.currentZoomValue -= 25;
			this.adjustZoomStatus();
		}
	}

	/**
	 * Builds zoom button.
	 * @return {HTMLElement}
	 */
	buildZoom(): HTMLElement
	{
		this.zoomLayout = {
			value: Tag.render`<span class="sign-master__preview-zoom-value">${this.currentZoomValue}</span>`,
			minus: Tag.render`<span class="sign-master__preview-zoom_control --minus" onclick="${this.zoomMinus.bind(this)}"></span>`,
			plus: Tag.render`<span class="sign-master__preview-zoom_control --plus" onclick="${this.zoomPlus.bind(this)}"></span>`
		};

		return this.zoomLayout.container = Tag.render`
			<span class="sign-master__preview-zoom">
				${this.zoomLayout.minus}
				${this.zoomLayout.value}
				${this.zoomLayout.plus}
			</span>
		`;
	}

	/**
	 * Fires on remove page click.
	 */
	onRemoveClick()
	{
		const preview = this.imageCollection[this.imageIndex];
		if (!preview)
		{
			return;
		}
	}

	/**
	 * Builds remove button.
	 * @return {HTMLElement}
	 */
	buildRemoveButton(): HTMLElement
	{
		if (this.readonly)
		{
			return Tag.render`
				<span class="sign-master__preview-remove --readonly" title="${Loc.getMessage('SIGN_CMP_MASTER_TPL_PREVIEW_REMOVE_ALERT')}" >
					${Loc.getMessage('SIGN_CMP_MASTER_TPL_PREVIEW_REMOVE')}
				</span>
			`;
		}
		else
		{
			return Tag.render`
				<span class="sign-master__preview-remove" onclick="${this.onRemoveClick.bind(this)}">
					${Loc.getMessage('SIGN_CMP_MASTER_TPL_PREVIEW_REMOVE')}
				</span>
			`;
		}
	}
}
