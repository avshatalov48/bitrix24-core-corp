/**
 * @bxjs_lang_path extension.php
 */

(() =>
{
	class TaskView
	{
		/**
		 *
		 * @param data
		 */
		static open(data)
		{
			data.canOpenInDefault = data.canOpenInDefault || false;
			console.log('TaskView.open');
			PageManager.openComponent("JSStackComponent",
				{
					canOpenInDefault: data.canOpenInDefault,
					scriptPath: data.path,
					componentCode: "tasks.list",
					params: data.params,
					rootWidget: {
						name: "tasks.list",
						settings: {
							objectName: "list",
							filter: "view_all",
							useSearch: true,
							menuSections: [{id: "presets"}, {id: "counters", itemTextColor: "#f00"}],
							menuItems: [
								{
									'id': "view_all",
									'title': BX.message("TASKS_ROLE_VIEW_ALL"),
									'sectionCode': 'presets',
									'showAsTitle': true,
									'badgeCount': 0
								},
								{
									'id': "view_role_accomplice",
									'title': BX.message("TASKS_ROLE_ACCOMPLICE"),
									'sectionCode': 'presets',
									'showAsTitle': true,
									'badgeCount': 0
								},
								{
									'id': "view_role_auditor",
									'title': BX.message("TASKS_ROLE_AUDITOR"),
									'sectionCode': 'presets',
									'showAsTitle': true,
									'badgeCount': 0
								},
								{

									'id': "view_role_originator",
									'title': BX.message("TASKS_ROLE_ORIGINATOR"),
									'sectionCode': 'presets',
									'showAsTitle': true,
									'badgeCount': 0
								},

							]
						},
					}
				}
			);
			console.log('TaskView.open:ed');
		}
	}

	class Task
	{
		static get statesList()
		{
			const statePrefix = 'MOBILE_TASKS_TASK_CARD_STATE';
			const backgroundColor = '#18ff5752';
			const fontColor = '#ff5752';

			return {
				isExpired: {
					message: BX.message(`${statePrefix}_EXPIRED`),
					fontColor,
					backgroundColor,
				},
				isExpiredSoon: {
					message: BX.message(`${statePrefix}_EXPIRED_SOON`),
					fontColor,
					backgroundColor,
				},
				isNew: {
					message: BX.message(`${statePrefix}_NEW`),
					fontColor,
					backgroundColor,
				},
				isWaitCtrl: {
					message: BX.message(`${statePrefix}_SUPPOSEDLY_COMPLETED`),
					fontColor,
					backgroundColor,
				},
				isWoDeadline: {
					message: BX.message(`${statePrefix}_WITHOUT_DEADLINE`),
					fontColor,
					backgroundColor,
				},
			};
		}

		static get statusList()
		{
			return {
				pending: 2,
				inprogress: 3,
				waitCtrl: 4,
				completed: 5,
				deferred: 6,
			};
		}

		static get counterColors()
		{
			const red = '#ff5752';
			return {
				isNew: red,
				expired: red,
				waitCtrl: red,
			};
		}

		constructor(currentUser)
		{
			this.id = `tmp-id-${(new Date()).getTime()}`;
			this.guid = '';
			this.isNewRecord = true;

			this.currentUser = currentUser;

			const defaultTaskUrl = `${env.siteDir}mobile/tasks/snmrouter/?routePage=#action#&TASK_ID=#taskId#`;
			this.taskUrlTemplate = BX.componentParameters.get('PATH_TO_TASK_ADD', defaultTaskUrl);
			this.error = false;

			this.deadline = null;
			this.timeTracking = false;
			this.status = Task.statusList.pending;
			this.subStatus = Task.statusList.pending;
			this.notViewed = false;
			this.messageCount = 0;
			this.commentsCount = 0;
			this.newCommentsCount = 0;
			this.accomplices = [];
			this.auditors = [];

			this.params = {};
			this.params.allowChangeDeadline = true;

			this.rawAccess = {};
		}

		setData(row)
		{
			this.id = row.id;
			this.title = row.title;
			this.groupId = row.groupId;
			this.group = (this.groupId > 0 && row.group ? row.group : {id: 0, name: ''});

			this.status = row.status;
			this.subStatus = row.subStatus || this.status;

			this.creator = row.creator;
			this.responsible = row.responsible;

			this.timeTracking = (row.allowTimeTracking === 'Y');

			this.commentsCount = row.commentsCount;
			this.newCommentsCount = row.newCommentsCount;

			this.notViewed = row.notViewed === 'Y';

			this.accomplices = row.accomplices || [];
			this.auditors = row.auditors || [];

			this.rawAccess = row.action;

			const deadline = Date.parse(row.deadline);
			this.deadline = (deadline > 0 ? deadline : null);

			if (
				Number(this.currentUser.id) === Number(row.creator.id)
				&& this.currentUser.icon !== row.creator.icon
			)
			{
				this.currentUser.icon = row.creator.icon;
			}

			if (
				Number(this.currentUser.id) === Number(row.responsible.id)
				&& this.currentUser.icon !== row.responsible.icon
			)
			{
				this.currentUser.icon = row.responsible.icon;
			}
		}

		cloneData(data)
		{
			for (let key in data)
			{
				if (this.hasOwnProperty(key))
				{
					this[key] = data[key];
				}
			}
		}

		get id()
		{
			return this._id.toString();
		}

		set id(id)
		{
			this._id = id.toString();
			this.isNewRecord = false;
		}

		get guid()
		{
			function s4()
			{
				return Math.floor((1 + Math.random()) * 0x10000).toString(16).substring(1);
			}

			if (!this._guid)
			{
				this._guid = `${s4()}${s4()}-${s4()}-${s4()}-${s4()}-${s4()}${s4()}${s4()}`;
			}

			return this._guid;
		}

		set guid(value)
		{
			this._guid = value;
		}

		get status()
		{
			return this._status;
		}

		set status(status)
		{
			this._status = Number(status);
		}

		get subStatus()
		{
			return this._subStatus;
		}

		set subStatus(subStatus)
		{
			this._subStatus = Number(subStatus);
		}

		get newCommentsCount()
		{
			if (isNaN(this._newCommentsCount) || typeof this._newCommentsCount == "undefined")
			{
				return 0;
			}

			return this._newCommentsCount;
		}

		set newCommentsCount(value)
		{
			if (value == null)
			{
				return;
			}

			if (isNaN(value) || typeof value == "undefined")
			{
				this._newCommentsCount = 0;
			}
			else
			{
				this._newCommentsCount = value;
			}
		}

		get accomplices()
		{
			return this._accomplices;
		}

		set accomplices(values)
		{
			this._accomplices = [];

			if (values.length > 0)
			{
				values.forEach(item => this._accomplices.push(Number(item)));
			}
		}

		get auditors()
		{
			return this._auditors;
		}

		set auditors(values)
		{
			this._auditors = [];

			if (values.length > 0)
			{
				values.forEach(item => this._auditors.push(Number(item)));
			}
		}

		isCreator(userId = null)
		{
			return Number(userId || this.currentUser.id) === Number(this.creator.id);
		}

		isResponsible(userId = null)
		{
			return Number(userId || this.currentUser.id) === Number(this.responsible.id);
		}

		isAccomplice(userId = null)
		{
			return this.accomplices.includes(Number(userId || this.currentUser.id));
		}

		isAuditor(userId = null)
		{
			return this.auditors.includes(Number(userId || this.currentUser.id));
		}

		isMember(userId = null)
		{
			return this.isCreator(userId)
				|| this.isResponsible(userId)
				|| this.isAccomplice(userId)
				|| this.isAuditor(userId);
		}

		isDoer(userId = null)
		{
			return this.isResponsible(userId) || this.isAccomplice(userId);
		}

		get isNew()
		{
			return this.notViewed || [-2, 1].includes(this.subStatus);
		}

		get isWaitCtrl()
		{
			return this.status === Task.statusList.waitCtrl;
		}

		get isExpired()
		{
			const date = new Date();
			return this.deadline
				&& this.deadline <= date.getTime()
				&& !this.isWaitCtrl;
		}

		get isExpiredSoon()
		{
			const date = new Date();
			date.setDate(date.getDate() + 1);

			return this.deadline
				&& this.deadline <= date.getTime()
				&& !this.isWaitCtrl
				&& !this.isExpired
				&& this.isDoer();
		}

		get isWoDeadline()
		{
			const onePersonTask = this.isCreator() && this.isResponsible();
			return !this.deadline && ((this.isCreator() || this.isResponsible()) && !onePersonTask);
		}

		get isCompleted()
		{
			return this.status === Task.statusList.completed
				|| (this.status === Task.statusList.waitCtrl && this.isDoer() && !this.isCreator());
		}

		get can()
		{
			return {
				changeDeadline: this.rawAccess && this.rawAccess.changeDeadline,
				changeResponsible: (this.rawAccess && this.rawAccess.edit) || this.currentUser.isAdmin || this.isCreator(),
				start: this.rawAccess && this.rawAccess.start,
				pause: this.rawAccess && this.rawAccess.pause,
				complete: this.rawAccess && this.rawAccess.complete,
				renew: this.rawAccess && this.rawAccess.renew,
				update: this.rawAccess && this.rawAccess.edit,
				remove: this.rawAccess && this.rawAccess.remove,
				defer: this.rawAccess && this.rawAccess.defer,
				disapprove: this.rawAccess && this.rawAccess.disapprove,
				approve: this.rawAccess && this.rawAccess.approve,
				delegate: this.rawAccess && this.rawAccess.delegate,
				decline: this.rawAccess && this.rawAccess.decline,
				'favorite.add': this.rawAccess && this.rawAccess['favorite.add'],
				'favorite.remove': this.rawAccess && this.rawAccess['favorite.delete'],
			};
		}

		getMessageCount()
		{
			let count = 0;

			if (this.isCompleted || this.status > Task.statusList.waitCtrl)
			{
				return count;
			}

			if (this.isWaitCtrl)
			{
				return count + 1;
			}

			if (this.isExpired)
			{
				count += 1;
			}

			if (this.isExpiredSoon)
			{
				count += 1;
			}

			if (this.isWoDeadline)
			{
				count += 1;
			}

			if (this.isNew && this.isDoer() && !this.isCreator())
			{
				count += 1;
			}

			return count;
		}

		save()
		{
			return new Promise((resolve, reject) => {
				const request = new Request();
				if (this.isNewRecord)
				{
					console.log('Create task');

					request
						.call('add', {
							fields: {
								TITLE: this.title,
								CREATED_BY: this.creator.id,
								RESPONSIBLE_ID: this.responsible.id,
								STATUS: this.status,
								DEADLINE: this.deadline,
								GUID: this.guid,
								ALLOW_CHANGE_DEADLINE: 'Y',
								TASK_CONTROL: 'Y',
								GROUP_ID: this.groupId || 0,
							},
							params: {
								PLATFORM: 'mobile',
							},
						})
						.then(
							(response) => {
								console.log(response.result.task);
								this.id = response.result.task.id;
								this.isNewRecord = false;
								this.error = false;
								this.rawAccess = response.result.task.action;

								resolve();
							},
							(response) => {
								console.log(response.ex.error_description);
								this.error = true;
								this.rawAccess = response.result.task.action;

								reject();
							}
						);
				}
				else
				{
					console.log('Update task');

					request
						.call('update', {
							taskId: this.id,
							fields: {
								TITLE: this.title,
								RESPONSIBLE_ID: this.responsible.id,
								STATUS: this.status,
								DEADLINE: this.deadline ? (new Date(this.deadline)).toISOString() : null,
							},
						})
						.then(
							(response) => {
								console.log(response);
								this.error = false;

								resolve(response);
							},
							(response) => {
								console.log(response);
								this.error = true;

								reject(response);
							}
						);
				}
			});
		}

		saveDeadline()
		{
			return new Promise((resolve, reject) => {
				(new Request())
					.call('update', {
						taskId: this.id,
						fields: {
							DEADLINE: this.deadline ? (new Date(this.deadline)).toISOString() : null,
						},
					})
					.then(
						(response) => {
							console.log(response);
							this.error = false;

							resolve(response);
						},
						(response) => {
							console.log(response);
							this.error = true;

							reject(response);
						}
					);
			});
		}

		complete()
		{
			this.status = Task.statusList.completed;

			return new Promise((resolve, reject) => {
				(new Request())
					.call('complete', {
						taskId: this.id,
						params: {HIDE: false},
					})
					.then(
						(response) => {
							console.log(response.result.task);
							this.error = false;
							this.rawAccess = response.result.task.action;

							resolve();
						},
						(response) => {
							console.log(response);
							this.error = true;

							reject();
						}
					);
			});
		}

		renew()
		{
			this.status = Task.statusList.pending;

			return new Promise((resolve, reject) => {
				(new Request())
					.call('renew', {
						taskId: this.id,
						params: {HIDE: false},
					})
					.then(
						(response) => {
							console.log(response.result.task);
							this.error = false;
							this.rawAccess = response.result.task.action;

							resolve();
						},
						(response) => {
							console.log(response);
							this.error = true;

							reject();
						}
					);
			});
		}

		start()
		{
			this.status = Task.statusList.inprogress;

			return new Promise((resolve, reject) => {
				(new Request())
					.call('start', {
						taskId: this.id,
					})
					.then(
						(response) => {
							console.log(response.result.task);
							this.error = false;
							this.rawAccess = response.result.task.action;

							resolve();
						},
						(response) => {
							console.log(response);
							this.error = true;

							reject();
						}
					);
			});
		}

		pause()
		{
			this.status = Task.statusList.pending;

			return new Promise((resolve, reject) => {
				(new Request())
					.call('pause', {
						taskId: this.id,
					})
					.then(
						(response) => {

							console.log(response.result.task);
							this.error = false;
							this.rawAccess = response.result.task.action;

							resolve();
						},
						(response) => {
							console.log(response);
							this.error = true;

							reject();
						}
					);
			});
		}

		approve()
		{
			this.status = Task.statusList.complete;

			return new Promise((resolve, reject) => {
				(new Request())
					.call('approve', {
						taskId: this.id,
					})
					.then(
						(response) => {
							console.log(response.result.task);
							this.error = false;
							this.rawAccess = response.result.task.action;

							resolve();
						},
						(response) => {
							console.log(response);
							this.error = true;

							reject();
						}
					);
			});
		}

		disapprove()
		{
			this.status = Task.statusList.pending;

			return new Promise((resolve, reject) =>
			{
				(new Request())
					.call('disapprove', {
						taskId: this.id,
					})
					.then(
						(response) => {
							console.log(response.result.task);
							this.error = false;
							this.rawAccess = response.result.task.action;

							resolve();
						},
						(response) => {
							console.log(response);
							this.error = true;

							reject();
						}
					);
			});
		}

		delegate()
		{
			return new Promise((resolve, reject) => {
				(new Request())
					.call('delegate', {
						taskId: this.id,
						userId: this.responsible.id,
						params: {
							PLATFORM: 'mobile',
						},
					})
					.then(
						(response) => {
							console.log(response.result.task);
							this.error = false;
							this.rawAccess = response.result.task.action;
							this.auditors = response.result.task.auditors;

							resolve();
						},
						(response) => {
							console.log(response);
							this.error = true;

							reject();
						}
					);
			});
		}

		favorite()
		{
			return {
				add: () => {
					return new Promise((resolve, reject) => {
						(new Request())
							.call('favorite.add', {
								taskId: this.id,
							})
							.then(
								(response) => {
									console.log(response);
									this.error = false;
									this.rawAccess['favorite.add'] = false;
									this.rawAccess['favorite.delete'] = true;

									resolve();
								},
								(response) => {
									console.log(response);
									this.error = true;

									reject();
								}
							);
					});
				},
				remove: () => {
					return new Promise((resolve, reject) => {
						(new Request())
							.call('favorite.remove', {
								taskId: this.id,
							})
							.then(
								(response) =>  {
									console.log(response);
									this.error = false;
									this.rawAccess['favorite.add'] = true;
									this.rawAccess['favorite.delete'] = false;

									resolve();
								},
								(response) => {
									console.log(response);
									this.error = true;

									reject();
								}
							);
					});
				},
			};
		}

		stopWatch()
		{
			return new Promise((resolve, reject) => {
				(new Request())
					.call('stopWatch', {
						taskId: this.id,
					})
					.then(
						(response) => {
							console.log(response.result.task);
							this.error = false;
							this.rawAccess = response.result.task.action;

							resolve(response);
						},
						(response) => {
							console.log(response);
							this.error = true;

							reject(response);
						}
					);
			});
		}

		open()
		{
			if (Application.getApiVersion() < 31)
			{
				PageManager.openPage({
					backdrop: {
						showOnTop: true,
						forceDismissOnSwipeDown: true,
					},
					url: this.makeUrl(this.id),
					cache: false,
					modal: false,
					title: this.title || null,
				});
			}
			else
			{
				const taskId = this.id;
				const taskData = {
					id: taskId,
					title: 'TASK',
					taskInfo: this.getTaskInfo(),
				};

				BX.postComponentEvent('taskbackground::task::action', [taskData, taskId, {userId: this.currentUser.id}], 'background');
				console.log(`sendEvent to open task #${taskId}`);
			}
		}

		makeUrl(taskId, action = 'view')
		{
			return this.taskUrlTemplate.replace('#action#', action).replace('#taskId#', taskId);
		}

		getTaskInfo(withActions = true)
		{
			const state = this.getState() || {message: '', backgroundColor: '', fontColor: ''};

			return {
				id: this.id,
				title: this.title || '',
				checked: this.isCompleted,
				checkable: this.can.complete || this.can.approve || this.can.renew,
				deadline: this.deadline || 0,
				state: state.message,
				messageCount: this.getMessageCount(),
				newCommentsCount: this.newCommentsCount,
				creatorIcon: this.creator.icon,
				responsibleIcon: this.responsible.icon,
				actions: (withActions ? this.getActions() : []),
				styles: {
					state: {
						backgroundColor: state.backgroundColor,
						font: {
							color: state.fontColor,
							fontStyle: 'semibold',
						},
					},
				},
			};
		}

		getState()
		{
			const states = Task.statesList;
			let currentState = null;

			Object.keys(states).forEach((key) => {
				if (currentState === null && this[key])
				{
					currentState = states[key];
				}
			});

			return currentState;
		}

		getStates()
		{
			const states = Task.statesList;
			const currentStates = {};

			Object.keys(states).forEach((key) => {
				if (this[key])
				{
					currentStates[key] = states[key];
				}
			});

			return currentStates;
		}

		getActions()
		{
			const actions = {
				changeDeadline: {
					identifier: 'changeDeadline',
					title: BX.message('MOBILE_TASKS_TASK_CARD_VIEW_ACTION_CHANGE_DEADLINE'),
					iconName: 'term',
					color: '#ff8f30',
					position: 'right',
				},
				changeResponsible: {
					identifier: 'changeResponsible',
					title: BX.message('MOBILE_TASKS_TASK_CARD_VIEW_ACTION_CHANGE_RESPONSIBLE'),
					iconName: 'userlist',
					color: '#0064c1',
					position: 'right',
				},
				start: {
					identifier: 'start',
					title: BX.message('MOBILE_TASKS_TASK_CARD_VIEW_ACTION_START'),
					iconName: 'start',
					color: '#00c7f3',
					position: 'right',
				},
				pause: {
					identifier: 'pause',
					title: BX.message('MOBILE_TASKS_TASK_CARD_VIEW_ACTION_PAUSE'),
					iconName: 'finish',
					color: '#00c7f3',
					position: 'right',
				},
				remove: {
					identifier: 'remove',
					title: BX.message('MOBILE_TASKS_TASK_CARD_VIEW_ACTION_REMOVE'),
					iconName: 'delete',
					color: '#ff5b50',
					position: 'right',
				},
				unfollow: {
					identifier: 'unfollow',
					title: BX.message('MOBILE_TASKS_TASK_CARD_VIEW_ACTION_DONT_FOLLOW'),
					iconName: 'unfollow',
					color: '#2fc6f6',
					position: 'right',
				},
			};
			const currentActions = [];

			Object.keys(actions).forEach((key) => {
				if (this.can[key])
				{
					currentActions.push(actions[key]);
				}
			});

			if (
				currentActions.length < 4
				&& !currentActions.includes(actions.unfollow)
				&& this.isAuditor(this.currentUser.id)
			)
			{
				currentActions.push(actions.unfollow);
			}

			return currentActions;
		}
	}

	//TODO duplicate from task.list
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
				console.log({
					method: this.restNamespace + method,
					params: params
				});

				BX.rest.callMethod(this.restNamespace + method, params || {}, response =>
				{
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

	this.TaskView = TaskView;
	this.Task = Task;
})();