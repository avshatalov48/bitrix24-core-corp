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

	initStyle()
	{
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
		}
		else if (this.sprint.isPlanned())
		{
			this.headerClass = 'tasks-scrum-sprint-header-planned';
			this.buttonClass = 'ui-btn ui-btn-primary ui-btn-xs';
			this.buttonText = Loc.getMessage('TASKS_SCRUM_SPRINT_TITLE_START_BUTTON');
		}

		if (this.headerNode)
		{
			if (this.sprint.isDisabled())
			{
				Dom.remove(this.headerNode.querySelector('.tasks-scrum-sprint-header-remove'));
			}

			this.headerNode.className = '';
			Dom.addClass(this.headerNode, 'tasks-scrum-sprint-header ' + this.headerClass);

			const button = this.buttonNode.querySelector('button');
			button.className = '';
			Dom.addClass(button, this.buttonClass);
			button.firstChild.replaceWith(this.buttonText);
		}
	}

	render(): HTMLElement
	{
		this.headerNodeId = 'tasks-scrum-sprint-header-' + this.sprint.getId();

		const tickAngleClass = (this.sprint.isCompleted() ? 'ui-btn-icon-angle-down' : 'ui-btn-icon-angle-up');

		return Tag.render`
			<div id="${this.headerNodeId}" class="tasks-scrum-sprint-header ${this.headerClass}">
				${this.renderDragnDrop()}
				<div class="tasks-scrum-sprint-header-name-container">
					<div class="tasks-scrum-sprint-header-name">
						${Text.encode(this.sprint.getName())}
					</div>
				</div>
				<div class="tasks-scrum-sprint-header-edit"></div>
				${this.renderRemove()}
				<div class="tasks-scrum-sprint-header-params">
					${this.renderStatsHeader()}
					${this.sprintDate ? this.sprintDate.createDate(
						this.sprint.getDateStart(), this.sprint.getDateEnd()) : ''}
					${this.createButton()}
					<div class="tasks-scrum-sprint-header-tick">
						<div class="ui-btn ui-btn-sm ui-btn-light ${tickAngleClass}"></div>
					</div>
				</div>
			</div>
		`;
	};

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
		this.headerNode = document.getElementById(this.headerNodeId);

		if (!this.sprint.isCompleted())
		{
			this.buttonNode = document.getElementById(this.buttonNodeId);
			Event.bind(this.buttonNode, 'click', this.onButtonClick.bind(this));
		}

		const nameNode = this.headerNode.querySelector('.tasks-scrum-sprint-header-name-container');
		const editButtonNode = this.headerNode.querySelector('.tasks-scrum-sprint-header-edit');
		Event.bind(editButtonNode, 'click', () => this.emit('changeName', nameNode));

		if (this.sprint.isPlanned())
		{
			const removeNode = this.headerNode.querySelector('.tasks-scrum-sprint-header-remove');
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

		const tickButtonNode = this.headerNode.querySelector('.tasks-scrum-sprint-header-tick');
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