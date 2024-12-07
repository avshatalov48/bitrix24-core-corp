import { Tag, Text as TextFormat } from 'main.core';
import Block from '../block';
import { EventEmitter } from 'main.core.events';

export default class Dummy extends EventEmitter
{
	block: Block;
	data: any = {};
	events: {[key: string]: string} = {
		onChange: 'onChange',
		onColorStyleChange: 'onColorStyleChange',
	};

	static defaultTextBlockPaddingStyles = {
		padding: '5px 8px'
	};

	/**
	 * Constructor.
	 * @param {Block} block
	 */
	constructor(block: Block)
	{
		super();
		this.setEventNamespace('BX.Sign.Blocks.Dummy');

		this.block = block;
	}

	/**
	 * Returns true if block is in singleton mode.
	 * @return {boolean}
	 */
	isSingleton(): boolean
	{
		return false;
	}

	/**
	 * Returns true if style panel mast be showed.
	 * @return {boolean}
	 */
	isStyleAllowed(): boolean
	{
		return true;
	}

	/**
	 * Sets new data.
	 * @param {any} data
	 */
	setData(data: any)
	{
		this.data = data ? data : {};
	}

	/**
	 * Changes only text key in data.
	 * @param {string} text
	 */
	setText(text: string)
	{
		this.setData({ text });
	}

	/**
	 * Returns initial dimension of block.
	 * @return {width: number, height: number}
	 */
	getInitDimension(): {width: number, height: number}
	{
		return {
			width: 250,
			height: 28
		};
	}

	/**
	 * Returns current data.
	 * @return {any}
	 */
	getData(): any
	{
		if (this.data.base64)
		{
			this.data.base64 = null;
		}

		return this.data;
	}

	/**
	 * Returns action button for edit content.
	 * @return {HTMLElement | null}
	 */
	getActionButton(): ?HTMLElement
	{
		return null;
	}

	/**
	 * @return {HTMLElement | null}
	 */
	getBlockCaption(): ?HTMLElement
	{
		return null;
	}

	/**
	 * Returns type's content in view mode.
	 * @return {HTMLElement | string}
	 */
	getViewContent(): HTMLElement | string
	{
		return Tag.render`
			<div>
				${TextFormat.encode(this.data.text || '').toString().replaceAll('[br]', '<br>')}
			</div>
		`;
	}

	/**
	 * Calls when block starts being resized or moved.
	 */
	onStartChangePosition()
	{
	}

	/**
	 * Calls when block has placed on document.
	 */
	onPlaced()
	{
	}

	/**
	 * Calls when block saved.
	 */
	onSave()
	{
	}

	/**
	 * Calls when block removed.
	 */
	onRemove()
	{
	}

	/**
	 * Calls when click was out the block.
	 */
	onClickOut()
	{
		this.block.forceSave();
	}

	onChange(): void
	{
		this.emit(this.events.onChange);
	}

	getStyles(): {[styleName: string]: string}
	{
		return {};
	}

	onStyleChange()
	{
		this.emit(this.events.onColorStyleChange);
	}

	onStyleRender(styles: Object)
	{
	}
}
