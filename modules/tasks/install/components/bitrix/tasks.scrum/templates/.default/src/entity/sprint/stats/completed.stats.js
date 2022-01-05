import {Loc, Tag} from 'main.core';

import {Stats} from './stats';
import {Date as HeaderDate} from '../header/date';

export class CompletedStats extends Stats
{
	render(): HTMLElement
	{
		const percentage = this.statsCalculator.calculatePercentage(
			this.getStoryPoints(),
			this.getCompletedStoryPoints()
		);

		const completedDate = this.getCompletedDate(this.getEndDate());

		const label = Loc.getMessage('TASKS_SCRUM_SPRINT_STATS_COMPLETED_LABEL')
			.replace('#percent#', percentage)
			.replace('#date#', completedDate)
		;

		const title = HeaderDate.getFormattedTitleDatePeriod(this.sprint);

		this.node = Tag.render`
			<div title="${title}">
				${label}
			</div>
		`;

		return this.node;
	}

	getCompletedDate(endDate: number): string
	{
		return BX.date.format('j F Y', endDate);
	};
}
