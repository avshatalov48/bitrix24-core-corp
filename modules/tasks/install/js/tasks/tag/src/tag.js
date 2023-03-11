import { ajax, Loc, Runtime, Tag, Type } from 'main.core';
import { Layout } from 'ui.sidepanel.layout';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { UI } from 'ui.notification';

type Params = {
	groupId: number,
}

export class TagActions
{
	static balloonLifeTime = 3000;

	constructor(pathToTask: string, pathToUser: string, tagId: number = null, tasksCount: number = null, groupId: number = null)
	{
		this.tagId = tagId;
		this.tasksCount = tasksCount;
		this.groupId = parseInt(groupId, 10) ?? 0;
		this.tagStorage = [];
		this.timeoutId = 0;
		this.gridId = 'tasks_by_tag_list';
		this.pathToTask = pathToTask;
		this.pathToUser = pathToUser;
		this.sidePanel = null;
		this.listData = null;
		this.pullTaskCommands = ['task_update', 'task_add', 'task_remove'];
		this.pullTagCommands = ['tag_added', 'tag_changed'];
		this.sidePanelManager = BX.SidePanel.Instance;
		this.balloon = null;
		this.timeoutDelete = null;

		this.bindEvents();
	}

	bindEvents()
	{
		this.pullTagCommands.forEach(command => {
			BX.PULL.subscribe({
				type: BX.PullClient.SubscriptionType.Server,
				moduleId: 'tasks',
				command: command,
				callback: this.onPullTag.bind(this),

			});
		});

		this.pullTaskCommands.forEach(command => {
			BX.PULL.subscribe({
				type: BX.PullClient.SubscriptionType.Server,
				moduleId: 'tasks',
				command: command,
				callback: this.onPullTask.bind(this),
			});
		});

		EventEmitter.subscribe('Grid::beforeRequest', this.onBeforeTagsGridRequest.bind(this));
	}

	onPullTag(params: Params, extra, command: string)
	{
		if (!this.getTagsGrid())
		{
			return;
		}

		const userId = parseInt(Loc.getMessage('USER_ID'), 10);
		if (this.groupId === 0 && command === 'tag_added')
		{
			this.getTagsGrid().reload();
		}
		if (this.groupId !== 0 && params.groupId === this.groupId)
		{
			if (userId !== params.userId)
			{
				this.getTagsGrid().reload();
			}
		}
	}

	onPullTask(params: Params, extra, command: string)
	{
		if (!this.getTagsGrid())
		{
			return;
		}
		clearTimeout(this.timeoutId);
		this.timeoutId = setTimeout(() => {
			this.getTagsGrid().reload();
		}, 500);
	}

	getTagsGrid()
	{
		return BX.Main.gridManager.getInstanceById('tags_list');
	}

	deleteTag(tagId): void
	{
		if (!this.getTagsGrid())
		{
			return;
		}

		clearTimeout(this.timeoutDelete);

		if (this.balloon)
		{
			this.balloon.close();
		}

		this.tagStorage.push(tagId);
		let groupDelete = false;

		if (this.tagStorage.length > 1)
		{
			groupDelete = true;
		}

		let cancelRequest = false;

		this.getTagsGrid().getRows().getById(tagId).hide();
		this.getTagsGrid().showEmptyStub();

		this.balloon = UI.Notification.Center.notify({
			content: this.tagStorage.length > 1
				? Loc.getMessage('ALL_TAGS_SUCCESSFULLY_DELETED')
				: Loc.getMessage('TAG_IS_SUCCESSFULLY_DELETED'),
			autoHideDelay: TagActions.balloonLifeTime,
				events: {
					onMouseEnter: () => {
						cancelRequest = true;
						clearTimeout(this.timeoutDelete);
					},
					onMouseLeave: () => {
						cancelRequest = false;
						this.timeoutDelete = setTimeout(sendRequest, TagActions.balloonLifeTime);
					},
				},
			actions: [{
				title: Loc.getMessage('TAG_CANCEL'),
				events: {
					click: (event, balloon, action) => {
						cancelRequest = true;
						this.tagStorage.forEach(id => {
							this.getTagsGrid().getRows().getById(id).show();
						});
						balloon.close();
					},
				},
			}],
		});

		let sendRequest = () => {
			if (cancelRequest)
			{
				return;
			}
			if (groupDelete)
			{
				groupDelete = false;
				ajax.runComponentAction('bitrix:tasks.tag.list', 'deleteTagGroup', {
						mode: 'class',
						data: {
							tags: this.tagStorage,
							groupId: this.groupId,
						},
					})
					.then(response => {
						if (response.status === 'success')
						{
							this.balloon.close();
							this.tagStorage = [];
							this.getTagsGrid().reload();
						}
					});
			}
			else
			{
				ajax.runComponentAction('bitrix:tasks.tag.list', 'deleteTag', {
						mode: 'class',
						data: {
							tagId: tagId,
							groupId: this.groupId,
						},
					})
					.then(response => {
						if (response.status === 'success')
						{
							this.balloon.close();
							this.tagStorage = [];
							this.getTagsGrid().removeRow(tagId);
						}
					});
			}
		};

		this.timeoutDelete = setTimeout(sendRequest, TagActions.balloonLifeTime);
	}

	updateTag(tagId: number): void
	{
		if (!this.getTagsGrid())
		{
			return;
		}

		if (this.balloon)
		{
			this.balloon.close();
		}

		let editingRowsCount = this.getTagsGrid().container.querySelectorAll('div.main-grid-editor-container').length;

		if (editingRowsCount === 1)
		{
			let id = this.getTagsGrid().container.querySelector('.main-grid-row.main-grid-row-body.main-grid-row-edit')
				.dataset.id;
			this.getTagsGrid().getRows().getById(id).editCancel();
		}

		this.getTagsGrid().getRows().getById(tagId).edit();

		let newName = '';
		let cell = '';
		let result = '';
		this.getTagsGrid().container.querySelector('div.main-grid-editor-container input')
			.addEventListener('keydown', (event) => {
				if (event.key === 'Enter')
				{
					if (this.balloon)
					{
						this.balloon.close();
					}
					cell = this.getTagsGrid().getRows().getById(tagId).getCellById('NAME');
					newName =
						(this.getTagsGrid().getRows().getById(tagId).getEditorContainer(cell).firstChild.value).trim();

					if (newName === '')
					{
						this.balloon = UI.Notification.Center.notify({
							content: Loc.getMessage('TAG_EMPTY_NEW_NAME'),
							autoHideDelay: TagActions.balloonLifeTime,
						});
						return;
					}

					ajax.runComponentAction('bitrix:tasks.tag.list', 'updateTag', {
						mode: 'class',
						data: {
							tagId: tagId,
							newName: newName,
							groupId: this.groupId,
						},
					}).then(response => {
						result = response.data;
						return result;
					}).then((result) => {
						if (!result.success)
						{
							if (!result.error)
							{
								this.getTagsGrid().getRows().getById(tagId).editCancel();
								return;
							}
							this.balloon = UI.Notification.Center.notify({
								content: result.error,
								autoHideDelay: TagActions.balloonLifeTime,
							});
							return;
						}
						this.getTagsGrid().updateRow(tagId);
						this.balloon = UI.Notification.Center.notify({
							content: Loc.getMessage('TAG_IS_SUCCESSFULLY_UPDATED'),
							autoHideDelay: TagActions.balloonLifeTime,
						});
					});
				}
			});
	}

	groupDelete(): void
	{
		if (!this.getTagsGrid())
		{
			return;
		}

		if (this.balloon)
		{
			this.balloon.close();
		}

		let cancelRequest = false;
		let tags = [];

		let selected = this.getTagsGrid().getRows().getSelected();

		selected.forEach(row => {
			tags.push(row.getId());
			this.getTagsGrid().getRows().getById(row.getId()).hide();
		});

		document.querySelector('div.main-grid-action-panel').className = 'main-grid-action-panel main-grid-disable';

		this.balloon = UI.Notification.Center.notify({
			content: Loc.getMessage('ALL_TAGS_SUCCESSFULLY_DELETED'),
			autoHideDelay: TagActions.balloonLifeTime,
			events: {
				onMouseEnter: () => {
					cancelRequest = true;
					clearTimeout(this.timeoutDelete);
				},
				onMouseLeave: () => {
					cancelRequest = false;
					this.timeoutDelete = setTimeout(sendRequest, TagActions.balloonLifeTime);
				},
			},
			actions: [{
				title: Loc.getMessage('TAG_CANCEL'),
				events: {
					click: (event, balloon, action) => {
						cancelRequest = true;
						tags.forEach(id => {
							let row = this.getTagsGrid().getRows().getById(id);
							row && row.show();
						});
						balloon.close();
					},
				},
			}],
		});

		let sendRequest = () => {
			if (cancelRequest)
			{
				return;
			}

			ajax.runComponentAction('bitrix:tasks.tag.list', 'deleteTagGroup', {
					mode: 'class',
					data: {
						tags: tags,
						groupId: this.groupId,
					},
				})
				.then(response => {
					if (response.status === 'success')
					{
						this.balloon.close();
						tags.forEach(tagId => {
							this.getTagsGrid().removeRow(tagId);
						});
					}
				});
		};

		this.timeoutDelete = setTimeout(sendRequest, TagActions.balloonLifeTime);
	}

	showTasksList()
	{
		this.sidePanelManager.open(
			'tasks-tag-tasks-list-side-panel',
			{
				width: 700,
				cacheable: false,
				contentCallback: () => {
					return Layout.createContent({
						extensions: ['tasks.tag'],
						title: Loc.getMessage('TASKS_BY_TAG_LIST'),
						content: this.createTasksListContent.bind(this),
						design: {
							section: false,
						},
						buttons: [],
					});
				},
				events: {
					onLoad: this.onLoadTasksList.bind(this),
				},
			});
	}

	onLoadTasksList(event)
	{
		const slider = event.getSlider();
		const listContainer = slider.getContainer().querySelector('.tasks-tags-tag-tasks-list');
		this.pullTaskCommands.forEach(command => {
			BX.PULL.subscribe({
				type: BX.PullClient.SubscriptionType.Server,
				moduleId: 'tasks',
				command: command,
				callback: () => {
					slider.destroy();
				},
			});
		});
		Runtime.html(listContainer, this.listData.html)
			.then(() => {
				EventEmitter.subscribe('Grid::beforeRequest', this.onBeforeTaskGridRequest.bind(this));
			});
	}

	onBeforeTaskGridRequest(event: BaseEvent)
	{
		const [gridObject, eventArgs] = event.getCompatData();

		eventArgs.sessid = BX.bitrix_sessid();
		eventArgs.method = 'POST';

		eventArgs.data = {
			...eventArgs.data,
			gridId: this.gridId,
			pathToTask: this.pathToTask,
			pathToUser: this.pathToUser,
			tagId: this.tagId,
			tasksCount: this.tasksCount,
		};
	}

	createTasksListContent(): Promise
	{
		return new Promise((resolve, reject) => {
			ajax.runComponentAction('bitrix:tasks.tag.list', 'getTasksByTag', {
				mode: 'class',
				data: {
					gridId: this.gridId,
					tagId: this.tagId,
					pathToTask: this.pathToTask,
					pathToUser: this.pathToUser,
					tasksCount: this.tasksCount,
				},
			}).then(response => {
				this.listData = response.data;
				resolve(this.renderTasksList());
			});
		});
	}

	renderTasksList(): HTMLElement
	{
		return Tag.render`<div class="tasks-tags-tag-tasks-list"></div>`;
	}

	show(tagId: number, tasksCount: number)
	{
		return top.BX.Runtime.loadExtension('tasks.tag')
			.then((exports) => {
				const ext = new exports['TagActions'](this.pathToTask, this.pathToUser, tagId, tasksCount);
				ext.showTasksList();
			});
	}

	onLoadTagList()
	{
		EventEmitter.subscribe('Grid::beforeRequest', this.onBeforeTagsGridRequest.bind(this));
	}

	onBeforeTagsGridRequest(event: BaseEvent)
	{
		const [gridObject, eventArgs] = event.getCompatData();

		eventArgs.sessid = BX.bitrix_sessid();
		eventArgs.method = 'POST';

		eventArgs.data = {
			...eventArgs.data,
			groupId: this.groupId,
		};
	}
}