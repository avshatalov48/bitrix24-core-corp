import {Loc, Tag, Type} from 'main.core';
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

		let label = '';
		if (Type.isInteger(remainingDays) && remainingDays <= 1)
		{
			label = Loc.getMessage('TASKS_SCRUM_SPRINT_STATS_ACTIVE_LAST_LABEL')
				.replace('#percent#', percentage)
			;
		}
		else
		{
			label = Loc.getMessage('TASKS_SCRUM_SPRINT_STATS_ACTIVE_LABEL')
				.replace('#days#', remainingDays)
				.replace('#percent#', percentage)
			;
		}

		const title = this.getSprintDate().getFormattedTitleDatePeriod();

		this.headerNode = Tag.render`
			<div class="${this.headerClass}" title="${title}">
				${label}
			</div>
		`;

		return this.headerNode;
	}

	getRemainingDays(endDate: number): string|number
	{
		const dateWithWeekendOffset = new Date();
		dateWithWeekendOffset.setSeconds(dateWithWeekendOffset.getSeconds() + this.weekendDaysTime);
		dateWithWeekendOffset.setHours(0, 0, 0, 0);

		const dateEnd = new Date(endDate * 1000);

		const msPerMinute = 60 * 1000;
		const msPerHour = msPerMinute * 60;
		const msPerDay = msPerHour * 24;

		const daysRemaining = Math.round((dateEnd - dateWithWeekendOffset) / msPerDay);

		if (daysRemaining <= 1)
		{
			return daysRemaining;
		}
		else
		{
			return BX.date.format('ddiff', dateWithWeekendOffset, dateEnd);
		}
	};
}