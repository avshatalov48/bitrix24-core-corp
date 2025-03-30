import { Dom, Event, Loc, Text, Type, Cache } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { Popup } from 'main.popup';
import { Backend } from 'sign.backend';
import { Guide } from 'sign.tour';
import { Api } from 'sign.v2.api';
import 'spotlight';
import type { DocumentInitiatedType } from 'sign.type';
import { Button, ButtonColor } from 'ui.buttons';
import { MessageBox } from 'ui.dialogs.messagebox';
import 'ui.info-helper';
import { UI } from 'ui.notification';
import { BlockItem, Config, DocumentOptions, MemberItem, PositionType } from '../types/document';
import type { FieldSelectEvent } from '../types/events/FieldSelectEvent';
import Block from './block';
import Resize from './resize';

const marginTop = 0;
const gapBetweenPages = 20;

type EventListenerCache = Map<Block, {
	subscription: (Block) => void,
	eventName: string,
}>;

export class BlocksManager
{
	#documentId: number;
	#documentUid: string;
	#companyId: number;
	#initiatorName: string;
	#entityId: number;
	#blankId: number;
	#pagesMinHeight: number = 0;
	#disableEdit: boolean;
	#closeDemoContent: boolean = false;
	#members: Array<MemberItem>;
	#documentLayout: HTMLElement;
	resizeArea: Resize;
	#languages: { [key: string]: { NAME: string; IS_BETA: boolean; } } = {};
	#blocks: Array<Block> = [];
	#eventListeners: { [eventListenerCacheName: string]: EventListenerCache } = {
		blockColorChange: new Map(),
		blockReferenceFieldSelect: new Map(),
		blockB2eReferenceFieldSelect: new Map(),
	};
	#pagesRects: Array<DOMRect> = [];
	#seams: Array<Array<number, number>> = [];
	#config: Config;
	#helpBtnElementId = 'sign-editor-help-btn';
	#helpBtnHelperArticleCode = '16571388';
	#api: Api;
	#lastContainerSize;
	#cache: Cache.MemoryCache = new Cache.MemoryCache();
	isTemplateMode: boolean = false;
	documentInitiatedByType: DocumentInitiatedType | null = null;

	/**
	 * Constructor.
	 * @param {DocumentOptions} options
	 */
	constructor(options: DocumentOptions)
	{
		const { documentLayout, disableEdit, isTemplateMode, documentInitiatedByType } = options;
		this.isTemplateMode = Boolean(isTemplateMode);
		this.documentInitiatedByType = documentInitiatedByType;
		this.#documentLayout = documentLayout;
		this.#disableEdit = disableEdit;
		if (!this.#disableEdit)
		{
			this.resizeArea = new Resize({ wrapperLayout: this.#documentLayout });
			Dom.append(this.resizeArea.getLayout(), this.#documentLayout);
		}

		Event.bind(documentLayout, 'click', ({ target }) => {
			const preventClickOut = target.closest('.sign-document__block-wrapper') ||
				target.closest('.sign-document__resize-area');
			if (preventClickOut)
			{
				return;
			}

			const activeBlock = this.resizeArea.getLinkedBlock();
			activeBlock?.onClickOut();
		});
		this.#languages = options.languages;
		this.#api = new Api();
		this.#lastContainerSize = {
			width: 0,
			height: 0,
		};
	}

	initPagesRect()
	{
		let top = 0;
		const pages = [...this.#documentLayout.querySelectorAll('.sign-editor__document_page img')];
		this.#pagesRects = [];
		this.#seams = [];
		const content = document.querySelector('.sign-editor__content');
		pages.forEach(page => {
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

			const topBound = top === 0 ? 0 : pageRectToMath.top - gapBetweenPages + content.scrollTop;
			const bottomBound = pageRectToMath.top + marginTop + content.scrollTop;
			// collect seam's gaps
			this.#seams.push([topBound, bottomBound]);

			// correct top after rounding
			if (top === 0)
			{
				top = pageRectToMath.top;
			}
			else
			{
				top += gapBetweenPages;
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
		this.#documentLayout.style.width = `${this.#documentLayout.offsetWidth}px`;
	}

	/**
	 * Transfers absolute position to page position and sets page number.
	 * @param {PositionType} position
	 * @return {PositionType}
	 */
	transferPositionToPage(position: PositionType): PositionType
	{
		position.page = 1;

		for (
			let i = 0,
				c = this.#pagesRects.length; i < c; i++
		)
		{
			const height = this.#pagesRects[i].height;

			if (i !== 0) // skip gap for first page
			{
				position.top -= gapBetweenPages;
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
	#pixelToPercent(position: PositionType, pageRect): PositionType
	{
		position.widthPx = position.width;
		position.heightPx = position.height;
		position.left = position.left * 100 / pageRect.width;
		position.top = position.top * 100 / pageRect.height;
		position.width = position.width / pageRect.width * 100;
		position.height = position.height / pageRect.height * 100;

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
		const pixelPosition = {};

		if (typeof position.left === 'number')
		{
			pixelPosition.left = position.left * pageImageRect.width / 100;
		}
		if (typeof position.top === 'number')
		{
			let top = position.top * pageImageRect.height / 100;
			let pageNum = 0;
			while (pageNum < position.page - 1)
			{
				top += this.#pagesRects[pageNum].height + gapBetweenPages;
				pageNum++;
			}

			pixelPosition.top = top;
		}
		if (typeof position.width === 'number')
		{
			pixelPosition.width = position.width * pageImageRect.width / 100;
		}
		if (typeof position.height === 'number')
		{
			pixelPosition.height = position.height * pageImageRect.height / 100;
		}

		return pixelPosition;
	}

	/**
	 * Returns minimal pages height.
	 * @return {number}
	 */
	getMinPageHeight(): number
	{
		return this.#pagesMinHeight - marginTop;
	}

	/**
	 * Returns document id.
	 * @return {number}
	 */
	getDocumentId(): number
	{
		return this.#documentId;
	}

	setDocumentId(documentId: number): self
	{
		this.#documentId = documentId;

		return this;
	}

	getDocumentUid(): string
	{
		return this.#documentUid;
	}

	setEditorContent(editorContent: HTMLElement)
	{
		this.resizeArea.setFullEditorContent(editorContent);
	}

	setDocumentUid(documentUid: string): BlocksManager
	{
		this.#documentUid = documentUid;
		return this;
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
		return gapBetweenPages + marginTop;
	}

	addMembers(members: Array<MemberItem>)
	{
		this.#members = members;
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
		this.resizeArea.hide();
	}

	/**
	 * Shows resizing area over the block.
	 * @param {Block} block
	 */
	showResizeArea(block: Block)
	{
		this.resizeArea.show(block);
	}

	/**
	 * Mutes resizing area.
	 */
	muteResizeArea()
	{
		this.resizeArea.getLayout().setAttribute('data-disable', 1);
	}

	/**
	 * Unmutes resizing area.
	 */
	unMuteResizeArea()
	{
		this.resizeArea.getLayout().removeAttribute('data-disable');
	}

	/**
	 * Adds block to the document.
	 * @param {BlockItem} data Block data.
	 * @param {boolean} seamShift If true, will be added seam shift to top's position.
	 * @return {Block}
	 */
	#addBlock(data: BlockItem, seamShift: boolean): Block
	{
		const position = data.position ? this.#percentToPixel(data.position) : null;
		const newBlock = new Block({
			id: data.id,
			code: data.code,
			party: data.party,
			blocksManager: this,
			data: data.data,
			position,
			style: data.style,
			onClick: (block: Block) => {
				if (this.#disableEdit)
				{
					return;
				}
				this.resizeArea.show(block);
				this.unMuteResizeArea();
				block.adjustActionsPanel();
			},
			onRemove: (block: Block) => {
				this.resizeArea.hide();
			},
		});
		const blockLayout = newBlock.getLayout();
		this.#subscribeOnBlockEvents(newBlock);

		if (['sign', 'mysign'].includes(newBlock.getCode()))
		{
			const existedSignBlock: Block | undefined = this.#blocks.find(block => block.getCode() === newBlock.getCode());
			const signColor: string | undefined = existedSignBlock?.getStyle?.()?.color;
			if (!Type.isNil(signColor))
			{
				newBlock.updateColor(signColor, false);
			}
		}

		this.#blocks.push(newBlock);

		Dom.append(blockLayout, this.#documentLayout);

		return newBlock;
	}

	#subscribeOnBlockEvents(newBlock: Block): void
	{
		const newBlockCode = newBlock.getCode();
		if (['sign', 'mysign'].includes(newBlockCode))
		{
			const colorChangeListener = (event) => this.#onBlockColorStyleChange(newBlock, event);
			this.#eventListeners.blockColorChange.set(
				newBlock,
				{ subscription: colorChangeListener, eventName: newBlock.events.onColorStyleChange },
			);
			newBlock.subscribe(
				newBlock.events.onColorStyleChange,
				colorChangeListener,
			);
		}
		else if (['myreference', 'reference'].includes(newBlockCode))
		{
			const onFieldSelectListener = (event: FieldSelectEvent) => this.#onReferenceBlockFieldSelect(
				newBlock,
				event,
			);
			this.#eventListeners.blockReferenceFieldSelect.set(
				newBlock,
				{ subscription: onFieldSelectListener, eventName: 'onFieldSelect' },
			);
			newBlock.subscribe(
				'onFieldSelect',
				onFieldSelectListener,
			);
		}
		else if (['b2ereference', 'myb2ereference', 'employeedynamic', 'hcmlinkreference'].includes(newBlockCode))
		{
			const onFieldSelectListener = (event: FieldSelectEvent) => this.#onB2eReferenceBlockFieldSelect(
				newBlock,
				event,
			);

			this.#eventListeners.blockB2eReferenceFieldSelect.set(
				newBlock,
				{ subscription: onFieldSelectListener, eventName: 'onFieldSelect' },
			);
			newBlock.subscribe(
				'onFieldSelect',
				onFieldSelectListener,
			);
		}
	}

	/**
	 * Fires on click repository item.
	 * @param {string} code Block code.
	 * @param {string} part Member part (base - my company, other - third part).
	 */
	#onRepositoryItemClick(code: string, party: number)
	{
		party = parseInt(party);
		this.setSavingMark(false);

		const block = this.#addBlock({ code, party });
		block.assign();
		block.onPlaced();
	}

	/**
	 * Sets exists blocks to the document.
	 * @param blocks
	 */
	initBlocks(blocks: Array<BlockItem>)
	{
		this.#unsubscribeFromBlocksEvents();
		this.#blocks = [];
		this.hideResizeArea();
		blocks.map((block) => this.#addBlock(block, true));
	}

	initRepository(repositoryItems: NodeList)
	{
		[...repositoryItems].forEach((item: HTMLElement) => {
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

	getLanguages(): {[key: string]: { NAME: string; IS_BETA: boolean; }}
	{
		return this.#languages;
	}

	/**
	 * Saves document and closes slider.
	 * @return {Promise}
	 */
	save(): Promise<any, any>
	{
		const postData = [];

		const realDocumentWidth = document.querySelector('.sign-editor__document').offsetWidth;

		this.#blocks.map(block => {

			if (block.isRemoved())
			{
				return;
			}

			const blockLayout = block.getLayout();
			const blockRect = blockLayout.getBoundingClientRect();
			const pages = [...this.#documentLayout.querySelectorAll('.sign-editor__document_page img')];
			const pagesRect = pages.map((page) => {
				const { top, left, width, height } = page.getBoundingClientRect();

				return {
					top,
					left,
					width,
					height,
				};
			});
			const nextPageRect = pagesRect.find((pageRect) => {
				if (Math.round(blockRect.top) < Math.round(pageRect.top))
				{
					return true;
				}

				return false;
			});
			const pageNum = nextPageRect ? pagesRect.indexOf(nextPageRect) : pagesRect.length;
			const currentPageRect = pagesRect[pageNum - 1];
			let position = {
				top: blockRect.top - currentPageRect.top,
				left: blockRect.left - currentPageRect.left,
				width: blockRect.width,
				height: blockRect.height,
				page: pageNum,
			};
			position = this.#pixelToPercent(position, currentPageRect);
			position.realDocumentWidthPx = realDocumentWidth ? realDocumentWidth : null;
			const code = block.getCode();
			let type = 'text';
			switch (code)
			{
				case 'stamp':
				case 'mystamp':
				case 'sign':
				case 'mysign':
					type = 'image';
			}
			postData.push({
				id: block.getId(),
				code: block.getCode(),
				data: block.getData(),
				party: block.getMemberPart(),
				style: block.getStyle(),
				position,
				type,
			});
		});

		const params = {};
		if (this.#closeDemoContent)
		{
			params['closeDemoContent'] = true;
		}
		this.hideResizeArea();

		return this.#api.saveBlank(
			this.getDocumentUid(),
			postData.length > 0 ? postData : [],
		).then((response) => {

			if (!('errors' in response))
			{
				return postData;
			}

			if (response.errors[0].customData?.field === 'requisites')
			{
				const popup = new Popup({
					id: 'sign_document_error_popup',
					titleBar: Loc.getMessage('SIGN_JS_DOCUMENT_REQUISITES_RESTORE_TITLE'),
					content: BX.util.htmlspecialchars(response.errors[0]?.message),
					buttons: [
						new Button({
							text: Loc.getMessage('SIGN_JS_DOCUMENT_REQUISITES_RESTORE'),
							color: ButtonColor.SUCCESS,
							onclick: ({ button }) => {
								const target = button;
								Dom.addClass(target, 'ui-btn-wait');
								Backend.controller({
									command: 'document.restoreRequisiteFields',
									postData: {
										documentId: this.#documentId,
										presetId: response.errors[0]?.customData.presetId,
										code: response.errors[0]?.customData.code,
									},
								}).then(() => {
									Dom.removeClass(target, 'ui-btn-wait');

									this.#blocks.map((block) => {
										if (block.isRemoved())
										{
											return;
										}

										if (block.getCode() === response.errors[0]?.customData.code)
										{
											block.assign();
										}
									});
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
								this.#blocks.map((block) => {
									if (block.isRemoved())
									{
										return;
									}

									if (block.getCode() === response.errors[0]?.customData.code)
									{
										const blockActionButton = document.querySelector(
											`button[data-id="action-${block.getCode()}]`,
										);

										if (blockActionButton)
										{
											blockActionButton.click();
										}
									}
								});
								popup.destroy();
							},
						}),
					],
				});
				popup.show();

				return null;
			}

			const firstError = response.errors[0];
			if (firstError.code === 'REFERENCE_ERROR_FIELD_NOT_SELECTED')
			{
				this.#highlightInvalidBlocks();
				const popup = new Popup({
					id: 'sign_document_error_popup',
					titleBar: Loc.getMessage('SIGN_EDITOR_ERROR_SAVE_REFERENCE_BACK_TITLE'),
					content: BX.util.htmlspecialchars(firstError.message),
					buttons: [
						new Button({
							text: Loc.getMessage('SIGN_EDITOR_ERROR_SAVE_REFERENCE_BACK'),
							color: ButtonColor.PRIMARY,
							onclick: () => {
								popup.destroy();
							},
						}),
					],
				});
				EventEmitter.subscribeOnce('SidePanel.Slider:onCloseComplete', () => {
					popup.destroy();
				});
				popup.show();

				return null;
			}

			console.error(firstError.message);

			return postData;
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
				});
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
						},
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
		const content = document.querySelector('.sign-editor__content');
		top += content.scrollTop;
		bottom += content.scrollTop;
		if (seams.length > 0 && notAddMargin !== true)
		{
			const documentLayoutRect = this.#documentLayout.getBoundingClientRect();
			top += documentLayoutRect.top;
			bottom += documentLayoutRect.top;
		}

		for (
			let ii = 0,
				cc = seams.length; ii < cc; ii++
		)
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

	#highlightInvalidBlocks()
	{
		this.#blocks.forEach((block) => {
			if (block.isRemoved())
			{
				return;
			}

			const code = block.getCode();
			const isCRM = code === 'reference' || code === 'myreference';
			if (isCRM && Type.isArray(block.getData()))
			{
				Dom.addClass(block.getLayout(), '--invalid');
			}
		});
	}

	#onBlockColorStyleChange(updatedBlock: Block, event: BaseEvent)
	{
		if (!['sign', 'mysign'].includes(updatedBlock.getCode()))
		{
			return;
		}

		const newColor: string | null = event.getData().color ?? null;
		if (Type.isNull(newColor))
		{
			return;
		}

		this.#blocks
			.filter((block) => updatedBlock.getCode() === block.getCode())
			.forEach((block) => {
				if (updatedBlock === block)
				{
					return;
				}

				block.updateColor(newColor, false);
			})
		;
	}

	#onReferenceBlockFieldSelect(block: Block, event: FieldSelectEvent): void
	{
		if (event.getData().selectedFieldNames.length === 0)
		{
			const deleteLatency = 150;
			setTimeout((): void => this.#onBlockDeleteBecauseFieldNotSelected(block), deleteLatency);
		}
	}

	#onB2eReferenceBlockFieldSelect(block: Block, event: FieldSelectEvent): void
	{
		if (event.getData().selectedFieldNames.length === 0)
		{
			const deleteLatency = 150;
			setTimeout((): void => this.#onB2eReferenceBlockDeleteBecauseFieldNotSelected(block), deleteLatency);
		}
	}

	#onBlockDeleteBecauseFieldNotSelected(block: Block): void
	{
		block.remove();
		// show notification once
		this.#cache.remember('notify', () => UI.Notification.Center.notify({
			content: Text.encode(Loc.getMessage('SIGN_EDITOR_ERROR_REFERENCE_BLOCK_FIELD_NOT_SELECTED_HINT_TEXT')),
			autoHideDelay: 3000,
			width: 480,
		}));
	}

	#onB2eReferenceBlockDeleteBecauseFieldNotSelected(block: Block): void
	{
		block.remove();
		// show notification once
		this.#cache.remember('notifyB2e', () => UI.Notification.Center.notify({
			content: Text.encode(Loc.getMessage('SIGN_EDITOR_ERROR_B2E_REFERENCE_BLOCK_FIELD_NOT_SELECTED_HINT_TEXT')),
			autoHideDelay: 3000,
			width: 480,
		}));
	}

	#unsubscribeFromBlocksEvents(): void
	{
		const subscriptions: Array<[Block, EventListenerCache]> = Object
			.values(this.#eventListeners)
			.flatMap((subscription) => [...subscription.entries()])
		;
		for (const [block, { subscription, eventName }] of subscriptions)
		{
			block.unsubscribe(eventName, subscription);
		}

		Object.values(this.#eventListeners).forEach((eventListenerCache) => eventListenerCache.clear());
	}
}
