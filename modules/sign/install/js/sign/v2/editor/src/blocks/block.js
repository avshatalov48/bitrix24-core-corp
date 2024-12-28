import { Dom, Event, Type } from 'main.core';
import { Backend } from 'sign.backend';
import { EventEmitter } from 'main.core.events';
import { Api } from 'sign.v2.api';
import type { FieldSelectEvent } from '../types/events/fieldSelectEvent';
import { Date, Dummy, MyRequisites, MySign, MyStamp, Number, Reference, Requisites, Sign, Stamp, Text, MyReference, B2eReference, MyB2eReference, EmployeeDynamic, HcmLinkReference } from './index';
import { BlocksManager } from './blocksManager';
import Style from './style';
import { PositionType, BlockOptions } from '../types/block';
import UI from './ui';

export default class Block extends EventEmitter
{
	events = {
		onColorStyleChange: 'onColorStyleChange',
	};

	#id: number;
	#code: string;
	#layout: HTMLElement;
	#stylePanel: Style;
	#content: Dummy = null;
	blocksManager: BlocksManager;
	#memberPart: number = 2;
	#panelCreated: boolean = false;
	#allowMembers: boolean = false;
	#onClickCallback: ?() => {};
	#onRemoveCallback: ?() => {};
	#contentProviders = {
		date: Date,
		myrequisites: MyRequisites,
		mysign: MySign,
		mystamp: MyStamp,
		number: Number,
		reference: Reference,
		myreference: MyReference,
		requisites: Requisites,
		sign: Sign,
		stamp: Stamp,
		text: Text,

		b2ereference: B2eReference,
		myb2ereference: MyB2eReference,
		employeedynamic: EmployeeDynamic,

		hcmlinkreference: HcmLinkReference,
	};
	#currentFontSize: String;

	#style = {
		blockContent: '.sign-document__block-content',
		blockPanel: '.sign-document__block-panel--wrapper',
		blockLoading: 'sign-document-block-loading',
		blockEditing: 'sign-document__block-wrapper-editing',
		pageWithNotAllowed: 'sign-editor__content-document--active-move',
	};

	#firstRenderReady: boolean;

	#api: Api;

	/**
	 * Constructor.
	 * @param {BlockOptions} options
	 */
	constructor(options: BlockOptions)
	{
		super();
		this.setEventNamespace('BX.Sign.V2.Editor.Block');
		this.blocksManager = options.blocksManager;
		this.#id = options.id || null;
		this.#code = options.code;
		this.#memberPart = options.party;
		this.#onClickCallback = options.onClick;
		this.#onRemoveCallback = options.onRemove;
		this.#firstRenderReady = null;
		this.#stylePanel = new Style({
			block: this,
			data: options.style
		});
		if (!this.#contentProviders[this.#code])
		{
			throw new Error(`Content provider for '${this.#code}' not found.`);
		}

		this.#content = new this.#contentProviders[this.#code](this);

		if (['sign', 'mysign'].includes(this.#code) && Type.isString(options.style?.color))
		{
			this.#content.changeStyleColor(options.style.color, false);
		}

		this.#createLayout();

		Event.bind(this.#layout, 'click', this.#onClick.bind(this));

		if (
			options.party > 1
			&& !['b2ereference', 'employeedynamic', 'hcmlinkreference'].includes(this.#code.toLowerCase()))
		{
			this.#allowMembers = true;
		}

		this.renderStyle();

		this.setPosition(options.position ?? this.#content.getInitDimension());

		if (options.data)
		{
			setTimeout(() => {
				this.setData(options.data);
			}, 0);
		}
		this.#api = new Api();
	}

	/**
	 * Returns block's layout.
	 * @return {HTMLElement}
	 */
	getLayout(): HTMLElement
	{
		return this.#layout;
	}

	/**
	 * Sets new data to the block.
	 * @param {any} data
	 */
	setData(data: any): void
	{
		this.#content.setData(data);
		this.renderView();
	}

	/**
	 * Sets initial position to the block.
	 * @param {PositionType} position
	 */
	setPosition(position: PositionType)
	{
		UI.setRect(this.#layout, position);
	}

	/**
	 * Returns block's data.
	 * @return {any}
	 */
	getData(): any
	{
		return this.#content.getData();
	}

	/**
	 * Returns position.
	 * @return {PositionType}
	 */
	getPosition(): PositionType
	{
		let {top, left, width, height} = this.#layout.getBoundingClientRect();
		const layout = this.#layout;
		top = Math.round(top);
		left = Math.round(left);
		width = Math.round(width);
		height = Math.round(height);

		const documentRect = this.blocksManager.getLayout().getBoundingClientRect();

		top -= Math.round(documentRect.top);
		left -= Math.round(documentRect.left);

		return {top, left, width, height};
	}

	/**
	 * Returns block styles.
	 * @return {{{[key: string]: string}}}
	 */
	getStyle(): {[key: string]: string}
	{
		return { ...this.#content.getStyles(), ...this.#stylePanel.collectStyles() };
	}

	/**
	 * Returns id.
	 * @return {number|null}
	 */
	getId(): ?number
	{
		return this.#id ?? null;
	}

	/**
	 * Returns code.
	 * @return {string}
	 */
	getCode(): string
	{
		return this.#code;
	}

	/**
	 * Shows page's areas not allowed for block's placement.
	 */
	showNotAllowedArea()
	{
		const {page} = this.blocksManager.transferPositionToPage(this.getPosition());
		const pageElement = document.querySelector(`.sign-editor__content-document--page[data-page="${page}"]`);

		Dom.addClass(pageElement, this.#style.pageWithNotAllowed);
	}

	hideNotAllowedArea()
	{
		const {page} = this.blocksManager.transferPositionToPage(this.getPosition());
		const pageElement = document.querySelector(`.sign-editor__content-document--page[data-page="${page}"]`);

		Dom.removeClass(pageElement, this.#style.pageWithNotAllowed);
	}

	/**
	 * Returns member part.
	 * @return {number}
	 */
	getMemberPart(): number
	{
		return this.#memberPart;
	}

	/**
	 * Handler on click to block.
	 */
	#onClick()
	{
		if (this.#onClickCallback)
		{
			this.#onClickCallback(this);
		}
	}

	/**
	 * Calls block's action.
	 */
	fireAction()
	{
		if (this.#content['onActionClick'])
		{
			if (this.#code === 'text')
			{
				Dom.addClass(this.#layout, this.#style.blockEditing);
			}
			this.#content['onActionClick']();
		}
	}

	/**
	 * Handler on member change.
	 * @param {number} part
	 */
	onMemberSelect(part: number)
	{
		this.#memberPart = part;
		this.assign();
	}

	/**
	 * Sets/removes awaiting class to the block.
	 * @param {boolean} flag
	 */
	await(flag: boolean)
	{
		const blockLayouts = [];

		if (!this.#content.isSingleton())
		{
			blockLayouts.push(this.getLayout());
		}
		else
		{
			const currentCode = this.getCode();

			this.blocksManager.getBlocks().map(block => {
				if (block.getCode() === currentCode)
				{
					blockLayouts.push(block.getLayout());
				}
			});
		}

		blockLayouts.map((blockLayout, key) => {
			if (flag)
			{
				if (blockLayouts.length === key + 1)
				{
					Dom.addClass(blockLayout, this.#style.blockLoading);
				}
			}
			else
			{
				Dom.removeClass(blockLayout, this.#style.blockLoading);
			}
		});
	}

	/**
	 * Assigns block to the document (without saving).
	 */
	assign()
	{
		const blockLayout = this.getLayout();
		const blocksData = [];
		const blocksInstance = [];

		this.await(true);

		if (!this.#content.isSingleton())
		{
			blocksData.push({
				code: this.#code,
				part: this.#memberPart,
				data: this.getData()
			});
			blocksInstance.push(this);
		}
		// if block is a singleton push all blocks with same code
		else
		{
			this.blocksManager.getBlocks().map(block => {
				if (
					block.getCode() === this.getCode() &&
					block.getMemberPart() === this.getMemberPart()
				)
				{
					blocksData.push({
						code: block.getCode(),
						part: block.getMemberPart(),
						data: block.getData()
					});
					blocksInstance.push(block);
				}
			});
		}

		this.#api.loadBlocksData(
			this.blocksManager.getDocumentUid(),
			blocksData
		).then(result => {
			if (Type.isArray(result))
			{
				result.forEach((block, i) => {
					blocksInstance[i].setData(block.data);
				});
			}

			this.await(false);
			this.blocksManager.showResizeArea(this);
		})
		.catch((response) => {
			Dom.remove(blockLayout);
		});
	}

	/**
	 * Renders block within document's layout.
	 */
	renderView()
	{
		const contentTag = this.#layout.querySelector(this.#style.blockContent);

		// content
		Dom.clean(contentTag);

		switch (this.#code.toLowerCase())
		{
			case 'stamp':
			case 'mystamp':
			case 'sign':
			case 'mysign':
				Dom.addClass(contentTag, '--image')
		}

		const resizeNode = this.#content.getViewContent();

		Dom.append(resizeNode, contentTag);

		Dom.addClass(resizeNode, '--' + this.#code.toLowerCase());

		if (
			this.#code.toLowerCase() === 'requisites'
			|| this.#code.toLowerCase() === 'myrequisites'
			|| this.#code.toLowerCase() === 'date'
			|| this.#code.toLowerCase() === 'number'
			|| this.#code.toLowerCase() === 'stamp'
			|| this.#code.toLowerCase() === 'mystamp'
			|| this.#code.toLowerCase() === 'sign'
			|| this.#code.toLowerCase() === 'mysign'
			|| this.#code.toLowerCase() === 'reference'
			|| this.#code.toLowerCase() === 'myreference'
			|| this.#code.toLowerCase() === 'text'
			|| this.#code.toLowerCase() === 'b2ereference'
			|| this.#code.toLowerCase() === 'myb2ereference'
			|| this.#code.toLowerCase() === 'employeedynamic'
			|| this.#code.toLowerCase() === 'hcmlinkreference'
		)
		{
			resizeNode.style.setProperty('display', 'block');
			resizeNode.style.setProperty('overflow', 'hidden');

			if (!this.observerReady)
			{
				if (this.getStyle()['fontSize'])
				{
					this.maxTextSize = parseFloat(this.getStyle()['fontSize']);
				}
				else
				{
					this.maxTextSize = 14;
				}

				this.isOverflownX = ({ clientHeight, scrollHeight }) => {
					return scrollHeight > clientHeight;
				}

				EventEmitter.subscribe(resizeNode.parentNode.parentNode, 'BX.Sign:setFontSize', (param)=> {
					if (param.data.fontSize)
					{
						this.maxTextSize = param.data.fontSize;
						this.resizeText({
							element: param.target.querySelector('.sign-document__block-content > div'),
							step: 0.5
						});
					}
				});

				this.resizeText = ({ element, minSize = 1, step = 1, unit = 'px' }) => {

					if (this.intervalTextResize)
					{
						clearTimeout(this.intervalTextResize);
					}

					let i = minSize;
					let overflow = false;

					const parent = element.parentNode

					while (!overflow && i <= this.maxTextSize)
					{
						element.style.fontSize = `${i}${unit}`;
						overflow = this.isOverflownX(parent);
						i += step;
					}
					this.#currentFontSize = `${i - step}${unit}`;
					element.style.fontSize = this.#currentFontSize;

					this.intervalTextResize = setTimeout(() => {
						element.parentNode.style.setProperty('font-size', element.style.fontSize);
						element.style.removeProperty('font-size', element.style.fontSize);
						this.#stylePanel.updateFontSize(this.#currentFontSize);
					}, 1000);
				}

				if (
					this.#code.toLowerCase() === 'requisites'
					|| this.#code.toLowerCase() === 'myrequisites'
					|| this.#code.toLowerCase() === 'reference'
					|| this.#code.toLowerCase() === 'myreference'
					|| this.#code.toLowerCase() === 'text'
				)
				{
					this.resizeText({
						element: resizeNode,
						step: 0.5
					});
				}
				this.#content.subscribe(this.#content.events.onChange, this.#onContentChange.bind(this));
				this.#content.subscribe(this.#content.events.onColorStyleChange, this.#onContentColorStyleChange.bind(this));
				this.#content.subscribe('onFieldSelect', this.#onFieldSelect.bind(this));

				this.observerReady = true;
			}

			if (this.#firstRenderReady)
			{
				this.resizeText({
					element: resizeNode,
					step: 0.5
				});
			}
		}

		this.#firstRenderReady = true;

		if (this.#panelCreated)
		{
			return;
		}

		// action / style panel
		const panelTag = this.#layout.querySelector(this.#style.blockPanel);
		Dom.clean(panelTag);

		// style
		if (this.#content.isStyleAllowed())
		{
			Dom.append(this.#stylePanel.getLayout(), panelTag);
		}

		// action
		Dom.append(this.#content.getActionButton(), panelTag);

		// block caption
		Dom.append(this.#content.getBlockCaption(), panelTag);

		// member selector
		if (this.#allowMembers)
		{
			if (
				['hcmlinkreference', 'b2ereference'].includes(this.#code.toLowerCase())
			)
			{
				return;
			}

			const allMembers = this.blocksManager.getMembers();
			const selectedMembers = allMembers.filter((member) => {
				return member.part > 1
			});

			Dom.append(UI.getMemberSelector(
				selectedMembers,
				this.#memberPart,
				this.onMemberSelect.bind(this)
			), panelTag);
		}

		this.#panelCreated = true;
	}

	getCurrentFontSize()
	{
		return this.#currentFontSize;
	}

	/**
	 * Calls when block starts being resized or moved.
	 */
	onStartChangePosition()
	{
		this.#content.onStartChangePosition();
	}

	/**
	 * Calls when block has placed on document.
	 */
	onPlaced()
	{
		this.#content.onPlaced();
	}

	updateColor(color: string, emitEvent: boolean = true): void
	{
		this.#content.changeStyleColor(color, emitEvent);
		this.#stylePanel.updateColor(color);
	}

	/**
	 * Calls when block saved.
	 */
	onSave()
	{
		this.#content.onSave();
	}

	/**
	 * Calls when block removed.
	 */
	onRemove()
	{
		this.#content.onRemove();
	}

	/**
	 * Calls when click was out the block.
	 */
	onClickOut()
	{
		this.#content.onClickOut();
	}

	/**
	 * Set block styles to layout.
	 */
	renderStyle()
	{
		this.#stylePanel.applyStyles(
			this.#layout.querySelector(this.#style.blockContent)
		);
	}

	/**
	 * Adjust actions panel.
	 */
	adjustActionsPanel()
	{
		const blockLayout = this.getLayout();
		const documentLayout = this.blocksManager.getLayout().parentElement;
		const documentLayoutRect = documentLayout.getBoundingClientRect();
		const actionsPanel = blockLayout.querySelector('[data-role="sign-block__actions"]');

		if (actionsPanel)
		{
			const actionsPanelRect = actionsPanel.getBoundingClientRect();
			const marginLeft = parseFloat(getComputedStyle(actionsPanel).marginLeft);

			if (documentLayoutRect.x >= actionsPanelRect.x)
			{
				actionsPanel.style.marginLeft = documentLayoutRect.x - actionsPanelRect.x + marginLeft + 'px';
			}
			else
			{
				actionsPanel.style.marginLeft = 0;
			}
		}
	}

	/**
	 * Force saves the block.
	 */
	forceSave()
	{
		this.#layout.querySelector('[data-role="saveAction"]').click();
	}

	/**
	 * Creates layout for new block within document.
	 */
	#createLayout()
	{
		this.#layout = UI.getBlockLayout({
			onSave: (event) => {
				this.renderView();
				this.blocksManager.unMuteResizeArea();
				this.blocksManager.hideResizeArea();
				this.blocksManager.setSavingMark(false);
				this.onSave();

				Dom.removeClass(this.#layout, this.#style.blockEditing);

				event.stopPropagation();
			},
			onRemove: this.#onRemoveBtnClick.bind(this),
		});
		const nodeForPosition = document.body.querySelector('.sign-editor__content');
		const documentLayout = this.blocksManager.getLayout();
		const documentLayoutRect = documentLayout.getBoundingClientRect();
		const blockInitDim = this.#content.getInitDimension();
		const position = {
			top: Math.min(nodeForPosition.scrollTop + nodeForPosition.offsetHeight / 2, documentLayoutRect.height - blockInitDim.height),
			left: documentLayoutRect.width / 2 - 100
		};

		if (this.blocksManager.inDeadZone(position.top, position.top + blockInitDim.height))
		{
			position.top += blockInitDim.height + this.blocksManager.getPagesGap();
		}

		this.setPosition(position);
	}

	remove(): void
	{
		if (this.#onRemoveCallback)
		{
			this.#onRemoveCallback(this);
		}
		this.#layout.hidden = true;
		this.onRemove();
	}

	#onRemoveBtnClick(event): void
	{
		this.remove();
		event.stopPropagation();
	}

	/**
	 * Returns true, if block was removed.
	 * @return {boolean}
	 */
	isRemoved()
	{
		return this.#layout.hidden === true;
	}

	#onContentChange(): void
	{
		const content = this.#content;
		if (!(content instanceof Text))
		{
			return;
		}

		this.resizeText({ element: content.getContainer(), step: 0.5 });
	}

	#onContentColorStyleChange(event): void
	{
		this.emit(this.events.onColorStyleChange, { color: this.#content.getStyles()?.color ?? null });
		const color = this.#content.getStyles()?.color ?? null;
		if (Type.isString(color))
		{
			this.#stylePanel.updateColor(color);
		}
	}

	#onFieldSelect(event: FieldSelectEvent): void
	{
		this.emit('onFieldSelect', event);
	}

	setMemberParty(party: Number): void
	{
		this.#memberPart = party;
	}
}
