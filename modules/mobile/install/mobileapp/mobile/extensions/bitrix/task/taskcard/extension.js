/* eslint-disable */
include('InAppNotifier');

(() => {
	const pathToExtension = '/bitrix/mobileapp/mobile/extensions/bitrix/task/taskcard/';
	const apiVersion = Application.getApiVersion();
	const platform = Application.getPlatform();

	class Request
	{
		constructor(namespace = 'tasks.task.')
		{
			this.restNamespace = namespace;
		}

		call(method, params)
		{
			this.currentAnswer = null;
			this.abortCurrentRequest();
			return new Promise((resolve, reject) =>
			{
				BX.rest.callMethod(this.restNamespace + method, params || {}, (response) => {
					this.currentAnswer = response;

					if (response.error())
					{
						reject(response);
					}
					else
					{
						resolve(response.answer);
					}
				}, this.onRequestCreate.bind(this));
			});
		}


		onRequestCreate(ajax)
		{
			this.currentAjaxObject = ajax;
		}

		abortCurrentRequest()
		{
			if (this.currentAjaxObject != null)
			{
				this.currentAjaxObject.abort();
			}
		}
	}

	class Options
	{
		constructor()
		{
			this.storageName = 'tasks.task.view.options';
			this.defaultOptions = {
				swipeShowHelper: {
					value: 0,
					limit: 1,
				},
			};
		}

		get()
		{
			return Application.storage.getObject(this.storageName, this.defaultOptions);
		}

		set(options)
		{
			Application.storage.setObject(this.storageName, options);
		}

		update(optionName, optionValue)
		{
			Application.storage.updateObject(this.storageName, {[optionName]: optionValue});
		}
	}

	class TaskCardHandler
	{
		constructor(taskCard)
		{
			this.taskCard = taskCard;
		}

		closeForm()
		{
			this.taskCard.back();
		}

		setTaskInfo(taskInfo)
		{
			this.taskCard.setTaskInfo(taskInfo);
		}
	}

	class TaskCard
	{
		static get selectFields()
		{
			return [
				'ID',
				'TITLE',
				'STATUS',
				'CREATED_BY',
				'ACTIVITY_DATE',
				'RESPONSIBLE_ID',
				'ACCOMPLICES',
				'AUDITORS',
				'DEADLINE',
				'COMMENTS_COUNT',
				'NEW_COMMENTS_COUNT',
				'FAVORITE',
				'NOT_VIEWED',
				'CHECKLIST',
				'GROUP_ID',
				'ALLOW_CHANGE_DEADLINE',
				'IS_MUTED',
				'IS_PINNED',
			];
		}

		static getRemoveSheetItems()
		{
			return [
				{
					id: 'yes',
					title: BX.message('TASKS_TASK_DETAIL_CONFIRM_REMOVE_YES'),
					code: 'answer',
				},
				{
					id: 'no',
					title: BX.message('TASKS_TASK_DETAIL_CONFIRM_REMOVE_NO'),
					code: 'answer',
				},
			];
		}

		static getFormattedName(user = {})
		{
			let name = `${user.NAME ? user.NAME : ''} ${user.LAST_NAME ? user.LAST_NAME : ''}`;

			if (name.trim() === '')
			{
				name = user.EMAIL;
			}

			return name;
		}

		static getGuid()
		{
			function s4()
			{
				return Math.floor((1 + Math.random()) * 0x10000).toString(16).substring(1);
			}

			return `${s4()}${s4()}-${s4()}-${s4()}-${s4()}-${s4()}${s4()}${s4()}`;
		}

		constructor(taskcard, userId)
		{
			this.init(taskcard, userId);
			this.setListeners();
		}

		init(taskcard, userId)
		{
			console.log('TaskCard init');

			this.userId = userId || parseInt(BX.componentParameters.get('USER_ID', 0), 10);
			this.taskId = BX.componentParameters.get('TASK_ID', 0);
			this.guid = BX.componentParameters.get('GUID', '');

			this.currentUser = result.settings.userInfo;
			this.deadlines = result.deadlines;

			this.task = new Task(this.currentUser);
			this.rest = new Request();
			this.options = new Options();
			this.taskCardHandler = null;
			this.taskPopupMenu = null;
		}

		setListeners()
		{
			BX.addCustomEvent('onMobileGridFormDataChange', eventData => this.onMobileGridFormDataChange(eventData));
			BX.addCustomEvent('onTaskDetailOptionsButtonClick', eventData => this.onTaskDetailOptionsButtonClick(eventData));
			BX.addCustomEvent('task.view.onCommentsRead', eventData => this.onCommentsRead(eventData));
			BX.addCustomEvent('onItemAction', eventData => this.onItemAction(eventData.action));
			BX.addCustomEvent('onItemChecked', eventData => this.onItemChecked(eventData.item.checked));

			const handlers = {
				task_update: this.onPullUpdate,
				task_remove: this.onPullDelete,
				comment_add: this.onPullComment,
			};

			BX.addCustomEvent('onPullEvent-tasks', (command, params) => {
				if (handlers[command])
				{
					handlers[command].apply(this, [params]);
				}
			});
		}

		onMobileGridFormDataChange(eventData)
		{
			console.log('onMobileGridFormDataChange', eventData);

			const {nodeName, nodeValue, dateValue, responsibleIcon, formId} = eventData;

			if (formId !== `MOBILE_TASK_VIEW_${this.guid.replace(/-/g, '')}`)
			{
				return;
			}

			switch (nodeName)
			{
				case 'data[DEADLINE]':
					if (dateValue || (!dateValue && nodeValue === ''))
					{
						this.updateTask({
							deadline: dateValue ? Date.parse(dateValue) : null,
							activityDate: Date.now(),
						});
					}
					break;

				case 'data[SE_RESPONSIBLE][0][ID]':
					if (typeof responsibleIcon !== 'undefined' && nodeValue)
					{
						let formattedIcon = 'images/avatar.png';
						if (responsibleIcon !== '')
						{
							formattedIcon = responsibleIcon.replace(/^url\(["']?/, '').replace(/["']?\)$/, '');
						}

						this.updateTask({
							responsible: {
								id: nodeValue,
								icon: formattedIcon,
							},
							activityDate: Date.now(),
						});

						(new Request('user.'))
							.call('search', {
								IMAGE_RESIZE: 'small',
								FILTER: {
									ID: nodeValue,
								},
							})
							.then((response) => {
								const user = response.result[0];
								this.updateTask({
									responsible: {
										id: user.ID,
										name: TaskCard.getFormattedName(user),
										icon: encodeURI(user.PERSONAL_PHOTO),
										link: '',
									},
								});
							});
					}
					break;

				default:
					break;
			}
		}

		onTaskDetailOptionsButtonClick(eventData)
		{
			if (this.task.id !== eventData.taskId || this.guid !== eventData.guid)
			{
				return;
			}

			this.taskPopupMenu.show();
		}

		onCommentsRead(eventData)
		{
			if (this.task.id === eventData.taskId)
			{
				this.updateTask({newCommentsCount: 0});
			}
		}

		onPullComment(data)
		{
			console.log('tasks.view.native::onPullComment');

			const [entityType, entityId] = data.entityXmlId.split('_');
			const returnConditions = {
				wrongEntityType: entityType !== 'TASK',
				wrongEntityId: entityId !== this.task.id,
				myComment: Number(data.ownerId) === Number(this.currentUser.id),
			};
			let shouldReturn = false;

			Object.keys(returnConditions).forEach((condition) => {
				if (!shouldReturn && returnConditions[condition])
				{
					console.log(`tasks.view.native::onPullComment.${condition}`);
					shouldReturn = true;
				}
			});

			if (shouldReturn)
			{
				return;
			}

			this.updateTask({newCommentsCount: this.task.newCommentsCount + 1});
		}

		onPullUpdate(data)
		{
			console.log('tasks.view.native::onPullUpdate', data);
			const taskId = data.TASK_ID.toString();

			if (taskId !== this.task.id)
			{
				return;
			}

			this.rest.call('get', {
				taskId,
				select: TaskCard.selectFields,
				params: {
					GET_TASK_LIMIT_EXCEEDED: true,
				},
			}).then(
				(response) => {
					const {task} = response.result;

					this.task.setData(task);
					this.taskLimitExceeded = task.taskLimitExceeded;

					this.updateTask();
				},
				response => console.log('tasks.view.native::onPullUpdate.get.error', response)
			);
		}

		onPullDelete(data)
		{
			console.log('tasks.view.native::onPullDelete', data);
			const taskId = data.TASK_ID.toString();

			if (taskId !== this.task.id)
			{
				return;
			}

			InAppNotifier.showNotification({
				title: BX.message('TASKS_TASK_DETAIL_TASK_WAS_REMOVED_IN_ANOTHER_PLACE'),
				backgroundColor: '#333333',
			});
			if (this.taskCardHandler)
			{
				this.taskCardHandler.closeForm();
			}
		}

		updateTask(fields = {})
		{
			BX.onViewLoaded(() => {
				const has = Object.prototype.hasOwnProperty;

				Object.keys(fields).forEach((key) => {
					if (has.call(this.task, key) || has.call(Object.getPrototypeOf(this.task), key))
					{
						this.task[key] = fields[key];
					}
				});

				this.updateTaskCardInfo();
			});
		}

		updateTaskCardInfo()
		{
			if (this.taskCardHandler)
			{
				this.taskCardHandler.setTaskInfo(this.getTaskInfo());
			}
		}

		getTaskInfo()
		{
			let taskInfo = this.task.getTaskInfo();

			taskInfo = this.handleItemActions(taskInfo);

			return taskInfo;
		}

		handleItemActions(taskInfo)
		{
			const notAllowedActions = ['pin', 'unpin', 'read'];
			let {actions} = taskInfo;

			actions = actions.filter(action => !notAllowedActions.includes(action.identifier));

			if (platform === 'ios')
			{
				const leftSwipeActions = actions.filter(action => action.position === 'left');

				if (actions.length > 4 + leftSwipeActions.length)
				{
					const swipeActions = leftSwipeActions.concat([Task.actions.more]);
					const popupActions = [];

					actions.filter(action => !leftSwipeActions.includes(action)).forEach((action) => {
						if (swipeActions.length < 4 + leftSwipeActions.length)
						{
							swipeActions.push(action);
						}
						else
						{
							popupActions.push({
								id: action.identifier,
								title: action.title,
								iconUrl: Task.popupImageUrls[action.identifier],
								textColor: action.textColor,
								sectionCode: 'default',
							});
						}
					});

					popupActions.push(Task.actions.cancel);

					actions = swipeActions;
					taskInfo.params.popupActions = popupActions;
				}
			}
			else
			{
				actions = actions.map((action) => {
					action.iconUrl = Task.popupImageUrls[action.identifier];
					return action;
				});

				taskInfo.menuMode = 'dialog';
			}

			taskInfo.actions = actions;

			return taskInfo;
		}

		handleSwipeActionsShow(taskInfo)
		{
			taskInfo.showSwipeActions = false;

			const {swipeShowHelper} = this.options.get();
			if (swipeShowHelper.value < swipeShowHelper.limit)
			{
				taskInfo.showSwipeActions = true;
				swipeShowHelper.value += 1;

				const name = (obj => Object.keys(obj)[0]);
				this.options.update(name({swipeShowHelper}), swipeShowHelper);
			}

			return taskInfo;
		}

		onItemChecked(isChecked)
		{
			if (isChecked)
			{
				this.complete();
			}
			else
			{
				this.renew();
			}
		}

		onItemAction(action)
		{
			switch (action.identifier)
			{
				case 'changeDeadline':
					this.onChangeDeadlineAction();
					break;

				case 'changeResponsible':
					this.onChangeResponsibleAction();
					break;

				case 'start':
					this.onStartAction();
					break;

				case 'pause':
					this.onPauseAction();
					break;

				case 'mute':
					this.onMuteAction();
					break;

				case 'unmute':
					this.onUnmuteAction();
					break;

				case 'unfollow':
					this.onUnfollowAction();
					break;

				case 'remove':
					this.onRemoveAction();
					break;

				case 'more':
					this.onMoreAction();
					return;

				default:
					break;
			}
		}

		// actions
		onChangeDeadlineAction()
		{
			const pickerParams = {
				title: BX.message('TASKS_TASK_DETAIL_DEADLINE_DATE_PICKER'),
				type: 'datetime',
				value: this.task.deadline,
			};

			if (apiVersion >= 34)
			{
				pickerParams.items = [];

				Object.keys(Task.deadlines).forEach((key) => {
					pickerParams.items.push({
						name: Task.deadlines[key].name,
						value: this.deadlines[key] * 1000,
					});
				});
			}

			dialogs.showDatePicker(
				pickerParams,
				(eventName, newTs) => {
					if (!newTs)
					{
						return;
					}
					this.updateTask({deadline: newTs, activityDate: Date.now()});

					console.log('tasks.view.native::onItemAction::changeDeadline');
					BX.postWebEvent('tasks.view.native::onItemAction', {
						taskId: this.task.id,
						taskGuid: this.guid,
						name: 'deadline',
						values: {deadline: newTs},
					});
				}
			);
		}

		onChangeResponsibleAction()
		{
			UserList.openPicker({
				title: BX.message('TASKS_TASK_DETAIL_TITLE_RESPONSIBLE'),
				allowMultipleSelection: false,
				listOptions: {
					users: {
						hideUnnamed: true,
						useRecentSelected: true,
					},
				},
			}).then((users) => {
				if (users.length > 0)
				{
					const user = users[0];

					this.updateTask({
						responsible: {
							id: user.params.id,
							name: user.title,
							icon: user.imageUrl,
							link: '',
						},
						activityDate: Date.now(),
					});

					console.log('tasks.view.native::onItemAction::changeResponsible');
					BX.postWebEvent('tasks.view.native::onItemAction', {
						taskId: this.task.id,
						taskGuid: this.guid,
						name: 'responsible',
						values: {
							user: this.task.responsible,
						},
					});
				}
			});
		}

		onMuteAction()
		{
			this.updateTask({isMuted: true});
			this.task.mute();
		}

		onUnmuteAction()
		{
			this.updateTask({isMuted: false});
			this.task.unmute();
		}

		onMoreAction()
		{
			const taskItemData = this.getTaskInfo();
			const actionsPopup = dialogs.createPopupMenu();
			actionsPopup.setData(taskItemData.params.popupActions, [{id: 'default'}], (eventName, item) => {
				if (eventName === 'onItemSelected')
				{
					this.onActionsPopupItemSelected(item);
				}
			});
			actionsPopup.setPosition('center');
			actionsPopup.show();
		}

		onActionsPopupItemSelected(item)
		{
			switch (item.id)
			{
				case 'mute':
					this.onMuteAction();
					break;

				case 'unmute':
					this.onUnmuteAction();
					break;

				case 'unfollow':
					this.onUnfollowAction();
					break;

				case 'remove':
					this.onRemoveAction();
					break;

				case 'cancel':
					return;

				default:
					break;
			}

			this.updateTask({});
		}

		openNewTaskPage()
		{
			const taskId = 0;
			const title = BX.message('TASKS_TASK_DETAIL_TASK_NEW_TASK_TITLE');
			const guid = TaskCard.getGuid();
			let url = result.settings.taskPaths.add
				.replace(/#taskId#/gi, 0)
				.replace(/#userId#/gi, this.currentUser.id)
				.replace(/#salt#/gi, new Date().getTime());
			url = `${url}&GUID=${guid}`;

			this.openTaskPage(url, guid, title, taskId);
		}

		openNewSubTaskPage()
		{
			const taskId = 0;
			const title = BX.message('TASKS_TASK_DETAIL_TASK_NEW_SUBTASK_TITLE');
			const guid = TaskCard.getGuid();
			let url = result.settings.taskPaths.addSub
				.replace(/#taskId#/gi, 0)
				.replace(/#parentTaskId#/gi, this.task.id)
				.replace(/#userId#/gi, this.currentUser.id)
				.replace(/#salt#/gi, new Date().getTime());
			url = `${url}&GUID=${guid}`;

			this.openTaskPage(url, guid, title, taskId);
		}

		openEditTaskPage()
		{
			const taskId = this.taskId;
			const title = BX.message('TASKS_TASK_DETAIL_TASK_EDIT_TITLE');
			const guid = TaskCard.getGuid();
			let url = result.settings.taskPaths.update
				.replace(/#taskId#/gi, this.task.id)
				.replace(/#userId#/gi, this.currentUser.id)
				.replace(/#salt#/gi, new Date().getTime());
			url = `${url}&GUID=${guid}`;

			this.openTaskPage(url, guid, title, taskId);
		}

		openTaskPage(url, guid, title, taskId)
		{
			if (Application.getApiVersion() >= 33)
			{
				PageManager.openComponent('JSStackComponent', {
					componentCode: 'tasks.edit',
					scriptPath: availableComponents['tasks.view'].publicUrl,
					rootWidget: {
						name: 'web',
						settings: {
							objectName: 'taskcard',
							modal: true,
							cache: false,
							page: {
								url,
								titleParams: {text: title},
							},
						},
					},
					params: {
						MODE: 'edit',
						COMPONENT_CODE: 'tasks.view',
						USER_ID: this.userId || 0,
						TASK_ID: taskId,
						GUID: guid,
					},
				});
			}
			else
			{
				PageManager.openPage({url, cache: false, modal: true});
			}
		}

		addToFavorite()
		{
			const taskId = this.task.id;

			this.task.rawAccess['favorite.add'] = false;
			this.task.rawAccess['favorite.delete'] = true;

			BX.postWebEvent('tasks.view.native::onTaskUpdate', {taskId, favorite: true}, true);
			this.redrawTaskPopupMenu();

			this.task.favorite().add().then(() => {}, () => {
				this.task.rawAccess['favorite.add'] = true;
				this.task.rawAccess['favorite.delete'] = false;

				BX.postWebEvent('tasks.view.native::onTaskUpdate', {taskId, favorite: false}, true);
				this.redrawTaskPopupMenu();
			});
		}

		removeFromFavorite()
		{
			const taskId = this.task.id;

			this.task.rawAccess['favorite.add'] = true;
			this.task.rawAccess['favorite.delete'] = false;

			BX.postWebEvent('tasks.view.native::onTaskUpdate', {taskId, favorite: false}, true);
			this.redrawTaskPopupMenu();

			this.task.favorite().remove().then(() => {}, () => {
				this.task.rawAccess['favorite.add'] = false;
				this.task.rawAccess['favorite.delete'] = true;

				BX.postWebEvent('tasks.view.native::onTaskUpdate', {taskId, favorite: true}, true);
				this.redrawTaskPopupMenu();
			});
		}

		onStartAction()
		{
			this.task.rawAccess.start = false;
			this.task.rawAccess.pause = true;

			this.updateTask({
				status: Task.statusList.inprogress,
				activityDate: Date.now(),
			});
			this.redrawTaskPopupMenu();

			this.task.start().then(() => {
				this.updateTask();
				this.redrawTaskPopupMenu();
			});
		}

		onPauseAction()
		{
			this.task.rawAccess.start = true;
			this.task.rawAccess.pause = false;

			this.updateTask({
				status: Task.statusList.pending,
				activityDate: Date.now(),
			});
			this.redrawTaskPopupMenu();

			this.task.pause().then(() => {
				this.updateTask();
				this.redrawTaskPopupMenu();
			});
		}

		complete()
		{
			this.updateTask({
				status: Task.statusList.completed,
				activityDate: Date.now(),
			});
			this.redrawTaskPopupMenu();

			this.task.complete().then(() => {
				this.updateTask();
				this.redrawTaskPopupMenu();
			});
		}

		renew()
		{
			this.updateTask({
				status: Task.statusList.pending,
				activityDate: Date.now(),
			});
			this.redrawTaskPopupMenu();

			this.task.renew().then(() => {
				this.updateTask();
				this.redrawTaskPopupMenu();
			});
		}

		approve()
		{
			this.updateTask({
				status: Task.statusList.completed,
				activityDate: Date.now(),
			});
			this.redrawTaskPopupMenu();

			this.task.approve().then(() => {
				this.updateTask();
				this.redrawTaskPopupMenu();
			});
		}

		disapprove()
		{
			this.updateTask({
				status: Task.statusList.pending,
				activityDate: Date.now(),
			});
			this.redrawTaskPopupMenu();

			this.task.disapprove().then(() => {
				this.updateTask();
				this.redrawTaskPopupMenu();
			});
		}

		delegate()
		{
			UserList.openPicker({
				allowMultipleSelection: false,
				listOptions: {
					users: {
						hideUnnamed: true,
						useRecentSelected: true,
					},
				},
			}).then((users) => {
				if (users.length > 0)
				{
					const user = users[0];

					this.updateTask({
						responsible: {
							id: user.params.id,
							name: user.title,
							icon: user.imageUrl,
							link: '',
						},
						activityDate: Date.now(),
					});
					this.task.delegate().then(() => BX.postWebEvent('tasks.view.native::onTaskUpdate', {
						taskId: this.task.id,
						responsible: true,
					}, true));
				}
			});
		}

		onRemoveAction()
		{
			dialogs.showActionSheet({
				title: BX.message('TASKS_TASK_DETAIL_CONFIRM_REMOVE'),
				callback: (item) => {
					if (item.id === 'yes')
					{
						InAppNotifier.showNotification({
							title: BX.message('TASKS_TASK_DETAIL_TASK_WAS_REMOVED'),
							backgroundColor: '#333333',
						});

						this.rest.call('delete', {taskId: this.task.id});
						if (this.taskCardHandler)
						{
							this.taskCardHandler.closeForm();
						}
					}
				},
				items: TaskCard.getRemoveSheetItems(),
			});
		}

		onUnfollowAction()
		{
			const currentUserId = Number(this.currentUser.id);

			this.task.auditors = this.task.auditors.filter(item => item !== currentUserId);
			if (!this.task.isMember(currentUserId))
			{
				this.task.stopWatch();
				if (this.taskCardHandler)
				{
					this.taskCardHandler.closeForm();
				}
			}
			else
			{
				this.updateTask({activityDate: Date.now()});
			}
		}

		// task popup menu
		redrawTaskPopupMenu()
		{
			this.taskPopupMenu.setData(this.popupMenuItems, [{id: '0'}], (eventName, item) => {
				if (eventName === 'onItemSelected')
				{
					const itemsMap = this.popupMenuItemsMap;
					if (Object.keys(itemsMap).includes(item.id))
					{
						itemsMap[item.id].action.apply(this);
					}
				}
			});
		}

		get popupMenuItems()
		{
			const items = [];
			const sectionCode = '0';
			const itemsMap = this.popupMenuItemsMap;

			Object.keys(itemsMap).forEach((item) => {
				if (this.can(item) || ['addTask', 'addSubTask'].includes(item))
				{
					items.push({
						sectionCode,
						id: item,
						title: itemsMap[item].title,
						iconUrl: itemsMap[item].iconUrl,
						disable: itemsMap[item].disable || false,
					});
				}
			});

			return items;
		}

		can(right)
		{
			const has = Object.prototype.hasOwnProperty;
			return has.call(this.task.can, right) && Boolean(this.task.can[right]);
		}

		get popupMenuItemsMap()
		{
			const urlPrefix = `${pathToExtension}/images/mobile-taskcard-popup-`;

			return {
				addTask: {
					title: BX.message('TASKS_TASK_DETAIL_TASK_ADD'),
					iconUrl: `${urlPrefix}add.png`,
					action: this.openNewTaskPage,
				},
				addSubTask: {
					title: BX.message('TASKS_TASK_DETAIL_TASK_ADD_SUBTASK'),
					iconUrl: `${urlPrefix}add.png`,
					action: this.openNewSubTaskPage,
				},
				'favorite.add': {
					title: BX.message('TASKS_TASK_DETAIL_BTN_ADD_FAVORITE_TASK'),
					iconUrl: `${urlPrefix}add-favorite.png`,
					action: this.addToFavorite,
				},
				'favorite.remove': {
					title: BX.message('TASKS_TASK_DETAIL_BTN_DELETE_FAVORITE_TASK'),
					iconUrl: `${urlPrefix}delete-favorite.png`,
					action: this.removeFromFavorite,
				},
				start: {
					title: BX.message('TASKS_TASK_DETAIL_BTN_START_TASK'),
					iconUrl: `${urlPrefix}start.png`,
					action: this.onStartAction,
				},
				complete: {
					title: BX.message('TASKS_TASK_DETAIL_BTN_CLOSE_TASK'),
					iconUrl: `${urlPrefix}finish.png`,
					action: this.complete,
				},
				renew: {
					title: BX.message('TASKS_TASK_DETAIL_BTN_RENEW_TASK'),
					iconUrl: `${urlPrefix}renew.png`,
					action: this.renew,
				},
				pause: {
					title: BX.message('TASKS_TASK_DETAIL_BTN_PAUSE_TASK'),
					iconUrl: `${urlPrefix}pause.png`,
					action: this.onPauseAction,
				},
				disapprove: {
					title: BX.message('TASKS_TASK_DETAIL_BTN_REDO_TASK'),
					iconUrl: `${urlPrefix}renew.png`,
					action: this.disapprove,
				},
				approve: {
					title: BX.message('TASKS_TASK_DETAIL_BTN_APPROVE_TASK'),
					iconUrl: `${urlPrefix}finish.png`,
					action: this.approve,
				},
				delegate: {
					title: BX.message('TASKS_TASK_DETAIL_BTN_DELEGATE_TASK'),
					iconUrl: `${urlPrefix}delegate.png`,
					action: this.delegate,
					disable: this.taskLimitExceeded,
				},
				update: {
					title: BX.message('TASKS_TASK_DETAIL_BTN_EDIT'),
					iconUrl: `${urlPrefix}edit.png`,
					action: this.openEditTaskPage,
				},
				remove: {
					title: BX.message('TASKS_TASK_DETAIL_BTN_REMOVE'),
					iconUrl: `${urlPrefix}delete.png`,
					action: this.onRemoveAction,
				},
			};
		}
	}

	/**
	 * @class TaskCardView
	 */
	class TaskCardView extends TaskCard
	{
		init(taskcard, userId)
		{
			super.init(taskcard, userId);

			this.mode = 'view';

			this.rest.call('get', {
				taskId: this.taskId,
				select: TaskCard.selectFields,
				params: {
					GET_TASK_LIMIT_EXCEEDED: true,
				},
			}).then((response) => {
				const {task} = response.result;
				task.newCommentsCount = 0;

				this.task.setData(task);
				this.taskLimitExceeded = task.taskLimitExceeded;

				BX.onViewLoaded(() => {
					this.taskCardHandler = new TaskCardHandler(taskcard);
					this.checklistController = new ChecklistController(this.taskId, this.userId, this.guid, this.mode);

					const taskInfo = this.getTaskInfo();
					this.taskCardHandler.setTaskInfo(this.handleSwipeActionsShow(taskInfo));
					this.taskCardHandler.setTaskInfo(this.handleSwipeActionsShow(taskInfo));

					this.taskPopupMenu = dialogs.createPopupMenu();
					this.taskPopupMenu.setPosition('center');
					this.redrawTaskPopupMenu();

					taskcard.setRightButtons([{
						type: 'more',
						callback: () => {
							this.taskPopupMenu.show();
						},
					}]);
				});
			});
		}
	}

	/**
	 * @class TaskCardEdit
	 */
	class TaskCardEdit extends TaskCard
	{
		init(taskcard, userId)
		{
			super.init(taskcard, userId);

			this.mode = 'edit';

			BX.onViewLoaded(() => {
				this.checklistController = new ChecklistController(this.taskId, this.userId, this.guid, this.mode);

				this.taskPopupMenu = dialogs.createPopupMenu();
				this.taskPopupMenu.setPosition('center');
				this.redrawTaskPopupMenu();

				taskcard.setLeftButtons([{
					name: BX.message('TASKS_TASK_DETAIL_TASK_EDIT_CANCEL'),
					callback: () => {
						taskcard.close();
					},
				}]);
			});
		}
	}

	jnexport([TaskCardView, 'TaskCardView'], [TaskCardEdit, 'TaskCardEdit']);
})();