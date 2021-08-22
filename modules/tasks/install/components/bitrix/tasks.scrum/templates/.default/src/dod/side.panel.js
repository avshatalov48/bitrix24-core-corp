import {Event, Loc, Runtime, Tag, Type, Dom} from 'main.core';
import {BaseEvent} from 'main.core.events';

import {SidePanel} from '../service/side.panel';

import {Settings} from './settings';
import {Entity} from '../entity/entity';
import {ItemType, ItemTypeParams} from './item.type';

import {RequestSender} from '../utility/request.sender';

type Params = {
	sidePanel: SidePanel,
	requestSender: RequestSender
};

import '../css/dod.css';

export class DodSidePanel
{
	constructor(params: Params)
	{
		this.sidePanel = params.sidePanel;
		this.requestSender = params.requestSender;
	}

	showSettingsPanel(entity: Entity)
	{
		this.sidePanelId = 'tasks-scrum-dod-side-panel';

		this.entity = entity;

		this.sidePanel.unsubscribeAll('onLoadSidePanel');
		this.sidePanel.subscribeOnce('onLoadSidePanel', this.onLoadSettingsPanel.bind(this));
		this.sidePanel.subscribeOnce('onCloseSidePanel', this.onCloseSettingsPanel.bind(this));

		this.sidePanel.openSidePanel(this.sidePanelId, {
			contentCallback: () => {
				return new Promise((resolve, reject) => {
					resolve(this.buildSettingsPanel());
				});
			},
			zIndex: 1000
		});
	}

	buildSettingsPanel(): HTMLElement
	{
		return Tag.render`
			<div class="tasks-scrum-dod-side-panel">
				<div class="tasks-scrum-dod-side-panel-header">
					<span class="tasks-scrum-dod-side-panel-header-title">
						${Loc.getMessage('TASKS_SCRUM_DOD_HEADER')}
					</span>
				</div>
				<div class="tasks-scrum-dod-side-panel-container"></div>
			</div>
		`;
	}

	onLoadSettingsPanel(baseEvent: BaseEvent)
	{
		const sidePanel = baseEvent.getData();

		sidePanel.showLoader();

		const container = sidePanel.getContainer().querySelector('.tasks-scrum-dod-side-panel-container');

		const dodSettingsRequest = {
			entityId: this.entity.getId()
		};

		this.requestSender.getDodSettings(dodSettingsRequest)
			.then((response) => {

				sidePanel.closeLoader();

				const types = Type.isArray(response.data.types) ? response.data.types : [];

				const itemTypes = new Map();

				types.forEach((typeData: ItemTypeParams) => {
					const itemType = new ItemType(typeData);
					itemTypes.set(itemType.getId(), itemType);
				});

				this.settings = new Settings({
					requestSender: this.requestSender,
					entityId: this.entity.getId(),
					types: itemTypes
				});

				this.settings.renderTo(container);
			})
			.catch((response) => {
				this.requestSender.showErrorAlert(
					response,
					Loc.getMessage('TASKS_SCRUM_ERROR_TITLE_POPUP')
				);
			})
		;
	}

	onCloseSettingsPanel(baseEvent: BaseEvent)
	{
		if (this.settings)
		{
			this.settings.saveSettings()
				.then(() => {})
				.catch(() => {})
			;
		}
	}
}