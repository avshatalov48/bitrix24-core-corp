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
	},
	pathToBurnDown: string
};

export class SprintSidePanel extends EventEmitter
{
	constructor(params: Params)
	{
		super(params);

		this.sidePanel = params.sidePanel;
		this.groupId = parseInt(params.groupId, 10);
		this.views = params.views;
		this.pathToBurnDown = params.pathToBurnDown ? params.pathToBurnDown : '';
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
		if (this.pathToBurnDown)
		{
			this.sidePanel.openSidePanel(this.pathToBurnDown.replace('#sprint_id#', sprint.getId()));
		}
		else
		{
			throw new Error('Could not find a page to display the chart.');
		}
	}
}
