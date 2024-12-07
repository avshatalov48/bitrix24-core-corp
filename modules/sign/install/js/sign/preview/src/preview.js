import { Dom, Tag, Type } from 'main.core';
import { EventEmitter } from 'main.core.events';

import { DocumentEmpty } from './document-empty/index';
import { DocumentLoading } from './document-loading/index';
import { DocumentReady } from './document-ready/index';

import 'ui.design-tokens';
import 'ui.font.opensans';
import './style.css';

export class Preview extends EventEmitter
{
	#renderTo: HTMLElement;
	#layout: Array;
	#pages: Array;
	#blocks: Array;
	#documentHash: String;
	#secCode: String;
	#documentReady: DocumentReady;

	constructor({ renderTo, pages, blocks, documentHash, secCode }): void
	{
		super();
		this.setEventNamespace('BX.Sign.Preview');
		this.#renderTo = renderTo || null;
		this.#pages = pages || [];
		this.#blocks = blocks || [];
		this.#documentHash = documentHash || null;
		this.#secCode = secCode || null;
		this.#documentReady = null;
		this.#layout = {
			document: null,
			footer: null,
		};
		this.currentWidth = null;
		this.init();
	}

	showDocumentLoading()
	{
		Dom.clean(this.#getNodeDocument());
		Dom.append(DocumentLoading.render(), this.#getNodeDocument());
	}

	showDocumentEmpty()
	{
		Dom.clean(this.#getNodeDocument());
		Dom.append(DocumentEmpty.render(), this.#getNodeDocument());
	}

	showDocumentReady()
	{
		Dom.clean(this.#getNodeDocument());

		this.#getDocumentReady().setPages(this.#pages);
		Dom.append(this.#getDocumentReady().render(), this.#getNodeDocument());

		this.#getDocumentReady().updateImageContainerSize();
	}

	#getDocumentReady()
	{
		if (!this.#documentReady)
		{
			this.#blocks.forEach((item) => {
				item.position.currentDocumentWithPx = this.currentWidth;
			});

			this.#documentReady = new DocumentReady({
				pages: this.#pages,
				blocks: this.#blocks,
			});
		}

		return this.#documentReady;
	}

	#getNodeDocument()
	{
		if (!this.#layout.document)
		{
			this.#layout.document = Tag.render`
				<div class="sign-preview__document"></div>
			`;
		}

		return this.#layout.document;
	}

	#getNodeFooter()
	{
		if (!this.#layout.footer)
		{
			this.#layout.footer = Tag.render`
				<div class="sign-preview__footer"></div>
			`;
		}

		return this.#layout.footer;
	}

	#loadFirstImage()
	{
		const imgUrl = this.#pages[0].path;
		const preparedImage = new Image();
		preparedImage.crossOrigin = 'Anonymous';
		preparedImage.src = imgUrl;
		preparedImage.addEventListener('load', this.#receiveImg.bind(this, preparedImage), false);

		preparedImage.onerror = () => {
			this.emit('firstImageIsLoadedFail');
		};
	}

	#receiveImg(preparedImage)
	{
		const canvas = document.createElement('canvas');
		const context = canvas.getContext('2d');

		canvas.width = preparedImage.width;
		canvas.height = preparedImage.height;

		context.drawImage(preparedImage, 0, 0);

		try
		{
			this.#pages[0].path = canvas.toDataURL('image/png');
			this.#pages[0].prepared = true;
			this.emit('firstImageIsLoaded');
			// fix block positioning
			setTimeout(() => this.#getDocumentReady().updateImageContainerSize(), 0);
		}
		catch (err)
		{
			console.error(`Error: ${err}`);
		}
	}

	afterRender()
	{
		this.#getDocumentReady().subscribe('onImageShow', () => {
			const documentReady = this.#getDocumentReady();
			documentReady.updateImageContainerSize();
		});
		if (this.#documentHash && this.#pages.length === 0 && BX.Sign.Backend)
		{
			this.showDocumentLoading();
			const checkReady = setInterval(() => {
				BX.ajax.runAction('sign.api.document.layoutIsReady', {
					data: {
						documentHash: this.#documentHash,
						secCode: this.#secCode,
					},
				});
			}, 1000 * 30);

			if (BX.PULL)
			{
				BX.PULL.subscribe({
					moduleId: 'sign',
					command: 'layoutIsReady',
					callback: (result) => {
						if (result?.layout)
						{
							const layout = result?.layout;
							const blocks = result?.blocks;

							if (Type.isArray(layout) && layout.length > 0)
							{
								this.#pages = layout;
								this.#blocks = blocks;

								this.subscribe('firstImageIsLoaded', () => {
									this.showDocumentReady();
								});
								this.subscribe('firstImageIsLoadedFail', () => {
									this.showDocumentReady();
									this.#getDocumentReady().showError();
								});
								this.#loadFirstImage();
							}
							clearInterval(checkReady);
						}
					},
				});
			}
		}
	}

	init()
	{
		if (!this.#renderTo)
		{
			console.warn('BX.Sign.Preview: \'renderTo\' is not defined');
			return;
		}

		if (!this.currentWidth)
		{
			this.currentWidth = this.#renderTo.offsetWidth - 20;
		}

		const target = this.#renderTo.parentNode;
		const nodeWrapper = Tag.render`
			<div class="sign-preview sign-preview__scope">
				<div class="sign-preview__body">
					${this.#getNodeDocument()}
				</div>
				${this.#getNodeFooter()}
			</div>
		`;

		if (this.#pages.length > 0)
		{
			this.showDocumentLoading();
			this.#loadFirstImage();
			this.subscribe('firstImageIsLoaded', () => {
				this.showDocumentReady();
			});
			this.subscribe('firstImageIsLoadedFail', () => {
				this.showDocumentReady();
				this.#getDocumentReady().showError();
			});
		}
		else
		{
			this.showDocumentEmpty();
		}

		Dom.clean(target);
		Dom.append(nodeWrapper, target);
		this.afterRender();
	}
}
