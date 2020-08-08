import {Dom, Loc, Tag, Reflection, Cache, Event} from 'main.core';
import {Manager} from 'rpa.manager';
import jsDDObject from 'main/install/js/main/dd.js';

const namespace = Reflection.namespace('BX.Rpa');

class DragDropBtn
{
	constructor(options) {
		this.btnContainer = options;
		this.draggableBtnContainer = null;
		this.dragElement = null;
		this.cache = new Cache.MemoryCache();
	}

	init()
	{
		const dragButton = this.getDragButton();
		Dom.prepend(dragButton, this.btnContainer);

		dragButton.onbxdragstart = this.onDragStart.bind(this);
		dragButton.onbxdrag = this.onDrag.bind(this);
		dragButton.onbxdragstop = this.onDragStop.bind(this);
		jsDD.registerObject(dragButton);

		this.btnContainer.onbxdestdraghover = this.onDragEnter.bind(this);
		this.btnContainer.onbxdestdraghout = this.onDragLeave.bind(this);
		this.btnContainer.onbxdestdragfinish = this.onDragDrop.bind(this);

		jsDD.registerDest(this.btnContainer, 30);
	}

	getDragButton(): HTMLSpanElement | jsDDObject
	{
		return this.cache.remember('dragButton', () => (
			Tag.render`
				<span class="rpa-edit-robot-btn-icon-draggable"></span>
			`
		));
	}

	onDragStart()
	{
		Dom.addClass(this.btnContainer, "rpa-edit-robot-btn-item-disabled");

		if (!this.dragElement)
		{
			this.dragElement = this.btnContainer.cloneNode(true);

			this.dragElement.style.position = "absolute";
			this.dragElement.style.width = this.btnContainer.offsetWidth + "px";
			this.dragElement.className = "rpa-edit-robot-btn-item rpa-edit-robot-btn-item-drag";

			Dom.append(this.dragElement, document.body);
		}
	}

	onDrag(x, y)
	{
		if (this.dragElement)
		{
			this.dragElement.style.left = x + "px";
			this.dragElement.style.top = y + "px";
		}
	}

	onDragStop()
	{
		Dom.removeClass(this.btnContainer, "rpa-edit-robot-btn-item-disabled");
		Dom.remove(this.dragElement);
		this.dragElement = null;
	}

	onDragEnter(draggableItem)
	{
		this.draggableBtnContainer = draggableItem.closest('.rpa-edit-robot-btn-item');
		if (this.draggableBtnContainer !== this.btnContainer)
		{
			this.showDragTarget();
		}
	}

	onDragLeave()
	{
		this.hideDragTarget();
	}

	onDragDrop()
	{
		if (this.draggableBtnContainer !== this.btnContainer)
		{
			this.hideDragTarget();
			Dom.remove(this.draggableBtnContainer);
			Dom.insertBefore(this.draggableBtnContainer, this.btnContainer);
		}
	}

	showDragTarget()
	{
		Dom.addClass(this.btnContainer, 'rpa-edit-robot-btn-item-target-shown');
		this.getDragTarget().style.height = this.btnContainer.offsetHeight + "px";
	}

	hideDragTarget()
	{
		Dom.removeClass(this.btnContainer, "rpa-edit-robot-btn-item-target-shown");
		this.getDragTarget().style.height = 0;
	}

	getDragTarget()
	{
		if (!this.dragTarget)
		{
			this.dragTarget = Tag.render`<div class="rpa-edit-robot-btn-item-drag-target"></div>`;
			Dom.prepend(this.dragTarget, this.btnContainer);
		}

		return this.dragTarget;
	}

}

class DragDropBtnContainer
{
	constructor() {

		this.container = document.querySelector('.rpa-edit-robot-btn-item-list');
		this.height = null;
	}

	init()
	{
		this.container.onbxdestdraghover = BX.delegate(this.onDragEnter, this);
		this.container.onbxdestdraghout = BX.delegate(this.onDragLeave, this);
		this.container.onbxdestdragfinish = BX.delegate(this.onDragDrop, this);
		jsDD.registerDest(this.container, 40);
	}

	onDragEnter(draggableItem)
	{
		this.draggableBtnContainer = draggableItem.closest('.rpa-edit-robot-btn-item');
		this.height = this.draggableBtnContainer.offsetHeight;

		this.showDragTarget();
	}

	onDragLeave()
	{
		this.hideDragTarget();
	}

	onDragDrop()
	{
		this.hideDragTarget();
		Dom.remove(this.draggableBtnContainer);
		Dom.insertBefore(this.draggableBtnContainer, this.dragTarget);
	}

	showDragTarget()
	{
		Dom.addClass(this.container, 'rpa-edit-robot-btn-list-target-shown');
		this.getDragTarget().style.height = this.height + "px";
	}

	hideDragTarget()
	{
		Dom.removeClass(this.container, "rpa-edit-robot-btn-list-target-shown");
		this.getDragTarget().style.height = 0;
	}

	getDragTarget()
	{
		if (!this.dragTarget)
		{
			this.dragTarget = Tag.render`<div class="rpa-edit-robot-btn-list-target-target"></div>`;
			Dom.append(this.dragTarget, this.container);
		}

		return this.dragTarget;
	}

}

namespace.DragDropBtn = DragDropBtn;
namespace.DragDropBtnContainer = DragDropBtnContainer;