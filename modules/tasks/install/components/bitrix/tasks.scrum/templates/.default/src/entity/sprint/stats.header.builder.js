import {Sprint} from './sprint';
import {StatsHeader} from './stats.header';
import {CompletedStatsHeader} from './completed.stats.header';
import {ExpiredStatsHeader} from './expired.stats.header';
import {ActiveStatsHeader} from './active.stats.header';

export class StatsHeaderBuilder
{
	static build(sprint: Sprint): StatsHeader
	{
		if (sprint.isCompleted())
		{
			return new CompletedStatsHeader(sprint);
		}
		else if (sprint.isExpired())
		{
			return new ExpiredStatsHeader(sprint);
		}
		else if (sprint.isActive())
		{
			return new ActiveStatsHeader(sprint);
		}
		else
		{
			return new StatsHeader(sprint);
		}
	}
}