import {Event, Loc, Tag, Text} from 'main.core';
import {EventEmitter} from 'main.core.events';
import {Sprint} from './sprint';

export class StoryPointsHeader extends EventEmitter
{
	constructor(sprint: Sprint)
	{
		super(sprint);

		this.setEventNamespace('BX.Tasks.Scrum.StoryPointsHeader');

		this.sprint = sprint;

		this.element = null;
	}

	render(): HTMLElement
	{
		this.totalStoryPointsNode = Tag.render`
			<div class="tasks-scrum-sprint-story-point-title">
				${Loc.getMessage('TASKS_SCRUM_SPRINT_TITLE_STORY_POINTS')}
			</div>
			<div class="tasks-scrum-sprint-story-point">
				${this.sprint.getTotalStoryPoints().getPoints()}
			</div>
		`;

		this.inWorkStoryPointsNode = (this.sprint.isActive() ? Tag.render`
			<div class="tasks-scrum-sprint-story-point-in-work-title">
				${Loc.getMessage('TASKS_SCRUM_SPRINT_TITLE_STORY_POINTS_IN_WORK')}
			</div>
			<div class="tasks-scrum-sprint-story-point tasks-scrum-sprint-story-point-in-work">
				${this.sprint.getTotalUncompletedStoryPoints().getPoints()}
			</div>
		` : '');

		this.doneStoryPointsNode = (this.sprint.isPlanned() ? '' : Tag.render`
			<div class="tasks-scrum-sprint-story-point-done-title">
				${Loc.getMessage('TASKS_SCRUM_SPRINT_TITLE_STORY_POINTS_DONE')}
			</div>
			<div class="tasks-scrum-sprint-story-point tasks-scrum-sprint-story-point-done">
				${this.sprint.getTotalCompletedStoryPoints().getPoints()}
			</div>
		`);

		this.element = Tag.render`
			${this.renderBurnDownChartIcon()}
			<div class="tasks-scrum-entity-title-btn tasks-scrum-entity-title-btn-task">
				<span class="tasks-scrum-entity-tasks-icon"></span>
				<span class="tasks-scrum-entity-tasks-title">
					${this.sprint.getNumberTasks()}
				</span>
			</div>
			${this.totalStoryPointsNode}
			${this.inWorkStoryPointsNode}
			${this.doneStoryPointsNode}
		`;

		Event.bind(this.getElementByClassName(
			this.element,
			'tasks-scrum-entity-title-btn-burn-down-chart'
		), 'click', () => this.emit('showSprintBurnDownChart'));

		return this.element;
	}

	renderBurnDownChartIcon(): HTMLElement
	{
		if (this.sprint.isPlanned())
		{
			return '';
		}

		return Tag.render`
			<div class="tasks-scrum-entity-title-btn tasks-scrum-entity-title-btn-burn-down-chart">
				<span class="tasks-scrum-entity-burn-down-chart-icon"></span>
			</div>
		`;
	}

	updateNumberTasks()
	{
		const parentNode = this.getElementByClassName(this.element, 'tasks-scrum-entity-title-btn-task');
		parentNode.querySelector('.tasks-scrum-entity-tasks-title').textContent = this.sprint.getNumberTasks();
	}

	setStoryPoints(storyPoints: string)
	{
		if (!this.totalStoryPointsNode)
		{
			return;
		}

		this.getElementByClassName(
			this.totalStoryPointsNode,
			'tasks-scrum-sprint-story-point'
		).textContent = Text.encode(storyPoints);
	}

	setCompletedStoryPoints(storyPoints: string)
	{
		if (!this.doneStoryPointsNode)
		{
			return;
		}

		this.getElementByClassName(
			this.doneStoryPointsNode,
			'tasks-scrum-sprint-story-point-done'
		).textContent = Text.encode(storyPoints);
	}

	setUncompletedStoryPoints(storyPoints: string)
	{
		if (!this.inWorkStoryPointsNode)
		{
			return;
		}

		this.getElementByClassName(
			this.inWorkStoryPointsNode,
			'tasks-scrum-sprint-story-point-in-work'
		).textContent = Text.encode(storyPoints);
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