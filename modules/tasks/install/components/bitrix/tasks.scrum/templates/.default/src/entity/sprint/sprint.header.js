import {Dom, Event, Loc, Tag, Text} from 'main.core';
import {EventEmitter} from 'main.core.events';
import {SprintDate} from './sprint.date';
import {Sprint} from './sprint';
import {MessageBox} from 'ui.dialogs.messagebox';
import {StatsHeaderBuilder} from './stats.header.builder';
import {StatsHeader} from './stats.header';

export class SprintHeader extends EventEmitter
{
	constructor(sprint: Sprint)
	{
		super(sprint);

		this.setEventNamespace('BX.Tasks.Scrum.SprintHeader');

		this.sprint = sprint;

		this.node = null;

		this.statsHeader = null;
		this.sprintDate = null;
	}

	static buildHeader(sprint: Sprint): SprintHeader
	{
		const sprintHeader = new SprintHeader(sprint);
		sprintHeader.addHeaderStats(StatsHeaderBuilder.build(sprint));
		sprintHeader.addHeaderDate(new SprintDate(sprint));
		return sprintHeader;
	}

	addHeaderStats(statsHeader: StatsHeader)
	{
		this.statsHeader = statsHeader;
	}

	addHeaderDate(sprintDate: SprintDate)
	{
		this.sprintDate = sprintDate;
		this.sprintDate.subscribe('changeSprintDeadline', (baseEvent) => {
			this.emit('changeSprintDeadline', baseEvent.getData());
		});
	}

	initStyle(sprint: Sprint)
	{
		this.sprint = sprint;

		if (this.sprint.isActive())
		{
			this.headerClass = 'tasks-scrum-sprint-header-active';
			this.buttonClass = 'ui-btn ui-btn-success ui-btn-xs';
			this.buttonText = Loc.getMessage('TASKS_SCRUM_SPRINT_TITLE_COMPLETE_BUTTON');
		}
		else if (this.sprint.isCompleted())
		{
			this.headerClass = 'tasks-scrum-sprint-header-completed';
			Dom.remove(this.buttonNode);
			this.sprint.hideContent();
		}
		else if (this.sprint.isPlanned())
		{
			this.headerClass = 'tasks-scrum-sprint-header-planned';
			this.buttonClass = 'ui-btn ui-btn-primary ui-btn-xs';
			this.buttonText = Loc.getMessage('TASKS_SCRUM_SPRINT_TITLE_START_BUTTON');
		}

		if (this.node)
		{
			this.addHeaderStats(StatsHeaderBuilder.build(this.sprint));
			this.addHeaderDate(new SprintDate(this.sprint));

			if (this.sprint.isDisabled())
			{
				Dom.remove(this.node.querySelector('.tasks-scrum-sprint-header-remove'));
			}

			this.node.className = '';
			Dom.addClass(this.node, 'tasks-scrum-sprint-header ' + this.headerClass);

			const button = this.buttonNode.querySelector('button');
			button.className = '';
			Dom.addClass(button, this.buttonClass);
			button.firstChild.replaceWith(this.buttonText);

			Dom.replace(this.node.querySelector('.tasks-scrum-sprint-header-params'), this.renderParams());
		}
	}

	render(): HTMLElement
	{
		this.node = Tag.render`
			<div class="tasks-scrum-sprint-header ${this.headerClass}">
				${this.renderDragnDrop()}
				<div class="tasks-scrum-sprint-header-name-container">
					<div class="tasks-scrum-sprint-header-name">
						${Text.encode(this.sprint.getName())}
					</div>
				</div>
				<div class="tasks-scrum-sprint-header-edit"></div>
				${this.renderRemove()}
				${this.renderParams()}
			</div>
		`;

		return this.node;
	};

	renderParams(): HTMLElement
	{
		const tickAngleClass = (this.sprint.isCompleted() ? 'ui-btn-icon-angle-down' : 'ui-btn-icon-angle-up');

		return Tag.render`
			<div class="tasks-scrum-sprint-header-params">
				${this.renderStatsHeader()}
				${this.sprintDate ? this.sprintDate.createDate(
				this.sprint.getDateStart(), this.sprint.getDateEnd()) : ''}
				${this.createButton()}
				<div class="tasks-scrum-sprint-header-tick">
					<div class="ui-btn ui-btn-sm ui-btn-light ${tickAngleClass}"></div>
				</div>
			</div>
		`;
	}

	getStatsHeader(): StatsHeader
	{
		return this.statsHeader;
	}

	renderDragnDrop(): HTMLElement
	{
		if (this.sprint.isPlanned())
		{
			return Tag.render`<div class="tasks-scrum-sprint-dragndrop"></div>`;
		}
		else
		{
			return Tag.render`<div class="tasks-scrum-sprint-header-empty"></div>`;
		}
	}

	renderRemove(): HTMLElement
	{
		if (this.sprint.isPlanned())
		{
			return Tag.render`<div class="tasks-scrum-sprint-header-remove"></div>`;
		}
		else
		{
			return '';
		}
	}

	renderStatsHeader(): HTMLElement
	{
		return (this.statsHeader ? this.statsHeader.render() : '');
	}

	updateNameNode(name: string)
	{
		if (this.node)
		{
			this.node.querySelector('.tasks-scrum-sprint-header-name').textContent = Text.encode(name);
		}
	}

	updateStatsHeader()
	{
		this.statsHeader.updateStats(this.sprint);
	}

	updateDateStartNode(timestamp)
	{
		if (this.sprintDate)
		{
			this.sprintDate.updateDateStartNode(timestamp);
		}
	}

	updateDateEndNode(timestamp)
	{
		if (this.sprintDate)
		{
			this.sprintDate.updateDateEndNode(timestamp);
		}
	}

	createButton()
	{
		if (this.sprint.isCompleted())
		{
			return '';
		}
		else
		{
			this.buttonNodeId = 'tasks-scrum-sprint-header-button-' + this.sprint.getId();
			return Tag.render`
				<div id="${this.buttonNodeId}" class="tasks-scrum-sprint-header-button">
					<button class="${this.buttonClass}">${this.buttonText}</button>
				</div>
			`;
		}
	};

	onAfterAppend()
	{
		if (!this.sprint.isCompleted())
		{
			this.buttonNode = document.getElementById(this.buttonNodeId);
			Event.bind(this.buttonNode, 'click', this.onButtonClick.bind(this));
		}

		const nameNode = this.node.querySelector('.tasks-scrum-sprint-header-name-container');
		const editButtonNode = this.node.querySelector('.tasks-scrum-sprint-header-edit');
		Event.bind(editButtonNode, 'click', () => this.emit('changeName', nameNode));

		if (this.sprint.isPlanned())
		{
			const removeNode = this.node.querySelector('.tasks-scrum-sprint-header-remove');
			Event.bind(removeNode, 'click', () => {
				MessageBox.confirm(
					Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_REMOVE_SPRINT'),
					(messageBox) => {
						this.emit('removeSprint');
						messageBox.close();
					},
					Loc.getMessage('TASKS_SCRUM_BUTTON_TEXT_REMOVE'),
				);
			});
		}

		const tickButtonNode = this.node.querySelector('.tasks-scrum-sprint-header-tick');
		Event.bind(tickButtonNode, 'click', () => {
			tickButtonNode.firstElementChild.classList.toggle('ui-btn-icon-angle-up');
			tickButtonNode.firstElementChild.classList.toggle('ui-btn-icon-angle-down');
			this.emit('toggleVisibilityContent');
		});

		if (this.sprintDate)
		{
			this.sprintDate.onAfterAppend();
		}
	}

	onButtonClick()
	{
		if (this.sprint.isActive())
		{
			this.emit('completeSprint');
		}
		else if (this.sprint.isPlanned())
		{
			this.emit('startSprint');
		}
	}
}