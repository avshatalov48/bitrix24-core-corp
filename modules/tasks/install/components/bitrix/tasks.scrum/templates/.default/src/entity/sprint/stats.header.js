import {Dom, Type} from 'main.core';
import {EventEmitter} from 'main.core.events';
import {Sprint} from './sprint';
import {StatsCalculator} from '../../utility/stats.calculator';

export class StatsHeader extends EventEmitter
{
	constructor(sprint: Sprint)
	{
		super(sprint);

		this.setEventNamespace('BX.Tasks.Scrum.StatsHeader');

		this.setSprintData(sprint);

		this.statsCalculator = new StatsCalculator();

		this.headerNode = null;
		this.headerClass = 'tasks-scrum-sprint-header-stats';

		this.kanbanMode = false;
	}

	setKanbanStyle()
	{
		this.kanbanMode = true;
		this.headerClass = 'tasks-scrum-sprint-header-stats-kanban';
	}

	isKanbanMode(): boolean
	{
		return this.kanbanMode;
	}

	render(): HTMLElement
	{
		return '';
	}

	updateStats(sprint: Sprint)
	{
		this.setSprintData(sprint);

		Dom.replace(this.headerNode, this.render());
	}

	setSprintData(sprint: Sprint)
	{
		this.setStoryPoints(sprint.getTotalStoryPoints().getPoints());
		this.setCompletedStoryPoints(sprint.getTotalCompletedStoryPoints().getPoints());
		this.setEndDate(sprint.getDateEnd());
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