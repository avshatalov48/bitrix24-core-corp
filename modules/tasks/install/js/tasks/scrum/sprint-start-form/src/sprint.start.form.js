import {Event, Loc, Tag, Text} from 'main.core';
import {EventEmitter} from 'main.core.events';

import {Layout} from 'ui.sidepanel.layout';

import {RequestSender} from './request.sender';

import 'ui.hint';
import 'ui.fonts.opensans';

import '../css/base.css';

type Params = {
	groupId: number,
	sprintId: number
}

type SprintData = {
	id: number,
	name: string,
	epics: Array<EpicType>,
	dateStart: string,
	dateEnd: string,
	numberTasks: number,
	storyPoints: number,
	differenceStoryPoints: string,
	differenceMarker: boolean,
	numberUnevaluatedTasks: number
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

export class SprintStartForm extends EventEmitter
{
	constructor(params: Params)
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Scrum.SprintStartForm');

		this.groupId = parseInt(params.groupId, 10);
		this.sprintId = parseInt(params.sprintId, 10);

		/* eslint-disable */
		this.sidePanelManager = BX.SidePanel.Instance;
		/* eslint-enable */

		this.requestSender = new RequestSender();

		this.node = null;

		this.startButton = null;
	}

	show()
	{
		this.sidePanelManager.open(
			'tasks-scrum-sprint-start-form-side-panel',
			{
				cacheable: false,
				width: 700,
				label: {
					text: Loc.getMessage('TASKS_SCRUM_SPRINT_SIDE_PANEL_LABEL'),
				},
				contentCallback: () => {
					return Layout.createContent({
						extensions: ['ui.dialogs.messagebox', 'tasks.scrum.sprint-start-form'],
						title: Loc.getMessage('TASKS_SCRUM_SPRINT_START_FORM_TITLE'),
						content: this.createContent.bind(this),
						design: {
							section: false
						},
						buttons: ({cancelButton, SaveButton}) => {
							return [
								this.startButton = new SaveButton({
									text: Loc.getMessage('TASKS_SCRUM_SPRINT_START_FORM_BUTTON'),
									onclick: this.onStart.bind(this)
								}),
								cancelButton
							];
						}
					});
				}
			}
		);
	}

	onStart()
	{
		this.startButton.setWaiting();

		const baseContainer = this.node.querySelector('.tasks-scrum__side-panel-start--info-basic');

		const timeContainer = this.node.querySelector('.tasks-scrum__side-panel-start--timing');
		const dateInputs = timeContainer.querySelectorAll('.ui-ctl-date');

		const dateStartValue = dateInputs.item(0).querySelector('input').value;
		const dateEndValue = dateInputs.item(1).querySelector('input').value;

		this.requestSender.startSprint({
			groupId: this.groupId,
			sprintId: this.sprintId,
			name: baseContainer.querySelector('input').value,
			sprintGoal: baseContainer.querySelector('textarea').value,
			dateStart: Math.floor(BX.parseDate(dateStartValue).getTime() / 1000),
			dateEnd: Math.floor(BX.parseDate(dateEndValue).getTime() / 1000)
		})
			.then((response) => {
				this.sidePanelManager.close(false, () => {
					this.emit('afterStart');
				});
			})
			.catch((response) => {
				this.startButton.setWaiting(false);
				this.requestSender.showErrorAlert(
					response,
					Loc.getMessage('TASKS_SCRUM_SPRINT_START_ERROR_TITLE_POPUP')
				);
			})
		;
	}

	createContent()
	{
		return new Promise((resolve, reject) => {
			this.requestSender.getDataForSprintStartForm({
				groupId: this.groupId,
				sprintId: this.sprintId
			})
				.then((response: Response) => {
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
		this.node = Tag.render`
			<div class="tasks-scrum__side-panel-start tasks-scrum__scope--side-panel-start">

				<div class="tasks-scrum__side-panel-start--block">

					<div class="tasks-scrum__side-panel-start--info-basic">
						<div class="tasks-scrum__side-panel-start--info-basic-block">
							<input
								placeholder="${Loc.getMessage('TASKS_SCRUM_SPRINT_START_FORM_NAME_PLACEHOLDER')}"
								type="text"
								class="tasks-scrum__side-panel-start--info-basic-input"
								value="${Text.encode(sprintData.name)}"
							>
						</div>
						<div class="tasks-scrum__side-panel-start--info-basic-block">
							<textarea
								placeholder="${Loc.getMessage('TASKS_SCRUM_SPRINT_START_FORM_DESC_PLACEHOLDER')}"
								rows="7"
								class="tasks-scrum__side-panel-start--info-basic-textarea"
							></textarea>
						</div>
					</div>

					<div class="tasks-scrum__side-panel-start--info-additional">

						${this.renderEpics(sprintData.epics)}

						<div class="tasks-scrum__side-panel-start--info-row tasks-scrum__side-panel-start--timing">
							<div class="tasks-scrum__side-panel-start--info-title">
								${Loc.getMessage('TASKS_SCRUM_SPRINT_START_FORM_TIME_ROW_LABEL')}
							</div>
							<div class="tasks-scrum__side-panel-start--info-content">
								<label class="tasks-scrum__side-panel-start--date">
									<div class="tasks-scrum__side-panel-start--date-name">
										${Loc.getMessage('TASKS_SCRUM_SPRINT_START_FORM_DATE_START_LABEL')}
									</div>
									<div class="ui-ctl ui-ctl-after-icon ui-ctl-date">
										<div class="ui-ctl-after ui-ctl-icon-calendar"></div>
										<input
											type="text"
											class="ui-ctl-element"
											value="${sprintData.dateStart}"
											readonly="readonly"
										>
									</div>
								</label>
								<label class="tasks-scrum__side-panel-start--date">
									<div class="tasks-scrum__side-panel-start--date-name">
										${Loc.getMessage('TASKS_SCRUM_SPRINT_START_FORM_DATE_END_LABEL')}
									</div>
									<div class="ui-ctl ui-ctl-after-icon ui-ctl-date">
										<div class="ui-ctl-after ui-ctl-icon-calendar"></div>
										<input
											type="text"
											class="ui-ctl-element"
											value="${sprintData.dateEnd}"
											readonly="readonly"
										>
									</div>
								</label>
							</div>
						</div>

						<div class="tasks-scrum__side-panel-start--info-row">
							<div class="tasks-scrum__side-panel-start--info-title">
								${Loc.getMessage('TASKS_SCRUM_SPRINT_START_FORM_PLAN_ROW_LABEL')}
							</div>
							<div class="tasks-scrum__side-panel-start--info-content">
								<div class="tasks-scrum__side-panel-start--plan-block">

									<div class="tasks-scrum__side-panel-start--sprint-plans">
										<div class="tasks-scrum__side-panel-start--plan-block-number">
											<div 
												class="tasks-scrum__side-panel-start--plan-block-number-date" 
												title="${sprintData.numberTasks}"
											>
												${sprintData.numberTasks}
											</div>
										</div>
										<div class="tasks-scrum__side-panel-start--plan-block-name">
											<div class="tasks-scrum__side-panel-start--plan-block-name-text">
												${Loc.getMessage('TASKS_SCRUM_SPRINT_START_FORM_TASK_COUNT_LABEL')}
											</div>
										</div>
									</div>

								<div class="tasks-scrum__side-panel-start--sprint-plans">
									${this.renderWheelStoryPoints(sprintData)}
									<div class="tasks-scrum__side-panel-start--plan-block-name">
										<div class="tasks-scrum__side-panel-start--plan-block-name-text">
											${Loc.getMessage('TASKS_SCRUM_SPRINT_START_FORM_STORY_POINTS_LABEL')}
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
					${this.renderWarning(sprintData.numberUnevaluatedTasks)}
				</div>
			</div>
		`;

		const timeContainer = this.node.querySelector('.tasks-scrum__side-panel-start--timing');
		timeContainer.querySelectorAll('.ui-ctl-date')
			.forEach((inputContainer: HTMLElement) => {
				Event.bind(
					inputContainer,
					'click',
					this.showCalendar.bind(this, inputContainer)
				);
			})
		;

		this.initHints(this.node);

		return this.node;
	}

	renderEpics(epics: Array<EpicType>): ?HTMLElement
	{
		if (!epics.length)
		{
			return '';
		}

		return Tag.render`
			<div class="tasks-scrum__side-panel-start--info-row">
				<div class="tasks-scrum__side-panel-start--info-title">
					${Loc.getMessage('TASKS_SCRUM_SPRINT_START_FORM_EPICS_ROW_LABEL')}
				</div>
				<div class="tasks-scrum__side-panel-start--info-content">
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

	renderWarning(count: number): ?HTMLElement
	{
		if (count === 0)
		{
			return '';
		}

		return Tag.render`
			<div class="ui-alert ui-alert-icon-danger ui-alert-warning">
				<span class="ui-alert-message">
					${Loc.getMessage('TASKS_SCRUM_SPRINT_START_FORM_WARN_TEXT').replace('#count#', count)}
				</span>
			</div>
		`;
	}

	renderWheelStoryPoints(sprintData: SprintData): string
	{
		let numberClass = '';

		if (sprintData.differenceMarker)
		{
			const arrowClass = sprintData.storyPoints === '' ? '' : '--arrow-up';

			numberClass = `tasks-scrum__side-panel-start--plan-block-number ${arrowClass} --success`;
		}
		else
		{
			const arrowClass = sprintData.storyPoints === '' ? '' : '--arrow-down';

			numberClass = `tasks-scrum__side-panel-start--plan-block-number ${arrowClass} --warning`;
		}

		const renderProgress = (differenceStoryPoints) => {
			if (parseInt(differenceStoryPoints, 10) === 0)
			{
				return '';
			}

			return Tag.render`
				<div class="tasks-scrum__side-panel-start--progress">
					<span class="tasks-scrum__side-panel-start--progress-number">${differenceStoryPoints}</span>
					<span class="tasks-scrum__side-panel-start--progress-percent">%</span></div>
				</div>
			`;
		};

		return Tag.render`
			<div class="${numberClass}">
			<div 
				class="tasks-scrum__side-panel-start--plan-block-number-date"
				title="${sprintData.storyPoints === '' ? 0 : sprintData.storyPoints}"
			>
				${sprintData.storyPoints === '' ? 0 : sprintData.storyPoints}
			</div>
			${renderProgress(sprintData.differenceStoryPoints)}
		`;
	}

	showCalendar(inputContainer: HTMLElement)
	{
		/* eslint-disable */
		new BX.JCCalendar().Show({
			node: inputContainer,
			field: inputContainer.querySelector('input'),
			bTime: false,
			bSetFocus: false,
			bHideTime: false
		})
		/* eslint-enable */
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