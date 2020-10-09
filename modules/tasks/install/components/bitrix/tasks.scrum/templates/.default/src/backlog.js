import {Event, Loc, Tag, Text} from 'main.core';
import {Entity} from './entity';
import {Item} from './item';

import './css/backlog.css';

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
	}

	getEntityType()
	{
		return 'backlog';
	}

	isDisabled()
	{
		return false;
	}

	/**
	 * @returns {HTMLElement}
	 */
	render()
	{
		const createBacklog = (title, actions, items) => {
			return Tag.render`
				<div class="tasks-scrum-backlog">
					${title}
					${actions}
					${items}
				</div>
			`;
		};

		const createBacklogTitle = () => {
			this.headerNodeId = 'tasks-scrum-backlog-story-points';
			return Tag.render`
				<div id="${this.headerNodeId}" class="tasks-scrum-backlog-header">
					<div class="tasks-scrum-backlog-title">
						${Loc.getMessage('TASKS_SCRUM_BACKLOG_TITLE')}
					</div>
					<div class="tasks-scrum-backlog-epics-title ui-btn ui-btn-xs ui-btn-secondary">
						${Loc.getMessage('TASKS_SCRUM_BACKLOG_EPICS_TITLE')}
					</div>
					<div class="tasks-scrum-backlog-title-spacer"></div>
					<div class="tasks-scrum-backlog-story-point-title">
						${Loc.getMessage('TASKS_SCRUM_BACKLOG_TITLE_STORY_POINTS')}
					</div>
					<div class="tasks-scrum-backlog-story-point">
						${Text.encode(this.storyPoints)}
					</div>
				</div>
			`;
		};

		const createBacklogItems = () => {
			this.backlogItemsNodeId = 'tasks-scrum-backlog-items';
			return Tag.render`
				<div class="tasks-scrum-backlog-items">
					<div id="${this.backlogItemsNodeId}" class=
						"tasks-scrum-backlog-items-list" data-entity-id="${this.getId()}">
						${this.input.render()}
						${[...this.items.values()].map((item) => item.render())}
					</div>
				</div>
			`;
		};

		return createBacklog(
			createBacklogTitle(),
			this.actionsHeader.createActionsHeader(),
			createBacklogItems()
		)
	}

	onAfterAppend()
	{
		this.listItemsNode = document.getElementById(this.backlogItemsNodeId);

		this.headerNode = document.getElementById(this.headerNodeId);
		this.storyPointsNode = this.headerNode.querySelector('.tasks-scrum-backlog-story-point');

		const listEpicNode = this.headerNode.querySelector('.tasks-scrum-backlog-epics-title');
		Event.bind(listEpicNode, 'click', () => this.emit('openListEpicGrid'));

		super.onAfterAppend();
	}
}