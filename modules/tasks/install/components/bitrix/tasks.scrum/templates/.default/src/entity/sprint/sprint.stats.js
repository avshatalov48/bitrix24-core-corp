import {Loc, Tag, Dom} from 'main.core';
import {Sprint} from './sprint';

export class SprintStats
{
	constructor(sprint: Sprint)
	{
		this.sprint = sprint;

		this.dateEnd = this.sprint.getDateEnd()
		this.storyPoints = this.sprint.getStoryPoints();
		this.completedStoryPoints = this.sprint.getCompletedStoryPoints();

		this.activeStatsNodeId = 'tasks-scrum-sprint-header-stats';
		this.activeStatsClasses = 'tasks-scrum-sprint-header-stats';

		this.kanbanMode = false;
	}

	setKanbanMode()
	{
		this.kanbanMode = true;
		this.activeStatsClasses = 'tasks-scrum-sprint-header-stats-kanban';
	}

	onAfterAppend()
	{
		this.activeStatsNode = document.getElementById(this.activeStatsNodeId);
	}

	createStats(): HTMLElement|string
	{
		if (this.sprint.isCompleted())
		{
			return this.createCompletedStatsInfo();
		}
		else if (this.sprint.isActive() && this.sprint.isExpired())
		{
			return this.createExpiredStatsInfo();
		}
		else if (this.sprint.isActive())
		{
			return this.createActiveStatsInfo();
		}
		else
		{
			return '';
		}
	}

	createActiveStatsInfo(): HTMLElement
	{
		return this.createActiveStatsNode(
			this.getRemainingDays(),
			`<b>${this.getPercentageCompletedStoryPoints()}</b>`
		);
	}

	createCompletedStatsInfo(): HTMLElement
	{
		return Tag.render`
			<div class="tasks-scrum-sprint-header-stats">
				${
					Loc.getMessage('TASKS_SCRUM_SPRINT_STATS_COMPLETED_LABEL')
						.replace('#percent#', `<b>${this.getPercentageCompletedStoryPoints()}</b>`)
						.replace('#date#', BX.date.format('j F Y', this.dateEnd))
				}
			</div>
		`;
	}

	createExpiredStatsInfo(): HTMLElement
	{
		this.expiredStatsNodeId = 'tasks-scrum-sprint-expired-stats';
		const statsClass = (this.kanbanMode ?
			'tasks-scrum-sprint-header-stats-kanban' : 'tasks-scrum-sprint-header-stats');

		return Tag.render`
			<div id="${this.expiredStatsNodeId}" class="${statsClass}">
				${
					Loc.getMessage('TASKS_SCRUM_SPRINT_STATS_EXPIRED_LABEL')
						.replace('#percent#', `<b>${this.getPercentageCompletedStoryPoints()}</b>`)
						.replace('#date#', BX.date.format('j F Y', this.dateEnd))
				}
			</div>
		`;
	}

	updateActiveStats(inputStoryPoints: String, increment: Boolean = true)
	{
		inputStoryPoints = (inputStoryPoints ? parseFloat(inputStoryPoints) : '');

		this.completedStoryPoints = (increment ?
			(this.completedStoryPoints + inputStoryPoints) :
			(this.completedStoryPoints - inputStoryPoints));

		if (this.sprint.isExpired())
		{
			Dom.replace(document.getElementById(this.expiredStatsNodeId), this.createExpiredStatsInfo());
		}
		else
		{
			if (!this.activeStatsNode)
			{
				return;
			}

			const newActiveStatsNode = this.createActiveStatsNode(
				this.getRemainingDays(),
				`<b>${this.getPercentageCompletedStoryPoints()}</b>`
			);

			Dom.replace(this.activeStatsNode, newActiveStatsNode);
			this.activeStatsNode = document.getElementById(this.activeStatsNodeId);
		}
	}

	getPercentageCompletedStoryPoints(): String
	{
		const percentage = (this.storyPoints > 0  ? Math.round(this.completedStoryPoints * 100 / this.storyPoints) : 0);
		return `${percentage}%`;
	}

	getRemainingDays(): String
	{
		return `<b>${BX.date.format('ddiff', new Date(), this.dateEnd)}</b>`;
	};

	createActiveStatsNode(remainingDays: String, percentageCompletedStoryPoints: String): HTMLElement
	{
		return Tag.render`
			<div id="${this.activeStatsNodeId}" class="${this.activeStatsClasses}">
				${
					Loc.getMessage('TASKS_SCRUM_SPRINT_STATS_ACTIVE_LABEL')
						.replace('#days#', remainingDays)
						.replace('#percent#', percentageCompletedStoryPoints)
				}
			</div>
		`;
	}
}