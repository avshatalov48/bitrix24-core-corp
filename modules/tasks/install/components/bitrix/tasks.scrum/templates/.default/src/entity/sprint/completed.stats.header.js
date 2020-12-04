import {Loc, Tag} from 'main.core';
import {StatsHeader} from './stats.header';

export class CompletedStatsHeader extends StatsHeader
{
	render(): HTMLElement
	{
		const percentage = this.statsCalculator.calculatePercentage(
			this.getStoryPoints(),
			this.getCompletedStoryPoints()
		);

		const completedDate = this.getCompletedDate(this.getEndDate());

		const label = Loc.getMessage('TASKS_SCRUM_SPRINT_STATS_COMPLETED_LABEL').
			replace('#percent#', '<b>' + percentage + '%</b>').
			replace('#date#', completedDate);

		this.headerNode = Tag.render`
			<div class="${this.headerClass}">
				${label}
			</div>
		`;

		return this.headerNode;
	}

	getCompletedDate(endDate: number): string
	{
		return BX.date.format('j F Y', endDate);
	};
}