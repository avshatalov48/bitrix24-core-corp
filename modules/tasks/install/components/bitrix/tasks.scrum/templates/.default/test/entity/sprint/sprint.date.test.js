import {Loc} from 'main.core';
import {Sprint} from '../../../src/entity/sprint/sprint';
import {SprintDate} from '../../../src/entity/sprint/sprint.date';

import loadMessages from '../../load-messages';

describe('Tasks.Scrum.SprintDate', () => {

	loadMessages(__dirname + '../../../../lang/', 'en', 'header.php');

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
		it('SprintDate must be a function', () => {
			assert(typeof SprintDate === 'function');
		});
		it('SprintDate must be initialized successfully', () => {
			const sprintDate = new SprintDate(sprint);
			assert(sprintDate.defaultSprintDuration === sprintParams.defaultSprintDuration);
		});
	});

	describe('Correct behaviour', () => {
		it('SprintDate must be create a DOM element', () => {
			const sprintDate = new SprintDate(sprint);
			const nodeId = 'tasks-scrum-sprint-header-date';
			const dateStart = 'start';
			const dateEnd = 'end';
			assert.equal(
				sprintDate.renderNode(nodeId, dateStart, dateEnd).outerHTML.replace(/[\n\r\t]/g, ''),
				`
				<div id="${nodeId}" class="tasks-scrum-sprint-date">
					<div class="tasks-scrum-sprint-date-start">${dateStart}</div>
					<div class="tasks-scrum-sprint-date-separator">-</div>
					<div class="tasks-scrum-sprint-date-end">${dateEnd}</div>
					<input type="hidden" name="dateStart">
					<input type="hidden" name="dateEnd">
				</div>
				`.replace(/[\n\r\t]/g, '')
			);
		});
		it('SprintDate must be return correct weeks', () => {
			const sprintDate = new SprintDate(sprint);
			assert(sprintDate.getWeeks() === '1 '+ Loc.getMessage('TASKS_SCRUM_DATE_WEEK_NAME_1'));
		});
	});

});
