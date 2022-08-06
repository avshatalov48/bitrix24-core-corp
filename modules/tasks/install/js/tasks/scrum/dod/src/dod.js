import {Loc} from 'main.core';
import {EventEmitter} from 'main.core.events';

import {Layout} from 'ui.sidepanel.layout';
import {CancelButton} from 'ui.buttons';
import {UI} from 'ui.notification';

import 'ui.layout-form';
import 'ui.forms';

import {Settings} from './settings';
import {List} from './list';
import {RequestSender} from './request.sender';

import '../css/base.css';

type Params = {
	view: 'settings' | 'list',
	groupId: number,
	taskId?: number,
	skipNotifications?: boolean
}

export class Dod extends EventEmitter
{
	constructor(params: Params)
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Scrum.Dod');

		this.view = params.view;

		this.groupId = parseInt(params.groupId, 10);
		this.taskId = parseInt(params.taskId, 10);

		/* eslint-disable */
		this.sidePanelManager = BX.SidePanel.Instance;
		/* eslint-enable */

		this.requestSender = new RequestSender();

		this.settings = new Settings({
			requestSender: this.requestSender,
			groupId: this.groupId,
			taskId: this.taskId
		});

		this.list = new List({
			requestSender: this.requestSender,
			groupId: this.groupId,
			taskId: this.taskId,
			skipNotifications: params.skipNotifications
		});

		this.list.subscribe('resolve', () => {
			this.emit('resolve');
			this.sidePanelManager.close(false);
		});
		this.list.subscribe('reject', () => {
			this.emit('reject');
			this.sidePanelManager.close(false);
		});
		this.list.subscribe('showSettings', () => {
			this.sidePanelManager.close(false, () => this.showSettings());
		});
	}

	isNecessary(): Promise
	{
		return this.requestSender.isNecessary({
			groupId: this.groupId,
			taskId: this.taskId
		})
			.then((response) => {
				return response.data;
			})
			.catch((response) => {
				this.requestSender.showErrorAlert(response);
			})
		;
	}

	show()
	{
		switch(this.view)
		{
			case 'settings':
				this.showSettings();
				break;
			case 'list':
				this.showList();
				break;
		}
	}

	showSettings()
	{
		this.sidePanelManager.open(
			'tasks-scrum-dod-settings-side-panel',
			{
				cacheable: false,
				width: 1000,
				contentCallback: () => {
					return Layout.createContent({
						extensions: ['tasks.scrum.dod', 'ui.entity-selector', 'tasks'],
						title: Loc.getMessage('TASKS_SCRUM_DOD_TITLE'),
						content: this.createSettingsContent.bind(this),
						design: {
							section: false
						},
						buttons: []
					});
				},
				events: {
					onLoad: this.onLoadSettings.bind(this),
					onClose: this.onCloseSettings.bind(this),
					onCloseComplete: this.onCloseSettingsComplete.bind(this)
				}
			}
		);
	}

	showList()
	{
		this.sidePanelManager.open(
			'tasks-scrum-dod-list-side-panel',
			{
				cacheable: false,
				width: 800,
				contentCallback: () => {
					return Layout.createContent({
						extensions: ['tasks.scrum.dod', 'tasks'],
						title: Loc.getMessage('TASKS_SCRUM_DOD_TITLE'),
						content: this.createListContent.bind(this),
						design: {
							section: false
						},
						toolbar: ({Button}) => {
							return [
								new Button({
									color: Button.Color.LIGHT_BORDER,
									text: Loc.getMessage('TASKS_SCRUM_DOD_TOOLBAR_SETTINGS'),
									onclick: this.showSettings.bind(this),
								}),
							];
						},
						buttons: ({cancelButton, SaveButton}) => {
							return [
								new SaveButton({
									text: this.getListButtonText(),
									onclick: this.onSaveList.bind(this)
								}),
								new CancelButton({
									onclick: () => {
										this.emit('reject')
										this.sidePanelManager.close(false);
									}
								}),
							];
						}
					});
				},
				events: {
					onLoad: this.onLoadList.bind(this)
				}
			}
		);
	}

	createSettingsContent(): Promise
	{
		return new Promise((resolve, reject) => {
			this.settings.renderContent()
				.then((content: HTMLElement) => {
					resolve(content);
				})
			;
		});
	}

	createListContent(): Promise
	{
		return new Promise((resolve, reject) => {
			this.list.renderContent()
				.then((content: HTMLElement) => {
					resolve(content);
				})
			;
		});
	}

	onLoadSettings()
	{
		if (!this.settings.isEmpty())
		{
			this.settings.buildEditingForm(this.settings.getActiveType());
		}
	}

	onLoadList()
	{
		if (this.list.isEmpty())
		{
			return;
		}

		this.list.renderList();
	}

	onCloseSettings()
	{
		if (this.settings.isChanged())
		{
			this.settings.saveSettings()
				.then(() => {
					UI.Notification.Center.notify({
						autoHideDelay: 1000,
						content: Loc.getMessage('TASKS_SCRUM_DOD_SAVE_SETTINGS_NOTIFY')
					});
				})
				.catch(() => {})
			;
		}
	}

	onCloseSettingsComplete()
	{
		const currentSlider = this.sidePanelManager.getTopSlider();
		if (currentSlider)
		{
			if (
				currentSlider.getUrl() === 'tasks-scrum-dod-list-side-panel'
				&& this.settings.isChanged()
			)
			{
				currentSlider.reload();
			}
		}
	}

	onSaveList()
	{
		if (this.list.isEmpty())
		{
			return;
		}

		this.list.save()
			.then((decision: string) => {
				if (decision === 'resolve')
				{
					this.emit('resolve');
					this.sidePanelManager.close(false);
				}
				else if (decision === 'reject')
				{
					this.emit('reject');
					this.sidePanelManager.close(false);
				}
			})
		;
	}

	getListButtonText(): string
	{
		if (this.list.isSkipNotifications())
		{
			return Loc.getMessage('TASKS_SCRUM_DOD_CONFIRM_SAVE_BUTTON_TEXT')
		}
		else
		{
			return Loc.getMessage('TASKS_SCRUM_DOD_CONFIRM_COMPLETE_BUTTON_TEXT')
		}
	}
}