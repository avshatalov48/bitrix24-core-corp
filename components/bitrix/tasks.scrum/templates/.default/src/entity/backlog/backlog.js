import {Event, Dom, Tag} from 'main.core';
import {BaseEvent} from 'main.core.events';

import {Header} from './header';
import {Blank} from '../blank';
import {Dropzone} from '../dropzone';
import {EmptySearchStub} from '../empty.search.stub';

import {Entity} from '../entity';
import {Item, ItemParams} from '../../item/item';

import '../../css/backlog.css';

export type BacklogParams = {
	id: number,
	items?: Array<ItemParams>
};

export class Backlog extends Entity
{
	constructor(params: BacklogParams)
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Scrum.Backlog');

		this.setBacklogParams(params);

		this.header = null;
	}

	setBacklogParams(params: BacklogParams)
	{
		params.items.forEach((itemData) => {
			const item = Item.buildItem(itemData);
			item.setShortView(this.getShortView());
			this.items.set(item.getId(), item);
		});
	}

	static buildBacklog(backlogData: BacklogParams): Backlog
	{
		const backlog = new Backlog(backlogData);

		backlog.setHeader(backlog);
		backlog.setBlank(backlog);
		backlog.setDropzone(backlog);
		backlog.setEmptySearchStub(backlog);
		backlog.setListItems(backlog);

		return backlog;
	}

	setHeader(backlog: Backlog)
	{
		this.header = new Header(backlog);

		this.header.subscribe('epicClick', (baseEvent: BaseEvent) => {
			this.emit('openAddEpicForm', baseEvent.getTarget())
		});

		this.header.subscribe('taskClick', (baseEvent: BaseEvent) => {
			if (this.mandatoryExists)
			{
				this.emit('openAddTaskForm', baseEvent.getTarget());
			}
			else
			{
				this.emit('showInput', baseEvent.getTarget());
			}
		});
	}

	setBlank(backlog: Backlog)
	{
		this.blank = new Blank(backlog);
	}

	setDropzone(backlog: Backlog)
	{
		this.dropzone = new Dropzone(backlog);

		this.dropzone.subscribe('createTask', () => {
			if (this.mandatoryExists)
			{
				this.emit('openAddTaskForm');
			}
			else
			{
				this.emit('showInput');
			}
		});
	}

	setEmptySearchStub(backlog: Backlog)
	{
		this.emptySearchStub = new EmptySearchStub(backlog);
	}

	setNumberTasks(numberTasks: number)
	{
		super.setNumberTasks(numberTasks);

		if (this.header)
		{
			this.header.updateTaskCounter(this.getNumberTasks());
		}
	}

	getEntityType(): string
	{
		return 'backlog';
	}

	isDisabled(): boolean
	{
		return false;
	}

	render(): HTMLElement
	{
		this.node = Tag.render`
			<div class="tasks-scrum__backlog">
				<div class="tasks-scrum__content --with-header --open">
					${this.header ? this.header.render() : ''}
					${this.blank ? this.blank.render() : ''}
					${this.dropzone ? this.dropzone.render() : ''}
					${this.emptySearchStub ? this.emptySearchStub.render() : ''}
					${this.listItems ? this.listItems.render() : ''}
				</div>
			</div>
		`;

		Event.bind(this.node.querySelector('.tasks-scrum__content-items'), 'scroll', this.onItemsScroll.bind(this));

		return this.node;
	}

	setItem(newItem: Item)
	{
		super.setItem(newItem);

		if (newItem.getNode())
		{
			Dom.addClass(newItem.getNode(), '--item-backlog');
		}
	}

	removeItem(item: Item)
	{
		super.removeItem(item);

		if (this.isEmpty())
		{
			this.emit('showBlank');
		}
	}

	onAfterAppend()
	{
		super.onAfterAppend();

		if (this.isEmpty())
		{
			this.emit('showBlank');
		}
	}

	onItemsScroll()
	{
		this.emit('itemsScroll');
	}
}
