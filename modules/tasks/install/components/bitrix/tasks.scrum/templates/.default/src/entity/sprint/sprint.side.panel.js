import { BaseEvent, EventEmitter } from 'main.core.events';
import { SidePanel } from '../../service/side.panel';
import { ViewInfo, Views } from '../../view/view';
import { Sprint } from './sprint';

type Params = {
	groupId: number,
	sidePanel: SidePanel,
	views: Views,
	pathToBurnDown: string
};

export class SprintSidePanel extends EventEmitter
{
	constructor(params: Params)
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Scrum.SprintSidePanel');

		this.sidePanel = params.sidePanel;
		this.groupId = parseInt(params.groupId, 10);
		this.views = params.views;
		this.pathToBurnDown = params.pathToBurnDown ?? '';
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
						location.href = this.views.find((view: ViewInfo) => view.id === 'activeSprint')?.url;
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
						location.href = this.views.find((view: ViewInfo) => view.id === 'plan')?.url;
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
