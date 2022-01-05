import {EventEmitter} from 'main.core.events';
import {Dom, Event, Loc, Tag} from 'main.core';

export class ActionsHeader extends EventEmitter
{
	constructor(entity)
	{
		super(entity);

		this.setEventNamespace('BX.Tasks.Scrum.ActionsHeader');

		this.entity = entity;
		this.headerId = this.entity.getEntityType() + '-' + this.entity.getId();
	}

	createActionsHeader(): ?HTMLElement
	{
		if (this.entity.getEntityType() === 'sprint')
		{
			return '';
		}

		this.nodeId = 'tasks-scrum-actions-header-' + this.headerId;
		
		const getAddEpicActionNode = () => {
			return Tag.render`
				<a class="ui-link ui-link-dashed ui-link-secondary tasks-scrum-action-epic">
					${Loc.getMessage('TASKS_SCRUM_BACKLOG_LIST_ACTIONS_EPIC_ADD')}
				</a>
			`;
		};

		const getGroupActions = () => {
			return '';
			return Tag.render`
				<a class="ui-link ui-link-dashed ui-link-secondary tasks-scrum-action-group">
					${Loc.getMessage('TASKS_SCRUM_BACKLOG_LIST_ACTIONS_GROUP')}
				</a>
			`;
		};

		return Tag.render`
			<div id="${this.nodeId}" class="tasks-scrum-actions-header">
				<div class="tasks-scrum-actions">
					${getAddEpicActionNode()}
					${getGroupActions()}
				</div>
			</div>
		`;
	}

	removeYourself()
	{
		Dom.remove(this.node);
	}

	onAfterAppend()
	{
		if (this.entity.isDisabled() || this.entity.getEntityType() === 'sprint')
		{
			return;
		}

		this.node = document.getElementById(this.nodeId);

		const addEpicNode = this.node.querySelector('.tasks-scrum-action-epic');
		Event.bind(addEpicNode, 'click', () => this.emit('openAddEpicForm'));
	}
}