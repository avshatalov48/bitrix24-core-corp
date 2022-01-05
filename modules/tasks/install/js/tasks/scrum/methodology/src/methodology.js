import {ajax, Event, Loc, Tag, Text, Type} from 'main.core';

import {PopupComponentsMaker} from 'ui.popupcomponentsmaker';

import {RequestSender} from './request.sender';
import {SidePanel} from './side.panel';

import 'ui.hint';

import '../css/base.css';

type Params = {
	groupId: number
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

export class Methodology
{
	constructor(params: Params)
	{
		this.groupId = parseInt(params.groupId, 10);

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
							html: this.renderTutor(),
							backgroundColor: '#fafafa'
						}
					]
				}
			]
		});

		this.menu.show();
	}

	renderEpics(): HTMLElement
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
		;
	}

	renderDod(): HTMLElement
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
		;
	}

	renderTeamSpeed(): HTMLElement
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
		;
	}

	renderTutor(): HTMLElement
	{
		const node = Tag.render`
			<div class="tasks-scrum__widget-methodology tasks-scrum__widget-methodology--scope tasks-scrum__widget-methodology--bg"
				<div class="tasks-scrum__widget-methodology--conteiner">
					<div class="tasks-scrum__widget-methodology--conteiner">
						<div class="ui-icon ui-icon-service-light-tutorial tasks-scrum__widget-methodology--icon"><i></i></div>
						<div class="tasks-scrum__widget-methodology--content">
							<div class="tasks-scrum__widget-methodology--name">
								${Loc.getMessage('TSF_TUTORIAL_TITLE')}
							</div>
							<div class="tasks-scrum__widget-methodology--description">
								${Loc.getMessage('TSF_TUTORIAL_TEXT')}
							</div>
						</div>
					</div>
				</div>
			</div>
		`;

		return node;
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
		this.sidePanel.showByExtension(
			'Team-Speed-Chart',
			{
				groupId: this.groupId
			}
		);

		this.menu.close();
	}

	initHints(node: HTMLElement)
	{
		BX.UI.Hint.init(node);
	}
}