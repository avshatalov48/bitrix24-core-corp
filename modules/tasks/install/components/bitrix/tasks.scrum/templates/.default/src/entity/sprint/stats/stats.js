import {Dom, Tag, Type} from 'main.core';
import {EventEmitter} from 'main.core.events';
import {Sprint} from '../sprint';

import {StatsCalculator} from './stats.calculator';

export class Stats extends EventEmitter
{
	constructor(sprint: Sprint)
	{
		super(sprint);

		this.setEventNamespace('BX.Tasks.Scrum.Sprint.Stats');

		this.setSprintData(sprint);

		this.statsCalculator = new StatsCalculator();

		this.node = null;

		this.kanbanMode = false;
	}

	setKanbanStyle()
	{
		this.kanbanMode = true;
	}

	isKanbanMode(): boolean
	{
		return this.kanbanMode;
	}

	render(): HTMLElement
	{
		this.node = Tag.render`<div></div>`;

		return this.node;
	}

	setSprintData(sprint: Sprint)
	{
		this.sprint = sprint;

		this.setStoryPoints(sprint.getStoryPoints().getPoints());
		this.setCompletedStoryPoints(sprint.getCompletedStoryPoints().getPoints());
		this.setUncompletedStoryPoints(sprint.getUncompletedStoryPoints().getPoints());
		this.setEndDate(sprint.getDateEnd());

		this.weekendDaysTime = sprint.getWeekendDaysTime();

		if (this.node)
		{
			Dom.replace(this.node, this.render());
		}
	}

	setStoryPoints(storyPoints)
	{
		if (Type.isUndefined(storyPoints) || isNaN(parseFloat(storyPoints)))
		{
			this.storyPoints = 0;
		}
		else
		{
			this.storyPoints = parseFloat(storyPoints);
		}
	}

	getStoryPoints(): number
	{
		return this.storyPoints;
	}

	setCompletedStoryPoints(storyPoints)
	{
		if (Type.isUndefined(storyPoints) || isNaN(parseFloat(storyPoints)))
		{
			this.completedStoryPoints = 0;
		}
		else
		{
			this.completedStoryPoints = parseFloat(storyPoints);
		}
	}

	getCompletedStoryPoints(): number
	{
		return this.completedStoryPoints;
	}

	setUncompletedStoryPoints(storyPoints)
	{
		if (Type.isUndefined(storyPoints) || isNaN(parseFloat(storyPoints)))
		{
			this.uncompletedStoryPoints = 0;
		}
		else
		{
			this.uncompletedStoryPoints = parseFloat(storyPoints);
		}
	}

	getUncompletedStoryPoints(): number
	{
		return this.completedStoryPoints;
	}

	setEndDate(endDate)
	{
		if (Type.isUndefined(endDate) || isNaN(parseInt(endDate, 10)))
		{
			this.endDate = (Date.now() / 1000);
		}
		else
		{
			this.endDate = parseInt(endDate, 10);
		}
	}

	getEndDate(): number
	{
		return this.endDate;
	}
}