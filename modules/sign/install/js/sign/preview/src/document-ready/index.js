import { Dom, Loc, Tag } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Loader } from 'main.loader';
import { BlockArea } from './block-area/index';

import { Navigation } from './navigation/index';

import './style.css';
import { Zoom } from './zoom/index';

export class DocumentReady extends EventEmitter
{
	#layout: {};
	#pages: Array;
	#blocks: Array;
	#navigation: Navigation;
	#zoom: Zoom;
	#loader: Loader;
	#currentPage: Number;

	constructor({ pages, blocks })
	{
		super();
		this.setEventNamespace('BX.Sign.Preview.DocumentReady');

		this.#pages = pages || [];
		this.#blocks = blocks || [];
		this.#navigation = null;
		this.#loader = null;
		this.#zoom = null;
		this.#currentPage = 1;
		this.#layout = {
			error: null,
			container: null,
			imageWrapper: null,
			previewArea: null,
		};
	}

	#getAreaSize()
	{
		let ratio = (this.#pages[0].height / this.#pages[0].width).toString();
		ratio = ratio.split('.').join('');
		let result = `${[...ratio].splice(0, 3).join('')}%`;
		if (ratio === '1')
		{
			result = '100%';
		}

		return result;
	}

	#getNodeImageWrapper()
	{
		if (!this.#layout.imageWrapper)
		{
			this.#layout.imageWrapper = Tag.render`
				<div class="sign-preview__image-container">
					<img src="${this.#pages[0].path}" alt="${this.#pages[0].name}" id="sign-preview_page-image" class="sign-preview__image-container_img">
				</div>
			`;

			this.#drawBlocks(this.#currentPage);
		}

		return this.#layout.imageWrapper;
	}

	#getNavigation()
	{
		if (!this.#navigation)
		{
			this.#navigation = new Navigation({
				totalPages: this.#pages.length,
			});

			EventEmitter.subscribe(this.#navigation, 'showNextPage', (param) => {
				this.#loadPage(param.data - 1, '--show-next-page');
				this.#getZoom().resetAll();
			});

			EventEmitter.subscribe(this.#navigation, 'showPrevPage', (param) => {
				this.#loadPage(param.data - 1, '--show-prev-page');
				this.#getZoom().resetAll();
			});
		}

		return this.#navigation;
	}

	#loadPage(index: Number, direction: String)
	{
		Dom.clean(this.#getNodeImageWrapper());
		if (!this.#pages[index].isLoaded)
		{
			this.#getNavigation().lock();
			this.lock();
		}

		const imageNode = Tag.render`
				<img alt="${this.#pages[index].name}" 
				class="sign-preview__image-container_img ${direction}"
			 	style="display: none;">
			`;

		Dom.append(imageNode, this.#getNodeImageWrapper());
		imageNode.addEventListener('animationend', () => {
			Dom.removeClass(imageNode, direction);
			this.emit('onImageShow');
		}, {
			once: true,
		});

		if (this.#pages[index].prepared)
		{
			imageNode.src = this.#pages[index].path;
			this.#updatePage(index, imageNode);

			return;
		}

		const imgUrl = this.#pages[index].path;
		const preparedImage = new Image();
		preparedImage.crossOrigin = 'Anonymous';
		preparedImage.src = imgUrl;
		preparedImage.onload = () => {
			this.#receiveImg(preparedImage, index, direction, imageNode);
		};

		imageNode.onerror = () => {
			this.#getNavigation().unLock();
			this.unLock();
			this.showError();
		};
	}

	#receiveImg(preparedImage, index, direction, imageNode: Element)
	{
		const canvas = document.createElement('canvas');
		const context = canvas.getContext('2d');

		canvas.width = preparedImage.width;
		canvas.height = preparedImage.height;

		context.drawImage(preparedImage, 0, 0);

		try
		{
			this.#pages[index].path = canvas.toDataURL('image/png');
			this.#pages[index].prepared = true;
			imageNode.src = this.#pages[index].path;

			this.#updatePage(index, imageNode);
		}
		catch (err)
		{
			console.error(`Error: ${err}`);
		}
	}

	#updatePage(param: Number, imageNode: Element)
	{
		Dom.append(imageNode, this.#getNodeImageWrapper());
		imageNode.style.removeProperty('display');
		this.#pages[param].isLoaded = true;
		this.#getNavigation().unLock();
		this.unLock();
		this.#drawBlocks(param + 1);
		this.#currentPage = param + 1;
	}

	#drawBlocks(pageNumber: Number)
	{
		if (!pageNumber)
		{
			return;
		}

		const currentBlocks = [];

		this.#blocks.forEach((block) => {
			if (pageNumber === parseInt(block.position.page))
			{
				// copy object to correct change of styles when flipping previews
				let style = block.style || {};
				style = { ...style };

				style.top = block.position.top + '%';

				style.left = block.position.left + '%';
				style.width = block.position.width + '%';
				style.height = block.position.height + '%';

				let realDocWidth = parseFloat(block.position.realDocumentWidthPx);
				let currentDocWidth = parseFloat(block.position.currentDocumentWithPx);
				let fontSize = parseFloat(block.style['font-size']) || 14;
				if (realDocWidth && currentDocWidth && fontSize)
				{
					style['font-size'] = (fontSize * (currentDocWidth / realDocWidth)) + 'px';
					// hack from css styles (need refactoring)
					let verticalPadding = (5 * (currentDocWidth / realDocWidth)) + 'px';
					let horizontalPadding = (8 * (currentDocWidth / realDocWidth)) + 'px';
					style['padding'] = verticalPadding + ' ' + horizontalPadding;
				}
				const newBlock = new BlockArea({
					id: block.id,
					style: style,
					data: block.data,
				});

				currentBlocks.push(newBlock);

			}
		});

		currentBlocks.forEach((block) => {
			Dom.append(block.render(), this.#getNodeImageWrapper());
		});
	}

	#getNodeImagePreviewAreq()
	{
		if (!this.#layout.previewArea)
		{
			this.#layout.previewArea = Tag.render`
				<div class="sign-preview__image" style="padding-top: ${this.#getAreaSize()}">
					${this.#getNodeImageWrapper()}
				</div>
			`;
		}

		return this.#layout.previewArea;
	}

	#getNodeDocument()
	{
		if (!this.#layout.container)
		{
			this.#layout.container = Tag.render`
				<div class="sign-preview__document-ready">
					<div class="sign-preview__document-background">
						${this.#getNodeImagePreviewAreq()}
					</div>
					<div class="sign-preview__document-controls">
						${this.#getNavigation().render()}
						${this.#getZoom().render()}
					</div>
				</div>
			`;
		}

		return this.#layout.container;
	}

	#getZoom()
	{
		if (!this.#zoom)
		{
			this.#zoom = new Zoom({
				imageWrapper: this.#getNodeImageWrapper(),
				previewArea: this.#getNodeImagePreviewAreq(),
			});
		}

		return this.#zoom;
	}

	#getNodeError()
	{
		if (!this.#layout.error)
		{
			this.#layout.error = Tag.render`
				<div class="sign-preview__image-error">
					${Loc.getMessage('SIGN_CMP_MASTER_TPL_PREVIEW_LOADIN_ERROR')}
				</div>
			`;

			const linkNode = this.#layout.error.getElementsByTagName('span')[0];

			linkNode.addEventListener('click', () => {
				this.#loadPage(this.#currentPage - 1);
			});
		}

		return this.#layout.error;
	}

	showError()
	{
		Dom.clean(this.#getNodeImageWrapper());
		Dom.append(this.#getNodeError(), this.#getNodeImageWrapper());
	}

	lock()
	{
		if (!this.#loader)
		{
			this.#loader = new Loader({
				size: 80,
			});
		}

		this.#loader.show(this.#getNodeImageWrapper());
		Dom.addClass(this.#getNodeImageWrapper(), '--lock');
	}

	unLock()
	{
		if (this.#loader)
		{
			this.#loader.hide(this.#getNodeImageWrapper());
		}

		Dom.removeClass(this.#getNodeImageWrapper(), '--lock');
	}

	render()
	{
		return this.#getNodeDocument();
	}

	#getCurrentShownImageElement(): ?HTMLElement
	{
		return this.#getNodeImageWrapper().querySelector('img.sign-preview__image-container_img');
	}

	updateImageContainerSize()
	{
		const currentPageImageHeight = this.#getCurrentShownImageElement()?.offsetHeight;
		if (currentPageImageHeight)
		{
			Dom.style(
				this.#getNodeImageWrapper(),
				'height',
				`${currentPageImageHeight}px`,
			);
		}
	}

	setPages(pages: Array)
	{
		this.#pages = pages;
	}
}
