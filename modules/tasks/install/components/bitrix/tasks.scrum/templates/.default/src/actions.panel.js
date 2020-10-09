import {Dom, Event, Loc, Tag} from 'main.core';

import './css/actions-panel.css';

export class ActionsPanel
{
	constructor(options)
	{
		this.actionPanelNodeId = 'tasks-scrum-actions-panel';

		this.bindElement = options.bindElement;

		this.itemList = {...{
			task: {activity: false},
			attachment: {activity: false},
			move: {activity: false},
			sprint: {activity: false},
			backlog: {activity: false},
			tags: {activity: false},
			epic: {activity: false},
			decomposition: {activity: false},
			remove: {activity: false},
		}, ...options.itemList};

		this.listBlockBlurNodes = new Set();
	}

	showPanel()
	{
		Dom.remove(document.getElementById(this.actionPanelNodeId));

		const actionsPanelContainer = this.createActionPanel();
		this.setBlockBlurNode(actionsPanelContainer);

		const position = Dom.getPosition(this.bindElement);

		actionsPanelContainer.style.top = `${position.top}px`;
		actionsPanelContainer.style.left = `${position.left}px`;
		actionsPanelContainer.style.width = `${position.width}px`;

		Dom.append(actionsPanelContainer, document.body);

		const customBlur = (event) => {
			let hasNode = false;
			this.listBlockBlurNodes.forEach((blockBlurNode) => {
				if (blockBlurNode.contains(event.target))
				{
					hasNode = true;
				}
			});
			if (!hasNode)
			{
				Dom.remove(actionsPanelContainer);
				Event.unbind(document, 'click', customBlur);
			}
		};
		Event.bind(document, 'click', customBlur);

		this.bindItems();

		const actionsPanel =  actionsPanelContainer.querySelector('.tasks-scrum-actions-panel');
		if (Dom.hasClass(actionsPanel.lastElementChild, 'tasks-scrum-actions-panel-separator'))
		{
			Dom.remove(actionsPanel.lastElementChild);
		}
	}

	setBlockBlurNode(node)
	{
		this.listBlockBlurNodes.add(node);
	}

	isShown(): Boolean
	{
		return Boolean(document.getElementById(this.actionPanelNodeId));
	}

	createActionPanel()
	{
		let task = '';
		let attachment = '';
		let move = '';
		let sprint = '';
		let backlog = '';
		let tags = '';
		let epic = '';
		let decomposition = '';
		let remove = '';

		if (this.itemList.task.activity)
		{
			this.showTaskActionButtonNodeId = 'tasks-scrum-actions-panel-btn-task';
			task = Tag.render`
				<div id="${this.showTaskActionButtonNodeId}" class=
					"tasks-scrum-actions-panel-btn tasks-scrum-actions-panel-btn-task">
					<span class="tasks-scrum-actions-panel-icon"></span>
					<span class="tasks-scrum-actions-panel-text">
						${Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_TASK')}
					</span>
				</div>
				<div class="tasks-scrum-actions-panel-separator"></div>
			`;
		}

		if (this.itemList.attachment.activity)
		{
			this.showAttachmentActionButtonNodeId = 'tasks-scrum-actions-panel-btn-attachment';
			attachment = Tag.render`
				<div id="${this.showAttachmentActionButtonNodeId}" class=
					"tasks-scrum-actions-panel-btn tasks-scrum-actions-panel-btn-attachment">
					<span class="tasks-scrum-actions-panel-icon"></span>
				</div>
				<div class="tasks-scrum-actions-panel-separator"></div>
			`;
		}

		if (this.itemList.move.activity)
		{
			this.showMoveActionButtonNodeId = 'tasks-scrum-actions-panel-btn-move';
			move = Tag.render`
				<div id="${this.showMoveActionButtonNodeId}" class=
					"tasks-scrum-actions-panel-btn tasks-scrum-actions-panel-btn-move">
					<span class="tasks-scrum-actions-panel-icon"></span>
					<span class="tasks-scrum-actions-panel-text">
						${Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_MOVE')}
					</span>
				</div>
				<div class="tasks-scrum-actions-panel-separator"></div>
			`;
		}

		if (this.itemList.sprint.activity)
		{
			this.sprintActionButtonNodeId = 'tasks-scrum-actions-panel-btn-sprint';
			sprint = Tag.render`
				<div id="${this.sprintActionButtonNodeId}" class=
					"tasks-scrum-actions-panel-btn tasks-scrum-actions-panel-btn-sprint">
					<span class="tasks-scrum-actions-panel-icon"></span>
					<span class="tasks-scrum-actions-panel-text">
						${Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_SPRINT')}
					</span>
				</div>
				<div class="tasks-scrum-actions-panel-separator"></div>
			`;
		}

		if (this.itemList.backlog.activity)
		{
			this.backlogActionButtonNodeId = 'tasks-scrum-actions-panel-btn-backlog';
			backlog = Tag.render`
				<div id="${this.backlogActionButtonNodeId}" class=
					"tasks-scrum-actions-panel-btn tasks-scrum-actions-panel-btn-backlog">
					<span class="tasks-scrum-actions-panel-icon"></span>
					<span class="tasks-scrum-actions-panel-text">
						${Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_BACKLOG')}
					</span>
				</div>
				<div class="tasks-scrum-actions-panel-separator"></div>
			`;
		}

		if (this.itemList.tags.activity)
		{
			this.tagsActionButtonNodeId = 'tasks-scrum-actions-panel-btn-tags';
			tags = Tag.render`
				<div  id="${this.tagsActionButtonNodeId}" class=
					"tasks-scrum-actions-panel-btn tasks-scrum-actions-panel-btn-tags">
					<span class="tasks-scrum-actions-panel-icon"></span>
					<span class="tasks-scrum-actions-panel-text">
						${Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_TAGS')}
					</span>
				</div>
				<div class="tasks-scrum-actions-panel-separator"></div>
			`;
		}

		if (this.itemList.epic.activity)
		{
			this.epicActionButtonNodeId = 'tasks-scrum-actions-panel-btn-epic';
			epic = Tag.render`
				<div  id="${this.epicActionButtonNodeId}" class=
					"tasks-scrum-actions-panel-btn tasks-scrum-actions-panel-btn-tags">
					<span class="tasks-scrum-actions-panel-icon"></span>
					<span class="tasks-scrum-actions-panel-text">
						${Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_EPIC')}
					</span>
				</div>
				<div class="tasks-scrum-actions-panel-separator"></div>
			`;
		}

		if (this.itemList.decomposition.activity)
		{
			this.decompositionActionButtonNodeId = 'tasks-scrum-actions-panel-btn-decomposition';
			decomposition = Tag.render`
				<div  id="${this.decompositionActionButtonNodeId}" class=
					"tasks-scrum-actions-panel-btn tasks-scrum-actions-panel-btn-decomposition">
					<span class="tasks-scrum-actions-panel-icon"></span>
				</div>
				<div class="tasks-scrum-actions-panel-separator"></div>
			`;
		}

		if (this.itemList.remove.activity)
		{
			this.removeActionButtonNodeId = 'tasks-scrum-actions-panel-btn-remove';
			remove = Tag.render`
				<div  id="${this.removeActionButtonNodeId}" class=
					"tasks-scrum-actions-panel-btn tasks-scrum-actions-panel-btn-remove">
					<span class="tasks-scrum-actions-panel-icon"></span>
				</div>
			`;
		}

		return Tag.render`
			<div id="${this.actionPanelNodeId}" class="tasks-scrum-actions-panel-container">
				<div class="tasks-scrum-actions-panel">
					${task}
					${attachment}
					${move}
					${sprint}
					${backlog}
					${tags}
					${epic}
					${decomposition}
					${remove}
				</div>
			</div>
		`;
	}

	destroy()
	{
		Dom.remove(document.getElementById(this.actionPanelNodeId));
	}

	bindItems()
	{
		if (this.itemList.task.activity)
		{
			Event.bind(
				document.getElementById(this.showTaskActionButtonNodeId),
				'click',
				this.itemList.task.callback
			);
		}

		if (this.itemList.attachment.activity)
		{
			Event.bind(
				document.getElementById(this.showAttachmentActionButtonNodeId),
				'click',
				this.itemList.attachment.callback
			);
		}

		if (this.itemList.move.activity)
		{
			Event.bind(
				document.getElementById(this.showMoveActionButtonNodeId),
				'click',
				this.itemList.move.callback
			);
		}

		if (this.itemList.sprint.activity)
		{
			Event.bind(
				document.getElementById(this.sprintActionButtonNodeId),
				'click',
				this.itemList.sprint.callback
			);
		}

		if (this.itemList.backlog.activity)
		{
			Event.bind(
				document.getElementById(this.backlogActionButtonNodeId),
				'click',
				this.itemList.backlog.callback
			);
		}

		if (this.itemList.tags.activity)
		{
			Event.bind(
				document.getElementById(this.tagsActionButtonNodeId),
				'click',
				this.itemList.tags.callback
			);
		}

		if (this.itemList.epic.activity)
		{
			Event.bind(
				document.getElementById(this.epicActionButtonNodeId),
				'click',
				this.itemList.epic.callback
			);
		}

		if (this.itemList.remove.activity)
		{
			Event.bind(
				document.getElementById(this.removeActionButtonNodeId),
				'click',
				this.itemList.remove.callback
			);
		}

		if (this.itemList.decomposition.activity)
		{
			Event.bind(
				document.getElementById(this.decompositionActionButtonNodeId),
				'click',
				this.itemList.decomposition.callback
			);
		}
	}
}