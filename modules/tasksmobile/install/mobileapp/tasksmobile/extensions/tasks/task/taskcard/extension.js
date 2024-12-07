/* eslint-disable */
include('InAppNotifier');

(() => {
	const { TaskStatus } = jn.require('tasks/enum');
	const pathToExtension = '/bitrix/mobileapp/tasksmobile/extensions/tasks/task/taskcard/';
	const platform = Application.getPlatform();

	class Request
	{
		constructor(namespace = 'tasks.task.')
		{
			this.restNamespace = namespace;
		}

		call(methodName, params)
		{
			const method = this.restNamespace + methodName;

			this.currentAnswer = null;
			this.abortCurrentRequest();

			return new Promise((resolve, reject) => {
				console.log({method, params});
				BX.rest.callMethod(method, params || {}, (response) => {
					this.currentAnswer = response;
					if (response.error())
					{
						console.log(response.error());
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
				'FAVORITE',
				'NOT_VIEWED',
				'CHECKLIST',
				'GROUP_ID',
				'ALLOW_CHANGE_DEADLINE',
				'IS_MUTED',
				'IS_PINNED',
				'COUNTERS',
			];
		}

		static get queryParams()
		{
			return {
				GET_TASK_LIMIT_EXCEEDED: true,
				WITH_RESULT_INFO: 'Y',
				WITH_TIMER_INFO: 'Y',
			};
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

		static getItemDataFromUser(user)
		{
			return {
				id: user.id,
				name: user.title,
				icon: (user.defaultImage ? '' : user.imageUrl),
				link: '',
			};
		}

		static getItemDataFromGroup(group)
		{
			return {
				id: group.id,
				name: group.title,
				image: (group.defaultImage ? '' : group.imageUrl),
			};
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
			this.taskObjectData = BX.componentParameters.get('TASK_OBJECT', {});

			this.currentUser = result.settings.userInfo;
			this.deadlines = result.deadlines;

			this.task = new Task(this.currentUser);
			this.task.importProperties(this.taskObjectData);

			this.rest = new Request();
			this.options = new Options();
			this.taskCardHandler = null;
			this.taskPopupMenu = null;
		}

		setListeners()
		{
			BX.addCustomEvent('task.view.onPageLoaded', eventData => this.onPageLoaded(eventData));
			BX.addCustomEvent('onMobileGridFormDataChange', eventData => this.onMobileGridFormDataChange(eventData));
			BX.addCustomEvent('onTaskDetailOptionsButtonClick', eventData => this.onTaskDetailOptionsButtonClick(eventData));
			BX.addCustomEvent('task.view.onCommentsRead', eventData => this.onCommentsRead(eventData));
			BX.addCustomEvent('task.view.onCommentAction', eventData => this.onCommentAction(eventData));
			BX.addCustomEvent('onItemAction', eventData => this.onItemAction(eventData.action));
			BX.addCustomEvent('onItemChecked', eventData => this.onItemChecked(eventData.item.checked));

			const handlers = {
				task_update: this.onPullUpdate,
				task_remove: this.onPullDelete,
				comment_add: this.onPullComment,
				task_result_create: this.onPullTaskResultCreate,
				task_result_delete: this.onPullTaskResultDelete,
			};

			BX.addCustomEvent('onPullEvent-tasks', (command, params) => {
				if (handlers[command])
				{
					handlers[command].apply(this, [params]);
				}
			});
		}

		onPageLoaded()
		{
			this.rest.call('limit.isExceeded').then((response) => {
				console.log('limit.isExceeded', response.result);
				this.taskLimitExceeded = response.result || false;
				this.redrawTaskPopupMenu();
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
				this.task.pseudoRead();
				this.updateTaskCardInfo();
			}
		}

		onCommentAction(eventData)
		{
			console.log('onCommentAction', eventData);

			const {taskId, userId, action, deadline} = eventData;

			if (taskId !== this.task.id || userId !== this.userId)
			{
				return;
			}

			switch (action)
			{
				case 'deadlineChange':
					this.onChangeDeadlineAction();
					break;

				case 'taskApprove':
					this.onApproveAction();
					break;

				case 'taskDisapprove':
					this.onDisapproveAction();
					break;

				case 'taskComplete':
					this.onCompleteAction();
					break;

				default:
					break;
			}
		}

		onPullTaskResultCreate(data)
		{
			this.updateTaskResultData(data);
		}

		onPullTaskResultDelete(data)
		{
			this.updateTaskResultData(data);
		}

		updateTaskResultData(data)
		{
			if (data.taskId.toString() === this.task.id)
			{
				this.task.updateData({
					taskRequireResult: data.taskRequireResult,
					taskHasOpenResult: data.taskHasOpenResult,
					taskHasResult: data.taskHasResult,
				})
			}
		}

		onPullComment(data)
		{
			console.log('tasks.view.native::onPullComment', data);

			const [entityType, entityId] = data.entityXmlId.split('_');
			if (
				entityType !== 'TASK'
				|| entityId !== this.task.id
				|| Number(data.ownerId) === Number(this.currentUser.id)
			)
			{
				console.log('tasks.view.native::onPullComment -> return');
				return;
			}

			this.rest.call('get', {
				taskId: entityId,
				select: TaskCard.selectFields,
				params: TaskCard.queryParams,
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

		onPullUpdate(data)
		{
			console.log('tasks.view.native::onPullUpdate', data);
			const taskId = data.TASK_ID.toString();

			if (taskId !== this.task.id || data.params.updateCommentExists !== false)
			{
				console.log('tasks.view.native::onPullUpdate -> return');
				return;
			}

			this.rest.call('get', {
				taskId,
				select: TaskCard.selectFields,
				params: TaskCard.queryParams,
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
				backgroundColor: AppTheme.colors.base1,
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
			delete taskInfo.project;

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
				this.onCompleteAction();
			}
			else
			{
				this.onRenewAction();
			}
		}

		onItemAction(action)
		{
			switch (action.identifier)
			{
				case 'ping':
					this.onPingAction();
					break;

				case 'changeDeadline':
					this.onChangeDeadlineAction();
					break;

				case 'approve':
					this.onApproveAction();
					break;

				case 'disapprove':
					this.onDisapproveAction();
					break;

				case 'startTimer':
					this.onStartTimerAction();
					break;

				case 'pauseTimer':
					this.onPauseTimerAction();
					break;

				case 'start':
					this.onStartAction();
					break;

				case 'pause':
					this.onPauseAction();
					break;

				case 'renew':
					this.onRenewAction();
					break;

				case 'changeResponsible':
					this.onChangeResponsibleAction();
					break;

				case 'delegate':
					this.onDelegateAction();
					break;

				case 'changeGroup':
					this.onChangeGroupAction();
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

				case 'share':
					this.onShareAction();
					break;

				default:
					break;
			}
		}

		// actions
		onPingAction()
		{
			this.updateTask({activityDate: Date.now()});
			this.task.ping();

			Notify.showIndicatorSuccess({
				text: BX.message('TASKS_TASK_DETAIL_TASK_PING_NOTIFICATION'),
				hideAfter: 1500,
			});
		}

		onChangeDeadlineAction()
		{
			const pickerParams = {
				title: BX.message('TASKS_TASK_DETAIL_DEADLINE_DATE_PICKER'),
				type: 'datetime',
				value: this.task.deadline,
				items: [],
			};
			Object.keys(Task.deadlines).forEach((key) => {
				pickerParams.items.push({
					name: Task.deadlines[key].name,
					value: this.deadlines[key] * 1000,
				});
			});

			dialogs.showDatePicker(
				pickerParams,
				(eventName, newTs) => {
					if (!newTs)
					{
						return;
					}
					this.updateTask({deadline: newTs, activityDate: Date.now()});
					this.sendOnItemActionWebEvent('deadline', {deadline: newTs});
				}
			);
		}

		onChangeResponsibleAction()
		{
			(new RecipientSelector('TASKS_MEMBER_SELECTOR_EDIT_responsible', ['user']))
				.setSingleChoose(true)
				.setTitle(BX.message('TASKS_TASK_DETAIL_TITLE_RESPONSIBLE_MSGVER_1'))
				.setSelected({
					user: [{
						id: this.task.responsible.id,
						title: this.task.responsible.name,
						imageUrl: this.task.responsible.icon,
					}],
				})
				.open()
				.then((recipients) => {
					if (recipients.user && recipients.user.length > 0)
					{
						const user = recipients.user[0];

						if (Number(this.task.responsible.id) === Number(user.id))
						{
							return;
						}
						this.updateTask({
							responsible: TaskCard.getItemDataFromUser(user),
							activityDate: Date.now(),
						});
						this.sendOnItemActionWebEvent('responsible', {user: this.task.responsible});
					}
				})
			;
		}

		onDelegateAction()
		{
			(new RecipientSelector('TASKS_MEMBER_SELECTOR_EDIT_responsible', ['user']))
				.setSingleChoose(true)
				.setTitle(BX.message('TASKS_TASK_DETAIL_TITLE_RESPONSIBLE_MSGVER_1'))
				.setSelected({
					user: [{
						id: this.task.responsible.id,
						title: this.task.responsible.name,
						imageUrl: this.task.responsible.icon,
					}],
				})
				.open()
				.then((recipients) => {
					if (recipients.user && recipients.user.length > 0)
					{
						const user = recipients.user[0];

						if (Number(this.task.responsible.id) === Number(user.id))
						{
							return;
						}
						this.updateTask({
							responsible: TaskCard.getItemDataFromUser(user),
							activityDate: Date.now(),
						});
						this.task.delegate().then(() => BX.postWebEvent('tasks.view.native::onTaskUpdate', {
							taskId: this.task.id,
							responsible: true,
						}, true));
					}
				})
			;
		}

		onChangeGroupAction()
		{
			const selected = [];
			if (this.task.group.id > 0)
			{
				selected.push({
					id: this.task.group.id,
					title: this.task.group.name,
					imageUrl: this.task.group.image,
				});
			}

			(new RecipientSelector('TASKS_PROJECT', ['project']))
				.setSingleChoose(true)
				.setTitle(BX.message('TASKS_LIST_POPUP_PROJECT'))
				.setSelected({project: selected})
				.open()
				.then((recipients) => {
					if (recipients.project && recipients.project.length)
					{
						const group = recipients.project[0];

						if (Number(this.task.groupId) === Number(group.id))
						{
							return;
						}
						this.task.groupId = Number(group.id);
						this.task.group = TaskCard.getItemDataFromGroup(group);
						this.updateTask({});
						this.sendOnItemActionWebEvent('group', {group: this.task.group});
					}
				})
			;
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
				case 'ping':
					this.onPingAction();
					break;

				case 'changeDeadline':
					this.onChangeDeadlineAction();
					break;

				case 'approve':
					this.onApproveAction();
					break;

				case 'disapprove':
					this.onDisapproveAction();
					break;

				case 'startTimer':
					this.onStartTimerAction();
					break;

				case 'pauseTimer':
					this.onPauseTimerAction();
					break;

				case 'start':
					this.onStartAction();
					break;

				case 'pause':
					this.onPauseAction();
					break;

				case 'renew':
					this.onRenewAction();
					break;

				case 'changeResponsible':
					this.onChangeResponsibleAction();
					break;

				case 'delegate':
					this.onDelegateAction();
					break;

				case 'changeGroup':
					this.onChangeGroupAction();
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

				case 'share':
					this.onShareAction();
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
			PageManager.openComponent('JSStackComponent', {
				componentCode: 'tasks.edit',
				scriptPath: availableComponents['tasks:tasks.view'].publicUrl,
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

		onAddToFavoriteAction()
		{
			const taskId = this.task.id;

			this.task.updateActions({
				canAddToFavorite: false,
				canRemoveFromFavorite: true,
			});
			BX.postWebEvent('tasks.view.native::onTaskUpdate', {taskId, favorite: true}, true);
			this.redrawTaskPopupMenu();

			this.task.addToFavorite().then(() => {}, () => {
				BX.postWebEvent('tasks.view.native::onTaskUpdate', {taskId, favorite: false}, true);
				this.redrawTaskPopupMenu();
			});
		}

		onRemoveFromFavoriteAction()
		{
			const taskId = this.task.id;

			this.task.updateActions({
				canAddToFavorite: true,
				canRemoveFromFavorite: false,
			});
			BX.postWebEvent('tasks.view.native::onTaskUpdate', {taskId, favorite: false}, true);
			this.redrawTaskPopupMenu();

			this.task.removeFromFavorite().then(() => {}, () => {
				BX.postWebEvent('tasks.view.native::onTaskUpdate', {taskId, favorite: true}, true);
				this.redrawTaskPopupMenu();
			});
		}

		onApproveAction()
		{
			this.task.updateActions({
				canApprove: false,
				canDisapprove: false,
				canRenew: true,
				canComplete: false,
			});

			this.updateTask({
				status: TaskStatus.COMPLETED,
				activityDate: Date.now(),
			});
			this.redrawTaskPopupMenu();
			this.sendOnItemActionStatusWebEvent();

			this.task.approve().then(() => {
				this.updateTask();
				this.redrawTaskPopupMenu();
				this.sendOnItemActionStatusWebEvent();
			});
		}

		onDisapproveAction()
		{
			this.task.updateActions({
				canApprove: false,
				canDisapprove: false,
				canRenew: false,
				canComplete: false,
				canStart: true,
			});

			this.updateTask({
				status: TaskStatus.PENDING,
				activityDate: Date.now(),
			});
			this.redrawTaskPopupMenu();
			this.sendOnItemActionStatusWebEvent();

			this.task.disapprove().then(() => {
				this.updateTask();
				this.redrawTaskPopupMenu();
				this.sendOnItemActionStatusWebEvent();
			});
		}

		onStartTimerAction()
		{
			this.task.updateActions({
				canStartTimer: false,
				canPauseTimer: true,
				canStart: false,
				canPause: false,
				canRenew: false,
			});

			this.updateTask({ status: TaskStatus.IN_PROGRESS });
			this.redrawTaskPopupMenu();
			this.sendOnItemActionStatusWebEvent();

			this.task.startTimer().then(() => {
				this.updateTask();
				this.redrawTaskPopupMenu();
				this.sendOnItemActionStatusWebEvent();
			});
		}

		onPauseTimerAction()
		{
			this.task.updateActions({
				canStartTimer: true,
				canPauseTimer: false,
				canStart: false,
				canPause: false,
				canRenew: false,
			});

			this.updateTask({ status: TaskStatus.PENDING });
			this.redrawTaskPopupMenu();
			this.sendOnItemActionStatusWebEvent();

			this.task.pauseTimer().then(() => {
				this.updateTask();
				this.redrawTaskPopupMenu();
				this.sendOnItemActionStatusWebEvent();
			});
		}

		onStartAction()
		{
			this.task.updateActions({
				canStartTimer: false,
				canPauseTimer: false,
				canStart: false,
				canPause: true,
				canRenew: false,
			});

			this.updateTask({ status: TaskStatus.IN_PROGRESS });
			this.redrawTaskPopupMenu();
			this.sendOnItemActionStatusWebEvent();

			this.task.start().then(() => {
				this.updateTask();
				this.redrawTaskPopupMenu();
				this.sendOnItemActionStatusWebEvent();
			});
		}

		onPauseAction()
		{
			this.task.updateActions({
				canStartTimer: false,
				canPauseTimer: false,
				canStart: true,
				canPause: false,
				canRenew: false,
			});

			this.updateTask({ status: TaskStatus.PENDING });
			this.redrawTaskPopupMenu();
			this.sendOnItemActionStatusWebEvent();

			this.task.pause().then(() => {
				this.updateTask();
				this.redrawTaskPopupMenu();
				this.sendOnItemActionStatusWebEvent();
			});
		}

		onRenewAction()
		{
			this.task.updateActions({
				canStart: true,
				canPause: false,
				canRenew: false,
			});

			this.updateTask({
				status: TaskStatus.PENDING,
				activityDate: Date.now(),
			});
			this.redrawTaskPopupMenu();
			this.sendOnItemActionStatusWebEvent();

			this.task.renew().then(() => {
				this.updateTask();
				this.redrawTaskPopupMenu();
			});
			this.sendOnItemActionStatusWebEvent();
		}

		onCompleteAction()
		{
			if (!this.task.isResultRequired || this.task.isOpenResultExists)
			{
				this.updateTask({
					status: TaskStatus.COMPLETED,
					activityDate: Date.now(),
				});
				this.redrawTaskPopupMenu();
				this.sendOnItemActionStatusWebEvent();

				this.task.complete().then(() => {
					this.updateTask();
					this.redrawTaskPopupMenu();
					this.sendOnItemActionStatusWebEvent();
				});
			}
			else
			{
				this.task.complete().then(() => {}, () => this.updateTask());
			}
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
							backgroundColor: AppTheme.colors.base1,
						});

						void this.task.remove();
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
			delete this.task.auditors[this.currentUser.id.toString()];

			if (!this.task.isMember(this.currentUser.id))
			{
				void this.task.stopWatch();
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

		onShareAction()
		{
			dialogs.showSharingDialog({
				message: `${currentDomain}/company/personal/user/${this.currentUser.id}/tasks/task/view/${this.task.id}/`,
			});
		}

		sendOnItemActionStatusWebEvent()
		{
			this.sendOnItemActionWebEvent('status', {status: this.task.status});
		}

		sendOnItemActionWebEvent(name, values)
		{
			BX.postWebEvent('tasks.view.native::onItemAction', {
				name,
				values,
				taskId: this.task.id,
				taskGuid: this.guid,
			});
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
			return (has.call(this.task.actions, right) && Boolean(this.task.actions[right]));
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
					action: this.onAddToFavoriteAction,
				},
				'favorite.delete': {
					title: BX.message('TASKS_TASK_DETAIL_BTN_DELETE_FAVORITE_TASK'),
					iconUrl: `${urlPrefix}delete-favorite.png`,
					action: this.onRemoveFromFavoriteAction,
				},
				start: {
					title: BX.message('TASKS_TASK_DETAIL_BTN_START_TASK'),
					iconUrl: `${urlPrefix}start.png`,
					action: this.onStartAction,
				},
				complete: {
					title: BX.message('TASKS_TASK_DETAIL_BTN_CLOSE_TASK'),
					iconUrl: `${urlPrefix}finish.png`,
					action: this.onCompleteAction,
				},
				renew: {
					title: BX.message('TASKS_TASK_DETAIL_BTN_RENEW_TASK'),
					iconUrl: `${urlPrefix}renew.png`,
					action: this.onRenewAction,
				},
				pause: {
					title: BX.message('TASKS_TASK_DETAIL_BTN_PAUSE_TASK'),
					iconUrl: `${urlPrefix}pause.png`,
					action: this.onPauseAction,
				},
				disapprove: {
					title: BX.message('TASKS_TASK_DETAIL_BTN_REDO_TASK_MSGVER_1'),
					iconUrl: `${urlPrefix}renew.png`,
					action: this.onDisapproveAction,
				},
				approve: {
					title: BX.message('TASKS_TASK_DETAIL_BTN_APPROVE_TASK'),
					iconUrl: `${urlPrefix}finish.png`,
					action: this.onApproveAction,
				},
				delegate: {
					title: BX.message('TASKS_TASK_DETAIL_BTN_DELEGATE_TASK'),
					iconUrl: `${urlPrefix}delegate.png`,
					action: this.onDelegateAction,
					disable: this.taskLimitExceeded,
				},
				edit: {
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

			if (this.task.id === this.taskId)
			{
				this.task.pseudoRead();
				this.onInitSuccess();
				return;
			}

			this.rest.call('get', {
				taskId: this.taskId,
				select: TaskCard.selectFields,
				params: TaskCard.queryParams,
			}).then((response) => {
				const {task} = response.result;

				this.task.setData(task);
				this.task.pseudoRead();
				this.taskLimitExceeded = task.taskLimitExceeded;

				this.onInitSuccess();
			});
		}

		onInitSuccess()
		{
			BX.onViewLoaded(() => {
				this.taskCardHandler = new TaskCardHandler(taskcard);

				const taskInfo = this.getTaskInfo();
				delete taskInfo.project;

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
