import {Dom, Tag} from 'main.core';
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
		this.epicCreationButton = null;
	}

	setBacklogParams(params: BacklogParams)
	{
		params.items.forEach((itemData) => {
			const item = new Item(itemData);
			this.items.set(item.itemId, item);
		});
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
				<div class="tasks-scrum-entity-items-loader"></div>
			</div>
		`;

		this.itemsLoaderNode = this.node.querySelector('.tasks-scrum-entity-items-loader');
		this.bindItemsLoader(this.itemsLoaderNode);

		return this.node;
	}

	setNumberTasks(numberTasks: number)
	{
		super.setNumberTasks(numberTasks);

		if (this.header)
		{
			this.header.updateNumberTasks();
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

	updateStoryPointsNode()
	{
		super.updateStoryPointsNode();

		if (this.header)
		{
			this.header.setStoryPoints(this.getStoryPoints().getPoints());
		}
	}
}