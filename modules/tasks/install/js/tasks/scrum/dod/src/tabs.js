import {Dom, Event, Loc, Tag, Text} from 'main.core';
import {EventEmitter} from 'main.core.events';

import {MessageBox, MessageBoxButtons} from 'ui.dialogs.messagebox';

import {ItemType} from './item.type';
import {TypeStorage} from './type.storage';

export class Tabs extends EventEmitter
{
	constructor()
	{
		super();

		this.setEventNamespace('BX.Tasks.Scrum.Dod.Tabs');

		this.sidePanelManager = BX.SidePanel.Instance;

		this.tabNodes = new Map();

		this.activeType = null;
		this.previousType = null;
	}

	setTypeStorage(typeStorage: TypeStorage)
	{
		this.typeStorage = typeStorage;
	}

	render(): HTMLElement
	{
		const sidebarClass = 'tasks-scrum-dod-settings-container-sidebar'
			+ ' tasks-scrum-dod-settings-container-sidebar-settings';

		this.node = Tag.render`
			<div class="${sidebarClass}">
				${[...this.typeStorage.getTypes().values()].map((type: ItemType) => this.renderTab(type))}
			</div>
		`;

		return this.node;
	}

	isEmpty(): boolean
	{
		return this.tabNodes.size === 0;
	}

	renderTab(type: ItemType): HTMLElement
	{
		if (this.isEmptyType(type))
		{
			const addNode = Tag.render`
				<div class="sidebar-tab-link">
					<div class="tasks-scrum-dod-settings-type-name">
						+ ${Loc.getMessage('TASKS_SCRUM_CREATE_TYPE')}
					</div>
				</div>
			`;

			Event.bind(addNode, 'click', this.createType.bind(this));

			return addNode;
		}
		else
		{
			const tabClass = this.isActiveType(type) ? 'sidebar-tab sidebar-tab-active' : 'sidebar-tab';

			const tabNode = Tag.render`
				<div class="${tabClass}">
					<div class="tasks-scrum-dod-settings-type-name">
						${Text.encode(type.getName())}
					</div>
					<div class="tasks-scrum-dod-settings-type-edit"></div>
					<div class="tasks-scrum-dod-settings-type-remove"></div>
				</div>
			`;

			this.tabNodes.set(type.getId(), tabNode);

			Event.bind(tabNode, 'click', (event) => {
				const edit = Dom.hasClass(event.target, 'tasks-scrum-dod-settings-type-edit');
				const remove = Dom.hasClass(event.target, 'tasks-scrum-dod-settings-type-remove');
				if (!this.isActiveType(type))
				{
					this.switchType(type, tabNode);
				}
				if (edit)
				{
					this.changeTypeName(type, tabNode);
				}
				if (remove)
				{
					this.removeType(type, tabNode);
				}
			});

			return tabNode;
		}
	}

	addType(newType: ItemType, tmpType?: ItemType)
	{
		if (tmpType)
		{
			Dom.remove(this.tabNodes.get(tmpType.getId()));
			this.tabNodes.delete(tmpType.getId());
		}

		const node = this.renderTab(newType);

		Dom.insertBefore(node, this.node.lastElementChild);

		this.switchType(newType, node);
	}

	switchType(type: ItemType, typeNode: HTMLElement, savePrevious: boolean = true)
	{
		this.tabNodes.forEach((node: HTMLElement) => {
			Dom.removeClass(node, 'sidebar-tab-active');
		});

		Dom.addClass(typeNode, 'sidebar-tab-active');

		if (savePrevious && !this.isEmpty())
		{
			this.setPreviousType(this.getActiveType());
		}
		else
		{
			this.setPreviousType(null);
		}

		this.setActiveType(type);

		this.emit('switchType', type);
	}

	createType()
	{
		const type = new ItemType();
		type.setSort(this.typeStorage.getTypes().size);

		this.tabNodes.forEach((node: HTMLElement) => {
			Dom.removeClass(node, 'sidebar-tab-active');
		});

		const node = Tag.render`
			<div class="sidebar-tab sidebar-tab-active">
				<input type="text" class="tasks-scrum-dod-settings-type-name-input">
			</div>
		`;
		const nameNode = Tag.render`<div class="tasks-scrum-dod-settings-type-name"></div>`;

		Dom.insertBefore(node, this.node.lastElementChild);

		const input = node.querySelector('input');

		Event.bind(input, 'change', (event) => {
			type.setName(event.target['value']);
			this.emit('createType', type);
			nameNode.textContent = Text.encode(type.getName());
			Dom.replace(input, nameNode);
		}, true);

		Event.bind(input, 'blur', (event) => {
			if (event.target['value'].trim() === '')
			{
				Dom.remove(node);
			}
		}, true);

		Event.bind(input, 'keydown', (event) => {
			if (event.isComposing || event.keyCode === 13)
			{
				input.blur();
			}
		});

		input.focus();

		this.tabNodes.set(type.getId(), node);
	}

	changeTypeName(type: ItemType, typeNode: HTMLElement)
	{
		const inputNode = Tag.render`
			<input type="text" class="tasks-scrum-dod-settings-type-name-input" value="${Text.encode(type.getName())}">
		`;
		const nameNode = typeNode.querySelector('.tasks-scrum-dod-settings-type-name');

		Event.bind(inputNode, 'change', (event) => {
			type.setName(event.target['value'])
			this.emit('changeTypeName', type);
			inputNode.blur();
		}, true);

		Event.bind(inputNode, 'blur', () => {
			nameNode.textContent = Text.encode(type.getName());
			Dom.replace(inputNode, nameNode);
		}, true);

		Event.bind(inputNode, 'keydown', (event) => {
			if (event.isComposing || event.keyCode === 13)
			{
				inputNode.blur();
			}
		});

		Dom.replace(nameNode, inputNode);

		inputNode.focus();
		inputNode.setSelectionRange(type.getName().length, type.getName().length);
	}

	removeType(type: ItemType, typeNode: HTMLElement)
	{
		const popupOptions = {};
		const currentSlider = this.sidePanelManager.getTopSlider();
		if (currentSlider)
		{
			popupOptions.targetContainer = currentSlider.getContainer();
		}

		(new MessageBox({
			message: Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_REMOVE_TYPE'),
			popupOptions: popupOptions,
			okCaption: Loc.getMessage('TASKS_SCRUM_BUTTON_TEXT_REMOVE'),
			buttons: MessageBoxButtons.OK_CANCEL,
			onOk: (messageBox) => {
				this.tabNodes.delete(type.getId());
				if (this.isActiveType(type))
				{
					this.setActiveType(null);
				}
				this.setPreviousType(null);
				Dom.remove(typeNode);
				this.emit('removeType', type);
				messageBox.close();
			}
		})).show();
	}

	setActiveType(type: ?ItemType)
	{
		this.activeType = type;
	}

	getActiveType(): ?ItemType
	{
		return this.activeType;
	}

	setPreviousType(type: ?ItemType)
	{
		this.previousType = type;
	}

	getPreviousType(): ?ItemType
	{
		return this.previousType;
	}

	isActiveType(type: ItemType): boolean
	{
		return (
			this.activeType
			&& this.activeType.getId() === type.getId()
		);
	}

	setEmptyType(type: ItemType)
	{
		this.emptyType = type;
	}

	isEmptyType(type: ItemType): boolean
	{
		return type.getId() === this.emptyType.getId();
	}

	switchToType(type: ItemType)
	{
		this.switchType(type, this.tabNodes.get(type.getId()), false);
	}
}