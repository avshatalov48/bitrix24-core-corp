import {Tag, Loc} from 'main.core';
import {EventEmitter} from 'main.core.events';

import {Sprint} from '../../sprint';


export class Counters extends EventEmitter
{
	constructor(sprint: Sprint)
	{
		super(sprint);

		this.setEventNamespace('BX.Tasks.Scrum.Sprint.Header.Info.Counters');

		this.sprint = sprint;

		this.node = null;
	}

	render(): HTMLElement
	{
		//todo maybe need for active sprint
		//${this.sprint.getUncompletedStoryPoints().getPoints()}
		//${this.sprint.getCompletedStoryPoints().getPoints()}

		this.node = Tag.render`
			<div class="tasks-scrum__sprint--event-content">
				<div class="tasks-scrum__sprint--event-container">
					<div class="tasks-scrum__sprint--subtitle">
						${Loc.getMessage('TASKS_SCRUM_TASK_LABEL')}
					</div>
					<div class="tasks-scrum__sprint--point">
						${parseInt(this.sprint.getNumberTasks(), 10)}
					</div>
				</div>
				<div class="tasks-scrum__sprint--event-container">
					<div class="tasks-scrum__sprint--subtitle">
						${Loc.getMessage('TASKS_SCRUM_SPRINT_HEADER_STORY_POINTS')}
					</div>
					<div class="tasks-scrum__sprint--point">
						${this.sprint.getStoryPoints().isEmpty() ? '-' : this.sprint.getStoryPoints().getPoints()}
					</div>
				</div>
			</div>
		`;

		return this.node;
	}
}