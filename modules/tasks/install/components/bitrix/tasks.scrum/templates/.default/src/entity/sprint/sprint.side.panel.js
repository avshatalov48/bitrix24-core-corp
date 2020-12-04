import {Event, Loc, Runtime, Tag, Text, Dom} from 'main.core';
import {Sprint} from './sprint';
import {SidePanel} from '../../service/side.panel';
import {RequestSender} from '../../utility/request.sender';
import {BaseEvent} from 'main.core.events';
import {Confetti} from 'ui.confetti';
import {StatsCalculator} from '../../utility/stats.calculator';

import '../../css/sprint.side.panel.css';

type Params = {
	sprints: Map,
	sidePanel: SidePanel,
	requestSender: RequestSender,
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

type EpicInfoType = {
	color: string
}

type EpicType = {
	id: number,
	name: string,
	description: string,
	info: EpicInfoType
}

type RequestDataToStartSprint = {
	sprintId: number,
	sprintGoal: string
}

type RequestDataToCompleteSprint = {
	sprintId: number,
	direction: number|string
}

export class SprintSidePanel
{
	constructor(params: Params)
	{
		this.sprints = params.sprints;
		this.sidePanel = params.sidePanel;
		this.requestSender = params.requestSender;
		this.views = params.views;

		this.currentSprint = null;
		this.uncompletedItems = new Map();

		this.lastCompletedSprint = this.getLastCompletedSprint(this.sprints);
	}

	showStartSidePanel(sprint: Sprint)
	{
		this.sidePanelId = 'tasks-scrum-start-' + Text.getRandom();

		this.currentSprint = sprint;

		this.sidePanel.subscribeOnce('onLoadSidePanel', this.onLoadStartPanel.bind(this));
		this.sidePanel.openSidePanel(this.sidePanelId, {
			contentCallback: () => {
				return new Promise((resolve, reject) => {
					resolve(this.buildStartPanel());
				});
			},
			zIndex: 1000,
			width: 600
		});
	}

	buildStartPanel(): HTMLElement
	{
		let differenceStoryPoints = this.currentSprint.getTotalStoryPoints().getPoints();
		differenceStoryPoints = (differenceStoryPoints > 0 ? '+' + differenceStoryPoints : differenceStoryPoints);
		if (this.lastCompletedSprint)
		{
			differenceStoryPoints = this.getDifferenceStoryPointsBetweenSprints(
				this.currentSprint,
				this.lastCompletedSprint
			);
		}

		return Tag.render`
			<div class="tasks-scrum-sprint-sidepanel">
				<div class="tasks-scrum-sprint-sidepanel-header">
					<span class="tasks-scrum-sprint-sidepanel-header-title">
						${Loc.getMessage('TASKS_SCRUM_SPRINT_START_HEADER')}
					</span>
				</div>
				<div class="tasks-scrum-sprint-sidepanel-block">
					<div class="tasks-scrum-sprint-sidepanel-title">
						${Loc.getMessage('TASKS_SCRUM_SPRINT_START_TITLE')}
					</div>
					<div class="tasks-scrum-sprint-sidepanel-content">
						<div class="tasks-scrum-sprint-sidepanel-info">
						<div class="tasks-scrum-sprint-sidepanel-info-box">
							<div class="tasks-scrum-sprint-sidepanel-info-title">
								${Loc.getMessage('TASKS_SCRUM_SPRINT_START_TASK_COUNT_TITLE')}
							</div>
							<div class="tasks-scrum-sprint-sidepanel-info-content">
								<span class="tasks-scrum-sprint-sidepanel-info-value">
									${parseInt(this.currentSprint.getNumberTasks())}
								</span>
							</div>
						</div>
						<div class="tasks-scrum-sprint-sidepanel-info-box tasks-scrum-sprint-sidepanel-info-box-story">
							<div class="tasks-scrum-sprint-sidepanel-info-title">
								${Loc.getMessage('TASKS_SCRUM_SPRINT_START_STORY_POINTS_COUNT_TITLE')}
							</div>
							<div class="tasks-scrum-sprint-sidepanel-info-content">
								<span class="tasks-scrum-sprint-sidepanel-info-value">
									${Text.encode(this.currentSprint.getTotalStoryPoints().getPoints())}
								</span>
								<span class="tasks-scrum-sprint-sidepanel-info-extra">
									${differenceStoryPoints}
								</span>
							</div>
						</div>
						</div>
						<div class="tasks-scrum-sprint-sidepanel-epic">
							<div class="tasks-scrum-sprint-sidepanel-epic-title">
								${Loc.getMessage('TASKS_SCRUM_SPRINT_START_EPICS_TITLE')}
							</div>
							<div class="tasks-scrum-sprint-sidepanel-epic-list">
								${[...this.currentSprint.getEpics().values()].map((epic: EpicType) => {
									return Tag.render`
										<span class="tasks-scrum-sprint-sidepanel-epic-item" style="background: ${Text.encode(epic.info.color)};">
											${Text.encode(epic.name)}
										</span>
									`;
								})}
							</div>
						</div>
					</div>
				</div>
				<div class="tasks-scrum-sprint-sidepanel-block">
					<div class="tasks-scrum-sprint-sidepanel-title">
						${Loc.getMessage('TASKS_SCRUM_SPRINT_GOAL_TITLE')}
					</div>
					<div class="tasks-scrum-sprint-sidepanel-content">
						<div class="ui-ctl ui-ctl-textarea ui-ctl-resize-y">
							<textarea class="ui-ctl-element"></textarea>
						</div>
					</div>
				</div>
				<div class="tasks-scrum-sprint-sidepanel-buttons"></div>
			</div>
		`;
	}

	onLoadStartPanel(baseEvent: BaseEvent)
	{
		const sidePanel = baseEvent.getData();

		this.form = sidePanel.getContainer().querySelector('.tasks-scrum-sprint-sidepanel');

		this.onLoadStartButtons().then((buttonsContainer: HTMLElement) => {
			Event.bind(buttonsContainer.querySelector('[name=save]'), 'click', this.onStartSprint.bind(this));
		});
	}

	showCompleteSidePanel(sprint: Sprint)
	{
		this.sidePanelId = 'tasks-scrum-start-' + Text.getRandom();

		this.currentSprint = sprint;

		this.sidePanel.subscribeOnce('onLoadSidePanel', this.onLoadCompletePanel.bind(this));
		this.sidePanel.openSidePanel(this.sidePanelId, {
			contentCallback: () => {
				return new Promise((resolve, reject) => {
					resolve(this.buildCompletePanel());
				});
			},
			zIndex: 1000,
			width: 600
		});
	}

	buildCompletePanel(): HTMLElement
	{
		return Tag.render`
			<div class="tasks-scrum-sprint-sidepanel">
				<div class="tasks-scrum-sprint-sidepanel-header">
					<span class="tasks-scrum-sprint-sidepanel-header-title">
						${Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETE_HEADER')}
					</span>
				</div>
				${this.buildSprintGoal()}
				${this.buildSprintActions()}
				${this.buildSprintPlan()}
				<div class="tasks-scrum-sprint-sidepanel-buttons"></div>
			</div>
		`;
	}

	buildSprintGoal(): HTMLElement|string
	{
		const sprintInfo = this.currentSprint.getInfo();
		const sprintGoal = sprintInfo.sprintGoal;
		if (!sprintGoal)
		{
			return '';
		}

		return Tag.render`
			<div class="tasks-scrum-sprint-sidepanel-block">
				<div class="tasks-scrum-sprint-sidepanel-title">
					${Loc.getMessage('TASKS_SCRUM_SPRINT_GOAL_TITLE')}
				</div>
				<div class="tasks-scrum-sprint-sidepanel-content">
					<div class="tasks-scrum-sprint-sidepanel-text">
						${Text.encode(sprintGoal)}
					 </div>
				</div>
			</div>
		`;
	}

	buildSprintActions(): HTMLElement|string
	{
		const uncompletedTasks = this.currentSprint.getUnCompletedTasks();
		if (uncompletedTasks === 0)
		{
			return '';
		}

		let listSprintsOptions = '';
		this.sprints.forEach((sprint) => {
			if (sprint.isPlanned())
			{
				listSprintsOptions += `<option value="${sprint.getId()}">${Text.encode(sprint.getName())}</option>`;
			}
		});

		return Tag.render`
			<div class="tasks-scrum-sprint-sidepanel-block">
				<div class="tasks-scrum-sprint-sidepanel-title tasks-scrum-sprint-sidepanel-title-icon">
					${Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETE_ACTIONS_TITLE')}
				</div>
				<div class="tasks-scrum-sprint-sidepanel-content">
					<div class="tasks-scrum-sprint-sidepanel-field">
						<div class="tasks-scrum-sprint-sidepanel-label">
							<span class="tasks-scrum-sprint-sidepanel-text">
								${Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETE_ACTIONS_SELECT')}
							</span>
						</div>
						<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100">
							<div class="ui-ctl-after ui-ctl-icon-angle"></div>
							<select class="ui-ctl-element">
								<option value="backlog">
									${Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETE_POPUP_MOVE_SELECTOR_BACKLOG')}
								</option>
								<option value="0">
									${Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETE_POPUP_NEW_SPRINT')}
								</option>
								${listSprintsOptions}
							</select>
						</div>
					</div>
					<div class="tasks-scrum-sprint-sidepanel-uncompleted">
						<span class="tasks-scrum-sprint-sidepanel-subtitle">
							${Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETE_ACTIONS_ITEM_LIST')}
						</span>
						<div class="tasks-scrum-sprint-sidepanel-uncompleted-list">
							${[...this.currentSprint.getUncompletedItems().values()].map((item: Item) => {
								const previewItem = item.getPreviewVersion();
								this.uncompletedItems.set(previewItem.getItemId(), previewItem);
								return previewItem.render();
							})}
						</div>
					</div>
				</div>
			</div>
		`;
	}

	buildSprintPlan(): HTMLElement
	{
		const statsCalculator = new StatsCalculator();
		const percentage = statsCalculator.calculatePercentage(
			this.currentSprint.getTotalStoryPoints().getPoints(),
			this.currentSprint.getTotalCompletedStoryPoints().getPoints()
		);

		let differencePercentage = statsCalculator.calculatePercentage(
			this.currentSprint.getTotalStoryPoints().getPoints(),
			this.currentSprint.getTotalCompletedStoryPoints().getPoints()
		);
		if (this.lastCompletedSprint)
		{
			differencePercentage = this.getDifferencePercentageBetweenSprints(
				this.currentSprint,
				this.lastCompletedSprint
			);
		}

		const percentageNodeClass = (differencePercentage > 0 ? '' : 'tasks-scrum-sprint-sidepanel-info-dif-min');
		const absoluteValue = Math.abs(differencePercentage);
		const speedInfoMessage = (differencePercentage > 0 ?
			Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETE_TEAM_SPEED_UP').replace('#value#', absoluteValue) :
			Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETE_TEAM_SPEED_DOWN').replace('#value#', absoluteValue)
		);

		const renderSpeed = (speedInfoMessage) => {
			return ''; //todo chart
			return Tag.render`
				<div class="tasks-scrum-sprint-sidepanel-speed">
					<span class="tasks-scrum-sprint-sidepanel-subtitle">
						${Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETE_TEAM_SPEED')}
					</span>
					<span class="tasks-scrum-sprint-sidepanel-result">${speedInfoMessage}</span>
				</div>
				<div class="tasks-scrum-sprint-sidepanel-graph"></div>
			`;
		};

		return Tag.render`
			<div class="tasks-scrum-sprint-sidepanel-block">
				<div class="tasks-scrum-sprint-sidepanel-title">
					${Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETE_PLAN_TITLE')}
				</div>
				<div class="tasks-scrum-sprint-sidepanel-content">
					<div class="tasks-scrum-sprint-sidepanel-info-box tasks-scrum-sprint-sidepanel-info-box-w100">
						<div class="tasks-scrum-sprint-sidepanel-info-title">
							${Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETE_PLAN_DONE')}
						</div>
						<div class="tasks-scrum-sprint-sidepanel-info-content">
							<span class="tasks-scrum-sprint-sidepanel-info-value">
								${percentage}%
							</span>
							<span class="tasks-scrum-sprint-sidepanel-info-dif ${percentageNodeClass}">
								<span class="tasks-scrum-sprint-sidepanel-info-dif-arrow"></span>
								<div class="tasks-scrum-sprint-sidepanel-info-dif-block">
									<span class="tasks-scrum-sprint-sidepanel-info-dif-val">${absoluteValue}</span>
									<span class="tasks-scrum-sprint-sidepanel-info-dif-icon">%</span>
								</div>
							</span>
						</div>
					</div>
					${renderSpeed()}
				</div>
			</div>
		`;
	}

	onLoadCompletePanel(baseEvent: BaseEvent)
	{
		const sidePanel = baseEvent.getData();

		this.form = sidePanel.getContainer().querySelector('.tasks-scrum-sprint-sidepanel');
		const itemsContainer = this.form.querySelector('.tasks-scrum-sprint-sidepanel-uncompleted-list');

		[...this.uncompletedItems.values()].map((previewItem: Item) => {
			previewItem.onAfterAppend(itemsContainer);
			previewItem.subscribe('showTask', () => {
				this.currentSprint.emit('showTask', previewItem);
			});
		});

		this.onLoadCompleteButtons().then((buttonsContainer: HTMLElement) => {
			Event.bind(
				buttonsContainer.querySelector('[name=save]'),
				'click',
				this.onCompleteSprint.bind(this, sidePanel)
			);
		});
	}

	getTasksCountLabel(count)
	{
		if (count > 5)
		{
			return count + ' ' + Loc.getMessage('TASKS_SCRUM_TASK_LABEL_3');
		}
		else if (count === 1)
		{
			return count + ' ' + Loc.getMessage('TASKS_SCRUM_TASK_LABEL_1');
		}
		else
		{
			return count + ' ' + Loc.getMessage('TASKS_SCRUM_TASK_LABEL_2');
		}
	}

	getLastCompletedSprint(sprints: Map): Sprint
	{
		return [...sprints.values()].find((sprint) => sprint.isCompleted() === true);
	}

	getDifferenceStoryPointsBetweenSprints(firstSprint: Sprint, secondSprint: Sprint): string
	{
		const difference = parseFloat(
			firstSprint.getTotalStoryPoints().getPoints() - secondSprint.getTotalStoryPoints().getPoints()
		);

		if (difference === 0)
		{
			return '';
		}
		else
		{
			return (difference > 0 ? '+' + difference : difference);
		}
	}

	getDifferencePercentageBetweenSprints(firstSprint: Sprint, secondSprint: Sprint): number
	{
		const statsCalculator = new StatsCalculator();

		const firstPercentage = statsCalculator.calculatePercentage(
			firstSprint.getTotalStoryPoints().getPoints(),
			firstSprint.getTotalCompletedStoryPoints().getPoints()
		);

		const secondPercentage = statsCalculator.calculatePercentage(
			secondSprint.getTotalStoryPoints().getPoints(),
			secondSprint.getTotalCompletedStoryPoints().getPoints()
		);

		return parseFloat(firstPercentage) - parseFloat(secondPercentage);
	}

	getStartButtons(): Promise
	{
		return new Promise((resolve, reject) => {
			this.requestSender.getSprintStartButtons().then(response => {
				resolve(response.data.html);
			})
		});
	}

	getCompleteButtons(): Promise
	{
		return new Promise((resolve, reject) => {
			this.requestSender.getSprintCompleteButtons().then(response => {
				resolve(response.data.html);
			})
		});
	}

	onLoadStartButtons(): Promise
	{
		return this.getStartButtons().then((buttonsHtml) => {
			const buttonsContainer = this.form.querySelector('.tasks-scrum-sprint-sidepanel-buttons');
			return Runtime.html(buttonsContainer, buttonsHtml).then(() => buttonsContainer);
		});
	}

	onLoadCompleteButtons(): Promise
	{
		return this.getCompleteButtons().then((buttonsHtml) => {
			const buttonsContainer = this.form.querySelector('.tasks-scrum-sprint-sidepanel-buttons');
			return Runtime.html(buttonsContainer, buttonsHtml).then(() => buttonsContainer);
		});
	}

	onStartSprint()
	{
		this.requestSender.startSprint(this.getRequestDataToStartSprint()).then((response) => {
			this.currentSprint.setStatus('active');
			location.href = this.views['activeSprint'].url;
		}).catch((response) => {
			this.removeClockIconFromButton();
			this.requestSender.showErrorAlert(response, Loc.getMessage('TASKS_SCRUM_SPRINT_START_ERROR_TITLE_POPUP'));
		});
	}

	onCompleteSprint(sidePanel)
	{
		this.requestSender.completeSprint(this.getRequestDataToCompleteSprint()).then((response) => {
			if (Confetti)
			{
				Confetti.fire({
					particleCount: 400,
					spread: 80,
					origin: {
						x: 0.7,
						y: 0.2
					},
					zIndex: (sidePanel.getZindex() + 1)
				}).then(() => {
					location.href = this.views['plan'].url;
				});
			}
			else
			{
				location.href = this.views['plan'].url;
			}
		}).catch((response) => {
			this.removeClockIconFromButton();
			this.requestSender.showErrorAlert(response, Loc.getMessage('TASKS_SCRUM_SPRINT_START_ERROR_TITLE_POPUP'));
		});
	}

	getRequestDataToStartSprint(): RequestDataToStartSprint
	{
		const requestData = {};

		requestData.sprintId = this.currentSprint.getId();
		requestData.sprintGoal = this.form.querySelector('textarea').value;

		return requestData;
	}

	getRequestDataToCompleteSprint(): RequestDataToCompleteSprint
	{
		const requestData = {};

		requestData.sprintId = this.currentSprint.getId();
		const directionSelectNode = this.form.querySelector('select');
		requestData.direction = (directionSelectNode ? directionSelectNode.value : 'backlog');

		return requestData;
	}

	removeClockIconFromButton()
	{
		const buttonsContainer = this.form.querySelector('.tasks-scrum-sprint-sidepanel-buttons');
		if (buttonsContainer)
		{
			Dom.removeClass(buttonsContainer.querySelector('[name=save]'), 'ui-btn-wait');
		}
	}
}