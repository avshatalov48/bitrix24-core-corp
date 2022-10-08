import {Type, ajax} from 'main.core';

import {Grid} from './grid';

export class PullController
{
	static get events()
	{
		return {
			add: 'add',
			update: 'update',
			remove: 'remove',
			userAdd: 'userAdd',
			userUpdate: 'userUpdate',
			userRemove: 'userRemove',
			pinChanged: 'pinChanged',
		};
	}

	static get counterEvents()
	{
		return [
			'onAfterTaskAdd',
			'onAfterTaskDelete',
			'onAfterTaskRestore',
			'onAfterTaskView',
			'onAfterTaskMute',
			'onAfterCommentAdd',
			'onAfterCommentDelete',
			'onProjectPermUpdate',
		];
	}

	static get movingProjectEvents()
	{
		return [
			'onAfterTaskAdd',
			'onAfterCommentAdd',
		];
	}

	constructor(options)
	{
		this.signedParameters = options.signedParameters;
		this.isScrumList = options.isScrumList === 'Y';
		this.createProjectUrl = options.createProjectUrl;
		this.scrumLimitSidePanelId = options.scrumLimitSidePanelId;

		this.grid = new Grid(options);

		this.timer = null;
		this.counterData = new Map();
		this.userOptions = options.userOptions;
	}

	getModuleId()
	{
		return 'tasks';
	}

	getMap()
	{
		return {
			project_add: this.onProjectAdd.bind(this),
			project_update: this.onProjectUpdate.bind(this),
			project_remove: this.onProjectRemove.bind(this),
			project_user_add: this.onProjectUserAdd.bind(this),
			project_user_update: this.onProjectUserUpdate.bind(this),
			project_user_remove: this.onProjectUserRemove.bind(this),
			project_user_option_changed: this.onProjectUserOptionChanged.bind(this),
			project_counter: this.onProjectCounter.bind(this),
			project_read_all: this.onProjectCommentsReadAll.bind(this),
			comment_read_all: this.onProjectCommentsReadAll.bind(this),
			scrum_read_all: this.onProjectCommentsReadAll.bind(this)
		};
	}

	onProjectAdd(data)
	{
		const params = {
			event: PullController.events.add,
			moveParams: {
				rowBefore: this.grid.getLastPinnedRowId(),
				rowAfter: this.grid.getFirstRowId(),
			},
		};

		this.checkExistence(data.ID).then(
			response => this.onCheckExistenceSuccess(response, data.ID, params),
			response => console.error(response)
		);

		if (this.isScrumList)
		{
			this.checkScrumLimit()
				.then(
					isLimitExceeded => this.onCheckScrumLimit(isLimitExceeded),
					response => console.error(response)
				)
			;
		}
	}

	onProjectUpdate(data)
	{
		const params = {
			event: PullController.events.update,
		};

		this.checkExistence(data.ID).then(
			response => this.onCheckExistenceSuccess(response, data.ID, params),
			response => console.error(response)
		);
	}

	onProjectRemove(data)
	{
		this.removeRow(data.ID);

		if (this.isScrumList)
		{
			this.checkScrumLimit()
				.then(
					isLimitExceeded => this.onCheckScrumLimit(isLimitExceeded),
					response => console.error(response)
				)
			;
		}
	}

	onProjectUserAdd(data)
	{
		const params = {
			event: PullController.events.userAdd,
		};

		this.checkExistence(data.GROUP_ID).then(
			response => this.onCheckExistenceSuccess(response, data.GROUP_ID, params),
			response => console.error(response)
		);
	}

	onProjectUserUpdate(data)
	{
		const params = {
			event: PullController.events.userUpdate,
		};

		this.checkExistence(data.GROUP_ID).then(
			response => this.onCheckExistenceSuccess(response, data.GROUP_ID, params),
			response => console.error(response)
		);
	}

	onProjectUserRemove(data)
	{
		const params = {
			event: PullController.events.userRemove,
		};

		this.checkExistence(data.GROUP_ID).then(
			response => this.onCheckExistenceSuccess(response, data.GROUP_ID, params),
			response => console.error(response)
		);
	}

	onProjectUserOptionChanged(data)
	{
		switch (data.OPTION)
		{
			case this.userOptions.pinned:
				this.onProjectPinChanged(data);
				break;

			default:
				break;
		}
	}

	onProjectPinChanged(data)
	{
		const params = {
			event: PullController.events.pinChanged,
		};

		this.moveToDirectPlace(data.PROJECT_ID, null, params);
	}

	onProjectCounter(data)
	{
		const groupId = data.GROUP_ID;
		const event = data.EVENT;

		if (!PullController.counterEvents.includes(event))
		{
			return;
		}

		if (!this.timer)
		{
			this.timer = setTimeout(() => {
				this.freeCounterQueue();
			}, 1000);
		}

		if (PullController.movingProjectEvents.includes(event) || !this.counterData.has(groupId))
		{
			this.counterData.set(groupId, event);
		}
	}

	freeCounterQueue()
	{
		this.counterData.forEach((event, groupId) => {
			const params = {
				event,
				highlightParams: {
					skip: true,
				},
			};
			if (PullController.movingProjectEvents.includes(event))
			{
				params.moveParams = {
					rowBefore: (this.grid.getIsPinned(groupId) ? 0 : this.grid.getLastPinnedRowId()),
					rowAfter: this.grid.getFirstRowId(),
				};
				params.highlightParams = {
					skip: false,
				};
			}
			this.checkExistence(groupId).then(
				response => this.onCheckExistenceSuccess(response, groupId, params),
				response => console.error(response)
			);
		});
		this.counterData.clear();
		this.timer = null;
	}

	onProjectCommentsReadAll(data)
	{
		const groupId = data.GROUP_ID;

		if (groupId)
		{
			if (this.grid.isRowExist(groupId))
			{
				this.updateCounter([groupId]);
			}
		}
		else
		{
			this.updateCounter(this.grid.getItems());
		}
	}

	checkExistence(groupId)
	{
		return new Promise((resolve, reject) => {
			BX.ajax.runComponentAction('bitrix:tasks.projects', 'checkExistence', {
				mode: 'class',
				data: {
					groupIds: [groupId],
				},
				signedParameters: this.signedParameters,
			}).then(
				response => resolve(response),
				response => reject(response)
			);
		});
	}

	checkScrumLimit(): Promise
	{
		return new Promise((resolve, reject) => {
			ajax.runAction(
				'bitrix:tasks.scrum.info.checkScrumLimit',
				{
					data: {},
					signedParameters: this.signedParameters,
				}
			)
				.then(
					response => resolve(response.data),
					response => reject(response)
				)
			;
		});
	}

	onCheckExistenceSuccess(response, groupId, params)
	{
		if (Type.isUndefined(response.data[groupId]))
		{
			return;
		}

		if (response.data[groupId] === false)
		{
			this.removeRow(groupId);
			return;
		}

		const group = response.data[groupId];

		if (this.grid.isRowExist(groupId))
		{
			this.grid.isActivityRealtimeMode()
				? this.updateRow(groupId, group, params)
				: this.moveToDirectPlace(groupId, group, params)
			;
		}
		else if (
			this.grid.isActivityRealtimeMode()
			&& (PullController.movingProjectEvents.includes(params.event) || params.event === PullController.events.add)
		)
		{
			this.updateItem(groupId, group, params);
		}
		else
		{
			this.moveToDirectPlace(groupId, group, params);
		}
	}

	onCheckScrumLimit(isLimitExceeded: boolean)
	{
		this.projectAddButton = document.getElementById('projectAddButton');
		if (!Type.isDomNode(this.projectAddButton))
		{
			return;
		}

		if (isLimitExceeded)
		{
			this.projectAddButton.href = `javascript:BX.UI.InfoHelper.show('${this.scrumLimitSidePanelId
				}', {isLimit: true, limitAnalyticsLabels: {module: 'tasks', source: 'scrumList'}})`
			;
		}
		else
		{
			this.projectAddButton.href = this.createProjectUrl;
		}
	}

	updateItem(rowId, rowData, params)
	{
		if (!this.grid.hasItem(rowId))
		{
			this.grid.addItem(rowId);
			this.addRow(rowId, rowData, params);
		}
		else
		{
			this.updateRow(rowId, rowData, params);
		}
	}

	addRow(rowId, rowData, params)
	{
		if (this.grid.isRowExist(rowId))
		{
			return;
		}

		BX.ajax.runComponentAction('bitrix:tasks.projects', 'prepareGridRows', {
			mode: 'class',
			data: {
				groupIds: [rowId],
				data: (rowData ? {[rowId]: rowData} : null),
			},
			signedParameters: this.signedParameters,
		})
			.then((response) => {
				if (!Type.isUndefined(response.data[rowId]))
				{
					this.grid.addRow(rowId, response.data[rowId], params);
				}
			})
		;
	}

	updateRow(rowId, rowData, params)
	{
		if (!this.grid.isRowExist(rowId))
		{
			return;
		}

		BX.ajax.runComponentAction('bitrix:tasks.projects', 'prepareGridRows', {
			mode: 'class',
			data: {
				groupIds: [rowId],
				data: (rowData ? {[rowId]: rowData} : null),
			},
			signedParameters: this.signedParameters,
		})
			.then((response) => {
				if (!Type.isUndefined(response.data[rowId]))
				{
					this.grid.updateRow(rowId, response.data[rowId], params)
				}
			})
		;
	}

	removeRow(rowId)
	{
		this.grid.removeItem(rowId);
		this.grid.removeRow(rowId);
	}

	moveToDirectPlace(groupId, data, params)
	{
		params = params || {};

		BX.ajax.runComponentAction('bitrix:tasks.projects', 'findProjectPlace', {
			mode: 'class',
			data: {
				groupId,
				currentPage: this.grid.getCurrentPage(),
			},
			signedParameters: this.signedParameters,
		}).then((response) => {
			if (response.data === null)
			{
				return;
			}

			const {projectBefore, projectAfter} = response.data;

			if (projectBefore === false && projectAfter === false)
			{
				this.removeRow(groupId);
			}
			else
			{
				if (
					(projectBefore && this.grid.isRowExist(projectBefore))
					|| (projectAfter && this.grid.isRowExist(projectAfter))
				)
				{
					params.moveParams = {
						rowBefore: projectBefore,
						rowAfter: projectAfter,
					};
				}
				else
				{
					params.moveParams = {
						skip: true,
					};
				}
				this.updateItem(groupId, data, params);
			}
		});
	}

	updateCounter(rowIds)
	{
		BX.ajax.runComponentAction('bitrix:tasks.projects', 'prepareGridRows', {
			mode: 'class',
			data: {
				groupIds: rowIds,
				data: null,
			},
			signedParameters: this.signedParameters,
		}).then((response) => {
			const projects = response.data;
			if (projects)
			{
				Object.keys(projects).forEach((projectId) => {
					if (this.grid.isRowExist(projectId))
					{
						this.grid.getRowById(projectId).setCounters(projects[projectId].counters);
					}
				});
			}
		});
	}
}