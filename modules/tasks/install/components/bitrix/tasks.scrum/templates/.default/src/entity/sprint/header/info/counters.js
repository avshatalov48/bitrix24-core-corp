import {Tag, Loc} from 'main.core';
import {EventEmitter} from 'main.core.events';

import {Sprint} from '../../sprint';

import 'ui.hint';

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
		this.node = Tag.render`
			<div class="tasks-scrum__sprint--event-content">
				<div class="tasks-scrum__sprint--event-container">
					<div class="tasks-scrum__sprint--subtitle">
						${Loc.getMessage('TASKS_SCRUM_TASK_LABEL')}
					</div>
					<div
						class="tasks-scrum__sprint--point"
						data-hint="${Loc.getMessage('TASKS_SCRUM_TASK_LABEL')}" data-hint-no-icon
					>
						${parseInt(this.sprint.getNumberTasks(), 10)}
					</div>
				</div>
				<div class="tasks-scrum__sprint--event-container">
					<div class="tasks-scrum__sprint--subtitle">
						${Loc.getMessage('TASKS_SCRUM_SPRINT_HEADER_STORY_POINTS')}
					</div>
					<div
						class="tasks-scrum__sprint--point"
						data-hint="${Loc.getMessage('TASKS_SCRUM_SPRINT_HEADER_STORY_POINTS')}" data-hint-no-icon
					>
						${this.sprint.getStoryPoints().isEmpty() ? '-' : this.sprint.getStoryPoints().getPoints()}
					</div>
					${this.renderAverageNumberStoryPoints()}
				</div>
			</div>
		`;

		BX.UI.Hint.createInstance({
			popupParameters: {
				closeByEsc: true,
				autoHide: true,
				animation: null
			}
		}).init(this.node);

		return this.node;
	}

	renderAverageNumberStoryPoints(): ?HTMLElement
	{
		if (
			!this.sprint.isPlanned()
			|| !this.sprint.getAverageNumberStoryPoints()
		)
		{
			return '';
		}

		return Tag.render`
			<div 
				class="tasks-scrum__sprint--point --completed"
				data-hint="${Loc.getMessage('TASKS_SCRUM_AVERAGE_NUMBER_STORY_POINTS')}" data-hint-no-icon
			>
				${this.sprint.getAverageNumberStoryPoints()}
			</div>
		`;
	}
}