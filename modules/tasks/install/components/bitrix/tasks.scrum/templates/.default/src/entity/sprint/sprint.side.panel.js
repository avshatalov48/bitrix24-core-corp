import {SidePanel} from '../../service/side.panel';
import {BaseEvent, EventEmitter} from 'main.core.events';

import {Sprint} from './sprint';

type Params = {
	groupId: number,
	sidePanel: SidePanel,
	views: {
		plan: {
			name: string,
			url: string,
			active: boolean
		},
		activeSprint: {
			name: string,
			url: string,
			active: boolean
		},
		completedSprint: {
			name: string,
			url: string,
			active: boolean
		}
	}
};

export class SprintSidePanel extends EventEmitter
{
	constructor(params: Params)
	{
		super(params);

		this.sidePanel = params.sidePanel;
		this.groupId = parseInt(params.groupId, 10);
		this.views = params.views;
	}

	showStartForm(sprint: Sprint)
	{
		this.sidePanel.showByExtension(
			'Sprint-Start-Form',
			{
				groupId: this.groupId,
				sprintId: sprint.getId()
			}
		)
			.then((extension) => {
				if (extension)
				{
					extension.subscribe('afterStart', (baseEvent: BaseEvent) => {
						location.href = this.views['activeSprint'].url;
					});
				}
			})
		;
	}

	showCompletionForm()
	{
		this.sidePanel.showByExtension(
			'Sprint-Completion-Form',
			{ groupId: this.groupId }
		)
			.then((extension) => {
				if (extension)
				{
					extension.subscribe('afterComplete', (baseEvent: BaseEvent) => {
						location.href = this.views['plan'].url;
					});
					extension.subscribe('taskClick', (baseEvent: BaseEvent) => {
						this.emit('showTask', baseEvent.getData());
					});
				}
			})
		;
	}

	showBurnDownChart(sprint: Sprint)
	{
		this.sidePanel.showByExtension(
			'Burn-Down-Chart',
			{
				groupId: this.groupId,
				sprintId: sprint.getId()
			}
		);
	}
}
