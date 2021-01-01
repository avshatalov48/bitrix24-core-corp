import {Event, Loc, Tag, Text} from 'main.core';
import {EventEmitter} from 'main.core.events';
import {Entity} from '../entity';

export class Header extends EventEmitter
{
	constructor(entity: Entity)
	{
		super(entity);

		this.setEventNamespace('BX.Tasks.Scrum.BacklogHeader');

		this.entity = entity;

		this.element = null;
	}

	render(): HTMLElement
	{
		this.element = Tag.render`
			<div class="tasks-scrum-backlog-title">
				${Loc.getMessage('TASKS_SCRUM_BACKLOG_TITLE')}
			</div>
			<div class="tasks-scrum-backlog-epics-title ui-btn ui-btn-xs ui-btn-secondary">
				${Loc.getMessage('TASKS_SCRUM_BACKLOG_EPICS_TITLE')}
			</div>
			<div title="Definition of Done" class="tasks-scrum-entity-title-btn tasks-scrum-entity-title-btn-dod">
				<span class="tasks-scrum-entity-dod-icon"></span>
			</div>
			<div class="tasks-scrum-backlog-title-spacer"></div>
			<div class="tasks-scrum-entity-title-btn tasks-scrum-entity-title-btn-task">
				<span class="tasks-scrum-entity-tasks-icon"></span>
				<span class="tasks-scrum-entity-tasks-title">
					${this.entity.getNumberTasks()}
				</span>
			</div>
			<div class="tasks-scrum-backlog-story-point-title">
				${Loc.getMessage('TASKS_SCRUM_BACKLOG_TITLE_STORY_POINTS')}
			</div>
			<div class="tasks-scrum-backlog-story-point">
				${Text.encode(this.entity.getStoryPoints().getPoints())}
			</div>
		`;

		Event.bind(this.getElementByClassName(this.element, 'tasks-scrum-backlog-epics-title'), 'click', () => {
			this.emit('openListEpicGrid');
		});

		Event.bind(this.getElementByClassName(this.element, 'tasks-scrum-entity-title-btn-dod'), 'click', () => {
			this.emit('openDefinitionOfDone');
		});

		return this.element;
	}

	setStoryPoints(storyPoints: string)
	{
		this.getElementByClassName(
			this.element,
			'tasks-scrum-backlog-story-point'
		).textContent = Text.encode(storyPoints);
	}

	updateNumberTasks()
	{
		const parentNode = this.getElementByClassName(
			this.element,
			'tasks-scrum-entity-title-btn-task'
		);
		parentNode.querySelector('.tasks-scrum-entity-tasks-title').textContent = this.entity.getNumberTasks();
	}

	getElementByClassName(elements: HTMLElement[], className: string): HTMLElement
	{
		let element = null;
		elements.forEach((elem) => {
			if (elem.classList.contains(className))
			{
				element = elem;
			}
		});
		return element;
	}
}