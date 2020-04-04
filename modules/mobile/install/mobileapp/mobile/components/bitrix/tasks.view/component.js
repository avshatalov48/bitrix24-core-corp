"use strict";
/**
 * @bxjs_lang_path component.php
 */

include('InAppNotifier');

(function()
{
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

	class App
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

		constructor(taskcard, userId)
		{
			this.userId = userId || parseInt(BX.componentParameters.get('USER_ID', 0), 10);
			this.taskId = BX.componentParameters.get('TASK_ID', 0);
			this.getTaskInfo = BX.componentParameters.get('GET_TASK_INFO', false);
			this.guid = BX.componentParameters.get('GUID');

			this.currentUser = result.settings.userInfo;
			this.task = new Task(this.currentUser);

			this.rest = new Request();
			this.rest.call('get', {taskId: this.taskId, select: App.selectFields}).then((response) => {
				const {task} = response.result;
				this.task.setData(task);

				BX.onViewLoaded(() => {
					this.taskCardHandler = new TaskCardHandler(taskcard, this.task, this.getTaskInfo);
					this.taskCardHandler.setState();
					this.taskCardHandler.updateMessageCount();
					this.taskCardHandler.updateNewCommentsCount();

					this.popupMenu = dialogs.createPopupMenu();
					this.popupMenu.setPosition('center');

					taskcard.setRightButtons([
						{
							type: 'more',
							callback: () =>
							{
								this.popupMenu.show();
							},
						},
					]);
					this.redrawMenu();
				});
			});

			this.setListeners();
		}

		setListeners()
		{
			BX.addCustomEvent('onMobileGridFormDataChange', (eventData) => {
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
							this.taskCardHandler.setResponsibleIcon(formattedIcon);
							this.taskCardHandler.setState();
							this.taskCardHandler.updateMessageCount();

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
			});

			BX.addCustomEvent('onTaskDetailOptionsButtonClick', (eventData) => {
				if (this.task.id !== eventData.taskId || this.guid !== eventData.guid)
				{
					return;
				}

				this.popupMenu.show();
			});

			BX.addCustomEvent('task.view.onCommentsRead', (eventData) => {
				if (this.task.id !== eventData.taskId)
				{
					return;
				}

				this.task.newCommentsCount = 0;
				this.taskCardHandler.updateNewCommentsCount();
			});

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

		redrawMenu()
		{
			this.popupMenu.setData(this.popupMenuItems, [{id: '0'}], (eventName, item) => {
				if (eventName === 'onItemSelected')
				{
					switch (item.id)
					{
						default:
							console.log(`Unknown action ${item.id}`);
							break;

						case 'addSubTask':
							PageManager.openPage({
								url: result.settings.taskPaths.addSub.replace(/#taskId#/gi, 0).replace(/#parentTaskId#/gi, this.task.id).replace(/#userId#/gi, this.currentUser.id).replace(/#salt#/gi, new Date().getTime()),
								cache: false,
								modal: true,
							});
							break;

						case 'addTask':
							PageManager.openPage({
								url: result.settings.taskPaths.add.replace(/#taskId#/gi, 0).replace(/#userId#/gi, this.currentUser.id).replace(/#salt#/gi, new Date().getTime()),
								cache: false,
								modal: true,
							});
							break;

						case 'update':
							PageManager.openPage({
								url: result.settings.taskPaths.update.replace(/#taskId#/gi, this.task.id).replace(/#userId#/gi, this.currentUser.id).replace(/#salt#/gi, new Date().getTime()),
								cache: false,
								modal: true,
							});
							break;

						case 'remove':
							this.remove();
							break;

						case 'complete':
							this.complete();
							break;

						case 'renew':
							this.renew();
							break;

						case 'start':
							this.start();
							break;

						case 'pause':
							this.pause();
							break;

						case 'approve':
							this.approve();
							break;

						case 'disapprove':
							this.disapprove();
							break;

						case 'delegate':
							this.delegate();
							break;

						case 'favorite.add':
							this.addToFavorite();
							break;

						case 'favorite.remove':
							this.removeFromFavorite();
							break;
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
						name: App.getFormattedName(user),
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
			this.taskCardHandler.setDeadline(deadline);
			this.taskCardHandler.setState();
			this.taskCardHandler.updateMessageCount();
		}

		changeResponsible(responsible)
		{
			this.task.responsible = {
				id: responsible.params.id,
				name: responsible.title,
				icon: responsible.imageUrl,
				link: '',
			};
			this.taskCardHandler.setResponsibleIcon(encodeURI(responsible.imageUrl));
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

		complete()
		{
			this.task.status = Task.statusList.completed;
			this.taskCardHandler.setIsChecked(true);
			this.taskCardHandler.setState();
			this.taskCardHandler.updateMessageCount();

			this.task.complete().then(() => {
				this.taskCardHandler.updateActions();
				this.redrawMenu();
			});
		}

		renew()
		{
			this.task.status = Task.statusList.pending;
			this.taskCardHandler.setIsChecked(false);
			this.taskCardHandler.setState();
			this.taskCardHandler.updateMessageCount();

			this.task.renew().then(() => {
				this.taskCardHandler.updateActions();
				this.redrawMenu();
			});
		}

		start()
		{
			this.task.status = Task.statusList.inprogress;
			this.task.start().then(() => {
				this.taskCardHandler.updateActions();
				this.redrawMenu();
			});
		}

		pause()
		{
			this.task.status = Task.statusList.pending;
			this.task.pause().then(() => {
				this.taskCardHandler.updateActions();
				this.redrawMenu();
			});
		}

		approve()
		{
			this.task.status = Task.statusList.completed;
			this.task.approve().then(() => this.redrawMenu());
		}

		disapprove()
		{
			this.task.status = Task.statusList.pending;
			this.task.disapprove().then(() => this.redrawMenu());
		}

		delegate()
		{
			UserList.openPicker().then((user) => {
				this.changeResponsible(user);
				this.task.delegate().then(() => BX.postWebEvent('tasks.view.native::onTaskUpdate', {
					taskId: this.task.id,
					responsible: true,
				}, true));
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
						this.taskCardHandler.closeForm();
					}
				},
				items: App.getRemoveSheetItems(),
			});
		}

		addToFavorite()
		{
			this.task.rawAccess['favorite.add'] = false;
			this.task.rawAccess['favorite.delete'] = true;

			BX.postWebEvent('tasks.view.native::onTaskUpdate', {taskId: this.task.id, favorite: true}, true);
			this.redrawMenu();

			this.task.favorite().add().then(() => {}, () => {
				this.task.rawAccess['favorite.add'] = true;
				this.task.rawAccess['favorite.delete'] = false;

				BX.postWebEvent('tasks.view.native::onTaskUpdate', {taskId: this.task.id, favorite: false}, true);
				this.redrawMenu();
			});
		}

		removeFromFavorite()
		{
			this.task.rawAccess['favorite.add'] = true;
			this.task.rawAccess['favorite.delete'] = false;

			BX.postWebEvent('tasks.view.native::onTaskUpdate', {taskId: this.task.id, favorite: false}, true);
			this.redrawMenu();

			this.task.favorite().remove().then(() => {}, () => {
				this.task.rawAccess['favorite.add'] = false;
				this.task.rawAccess['favorite.delete'] = true;

				BX.postWebEvent('tasks.view.native::onTaskUpdate', {taskId: this.task.id, favorite: true}, true);
				this.redrawMenu();
			});
		}

		unfollow()
		{
			const currentUserId = Number(this.currentUser.id);

			this.task.auditors = this.task.auditors.filter(item => item !== currentUserId);
			if (!this.task.isMember(currentUserId))
			{
				this.task.stopWatch();
				this.taskCardHandler.closeForm();
			}
			else
			{
				this.taskCardHandler.updateActions();
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
								name: 'deadline',
								values: {deadline: newTs},
							});
						}
					);
					break;

				case 'changeResponsible':
					UserList.openPicker().then((user) => {
						this.changeResponsible(user);

						console.log('tasks.view.native::onItemAction::changeResponsible');
						BX.postWebEvent('tasks.view.native::onItemAction', {
							taskId: this.task.id,
							name: 'responsible',
							values: {
								user: this.task.responsible,
							},
						});
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

			this.rest.call('get', {taskId, select: App.selectFields}).then(
				(response) => {
					const {task} = response.result;

					this.task.status = task.status;
					this.task.rawAccess = task.action;

					this.taskCardHandler.setIsCheckable();
					this.taskCardHandler.setIsChecked();
					this.taskCardHandler.updateActions();
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
			this.taskCardHandler.closeForm();
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
			this.taskCardHandler.updateNewCommentsCount();
		}

		updateTask(taskData)
		{
			console.log(taskData);
		}

		get popupMenuItems()
		{
			const items = [];
			const sectionCode = '0';
			const possibleActions = {
				addTask: BX.message('TASKS_TASK_DETAIL_TASK_ADD'),
				addSubTask: BX.message('TASKS_TASK_DETAIL_TASK_ADD_SUBTASK'),
				complete: BX.message('TASKS_TASK_DETAIL_BTN_CLOSE_TASK'),
				start: BX.message('TASKS_TASK_DETAIL_BTN_START_TASK'),
				renew: BX.message('TASKS_TASK_DETAIL_BTN_RENEW_TASK'),
				pause: BX.message('TASKS_TASK_DETAIL_BTN_PAUSE_TASK'),
				disapprove: BX.message('TASKS_TASK_DETAIL_BTN_REDO_TASK'),
				approve: BX.message('TASKS_TASK_DETAIL_BTN_APPROVE_TASK'),
				delegate: BX.message('TASKS_TASK_DETAIL_BTN_DELEGATE_TASK'),
				remove: BX.message('TASKS_TASK_DETAIL_BTN_REMOVE'),
				update: BX.message('TASKS_TASK_DETAIL_BTN_EDIT'),
				// defer: BX.message('TASKS_TASK_DETAIL_BTN_DEFER_TASK'),
				'favorite.add': BX.message('TASKS_TASK_DETAIL_BTN_ADD_FAVORITE_TASK'),
				'favorite.remove': BX.message('TASKS_TASK_DETAIL_BTN_DELETE_FAVORITE_TASK'),
			};

			items.push({id: 'addTask', sectionCode: '0', title: BX.message('TASKS_TASK_DETAIL_TASK_ADD')});
			items.push({id: 'addSubTask', sectionCode: '0', title: BX.message('TASKS_TASK_DETAIL_TASK_ADD_SUBTASK')});

			Object.keys(possibleActions).forEach((action) => {
				if (this.can(action))
				{
					items.push({id: action, sectionCode, title: possibleActions[action]});
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

	return (new App(taskcard, userId));
}());