import {Dom, Tag} from 'main.core';
import {BaseEvent} from 'main.core.events';
import {Entity} from '../entity';
import {Header} from './header';
import {EpicCreationButton} from './epic.creation.button';
import {GroupActionsButton} from '../group.actions.button';
import {ListItems} from '../list.items';
import {Item} from '../../item/item';

import '../../css/backlog.css';

type backlogParams = {
	items?: Array
};

export class Backlog extends Entity
{
	constructor(backlogData: backlogParams = {})
	{
		super(backlogData);

		this.setEventNamespace('BX.Tasks.Scrum.Backlog');

		backlogData.items.forEach((itemData) => {
			const item = new Item(itemData);
			this.items.set(item.itemId, item);
		});

		this.header = null;
		this.epicCreationButton = null;
	}

	static buildBacklog(backlogData: backlogParams = {}): Backlog
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
			</div>
		`;
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
}