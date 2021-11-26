import {Event, Text, Reflection, Type, ajax as Ajax, Loc, Uri} from 'main.core';
import {EventEmitter, BaseEvent} from 'main.core.events';
import {Loader} from 'main.loader';

import {StageFlow} from 'ui.stageflow';
import {Manager} from 'rpa.manager';
import {Timeline as RpaTimeline} from 'rpa.timeline';

import {Timeline} from 'ui.timeline';
import 'ui.notification';
import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';

const namespace = Reflection.namespace('BX.Rpa');

const reloadTasksTimeout = 1000;

class ItemDetailComponent
{
	item: {};
	stages: [];
	container: Element;
	overlay: Element;
	currentUrl: Uri;
	id: number;
	typeId: number;
	eventIds: Set;
	itemUpdatedPullTag: ?string;
	editorId: string;
	editor;
	stream: Timeline.Stream;
	taskCountersPullTag: ?string;
	completedTasks: Set;
	reloadTasksTimeoutId: ?number;

	constructor(params)
	{
		this.eventIds = new Set();
		this.completedTasks = new Set();
		this.overlay = BX.create("div", { attrs: { className: "rpa-entity-overlay" } });
		this.currentUrl = new Uri(location.href);
		if(Type.isPlainObject(params))
		{
			this.id = Text.toInteger(params.id);
			this.typeId = Text.toInteger(params.typeId);
			if(Type.isString(params.containerId))
			{
				this.container = document.getElementById(params.containerId);
			}
			if(Type.isArray(params.stages))
			{
				this.stages = params.stages;
			}
			if(Type.isPlainObject(params.item))
			{
				this.item = params.item;
			}
			if(Type.isString(params.itemUpdatedPullTag))
			{
				this.itemUpdatedPullTag = params.itemUpdatedPullTag;
			}
			if(Type.isString(params.timelinePullTag))
			{
				this.timelinePullTag = params.timelinePullTag;
			}
			if(Type.isString(params.taskCountersPullTag))
			{
				this.taskCountersPullTag = params.taskCountersPullTag;
			}

			if(Type.isString(params.editorId))
			{
				this.editorId = params.editorId;
				this.editor = BX.UI.EntityEditor.get(this.editorId);
			}
			if(params.stream instanceof Timeline.Stream)
			{
				this.stream = params.stream;
			}
		}
	}

	init()
	{
		this.initStageFlow();
		this.initTabs();
		this.initPull();
		this.bindEvents();

		if(this.id <= 0)
		{
			this.container.appendChild(this.overlay);
		}
	}

	initStageFlow()
	{
		if(this.container && this.stages && this.item)
		{
			const stageFlowContainer = this.container.querySelector('[data-role="stageflow-wrap"]');
			if(stageFlowContainer)
			{
				this.stageflowChart = new StageFlow.Chart({
					backgroundColor: 'd3d7dc',
					currentStage: this.item.stageId,
					isActive: (this.item.id > 0 && this.item.permissions.draggable),
					onStageChange: this.handleStageChange.bind(this),
					labels: {
						finalStageName: Loc.getMessage('RPA_ITEM_DETAIL_FINAL_STAGE_NAME'),
						finalStagePopupTitle: Loc.getMessage('RPA_ITEM_DETAIL_FINAL_STAGE_POPUP_TITLE'),
						finalStagePopupFail: Loc.getMessage('RPA_ITEM_DETAIL_FINAL_STAGE_POPUP_FAIL'),
						finalStageSelectorTitle: Loc.getMessage('RPA_ITEM_DETAIL_FINAL_STAGE_SELECTOR_TITLE'),
					},
				}, this.stages);
				stageFlowContainer.appendChild(this.stageflowChart.render());
			}
		}
	}

	initTabs()
	{
		if(this.container)
		{
			const tabMenu = this.container.querySelector('[data-role="tab-menu"]');
			if(tabMenu)
			{
				const tabs = tabMenu.querySelectorAll('.rpa-item-detail-tabs-item');
				if(tabs)
				{
					tabs.forEach((tab) =>
					{
						Event.bind(tab, 'click', () =>
						{
							this.handleTabClick(tab);
						})
					})
				}
			}
		}
	}

	initPull()
	{
		Event.ready(() =>
		{
			const Pull = BX.PULL;
			if(!Pull)
			{
				console.error('pull is not initialized');
				return;
			}
			if(this.itemUpdatedPullTag)
			{
				Pull.subscribe({
					moduleId: 'rpa',
					command: this.itemUpdatedPullTag,
					callback: this.handlePullItemUpdated.bind(this)
				});

				Pull.extendWatch(this.itemUpdatedPullTag);
			}
			if(this.timelinePullTag)
			{
				Pull.subscribe({
					moduleId: 'rpa',
					command: this.timelinePullTag,
					callback: this.handlePullTimelineEvent.bind(this),
				});

				Pull.extendWatch(this.timelinePullTag);
			}
			if(this.taskCountersPullTag)
			{
				Pull.subscribe({
					moduleId: 'rpa',
					command: this.taskCountersPullTag,
					callback: this.handlePullTasksCounters.bind(this),
				});

				Pull.extendWatch(this.taskCountersPullTag);
			}
		});
	}

	handlePullItemUpdated(params: {
		eventId: ?string,
		item: ?Object,
		itemChangedUserFieldNames: ?Array
	})
	{
		if(Type.isString(params.eventId) && params.eventId.length > 0 && this.eventIds.has(params.eventId))
		{
			return;
		}

		if(Type.isPlainObject(params.item))
		{
			if(params.item.stageId !== this.item.stageId)
			{
				this.item.stageId = params.item.stageId;
				this.stageflowChart.setCurrentStageId(this.item.stageId);

				this.reloadTasks();
			}
		}

		if (Type.isArray(params.itemChangedUserFieldNames) && params.itemChangedUserFieldNames.length)
		{
			this.handleItemExternalUpdate();
		}
	}

	handleItemExternalUpdate()
	{
		const slider = BX.getClass('BX.SidePanel.Instance');
		if (!this.editor || !slider)
		{
			return;
		}
		const thisSlider = slider.getSliderByWindow(window);
		//TODO: reload only editor data & layout :)

		if (this.editor.getMode() === BX.UI.EntityEditorMode.edit)
		{
			MessageBox.show({
				message: Loc.getMessage('RPA_ITEM_DETAIL_ITEM_EXTERNAL_UPDATE_NOTIFY'),
				modal: true,
				buttons: MessageBoxButtons.OK_CANCEL,
				onOk: function(messageBox) {
					thisSlider.reload();
					messageBox.close();
				}
			});
		}
		else
		{
			thisSlider.reload();
		}
	}

	handleTabClick(tab: Element)
	{
		if(!tab.classList.contains('rpa-item-detail-tabs-item-current'))
		{
			const tabs = this.container.querySelectorAll('.rpa-item-detail-tabs-item');
			if(tabs)
			{
				tabs.forEach((tab) =>
				{
					tab.classList.remove('rpa-item-detail-tabs-item-current');
				});
			}

			tab.classList.add('rpa-item-detail-tabs-item-current');
			const tabId = tab.dataset.tabId;
			const contents = this.container.querySelectorAll('.rpa-item-detail-tab-content');
			contents.forEach((content) =>
			{
				if(tabId && content.dataset.tabContent && content.dataset.tabContent === tabId)
				{
					content.classList.remove('rpa-item-detail-tab-content-hidden');
				}
				else
				{
					content.classList.add('rpa-item-detail-tab-content-hidden');
				}
			});
		}
	}

	handleStageChange(stage: StageFlow.Stage)
	{
		if(this.isProgress())
		{
			return;
		}
		this.startProgress();
		this.progress = false;
		const eventId = Text.getRandom();
		this.eventIds.add(eventId);
		Ajax.runAction('rpa.item.update', {
			analyticsLabel: 'rpaItemDetailUpdateStage',
			data: {
				id: this.item.id,
				typeId: this.item.typeId,
				fields: {
					stageId: stage.getId(),
				},
				eventId
			}
		}).then((response) =>
		{
			this.stopProgress();
			this.item = response.data.item;
			this.stageflowChart.setCurrentStageId(response.data.item.stageId);
			this.stageflowChart.render();
		}).catch((response) =>
		{
			this.stopProgress();
			let isShowTasks = false;
			response.errors.forEach((error) =>
			{
				if(error.code && error.code === 'RPA_ITEM_USER_HAS_TASKS')
				{
					isShowTasks = true;
				}
				else
				{
					BX.UI.Notification.Center.notify({
						content: error.message
					});
				}
			});

			if(isShowTasks)
			{
				Manager.Instance.openTasks(this.item.typeId, this.item.id);
			}
		})
	}

	getLoader(): Loader
	{
		if(!this.loader)
		{
			this.loader = new Loader({
				size: 200,
			});
		}

		return this.loader;
	}

	startProgress()
	{
		this.progress = true;
		if(!this.getLoader().isShown())
		{
			this.getLoader().show(this.container);
		}
	}

	stopProgress()
	{
		this.progress = false;
		if(this.getLoader().isShown())
		{
			this.getLoader().hide();
		}
	}

	isProgress(): boolean
	{
		return (this.progress === true);
	}

	showErrors(errors: Array|string)
	{

	}

	handlePullTimelineEvent(params: {
		command: string,
		timeline: ?Object,
		eventId: ?string,
	})
	{
		if(!Type.isPlainObject(params))
		{
			return;
		}
		if(Type.isString(params.eventId) && params.eventId.length > 0 && this.eventIds.has(params.eventId))
		{
			return;
		}
		if(params.command === 'add')
		{
			this.handlePullTimelineAdd(params);
		}
		else if(params.command === 'update')
		{
			this.handlePullTimelineUpdate(params);
		}
		else if(params.command === 'pin')
		{
			this.handlePullTimelinePin(params);
		}
		else if(params.command === 'delete')
		{
			this.handlePullTimelineDelete(params);
		}
	}

	handlePullTimelineAdd(params: {
		timeline: ?Object,
		comment: ?Object,
	})
	{
		let timeline = params.timeline;
		if(!timeline)
		{
			timeline = params.comment;
		}
		if(Type.isPlainObject(timeline))
		{
			this.stream.addUsers(timeline.users);
			const item = this.stream.createItem(timeline);
			if(item instanceof RpaTimeline.TaskComplete)
			{
				if(this.completedTasks.has(item.data.task.ID))
				{
					return;
				}
				this.reloadTasks();
			}
			if(item)
			{
				this.stream.insertItem(item);
			}
		}
	}

	handlePullTimelinePin(params: {
		timeline: ?Object,
	})
	{
		if(Type.isPlainObject(params.timeline))
		{
			const item = this.stream.getItem(params.timeline.id);
			item.isFixed = params.timeline.isFixed;
			item.renderPin();
			if(item.isFixed)
			{
				this.stream.pinItem(item);
			}
			else
			{
				this.stream.unPinItem(item);
			}
		}
	}

	handlePullTimelineDelete(params: {
		timeline: ?{
			id: ?number,
		}
	})
	{
		if(params && params.timeline && params.timeline.id > 0)
		{
			const item = this.stream.getItem(params.timeline.id);
			if(item)
			{
				this.stream.deleteItem(item);
			}
		}
	}

	handlePullTimelineUpdate(params: {
		timeline: ?{
			id: number,
			description: string
		}
	})
	{
		if(params && params.timeline && params.timeline.id > 0)
		{
			let item = this.stream.getItem(params.timeline.id);
			if(item)
			{
				item.update(params.timeline);
			}
			item = this.stream.getPinnedItem(params.timeline.id);
			if(item)
			{
				item.update(params.timeline);
			}
		}
	}

	handlePullTasksCounters(params: {
		typeId: ?number,
		itemId: ?number,
	})
	{
		if (params.typeId === this.typeId && params.itemId === this.id)
		{
			this.reloadTasks()
		}
	}

	reloadTasks()
	{
		if(this.isProgress())
		{
			return;
		}

		if(this.reloadTasksTimeoutId)
		{
			return;
		}

		this.reloadTasksTimeoutId = setTimeout(() =>
		{
			this.startProgress();
			Ajax.runAction('rpa.item.getTasks', {
				analyticsLabel: 'rpaItemTimelineGetTasks',
				data: {
					typeId: this.typeId,
					id: this.id,
				}
			}).then((response) => {
				this.stopProgress();
				this.reloadTasksTimeoutId = null;
				if (Type.isArray(response.data.tasks))
				{
					this.stream.updateTasks(response.data.tasks);
				}
			}).catch(() => {
				this.reloadTasksTimeoutId = null;
				this.stopProgress();
			});
		}, reloadTasksTimeout);
	}

	bindEvents()
	{
		Event.ready(() =>
		{
			EventEmitter.subscribe('BX.UI.EntityEditor:onSave', (event: BaseEvent) => {
				if(Type.isArray(event.getData()))
				{
					const editor = event.getData()[0];
					if(editor._ajaxForm && Type.isFunction(editor._ajaxForm.addUrlParams))
					{
						const eventId = Text.getRandom();
						this.eventIds.add(eventId);
						editor._ajaxForm.addUrlParams({
							eventId
						});
					}
				}
			});

			if(!this.item.id)
			{
				EventEmitter.subscribe('BX.UI.EntityEditorAjax:onSubmit', (event: BaseEvent) => {
					const data = event.getData();
					if(Type.isArray(data))
					{
						const response = data[1];
						if(response && response.data && response.data.item)
						{
							const url = Manager.Instance.getItemDetailUrl(this.typeId, response.data.item.id);
							if(url)
							{
								const {IFRAME: iframe, IFRAME_TYPE: iframeType} = this.currentUrl.getQueryParams();
								const isSlider = (iframe === 'Y') && (iframeType === 'SIDE_SLIDER');
								if (isSlider)
								{
									url.setQueryParams({ IFRAME: 'Y', IFRAME_TYPE: 'SIDE_SLIDER' });
								}

								location.href = url.toString();
							}
						}
					}
				});
			}

			this.stream.subscribe('onScrollToTheBottom', this.loadItems.bind(this));
			this.stream.subscribe('onPinClick', this.handleItemPinClick.bind(this));

			EventEmitter.subscribe('BX.UI.Timeline.CommentEditor:onLoadVisualEditor', (event: BaseEvent) => {
				return new Promise((resolve, reject) => {
					Ajax.runAction('rpa.comment.getVisualEditor', {
						analyticsLabel: 'rpaTimelineCommentLoadVisualEditor',
						data: {
							name: event.getData().name,
							commentId: event.getData().commentId,
						}
					}).then((response) => {
						event.getData().html = response.data.html;
						resolve();
					}).catch(() => {
						reject();
					});
				});
			});

			EventEmitter.subscribe('BX.UI.Timeline.CommentEditor:onSave', (event: BaseEvent) => {
				return new Promise((resolve, reject) => {
					const eventId = Text.getRandom();
					this.eventIds.add(eventId);
					let analyticsLabel = 'rpaTimelineCommentAdd';
					let action = 'rpa.comment.add';
					let data = {
						typeId: this.typeId,
						itemId: this.id,
						fields: {
							description: event.getData().description,
							files: event.getData().files,
						},
						eventId,
					};
					const commentId = Text.toInteger(event.getData().commentId);
					if(commentId > 0)
					{
						action = 'rpa.comment.update';
						data.id = commentId;
						analyticsLabel = 'rpaTimelineCommentUpdate';
					}
					Ajax.runAction(action, {
						analyticsLabel,
						data,
					}).then((response) => {
						event.getData().comment = response.data.comment;
						if(commentId <= 0)
						{
							if(response.data && response.data.comment)
							{
								this.stream.addUsers(response.data.comment.users);
								const item = this.stream.createItem(response.data.comment);
								if(item)
								{
									this.stream.insertItem(item);
								}
							}
						} else
						{
							const comment = this.stream.createItem(response.data.comment);
							const commonComment = this.stream.getItem(comment.getId());
							if(commonComment)
							{
								commonComment.update(response.data.comment);
							}
							const pinnedComment = this.stream.getPinnedItem(comment.getId());
							if(pinnedComment)
							{
								pinnedComment.update(response.data.comment);
							}
						}
						resolve();
					}).catch((response) => {
						event.getData().message = response.errors.map(({message}) => {
							return message;
						}).join("; ");
						reject();
					});
				});
			});

			EventEmitter.subscribe('BX.UI.Timeline.Comment:onLoadContent', (event: BaseEvent) => {
				return new Promise((resolve, reject) => {
					const commentId = Text.toInteger(event.getData().commentId);
					if(!commentId)
					{
						reject();
						return;
					}
					Ajax.runAction('rpa.comment.get', {
						analyticsLabel: 'rpaTimelineCommentGetContent',
						data: {
							id: commentId,
						}
					})
					.then((response) => {
						if(response.data && response.data.comment)
						{
							event.getData().comment = response.data.comment;
							resolve();
						}
						else
						{
							reject();
						}
					})
					.catch((response) => {
						event.getData().message = response.errors.map(({message}) => {
							return message;
						}).join("; ");
						reject();
					});
				});
			});

			EventEmitter.subscribe('BX.UI.Timeline.Comment:onLoadFilesContent', (event: BaseEvent) => {
				return new Promise((resolve, reject) => {
					const commentId = Text.toInteger(event.getData().commentId);
					if(!commentId)
					{
						reject();
						return;
					}
					Ajax.runAction('rpa.comment.getFilesContent', {
						analyticsLabel: 'rpaTimelineCommentGetFilesContent',
						data: {
							id: commentId,
						}
					})
					.then((response) => {
						if(response.data && Type.isString(response.data.html) && response.data.html.length > 0)
						{
							event.getData().html = response.data.html;
							resolve();
						}
						else
						{
							reject();
						}
					})
					.catch((response) => {
						event.getData().message = response.errors.map(({message}) => {
							return message;
						}).join("; ");
						reject();
					});
				});
			});


			EventEmitter.subscribe('BX.UI.Timeline.Comment:onDelete', (event: BaseEvent) => {
				return new Promise((resolve, reject) => {
					const commentId = Text.toInteger(event.getData().commentId);
					if(!commentId)
					{
						reject();
						return;
					}
					const eventId = Text.getRandom();
					this.eventIds.add(eventId);
					Ajax.runAction('rpa.comment.delete', {
						analyticsLabel: 'rpaTimelineCommentDelete',
						data: {
							id: commentId,
							eventId,
						}
					})
					.then(() => {
						resolve();
					})
					.catch((response) => {
						event.getData().message = response.errors.map(({message}) => {
							return message;
						}).join("; ");
						reject();
					});
				});
			});

			EventEmitter.subscribe('BX.Rpa.Timeline.Task:onBeforeCompleteTask', (event: BaseEvent) => {
				this.completedTasks.add(event.getData().taskId);
			});
		});
	}

	loadItems()
	{
		if(this.isProgress())
		{
			return;
		}
		this.startProgress();
		this.stream.currentPage++;
		Ajax.runAction('rpa.timeline.listForItem', {
			analyticsLabel: 'rpaItemDetailTimelineLoadOnScroll',
			data: {
				typeId: this.typeId,
				itemId: this.id,
			},
			navigation: {
				page: this.stream.currentPage,
				size: this.stream.pageSize,
			}
		}).then((response) => {
			this.stopProgress();
			const items = response.data.timeline;
			if(Type.isArray(items))
			{
				if(items.length <= 0)
				{
					this.stream.disableLoadOnScroll();
				}
				else
				{
					items.forEach((itemData) => {
						const item = this.stream.createItem(itemData);
						if(item)
						{
							this.stream.addItem(item);
						}
					});

					this.stream.renderItems();
				}
			}
			else
			{
				this.stream.disableLoadOnScroll();
			}
		}).catch(() => {
			this.stopProgress();
			this.stream.disableLoadOnScroll();
		});
	}

	handleItemPinClick(event: BaseEvent)
	{
		const item = event.getData().item;
		if(item instanceof Timeline.Item)
		{
			Ajax.runAction('rpa.timeline.updateIsFixed', {
				analyticsLabel: 'rpaTimelinePinClick',
				data: {
					id: item.getId(),
					isFixed: item.isFixed ? 'y' : 'n',
					eventId: this.registerRandomEventId(),
				}
			});
		}
	}

	registerRandomEventId(): string
	{
		const eventId = Text.getRandom();
		this.eventIds.add(eventId);
		return eventId;
	}
}

namespace.ItemDetailComponent = ItemDetailComponent;