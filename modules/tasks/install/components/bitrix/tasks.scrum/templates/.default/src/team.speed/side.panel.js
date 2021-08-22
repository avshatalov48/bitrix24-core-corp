import {Loc, Tag} from 'main.core';
import {BaseEvent} from 'main.core.events';
import {SidePanel} from '../service/side.panel';
import {RequestSender} from '../utility/request.sender';
import {Chart} from './chart';

type Params = {
	sidePanel: SidePanel,
	requestSender: RequestSender
};

export class TeamSpeedSidePanel
{
	constructor(params: Params)
	{
		this.sidePanel = params.sidePanel;
		this.requestSender = params.requestSender;
	}

	showTeamSpeedChart()
	{
		this.sidePanelId = 'tasks-scrum-team-speed-chart';

		this.sidePanel.unsubscribeAll('onLoadSidePanel');
		this.sidePanel.subscribeOnce('onLoadSidePanel', this.onLoadTeamSpeedChartPanel.bind(this));

		this.sidePanel.subscribe('onCloseSidePanel', this.onCloseTeamSpeedChart.bind(this));

		this.sidePanel.openSidePanel(this.sidePanelId, {
			contentCallback: () => {
				return new Promise((resolve, reject) => {
					resolve(this.buildTeamSpeedPanel());
				});
			},
			zIndex: 1000
		});
	}

	buildTeamSpeedPanel(): HTMLElement
	{
		return Tag.render`
			<div class="tasks-scrum-project-side-panel">
				<div class="tasks-scrum-project-side-panel-header">
					<span class="tasks-scrum-project-side-panel-header-title">
						${Loc.getMessage('TASKS_SCRUM_TEAM_SPEED_CHART_HEADER')}
					</span>
				</div>
				<div class="tasks-scrum-project-side-panel-chart"></div>
				<div class="tasks-scrum-project-side-panel-buttons"></div>
			</div>
		`;
	}

	onLoadTeamSpeedChartPanel(baseEvent: BaseEvent)
	{
		const sidePanel = baseEvent.getData();

		sidePanel.showLoader();

		this.form = sidePanel.getContainer().querySelector('.tasks-scrum-project-side-panel');

		this.getTeamSpeedChartData().then((data) => {

			sidePanel.closeLoader();

			setTimeout(() => {
				this.teamSpeedChart = new Chart(data);
				this.teamSpeedChart.createChart(this.form.querySelector('.tasks-scrum-project-side-panel-chart'));
			}, 300);

		});
	}

	onCloseTeamSpeedChart(baseEvent: BaseEvent)
	{
		const sidePanel = baseEvent.getData();

		if (this.sidePanelId === sidePanel.getUrl())
		{
			setTimeout(() => {
				if (this.teamSpeedChart)
				{
					this.teamSpeedChart.destroyChart();
					this.teamSpeedChart = null;
				}
			}, 300);
		}
	}

	getTeamSpeedChartData(): Promise
	{
		return new Promise((resolve, reject) => {
			this.requestSender.getTeamSpeedChartData().then(response => {
				resolve(response.data);
			});
		});
	}
}