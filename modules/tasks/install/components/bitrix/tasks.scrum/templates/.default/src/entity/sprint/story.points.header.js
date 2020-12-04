import {Loc, Tag, Text} from 'main.core';
import {Sprint} from './sprint';

export class StoryPointsHeader
{
	constructor(sprint: Sprint)
	{
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
				${this.sprint.getStoryPoints().getPoints()}
			</div>
		`;

		this.inWorkStoryPointsNode = (this.sprint.isActive() ? Tag.render`
			<div class="tasks-scrum-sprint-story-point-in-work-title">
				${Loc.getMessage('TASKS_SCRUM_SPRINT_TITLE_STORY_POINTS_IN_WORK')}
			</div>
			<div class="tasks-scrum-sprint-story-point tasks-scrum-sprint-story-point-in-work">
				${this.sprint.getUncompletedStoryPoints().getPoints()}
			</div>
		` : '');

		this.doneStoryPointsNode = (this.sprint.isPlanned() ? '' : Tag.render`
			<div class="tasks-scrum-sprint-story-point-done-title">
				${Loc.getMessage('TASKS_SCRUM_SPRINT_TITLE_STORY_POINTS_DONE')}
			</div>
			<div class="tasks-scrum-sprint-story-point tasks-scrum-sprint-story-point-done">
				${this.sprint.getCompletedStoryPoints().getPoints()}
			</div>
		`);


		this.element = Tag.render`
			${this.totalStoryPointsNode}
			${this.inWorkStoryPointsNode}
			${this.doneStoryPointsNode}
		`;

		return this.element;
	}

	setStoryPoints(storyPoints: string)
	{
		this.getElementByClassName(
			this.totalStoryPointsNode,
			'tasks-scrum-sprint-story-point'
		).textContent = Text.encode(storyPoints);
	}

	setCompletedStoryPoints(storyPoints: string)
	{
		this.getElementByClassName(
			this.doneStoryPointsNode,
			'tasks-scrum-sprint-story-point-done'
		).textContent = Text.encode(storyPoints);
	}

	setUncompletedStoryPoints(storyPoints: string)
	{
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