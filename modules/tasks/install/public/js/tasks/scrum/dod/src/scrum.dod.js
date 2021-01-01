import {Event, Loc, Runtime, Tag, Text, Dom} from 'main.core';
import {BaseEvent} from 'main.core.events';
import {SidePanel} from './side.panel';
import {RequestSender} from './request.sender';

import './css/base.css';

type Params = {
	groupId: number
}

export class ScrumDod
{
	constructor(params: Params)
	{
		this.groupId = parseInt(params.groupId, 10);

		this.sidePanel = new SidePanel();
		this.requestSender = new RequestSender();
	}

	showList(taskId: number): Promise
	{
		return this.getListOptions().then((data) => {
			if (this.isRequiredToggle(data))
			{
				this.sidePanelId = 'tasks-scrum-dod-' + Text.getRandom();

				this.taskId = taskId;

				this.sidePanel.subscribeOnce('onLoadSidePanel', this.onLoadList.bind(this));

				this.sidePanel.openSidePanel(this.sidePanelId, {
					contentCallback: () => {
						return new Promise((resolve, reject) => {
							resolve(this.buildList());
						});
					},
					zIndex: 1000
				});

				return new Promise((resolve, reject) => {
					this.resolver = resolve;
					this.rejecter = reject;
				});
			}
			else
			{
				return new Promise((resolve) => {
					resolve();
				});
			}
		});
	}

	buildList(): HTMLElement
	{
		return Tag.render`
			<div class="tasks-scrum-project-side-panel">
				<div class="tasks-scrum-project-side-panel-header">
					<span class="tasks-scrum-project-side-panel-header-title">
						${Loc.getMessage('TASKS_SCRUM_DOD_HEADER')}
					</span>
				</div>
				<div class="tasks-scrum-project-dod-error"></div>
				<div class="tasks-scrum-project-dod-list"></div>
				<div class="tasks-scrum-project-side-panel-buttons"></div>
			</div>
		`;
	}

	onLoadList(baseEvent: BaseEvent)
	{
		const sidePanel = baseEvent.getData();

		this.form = sidePanel.getContainer().querySelector('.tasks-scrum-project-side-panel');

		this.getListComponent().then((data) => {
			const listContainer = this.form.querySelector('.tasks-scrum-project-dod-list');
			Runtime.html(listContainer, data.html);
		}).then(() => {
			this.getListButtons().then((response) => {
				const buttonsContainer = this.form.querySelector('.tasks-scrum-project-side-panel-buttons');
				Runtime.html(buttonsContainer, response.html).then(() => {
					Event.bind(
						buttonsContainer.querySelector('[name=save]'),
						'click',
						this.onCompleteClick.bind(this, sidePanel)
					);
					Event.bind(
						buttonsContainer.querySelector('[name=cancel]'),
						'click',
						this.onCancelClick.bind(this, sidePanel)
					);
				});
			});
		});
	}

	getListComponent(): Promise
	{
		return new Promise((resolve, reject) => {
			this.requestSender.getListComponent({
				groupId: this.groupId,
				taskId: this.taskId
			}).then((response) => {
				resolve(response.data);
			});
		});
	}

	getListOptions(): Promise
	{
		return new Promise((resolve, reject) => {
			this.requestSender.getListOptions({
				groupId: this.groupId
			}).then((response) => {
				resolve(response.data);
			});
		});
	}

	getListButtons(): Promise
	{
		return new Promise((resolve, reject) => {
			this.requestSender.getListButtons()
				.then((response) => {
					resolve(response.data);
				});
		});
	}

	saveList(): Promise
	{
		return new Promise((resolve, reject) => {
			this.requestSender.saveList(this.getRequestDataForSaveList())
				.then((response) => {
					resolve(response.data);
				})
				.catch((response) => {
					reject(response);
				});
		});
	}

	getRequestDataForSaveList()
	{
		const requestData = {};

		requestData.taskId = this.taskId;
		requestData.items = this.getListItems();

		return requestData;
	}

	getListItems(): Array
	{
		/* eslint-disable */
		if (typeof BX.Tasks.CheckListInstance === 'undefined')
		{
			return [];
		}

		const treeStructure = BX.Tasks.CheckListInstance.getTreeStructure();
		return treeStructure.getRequestData();
		/* eslint-enable */
	}

	onCompleteClick(sidePanel)
	{
		if (this.isAllToggled())
		{
			this.saveList().then((response) => {
				sidePanel.close();
				this.resolver();
			});
		}
		else
		{
			this.removeClockIconFromButton();
			this.showError(this.getErrorContainer());
		}
	}

	onCancelClick(sidePanel)
	{
		sidePanel.close();
	}

	showError(node: HTMLElement)
	{
		Dom.clean(this.form.querySelector('.tasks-scrum-project-dod-error'));
		Dom.append(node, this.form.querySelector('.tasks-scrum-project-dod-error'));
		this.form.querySelector('.tasks-scrum-project-side-panel-header').scrollIntoView(true);
	}

	getErrorContainer(): HTMLElement
	{
		return Tag.render`
			<div class="ui-alert ui-alert-danger">
				<span class="ui-alert-message">
					${Loc.getMessage('TASKS_SCRUM_ERROR_ALL_TOGGLED')}
				</span>
			</div>
		`;
	}

	isAllToggled(): boolean
	{
		/* eslint-disable */
		if (typeof BX.Tasks.CheckListInstance === 'undefined')
		{
			return false;
		}

		let isAllToggled = true;

		const treeStructure = BX.Tasks.CheckListInstance.getTreeStructure();

		treeStructure.getDescendants().forEach((checkList) => {
			if (!checkList.checkIsComplete())
			{
				isAllToggled = false;
			}
		});

		return isAllToggled;
		/* eslint-enable */
	}

	isRequiredToggle(data): boolean
	{
		return (data.requiredOption === 'Y');
	}

	removeClockIconFromButton()
	{
		const buttonsContainer = this.form.querySelector('.tasks-scrum-project-side-panel-buttons');
		if (buttonsContainer)
		{
			Dom.removeClass(buttonsContainer.querySelector('[name=save]'), 'ui-btn-wait');
		}
	}
}