/**
 * @bxjs_lang_path extension.php
 */

(() => {
	const require = (ext) => jn.require(ext);

	const { Loc } = require('loc');
	const { Type } = require('type');
	const { Entry } = require('tasks/entry');
	const { RequestExecutor } = require('rest');
	const AppTheme = require('apptheme');
	const { showToast } = require('toast');

	const pathToExtension = '/bitrix/mobileapp/tasksmobile/extensions/tasks/task/';

	const pathToIcons = '/bitrix/mobileapp/tasksmobile/extensions/tasks/layout/action-menu/images';
	const iconPrefix = `${currentDomain}${pathToIcons}/tasksmobile-layout-action-menu-`;
	const { TaskStatus } = require('tasks/enum');

	class Console
	{
		static log()
		{
			console.log(...arguments);
		}
	}

	class Counter
	{
		static get types()
		{
			return {
				expired: {
					expired: 'expired',
					mutedExpired: 'mutedExpired',
					projectExpired: 'projectExpired',
				},
				newComments: {
					newComments: 'newComments',
					mutedNewComments: 'mutedNewComments',
					projectNewComments: 'projectNewComments',
				},
				my: {
					expired: 'expired',
					mutedExpired: 'mutedExpired',
					newComments: 'newComments',
					mutedNewComments: 'mutedNewComments',
				},
				project: {
					projectExpired: 'projectExpired',
					projectNewComments: 'projectNewComments',
				},
			};
		}

		static get colors()
		{
			return {
				danger: 'danger',
				success: 'success',
				gray: 'gray',
			};
		}

		static getDefaultData()
		{
			return {
				counters: {
					[Counter.types.my.expired]: 0,
					[Counter.types.my.newComments]: 0,
					[Counter.types.my.mutedExpired]: 0,
					[Counter.types.my.mutedNewComments]: 0,
					[Counter.types.project.projectExpired]: 0,
					[Counter.types.project.projectNewComments]: 0,
				},
				color: Counter.colors.gray,
				value: 0,
			};
		}

		/**
		 * @param {Task} task
		 */
		constructor(task)
		{
			this.task = task;

			this.set(Counter.getDefaultData());
		}

		get()
		{
			return {
				counters: this.counters,
				color: this.color,
				value: this.value,
			};
		}

		set(counter)
		{
			this.counters = (counter.counters || this.counters);
			this.color = (counter.color || this.color);
			this.value = (counter.value || 0);
		}

		exportProperties()
		{
			return this.get();
		}

		importProperties(properties)
		{
			this.set(properties);
		}

		read()
		{
			Object.keys(this.counters).forEach((type) => {
				if (Counter.types.newComments[type])
				{
					this.value -= this.counters[type];
					this.counters[type] = 0;
				}
			});
			this.value = (this.value < 0 ? 0 : this.value);
			this.color = (
				this.counters[Counter.types.expired.expired] > 0 ? Counter.colors.danger : Counter.colors.gray
			);
		}

		getNewCommentsCount()
		{
			let count = 0;

			Object.keys(this.counters).forEach((type) => {
				if (Counter.types.newComments[type])
				{
					count += this.counters[type];
				}
			});

			return count;
		}
	}

	class Action
	{
		static get types()
		{
			return {
				changeDeadline: 'changeDeadline',
				changeResponsible: 'changeResponsible',
				changeGroup: 'changeGroup',
				delegate: 'delegate',
				unfollow: 'unfollow',
				startTimer: 'startTimer',
				pauseTimer: 'pauseTimer',
				start: 'start',
				pause: 'pause',
				complete: 'complete',
				renew: 'renew',
				approve: 'approve',
				disapprove: 'disapprove',
				defer: 'defer',
				edit: 'edit',
				remove: 'remove',
				pin: 'pin',
				unpin: 'unpin',
				mute: 'mute',
				unmute: 'unmute',
				read: 'read',
				ping: 'ping',
				addTask: 'addTask',
				addSubTask: 'addSubTask',
				share: 'share',
				'favorite.add': 'favorite.add',
				'favorite.delete': 'favorite.delete',
			};
		}

		static getDefaultData()
		{
			return {
				[Action.types.changeDeadline]: false,
				[Action.types.delegate]: false,
				[Action.types.startTimer]: false,
				[Action.types.pauseTimer]: false,
				[Action.types.start]: false,
				[Action.types.pause]: false,
				[Action.types.complete]: false,
				[Action.types.renew]: false,
				[Action.types.approve]: false,
				[Action.types.disapprove]: false,
				[Action.types.defer]: false,
				[Action.types.edit]: false,
				[Action.types.remove]: false,
				[Action.types['favorite.add']]: false,
				[Action.types['favorite.delete']]: false,
			};
		}

		/**
		 * @param {Task} task
		 */
		constructor(task)
		{
			this.task = task;
			this.counter = task._counter;
			this.fieldChangesTracker = task._fieldChangesTracker;

			this.set(Action.getDefaultData());
		}

		get()
		{
			return {
				[Action.types.changeDeadline]: this.canChangeDeadline,
				[Action.types.changeResponsible]: (this.canEdit || this.task.currentUser.isAdmin || this.task.isCreator()),
				[Action.types.changeGroup]: this.canEdit,
				[Action.types.delegate]: this.canDelegate,
				[Action.types.unfollow]: this.task.isAuditor(),
				[Action.types.startTimer]: this.canStartTimer,
				[Action.types.pauseTimer]: this.canPauseTimer,
				[Action.types.start]: this.canStart,
				[Action.types.pause]: this.canPause,
				[Action.types.complete]: this.canComplete,
				[Action.types.renew]: this.canRenew,
				[Action.types.approve]: this.canApprove,
				[Action.types.disapprove]: this.canDisapprove,
				[Action.types.defer]: this.canDefer,
				[Action.types.edit]: this.canEdit,
				[Action.types.remove]: this.canRemove,
				[Action.types.pin]: !this.task.isPinned,
				[Action.types.unpin]: this.task.isPinned,
				[Action.types.mute]: !this.task.isMuted,
				[Action.types.unmute]: this.task.isMuted,
				[Action.types.read]: true,
				[Action.types.ping]: true,
				[Action.types.addTask]: true,
				[Action.types.addSubTask]: true,
				[Action.types.share]: true,
				[Action.types['favorite.add']]: this.canAddToFavorite,
				[Action.types['favorite.delete']]: this.canRemoveFromFavorite,
			};
		}

		set(actions)
		{
			this.actions = actions;

			this.canChangeDeadline = (actions.changeDeadline || false);
			this.canDelegate = (actions.delegate || false);
			this.canStartTimer = ((actions['dayplan.timer.toggle'] && !this.task.isTimerRunningForCurrentUser) || false);
			this.canPauseTimer = ((actions['dayplan.timer.toggle'] && this.task.isTimerRunningForCurrentUser) || false);
			this.canStart = ((actions.start && !actions['dayplan.timer.toggle']) || false);
			this.canPause = ((actions.pause && !actions['dayplan.timer.toggle']) || false);
			this.canComplete = (actions.complete || false);
			this.canRenew = (actions.renew || false);
			this.canApprove = (actions.approve || false);
			this.canDisapprove = (actions.disapprove || false);
			this.canDefer = (actions.defer || false);
			this.canEdit = (actions.edit || false);
			this.canRemove = (actions.remove || false);
			this.canAddToFavorite = (actions['favorite.add'] || false);
			this.canRemoveFromFavorite = (actions['favorite.delete'] || false);
		}

		updateActions(actions)
		{
			const has = Object.prototype.hasOwnProperty;

			if (has.call(actions, 'canChangeDeadline'))
			{
				this.canChangeDeadline = actions.canChangeDeadline;
			}

			if (has.call(actions, 'canDelegate'))
			{
				this.canDelegate = actions.canDelegate;
			}

			if (has.call(actions, 'canStartTimer'))
			{
				this.canStartTimer = actions.canStartTimer;
			}

			if (has.call(actions, 'canPauseTimer'))
			{
				this.canPauseTimer = actions.canPauseTimer;
			}

			if (has.call(actions, 'canStart'))
			{
				this.canStart = actions.canStart;
			}

			if (has.call(actions, 'canPause'))
			{
				this.canPause = actions.canPause;
			}

			if (has.call(actions, 'canComplete'))
			{
				this.canComplete = actions.canComplete;
			}

			if (has.call(actions, 'canRenew'))
			{
				this.canRenew = actions.canRenew;
			}

			if (has.call(actions, 'canApprove'))
			{
				this.canApprove = actions.canApprove;
			}

			if (has.call(actions, 'canDisapprove'))
			{
				this.canDisapprove = actions.canDisapprove;
			}

			if (has.call(actions, 'canDefer'))
			{
				this.canDefer = actions.canDefer;
			}

			if (has.call(actions, 'canEdit'))
			{
				this.canEdit = actions.canEdit;
			}

			if (has.call(actions, 'canRemove'))
			{
				this.canRemove = actions.canRemove;
			}

			if (has.call(actions, 'canAddToFavorite'))
			{
				this.canAddToFavorite = actions.canAddToFavorite;
			}

			if (has.call(actions, 'canRemoveFromFavorite'))
			{
				this.canRemoveFromFavorite = actions.canRemoveFromFavorite;
			}
		}

		exportProperties()
		{
			return {
				actions: this.actions,
				canChangeDeadline: this.canChangeDeadline,
				canDelegate: this.canDelegate,
				canStartTimer: this.canStartTimer,
				canPauseTimer: this.canPauseTimer,
				canStart: this.canStart,
				canPause: this.canPause,
				canComplete: this.canComplete,
				canRenew: this.canRenew,
				canApprove: this.canApprove,
				canDisapprove: this.canDisapprove,
				canDefer: this.canDefer,
				canEdit: this.canEdit,
				canRemove: this.canRemove,
				canAddToFavorite: this.canAddToFavorite,
				canRemoveFromFavorite: this.canRemoveFromFavorite,
			};
		}

		importProperties(properties)
		{
			this.actions = properties.actions;
			this.canChangeDeadline = properties.canChangeDeadline;
			this.canDelegate = properties.canDelegate;
			this.canStartTimer = properties.canStartTimer;
			this.canPauseTimer = properties.canPauseTimer;
			this.canStart = properties.canStart;
			this.canPause = properties.canPause;
			this.canComplete = properties.canComplete;
			this.canRenew = properties.canRenew;
			this.canApprove = properties.canApprove;
			this.canDisapprove = properties.canDisapprove;
			this.canDefer = properties.canDefer;
			this.canEdit = properties.canEdit;
			this.canRemove = properties.canRemove;
			this.canAddToFavorite = properties.canAddToFavorite;
			this.canRemoveFromFavorite = properties.canRemoveFromFavorite;
		}

		save()
		{
			if (this.task.isNewRecord)
			{
				return this.add();
			}

			return this.update();
		}

		add()
		{
			Console.log('Create task');

			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.add', {
					fields: {
						...this.getFieldsToSave(),
						GUID: this.task.guid,
					},
					params: {
						PLATFORM: 'mobile',
					},
				}))
					.call()
					.then(
						(response) => {
							Console.log(response);

							const { task } = response.result;
							this.task.isNewRecord = false;
							this.task.id = task.id;
							this.set(task.action);

							resolve(response);
						},
						(response) => {
							Console.log(response);
							Notify.showMessage(
								response.error.description.replaceAll(/<\/?[^>]+(>|$)/g, ''),
								'',
								{
									time: 5,
								},
							);
							reject(response);
						},
					)
					.catch((response) => reject(response))
				;
			});
		}

		async update()
		{
			try
			{
				const updateTaskResponse = await this.updateTask();
				const updateStageResponse = await this.updateStage();

				return updateTaskResponse;
			}
			catch (error)
			{
				console.error(error);

				throw error;
			}
		}

		updateTask()
		{
			Console.log('Update task');

			return new Promise((resolve, reject) => {
				const fieldsToSave = this.getFieldsToSave();
				if (Object.keys(fieldsToSave).length === 0)
				{
					resolve();
				}
				else
				{
					(new RequestExecutor('tasks.task.update', {
						taskId: this.task.id,
						fields: fieldsToSave,
					}))
						.call()
						.then(
							(response) => {
								Console.log(response);

								const { task } = response.result;
								this.task.updateData(task);

								resolve(response);
							},
							(response) => {
								Console.log(response);
								reject(response);
							},
						)
						.catch((response) => reject(response))
					;
				}
			});
		}

		updateStage()
		{
			const fieldsToSave = (
				this.fieldChangesTracker.isEnabled
					? this.fieldChangesTracker.getChangedFields()
					: Object.values(Task.fields)
			);

			if (fieldsToSave.includes(Task.fields.stageId))
			{
				return new Promise((resolve, reject) => {
					BX.ajax.runAction('tasksmobile.Task.updateProjectKanbanTaskStage', {
						data: {
							id: this.task.id,
							stageId: this.task.currentStageId,
							projectId: this.task.groupId,
							order: 'ACTIVITY',
							extra: {
								filterParams: {},
							},
							searchParams: {
								ownerId: this.task.currentUser.id,
							},
						},
					})
						.then((response) => {
							resolve(response);
						})
						.catch((response) => {
							reject(response);
						});
				});
			}

			return Promise.resolve();
		}

		getFieldsToSave()
		{
			const fieldsValueGetters = {
				[Task.fields.title]: () => (Type.isUndefined(this.task.title) ? {} : { TITLE: this.task.title }),
				[Task.fields.description]: () => (Type.isUndefined(this.task.description) ? {} : { DESCRIPTION: this.task.description }),
				[Task.fields.status]: () => (Type.isUndefined(this.task.status) ? {} : { STATUS: this.task.status }),
				[Task.fields.group]: () => (Type.isUndefined(this.task.groupId) ? {} : { GROUP_ID: (this.task.groupId || 0) }),
				[Task.fields.priority]: () => (Type.isUndefined(this.task.priority) ? {} : { PRIORITY: this.task.priority }),
				[Task.fields.timeEstimate]: () => (Type.isUndefined(this.task.timeEstimate) ? {} : { TIME_ESTIMATE: this.task.timeEstimate }),

				[Task.fields.creator]: () => (Type.isUndefined(this.task.creator) ? {} : { CREATED_BY: this.task.creator.id }),
				[Task.fields.responsible]: () => (Type.isUndefined(this.task.responsible) ? {} : { RESPONSIBLE_ID: this.task.responsible.id }),
				[Task.fields.accomplices]: () => (Type.isUndefined(this.task.accomplices) ? {} : {
					ACCOMPLICES: Object.keys(this.task.accomplices),
				}),
				[Task.fields.auditors]: () => (Type.isUndefined(this.task.auditors) ? {} : { AUDITORS: Object.keys(this.task.auditors) }),

				[Task.fields.deadline]: () => (Type.isUndefined(this.task.deadline) ? {} : {
					DEADLINE: (this.task.deadline ? (new Date(this.task.deadline)).toISOString() : ''),
				}),
				[Task.fields.startDatePlan]: () => (Type.isUndefined(this.task.startDatePlan) ? {} : {
					START_DATE_PLAN: (this.task.startDatePlan ? (new Date(this.task.startDatePlan)).toISOString() : ''),
				}),
				[Task.fields.endDatePlan]: () => (Type.isUndefined(this.task.endDatePlan) ? {} : {
					END_DATE_PLAN: (this.task.endDatePlan ? (new Date(this.task.endDatePlan)).toISOString() : ''),
				}),

				[Task.fields.allowChangeDeadline]: () => (Type.isUndefined(this.task.allowChangeDeadline) ? {} : { ALLOW_CHANGE_DEADLINE: (this.task.allowChangeDeadline ? 'Y' : 'N') }),
				[Task.fields.isMatchWorkTime]: () => (Type.isUndefined(this.task.isMatchWorkTime) ? {} : { MATCH_WORK_TIME: (this.task.isMatchWorkTime ? 'Y' : 'N') }),
				[Task.fields.allowTaskControl]: () => (Type.isUndefined(this.task.allowTaskControl) ? {} : { TASK_CONTROL: (this.task.allowTaskControl ? 'Y' : 'N') }),
				[Task.fields.allowTimeTracking]: () => (Type.isUndefined(this.task.allowTimeTracking) ? {} : { ALLOW_TIME_TRACKING: (this.task.allowTimeTracking ? 'Y' : 'N') }),

				[Task.fields.isResultRequired]: () => (Type.isUndefined(this.task.isResultRequired) ? {} : {
					SE_PARAMETER: [
						{
							CODE: Task.parameterCodes.isResultRequired,
							VALUE: (this.task.isResultRequired ? 'Y' : 'N'),
						},
					],
				}),

				[Task.fields.mark]: () => (Type.isUndefined(this.task.mark) ? {} : { MARK: (this.task.mark === Task.mark.none ? '' : this.task.mark) }),
				[Task.fields.tags]: () => (Type.isUndefined(this.task.tags) ? {} : {
					TAGS: Object.values(this.task.tags).map((tag) => tag.title),
				}),
				[Task.fields.crm]: () => (Type.isUndefined(this.task.crm) ? {} : { CRM: (Object.keys(this.task.crm).length > 0 ? this.task.crm : []) }),
				[Task.fields.uploadedFiles]: () => (Type.isUndefined(this.task.uploadedFiles) ? {} : {
					UPLOADED_FILES: this.task.uploadedFiles.map((file) => file.token),
				}),
				[Task.fields.files]: () => (Type.isUndefined(this.task.files) ? {} : {
					UF_TASK_WEBDAV_FILES: this.task.files.map((file) => file.id),
				}),
				[Task.fields.parentTask]: () => (Type.isUndefined(this.task.parentId) ? {} : { PARENT_ID: (this.task.parentId || 0) }),
			};
			const fieldsToSave = (
				this.fieldChangesTracker.isEnabled
					? this.fieldChangesTracker.getChangedFields()
					: Object.values(Task.fields)
			);

			return fieldsToSave.reduce((accumulator, field) => {
				if (Object.keys(fieldsValueGetters).includes(field))
				{
					return {
						...accumulator,
						...fieldsValueGetters[field](),
					};
				}

				return accumulator;
			}, {});
		}

		remove()
		{
			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.delete', { taskId: this.task.id }))
					.call()
					.then(
						(response) => {
							Console.log(response);
							resolve(response);
						},
						(response) => {
							Console.log(response);
							reject(response);
						},
					)
					.catch((response) => reject(response))
				;
			});
		}

		saveDeadline()
		{
			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.update', {
					taskId: this.task.id,
					fields: {
						DEADLINE: (this.task.deadline ? (new Date(this.task.deadline)).toISOString() : ''),
					},
				}))
					.call()
					.then(
						(response) => {
							Console.log(response);
							resolve(response);
						},
						(response) => {
							Console.log(response);
							reject(response);
						},
					)
					.catch((response) => reject(response))
				;
			});
		}

		delegate()
		{
			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.delegate', {
					taskId: this.task.id,
					userId: this.task.responsible.id,
					params: {
						PLATFORM: 'mobile',
					},
				}))
					.call()
					.then(
						(response) => {
							Console.log(response);

							const { task } = response.result;
							this.task.auditors = task.auditors;
							this.set(task.action);

							resolve(response);
						},
						(response) => {
							Console.log(response);
							reject(response);
						},
					)
					.catch((response) => reject(response))
				;
			});
		}

		stopWatch()
		{
			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.stopWatch', { taskId: this.task.id }))
					.call()
					.then(
						(response) => {
							Console.log(response);
							this.set(response.result.task.action);
							resolve(response);
						},
						(response) => {
							Console.log(response);
							reject(response);
						},
					)
					.catch((response) => reject(response))
				;
			});
		}

		startTimer()
		{
			this.task.isTimerRunningForCurrentUser = true;

			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.startTimer', { taskId: this.task.id }))
					.call()
					.then(
						(response) => {
							Console.log(response);

							const { task } = response.result;
							this.task.isTimerRunningForCurrentUser = (task.timerIsRunningForCurrentUser === 'Y');
							this.set(task.action);

							resolve(response);
						},
						(response) => {
							Console.log(response);

							Notify.showMessage(
								response.error.description.replaceAll(/<\/?[^>]+(>|$)/g, ''),
								'',
								{
									time: 5,
								},
							);
							this.task.isTimerRunningForCurrentUser = false;

							reject(response);
						},
					)
					.catch((response) => reject(response))
				;
			});
		}

		pauseTimer()
		{
			this.task.isTimerRunningForCurrentUser = false;

			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.pauseTimer', { taskId: this.task.id }))
					.call()
					.then(
						(response) => {
							Console.log(response);

							const { task } = response.result;
							this.task.isTimerRunningForCurrentUser = (task.timerIsRunningForCurrentUser === 'Y');
							this.set(task.action);

							resolve(response);
						},
						(response) => {
							Console.log(response);

							Notify.showMessage(
								response.error.description.replaceAll(/<\/?[^>]+(>|$)/g, ''),
								'',
								{
									time: 5,
								},
							);
							this.task.isTimerRunningForCurrentUser = true;

							reject(response);
						},
					)
					.catch((response) => reject(response))
				;
			});
		}

		start()
		{
			this.task.status = TaskStatus.IN_PROGRESS;

			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.start', { taskId: this.task.id }))
					.call()
					.then(
						(response) => {
							Console.log(response);
							this.set(response.result.task.action);
							resolve(response);
						},
						(response) => {
							Console.log(response);
							reject(response);
						},
					)
					.catch((response) => reject(response))
				;
			});
		}

		pause()
		{
			this.task.status = TaskStatus.PENDING;

			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.pause', { taskId: this.task.id }))
					.call()
					.then(
						(response) => {
							Console.log(response);
							this.set(response.result.task.action);
							resolve(response);
						},
						(response) => {
							Console.log(response);
							reject(response);
						},
					)
					.catch((response) => reject(response))
				;
			});
		}

		complete()
		{
			const oldStatus = this.task.status;
			this.task.status = TaskStatus.COMPLETED;
			if (this.task.allowTaskControl && !this.task.isPureCreator())
			{
				this.task.status = TaskStatus.SUPPOSEDLY_COMPLETED;
			}

			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.complete', {
					taskId: this.task.id,
					params: {
						HIDE: false,
						PLATFORM: 'mobile',
					},
				}))
					.call()
					.then(
						(response) => {
							Console.log(response);
							this.set(response.result.task.action);
							resolve(response);
						},
						(response) => {
							Console.log(response);
							Notify.showMessage(
								response.error.description.replaceAll(/<\/?[^>]+(>|$)/g, ''),
								'',
								{
									time: 5,
								},
							);
							if (this.task.isResultRequired && !this.task.isOpenResultExists)
							{
								this.task.status = oldStatus;
							}
							reject(response);
						},
					)
					.catch((response) => reject(response))
				;
			});
		}

		renew()
		{
			this.task.status = TaskStatus.PENDING;

			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.renew', {
					taskId: this.task.id,
					params: {
						HIDE: false,
					},
				}))
					.call()
					.then(
						(response) => {
							Console.log(response);
							this.set(response.result.task.action);
							resolve(response);
						},
						(response) => {
							Console.log(response);
							reject(response);
						},
					)
					.catch((response) => reject(response))
				;
			});
		}

		approve()
		{
			this.task.status = TaskStatus.COMPLETED;

			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.approve', { taskId: this.task.id }))
					.call()
					.then(
						(response) => {
							Console.log(response);
							this.set(response.result.task.action);
							resolve(response);
						},
						(response) => {
							Console.log(response);
							reject(response);
						},
					)
					.catch((response) => reject(response))
				;
			});
		}

		disapprove()
		{
			this.task.status = TaskStatus.PENDING;

			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.disapprove', { taskId: this.task.id }))
					.call()
					.then(
						(response) => {
							Console.log(response);
							this.set(response.result.task.action);
							resolve(response);
						},
						(response) => {
							Console.log(response);
							reject(response);
						},
					)
					.catch((response) => reject(response))
				;
			});
		}

		pin()
		{
			this.task.isPinned = true;

			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.pin', { taskId: this.task.id }))
					.call()
					.then(
						(response) => {
							Console.log(response);
							this.task.isPinned = true;
							resolve(response);
						},
						(response) => {
							Console.log(response);
							this.task.isPinned = false;
							reject(response);
						},
					)
					.catch((response) => reject(response))
				;
			});
		}

		unpin()
		{
			this.task.isPinned = false;

			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.unpin', { taskId: this.task.id }))
					.call()
					.then(
						(response) => {
							Console.log(response);
							this.task.isPinned = false;
							resolve(response);
						},
						(response) => {
							Console.log(response);
							this.task.isPinned = true;
							reject(response);
						},
					)
					.catch((response) => reject(response))
				;
			});
		}

		mute()
		{
			this.task.isMuted = true;

			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.mute', { taskId: this.task.id }))
					.call()
					.then(
						(response) => {
							Console.log(response);
							this.task.isMuted = true;
							resolve(response);
						},
						(response) => {
							Console.log(response);
							this.task.isMuted = false;
							reject(response);
						},
					)
					.catch((response) => reject(response))
				;
			});
		}

		unmute()
		{
			this.task.isMuted = false;

			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.unmute', { taskId: this.task.id }))
					.call()
					.then(
						(response) => {
							Console.log(response);
							this.task.isMuted = false;
							resolve(response);
						},
						(response) => {
							Console.log(response);
							this.task.isMuted = true;
							reject(response);
						},
					)
					.catch((response) => reject(response))
				;
			});
		}

		pseudoRead()
		{
			this.counter.read();
		}

		read()
		{
			this.pseudoRead();

			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.view.update', { taskId: this.task.id }))
					.call()
					.then(
						(response) => {
							Console.log(response);
							resolve(response);
						},
						(response) => {
							Console.log(response);
							reject(response);
						},
					)
					.catch((response) => reject(response))
				;
			});
		}

		ping()
		{
			showToast({
				message: Loc.getMessage('MOBILE_TASKS_TASK_CARD_VIEW_ACTION_PING_NOTIFICATION'),
				svg: {
					url: `${iconPrefix}ping.svg`,
				},
			});

			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.ping', { taskId: this.task.id }))
					.call()
					.then(
						(response) => {
							Console.log(response);
							resolve(response);
						},
						(response) => {
							Console.log(response);
							reject(response);
						},
					)
					.catch((response) => reject(response))
				;
			});
		}

		addToFavorite()
		{
			this.canAddToFavorite = false;
			this.canRemoveFromFavorite = true;

			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.favorite.add', { taskId: this.task.id }))
					.call()
					.then(
						(response) => {
							Console.log(response);
							this.canAddToFavorite = false;
							this.canRemoveFromFavorite = true;
							resolve(response);
						},
						(response) => {
							Console.log(response);
							this.canAddToFavorite = true;
							this.canRemoveFromFavorite = false;
							reject(response);
						},
					)
					.catch((response) => reject(response))
				;
			});
		}

		removeFromFavorite()
		{
			this.canAddToFavorite = true;
			this.canRemoveFromFavorite = false;

			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.favorite.remove', { taskId: this.task.id }))
					.call()
					.then(
						(response) => {
							Console.log(response);
							this.canAddToFavorite = true;
							this.canRemoveFromFavorite = false;
							resolve(response);
						},
						(response) => {
							Console.log(response);
							this.canAddToFavorite = false;
							this.canRemoveFromFavorite = true;
							reject(response);
						},
					)
					.catch((response) => reject(response))
				;
			});
		}

		open(parentWidget, context, params = {})
		{
			const taskId = this.task.id;
			const taskData = {
				taskId,
				id: taskId,
				title: (this.task.title || 'TASK'),
			};
			const { analyticsLabel = {}, view, kanbanOwnerId } = params;

			const newParams = {
				parentWidget,
				context,
				view,
				kanbanOwnerId,
				userId: this.task.currentUser.id,
				taskObject: (this.task.canSendMyselfOnOpen ? this.task.exportProperties() : null),
				analyticsLabel,
			};

			Entry.openTask(taskData, newParams);
		}
	}

	class State
	{
		static checkMatchDates(date, datesToMatch)
		{
			let isMatch = false;

			datesToMatch.forEach((dateToMatch) => {
				if (!isMatch)
				{
					isMatch = (
						date.getDate() === dateToMatch.getDate()
						&& date.getMonth() === dateToMatch.getMonth()
						&& date.getFullYear() === dateToMatch.getFullYear()
					);
				}
			});

			return isMatch;
		}

		/**
		 * @param {Task} task
		 */
		constructor(task)
		{
			this.task = task;
		}

		get()
		{
			const states = this.getList();
			let currentState = null;

			Object.keys(states).forEach((key) => {
				if (currentState === null && this[key])
				{
					currentState = states[key];
				}
			});

			return currentState;
		}

		getList()
		{
			const statePrefix = 'MOBILE_TASKS_TASK_CARD_DEADLINE_STATE';
			const deadline = new Date(this.task.deadline);
			const deadlineTime = deadline.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

			return {
				isCompleted: {
					message: '',
					fontColor: '',
					backgroundColor: '',
				},
				isDeferred: {
					message: Loc.getMessage('MOBILE_TASKS_TASK_CARD_STATE_DEFERRED'),
					fontColor: AppTheme.colors.base1,
					backgroundColor: AppTheme.colors.base8,
					border: {
						color: AppTheme.colors.base4,
						width: 2,
					},
				},
				isSupposedlyCompleted: {
					message: Loc.getMessage('MOBILE_TASKS_TASK_CARD_STATE_SUPPOSEDLY_COMPLETED'),
					fontColor: AppTheme.colors.base1,
					backgroundColor: AppTheme.colors.base8,
					border: {
						color: AppTheme.colors.accentMainWarning,
						width: 2,
					},
				},
				isExpired: {
					message: this.getExpiredTimeText(),
					fontColor: AppTheme.colors.base8,
					backgroundColor: AppTheme.colors.accentMainAlert,
				},
				isToday: {
					// eslint-disable-next-line sonarjs/no-nested-template-literals
					message: `${Loc.getMessage(`${statePrefix}_TODAY`)} ${deadlineTime}`,
					fontColor: AppTheme.colors.base8,
					backgroundColor: AppTheme.colors.accentMainWarning,
				},
				isTomorrow: {
					message: Loc.getMessage(`${statePrefix}_TOMORROW`),
					fontColor: AppTheme.colors.base8,
					backgroundColor: AppTheme.colors.accentMainSuccess,
				},
				isThisWeek: {
					message: Loc.getMessage(`${statePrefix}_THIS_WEEK`),
					fontColor: AppTheme.colors.base8,
					backgroundColor: AppTheme.colors.accentBrandBlue,
				},
				isNextWeek: {
					message: Loc.getMessage(`${statePrefix}_NEXT_WEEK`),
					fontColor: AppTheme.colors.base8,
					backgroundColor: AppTheme.colors.accentExtraAqua,
				},
				isWoDeadline: {
					message: Loc.getMessage(`${statePrefix}_NO_DEADLINE`),
					fontColor: AppTheme.colors.base3,
					backgroundColor: AppTheme.colors.base6,
				},
				isMoreThanTwoWeeks: {
					message: Loc.getMessage(`${statePrefix}_MORE_THAN_TWO_WEEKS`),
					fontColor: AppTheme.colors.base8,
					backgroundColor: AppTheme.colors.base5,
				},
			};
		}

		get isCompleted()
		{
			return this.task.isCompleted;
		}

		get isDeferred()
		{
			return this.task.isDeferred;
		}

		get isSupposedlyCompleted()
		{
			return this.task.isSupposedlyCompleted;
		}

		get isExpired()
		{
			return this.task.isExpired;
		}

		get isWoDeadline()
		{
			return this.task.isWoDeadline;
		}

		getExpiredTimeText()
		{
			const expiredStatePrefix = 'MOBILE_TASKS_TASK_CARD_DEADLINE_STATE_EXPIRED';
			const extensions = {
				year: {
					value: 31_536_000,
					message: Loc.getMessage(`${expiredStatePrefix}_YEAR`),
				},
				month: {
					value: 2_592_000,
					message: Loc.getMessage(`${expiredStatePrefix}_MONTH`),
				},
				week: {
					value: 604_800,
					message: Loc.getMessage(`${expiredStatePrefix}_WEEK`),
				},
				day: {
					value: 86400,
					message: Loc.getMessage(`${expiredStatePrefix}_DAY`),
				},
				hour: {
					value: 3600,
					message: Loc.getMessage(`${expiredStatePrefix}_HOUR`),
				},
				minute: {
					value: 60,
					message: Loc.getMessage(`${expiredStatePrefix}_MINUTE`),
				},
			};

			const date = new Date();
			let delta = (date.getTime() - this.task.deadline) / 1000;
			let expiredTime = false;

			Object.keys(extensions).forEach((key) => {
				const value = Math.floor(delta / extensions[key].value);
				if (!expiredTime && value >= 1)
				{
					expiredTime = extensions[key].message.replace('#TIME#', value);

					return;
				}
				delta -= value * extensions[key].value;
			});

			return (expiredTime || extensions.minute.message.replace('#TIME#', 1));
		}

		get isToday()
		{
			if (!this.task.deadline)
			{
				return false;
			}

			const deadline = new Date(this.task.deadline);
			const today = new Date();

			return (deadline && State.checkMatchDates(deadline, [today]));
		}

		get isTomorrow()
		{
			if (!this.task.deadline)
			{
				return false;
			}

			const deadline = new Date(this.task.deadline);
			const tomorrow = new Date();
			tomorrow.setDate(tomorrow.getDate() + 1);

			return (deadline && State.checkMatchDates(deadline, [tomorrow]));
		}

		get isThisWeek()
		{
			if (!this.task.deadline)
			{
				return false;
			}

			const deadline = new Date(this.task.deadline);
			const today = new Date();
			const thisWeekDays = [];

			for (let i = 1; i <= 7; i++)
			{
				const first = today.getDate() - today.getDay() + i;
				const day = new Date(today.setDate(first));
				thisWeekDays.push(day);
			}

			return (deadline && State.checkMatchDates(deadline, thisWeekDays));
		}

		get isNextWeek()
		{
			if (!this.task.deadline)
			{
				return false;
			}

			const deadline = new Date(this.task.deadline);
			const nextWeekDays = [];
			const nextWeekDay = new Date();
			nextWeekDay.setDate(nextWeekDay.getDate() + 7);

			for (let i = 1; i <= 7; i++)
			{
				const first = nextWeekDay.getDate() - nextWeekDay.getDay() + i;
				const day = new Date(nextWeekDay.setDate(first));
				nextWeekDays.push(day);
			}

			return (deadline && State.checkMatchDates(deadline, nextWeekDays));
		}

		get isMoreThanTwoWeeks()
		{
			return true;
		}
	}

	class FieldChangesTracker
	{
		static isValidField(field)
		{
			return Object.values(Task.fields).includes(field);
		}

		/**
		 * @param {Task} task
		 */
		constructor(task)
		{
			this.task = task;

			this.isEnabled = false;
			this.changedFields = new Set();
		}

		addChangedFields(fields)
		{
			const arrayedFields = (Type.isArray(fields) ? fields : [fields]);

			arrayedFields.forEach((field) => {
				if (FieldChangesTracker.isValidField(field) && !this.changedFields.has(field))
				{
					this.changedFields.add(field);
				}
			});
		}

		removeChangedFields(fields)
		{
			const arrayedFields = (Type.isArray(fields) ? fields : [fields]);

			arrayedFields.forEach((field) => {
				if (this.isFieldChanged(field))
				{
					this.changedFields.delete(field);
				}
			});
		}

		isFieldChanged(field)
		{
			return (FieldChangesTracker.isValidField(field) && this.changedFields.has(field));
		}

		getChangedFields()
		{
			return [...this.changedFields];
		}

		clearChangedFields()
		{
			this.changedFields.clear();
		}

		get isEnabled()
		{
			return this._isEnabled;
		}

		set isEnabled(isEnabled)
		{
			this._isEnabled = isEnabled;
		}
	}

	class Task
	{
		static get userOptions()
		{
			return {
				muted: 1,
				pinned: 2,
			};
		}

		static get parameterCodes()
		{
			return {
				isResultRequired: 3,
			};
		}

		static get counterColors()
		{
			return {
				danger: AppTheme.colors.accentMainAlert,
				gray: AppTheme.colors.base4,
				success: AppTheme.colors.accentMainSuccess,
			};
		}

		static get backgroundColors()
		{
			return {
				default: AppTheme.colors.bgContentPrimary,
				pinned: AppTheme.colors.bgContentTertiary,
				blinking: AppTheme.colors.accentSoftGreen3,
			};
		}

		static get actions()
		{
			const titlePrefix = 'MOBILE_TASKS_TASK_CARD_VIEW_ACTION';

			return {
				more: {
					identifier: 'more',
					title: Loc.getMessage(`${titlePrefix}_MORE`),
					iconName: 'more',
					color: AppTheme.colors.base3,
					position: 'right',
				},
				cancel: {
					id: 'cancel',
					title: Loc.getMessage(`${titlePrefix}_CANCEL`),
					textColor: AppTheme.colors.base3,
					sectionCode: 'default',
					showTopSeparator: true,
				},
			};
		}

		static get deadlines()
		{
			const statePrefix = 'MOBILE_TASKS_TASK_CARD_DEADLINE_STATE';

			return {
				today: {
					name: Loc.getMessage(`${statePrefix}_TODAY`),
				},
				tomorrow: {
					name: Loc.getMessage(`${statePrefix}_TOMORROW`),
				},
				thisWeek: {
					name: Loc.getMessage(`${statePrefix}_THIS_WEEK`),
				},
				nextWeek: {
					name: Loc.getMessage(`${statePrefix}_NEXT_WEEK`),
				},
				moreThanTwoWeeks: {
					name: Loc.getMessage(`${statePrefix}_MORE_THAN_TWO_WEEKS`),
				},
			};
		}

		static get popupImageUrls()
		{
			const urlPrefix = `${pathToExtension}images/mobile-task-popup-`;
			const urlPostfix = '.png';
			const names = {
				ping: 'ping',
				changeDeadline: 'deadline',
				approve: 'approve',
				disapprove: 'disapprove',
				startTimer: 'start',
				pauseTimer: 'pause',
				start: 'start',
				pause: 'pause',
				renew: 'renew',
				changeResponsible: 'responsible',
				delegate: 'delegate',
				changeGroup: 'group',
				mute: 'mute',
				unmute: 'unmute',
				share: 'share',
				unfollow: 'unfollow',
				remove: 'remove',
				read: 'read',
				pin: 'pin',
				unpin: 'unpin',
			};
			const urls = {};

			Object.keys(names).forEach((key) => {
				urls[key] = `${urlPrefix}${names[key]}${urlPostfix}`;
			});

			return urls;
		}

		static get priority()
		{
			return {
				important: '2',
				none: '1',
			};
		}

		static get mark()
		{
			return {
				positive: 'P',
				negative: 'N',
				none: null,
			};
		}

		static get fields()
		{
			return {
				id: 'id',
				guid: 'guid',
				title: 'title',
				description: 'description',
				group: 'group',
				timeElapsed: 'timeElapsed',
				timeEstimate: 'timeEstimate',
				commentsCount: 'commentsCount',
				serviceCommentsCount: 'serviceCommentsCount',

				status: 'status',
				subStatus: 'subStatus',
				priority: 'priority',
				mark: 'mark',

				creator: 'creator',
				responsible: 'responsible',
				accomplices: 'accomplices',
				auditors: 'auditors',

				crm: 'crm',
				tags: 'tags',
				files: 'files',
				uploadedFiles: 'uploadedFiles',
				relatedTasks: 'relatedTasks',
				subTasks: 'subTasks',
				parentTask: 'parentTask',

				isMuted: 'isMuted',
				isPinned: 'isPinned',
				isResultRequired: 'isResultRequired',
				isResultExists: 'isResultExists',
				isOpenResultExists: 'isOpenResultExists',
				isMatchWorkTime: 'isMatchWorkTime',
				allowChangeDeadline: 'allowChangeDeadline',
				allowTimeTracking: 'allowTimeTracking',
				allowTaskControl: 'allowTaskControl',
				isTimerRunningForCurrentUser: 'isTimerRunningForCurrentUser',

				deadline: 'deadline',
				activityDate: 'activityDate',
				startDatePlan: 'startDatePlan',
				endDatePlan: 'endDatePlan',

				checkList: 'checkList',
				stageId: 'stageId',
			};
		}

		constructor(currentUser)
		{
			this.currentUser = currentUser;

			this._counter = new Counter(this);
			this._fieldChangesTracker = new FieldChangesTracker(this);
			this._actions = new Action(this);
			this._state = new State(this);

			this.setDefaultData();

			this.isNewRecord = true;
		}

		setDefaultData()
		{
			this.id = `tmp-id-${Date.now()}`;
			this.temporaryId = undefined;
			this.guid = undefined;
			this.title = undefined;
			this.description = undefined;
			this.parsedDescription = undefined;
			this.groupId = undefined;
			this.group = undefined;
			this.timeElapsed = undefined;
			this.timeEstimate = undefined;
			this.commentsCount = undefined;
			this.serviceCommentsCount = undefined;
			this.stageId = undefined;

			this.status = undefined;
			this.subStatus = undefined;
			this.priority = undefined;
			this.mark = undefined;

			this.creator = undefined;
			this.responsible = undefined;
			this.accomplices = undefined;
			this.auditors = undefined;
			this.relatedTasks = undefined;
			this.subTasks = undefined;

			this.crm = undefined;
			this.tags = undefined;
			this.files = undefined;
			this.uploadedFiles = undefined;
			this.parentId = undefined;
			this.parentTask = undefined;

			this.isMuted = undefined;
			this.isPinned = undefined;
			this.isResultRequired = undefined;
			this.isResultExists = undefined;
			this.isOpenResultExists = undefined;
			this.isMatchWorkTime = undefined;
			this.allowChangeDeadline = undefined;
			this.allowTimeTracking = undefined;
			this.allowTaskControl = undefined;
			this.isTimerRunningForCurrentUser = undefined;

			this.deadline = undefined;
			this.activityDate = undefined;
			this.startDatePlan = undefined;
			this.endDatePlan = undefined;

			this.counter = {};
			this.actions = {};

			this.canSendMyselfOnOpen = true;
			this.currentStageId = undefined;
		}

		setData(row)
		{
			const fieldMap = {
				id: () => row.id,
				temporaryId: () => row.temporaryId,
				title: () => row.title,
				name: () => row.name,
				description: () => row.description,
				parsedDescription: () => row.parsedDescription,
				groupId: () => row.groupId,
				group: () => row.group,
				timeElapsed: () => Number(row.timeElapsed),
				timeEstimate: () => Number(row.timeEstimate),
				commentsCount: () => Number(row.commentsCount),
				serviceCommentsCount: () => Number(row.serviceCommentsCount),
				status: () => Number(row.status),
				subStatus: () => Number(row.subStatus),
				priority: () => row.priority,
				mark: () => (row.mark === '' ? Task.mark.none : row.mark),
				creator: () => {
					this.tryUpdateCurrentUserIcon(row.creator);

					return row.creator;
				},
				responsible: () => {
					this.tryUpdateCurrentUserIcon(row.responsible);

					return row.responsible;
				},
				accomplices: {
					checker: () => !Type.isUndefined(row.accomplicesData),
					getter: () => (Type.isArray(row.accomplicesData) ? {} : row.accomplicesData),
				},
				auditors: {
					checker: () => !Type.isUndefined(row.auditorsData),
					getter: () => (Type.isArray(row.auditorsData) ? {} : row.auditorsData),
				},
				relatedTasks: () => (Type.isArray(row.relatedTasks) ? {} : row.relatedTasks),
				subTasks: () => (Type.isArray(row.subTasks) ? {} : row.subTasks),
				crm: () => (Type.isArray(row.crm) ? {} : row.crm),
				tags: () => (Type.isArray(row.tags) ? {} : row.tags),
				files: () => (row.files || []),
				uploadedFiles: () => (row.uploadedFiles || []),
				parentId: () => (Number(row.parentId) || 0),
				parentTask: () => row.parentTask,
				isMuted: () => (row.isMuted === 'Y'),
				isPinned: () => (row.isPinned === 'Y'),
				isResultRequired: {
					checker: () => !Type.isUndefined(row.taskRequireResult),
					getter: () => (row.taskRequireResult === 'Y'),
				},
				isResultExists: {
					checker: () => !Type.isUndefined(row.taskHasResult),
					getter: () => (row.taskHasResult === 'Y'),
				},
				isOpenResultExists: {
					checker: () => !Type.isUndefined(row.taskHasOpenResult),
					getter: () => (row.taskHasOpenResult === 'Y'),
				},
				isMatchWorkTime: {
					checker: () => !Type.isUndefined(row.matchWorkTime),
					getter: () => (row.matchWorkTime === 'Y'),
				},
				allowChangeDeadline: () => (row.allowChangeDeadline === 'Y'),
				allowTaskControl: {
					checker: () => !Type.isUndefined(row.taskControl),
					getter: () => (row.taskControl === 'Y'),
				},
				allowTimeTracking: () => (row.allowTimeTracking === 'Y'),
				isTimerRunningForCurrentUser: {
					checker: () => !Type.isUndefined(row.timerIsRunningForCurrentUser),
					getter: () => (row.timerIsRunningForCurrentUser === 'Y'),
				},
				deadline: () => (Date.parse(row.deadline) || null),
				activityDate: () => (Date.parse(row.activityDate) || null),
				startDatePlan: () => (Date.parse(row.startDatePlan) || null),
				endDatePlan: () => (Date.parse(row.endDatePlan) || null),
				counter: () => row.counter,
				actions: {
					checker: () => !Type.isUndefined(row.action),
					getter: () => row.action,
				},
				stageId: () => Number(row.stageId),
			};
			const baseChecker = (value) => !Type.isUndefined(value);

			Object.entries(fieldMap).forEach(([field, value]) => {
				const getter = (Type.isFunction(value) ? value : value.getter);
				const checker = (!Type.isFunction(value) && Type.isFunction(value.checker) ? value.checker : baseChecker);

				if (checker(row[field]))
				{
					this[field] = getter();
				}
			});
		}

		updateData(row)
		{
			const has = Object.prototype.hasOwnProperty;
			const fieldMap = {
				id: () => row.id,
				temporaryId: () => row.temporaryId,
				title: () => row.title,
				description: () => row.description,
				parsedDescription: () => row.parsedDescription,
				groupId: () => row.groupId,
				group: () => row.group,
				timeElapsed: () => Number(row.timeElapsed),
				timeEstimate: () => Number(row.timeEstimate),
				commentsCount: () => Number(row.commentsCount),
				serviceCommentsCount: () => Number(row.serviceCommentsCount),
				status: () => Number(row.status),
				subStatus: () => Number(row.subStatus),
				priority: () => row.priority,
				mark: () => (row.mark === '' ? Task.mark.none : row.mark),
				creator: () => {
					const { creator } = row;
					if (this.creator && this.creator.id === creator.id)
					{
						creator.name = (creator.name || this.creator.name);
						creator.icon = (creator.icon || this.creator.icon);
						creator.link = (creator.link || this.creator.link);
						creator.workPosition = (creator.workPosition || this.creator.workPosition);
					}
					this.tryUpdateCurrentUserIcon(creator);

					return creator;
				},
				responsible: () => {
					const { responsible } = row;
					if (this.responsible && this.responsible.id === responsible.id)
					{
						responsible.name = (responsible.name || this.responsible.name);
						responsible.icon = (responsible.icon || this.responsible.icon);
						responsible.link = (responsible.link || this.responsible.link);
						responsible.workPosition = (responsible.workPosition || this.responsible.workPosition);
					}
					this.tryUpdateCurrentUserIcon(responsible);

					return responsible;
				},
				accomplices: {
					checker: () => has.call(row, 'accomplicesData'),
					getter: () => {
						if (Type.isArray(row.accomplicesData))
						{
							return {};
						}

						const accomplices = {};
						Object.entries(row.accomplicesData).forEach(([id, user]) => {
							accomplices[id] = { id, ...user };
							if (this.accomplices && this.accomplices[id])
							{
								const currentUserData = this.accomplices[id];
								accomplices[id] = {
									id,
									name: (user.name || currentUserData.name),
									icon: (user.icon || currentUserData.icon),
									link: (user.link || currentUserData.link),
									workPosition: (user.workPosition || currentUserData.workPosition),
								};
							}
						});

						return accomplices;
					},
				},
				auditors: {
					checker: () => has.call(row, 'auditorsData'),
					getter: () => {
						if (Type.isArray(row.auditorsData))
						{
							return {};
						}

						const auditors = {};
						Object.entries(row.auditorsData).forEach(([id, user]) => {
							auditors[id] = { id, ...user };
							if (this.auditors && this.auditors[id])
							{
								const currentUserData = this.auditors[id];
								auditors[id] = {
									id,
									name: (user.name || currentUserData.name),
									icon: (user.icon || currentUserData.icon),
									link: (user.link || currentUserData.link),
									workPosition: (user.workPosition || currentUserData.workPosition),
								};
							}
						});

						return auditors;
					},
				},
				relatedTasks: () => (Type.isArray(row.relatedTasks) ? {} : row.relatedTasks),
				subTasks: () => (Type.isArray(row.subTasks) ? {} : row.subTasks),
				crm: () => (Type.isArray(row.crm) ? {} : row.crm),
				tags: () => (Type.isArray(row.tags) ? {} : row.tags),
				files: () => (row.files || []),
				uploadedFiles: () => (row.uploadedFiles || []),
				parentId: () => (Number(row.parentId) || 0),
				parentTask: () => row.parentTask,
				isMuted: () => (row.isMuted === 'Y'),
				isPinned: () => (row.isPinned === 'Y'),
				isResultRequired: {
					checker: () => has.call(row, 'taskRequireResult'),
					getter: () => (row.taskRequireResult === 'Y'),
				},
				isResultExists: {
					checker: () => has.call(row, 'taskHasResult'),
					getter: () => (row.taskHasResult === 'Y'),
				},
				isOpenResultExists: {
					checker: () => has.call(row, 'taskHasOpenResult'),
					getter: () => (row.taskHasOpenResult === 'Y'),
				},
				isMatchWorkTime: {
					checker: () => has.call(row, 'matchWorkTime'),
					getter: () => (row.matchWorkTime === 'Y'),
				},
				allowChangeDeadline: () => (row.allowChangeDeadline === 'Y'),
				allowTaskControl: {
					checker: () => has.call(row, 'taskControl'),
					getter: () => (row.taskControl === 'Y'),
				},
				allowTimeTracking: () => (row.allowTimeTracking === 'Y'),
				isTimerRunningForCurrentUser: {
					checker: () => has.call(row, 'timerIsRunningForCurrentUser'),
					getter: () => (row.timerIsRunningForCurrentUser === 'Y'),
				},
				deadline: () => (Date.parse(row.deadline) || null),
				activityDate: () => (Date.parse(row.activityDate) || null),
				startDatePlan: () => (Date.parse(row.startDatePlan) || null),
				endDatePlan: () => (Date.parse(row.endDatePlan) || null),
				counter: () => row.counter,
				actions: {
					checker: () => has.call(row, 'action'),
					getter: () => row.action,
				},
				stageId: () => row.stageId,
			};
			const baseChecker = (field) => has.call(row, field);

			Object.entries(fieldMap).forEach(([field, value]) => {
				const getter = (Type.isFunction(value) ? value : value.getter);
				const checker = (!Type.isFunction(value) && Type.isFunction(value.checker) ? value.checker : baseChecker);

				if (checker(field))
				{
					this[field] = getter();
				}
			});
		}

		exportProperties()
		{
			return {
				currentUser: this.currentUser,
				isNewRecord: this.isNewRecord,

				_id: this._id,
				_guid: this._guid,

				temporaryId: this.temporaryId,
				title: this.title,
				description: this.description,
				parsedDescription: this.parsedDescription,
				groupId: this.groupId,
				group: this.group,
				timeElapsed: this.timeElapsed,
				timeEstimate: this.timeEstimate,
				commentsCount: this.commentsCount,
				serviceCommentsCount: this.serviceCommentsCount,

				status: this.status,
				subStatus: this.subStatus,
				priority: this.priority,
				mark: this.mark,

				creator: this.creator,
				responsible: this.responsible,
				accomplices: this.accomplices,
				auditors: this.auditors,

				relatedTasks: this.relatedTasks,
				subTasks: this.subTasks,

				crm: this.crm,
				tags: this.tags,
				files: this.files,
				uploadedFiles: this.uploadedFiles,
				parentId: this.parentId,
				parentTask: this.parentTask,

				isMuted: this.isMuted,
				isPinned: this.isPinned,
				isResultRequired: this.isResultRequired,
				isResultExists: this.isResultExists,
				isOpenResultExists: this.isOpenResultExists,
				isMatchWorkTime: this.isMatchWorkTime,
				allowChangeDeadline: this.allowChangeDeadline,
				allowTaskControl: this.allowTaskControl,
				allowTimeTracking: this.allowTimeTracking,
				isTimerRunningForCurrentUser: this.isTimerRunningForCurrentUser,

				deadline: this.deadline,
				activityDate: this.activityDate,
				startDatePlan: this.startDatePlan,
				endDatePlan: this.endDatePlan,

				_counter: this._counter.exportProperties(),
				_actions: this._actions.exportProperties(),

				isCreator: this.isCreator(),
				isPureCreator: this.isPureCreator(),
				isResponsible: this.isResponsible(),
				isAccomplice: this.isAccomplice(),
				isAuditor: this.isAuditor(),
				isMember: this.isMember(),
				isSupposedlyCompleted: this.isSupposedlyCompleted,
				isSupposedlyCompletedCounts: this.isSupposedlyCompletedCounts,
				isCompleted: this.isCompleted,
				isCompletedCounts: this.isCompletedCounts,
				isDeferred: this.isDeferred,
				isWoDeadline: this.isWoDeadline,
				isExpired: this.isExpired,
			};
		}

		importProperties(properties)
		{
			const has = Object.prototype.hasOwnProperty;

			for (const key in properties)
			{
				if (key === '_counter')
				{
					this._counter.importProperties(properties[key]);
				}
				else if (key === '_actions')
				{
					this._actions.importProperties(properties[key]);
				}
				else if (has.call(this, key))
				{
					this[key] = properties[key];
				}
			}
		}

		tryUpdateCurrentUserIcon(user)
		{
			if (
				Number(this.currentUser.id) === Number(user?.id)
				&& this.currentUser.icon !== user.icon
			)
			{
				this.currentUser.icon = user.icon;
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
				return Math.floor((1 + Math.random()) * 0x10000).toString(16).slice(1);
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

		isCreator(userId = null)
		{
			return Number(userId || this.currentUser.id) === Number(this.creator.id);
		}

		isPureCreator(userId = null)
		{
			return (
				Number(userId || this.currentUser.id) === Number(this.creator.id)
				&& Number(userId || this.currentUser.id) !== Number(this.responsible.id)
			);
		}

		isResponsible(userId = null)
		{
			return Number(userId || this.currentUser.id) === Number(this.responsible.id);
		}

		isAccomplice(userId = null)
		{
			return Object.keys(this.accomplices).includes((userId || this.currentUser.id).toString());
		}

		isAuditor(userId = null)
		{
			return Object.keys(this.auditors).includes((userId || this.currentUser.id).toString());
		}

		isMember(userId = null)
		{
			return (
				this.isCreator(userId)
				|| this.isResponsible(userId)
				|| this.isAccomplice(userId)
				|| this.isAuditor(userId)
			);
		}

		get isSupposedlyCompleted()
		{
			return this.status === TaskStatus.SUPPOSEDLY_COMPLETED;
		}

		get isSupposedlyCompletedCounts()
		{
			return (this.isSupposedlyCompleted && this.isCreator() && !this.isResponsible());
		}

		get isCompleted()
		{
			return this.status === TaskStatus.COMPLETED;
		}

		get isCompletedCounts()
		{
			return (this.isCompleted || (this.isSupposedlyCompleted && !this.isCreator()));
		}

		get isDeferred()
		{
			return this.status === TaskStatus.DEFERRED;
		}

		get isWoDeadline()
		{
			return !this.deadline;
		}

		get isExpiredSoon()
		{
			return (!this.isWoDeadline && this.deadline - Date.now() < 86_400_000);
		}

		get isExpired()
		{
			return (!this.isWoDeadline && this.deadline <= Date.now());
		}

		// counters

		get counter()
		{
			return this._counter.get();
		}

		set counter(counter)
		{
			this._counter.set(counter);
		}

		getCounterValue()
		{
			return this.counter.value;
		}

		getCounterColor()
		{
			return this.counter.color;
		}

		getCounterMyExpiredCount()
		{
			return this.counter.counters[Counter.types.my.expired];
		}

		getCounterMyNewCommentsCount()
		{
			return this.counter.counters[Counter.types.my.newComments];
		}

		getCounterMyCount()
		{
			return (this.getCounterMyExpiredCount() + this.getCounterMyNewCommentsCount());
		}

		getNewCommentsCount()
		{
			return this._counter.getNewCommentsCount();
		}

		// taskInfo

		getTaskInfo(withActions = true)
		{
			const state = (this.getState() || this.getStateList().isCompleted);
			const taskInfo = {
				id: this.id,
				title: (this.title || ''),
				checked: this.isCompletedCounts,
				checkable: (
					this.actions[Action.types.complete]
					|| this.actions[Action.types.approve]
					|| this.actions[Action.types.renew]
				),
				state: state.message,
				date: this.activityDate / 1000,
				messageCount: this.getCounterValue(),
				creatorIcon: this.creator.icon,
				responsibleIcon: this.responsible.icon,
				actions: (withActions ? this.getSwipeActions() : []),
				project: {},
				params: {},
				styles: {
					state: {
						backgroundColor: state.backgroundColor,
						font: {
							color: state.fontColor,
							fontStyle: 'semibold',
						},
						border: state.border,
					},
					counter: {
						backgroundColor: Task.counterColors[this.getCounterColor()],
					},
					date: {
						image: {
							name: (this.isPinned ? 'message_pin' : ''),
						},
						font: {
							size: 13,
						},
					},
					title: {
						additionalImage: {
							name: (this.isMuted ? 'name_status_mute' : ''),
						},
					},
				},
			};

			if (this.groupId > 0)
			{
				taskInfo.project = {
					id: this.groupId,
					imageUrl: this.group.image,
				};
			}

			return taskInfo;
		}

		getState()
		{
			return this._state.get();
		}

		getStateList()
		{
			return this._state.getList();
		}

		getSwipeActions()
		{
			const titlePrefix = 'MOBILE_TASKS_TASK_CARD_VIEW_ACTION';
			const actions = {
				changeDeadline: {
					identifier: Action.types.changeDeadline,
					title: Loc.getMessage(`${titlePrefix}_CHANGE_DEADLINE`),
					iconName: 'action_term',
					color: AppTheme.colors.accentMainWarning,
					position: 'right',
				},
				approve: {
					identifier: Action.types.approve,
					title: Loc.getMessage(`${titlePrefix}_APPROVE`),
					iconName: 'action_accept',
					color: AppTheme.colors.accentExtraDarkblue,
					position: 'right',
				},
				disapprove: {
					identifier: Action.types.disapprove,
					title: Loc.getMessage(`${titlePrefix}_DISAPPROVE_MSGVER_2`),
					iconName: 'action_finish_up',
					color: AppTheme.colors.accentMainAlert,
					position: 'right',
				},
				changeResponsible: {
					identifier: Action.types.changeResponsible,
					title: Loc.getMessage(`${titlePrefix}_CHANGE_RESPONSIBLE`),
					iconName: 'action_userlist',
					color: AppTheme.colors.accentMainLinks,
					position: 'right',
				},
				delegate: {
					identifier: Action.types.delegate,
					title: Loc.getMessage(`${titlePrefix}_DELEGATE`),
					iconName: 'action_userlist',
					color: AppTheme.colors.accentMainLinks,
					position: 'right',
				},
				ping: {
					identifier: Action.types.ping,
					title: Loc.getMessage(`${titlePrefix}_PING`),
					iconName: 'action_ping',
					color: AppTheme.colors.accentExtraAqua,
					position: 'right',
				},
				share: {
					identifier: Action.types.share,
					title: Loc.getMessage(`${titlePrefix}_SHARE`),
					iconName: 'action_share',
					color: AppTheme.colors.base3,
					position: 'right',
				},
				changeGroup: {
					identifier: Action.types.changeGroup,
					title: Loc.getMessage(`${titlePrefix}_CHANGE_GROUP`),
					iconName: 'action_project',
					color: AppTheme.colors.accentExtraAqua,
					position: 'right',
				},
				startTimer: {
					identifier: Action.types.startTimer,
					title: Loc.getMessage(`${titlePrefix}_START`),
					iconName: 'action_start',
					color: AppTheme.colors.accentExtraAqua,
					position: 'right',
				},
				pauseTimer: {
					identifier: Action.types.pauseTimer,
					title: Loc.getMessage(`${titlePrefix}_PAUSE`),
					iconName: 'action_finish',
					color: AppTheme.colors.accentExtraAqua,
					position: 'right',
				},
				start: {
					identifier: Action.types.start,
					title: Loc.getMessage(`${titlePrefix}_START`),
					iconName: 'action_start',
					color: AppTheme.colors.accentExtraAqua,
					position: 'right',
				},
				pause: {
					identifier: Action.types.pause,
					title: Loc.getMessage(`${titlePrefix}_PAUSE`),
					iconName: 'action_finish',
					color: AppTheme.colors.accentExtraAqua,
					position: 'right',
				},
				renew: {
					identifier: Action.types.renew,
					title: Loc.getMessage(`${titlePrefix}_RENEW`),
					iconName: 'action_reload',
					color: AppTheme.colors.accentExtraAqua,
					position: 'right',
				},
				mute: {
					identifier: Action.types.mute,
					title: Loc.getMessage(`${titlePrefix}_MUTE`),
					iconName: 'action_mute',
					color: AppTheme.colors.accentMainSuccess,
					position: 'right',
				},
				unmute: {
					identifier: Action.types.unmute,
					title: Loc.getMessage(`${titlePrefix}_UNMUTE`),
					iconName: 'action_unmute',
					color: AppTheme.colors.accentMainSuccess,
					position: 'right',
				},
				unfollow: {
					identifier: Action.types.unfollow,
					title: Loc.getMessage(`${titlePrefix}_DONT_FOLLOW`),
					iconName: 'action_unfollow',
					color: AppTheme.colors.accentExtraBrown,
					position: 'right',
				},
				remove: {
					identifier: Action.types.remove,
					title: Loc.getMessage(`${titlePrefix}_REMOVE`),
					iconName: 'action_remove',
					color: AppTheme.colors.base3,
					position: 'right',
				},
				read: {
					identifier: Action.types.read,
					title: Loc.getMessage(`${titlePrefix}_READ`),
					iconName: 'action_read',
					color: AppTheme.colors.accentExtraPink,
					position: 'left',
				},
				pin: {
					identifier: Action.types.pin,
					title: Loc.getMessage(`${titlePrefix}_PIN`),
					iconName: 'action_pin',
					color: AppTheme.colors.accentExtraDarkblue,
					position: 'left',
				},
				unpin: {
					identifier: Action.types.unpin,
					title: Loc.getMessage(`${titlePrefix}_UNPIN`),
					iconName: 'action_unpin',
					color: AppTheme.colors.accentExtraDarkblue,
					position: 'left',
				},
			};
			const currentActions = [];

			Object.keys(actions).forEach((key) => {
				if (this.actions[key])
				{
					currentActions.push(actions[key]);
				}
			});

			if (this.actions[Action.types.changeResponsible] && this.actions[Action.types.delegate])
			{
				currentActions.splice(currentActions.findIndex((item) => item.identifier === Action.types.delegate), 1);
			}

			return currentActions;
		}

		// fieldChangesTracker

		enableFieldChangesTracker()
		{
			this._fieldChangesTracker.isEnabled = true;
		}

		disableFieldChangesTracker()
		{
			this._fieldChangesTracker.isEnabled = false;
		}

		addChangedFields(fields)
		{
			this._fieldChangesTracker.addChangedFields(fields);
		}

		removeChangedFields(fields)
		{
			this._fieldChangesTracker.removeChangedFields(fields);
		}

		isFieldChanged(field)
		{
			return this._fieldChangesTracker.isFieldChanged(field);
		}

		haveChangedFields()
		{
			return (this._fieldChangesTracker.getChangedFields().length > 0);
		}

		getChangedFields()
		{
			return this._fieldChangesTracker.getChangedFields();
		}

		clearChangedFields()
		{
			this._fieldChangesTracker.clearChangedFields();
		}

		// actions

		get actions()
		{
			return this._actions.get();
		}

		set actions(actions)
		{
			this._actions.set(actions);
		}

		updateActions(actions)
		{
			this._actions.updateActions(actions);
		}

		exportActions()
		{
			return this._actions.exportProperties();
		}

		open(parentWidget = null, context = null, params = {})
		{
			this._actions.open(parentWidget, context, params);
		}

		save()
		{
			return this._actions.save();
		}

		add()
		{
			return this._actions.add();
		}

		update()
		{
			return this._actions.update();
		}

		remove()
		{
			return this._actions.remove();
		}

		saveDeadline()
		{
			return this._actions.saveDeadline();
		}

		delegate()
		{
			return this._actions.delegate();
		}

		stopWatch()
		{
			return this._actions.stopWatch();
		}

		startTimer()
		{
			return this._actions.startTimer();
		}

		pauseTimer()
		{
			return this._actions.pauseTimer();
		}

		start()
		{
			return this._actions.start();
		}

		pause()
		{
			return this._actions.pause();
		}

		complete()
		{
			return this._actions.complete();
		}

		renew()
		{
			return this._actions.renew();
		}

		approve()
		{
			return this._actions.approve();
		}

		disapprove()
		{
			return this._actions.disapprove();
		}

		mute()
		{
			return this._actions.mute();
		}

		unmute()
		{
			return this._actions.unmute();
		}

		pin()
		{
			return this._actions.pin();
		}

		unpin()
		{
			return this._actions.unpin();
		}

		pseudoRead()
		{
			this._actions.pseudoRead();
		}

		read()
		{
			return this._actions.read();
		}

		ping()
		{
			return this._actions.ping();
		}

		addToFavorite()
		{
			return this._actions.addToFavorite();
		}

		removeFromFavorite()
		{
			return this._actions.removeFromFavorite();
		}
	}

	this.Task = Task;
})();
