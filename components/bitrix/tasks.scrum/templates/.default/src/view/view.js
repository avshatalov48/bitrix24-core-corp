import {Dom, Type} from 'main.core';
import {EventEmitter} from 'main.core.events';

import {Filter} from '../service/filter';

import {RequestSender} from '../utility/request.sender';
import {Counters} from '../counters/counters';

import {Tabs} from './header/tabs';

import '../css/base.css';
import {SidePanel} from "../service/side.panel";

export type ViewInfo = {
	name: string,
	url: string,
	active: boolean
}

export type Views = {
	plan: ViewInfo,
	activeSprint: ViewInfo,
	completedSprint: ViewInfo
}

type Params = {
	signedParameters: string,
	debugMode: string,
	isOwnerCurrentUser: 'Y' | 'N',
	userId: number,
	groupId: number,
	filterId: string
}

export class View extends EventEmitter
{
	constructor(params: Params)
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Scrum.View');

		this.isOwnerCurrentUser = (params.isOwnerCurrentUser === 'Y');

		this.sidePanel = new SidePanel();

		this.requestSender = new RequestSender({
			signedParameters: params.signedParameters,
			debugMode: params.debugMode
		});

		this.filter = new Filter({
			filterId: params.filterId,
			scrumManager: this,
			requestSender: this.requestSender
		});

		this.userId = parseInt(params.userId, 10);
		this.groupId = parseInt(params.groupId, 10);
	}

	renderTo(container: HTMLElement)
	{
		if (!Type.isDomNode(container))
		{
			throw new Error('Scrum: HTMLElement for scrum not found');
		}
	}

	renderTabsTo(container: HTMLElement)
	{
		if (!Type.isDomNode(container))
		{
			throw new Error('Scrum: HTMLElement for tabs not found');
		}

		const tabs = new Tabs({
			sidePanel: this.sidePanel,
			views: this.views
		});

		Dom.append(tabs.render(), container);
	}

	renderSprintStatsTo(container: HTMLElement)
	{
		if (!Type.isDomNode(container))
		{
			throw new Error('Scrum: HTMLElement for Sprint stats not found');
		}
	}

	renderButtonsTo(container: HTMLElement)
	{
		if (!Type.isDomNode(container))
		{
			throw new Error('Scrum: HTMLElement for buttons not found');
		}
	}

	getCurrentUserId(): number
	{
		return this.userId;
	}

	getCurrentGroupId(): number
	{
		return this.groupId;
	}
}