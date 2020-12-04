import {Loc} from 'main.core';
import {Sprint} from '../src/entity/sprint/sprint';
import {SprintStats} from '../src/entity/sprint/sprint.stats';

import loadMessages from './load-messages';

describe('Tasks.Scrum.SprintStats', () => {

	loadMessages(__dirname + '../../lang/', 'en', 'header.php');

	const sprintParams = {
		id: 1,
		name: 'Sprint name',
		sort: 1,
		dateStart: 1596723266,
		dateEnd: 1597881600,
		defaultSprintDuration: 604800,
		storyPoints: 3,
		completedStoryPoints: 2,
		unCompletedStoryPoints: 1,
		status: 'active'
	};

	let sprint = null;
	before(() => {
		sprint = new Sprint(sprintParams);
	});

	describe('Initialization', () => {
		it('SprintStats must be a function', () => {
			assert(typeof SprintStats === 'function');
		});
		it('SprintStats must be initialized successfully', () => {
			const sprintStats = new SprintStats(sprint);
			assert(sprintStats.dateEnd === sprintParams.dateEnd);
			assert(sprintStats.storyPoints === sprintParams.storyPoints);
		});
	});

	describe('Correct behaviour', () => {
		it('SprintStats must be return percentage completed story points', () => {
			const sprintStats = new SprintStats(sprint);
			assert.equal(sprintStats.getPercentageCompletedStoryPoints(), `67%`);
		});
		it('SprintStats must be create a DOM element with active stats', () => {
			const sprintStats = new SprintStats(sprint);
			const remainingDays = '7 days';
			const percentageCompleted = sprintStats.getPercentageCompletedStoryPoints();
			assert.equal(
				sprintStats.createActiveStatsNode(remainingDays, percentageCompleted)
					.outerHTML.replace(/[\n\r\t]/g, ''),
				`
				<div id="tasks-scrum-sprint-header-stats" class="tasks-scrum-sprint-header-stats">
					${
						Loc.getMessage('TASKS_SCRUM_SPRINT_STATS_ACTIVE_LABEL')
							.replace('#days#', remainingDays)
							.replace('#percent#', percentageCompleted)
					}
				</div>
				`.replace(/[\n\r\t]/g, '')
			);
		});
	});

});
