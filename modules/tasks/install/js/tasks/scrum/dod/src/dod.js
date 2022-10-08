import {BaseEvent, EventEmitter} from 'main.core.events';

import {RequestSender} from './request.sender';
import {Settings} from './settings';
import {List} from './list';

import 'ui.layout-form';
import 'ui.forms';
import 'ui.fonts.opensans';

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
		this.list.subscribe('showSettings', (baseEvent: BaseEvent) => {
			const close: boolean = baseEvent.getData();
			if (close)
			{
				this.sidePanelManager.close(false, () => this.showSettings());
			}
			else
			{
				this.showSettings();
			}
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
		this.settings.show();
	}

	showList()
	{
		this.list.show();
	}
}