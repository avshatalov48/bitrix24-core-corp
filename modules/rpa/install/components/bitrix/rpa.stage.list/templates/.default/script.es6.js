import {Reflection, Event, Type, Loc, Dom, Tag, Text} from 'main.core';
import {Component} from 'rpa.component';
import {Manager} from 'rpa.manager';

const namespace = Reflection.namespace('BX.Rpa');

class StagesComponent extends Component
{
	static defaultCommonStageColor = '39A8EF';
	static defaultFailStageColor = 'FF5752';

	constructor(...args)
	{
		super(...args);

		this.stages = new Set();
		if(Type.isArray(this.params.stages))
		{
			this.params.stages.forEach((data) =>
			{
				if(this.stages.size <= 0)
				{
					data.first = true;
				}
				const stage = this.createStage(data);
				if(stage)
				{
					this.addStage(stage);
				}
			});
		}
		this.typeId = this.params.typeId;

		this.firstStageContainer = this.form.querySelector('[data-role="rpa-stages-first"]');
		this.commonStagesContainer = this.form.querySelector('[data-role="rpa-stages-common"]');
		this.successStageContainer = this.form.querySelector('[data-role="rpa-stages-success"]');
		this.failStageContainer = this.form.querySelector('[data-role="rpa-stages-fail"]');

		this.addCommonStageButton = this.form.querySelector('[data-role="rpa-stage-common-add"]');
		this.addFailStageButton = this.form.querySelector('[data-role="rpa-stage-fail-add"]');
	}

	init()
	{
		super.init();

		this.renderStages();
	}

	renderStages()
	{
		this.stages.forEach((stage) =>
		{
			if(stage.isFirst() && this.firstStageContainer)
			{
				Dom.append(stage.render(), this.firstStageContainer);
			}
			else if(stage.isSuccess() && this.successStageContainer)
			{
				Dom.append(stage.render(), this.successStageContainer);
			}
			else if(stage.isFail() && this.failStageContainer)
			{
				Dom.append(stage.render(), this.failStageContainer);
			}
			else if(this.commonStagesContainer)
			{
				Dom.append(stage.render(), this.commonStagesContainer);
			}
		});
	}

	bindEvents()
	{
		super.bindEvents();

		if(this.addCommonStageButton && this.commonStagesContainer)
		{
			Event.bind(this.addCommonStageButton, 'click', () =>
			{
				var data = {
					name: Loc.getMessage('RPA_STAGES_NEW_STAGE_NAME'),
					color: this.constructor.defaultCommonStageColor,
				};
				var stage = this.createStage(data);
				if(stage)
				{
					this.addStage(stage);
					Dom.append(stage.render(), this.commonStagesContainer);
				}
			})
		}

		if(this.addFailStageButton && this.failStageContainer)
		{
			Event.bind(this.addFailStageButton, 'click', () =>
			{
				var data = {
					name: Loc.getMessage('RPA_STAGES_NEW_STAGE_NAME'),
					color: this.constructor.defaultFailStageColor,
					semantic: 'FAIL',
				};
				var stage = this.createStage(data);
				if(stage)
				{
					this.addStage(stage);
					Dom.append(stage.render(), this.failStageContainer);
				}
			})
		}
	}

	createStage(data): ?Stage
	{
		let stage = null;
		if(
			Type.isPlainObject(data) &&
			Type.isString(data.name)
		)
		{
			data.typeId = this.typeId;
			stage = new Stage(data);
			stage.setComponent(this);
		}

		return stage;
	}

	addStage(stage: Stage): StagesComponent
	{
		if(stage instanceof Stage)
		{
			this.stages.add(stage);
		}
	}

	removeStage(stage: Stage): StagesComponent
	{
		this.stages.delete(stage);
		return this;
	}

	onMoveStage(movedStage: Stage)
	{
		const groupContainer = movedStage.getContainer().parentElement;
		if(!groupContainer)
		{
			return;
		}
		const groupStages = new Set();
		this.stages.forEach((stage: Stage) =>
		{
			if((stage.isFail() && movedStage.isFail()) || (!stage.isFinal() && !movedStage.isFinal() && !stage.isFirst() && !movedStage.isFirst()))
			{
				groupStages.add(stage);
				this.removeStage(stage);
			}
		});
		groupContainer.childNodes.forEach((container) =>
		{
			const stage = this.getStageByElement(container, groupStages);
			if(stage)
			{
				groupStages.delete(stage);
				this.stages.add(stage);
			}
		});
	}

	getStageByElement(container: Element, stages: Set): ?Stage
	{
		let result = null;
		stages.forEach((stage) =>
		{
			if(stage.getContainer() === container)
			{
				result = stage;
			}
		});

		return result;
	}

	prepareData()
	{
		const data = {
			stages: [],
			typeId: this.typeId,
		};
		let sort = 1000;
		const pushStage = function(stage: Stage, stages: Array, sort: number)
		{
			stages.push({
				id: stage.getId(),
				name: stage.getName(),
				color: stage.getColor(),
				semantic: stage.getSemantic(),
				typeId: stage.getTypeId(),
				sort: sort,
			});
		};

		this.stages.forEach((stage) =>
		{
			if(stage.isFirst())
			{
				pushStage(stage, data.stages, sort);
				sort += 1000;
			}
		});

		this.stages.forEach((stage) =>
		{
			if(!stage.isFirst() && !stage.isFinal())
			{
				pushStage(stage, data.stages, sort);
				sort += 1000;
			}
		});

		this.stages.forEach((stage) =>
		{
			if(stage.isSuccess())
			{
				pushStage(stage, data.stages, sort);
				sort += 1000;
			}
		});

		this.stages.forEach((stage) =>
		{
			if(stage.isFail())
			{
				pushStage(stage, data.stages, sort);
				sort += 1000;
			}
		});

		return data;
	}

	afterSave(response)
	{
		super.afterSave(response);

		const slider = this.getSlider();
		if(slider)
		{
			slider.close();
		}
	}

	showErrors(errors)
	{
		super.showErrors(errors);
		this.errorsContainer.parentNode.style.display = 'block';
	}

	hideErrors()
	{
		super.hideErrors();
		this.errorsContainer.parentNode.style.display = 'none';
	}
}

class Stage
{
	constructor(data: {
		id: ?number,
		name: string,
		color: string,
		sort: number,
		semantic: string,
		first: ?boolean,
		typeId: number,
	})
	{
		this.id = data.id;
		this.name = data.name;
		this.color = data.color;
		this.sort = data.sort;
		this.semantic = data.semantic;
		this.first = data.first;
		this.typeId = data.typeId;
		this.initialData = data;
		this.layout = {};
	}

	setComponent(component: StagesComponent): Stage
	{
		this.stagesComponent = component;

		return this;
	}

	getComponent(): ?StagesComponent
	{
		return this.stagesComponent;
	}

	getId(): number
	{
		if(this.id > 0)
		{
			return this.id;
		}

		return 0;
	}

	getTypeId(): number
	{
		return this.typeId;
	}

	getName(): string
	{
		if(Type.isString(this.name))
		{
			return this.name;
		}

		return '';
	}

	getColor(): string
	{
		if(Type.isString(this.color))
		{
			return this.color;
		}

		return '39A8EF';
	}

	setName(name: string): Stage
	{
		this.name = name;
		return this;
	}

	setColor(color: string): Stage
	{
		this.color = color;
		return this;
	}

	getSemantic(): ?string
	{
		if(Type.isString(this.semantic))
		{
			return this.semantic;
		}

		return null;
	}

	isFirst(): boolean
	{
		return (this.first === true);
	}

	isFinal(): boolean
	{
		return (this.isSuccess() || this.isFail());
	}

	isSuccess(): boolean
	{
		return (this.semantic === 'SUCCESS');
	}

	isFail(): boolean
	{
		return (this.semantic === 'FAIL');
	}

	getContainer(): Element
	{
		if(!this.layout.container)
		{
			this.layout.container = Tag.render`<div class="rpa-stage-phase"></div>`;
		}

		return this.layout.container;
	}

	getInnerContainer(): Element
	{
		if(!this.layout.innerContainer)
		{
			this.layout.innerContainer = Tag.render`<div class="rpa-stage-phase-inner"></div>`;
		}

		return this.layout.innerContainer;
	}

	render(): Element
	{
		const innerContainer = this.getInnerContainer();
		const container = this.getContainer();
		Dom.clean(innerContainer);

		Dom.append(this.renderPanel(), innerContainer);
		Dom.append(this.renderIcon(), innerContainer);
		Dom.append(this.renderTitle(), innerContainer);
		Dom.append(innerContainer, container);

		if (!this.isFirst() && !this.isSuccess())
		{
			let item = new DragDropItem({stage: this});
			item.init();
		}

		this.adjustColors();

		return container;
	}

	renderPanel(): Element
	{
		this.layout.panel = Tag.render`<div class="rpa-stage-phase-panel">
			<div title="${Loc.getMessage('RPA_STAGES_STAGE_PANEL_RELOAD')}" class="rpa-stage-phase-panel-button rpa-stage-phase-panel-button-refresh" onclick="${this.restoreInitialData.bind(this)}"></div>
			${this.getColorButton()}
			${this.getDeleteButton() ? this.getDeleteButton() : ''}
		</div>`;

		return this.layout.panel;
	}

	getDeleteButton(): ?Element
	{
		if(!this.isSuccess() && !this.isFirst())
		{
			if(!this.layout.deleteButton)
			{
				this.layout.deleteButton = Tag.render`<div title="${Loc.getMessage('RPA_COMMON_ACTION_DELETE')}" class="rpa-stage-phase-panel-button rpa-stage-phase-panel-button-close" onclick="${this.destroy.bind(this)}"></div>`;
			}
		}
		else if(this.layout.deleteButton)
		{
			Dom.remove(this.layout.deleteButton);
			this.layout.deleteButton = null;
		}

		return this.layout.deleteButton;
	}

	getColorButton(): Element
	{
		if(!this.layout.colorButton)
		{
			this.layout.colorButton = Tag.render`<div class="rpa-stage-phase-panel-button" title="${Loc.getMessage('RPA_STAGES_STAGE_PANEL_COLOR')}" onclick="${this.showColorPicker.bind(this)}"></div>`;
		}

		return this.layout.colorButton;
	}

	renderIcon(): Element
	{
		this.layout.icon = Tag.render`<span class="rpa-stage-phase-icon">
			<span class="${(this.isFirst() || this.isSuccess() ? 'rpa-stage-phase-icon-arrow' : 'rpa-stage-phase-icon-burger')}"></span>
		</span>`;

		return this.layout.icon;
	}

	renderTitle(): Element
	{
		this.layout.title = Tag.render`<span class="rpa-stage-phase-title">
			<span class="rpa-stage-phase-title-inner">
				<span class="rpa-stage-phase-name">${Text.encode(this.getName())}</span>
				<span class="rpa-stage-phase-icon-edit" onclick="${this.switchToEditMode.bind(this)}" title="${Loc.getMessage('RPA_STAGES_STAGE_CHANGE_TITLE')}"></span>
			</span>
			<span class="rpa-stage-phase-title-form">
				${this.getNameInput()}
			</span>
		</span>`;

		return this.layout.title;
	}

	getNameInput(): Element
	{
		if(!this.layout.nameInput)
		{
			this.layout.nameInput = Tag.render`<input class="rpa-stage-phase-title-input" value="${Text.encode(this.getName())}" onblur="${this.switchToViewMode.bind(this)}" />`;
		}

		return this.layout.nameInput;
	}

	switchToEditMode()
	{
		this.getContainer().classList.add("rpa-stage-edit-mode");
		this.getNameInput().value = this.getName();
		this.focusNameInput();
	}

	switchToViewMode()
	{
		this.name = this.getNameInput().value;
		this.getContainer().classList.remove("rpa-stage-edit-mode");
		this.render();
	}

	focusNameInput()
	{
		this.getNameInput().focus();
		this.getNameInput().selectionStart = this.getNameInput().value.length;
	}

	adjustColors()
	{
		const backgroundColor = '#' + this.getColor();
		const textColor = Manager.calculateTextColor(backgroundColor);
		this.layout.innerContainer.style.backgroundColor = backgroundColor;
		this.layout.innerContainer.style.color = textColor;
	}

	getColorPicker()
	{
		if (this.colorPicker)
		{
			return this.colorPicker;
		}

		this.colorPicker = new BX.ColorPicker({
			bindElement: this.getColorButton(),
			onColorSelected: (color: string) =>
			{
				this.setColor(color.substr(1));
				this.adjustColors();
			},
			// popupOptions: {
			// 	events: {
			// 		onPopupClose: this.focusTextBox.bind(this)
			// 	}
			// }
		});

		return this.colorPicker;
	}

	showColorPicker()
	{
		this.getColorPicker().open();
	}

	restoreInitialData()
	{
		this.name = this.initialData.name;
		this.color = this.initialData.color;

		this.render();
	}

	destroy()
	{
		Dom.remove(this.getContainer());
		if(this.getComponent())
		{
			this.getComponent().removeStage(this);
		}
	}
}

class DragDropItem
{
	constructor(options: {
		stage: Stage
	}) {
		this.stage = options.stage;
		this.itemContainer = this.stage.getContainer();
		this.draggableItemContainer = null;
		this.dragElement = null;
	}

	init()
	{
		const dragButton = this.itemContainer.querySelector('.rpa-stage-phase-icon');

		if(jsDD)
		{
			dragButton.onbxdragstart = this.onDragStart.bind(this);
			dragButton.onbxdrag = this.onDrag.bind(this);
			dragButton.onbxdragstop = this.onDragStop.bind(this);

			jsDD.registerObject(dragButton);

			this.itemContainer.onbxdestdraghover = this.onDragEnter.bind(this);
			this.itemContainer.onbxdestdraghout = this.onDragLeave.bind(this);
			this.itemContainer.onbxdestdragfinish = this.onDragDrop.bind(this);

			jsDD.registerDest(this.itemContainer, 30);
		}
	}

	onDragStart()
	{
		Dom.addClass(this.itemContainer, "rpa-edit-robot-btn-item-disabled");

		if (!this.dragElement)
		{
			this.dragElement = this.itemContainer.cloneNode(true);

			this.dragElement.style.position = "absolute";
			this.dragElement.style.width = this.itemContainer.offsetWidth + "px";
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
		Dom.removeClass(this.itemContainer, "rpa-edit-robot-btn-item-disabled");
		Dom.remove(this.dragElement);
		this.dragElement = null;
	}

	onDragEnter(draggableItem)
	{
		this.draggableItemContainer = draggableItem.closest('.rpa-stage-phase');
		if (this.draggableItemContainer !== this.itemContainer)
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
		if (this.draggableItemContainer !== this.itemContainer)
		{
			this.hideDragTarget();
			Dom.remove(this.draggableItemContainer);
			Dom.insertBefore(this.draggableItemContainer, this.itemContainer);
			this.stage.getComponent().onMoveStage(this.stage);
		}
	}

	showDragTarget()
	{
		Dom.addClass(this.getDragTarget(), 'rpa-edit-robot-btn-item-drag-target-shown');
		this.getDragTarget().style.height = this.itemContainer.offsetHeight + "px";
	}

	hideDragTarget()
	{
		Dom.removeClass(this.getDragTarget(), "rpa-edit-robot-btn-item-drag-target-shown");
		this.getDragTarget().style.height = 0;
	}

	getDragTarget()
	{
		if (!this.dragTarget)
		{
			this.dragTarget = Tag.render`<div class="rpa-edit-robot-btn-item-drag-target"></div>`;
			Dom.prepend(this.dragTarget, this.itemContainer);
		}

		return this.dragTarget;
	}
}

class DragDropItemContainer
{
	constructor(options) {
		this.container = options;
		this.items = [];
		this.height = null;
	}

	init()
	{
		if(jsDD)
		{
			this.container.onbxdestdraghover = BX.delegate(this.onDragEnter, this);
			this.container.onbxdestdraghout = BX.delegate(this.onDragLeave, this);
			this.container.onbxdestdragfinish = BX.delegate(this.onDragDrop, this);

			jsDD.registerDest(this.container, 40);
		}
	}

	onDragEnter(draggableItem)
	{
		this.draggableItemContainer = draggableItem.closest('.rpa-stage-phase');
		this.height = this.draggableItemContainer.offsetHeight;

		this.showDragTarget();
	}

	onDragLeave()
	{
		this.hideDragTarget();
	}

	onDragDrop()
	{
		this.hideDragTarget();
		Dom.remove(this.draggableItemContainer);
		Dom.insertBefore(this.draggableItemContainer, this.dragTarget);
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

namespace.DragDropItem = DragDropItem;
namespace.DragDropItemContainer = DragDropItemContainer;
namespace.StagesComponent = StagesComponent;
