import {Sprint} from '../sprint';

import {Stats} from './stats';
import {CompletedStats} from './completed.stats';
import {ExpiredStats} from './expired.stats';
import {ActiveStats} from './active.stats';

export class StatsBuilder
{
	static build(sprint: Sprint): Stats
	{
		if (sprint.isCompleted())
		{
			return new CompletedStats(sprint);
		}
		else if (sprint.isExpired())
		{
			return new ExpiredStats(sprint);
		}
		else if (sprint.isActive())
		{
			return new ActiveStats(sprint);
		}
		else
		{
			return new Stats(sprint);
		}
	}
}