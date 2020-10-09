import {Loc} from 'main.core';

import {Sprint} from '../src/sprint';
import {SprintDate} from '../src/sprint.date';
import {SprintStats} from '../src/sprint.stats';
import {SprintHeader} from '../src/sprint.header';

import loadMessages from './load-messages';

describe('Tasks.Scrum.Sprint', () => {

	loadMessages(__dirname + '../../lang/', 'en', 'header.php');

	describe('Planned sprint', () => {
		const sprintParams = {
			id: 1,
			name: 'Sprint name',
			sort: 1,
			dateStart: 1596723266,
			dateEnd: 1597881600,
			defaultSprintDuration: 604800,
			storyPoints: '39',
			status: 'planned'
		};

		let sprint = null;
		let sprintHeader = null;
		let sprintDate = null;
		let sprintStats = null;
		before(() => {
			sprint = new Sprint(sprintParams);
			sprintHeader = new SprintHeader(sprint);
			sprintDate = new SprintDate(sprint);
			sprintStats = new SprintStats(sprint);
		})

		describe('Initialization', () => {
			it('Sprint should be a function', () => {
				assert(typeof Sprint === 'function');
			});
			it('Sprint should be initialized successfully', () => {
				assert(sprint.getId() === sprintParams.id);
				assert(sprint.getName() === sprintParams.name);
				assert(sprint.getSort() === sprintParams.sort);
				assert(sprint.getDateStart() === sprintParams.dateStart);
				assert(sprint.getDateEnd() === sprintParams.dateEnd);
				assert(sprint.getDefaultSprintDuration() === sprintParams.defaultSprintDuration);
			});
			it('SprintHeader should be a function', () => {
				assert(typeof SprintHeader === 'function');
			});
			it('SprintHeader should be initialized successfully', () => {
				sprintHeader.initStyle();
				assert(sprintHeader.headerClass === 'tasks-scrum-sprint-header-planned');
			});
			it('SprintDate should be a function', () => {
				assert(typeof SprintDate === 'function');
			});
			it('SprintDate should be initialized successfully', () => {
				assert(sprintDate.defaultSprintDuration === sprintParams.defaultSprintDuration);
			});
			it('SprintStats should be a function', () => {
				assert(typeof SprintStats === 'function');
			});
			it('SprintStats should be initialized successfully', () => {
				assert(sprintStats.dateEnd === sprintParams.dateEnd);
				assert(sprintStats.storyPoints === sprintParams.storyPoints);
			});
		});

		describe('Correct behaviour', () => {
			it('Sprint must be with planned status', () => {
				assert(sprint.isPlanned() === true);
			});
			it('SprintDate should return correct weeks', () => {
				assert(sprintDate.getWeeks() === '1 '+ Loc.getMessage('TASKS_SCRUM_DATE_WEEK_NAME_1'));
			});
		});

		describe('Edge cases', () => {

		});
	});

});
