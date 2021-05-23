import {Event, Loc, Runtime, Tag, Text} from 'main.core';
import {BaseEvent} from 'main.core.events';
import {SidePanel} from '../service/side.panel';
import {RequestSender} from './request.sender';
import {TeamSpeedChart} from './team.speed.chart';
import {Entity} from '../entity/entity';

type Params = {
	sprints?: Map,
	sidePanel: SidePanel,
	requestSender: RequestSender
};

export class ProjectSidePanel
{
	constructor(params: Params)
	{
		this.sprints = params.sprints ? params.sprints : new Map();
		this.sidePanel = params.sidePanel;
		this.requestSender = params.requestSender;
	}

	showTeamSpeedChart()
	{
		this.sidePanelId = 'tasks-scrum-start-' + Text.getRandom();

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

	showDefinitionOfDone(entity: Entity)
	{
		this.sidePanelId = 'tasks-scrum-dod-' + Text.getRandom();

		this.entity = entity;

		this.sidePanel.subscribeOnce('onLoadSidePanel', this.onLoadDodPanel.bind(this));

		this.sidePanel.openSidePanel(this.sidePanelId, {
			contentCallback: () => {
				return new Promise((resolve, reject) => {
					resolve(this.buildDodPanel());
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

	buildDodPanel(): HTMLElement
	{
		return Tag.render`
			<div class="tasks-scrum-project-side-panel">
				<div class="tasks-scrum-project-side-panel-header">
					<span class="tasks-scrum-project-side-panel-header-title">
						${Loc.getMessage('TASKS_SCRUM_DOD_HEADER')}
					</span>
				</div>
				<div class="tasks-scrum-project-dod-panel"></div>
				<div class="tasks-scrum-project-dod-options">
					<label class="ui-ctl ui-ctl-checkbox">
						<input type="checkbox" class="ui-ctl-element dod-items-required">
						<div class="ui-ctl-label-text">
							${Loc.getMessage('TASKS_SCRUM_DOD_OPTIONS_REQUIRED_LABEL')}
						</div>
					</label>
				</div>
				<div class="tasks-scrum-project-side-panel-buttons"></div>
			</div>
		`;
	}

	onLoadTeamSpeedChartPanel(baseEvent: BaseEvent)
	{
		const sidePanel = baseEvent.getData();

		this.form = sidePanel.getContainer().querySelector('.tasks-scrum-project-side-panel');

		this.getTeamSpeedChartData().then((data) => {
			setTimeout(() => {
				this.teamSpeedChart = new TeamSpeedChart(data);
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
				this.teamSpeedChart.destroyChart();
				this.teamSpeedChart = null;
			}, 300);
		}
	}

	onLoadDodPanel(baseEvent: BaseEvent)
	{
		const sidePanel = baseEvent.getData();

		this.form = sidePanel.getContainer().querySelector('.tasks-scrum-project-side-panel');

		this.getDodComponent().then((data) => {
			const dodContainer = this.form.querySelector('.tasks-scrum-project-dod-panel');
			Runtime.html(dodContainer, data.html);
		}).then(() => {
			this.getDodPanelData().then((data) => {
				this.prepareDodOptionsContainer(data);
			});
		}).then(() => {
			this.requestSender.getDodButtons().then(response => {
				const buttonsContainer = this.form.querySelector('.tasks-scrum-project-side-panel-buttons');
				Runtime.html(buttonsContainer, response.data.html).then(() => {
					Event.bind(buttonsContainer.querySelector('[name=save]'), 'click', () => {
						this.requestSender.saveDod(this.getRequestDataForSaveList())
							.then(response => {
							sidePanel.close();
						});
					});
				});
			});
		});
	}

	getTeamSpeedChartData(): Promise
	{
		return new Promise((resolve, reject) => {
			this.requestSender.getTeamSpeedChartData().then(response => {
				resolve(response.data);
			});
		});
	}

	getDodComponent(): Promise
	{
		return new Promise((resolve, reject) => {
			this.requestSender.getDodComponent({
				entityId: this.entity.getId()
			}).then(response => {
				resolve(response.data);
			}).catch((response) => {
				this.requestSender.showErrorAlert(
					response,
					Loc.getMessage('TASKS_SCRUM_ERROR_TITLE_POPUP')
				);
			});
		});
	}

	getDodPanelData(): Promise
	{
		return new Promise((resolve, reject) => {
			this.requestSender.getDodPanelData({
				entityId: this.entity.getId()
			}).then(response => {
				resolve(response.data);
			}).catch((response) => {
				this.requestSender.showErrorAlert(
					response,
					Loc.getMessage('TASKS_SCRUM_ERROR_TITLE_POPUP')
				);
			});
		});
	}

	getRequestDataForSaveList()
	{
		const requestData = {};

		requestData.entityId = this.entity.getId();
		requestData.items = this.getEntityChecklistItems();
		requestData.required = this.getDodItemsRequired();

		return requestData;
	}

	getEntityChecklistItems(): Array
	{
		/* eslint-disable */
		if (typeof BX.Tasks.CheckListInstance === 'undefined')
		{
			return [];
		}

		const treeStructure = BX.Tasks.CheckListInstance.getTreeStructure();
		return treeStructure.getRequestData();
		/* eslint-enable */
	}

	getDodItemsRequired(): string
	{
		const optionsContainer = this.form.querySelector('.tasks-scrum-project-dod-options');
		const option = optionsContainer.querySelector('.dod-items-required');

		return (option.checked === true ? 'Y' : 'N');
	}

	prepareDodOptionsContainer(data)
	{
		if (data['dodItemsRequired'])
		{
			const optionsContainer = this.form.querySelector('.tasks-scrum-project-dod-options');
			const option = optionsContainer.querySelector('.dod-items-required');
			option.checked = (data['dodItemsRequired'] === 'Y');
		}
	}
}