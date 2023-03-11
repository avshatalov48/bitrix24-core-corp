import {Event, Loc, Tag, Text, Type} from 'main.core';
import {EventEmitter} from 'main.core.events';

import {Layout} from 'ui.sidepanel.layout';
import {Confetti} from 'ui.confetti';

import {RequestSender} from './request.sender';
import {Culture, CultureData} from './culture';

import 'ui.design-tokens';
import 'ui.fonts.opensans';
import 'ui.hint';

import '../css/base.css';
import '../css/item.css';

type Params = {
	groupId: number
}

type SprintData = {
	id: number,
	name: string,
	info: {
		sprintGoal: string
	},
	epics: Array<EpicType>,
	dateStart: string,
	dateEnd: string,
	existsLastSprint: boolean,
	storyPoints: number,
	completedStoryPoints: number,
	lastStoryPoints: number,
	lastCompletedStoryPoints: number,
	plannedSprints: Array<PlannedSprint>,
	uncompletedTasks: Array<Item>,
	culture: CultureData
}

type PlannedSprint = {
	id: number,
	name: string
}

type Item = {
	id: number,
	name: string,
	responsible: {
		id: number,
		name: string,
		pathToUser: string,
		photo?: {
			src: string
		}
	},
	epic: ?EpicType,
	tags: Array<string>,
	sourceId: number,
	storyPoints: string
}

type EpicType = {
	id: number,
	name: string,
	description: string,
	color: string
}

type Response = {
	status: string,
	data: SprintData,
	errors: Array
}

export class SprintCompletionForm extends EventEmitter
{
	constructor(params: Params)
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Scrum.SprintCompletionForm');

		this.groupId = parseInt(params.groupId, 10);

		/* eslint-disable */
		this.sidePanelManager = BX.SidePanel.Instance;
		/* eslint-enable */

		this.sidePanelId = 'tasks-scrum-sprint-completion-form-side-panel';

		this.requestSender = new RequestSender();

		this.node = null;

		this.completeButton = null;
	}

	show()
	{
		this.sidePanelManager.open(
			this.sidePanelId,
			{
				cacheable: false,
				width: 700,
				contentCallback: () => {
					return Layout.createContent({
						extensions: ['tasks.scrum.sprint-completion-form'],
						title: Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETION_FORM_TITLE'),
						content: this.createContent.bind(this),
						design: {
							section: false
						},
						buttons: ({cancelButton, SaveButton}) => {
							return [
								this.completeButton = new SaveButton({
									text: Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETION_FORM_BUTTON'),
									onclick: this.onComplete.bind(this)
								}),
								cancelButton
							];
						}
					});
				}
			}
		);
	}

	onComplete()
	{
		let direction = 'backlog';

		let directionSelector = this.node.querySelector('.tasks-scrum__side-panel-completion--info-select');
		if (directionSelector)
		{
			directionSelector = directionSelector.querySelector('select');
			direction = directionSelector.value;
		}

		this.completeButton.setWaiting();

		this.requestSender.completeSprint({
			groupId: this.groupId,
			direction: direction
		})
			.then((response) => {
				if (Confetti)
				{
					Confetti.fire({
						particleCount: 400,
						spread: 80,
						origin: {
							x: 0.7,
							y: 0.2
						},
						zIndex: (this.sidePanelManager.getTopSlider().getZindex() + 1)
					}).then(() => {
						this.closeSidePanel();
						this.emit('afterComplete');
					});
				}
				else
				{
					this.closeSidePanel();
					this.emit('afterComplete');
				}
			})
			.catch((response) => {
				this.completeButton.setWaiting(false);
				this.requestSender.showErrorAlert(
					response,
					Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETION_ERROR_TITLE_POPUP')
				);
			})
		;
	}

	closeSidePanel()
	{
		const openSliders = this.sidePanelManager.getOpenSliders();
		if (openSliders.length > 0)
		{
			openSliders.forEach((slider) => {
				if (slider.getUrl() === this.sidePanelId)
				{
					slider.close(false);
				}
			});
		}
	}

	createContent()
	{
		return new Promise((resolve, reject) => {
			this.requestSender.getDataForSprintCompletionForm({
				groupId: this.groupId
			})
				.then((response: Response) => {
					Culture.getInstance().setData(response.data.culture);
					resolve(this.render(response.data));
				})
				.catch((response) => {
					reject();
					this.sidePanelManager.close(false, () => {
						this.requestSender.showErrorAlert(response);
					});
				})
			;
		});
	}

	render(sprintData: SprintData): HTMLElement
	{
		const storyPoints = (sprintData.storyPoints === '' ? 0 : sprintData.storyPoints);

		const periodDays = this.getPeriodDays(sprintData.dateStart);

		this.node = Tag.render`
			<div id="${Text.getRandom()}" class="tasks-scrum__scope--side-panel-completion">

			<div class="tasks-scrum__side-panel-completion--block">

				<div class="tasks-scrum__side-panel-completion--info-basic">
					<div class="tasks-scrum__side-panel-completion--info-basic-block">
						<div class="tasks-scrum__side-panel-completion--info-basic-title">
							${Text.encode(sprintData.name)}
						</div>
					</div>
					${this.renderGoal(sprintData.info.sprintGoal)}
				</div>

				<div class="tasks-scrum__side-panel-completion--info-additional">

					${this.renderEpics(sprintData.epics)}

					<div class="tasks-scrum__side-panel-completion--info-row">
						<div class="tasks-scrum__side-panel-completion--info-title">
							${Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETION_FORM_TIME_ROW_LABEL')}
						</div>
						<div class="tasks-scrum__side-panel-completion--info-content tasks-scrum__side-panel-completion--sprint-timing">
							<div class="tasks-scrum__side-panel-completion--date-name-block">
								<div>${Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETION_FORM_DATE_START_LABEL')}</div>
								<div>${Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETION_FORM_DATE_END_LABEL')}</div>
								${this.renderLabelPeriodDays(periodDays)}
							</div>
							<div class="tasks-scrum__side-panel-completion--date-result-block">
								<div>${this.getFormattedDateStart(sprintData.dateStart)}</div>
								<div>${this.getFormattedDateStart(sprintData.dateEnd)}</div>
								${this.renderPeriodDays(periodDays)}
							</div>
						</div>
					</div>

					<div class="tasks-scrum__side-panel-completion--info-row">

						<div class="tasks-scrum__side-panel-completion--info-title">
							${Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETION_FORM_PLAN_ROW_LABEL')}
						</div>
						<div class="tasks-scrum__side-panel-completion--info-content">
							<div class="tasks-scrum__side-panel-completion--plan-block">

								<div class="tasks-scrum__side-panel-completion--sprint-plans">
									<div class="tasks-scrum__side-panel-completion--plan-block-number --percent">
										<div 
											class="tasks-scrum__side-panel-completion--plan-block-number-date"
											title="${Text.encode(storyPoints)}"
										>
											${Text.encode(storyPoints)}
										</div>
									</div>
									<div class="tasks-scrum__side-panel-completion--plan-block-name">
										<div class="tasks-scrum__side-panel-completion--plan-block-name-text">
											${Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETION_FORM_PLAN_SP')}
										</div>
										<div class="ui-hint">
											<span
												class="ui-hint-icon"
												data-hint="${Loc.getMessage('TSS_START_STORY_POINTS_HINT')}"
												data-hint-no-icon
											></span>
										</div>
									</div>
								</div>

								<div class="tasks-scrum__side-panel-completion--sprint-plans">
									${this.renderWheelCompletedStoryPoints(sprintData)}
									<div class="tasks-scrum__side-panel-completion--plan-block-name">
										<div class="tasks-scrum__side-panel-completion--plan-block-name-text">
											${Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETION_FORM_DONE_SP')}
										</div>
										<div class="ui-hint">
											<span
												class="ui-hint-icon"
												data-hint="${Loc.getMessage('TSS_START_STORY_POINTS_HINT')}"
												data-hint-no-icon
											></span>
										</div>
									</div>
								</div>

							</div>
						</div>
					</div>
				</div>
			</div>

			${this.renderUncompletedTasks(sprintData)}

			</div>
		`;

		this.initHints(this.node);

		return this.node;
	}

	renderLabelPeriodDays(periodDays: ?string): ?HTMLElement
	{
		if (Type.isNull(periodDays))
		{
			return '';
		}

		return Tag.render`<div>${Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETION_FORM_PERIOD_LABEL')}</div>`;
	}

	renderPeriodDays(periodDays: ?string): ?HTMLElement
	{
		if (Type.isNull(periodDays))
		{
			return '';
		}

		return Tag.render`<div>${Text.encode(periodDays)}</div>`;
	}

	renderGoal(goal: string): ?HTMLElement
	{
		if (goal === '')
		{
			return '';
		}

		return Tag.render`
			<div class="tasks-scrum__side-panel-completion--info-basic-block">
				<div class="tasks-scrum__side-panel-completion--info-basic-description">
					${Text.encode(goal)}
				</div>
			</div>
		`;
	}

	renderEpics(epics: Array<EpicType>): ?HTMLElement
	{
		if (!epics.length)
		{
			return '';
		}

		return Tag.render`
			<div class="tasks-scrum__side-panel-completion--info-row">
				<div class="tasks-scrum__side-panel-completion--info-title">
					${Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETION_FORM_EPICS_ROW_LABEL')}
				</div>
				<div class="tasks-scrum__side-panel-completion--info-content">
					${
						epics.map((epic: EpicType) => {
							const colorBorder = this.convertHexToRGBA(epic.color, 0.7);
							const colorBackground = this.convertHexToRGBA(epic.color, 0.3);

							return Tag.render`
								<span
									class="tasks-scrum__epic-label"
									style="background: ${colorBackground}; border-color: ${colorBorder};"
								>${Text.encode(epic.name)}</span>`
						})
					}
				</div>
			</div>
		`;
	}

	renderWheelCompletedStoryPoints(sprintData: SprintData): HTMLElement
	{
		let differencePercentage = 0;

		const currentPercentage = this.calculatePercentage(
			sprintData.storyPoints,
			sprintData.completedStoryPoints
		);

		if (sprintData.existsLastSprint)
		{
			const lastPercentage = this.calculatePercentage(
				sprintData.lastStoryPoints,
				sprintData.lastCompletedStoryPoints
			);

			differencePercentage = parseFloat(currentPercentage) - parseFloat(lastPercentage);
		}
		else
		{
			differencePercentage = currentPercentage;
		}

		let wheelClass = '';

		if (differencePercentage > 0)
		{
			wheelClass = `tasks-scrum__side-panel-completion--plan-block-number --arrow-up --percent --success`;
		}
		else
		{
			wheelClass = `tasks-scrum__side-panel-completion--plan-block-number --arrow-down --percent --warning`;
		}

		const absoluteValue = Math.abs(differencePercentage);

		const renderProgress = (percent: number) => {
			if (percent === 0)
			{
				return '';
			}

			return Tag.render`
				<div class="tasks-scrum__side-panel-completion--progress">
					<span class="tasks-scrum__side-panel-completion--progress-number">${percent}</span>
					<span class="tasks-scrum__side-panel-completion--progress-percent">%</span></div>
				</div>
			`;
		};

		return Tag.render`
			<div class="${wheelClass}">
				<div 
					class="tasks-scrum__side-panel-completion--plan-block-number-date"
					title="${Text.encode(sprintData.completedStoryPoints)}"
				>
					${Text.encode(sprintData.completedStoryPoints)}
				</div>
				${renderProgress(absoluteValue)}
			</div>
		`;
	}

	renderUncompletedTasks(sprintData: SprintData): HTMLElement
	{
		if (sprintData.uncompletedTasks.length === 0)
		{
			return '';
		}

		return Tag.render`
			<div class="tasks-scrum__side-panel-completion--block">
				<div class="tasks-scrum__side-panel-completion--info-basic">
					<div class="tasks-scrum__side-panel-completion--info-basic-block">
						<div class="tasks-scrum__side-panel-completion--info-basic-title-icon">
							${Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETION_FORM_ACTION_ROW_LABEL')}
						</div>
					</div>
					<div class="tasks-scrum__side-panel-completion--info-basic-description">
						${Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETION_FORM_ACTION_MOVE_LABEL')}
					</div>
					${this.renderMoveSelect(sprintData.plannedSprints)}
				</div>
				<div class="tasks-scrum__side-panel-completion--info-items">
					${sprintData.uncompletedTasks.map((item: Item) => this.renderItem(item))}
				</div>
			</div>
		`;
	}

	renderMoveSelect(plannedSprints: Array<PlannedSprint>): HTMLElement
	{
		const uiClasses = 'ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100';

		let sprintsOptions = '';

		plannedSprints.forEach((sprint: PlannedSprint) => {
			sprintsOptions += `<option value="${sprint.id}">${Text.encode(sprint.name)}</option>`;
		})

		return Tag.render`
			<div class="tasks-scrum__side-panel-completion--info-select ${uiClasses}">
				<div class="ui-ctl-after ui-ctl-icon-angle"></div>
				<select class="ui-ctl-element">
					<option value="backlog">
						${Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETION_FORM_SELECTOR_BACKLOG')}
					</option>
					<option value="0">
						${Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETION_FORM_SELECTOR_SPRINT')}
					</option>
					${sprintsOptions}
				</select>
			</div>
		`;
	}

	renderItem(item: Item): HTMLElement
	{
		const src = item.responsible.photo ? Text.encode(item.responsible.photo.src) : null;
		const photoStyle = src ? `background-image: url('${encodeURI(src)}');` : '';

		const storyPointsClass = (item.storyPoints === '' ? '--empty' : '');

		const node = Tag.render`
			<div class="tasks-scrum__item-side-panel">
				<div class="tasks-scrum__item-side-panel--info">
					<div class="tasks-scrum__item-side-panel--main-info">
						<div class="tasks-scrum__item-side-panel--title">
							${Text.encode(item.name)}
						</div>
						<div class="tasks-scrum__item-side-panel--tags">
							${this.renderEpic(item.epic)}
							${this.renderTags(item.tags)}
						</div>
					</div>
				</div>
				<div class="tasks-scrum__item-side-panel--responsible">
					<div
						class="tasks-scrum__item-side-panel--responsible-photo ui-icon ui-icon-common-user"
						title="${Text.encode(item.responsible.name)}"
					><i style="${photoStyle}"></i>
					</div>
					<span>${Text.encode(item.responsible.name)}</span>
				</div>
				<div class="tasks-scrum__item-side-panel--story-points ${storyPointsClass}">
					<div class="tasks-scrum__item-side-panel--story-points-content">
						<div class="tasks-scrum__item-side-panel--story-points-element">
							${item.storyPoints === '' ? '-' : Text.encode(item.storyPoints)}
						</div>
					</div>
				</div>
			</div>
		`;

		Event.bind(node, 'click', () => this.emit('taskClick', item.sourceId))

		return node;
	}

	renderEpic(epic: ?EpicType): ?HTMLElement
	{
		if (Type.isArray(epic) || Type.isUndefined(epic))
		{
			return '';
		}

		return Tag.render`
			<div class="tasks-scrum__item-side-panel--epic --visible">
				<i
					class="tasks-scrum__item-side-panel--epic-point"
					style="${`background-color: ${epic.color}`}"
				></i>
				<span>${Text.encode(epic.name)}</span>
			</div>
		`;
	}

	renderTags(tags: Array<string>): ?HTMLElement
	{
		if (tags.length === 0)
		{
			return '';
		}

		return Tag.render`
			${tags.map((tag) => Tag.render`
				<div class="tasks-scrum__item-side-panel--hashtag --visible">#${Text.encode(tag)}</div>
			`)}
		`;
	}

	getFormattedDateStart(dateStart: number): string
	{
		/* eslint-disable */
		return BX.date.format(Culture.getInstance().getLongDateFormat(), dateStart);
		/* eslint-enable */
	}

	getFormattedDateEnd(dateEnd: number): string
	{
		/* eslint-disable */
		return BX.date.format(Culture.getInstance().getLongDateFormat(), dateEnd);
		/* eslint-enable */
	}

	getPeriodDays(dateStartTime: number): ?string
	{
		const dateWithWeekendOffset = new Date();

		dateWithWeekendOffset.setSeconds(dateWithWeekendOffset.getSeconds());
		dateWithWeekendOffset.setHours(0, 0, 0, 0);

		const dateStart = new Date(dateStartTime * 1000);

		const date = new Date();
		if (dateStart >= date)
		{
			return null;
		}

		return BX.date.format('ddiff', dateStart, dateWithWeekendOffset);
	};

	calculatePercentage(first: number, second: number): number
	{
		if (first === 0)
		{
			return 0;
		}

		const result = Math.round(second * 100 / first);

		return (isNaN(result) ? 0 : result);
	}

	convertHexToRGBA(hexCode, opacity)
	{
		let hex = hexCode.replace('#', '');

		if (hex.length === 3)
		{
			hex = `${hex[0]}${hex[0]}${hex[1]}${hex[1]}${hex[2]}${hex[2]}`;
		}

		const r = parseInt(hex.substring(0, 2), 16);
		const g = parseInt(hex.substring(2, 4), 16);
		const b = parseInt(hex.substring(4, 6), 16);

		return `rgba(${r},${g},${b},${opacity})`;
	}

	initHints(node: HTMLElement)
	{
		// todo wtf hint
		BX.UI.Hint.popup = null;
		BX.UI.Hint.id = 'ui-hint-popup-' + (+new Date());
		BX.UI.Hint.init(node);
	}
}