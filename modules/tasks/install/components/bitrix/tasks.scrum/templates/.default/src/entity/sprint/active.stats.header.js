import {Loc, Tag} from 'main.core';
import {StatsHeader} from './stats.header';

export class ActiveStatsHeader extends StatsHeader
{
	render(): HTMLElement
	{
		const remainingDays = this.getRemainingDays(this.getEndDate());
		const percentage = this.statsCalculator.calculatePercentage(
			this.getStoryPoints(),
			this.getCompletedStoryPoints()
		);

		const label = Loc.getMessage('TASKS_SCRUM_SPRINT_STATS_ACTIVE_LABEL').
			replace('#days#', '<b>' + remainingDays + '</b>').
			replace('#percent#', '<b>' + percentage + '%</b>');

		this.headerNode = Tag.render`
			<div class="${this.headerClass}">
				${label}
			</div>
		`;

		return this.headerNode;
	}

	getRemainingDays(endDate: number): string
	{
		return BX.date.format('ddiff', new Date(), endDate);
	};
}