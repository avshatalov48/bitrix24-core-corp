import {Dom, Event, Loc, Type, Tag, ajax} from 'main.core';
import { Backend } from 'sign.backend';
import { MessageBox } from 'ui.dialogs.messagebox';
import { Popup } from 'main.popup';
import { ProgressRound, ProgressRoundStatus } from 'ui.progressround';
import { Loader } from 'main.loader';

import Block from './block';
import Resize from './resize';
import { BlockItem, Config, DocumentOptions, MemberItem, PositionType } from './types/document';
import {Button, ButtonColor} from "ui.buttons";
import { Guide } from "sign.tour";
import "ui.info-helper";
import "spotlight";

export class Document
{
	static alreadyInit = false;
	static marginTop = 0;
	static gapBetweenPages = 20;

	#documentId: number;
	#companyId: number;
	#initiatorName: string;
	#entityId: number;
	#blankId: number;
	#pagesMinHeight: number = 0;
	#disableEdit: boolean;
	#closeDemoContent: boolean = false;
	#members: Array<MemberItem>;
	#documentLayout: HTMLElement;
	#documentLayoutRect: DOMRect;
	#resizeArea: Resize;
	#blocks: Array<Block> = [];
	#pagesRects: Array<DOMRect> = [];
	#seams: Array<Array<number, number>> = [];
	#config: Config;

	#helpBtnElementId = 'sign-editor-help-btn';
	#helpBtnHelperArticleCode = '16571388';

	/**
	 * Constructor.
	 * @param {DocumentOptions} options
	 */
	constructor(options: DocumentOptions)
	{
		if (Document.alreadyInit)
		{
			return;
		}

		if (!Type.isDomNode(options.documentLayout))
		{
			throw new Error('Option documentLayout is undefined or not valid DOM Element.');
		}

		Document.alreadyInit = true;
		this.#documentId = options.documentId;
		this.#companyId = options.companyId;
		this.#initiatorName = options.initiatorName;
		this.#entityId = options.entityId;
		this.#blankId = options.blankId;
		this.#documentLayout = options.documentLayout;
		this.#documentLayoutRect = this.#documentLayout.getBoundingClientRect();
		this.#disableEdit = options.disableEdit;
		this.#config = options.config;
		this.#members = options.members.filter(member => member.cid !== 0);
		this.#initPagesRect();

		if (!this.#disableEdit)
		{
			this.#resizeArea = new Resize({ wrapperLayout: this.#documentLayout });
			Dom.append(this.#resizeArea.getLayout(), this.#documentLayout);
		}

		if (options.repositoryItems && !this.#disableEdit)
		{
			this.#initRepository(options.repositoryItems);
		}

		if (options.blocks)
		{
			this.#initBlocks(options.blocks);
		}

		if (options.saveButton)
		{
			this.onSave(options);
		}

		this.onCloseSlider();

		Event.bind(document.body, 'click', (event) => {
			if (event.target.getAttribute('data-role'))
			{
				if (this.#resizeArea)
				{
					const activeBlock = this.#resizeArea.getLinkedBlock();
					if (activeBlock)
					{
						activeBlock.onClickOut();
					}
				}
			}
		});

		if (options.closeDemoContent)
		{
			Event.bind(options.closeDemoContent, 'click', (event) => {
				this.#closeDemoContent = true;
			});
		}

		Event.bind(document.getElementById(this.#helpBtnElementId), 'click', (event: PointerEvent) => {
			top.BX.Helper.show("redirect=detail&code=" + this.#helpBtnHelperArticleCode);
			event.preventDefault();
		});

		if (this.#disableEdit)
		{
			this.#startDisabledEditDocumentTour();
		}
		else
		{
			this.#startEditDocumentTour();
		}
	}

	/**
	 * Detects pages rect.
	 */
	#initPagesRect()
	{
		let top = 0;

		[...this.#documentLayout.querySelectorAll('[data-page] img')].map(page => {
			const pageRect = page.getBoundingClientRect();
			const pageRectToMath = {};

			// round getBoundingClientRect
			for (let pageRectKey in pageRect)
			{
				if (Type.isNumber(pageRect[pageRectKey]))
				{
					pageRectToMath[pageRectKey] = Math.round(pageRect[pageRectKey]);
				}
				else
				{
					pageRectToMath[pageRectKey] = pageRect[pageRectKey];
				}
			}

			// collect seam's gaps
			this.#seams.push([(top === 0) ? 0 : pageRectToMath.top - Document.gapBetweenPages, pageRectToMath.top + Document.marginTop]);

			// correct top after rounding
			if (top === 0)
			{
				top = pageRectToMath.top;
			}
			else
			{
				top += Document.gapBetweenPages;
				pageRectToMath.top = top;
			}

			// collect pages rects
			this.#pagesRects.push(pageRectToMath);

			// remember min page height
			if (!this.#pagesMinHeight || pageRect.height < this.#pagesMinHeight)
			{
				this.#pagesMinHeight = pageRect.height;
			}
		});
	}

	/**
	 * Returns absolute top by relative top and page number.
	 * @param {number} top
	 * @param {number} page
	 * @return {number}
	 */
	#relativeToAbsolute(top: number, page: number): number
	{
		for (let i = 0; i < page - 1; i++)
		{
			top += this.#pagesRects[i].height;
		}

		return top;
	}

	/**
	 * Transfers absolute position to page position and sets page number.
	 * @param {PositionType} position
	 * @return {PositionType}
	 */
	transferPositionToPage(position: PositionType): PositionType
	{
		position.page = 1;

		for (let i = 0, c = this.#pagesRects.length; i < c; i++)
		{
			const height = this.#pagesRects[i].height;

			if (i !== 0) // skip gap for first page
			{
				position.top -= Document.gapBetweenPages;
			}

			position.top -= height;
			if (position.top < 0)
			{
				position.top += height;
				break;
			}
			else
			{
				position.page++;
			}
		}

		return position;
	}

	/**
	 * Transfers pixel of position to percent.
	 * @param {PositionType} position
	 * @return {PositionType}
	 */
	#pixelToPercent(position: PositionType): PositionType
	{
		if (!position.page || typeof this.#pagesRects[position.page - 1] === 'undefined')
		{
			return position;
		}

		const pageImageRect = this.#pagesRects[position.page - 1];

		position.widthPx = position.width;
		position.heightPx = position.height;
		position.left = position.left / pageImageRect.width * 100;
		position.top = position.top / pageImageRect.height * 100;
		position.width = position.width / pageImageRect.width * 100;
		position.height = position.height / pageImageRect.height * 100;

		return position;
	}

	/**
	 * Transfers percent of position to pixel.
	 * @param {PositionType} position
	 * @return {PositionType}
	 */
	#percentToPixel(position: PositionType): PositionType
	{
		if (!position.page || typeof this.#pagesRects[position.page - 1] === 'undefined')
		{
			return position;
		}

		const pageImageRect = this.#pagesRects[position.page - 1];

		if (position.left)
		{
			position.left = position.left * pageImageRect.width / 100;
		}
		if (position.top)
		{
			position.top = position.top * pageImageRect.height / 100;
		}
		if (position.width)
		{
			position.width = position.width * pageImageRect.width / 100;
		}
		if (position.height)
		{
			position.height = position.height * pageImageRect.height / 100;
		}

		return position;
	}

	/**
	 * Returns minimal pages height.
	 * @return {number}
	 */
	getMinPageHeight(): number
	{
		return this.#pagesMinHeight - Document.marginTop;
	}

	/**
	 * Returns document id.
	 * @return {number}
	 */
	getId(): number
	{
		return this.#documentId;
	}

	/**
	 * Returns document entity id.
	 * @return {number}
	 */
	getEntityId(): number
	{
		return this.#entityId;
	}

	/**
	 * Returns document's layout.
	 * @return {HTMLElement}
	 */
	getLayout(): HTMLElement
	{
		return this.#documentLayout;
	}

	/**
	 * Returns number pairs (seams between pages).
	 * @return {Array<Array<number, number>>}
	 */
	getSeams(): Array<Array<number, number>>
	{
		return this.#seams;
	}

	/**
	 * Returns gap between pages.
	 * @return {number}
	 */
	getPagesGap(): number
	{
		return Document.gapBetweenPages + Document.marginTop;
	}

	/**
	 * Returns document's members
	 * @return {Array<MemberItem>}
	 */
	getMembers(): Array<MemberItem>
	{
		return this.#members;
	}

	/**
	 * Returns member item by member part.
	 * @param {number} part
	 * @return {null}
	 */
	getMemberByPart(part: number): ?MemberItem
	{
		let returnMember = null;

		this.#members.map((member: MemberItem) => {
			if (member.part === part)
			{
				returnMember = member;
			}
		});

		return returnMember;
	}

	/**
	 * Returns company id.
	 * @return {number}
	 */
	getCompanyId(): number
	{
		return this.#companyId;
	}

	/**
	 * @return {string}
	 */
	getInitiatorName(): string
	{
		return this.#initiatorName;
	}

	/**
	 * Returns Config object.
	 * @return {Config}
	 */
	getConfig(): Config
	{
		return this.#config;
	}

	/**
	 * Hides resizing area.
	 */
	hideResizeArea()
	{
		this.#resizeArea.hide();
	}

	/**
	 * Shows resizing area over the block.
	 * @param {Block} block
	 */
	showResizeArea(block: Block)
	{
		this.#resizeArea.show(block);
	}

	/**
	 * Mutes resizing area.
	 */
	muteResizeArea()
	{
		this.#resizeArea.getLayout().setAttribute('data-disable', 1);
	}

	/**
	 * Unmutes resizing area.
	 */
	unMuteResizeArea()
	{
		this.#resizeArea.getLayout().removeAttribute('data-disable');
	}

	/**
	 * Adds block to the document.
	 * @param {BlockItem} data Block data.
	 * @param {boolean} seamShift If true, will be added seam shift to top's position.
	 * @return {Block}
	 */
	#addBlock(data: BlockItem, seamShift: boolean): Block
	{
		if (data.position)
		{
			data.position = this.#percentToPixel(data.position);

			if (!data.style['font-size'])
			{
				data.style['font-size'] = '14px';
			}

			if (data?.position['page'] && data?.position['top'])
			{
				data.position['top'] = this.#relativeToAbsolute(
					parseFloat(data.position['top']),
					parseFloat(data.position['page'])
				);

				if (seamShift)
				{
					data.position.top += (data.position.page - 1) * Document.gapBetweenPages;
				}
			}
		}

		const block = new Block({
			id: data.id,
			code: data.code,
			part: data.part,
			document: this,
			data: data.data,
			position: data.position,
			style: data.style,
			onClick: (block: Block) => {
				if (this.#disableEdit)
				{
					return;
				}
				this.#resizeArea.show(block);
				this.unMuteResizeArea();
				block.adjustActionsPanel();
			},
			onRemove: (block: Block) => {
				this.#resizeArea.hide();
			}
		});
		const blockLayout = block.getLayout();

		this.#blocks.push(block);
		Dom.append(blockLayout, this.#documentLayout);

		return block;
	}

	/**
	 * Fires on click repository item.
	 * @param {string} code Block code.
	 * @param {string} part Member part (base - my company, other - third part).
	 */
	#onRepositoryItemClick(code: string, part: number)
	{
		part = parseInt(part);
		this.setSavingMark(false);

		const block = this.#addBlock({ code, part });
		block.assign();
		block.onPlaced();
	}

	/**
	 * Sets exists blocks to the document.
	 * @param blocks
	 */
	#initBlocks(blocks: Array<BlockItem>)
	{
		blocks.map(block => this.#addBlock(block, true));
	}

	/**
	 * Initializes the repository from specified html area with data-attributes.
	 * data-code="*" - block's code
	 * data-action="add" - button to add block to the layout
	 * @param {NodeList} repositoryItems
	 */
	#initRepository(repositoryItems: NodeList)
	{
		[...repositoryItems].map((item: HTMLElement) => {

			const code = item.getAttribute('data-code');
			const part = item.getAttribute('data-part');

			Event.bind(item, 'click', e => {
				this.#onRepositoryItemClick(code, part);
				e.preventDefault();
			});
		});
	}

	/**
	 * Returns blocks collection.
	 * @return {Array<Block>}
	 */
	getBlocks(): Array<Block>
	{
		return this.#blocks;
	}

	/**
	 * Saves document and closes slider.
	 * @return {Promise}
	 */
	save(): Promise<any, any>
	{
		const postData = [];

		const realDocumentWidth = document.querySelector('[data-type="document-layout"]').offsetWidth;

		this.#blocks.map(block => {

			if (block.isRemoved())
			{
				return;
			}

			let position = block.getPosition();

			position = this.transferPositionToPage(position);
			position = this.#pixelToPercent(position);
			position.realDocumentWidthPx = realDocumentWidth ? realDocumentWidth : null;

			postData.push({
				id: block.getId(),
				code: block.getCode(),
				data: block.getData(),
				part: block.getMemberPart(),
				style: block.getStyle(),
				position
			});
		});

		const params = {};
		if (this.#closeDemoContent)
		{
			params['closeDemoContent'] = true;
		}


		return Backend.controller({
				command: 'blank.save',
				postData: {
					documentId: this.#documentId,
					blocks: postData.length > 0 ? postData : [],
					params
				}
			})
			.catch((response) =>
			{
				if (response.errors[0]?.customData?.field === 'requisites')
				{
					const popup = new Popup({
						id: 'sign_document_error_popup',
						titleBar: Loc.getMessage('SIGN_JS_DOCUMENT_REQUISITES_RESTORE_TITLE'),
						content: BX.util.htmlspecialchars(response.errors[0]?.message),
						buttons: [
							new Button({
								text: Loc.getMessage('SIGN_JS_DOCUMENT_REQUISITES_RESTORE'),
								color: ButtonColor.SUCCESS,
								onclick: () => {
									Backend.controller({
										command: 'document.restoreRequisiteFields',
										postData: {
											documentId: this.#documentId,
											presetId: response.errors[0]?.customData.presetId,
											code: response.errors[0]?.customData.code
										}
									}).then(() => {
										this.#blocks.map(block => {
											if (block.isRemoved())
											{
												return;
											}

											if (block.getCode() === response.errors[0]?.customData.code)
											{
												block.assign();
											}
										})
										popup.destroy();
									}).catch(() => {
										popup.destroy();
									});
								},
							}),
							new Button({
								text: Loc.getMessage('SIGN_JS_DOCUMENT_REQUISITES_RESTORE_BY_HANDS'),
								color: ButtonColor.INFO,
								onclick: () => {
									this.#blocks.map(block => {
										if (block.isRemoved())
										{
											return;
										}

										if (block.getCode() === response.errors[0]?.customData.code)
										{
											const blockActionButton = document.querySelector('button[data-id="action-'+block.getCode()+'"]');

											if (blockActionButton)
											{
												blockActionButton.click();
											}
										}
									})
									popup.destroy();
								}
							}),
						],
					});
					popup.show();
				}
			});
	}

	/**
	 * Registers save button.
	 * @param {DocumentOptions} options
	 */
	onSave(options: DocumentOptions)
	{
		Event.bind(options.saveButton, 'click', (e) => {
			if (this.#disableEdit)
			{
				if (options.afterSaveCallback)
				{
					options.afterSaveCallback();
				}
				return;
			}

			this.save()
				.then(result => {
					//todo: we need to parse response and sets id for each new block
					if (result === true)
					{
						this.setSavingMark(true);

						if (options.afterSaveCallback)
						{
							options.afterSaveCallback();
						}
					}

					if (result !== true)
					{
						if (options.saveErrorCallback)
						{
							options.saveErrorCallback();
						}
					}
				})
		});
	}

	/**
	 * Registers callback on slider close.
	 */
	onCloseSlider()
	{
		BX.addCustomEvent('SidePanel.Slider:onClose', (event) => {
			if (event.slider.url.indexOf('/sign/edit/') === 0)
			{
				if (!this.everythingIsSaved())
				{
					event.denyAction();

					MessageBox.confirm(
						Loc.getMessage('SIGN_JS_DOCUMENT_SAVE_ALERT_MESSAGE'),
						Loc.getMessage('SIGN_JS_DOCUMENT_SAVE_ALERT_TITLE'),
						(messageBox: MessageBox) => {
							this.setSavingMark(true);
							messageBox.close();
							event.slider.close();
						},
						Loc.getMessage('SIGN_JS_DOCUMENT_SAVE_ALERT_APPLY'),
						(messageBox: MessageBox) => {
							messageBox.close();
						}
					);
				}
			}
		});
	}

	/**
	 * Sets mark to document that everything was saved or not.
	 * @param {boolean} mark
	 */
	setSavingMark(mark: boolean)
	{
		this.setParam('bxSignEditorAllSaved', mark);
	}

	/**
	 * Returns true, if everything was saved within editor.
	 * @return {boolean}
	 */
	everythingIsSaved(): boolean
	{
		return this.getParam('bxSignEditorAllSaved') === true;
	}

	setParam(name: string, value: any): void
	{
		const slider = BX.SidePanel.Instance.getSliderByWindow(window);
		if (slider)
		{
			slider.getData().set(name, value);
		}
	}

	getParam(name: string): any
	{
		const slider = BX.SidePanel.Instance.getSliderByWindow(window);
		if (slider)
		{
			return slider.getData().get(name);
		}

		return undefined;
	}

	/**
	 * Returns true if element with specified top & bottom over dead zone.
	 * @param {number} top
	 * @param {number} bottom
	 * @param {boolean} notAddMargin
	 * @return {boolean}
	 */
	inDeadZone(top: number, bottom: number, notAddMargin: boolean): boolean
	{
		const seams = this.getSeams();

		if (seams.length > 0 && notAddMargin !== true)
		{
			top += this.#documentLayoutRect.top;
			bottom += this.#documentLayoutRect.top;
		}

		for (let ii = 0, cc = seams.length; ii < cc; ii++)
		{
			const seam = seams[ii];

			// top on page, bottom on seam
			if (top <= seam[0] && bottom >= seam[0] && bottom <= seam[1])
			{
				return seam[0] - bottom;
			}

			// top on one page, bottom on another (seam in a middle)
			if (top <= seam[0] && bottom >= seam[1])
			{
				//seam[0] - bottom >> shift top
				//seam[1] - top >> shift bottom
				return seam[0] - bottom;
			}

			// block into a seam
			if (top >= seam[0] && bottom <= seam[1])
			{
				return seam[0] - bottom;
			}

			// top on seam, bottom on page
			if (top >= seam[0] && top <= seam[1] && bottom >= seam[1])
			{
				return seam[1] - top;
			}

		}

		return 0;
	}

	#startDisabledEditDocumentTour()
	{
		const guide = new Guide({
			steps: [
				{
					target: document.getElementById('sign-editor-btn-edit'),
					title: Loc.getMessage('SIGN_JS_DOCUMENT_TOUR_BTN_EDIT_TITLE'),
					text: Loc.getMessage('SIGN_JS_DOCUMENT_TOUR_BTN_EDIT_TEXT'),
				},
			],
			id: 'sign-tour-guide-onboarding-master-document-edit-disabled',
			autoSave: true,
			simpleMode: true,
		});
		guide.startOnce();
	}

	#startEditDocumentTour()
	{
		const helpBtnSpotlight = new BX.SpotLight({
			targetElement: document.getElementById(this.#helpBtnElementId),
			targetVertex: 'middle-center',
			autoSave: true,
			id: 'sign-spotlight-onboarding-master-document-edit',
		});

		const guide = new Guide({
			steps: [
				{
					target: document.getElementById('sign-editor-bar-content'),
					title: Loc.getMessage('SIGN_JS_DOCUMENT_TOUR_BTN_BLOCKS_TITLE'),
					text: Loc.getMessage('SIGN_JS_DOCUMENT_TOUR_BTN_BLOCKS_TEXT'),
					position: 'left',
				},
				{
					target: document.getElementById(this.#helpBtnElementId),
					title: Loc.getMessage('SIGN_JS_DOCUMENT_TOUR_BTN_HELP_TITLE'),
					text: Loc.getMessage('SIGN_JS_DOCUMENT_TOUR_BTN_HELP_TEXT'),
					rounded: true,
					article: this.#helpBtnHelperArticleCode,
					events: {
						onShow: () => helpBtnSpotlight.show(),
						onClose: () => helpBtnSpotlight.close(),
					},
				},
			],
			id: 'sign-tour-guide-onboarding-master-document-edit',
			autoSave: true,
			simpleMode: true,
		});
		guide.startOnce();
	}
}

export class DocumentMasterLoader
{
	#totalImages: number;
	#progressRound: ProgressRound;
	#layout: {
		overlay: HTMLElement;
		title: HTMLElement;
		error: HTMLElement;
		loader: HTMLElement;
	};
	#loader: Loader

	constructor({ totalImages })
	{
		this.#totalImages = totalImages || null;
		this.#progressRound = null;
		this.#layout = {
			overlay: null,
			title: null,
			error: null,
			loader: null
		}
		this.#loader = null;
	}

	#getProgress()
	{
		if (!this.#progressRound)
		{
			this.#progressRound = new ProgressRound({
				colorBar: '#1ec6fa',
				colorTrack: '#fff',
				statusType: ProgressRoundStatus
					? ProgressRoundStatus.INCIRCLECOUNTER
					: BX.UI.ProgressRound.Status.INCIRCLECOUNTER,
				maxValue: this.#totalImages
			});
		}

		return this.#progressRound;
	}

	#getLoader()
	{
		if (!this.#loader)
		{
			this.#loader = new Loader({
				size: 100,
				color: '#1ec6fa'
			});
		}

		return this.#loader;
	}

	#getOverlay()
	{
		if (!this.#layout.overlay)
		{
			this.#layout.overlay = Tag.render`
				<div class="sign-document__master-loader-overlay">
					${this.#getTitle()}
					${this.#getLoaderWrapper()}
				</div>
			`;
		}

		return this.#layout.overlay;
	}

	#getLoaderWrapper()
	{
		if (!this.#layout.loader)
		{
			this.#layout.loader = Tag.render`
				<div class="sign-document__master-loader-container"></div>
			`;
		}

		return this.#layout.loader;
	}

	#getTitle()
	{
		if (!this.#layout.title)
		{
			this.#layout.title = Tag.render`
				<div class="sign-document__master-loader-title">${Loc.getMessage('SIGN_JS_DOCUMENT_LOADING_DOCUMENT_PAGES')}</div>
			`;
		}

		return this.#layout.title;
	}

	updateLoadPage(param: Number)
	{
		if (Type.isNumber(param))
		{
			this.#getProgress().update(param);
		}
	}

	showError(message: string)
	{
		Dom.remove(this.#getLoaderWrapper());
		this.#getLoader().destroy();
		this.#getTitle().innerHTML = message || Loc.getMessage('SIGN_JS_DOCUMENT_LOAD_ERROR');
		const linkReload = this.#getTitle().getElementsByTagName('span')[0];
		if (linkReload)
		{
			linkReload.addEventListener('click', () => {
				document.location.reload();
			});
		}
	}

	show()
	{
		if (!this.#totalImages)
		{
			console.warn('BX.Sign.DocumentMasterLoader: totalImages is not defined');
			return;
		}

		Dom.append(this.#getOverlay(), document.body);

		if (this.#totalImages > 1)
		{
			this.#getProgress().renderTo(this.#getLoaderWrapper());
		}
		else
		{
			this.#getLoader().show(this.#getLoaderWrapper());
		}
	}

	close()
	{
		this.#getOverlay().addEventListener('animationend', () => {
			Dom.remove(this.#getOverlay());
			this.#getLoader().destroy();
			this.#totalImages = null;
			this.#progressRound = null;
			this.#layout = {
				overlay: null,
				title: null,
				error: null,
				loader: null
			}
			this.#loader = null;
		});

		Dom.addClass(this.#getOverlay(), '--hide');
	}
}
