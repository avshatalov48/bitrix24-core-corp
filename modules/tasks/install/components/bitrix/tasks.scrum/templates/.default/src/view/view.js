import {Dom, Type} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';

import {SidePanel} from '../service/side.panel';
import {Filter} from '../service/filter';

import {RequestSender} from '../utility/request.sender';

import {Tabs} from './header/tabs';

import '../css/base.css';

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
	filterId: string,
	pathToTask: string
}

export class View extends EventEmitter
{
	constructor(params: Params)
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Scrum.View');

		this.isOwnerCurrentUser = (params.isOwnerCurrentUser === 'Y');

		this.loadItemsRepeatCounter = new Map();

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
		this.filter.subscribe('applyFilter', this.onApplyFilter.bind(this));

		this.userId = parseInt(params.userId, 10);
		this.groupId = parseInt(params.groupId, 10);

		this.pathToTask = (Type.isString(params.pathToTask) ? params.pathToTask : '');
	}

	onApplyFilter(baseEvent: BaseEvent)
	{
		this.loadItemsRepeatCounter.clear();
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

	renderRightElementsTo(container: HTMLElement)
	{
		if (!Type.isDomNode(container))
		{
			throw new Error('Scrum: HTMLElement for buttons not found');
		}
	}

	setDisplayPriority(value: string)
	{
		const availableValues = new Set([
			'backlog',
			'sprint',
		]);

		if (!availableValues.has(value))
		{
			throw Error('Invalid parameter to set display priority');
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

	getPathToTask(): string
	{
		return this.pathToTask;
	}
}