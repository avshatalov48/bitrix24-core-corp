import {Loc, Dom} from 'main.core';
import {Sprint} from '../src/entity/sprint/sprint';
import {SprintHeader} from '../src/entity/sprint/sprint.header';

import loadMessages from './load-messages';

describe('Tasks.Scrum.SprintHeader', () => {

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
		status: 'planned'
	};

	let sprint = null;
	before(() => {
		sprint = new Sprint(sprintParams);
	});

	describe('Initialization', () => {
		it('SprintHeader must be a function', () => {
			assert(typeof SprintHeader === 'function');
		});
		it('SprintHeader must be initialized successfully', () => {
			const sprintHeader = new SprintHeader(sprint);
			sprintHeader.initStyle();
			assert(sprintHeader.headerClass === 'tasks-scrum-sprint-header-planned');
			assert(sprintHeader.buttonClass === 'ui-btn ui-btn-primary ui-btn-xs');
			assert(sprintHeader.buttonText === Loc.getMessage('TASKS_SCRUM_SPRINT_TITLE_START_BUTTON'));
		});
	});

	describe('Correct behaviour', () => {
		describe('Planned sprint', () => {
			it('SprintHeader must be create a DOM element', () => {
				const sprintHeader = new SprintHeader(sprint);
				sprintHeader.initStyle();
				const headerNodeId = 'tasks-scrum-sprint-header-' + sprint.getId();
				const buttonNodeId = 'tasks-scrum-sprint-header-button-' + sprint.getId();
				assert.equal(
					sprintHeader.render().outerHTML.replace(/[\n\r\t]/g, ''),
					`
				<div id="${headerNodeId}" class="tasks-scrum-sprint-header ${sprintHeader.headerClass}">
					<div class="tasks-scrum-sprint-dragndrop"></div>
					<div class="tasks-scrum-sprint-header-name-container">
						<div class="tasks-scrum-sprint-header-name">
							${sprint.getName()}
						</div>
					</div>
					<div class="tasks-scrum-sprint-header-edit"></div>
					<div class="tasks-scrum-sprint-header-remove"></div>
					<div class="tasks-scrum-sprint-header-params">
						<div id="${buttonNodeId}" class="tasks-scrum-sprint-header-button">
							<button class="${sprintHeader.buttonClass}">${sprintHeader.buttonText}</button>
						</div>
						<div class="tasks-scrum-sprint-header-tick">
							<div class="ui-btn ui-btn-sm ui-btn-light ui-btn-icon-angle-up"></div>
						</div>
					</div>
				</div>
				`.replace(/[\n\r\t]/g, '')
				);
			});
			it('SprintHeader must be bind DOM events', () => {
				const sprintHeader = new SprintHeader(sprint);
				sprintHeader.initStyle();
				const headerNode = sprintHeader.render();
				Dom.append(headerNode, document.body);
				sprintHeader.onAfterAppend();

				const eventStartSprint = 'startSprint';
				const listenerStartSprint = sinon.stub();
				sprintHeader.subscribe(eventStartSprint, listenerStartSprint);
				document.getElementById('tasks-scrum-sprint-header-button-' + sprint.getId()).click();
				assert(listenerStartSprint.callCount === 1);

				const eventChangeName = 'changeName';
				const listenerChangeName = sinon.stub();
				sprintHeader.subscribe(eventChangeName, listenerChangeName);
				headerNode.querySelector('.tasks-scrum-sprint-header-edit').click();
				assert(listenerChangeName.callCount === 1);

				const eventChangeVisibility = 'toggleVisibilityContent';
				const listenerChangeVisibility = sinon.stub();
				sprintHeader.subscribe(eventChangeVisibility, listenerChangeVisibility);
				const tickButtonNode = headerNode.querySelector('.tasks-scrum-sprint-header-tick');
				tickButtonNode.click();
				assert(listenerChangeVisibility.callCount === 1);
			});
		});
	});

});
