import {Dom, Tag} from 'main.core';
import {Loader} from 'main.loader';
import {BaseEvent} from 'main.core.events';
import {Entity} from '../entity';
import {Header} from './header';
import {EpicCreationButton} from './epic.creation.button';
import {GroupActionsButton} from '../group.actions.button';
import {ListItems} from '../list.items';
import {Item} from '../../item/item';

import type {ItemParams} from '../../item/item';

import '../../css/backlog.css';

export type BacklogParams = {
	id: number,
	items?: Array<ItemParams>,
	pageNumberItems: number
};

export class Backlog extends Entity
{
	constructor(params: BacklogParams)
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Scrum.Backlog');

		this.setBacklogParams(params);

		this.header = null;
		this.epicCreationButton = null;
	}

	setBacklogParams(params: BacklogParams)
	{
		params.items.forEach((itemData) => {
			const item = new Item(itemData);
			this.items.set(item.itemId, item);
		});

		this.pageNumberItems = parseInt(params.pageNumberItems, 10);
	}

	static buildBacklog(backlogData: BacklogParams): Backlog
	{
		const backlog = new Backlog(backlogData);
		backlog.addHeader(new Header(backlog));
		backlog.addEpicCreationButton(new EpicCreationButton());
		backlog.addGroupActionsButton(new GroupActionsButton());
		backlog.addListItems(new ListItems(backlog));
		return backlog;
	}

	addHeader(header: Header)
	{
		this.header = header;
		this.header.subscribe('openListEpicGrid', () => this.emit('openListEpicGrid'));
		this.header.subscribe('openDefinitionOfDone', () => this.emit('openDefinitionOfDone'));
	}

	addEpicCreationButton(epicCreationButton: EpicCreationButton)
	{
		this.epicCreationButton = epicCreationButton;
		this.epicCreationButton.subscribe('openAddEpicForm', () => this.emit('openAddEpicForm'));
	}

	getEntityType()
	{
		return 'backlog';
	}

	isDisabled()
	{
		return false;
	}

	getPageNumberItems(): number
	{
		return this.pageNumberItems;
	}

	incrementPageNumberItems()
	{
		this.pageNumberItems++;
	}

	render(): HTMLElement
	{
		this.node = Tag.render`
			<div class="tasks-scrum-backlog">
				<div class="tasks-scrum-backlog-header">
					${this.header ? this.header.render() : ''}
				</div>
				<div class="tasks-scrum-backlog-actions">
					${this.epicCreationButton ? this.epicCreationButton.render() : ''}
					${this.groupActionsButton ? this.groupActionsButton.render() : ''}
				</div>
				<div class="tasks-scrum-backlog-items">
					${this.listItems ? this.listItems.render() : ''}
				</div>
				<div class="tasks-scrum-backlog-items-loader"></div>
			</div>
		`;

		this.backlogItemsLoaderNode = this.node.querySelector('.tasks-scrum-backlog-items-loader');
		this.bindBacklogItemsLoader(this.backlogItemsLoaderNode);

		return this.node;
	}

	setItem(newItem: Item)
	{
		super.setItem(newItem);

		this.updateStoryPoints();
	}

	removeItem(item: Item)
	{
		super.removeItem(item);

		this.updateStoryPoints();
	}

	addNumberTasks(value: number)
	{
		super.addNumberTasks(value);

		if (this.header)
		{
			this.header.updateNumberTasks();
		}
	}

	subtractNumberTasks(value: number)
	{
		super.subtractNumberTasks(value);

		if (this.header)
		{
			this.header.updateNumberTasks();
		}
	}

	updateStoryPoints()
	{
		super.updateStoryPoints();

		if (this.header)
		{
			this.header.setStoryPoints(this.getStoryPoints().getPoints());
		}
	}

	onActivateGroupMode(baseEvent: BaseEvent)
	{
		super.onActivateGroupMode(baseEvent);

		Dom.addClass(this.node.querySelector('.tasks-scrum-backlog-items'), 'tasks-scrum-backlog-items-group-mode');
	}

	onDeactivateGroupMode(baseEvent: BaseEvent)
	{
		super.onDeactivateGroupMode(baseEvent);

		Dom.removeClass(this.node.querySelector('.tasks-scrum-backlog-items'), 'tasks-scrum-backlog-items-group-mode');
	}

	bindBacklogItemsLoader(loader: HTMLElement)
	{
		this.setActiveLoadBacklogItems(false);

		const observer = new IntersectionObserver((entries) =>
			{
				if(entries[0].isIntersecting === true)
				{
					if (!this.isActiveLoadBacklogItems())
					{
						this.emit('loadBacklogItems');
					}
				}
			},
			{
				threshold: [0]
			}
		);

		observer.observe(loader);
	}

	setActiveLoadBacklogItems(value: boolean)
	{
		this.activeLoadBacklogItems = Boolean(value);
	}

	isActiveLoadBacklogItems(): boolean
	{
		return this.activeLoadBacklogItems;
	}

	showItemsLoader()
	{
		const listPosition = Dom.getPosition(this.backlogItemsLoaderNode);

		const loader = new Loader({
			target: this.backlogItemsLoaderNode,
			size: 60,
			mode: 'inline',
			color: 'rgba(82, 92, 105, 0.9)',
			offset: {
				left: `${(listPosition.width / 2 - 30)}px`
			}
		});

		loader.show();

		return loader;
	}
}