import {Dom, Type} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';
import {Loader} from 'main.loader';

import 'main.polyfill.intersectionobserver';

import {Item} from '../item/item';
import {ListItems} from './list.items';

import {Input} from '../utility/input';
import {StoryPointsStorage} from '../utility/story.points.storage';

import type {Views} from '../view/view';

type EntityParams = {
	id: number,
	views?: Views,
	numberTasks?: number,
	isExactSearchApplied: 'Y' | 'N',
	storyPoints?: string,
	pageSize: number,
	pageNumberItems: number,
	isShortView: 'Y' | 'N',
	mandatoryExists: 'Y' | 'N'
};

export class Entity extends EventEmitter
{
	constructor(params: EntityParams)
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Scrum.Entity');

		this.storyPoints = new StoryPointsStorage();

		this.items = new Map();

		this.groupMode = false;
		this.groupModeItems = new Map();

		this.setEntityParams(params);

		this.observerLoadItems = null;

		this.node = null;
		this.listItems = null;

		this.itemLoader = null;
		this.itemsLoaderNode = null;

		this.blank = null;
		this.dropzone = null;
		this.emptySearchStub = null;

		this.hideCont = false;
	}

	setEntityParams(params: EntityParams)
	{
		this.setId(params.id);
		this.setViews(params.views);
		this.setNumberTasks(params.numberTasks);
		this.setStoryPoints(params.storyPoints);
		this.setShortView(params.isShortView);
		this.setMandatory(params.mandatoryExists);
		this.setExactSearchApplied(params.isExactSearchApplied);

		this.pageSize = parseInt(params.pageSize, 10);
		this.setPageNumberItems(params.pageNumberItems);
	}

	setListItems(entity: Entity)
	{
		this.listItems = new ListItems(entity);
	}

	getListItems(): ?ListItems
	{
		return this.listItems;
	}

	setShortView(value: string)
	{
		this.shortView = (value === 'Y' ? 'Y' : 'N');

		this.getItems()
			.forEach((item: Item) => {
				if (item.isParentTask() && item.isShownSubTasks())
				{
					item.hideSubTasks();
				}
				item.setShortView(this.shortView);
			})
		;
	}

	getShortView(): 'Y' | 'N'
	{
		return this.shortView;
	}

	isShortView(): boolean
	{
		return this.shortView === 'Y';
	}

	setMandatory(value: 'Y' | 'N')
	{
		this.mandatoryExists = value === 'Y';
	}

	getNode(): ?HTMLElement
	{
		return this.node;
	}

	setId(id: number)
	{
		this.id = (Type.isInteger(id) ? parseInt(id, 10) : 0);

		if (this.listItems)
		{
			this.listItems.setEntityId(this.id);
		}
	}

	getId(): number
	{
		return this.id;
	}

	setViews(views)
	{
		this.views = (Type.isPlainObject(views) ? views : {});
	}

	getViews()
	{
		return this.views;
	}

	getEntityType()
	{
		return 'entity';
	}

	isBacklog(): boolean
	{
		return this.getEntityType() === 'backlog';
	}

	isHideContent(): boolean
	{
		return this.hideCont;
	}

	isActive(): boolean
	{
		return false;
	}

	isCompleted(): boolean
	{
		return false;
	}

	getListItemsNode(): ?HTMLElement
	{
		return (this.listItems ? this.listItems.getListNode() : null);
	}

	isEmpty(): boolean
	{
		return (this.items.size === 0);
	}

	getNumberItems(): number
	{
		return this.items.size;
	}

	getItems(): Map
	{
		return this.items;
	}

	hasItem(item: Item): boolean
	{
		return this.items.has(item.getId());
	}

	getPageSize(): number
	{
		return this.pageSize;
	}

	getPageNumberItems(): number
	{
		return this.pageNumberItems;
	}

	setPageNumberItems(pageNumberItems: number)
	{
		if (Type.isInteger(pageNumberItems))
		{
			pageNumberItems = parseInt(pageNumberItems, 10);
		}

		this.pageNumberItems = pageNumberItems ? pageNumberItems : 1;
	}

	incrementPageNumberItems()
	{
		this.pageNumberItems++;
	}

	decrementPageNumberItems()
	{
		this.pageNumberItems--;
	}

	recalculateItemsSort()
	{
		const listItemsNode = this.getListItemsNode();
		if (!listItemsNode)
		{
			return;
		}

		let sort = 1;
		listItemsNode.querySelectorAll('.tasks-scrum-item').forEach((node: HTMLElement) => {
			const item = this.getItems().get(parseInt(node.dataset.id, 10));
			if (item)
			{
				item.setSort(sort);
				sort++;
			}
		});
	}

	setItem(newItem: Item)
	{
		this.items.set(newItem.getId(), newItem);

		this.subscribeToItem(newItem);

		[...this.items.values()].map((item) => {
			this.setItemMoveActivity(item);
		});

		newItem.setEntityType(this.getEntityType());
		newItem.setShortView(this.getShortView());

		this.hideBlank();
		this.hideDropzone();
		this.hideEmptySearchStub();

		this.adjustListItemsWidth();
	}

	setItemMoveActivity(item: Item)
	{
		item.setMoveActivity(this.items.size > 2);
	}

	removeItem(item: Item)
	{
		if (this.items.has(item.getId()))
		{
			this.items.delete(item.getId());

			item.unsubscribeAll();

			[...this.items.values()].map((item) => {
				this.setItemMoveActivity(item);
			});

			if (item.isParentTask())
			{
				item.getSubTasks().getList().forEach((item: Item) => {
					this.removeItem(item);
				});
			}

			this.pageNumberItems = 1;

			this.adjustListItemsWidth();
		}
	}

	appendItemToList(item: Item)
	{
		Dom.insertBefore(item.render(), this.getListItemsNode().lastElementChild);

		this.adjustListItemsWidth();
	}

	isNodeCreated(): boolean
	{
		return !Type.isNull(this.node);
	}

	setNumberTasks(numberTasks: number)
	{
		this.numberTasks = (Type.isInteger(numberTasks) ? parseInt(numberTasks, 10) : 0);
	}

	getNumberTasks(): number
	{
		return (this.numberTasks ? this.numberTasks : this.getItems().size);
	}

	setExactSearchApplied(value: 'Y' | 'N')
	{
		this.exactSearchApplied = value === 'Y';
	}

	isExactSearchApplied(): boolean
	{
		return this.exactSearchApplied;
	}

	onAfterAppend()
	{
		[...this.items.values()].map((item) => {
			this.subscribeToItem(item);
			this.setItemMoveActivity(item);
		});

		this.setStats();

		if (!this.isCompleted())
		{
			this.itemsLoaderNode = this.getNode().querySelector('.tasks-scrum-entity-items-loader');
			if (this.getNumberItems() >= this.pageSize)
			{
				this.bindItemsLoader();
			}
		}

		this.adjustListItemsWidth();
	}

	subscribeToItem(item: Item)
	{
		if (!this.getListItemsNode())
		{
			return;
		}

		item.setEntityType(this.getEntityType());

		item.subscribe('updateItem', (baseEvent: BaseEvent) => {
			this.emit('updateItem', baseEvent.getData());
		});

		item.subscribe('showTask', (baseEvent: BaseEvent) => {
			this.emit('showTask', baseEvent.getTarget());
		});

		item.subscribe('destroyActionPanel', (baseEvent: BaseEvent) => {
			this.emit('destroyActionPanel', baseEvent.getTarget());
		});

		item.subscribe('changeTaskResponsible', (baseEvent: BaseEvent) => {
			this.emit('changeTaskResponsible', baseEvent.getTarget());
		});

		item.subscribe('onShowResponsibleDialog', (baseEvent: BaseEvent) => {
			this.emit('onShowResponsibleDialog', baseEvent.getData());
		});

		item.subscribe('filterByEpic', (baseEvent: BaseEvent) => {
			this.emit('filterByEpic', baseEvent.getData());
		});

		item.subscribe('filterByTag', (baseEvent: BaseEvent) => {
			this.emit('filterByTag', baseEvent.getData());
		});

		item.subscribe('toggleActionPanel', (baseEvent: BaseEvent) => {
			this.emit('toggleActionPanel', baseEvent.getTarget());
		});

		item.subscribe('showLinked', (baseEvent: BaseEvent) => {
			this.emit('showLinked', baseEvent.getTarget());
		});
	}

	getItemByItemId(itemId: number | string): ?Item
	{
		return this.items.get((Type.isInteger(itemId) ? parseInt(itemId, 10) : itemId));
	}

	getItemBySourceId(sourceId: number): ?Item
	{
		return [...this.items.values()].find((item: Item) => item.getSourceId() === sourceId);
	}

	getItemsByParentTaskId(parentTaskId: number): Map<number, Item>
	{
		const items = new Map();

		[...this.items.values()].map((item: Item) => {
			if (item.getParentTaskId() === parentTaskId)
			{
				items.set(item.getId(), item);
			}
		});

		return items;
	}

	setStoryPoints(storyPoints: string)
	{
		this.storyPoints.setPoints(storyPoints);

		this.setStats();
	}

	getStoryPoints(): StoryPointsStorage
	{
		return this.storyPoints;
	}

	isFirstItem(item: Item): boolean
	{
		const listItemsNode = this.getListItemsNode();
		const itemNode = item.getNode();
		const firstElementChild = listItemsNode.firstElementChild;

		return firstElementChild.isEqualNode(itemNode);
	}

	isLastItem(item: Item): boolean
	{
		const listItemsNode = this.getListItemsNode();
		const itemNode = item.getNode();

		return listItemsNode.lastElementChild.isEqualNode(itemNode);
	}

	getFirstItemNode(input?: Input): ?HTMLElement
	{
		const listItemsNode = this.getListItemsNode();

		const fistNode = listItemsNode.firstElementChild;

		if (input && fistNode.isEqualNode(input.getNode()))
		{
			return fistNode.nextElementSibling;
		}
		else
		{
			return fistNode;
		}
	}

	getLoaderNode(): ?HTMLElement
	{
		return this.itemsLoaderNode ? this.itemsLoaderNode : null;
	}

	fadeOut()
	{
		Dom.addClass(this.getListItemsNode(), 'tasks-scrum__entity-items-faded');
	}

	fadeIn()
	{
		Dom.removeClass(this.getListItemsNode(), 'tasks-scrum__entity-items-faded');
	}

	activateGroupMode()
	{
		this.groupMode = true;

		this.getItems().forEach((item: Item) => {
			item.activateGroupMode();
		});
	}

	deactivateGroupMode()
	{
		this.groupMode = false;

		this.getItems().forEach((item: Item) => {
			item.deactivateGroupMode();
		});

		this.groupModeItems.forEach((item: Item) => {
			item.removeItemFromGroupMode();
		});
		this.groupModeItems.clear();

		this.emit('deactivateGroupMode');
	}

	isGroupMode(): boolean
	{
		return this.groupMode;
	}

	addItemToGroupMode(item: Item)
	{
		this.groupModeItems.set(item.getId(), item);

		item.addItemToGroupMode();
	}

	removeItemFromGroupMode(item: Item)
	{
		this.groupModeItems.delete(item.getId());

		item.removeItemFromGroupMode();
	}

	hasItemInGroupMode(item: Item): boolean
	{
		return this.groupModeItems.has(item.getId());
	}

	getGroupModeItems(): Map<number, Item>
	{
		return this.groupModeItems;
	}

	bindItemsLoader()
	{
		if (!this.itemsLoaderNode)
		{
			return;
		}

		Dom.addClass(this.itemsLoaderNode, '--waiting');

		this.showItemsLoader();

		if (Type.isUndefined(IntersectionObserver))
		{
			return;
		}

		if (this.observerLoadItems)
		{
			this.observerLoadItems.disconnect();
		}

		this.observerLoadItems = new IntersectionObserver((entries) =>
			{
				if (entries[0].isIntersecting === true)
				{
					if (!this.isActiveLoadItems())
					{
						this.emit('loadItems');
					}
				}
			},
			{
				threshold: [0]
			}
		);

		this.observerLoadItems.observe(this.itemsLoaderNode);
	}

	unbindItemsLoader()
	{
		if (this.observerLoadItems)
		{
			this.observerLoadItems.disconnect();
		}

		if (this.itemsLoaderNode)
		{
			this.hideItemsLoader();

			Dom.removeClass(this.itemsLoaderNode, '--waiting');
		}
	}

	setActiveLoadItems(value: boolean)
	{
		this.activeLoadItems = Boolean(value);
	}

	isActiveLoadItems(): boolean
	{
		return this.activeLoadItems === true;
	}

	showItemsLoader(): Loader
	{
		const listPosition = Dom.getPosition(this.itemsLoaderNode);

		if (this.itemLoader)
		{
			this.itemLoader.destroy();
		}

		this.itemLoader = new Loader({
			target: this.itemsLoaderNode,
			size: 60,
			mode: 'inline',
			offset: {
				top: '7px',
				left: `${((listPosition.width / 2) - 45)}px`
			}
		});

		if (this.getNumberItems() >= this.pageSize)
		{
			this.itemLoader.show();
		}

		return this.itemLoader;
	}

	hideItemsLoader()
	{
		if (this.itemLoader)
		{
			this.itemLoader.hide();
		}
	}

	setStats() {}

	showBlank()
	{
		if (this.blank)
		{
			Dom.addClass(this.blank.getNode(), '--open');
		}
	}

	hideBlank()
	{
		if (this.blank)
		{
			Dom.removeClass(this.blank.getNode(), '--open');
		}
	}

	showDropzone()
	{
		if (this.dropzone)
		{
			Dom.addClass(this.dropzone.getNode(), '--open');
		}
	}

	hideDropzone()
	{
		if (this.dropzone)
		{
			Dom.removeClass(this.dropzone.getNode(), '--open');
		}
	}

	showEmptySearchStub()
	{
		if (this.emptySearchStub)
		{
			Dom.addClass(this.emptySearchStub.getNode(), '--open');
		}
	}

	hideEmptySearchStub()
	{
		if (this.emptySearchStub)
		{
			Dom.removeClass(this.emptySearchStub.getNode(), '--open');
		}
	}

	getDropzone(): ?HTMLElement
	{
		return this.dropzone ? this.dropzone.getNode() : null;
	}

	appendNodeAfterItem(newItemNode: HTMLElement, bindItemNode: HTMLElement)
	{
		if (bindItemNode.nextElementSibling)
		{
			Dom.insertBefore(newItemNode, bindItemNode.nextElementSibling);
		}
		else
		{
			if (this.getLoaderNode())
			{
				Dom.append(newItemNode, this.getLoaderNode());
			}
			else
			{
				Dom.append(newItemNode, this.getListItemsNode());
			}
		}
	}

	adjustListItemsWidth()
	{
		if (Type.isNull(this.getListItemsNode()))
		{
			return;
		}

		const hasListItemsScroll = this.getListItemsNode().scrollHeight > this.getListItemsNode().clientHeight;

		if (hasListItemsScroll)
		{
			this.getListItems().addScrollbar();
		}
		else
		{
			this.getListItems().removeScrollbar();
		}
	}
}
