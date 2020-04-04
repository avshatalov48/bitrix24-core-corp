/* eslint-disable */
include('InAppNotifier');

(() => {
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

	class TaskCardHandler
	{
		constructor(taskCard, task, getTaskInfo)
		{
			this.taskCard = taskCard;
			this.task = task;

			if (getTaskInfo)
			{
				this.taskInfo = this.task.getTaskInfo();
				this.taskCard.setTaskInfo(this.taskInfo);
			}
			else
			{
				this.taskInfo = this.taskCard.getTaskInfo();
			}
		}

		closeForm()
		{
			this.taskCard.back();
		}

		setIsChecked(isChecked)
		{
			const checked = (typeof isChecked === 'boolean' ? isChecked : this.task.isCompleted);
			this.setTaskInfo({checked});
		}

		setIsCheckable(isCheckable)
		{
			const canCheck = this.task.can.complete || this.task.can.approve || this.task.can.renew;
			const checkable = (typeof isCheckable === 'boolean' ? isCheckable : canCheck);

			this.setTaskInfo({checkable});
		}

		setDeadline(deadline)
		{
			this.setTaskInfo({deadline});
		}

		setResponsibleIcon(responsibleIcon)
		{
			this.setTaskInfo({responsibleIcon});
		}

		setState(newState = null)
		{
			const currentState = (!newState ? this.task.getState() : this.task.statesList[newState]);

			if (this.task.isCompleted || currentState === null)
			{
				if (this.taskInfo && this.taskInfo.styles && this.taskInfo.styles.state)
				{
					delete this.taskInfo.styles.state;
				}
				this.setTaskInfo({state: '', styles: this.taskInfo.styles});
			}
			else
			{
				const {message, backgroundColor, fontColor} = currentState;
				const currentStyles = this.taskInfo.styles || {};

				currentStyles.state = {
					backgroundColor,
					font: {
						color: fontColor,
						fontStyle: 'semibold',
					},
				};

				this.setTaskInfo({state: message, styles: currentStyles});
			}
		}

		updateActions(newActions)
		{
			const actions = newActions || this.task.getActions();
			this.setTaskInfo({actions});
		}

		updateMessageCount(newMessageCount = null)
		{
			const messageCount = newMessageCount || this.task.getMessageCount() || 0;
			this.setTaskInfo({messageCount});
		}

		updateNewCommentsCount(newCommentsCount)
		{
			this.setTaskInfo({newCommentsCount: newCommentsCount || this.task.newCommentsCount});
		}

		setTaskInfo(newProperties)
		{
			Object.keys(newProperties).forEach((key) => {
				this.taskInfo[key] = newProperties[key];
			});

			this.taskCard.setTaskInfo(this.taskInfo);
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
			this.getTaskInfo = BX.componentParameters.get('GET_TASK_INFO', false);

			this.currentUser = result.settings.userInfo;
			this.task = new Task(this.currentUser);

			this.rest = new Request();
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
			const {nodeName, nodeValue, dateValue, responsibleIcon} = eventData;
			switch (nodeName)
			{
				case 'data[DEADLINE]':
					if (dateValue || (!dateValue && nodeValue === ''))
					{
						this.changeDeadline(dateValue ? Date.parse(dateValue) : null);
					}
					break;

				case 'data[SE_RESPONSIBLE][0][ID]':
					if (typeof responsibleIcon !== 'undefined' && nodeValue)
					{
						const currentResponsibleId = this.task.responsible.id;
						let formattedIcon = 'images/avatar.png';

						if (responsibleIcon !== '')
						{
							formattedIcon = responsibleIcon.replace(/^url\(["']?/, '').replace(/["']?\)$/, '');
						}

						this.task.responsible.id = nodeValue;
						if (this.taskCardHandler)
						{
							this.taskCardHandler.setResponsibleIcon(formattedIcon);
							this.taskCardHandler.setState();
							this.taskCardHandler.updateMessageCount();
						}

						this.setResponsibleById(nodeValue).then(
							() => {
								console.log('onMobileGridFormDataChange::setResponsibleById::then.resolve');
								this.task.save();
							},
							() => {
								console.log('onMobileGridFormDataChange::setResponsibleById::then.reject');
								this.task.responsible.id = currentResponsibleId;
							}
						);
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
			if (this.task.id !== eventData.taskId)
			{
				return;
			}

			this.task.newCommentsCount = 0;
			if (this.taskCardHandler)
			{
				this.taskCardHandler.updateNewCommentsCount();
			}
		}

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

		setResponsibleById(responsibleId)
		{
			return new Promise((resolve, reject) => {
				const userRequest = new Request('user.');
				userRequest.call('search', {
					IMAGE_RESIZE: 'small',
					FILTER: {
						ID: responsibleId,
					},
				}).then((response) => {
					const user = response.result[0];
					this.task.responsible = {
						id: user.ID,
						name: TaskCard.getFormattedName(user),
						icon: encodeURI(user.PERSONAL_PHOTO),
						link: '',
					};
					resolve(response);
				}).catch((response) => {
					reject(response);
				});
			});
		}

		changeDeadline(deadline)
		{
			this.task.deadline = deadline;
			if (this.taskCardHandler)
			{
				this.taskCardHandler.setDeadline(deadline);
				this.taskCardHandler.setState();
				this.taskCardHandler.updateMessageCount();
			}
		}

		changeResponsible(responsible)
		{
			this.task.responsible = {
				id: responsible.params.id,
				name: responsible.title,
				icon: responsible.imageUrl,
				link: '',
			};
			if (this.taskCardHandler)
			{
				this.taskCardHandler.setResponsibleIcon(encodeURI(responsible.imageUrl));
			}
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

		openNewTaskPage()
		{
			const guid = TaskCard.getGuid();
			let url = result.settings.taskPaths.add
				.replace(/#taskId#/gi, 0)
				.replace(/#userId#/gi, this.currentUser.id)
				.replace(/#salt#/gi, new Date().getTime());
			url = `${url}&GUID=${guid}`;

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
								titleParams: {text: BX.message('TASKS_TASK_DETAIL_TASK_NEW_TASK_TITLE')},
							},
						},
					},
					params: {
						MODE: 'edit',
						COMPONENT_CODE: 'tasks.view',
						USER_ID: this.userId || 0,
						TASK_ID: 0,
						GUID: guid,
					},
				});
			}
			else
			{
				PageManager.openPage({url, cache: false, modal: true});
			}
		}

		openNewSubTaskPage()
		{
			const guid = TaskCard.getGuid();
			let url = result.settings.taskPaths.addSub
				.replace(/#taskId#/gi, 0)
				.replace(/#parentTaskId#/gi, this.task.id)
				.replace(/#userId#/gi, this.currentUser.id)
				.replace(/#salt#/gi, new Date().getTime());
			url = `${url}&GUID=${guid}`;

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
								titleParams: {text: BX.message('TASKS_TASK_DETAIL_TASK_NEW_SUBTASK_TITLE')},
							},
						},
					},
					params: {
						MODE: 'edit',
						COMPONENT_CODE: 'tasks.view',
						USER_ID: this.userId || 0,
						TASK_ID: 0,
						GUID: guid,
					},
				});
			}
			else
			{
				PageManager.openPage({url, cache: false, modal: true});
			}
		}

		openEditTaskPage()
		{
			const guid = TaskCard.getGuid();
			let url = result.settings.taskPaths.update
				.replace(/#taskId#/gi, this.task.id)
				.replace(/#userId#/gi, this.currentUser.id)
				.replace(/#salt#/gi, new Date().getTime());
			url = `${url}&GUID=${guid}`;

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
								titleParams: {text: BX.message('TASKS_TASK_DETAIL_TASK_EDIT_TITLE')},
							},
						},
					},
					params: {
						MODE: 'edit',
						COMPONENT_CODE: 'tasks.view',
						USER_ID: this.userId || 0,
						TASK_ID: this.taskId,
						GUID: guid,
					},
				});
			}
			else
			{
				PageManager.openPage({url, cache: false, modal: true});
			}
		}

		complete()
		{
			this.task.status = Task.statusList.completed;
			if (this.taskCardHandler)
			{
				this.taskCardHandler.setIsChecked(true);
				this.taskCardHandler.setState();
				this.taskCardHandler.updateMessageCount();
			}

			this.task.complete().then(() => {
				if (this.taskCardHandler)
				{
					this.taskCardHandler.updateActions();
				}
				this.redrawTaskPopupMenu();
			});
		}

		renew()
		{
			this.task.status = Task.statusList.pending;
			if (this.taskCardHandler)
			{
				this.taskCardHandler.setIsChecked(false);
				this.taskCardHandler.setState();
				this.taskCardHandler.updateMessageCount();
			}

			this.task.renew().then(() => {
				if (this.taskCardHandler)
				{
					this.taskCardHandler.updateActions();
				}
				this.redrawTaskPopupMenu();
			});
		}

		start()
		{
			this.task.status = Task.statusList.inprogress;
			this.task.start().then(() => {
				if (this.taskCardHandler)
				{
					this.taskCardHandler.updateActions();
				}
				this.redrawTaskPopupMenu();
			});
		}

		pause()
		{
			this.task.status = Task.statusList.pending;
			this.task.pause().then(() => {
				if (this.taskCardHandler)
				{
					this.taskCardHandler.updateActions();
				}
				this.redrawTaskPopupMenu();
			});
		}

		approve()
		{
			this.task.status = Task.statusList.completed;
			this.task.approve().then(() => this.redrawTaskPopupMenu());
		}

		disapprove()
		{
			this.task.status = Task.statusList.pending;
			this.task.disapprove().then(() => this.redrawTaskPopupMenu());
		}

		delegate()
		{
			UserList.openPicker({allowMultipleSelection: false}).then((data) => {
				if (data.length > 0)
				{
					this.changeResponsible(data[0]);
					this.task.delegate().then(() => BX.postWebEvent('tasks.view.native::onTaskUpdate', {
						taskId: this.task.id,
						responsible: true,
					}, true));
				}
			});
		}

		remove()
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

		addToFavorite()
		{
			this.task.rawAccess['favorite.add'] = false;
			this.task.rawAccess['favorite.delete'] = true;

			BX.postWebEvent('tasks.view.native::onTaskUpdate', {taskId: this.task.id, favorite: true}, true);
			this.redrawTaskPopupMenu();

			this.task.favorite().add().then(() => {}, () => {
				this.task.rawAccess['favorite.add'] = true;
				this.task.rawAccess['favorite.delete'] = false;

				BX.postWebEvent('tasks.view.native::onTaskUpdate', {taskId: this.task.id, favorite: false}, true);
				this.redrawTaskPopupMenu();
			});
		}

		removeFromFavorite()
		{
			this.task.rawAccess['favorite.add'] = true;
			this.task.rawAccess['favorite.delete'] = false;

			BX.postWebEvent('tasks.view.native::onTaskUpdate', {taskId: this.task.id, favorite: false}, true);
			this.redrawTaskPopupMenu();

			this.task.favorite().remove().then(() => {}, () => {
				this.task.rawAccess['favorite.add'] = false;
				this.task.rawAccess['favorite.delete'] = true;

				BX.postWebEvent('tasks.view.native::onTaskUpdate', {taskId: this.task.id, favorite: true}, true);
				this.redrawTaskPopupMenu();
			});
		}

		unfollow()
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
				if (this.taskCardHandler)
				{
					this.taskCardHandler.updateActions();
				}
			}
		}

		onItemAction(action)
		{
			switch (action.identifier)
			{
				default:
					break;

				case 'changeDeadline':
					dialogs.showDatePicker(
						{
							title: BX.message('TASKS_TASK_DETAIL_DEADLINE_DATE_PICKER'),
							type: 'datetime',
							value: this.task.deadline,
						},
						(eventName, newTs) => {
							if (!newTs)
							{
								return;
							}
							this.changeDeadline(newTs);

							console.log('tasks.view.native::onItemAction::changeDeadline');
							BX.postWebEvent('tasks.view.native::onItemAction', {
								taskId: this.task.id,
								taskGuid: this.guid,
								name: 'deadline',
								values: {deadline: newTs},
							});
						}
					);
					break;

				case 'changeResponsible':
					UserList.openPicker({
						allowMultipleSelection: false,
						title: BX.message("TASKS_TASK_DETAIL_TITLE_RESPONSIBLE"),
					}).then((users) => {
						if (users.length > 0)
						{
							console.log('tasks.view.native::onItemAction::changeResponsible');

							this.changeResponsible(users[0]);
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

					break;

				case 'start':
					this.start();
					break;

				case 'pause':
					this.pause();
					break;

				case 'remove':
					this.remove();
					break;

				case 'unfollow':
					this.unfollow();
					break;
			}
		}

		onPullUpdate(data)
		{
			console.log('tasks.view.native::onPullUpdate', data);
			const taskId = data.TASK_ID.toString();

			if (taskId !== this.task.id)
			{
				return;
			}

			this.rest.call('get', {taskId, select: TaskCard.selectFields}).then(
				(response) => {
					const {task} = response.result;

					this.task.status = task.status;
					this.task.rawAccess = task.action;

					if (this.taskCardHandler)
					{
						this.taskCardHandler.setIsCheckable();
						this.taskCardHandler.setIsChecked();
						this.taskCardHandler.updateActions();
					}
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

		onPullComment(data)
		{
			console.log('tasks.view.native::onPullComment');

			const [entityType, entityId] = data.ENTITY_XML_ID.split('_');
			const returnConditions = {
				wrongEntityType: entityType !== 'TASK',
				wrongEntityId: entityId !== this.task.id,
				myComment: Number(data.OWNER_ID) === Number(this.currentUser.id),
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

			this.task.newCommentsCount += 1;
			if (this.taskCardHandler)
			{
				this.taskCardHandler.updateNewCommentsCount();
			}
		}

		updateTask(taskData)
		{
			console.log(taskData);
		}

		get popupMenuItemsMap()
		{
			return {
				addTask: {
					title: BX.message('TASKS_TASK_DETAIL_TASK_ADD'),
					iconUrl: '/bitrix/mobileapp/mobile/components/bitrix/tasks.view/images/mobile-task-view-popup-add.png',
					action: this.openNewTaskPage,
				},
				addSubTask: {
					title: BX.message('TASKS_TASK_DETAIL_TASK_ADD_SUBTASK'),
					iconUrl: '/bitrix/mobileapp/mobile/components/bitrix/tasks.view/images/mobile-task-view-popup-add.png',
					action: this.openNewSubTaskPage,
				},
				'favorite.add': {
					title: BX.message('TASKS_TASK_DETAIL_BTN_ADD_FAVORITE_TASK'),
					iconUrl: '/bitrix/mobileapp/mobile/components/bitrix/tasks.view/images/mobile-task-view-popup-add-favorite.png',
					action: this.addToFavorite,
				},
				'favorite.remove': {
					title: BX.message('TASKS_TASK_DETAIL_BTN_DELETE_FAVORITE_TASK'),
					iconUrl: '/bitrix/mobileapp/mobile/components/bitrix/tasks.view/images/mobile-task-view-popup-delete-favorite.png',
					action: this.removeFromFavorite,
				},
				start: {
					title: BX.message('TASKS_TASK_DETAIL_BTN_START_TASK'),
					iconUrl: '/bitrix/mobileapp/mobile/components/bitrix/tasks.view/images/mobile-task-view-popup-start.png',
					action: this.start,
				},
				complete: {
					title: BX.message('TASKS_TASK_DETAIL_BTN_CLOSE_TASK'),
					iconUrl: '/bitrix/mobileapp/mobile/components/bitrix/tasks.view/images/mobile-task-view-popup-finish.png',
					action: this.complete,
				},
				renew: {
					title: BX.message('TASKS_TASK_DETAIL_BTN_RENEW_TASK'),
					iconUrl: '/bitrix/mobileapp/mobile/components/bitrix/tasks.view/images/mobile-task-view-popup-renew.png',
					action: this.renew,
				},
				pause: {
					title: BX.message('TASKS_TASK_DETAIL_BTN_PAUSE_TASK'),
					iconUrl: '/bitrix/mobileapp/mobile/components/bitrix/tasks.view/images/mobile-task-view-popup-pause.png',
					action: this.pause,
				},
				disapprove: {
					title: BX.message('TASKS_TASK_DETAIL_BTN_REDO_TASK'),
					iconUrl: '/bitrix/mobileapp/mobile/components/bitrix/tasks.view/images/mobile-task-view-popup-renew.png',
					action: this.disapprove,
				},
				approve: {
					title: BX.message('TASKS_TASK_DETAIL_BTN_APPROVE_TASK'),
					iconUrl: '/bitrix/mobileapp/mobile/components/bitrix/tasks.view/images/mobile-task-view-popup-finish.png',
					action: this.approve,
				},
				delegate: {
					title: BX.message('TASKS_TASK_DETAIL_BTN_DELEGATE_TASK'),
					iconUrl: '/bitrix/mobileapp/mobile/components/bitrix/tasks.view/images/mobile-task-view-popup-delegate.png',
					action: this.delegate,
				},
				update: {
					title: BX.message('TASKS_TASK_DETAIL_BTN_EDIT'),
					iconUrl: '/bitrix/mobileapp/mobile/components/bitrix/tasks.view/images/mobile-task-view-popup-edit.png',
					action: this.openEditTaskPage,
				},
				remove: {
					title: BX.message('TASKS_TASK_DETAIL_BTN_REMOVE'),
					iconUrl: '/bitrix/mobileapp/mobile/components/bitrix/tasks.view/images/mobile-task-view-popup-delete.png',
					action: this.remove,
				},
			};
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

			this.rest.call('get', {taskId: this.taskId, select: TaskCard.selectFields}).then((response) => {
				const {task} = response.result;
				this.task.setData(task);

				BX.onViewLoaded(() => {
					this.taskCardHandler = new TaskCardHandler(taskcard, this.task, this.getTaskInfo);
					this.taskCardHandler.setState();
					this.taskCardHandler.updateMessageCount();
					this.taskCardHandler.updateNewCommentsCount();

					this.checklistController = new ChecklistController(this.taskId, this.userId, this.guid, this.mode);

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