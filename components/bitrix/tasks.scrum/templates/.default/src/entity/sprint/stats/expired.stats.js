import {Loc, Tag} from 'main.core';

import {Stats} from './stats';
import {Date as HeaderDate} from '../header/date';

export class ExpiredStats extends Stats
{
	render(): HTMLElement
	{
		const percentage = this.statsCalculator.calculatePercentage(
			this.getStoryPoints(),
			this.getCompletedStoryPoints()
		);
		const expiredDay = this.getExpiredDay(this.getEndDate());

		const label = Loc.getMessage('TASKS_SCRUM_SPRINT_STATS_EXPIRED_LABEL')
			.replace('#percent#', percentage)
			.replace('#date#', expiredDay)
		;

		const title = HeaderDate.getFormattedTitleDatePeriod(this.sprint);

		this.node = Tag.render`
			<div title="${title}">
				${label}
			</div>
		`;

		return this.node;
	}

	getExpiredDay(endDate: number): string
	{
		return BX.date.format('j F Y', endDate);
	};
}
