import { Dom, Event, Type } from 'main.core';
import { Draggable, DragMoveEvent, DragStartEvent, DragEndEvent } from 'ui.draganddrop.draggable';
import { EventEmitter } from 'main.core.events';
import Block from './block';
import UI from './ui';

type ResizeOptions = {
	wrapperLayout: HTMLElement
};

export default class Resize
{
	static borderDelta = 2;

	#wrapperLayout: HTMLElement;
	#layout: HTMLElement;
	#fullEditorContent: HTMLElement;
	#linkedElement: HTMLElement;
	#linkedBlock: Block;

	position: DOMRect;
	positionLast: { ...DOMRect, notAddDeathMargin?: boolean };
	resizing: boolean;
	moving: boolean;

	#style = {
		resizeContainer: '.sign-area-resizable-controls > div',
		moveContainer: '.sign-document__resize-area',
		blockEditing: 'sign-document__block-wrapper-editing'
	};

	/**
	 * Constructor.
	 * @param {ResizeOptions} options
	 */
	constructor(options: ResizeOptions)
	{
		if (!Type.isDomNode(options.wrapperLayout))
		{
			throw new Error('Option wrapperLayout is undefined or not valid DOM Element.');
		}

		this.#layout = UI.getResizeArea();
		this.#wrapperLayout = options.wrapperLayout;
		Event.bind(this.#layout, 'click', this.#onClick.bind(this));

		this.#initResize();
		this.#initMove();
	}

	/**
	 * Returns layout of resizing area.
	 * @return {HTMLElement}
	 */
	getLayout(): HTMLElement
	{
		return this.#layout;
	}

	/**
	 * Shows resizing area over the element.
	 * @param {Block} block
	 */
	show(block: Block)
	{
		if (this.#linkedBlock === block)
		{
			return;
		}

		const pointRect = block.getLayout().getBoundingClientRect();
		this.#layout.style.display = 'block';

		if (this.#linkedElement)
		{
			this.#linkedElement.removeAttribute('data-active');
		}
		this.#linkedBlock = block;
		this.#linkedElement = block.getLayout();
		this.#linkedElement.setAttribute('data-active', 1);
		const wrapperLayoutRect = this.#wrapperLayout.getBoundingClientRect();
		UI.setRect(this.#layout, {
			top: pointRect.top - wrapperLayoutRect.top,
			left: pointRect.left - wrapperLayoutRect.left,
			width: pointRect.width + Resize.borderDelta,
			height: pointRect.height + Resize.borderDelta,
		});
	}

	setFullEditorContent(editorContent: HTMLElement)
	{
		this.#fullEditorContent = editorContent;
	}

	/**
	 * Hides resizing area.
	 */
	hide()
	{
		this.#linkedBlock = null;
		this.#layout.style.display = 'none';

		if (this.#linkedElement)
		{
			this.#linkedElement.removeAttribute('data-active');
		}
	}

	/**
	 * Returns linked block.
	 * @return {Block|null}
	 */
	getLinkedBlock(): ?Block
	{
		return this.#linkedBlock;
	}

	/**
	 * On click handler (provide click to linked element).
	 */
	#onClick(e)
	{
		if (this.moving || this.resizing)
		{
			return;
		}

		if (this.#linkedBlock)
		{
			this.#linkedBlock.fireAction();
		}
	}

	/**
	 * Initializes resizing.
	 */
	#initResize()
	{
		let initialRect = null;

		const draggable = new Draggable({
			container: this.#wrapperLayout,
			draggable: this.#style.resizeContainer,
			type: BX.UI.DragAndDrop.Draggable.HEADLESS
		});

		this.textResize = {
			wrapper: null,
			content: null
		};

		draggable
			.subscribe('start', (event: DragStartEvent) => {
				if (this.#layout.getAttribute('data-disable'))
				{
					return;
				}

				if (!this.textResize.wrapper || !this.textResize.content)
				{
					this.textResize = {
						wrapper: this.#linkedBlock.getLayout(),
						content: this.#linkedBlock.getLayout().querySelector('.sign-document__block-content')
					}
				}

				EventEmitter.emit('BX.Sign:resizeStart', draggable);
				initialRect = this.#layout.getBoundingClientRect();
				this.#linkedBlock.onStartChangePosition();
				this.positionLast = null;

				Dom.removeClass(this.#linkedBlock.getLayout(), this.#style.blockEditing);
			})
			.subscribe('end', (event: DragEndEvent) => {

				setTimeout(() => {
					this.resizing = false;
				}, 0);

			})
			.subscribe('move', (event) => {

				if (this.#layout.getAttribute('data-disable'))
				{
					return;
				}

				this.resizing = true;

				let left = null;
				let top = null;
				let bottomResize = false;
				let {width, height} = initialRect;

				const data = event.getData();
				const areaRect = this.#layout.getBoundingClientRect();
				const wrapperRect = this.#wrapperLayout.getBoundingClientRect();

				if (Dom.hasClass(data.draggable, 'sign-document__resize-control --left-top'))
				{
					left = Math.max(0, initialRect.left + data.offsetX - wrapperRect.left);
					top = Math.max(0, initialRect.top + data.offsetY - wrapperRect.top);
					width = initialRect.width - data.offsetX;
					height = initialRect.height - data.offsetY;
				}

				if (Dom.hasClass(data.draggable, 'sign-document__resize-control --middle-top'))
				{
					top = Math.max(0, initialRect.top + data.offsetY - wrapperRect.top);
					height = initialRect.height - data.offsetY;
				}

				if (Dom.hasClass(data.draggable, 'sign-document__resize-control --right-top'))
				{
					top = Math.max(0, initialRect.top + data.offsetY - wrapperRect.top);
					width = initialRect.width + data.offsetX;
					height = initialRect.height - data.offsetY;
				}

				if (Dom.hasClass(data.draggable, 'sign-document__resize-control --middle-right'))
				{
					width = initialRect.width + data.offsetX;
				}

				if (Dom.hasClass(data.draggable, 'sign-document__resize-control --right-bottom'))
				{
					width = initialRect.width + data.offsetX;
					height = initialRect.height + data.offsetY;
					bottomResize = true;
				}

				if (Dom.hasClass(data.draggable, 'sign-document__resize-control --middle-bottom'))
				{
					height = initialRect.height + data.offsetY;
					bottomResize = true;
				}

				if (Dom.hasClass(data.draggable, 'sign-document__resize-control --left-bottom'))
				{
					left = initialRect.left + data.offsetX - wrapperRect.left;
					width = initialRect.width - data.offsetX;
					height = initialRect.height + data.offsetY;
					bottomResize = true;
				}

				if (Dom.hasClass(data.draggable, 'sign-document__resize-control --middle-left'))
				{
					left = initialRect.left + data.offsetX - wrapperRect.left;
					width = initialRect.width - data.offsetX;
				}

				if (width < 60 || height < 20)
				{
					return;
				}

				const newPosition = { width, height };

				if (newPosition['width'] + areaRect.left - wrapperRect.left > wrapperRect.width)
				{
					width = newPosition['width'] = wrapperRect.width + wrapperRect.left - areaRect.left;
				}

				if (newPosition['height'] + areaRect.top - wrapperRect.top > wrapperRect.height)
				{
					height = newPosition['height'] = wrapperRect.height + wrapperRect.top - areaRect.top;
				}

				if (left)
				{
					if (left < 0)
					{
						left = 0;
					}
					newPosition['left'] = left;
				}
				if (top)
				{
					newPosition['top'] = top;
				}

				let calcDeathTop = initialRect.top;
				let notAddDeathMargin = true;
				if (newPosition.top)
				{
					calcDeathTop = newPosition.top;
					notAddDeathMargin = false;
				}

				if (this.#linkedBlock.blocksManager.inDeadZone(calcDeathTop, calcDeathTop + newPosition.height, notAddDeathMargin))
				{
					if (!bottomResize)
					{
						this.#linkedBlock.showNotAllowedArea();
					}
					return;
				}

				const newPositionLinked = Object.assign({}, {
					...newPosition,
					width: width - Resize.borderDelta,
					height: height - Resize.borderDelta,
				});

				UI.setRect(this.#layout, newPosition);
				UI.setRect(this.#linkedElement, newPositionLinked);

				this.#linkedBlock.renderView();
				this.#linkedBlock.blocksManager.setSavingMark(false);
				this.#linkedBlock.adjustActionsPanel();
			});
	}

	/**
	 * Initializes moving.
	 */
	#initMove()
	{
		const dragArea = this.#wrapperLayout;
		let widthInProcess;

		const draggable = new Draggable({
			container: dragArea,
			draggable: this.#style.moveContainer,
			type: Draggable.HEADLESS
		});

		draggable
			.subscribe('start', (event: DragStartEvent) => {

				if (this.#layout.getAttribute('data-disable'))
				{
					return;
				}

				EventEmitter.emit('BX.Sign:moveStart', draggable);

				if (this.resizing)
				{
					return;
				}

				const {source} = event.getData();
				this.position = Dom.getPosition(source);
				this.#linkedBlock.onStartChangePosition();
				this.positionLast = null;

				Dom.removeClass(this.#linkedBlock.getLayout(), this.#style.blockEditing);
			})
			.subscribe('end', (event: DragEndEvent) => {

				setTimeout(() => {
					this.moving = false;
				}, 0);

				if (!this.#linkedBlock)
				{
					return;
				}

				if (this.resizing)
				{
					return;
				}

				widthInProcess = null;

				const data = event.getData();
				let moveTopDelta = null;
				if (this.positionLast)
				{
					moveTopDelta = this.#linkedBlock.blocksManager.inDeadZone(this.positionLast.top, this.positionLast.top + this.positionLast.height);
				}

				this.#linkedBlock.hideNotAllowedArea();

				if (moveTopDelta)
				{
					UI.setRect(data.source, {top: this.positionLast.top + moveTopDelta});
					UI.setRect(this.#linkedElement, {top: this.positionLast.top + moveTopDelta});
				}
			})
			.subscribe('move', (event: DragMoveEvent) => {

				if (this.#layout.getAttribute('data-disable'))
				{
					return;
				}

				if (this.resizing)
				{
					return;
				}

				this.moving = true;

				const data = event.getData();
				const {source, offsetY, offsetX} = data;
				const areaRect = data.source.getBoundingClientRect();
				const wrapperRect = this.#wrapperLayout.getBoundingClientRect();

				if (this.position)
				{
					const newPosition = {
						top: offsetY - wrapperRect.top + this.position.y,
						left: offsetX - wrapperRect.left + this.position.x,
						width: widthInProcess ? widthInProcess : this.position.width,
						height: this.position.height
					};

					if (newPosition.left < 0)
					{
						newPosition.left = 0;
					}
					if (newPosition.top < 0)
					{
						newPosition.top = 0;
					}
					if (newPosition.top + areaRect.height > wrapperRect.height)
					{
						newPosition.top = wrapperRect.height - areaRect.height;
					}
					if (newPosition.left + areaRect.width > wrapperRect.width)
					{
						this.#linkedBlock.renderView();
						if (wrapperRect.width - newPosition.left > 50)
						{
							widthInProcess = newPosition.width = wrapperRect.width - newPosition.left;
						}
						else
						{
							newPosition.left = wrapperRect.width - areaRect.width;
						}
					}

					this.positionLast = Object.assign({}, newPosition);

					let moveTopDelta = null;

					if (this.positionLast)
					{
						moveTopDelta = this.#linkedBlock.blocksManager.inDeadZone(this.positionLast.top, this.positionLast.top + this.positionLast.height);
					}

					if (moveTopDelta > 0)
					{
						this.#linkedBlock.showNotAllowedArea();
					}
					else
					{
						this.#linkedBlock.hideNotAllowedArea();
					}

					const newPositionLinked = Object.assign({}, {
						...newPosition,
						width: newPosition.width - Resize.borderDelta,
						height: newPosition.height - Resize.borderDelta,
					});

					UI.setRect(source, newPosition);
					UI.setRect(this.#linkedElement, newPositionLinked);

					this.#linkedBlock.blocksManager.setSavingMark(false);
					this.#linkedBlock.adjustActionsPanel();
				}
			});
	}
}
