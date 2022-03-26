import {ajax, Event, Loc, Tag, Uri} from 'main.core';

import {PopupComponentsMaker} from 'ui.popupcomponentsmaker';

import {RequestSender} from './request.sender';
import {SidePanel} from './side.panel';

import 'ui.hint';

import '../css/base.css';

type Params = {
	groupId: number,
	teamSpeedPath: string,
	burnDownPath: string
}

type EpicInfoResponse = {
	data: {
		existsEpic: boolean
	}
}

type DodInfoResponse = {
	data: {
		existsDod: boolean
	}
}

type TeamSpeedResponse = {
	data: {
		existsCompletedSprint: boolean
	}
}

type TutorResponse = {
	data: {
		url: string
	}
}

type BurnDownResponse = {
	data: {
		sprint: ?SprintData
	}
}

type SprintData = {
	id: number,
	name: string
}

export class Methodology
{
	constructor(params: Params)
	{
		this.groupId = parseInt(params.groupId, 10);

		this.teamSpeedPath = params.teamSpeedPath ? params.teamSpeedPath : '';
		this.burnDownPath = params.burnDownPath ? params.burnDownPath : '';

		this.requestSender = new RequestSender();
		this.sidePanel = new SidePanel();

		this.menu = null;
	}

	showMenu(targetNode: HTMLElement)
	{
		if (this.menu)
		{
			if (this.menu.isShown())
			{
				this.menu.close();

				return;
			}
		}

		this.menu = new PopupComponentsMaker({
			target: targetNode,
			content: [
				{
					html: [
						{
							html: this.renderEpics(),
							backgroundColor: '#fafafa'
						},
						{
							html: this.renderDod(),
							backgroundColor: '#fafafa'

						}
					]
				},
				{
					html: [
						{
							html: this.renderTeamSpeed()
						}
					]
				},
				{
					html: [
						{
							html: this.renderBurnDown()
						}
					]
				},
				{
					html: [
						{
							html: this.renderTutor(),
							backgroundColor: '#fafafa'
						}
					]
				},
				{
					html: [
						{
							html: this.renderMigration()
						}
					]
				}
			]
		});

		this.menu.show();
	}

	renderEpics(): Promise
	{
		return this.requestSender.getEpicInfo({
			groupId: this.groupId
		})
			.then((response: EpicInfoResponse) => {

				const existsEpic = response.data.existsEpic;

				const buttonText = existsEpic
					? Loc.getMessage('TSF_EPIC_OPEN_BUTTON')
					: Loc.getMessage('TSF_EPIC_CREATE_BUTTON')
				;

				const buttonClass = existsEpic
					? '--border'
					: ''
				;

				const iconClass = existsEpic
					? 'ui-icon-service-epics'
					: 'ui-icon-service-light-epics'
				;

				const blockClass = existsEpic
					? '--active'
					: ''
				;

				const node = Tag.render`
					<div class="tasks-scrum__widget-methodology tasks-scrum__widget-methodology--scope tasks-scrum__widget-methodology--bg  ${blockClass}">
						<div class="tasks-scrum__widget-methodology--conteiner">
							<div
								class="ui-icon ${iconClass} tasks-scrum__widget-methodology--icon"
							><i></i></div>
							<div class="tasks-scrum__widget-methodology--content">
								<div class="tasks-scrum__widget-methodology--name">
									<span>${Loc.getMessage('TSF_EPIC_TITLE')}</span>
									<span class="ui-hint">
										<i class="ui-hint-icon" data-hint="${Loc.getMessage('TSF_EPIC_HINT')}"></i>
									</span>
								</div>
								<div class="tasks-scrum__widget-methodology--btn-box">
									<button class="ui-qr-popupcomponentmaker__btn ${buttonClass}">
										${buttonText}
									</button>
								</div>
							</div>
						</div>
					</div>
				`;

				this.initHints(node);

				Event.bind(node.querySelector('button'), 'click', () => {
					if (existsEpic)
					{
						this.showEpics();
					}
					else
					{
						this.createEpic();
					}
				});

				return node;
			})
			.catch((response) => {
				this.requestSender.showErrorAlert(response);
			})
		;
	}

	renderDod(): Promise
	{
		return this.requestSender.getDodInfo({
			groupId: this.groupId
		})
			.then((response: DodInfoResponse) => {

				const existsDod = response.data.existsDod;

				const buttonText = existsDod
					? Loc.getMessage('TSF_DOD_OPEN_BUTTON')
					: Loc.getMessage('TSF_DOD_CREATE_BUTTON')
				;

				const buttonClass = existsDod
					? '--border'
					: ''
				;

				const iconClass = existsDod
					? 'ui-icon-service-dod'
					: 'ui-icon-service-light-dod'
				;

				const blockClass = existsDod
					? '--active'
					: ''
				;

				const node = Tag.render`
					<div class="tasks-scrum__widget-methodology tasks-scrum__widget-methodology--scope tasks-scrum__widget-methodology--bg ${blockClass}">
						<div class="tasks-scrum__widget-methodology--conteiner">
							<div
								class="ui-icon ${iconClass} tasks-scrum__widget-methodology--icon"
							><i></i></div>
							<div class="tasks-scrum__widget-methodology--content">
								<div class="tasks-scrum__widget-methodology--name">
									<span>${Loc.getMessage('TSF_DOD_TITLE')}</span>
									<span class="ui-hint">
										<i class="ui-hint-icon" data-hint="${Loc.getMessage('TSF_DOD_HINT')}"></i>
									</span>
								</div>
								<div class="tasks-scrum__widget-methodology--btn-box">
									<button class="ui-qr-popupcomponentmaker__btn ${buttonClass}">
										${buttonText}
									</button>
								</div>
							</div>
						</div>
					</div>
				`;

				this.initHints(node);

				Event.bind(node.querySelector('button'), 'click', this.showDodSettings.bind(this));

				return node;
			})
			.catch((response) => {
				this.requestSender.showErrorAlert(response);
			})
		;
	}

	renderTeamSpeed(): Promise
	{
		return this.requestSender.getTeamSpeedInfo({
			groupId: this.groupId
		})
			.then((response: TeamSpeedResponse) => {

				const isDisabled = (!response.data.existsCompletedSprint);

				const btnUiClasses = 'ui-qr-popupcomponentmaker__btn --border';
				const disableClass = isDisabled ? '--disabled' : '';

				const node = Tag.render`
					<div class="tasks-scrum__widget-methodology tasks-scrum__widget-methodology--scope">
						<div class="tasks-scrum__widget-methodology--btn-box-center">
							<div class="tasks-scrum__widget-methodology--image ${disableClass}">
							</div>
							<button class="${btnUiClasses} ${disableClass}">
								${Loc.getMessage('TSF_TEAM_SPEED_BUTTON')}
							</button>
						</div>
					</div>
				`;

				if (!isDisabled)
				{
					Event.bind(node, 'click', this.showTeamSpeedChart.bind(this));
				}

				return node;
			})
			.catch((response) => {
				this.requestSender.showErrorAlert(response);
			})
		;
	}

	renderBurnDown(): Promise
	{
		return this.requestSender.getBurnDownInfo({
			groupId: this.groupId
		})
			.then((response: BurnDownResponse) => {
				const existsChart = response.data.sprint !== null;
				const btnUiClasses = 'ui-qr-popupcomponentmaker__btn --border';
				const disableClass = existsChart ? '' : '--disabled';

				const node = Tag.render`
					<div class="tasks-scrum__widget-methodology tasks-scrum__widget-methodology--scope">
						<div class="tasks-scrum__widget-methodology--btn-box-center">
							<div class="tasks-scrum__widget-methodology--image-diagram ${disableClass}">
							</div>
							<button class="${btnUiClasses} ${disableClass}">
								${Loc.getMessage('TSF_TEAM_SPEED_DIAGRAM')}
							</button>
						</div>
					</div>
				`;

				if (existsChart)
				{
					Event.bind(node, 'click', this.showBurnDownChart.bind(this, response.data.sprint.id));
				}

				return node;
			})
			.catch((response) => {
				this.requestSender.showErrorAlert(response);
			})
		;
	}

	renderTutor(): HTMLElement
	{
		return this.requestSender.getTutorInfo({
			groupId: this.groupId
		})
			.then((response: TutorResponse) => {
				const baseClasses = 'tasks-scrum__widget-methodology tasks-scrum__widget-methodology--scope';
				const tutorClasses = 'tasks-scrum__widget-methodology--training tasks-scrum__widget-methodology--bg';

				const url = response.data.url;
				const existsTutor = (url !== '');

				const iconClass = existsTutor
					? 'ui-icon-service-tutorial'
					: 'ui-icon-service-light-tutorial'
				;

				const blockClass = existsTutor
					? '--active'
					: ''
				;

				const labelClass = existsTutor
					? '--hidden'
					: ''
				;

				const node = Tag.render`
					<div class="${baseClasses} ${tutorClasses} ${blockClass}"
						<div class="tasks-scrum__widget-methodology--conteiner">
							<div class="tasks-scrum__widget-methodology--conteiner">
								<div class="ui-icon ${iconClass} tasks-scrum__widget-methodology--icon">
									<i></i>
								</div>
								<div class="tasks-scrum__widget-methodology--content">
									<div class="tasks-scrum__widget-methodology--name">
										${Loc.getMessage('TSF_TUTORIAL_TITLE')}
									</div>
									<div class="tasks-scrum__widget-methodology--description">
										${Loc.getMessage('TSF_TUTORIAL_TEXT')}
									</div>
								</div>
								<div class="tasks-scrum__widget-methodology--label ${labelClass}">
									${Loc.getMessage('TSF_TEAM_SPEED_LABEL')}
								</div>
							</div>
						</div>
					</div>
				`;

				Event.bind(node, 'click', () => {
					if (existsTutor)
					{
						ajax.runAction(
							'bitrix:tasks.scrum.info.openTutor',
							{
								data: {
									groupId: this.groupId
								},
								analyticsLabel: {
									scrum: 'Y',
									action: 'guide_open',
								}
							}
						)
							.then(() => {
								this.sidePanel.openSidePanel(url, {
									width: 1000,
									contentCallback: () => {
										return this.createTutorFrame(url);
									},
								});
							})
						;
					}
				});

				return node;
			})
			.catch((response) => {
				this.requestSender.showErrorAlert(response);
			})
		;
	}

	renderMigration()
	{
		const baseClasses = 'tasks-scrum__widget-methodology tasks-scrum__widget-methodology--scope';
		const migrationClasses = 'tasks-scrum__widget-methodology--migration tasks-scrum__widget-methodology--bg';
		const iconClass = 'ui-icon-service-tutorial tasks-scrum__widget-methodology--migration-btn';

		const node = Tag.render`
			<div class="${baseClasses} ${migrationClasses}"
				<div class="tasks-scrum__widget-methodology--conteiner">
					<div class="tasks-scrum__widget-methodology--conteiner">
						<div class="ui-icon ${iconClass} tasks-scrum__widget-methodology--icon">
							<i></i>
						</div>
						<div class="tasks-scrum__widget-methodology--content">
							<div class="tasks-scrum__widget-methodology--name">
								${Loc.getMessage('TSF_MIGRATION_TITLE')}
							</div>
						</div>
						<div class="tasks-scrum__widget-methodology--label --migration">
							${Loc.getMessage('TSF_MIGRATION_LABEL')}
						</div>
					</div>
				</div>
			</div>
		`;

		Event.bind(node, 'click', () => {
			const uri = new Uri('/marketplace/');
			uri.setQueryParam('tag', ['migrator', 'tasks']);

			this.sidePanel.openSidePanelByUrl(uri.toString());

			this.menu.close();
		});

		return node;
	}

	createTutorFrame(url: string)
	{
		const frameStyles = 'position: absolute; left: 0; top: 0; padding: 0;'
			+ ' border: none; margin: 0; width: 100%; height: 100%;';

		return new Promise((resolve) => resolve(Tag.render`<iframe style="${frameStyles}" src="${url}"></iframe>`));
	}

	showEpics()
	{
		this.sidePanel.showByExtension(
			'Epic',
			{
				view: 'list',
				groupId: this.groupId
			}
		);

		this.menu.close();
	}

	createEpic()
	{
		this.sidePanel.showByExtension(
			'Epic',
			{
				view: 'add',
				groupId: this.groupId
			}
		);

		this.menu.close();
	}

	showDodSettings()
	{
		this.sidePanel.showByExtension(
			'Dod',
			{
				view: 'settings',
				groupId: this.groupId
			}
		);

		this.menu.close();
	}

	showTeamSpeedChart()
	{
		if (this.teamSpeedPath)
		{
			this.sidePanel.openSidePanel(this.teamSpeedPath);
		}
		else
		{
			throw new Error('Could not find a page to display the chart.');
		}

		this.menu.close();
	}

	showBurnDownChart(sprintId: number)
	{
		if (this.burnDownPath)
		{
			this.sidePanel.openSidePanel(this.burnDownPath.replace('#sprint_id#', sprintId));
		}
		else
		{
			throw new Error('Could not find a page to display the chart.');
		}

		this.menu.close();
	}

	initHints(node: HTMLElement)
	{
		BX.UI.Hint.init(node);
	}
}