/**
 * @bxjs_lang_path extension.php
 */

(() => {
	const {Loc} = jn.require('loc');
	const {Type} = jn.require('type');
	const {Entry} = jn.require('tasks/entry');
	const pathToExtension = '/bitrix/mobileapp/tasksmobile/extensions/tasks/task/';

	class Counter
	{
		static get types()
		{
			return {
				expired: {
					expired: 'expired',
					projectExpired: 'projectExpired',
				},
				newComments: {
					newComments: 'newComments',
					projectNewComments: 'projectNewComments',
				},
				my: {
					expired: 'expired',
					newComments: 'newComments',
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

		constructor(task)
		{
			this.task = task;

			this.set(this.getDefault());
		}

		getDefault()
		{
			return {
				counters: {
					[Counter.types.my.expired]: 0,
					[Counter.types.my.newComments]: 0,
					[Counter.types.project.projectExpired]: 0,
					[Counter.types.project.projectNewComments]: 0,
				},
				color: Counter.colors.gray,
				value: 0,
			};
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

		/**
		 * @param {Task} task
		 */
		constructor(task)
		{
			this.task = task;
			this.counter = task._counter;
			this.fieldChangesTracker = task._fieldChangesTracker;

			this.set(this.getDefault());
		}

		getDefault()
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
			console.log('Create task');

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
							console.log(response);

							const {task} = response.result;
							this.task.isNewRecord = false;
							this.task.id = task.id;
							this.set(task.action);

							resolve(response);
						},
						(response) => {
							console.log(response);
							Notify.showMessage(
								response.error.description.replace(/<\/?[^>]+(>|$)/g, ""),
								'',
								{
									time: 5,
								}
							);
							reject(response);
						}
					)
				;
			});
		}

		update()
		{
			console.log('Update task');

			return new Promise((resolve, reject) => {
				const fieldsToSave = this.getFieldsToSave();
				if (!Object.keys(fieldsToSave).length)
				{
					resolve();
				}
				(new RequestExecutor('tasks.task.update', {
					taskId: this.task.id,
					fields: fieldsToSave,
				}))
					.call()
					.then(
						(response) => {
							console.log(response);

							const {task} = response.result;
							this.task.updateData(task);

							resolve(response);
						},
						(response) => {
							console.log(response);
							reject(response);
						}
					)
				;
			});
		}

		getFieldsToSave()
		{
			const fieldsValueGetters = {
				[Task.fields.title]: () => ({TITLE: this.task.title}),
				[Task.fields.description]: () => ({DESCRIPTION: this.task.description}),
				[Task.fields.status]: () => ({STATUS: this.task.status}),
				[Task.fields.group]: () => ({GROUP_ID: (this.task.groupId || 0)}),
				[Task.fields.priority]: () => ({PRIORITY: this.task.priority}),
				[Task.fields.timeEstimate]: () => ({TIME_ESTIMATE: this.task.timeEstimate}),

				[Task.fields.creator]: () => ({CREATED_BY: this.task.creator.id}),
				[Task.fields.responsible]: () => ({RESPONSIBLE_ID: this.task.responsible.id}),
				[Task.fields.accomplices]: () => ({ACCOMPLICES: Object.keys(this.task.accomplices)}),
				[Task.fields.auditors]: () => ({AUDITORS: Object.keys(this.task.auditors)}),

				[Task.fields.deadline]: () => ({DEADLINE: (this.task.deadline ? (new Date(this.task.deadline)).toISOString() : '')}),
				[Task.fields.startDatePlan]: () => ({START_DATE_PLAN: (this.task.startDatePlan ? (new Date(this.task.startDatePlan)).toISOString() : '')}),
				[Task.fields.endDatePlan]: () => ({END_DATE_PLAN: (this.task.endDatePlan ? (new Date(this.task.endDatePlan)).toISOString() : '')}),

				[Task.fields.allowChangeDeadline]: () => ({ALLOW_CHANGE_DEADLINE: (this.task.allowChangeDeadline ? 'Y' : 'N')}),
				[Task.fields.isMatchWorkTime]: () => ({MATCH_WORK_TIME: (this.task.isMatchWorkTime ? 'Y' : 'N')}),
				[Task.fields.allowTaskControl]: () => ({TASK_CONTROL: (this.task.allowTaskControl ? 'Y' : 'N')}),
				[Task.fields.allowTimeTracking]: () => ({ALLOW_TIME_TRACKING: (this.task.allowTimeTracking ? 'Y' : 'N')}),

				[Task.fields.isResultRequired]: () => ({
					SE_PARAMETER: [
						{
							CODE: Task.parameterCodes.isResultRequired,
							VALUE: (this.task.isResultRequired ? 'Y' : 'N'),
						},
					],
				}),

				[Task.fields.mark]: () => (!Type.isUndefined(this.task.mark) ? {MARK: (this.task.mark === Task.mark.none ? '' : this.task.mark)} : {}),
				[Task.fields.tags]: () => (!Type.isUndefined(this.task.tags) ? {TAGS: Object.values(this.task.tags).map(tag => tag.title)} : {}),
				[Task.fields.crm]: () => (!Type.isUndefined(this.task.crm) ? {CRM: (Object.keys(this.task.crm).length > 0 ? this.task.crm : [])} : {}),
				[Task.fields.uploadedFiles]: () => (!Type.isUndefined(this.task.uploadedFiles) ? {UPLOADED_FILES: this.task.uploadedFiles.map(file => file.token)} : {}),
				[Task.fields.files]: () => {
					const filesFields = {};
					if (!Type.isUndefined(this.task.diskFiles))
					{
						filesFields.UF_TASK_WEBDAV_FILES = this.task.diskFiles;
					}
					else if (!Type.isUndefined(this.task.files))
					{
						filesFields.UF_TASK_WEBDAV_FILES = this.task.files.map(file => file.id);
					}
					return filesFields;
				},
				[Task.fields.parentTask]: () => (!Type.isUndefined(this.task.parentId) ? {PARENT_ID: (this.task.parentId || 0)} : {}),
			};
			const fieldsToSave = (
				this.fieldChangesTracker.isEnabled
					? this.fieldChangesTracker.getChangedFields()
					: Object.values(Task.fields)
			);

			return fieldsToSave.reduce((result, field) => {
				if (Object.keys(fieldsValueGetters).includes(field))
				{
					result = {
						...result,
						...fieldsValueGetters[field](),
					};
				}
				return result;
			}, {});
		}

		remove()
		{
			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.delete', {taskId: this.task.id}))
					.call()
					.then(
						(response) => {
							console.log(response);
							resolve(response);
						},
						(response) => {
							console.log(response);
							reject(response);
						}
					)
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
							console.log(response);
							resolve(response);
						},
						(response) => {
							console.log(response);
							reject(response);
						}
					)
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
							console.log(response);

							const {task} = response.result;
							this.task.auditors = task.auditors;
							this.set(task.action);

							resolve(response);
						},
						(response) => {
							console.log(response);
							reject(response);
						}
					)
				;
			});
		}

		stopWatch()
		{
			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.stopWatch', {taskId: this.task.id}))
					.call()
					.then(
						(response) => {
							console.log(response);
							this.set(response.result.task.action);
							resolve(response);
						},
						(response) => {
							console.log(response);
							reject(response);
						}
					);
			});
		}

		startTimer()
		{
			this.task.isTimerRunningForCurrentUser = true;

			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.startTimer', {taskId: this.task.id}))
					.call()
					.then(
						(response) => {
							console.log(response);

							const {task} = response.result;
							this.task.isTimerRunningForCurrentUser = (task.timerIsRunningForCurrentUser === 'Y');
							this.set(response.result.task.action);

							resolve(response);
						},
						(response) => {
							console.log(response);

							Notify.showMessage(
								response.error.description.replace(/<\/?[^>]+(>|$)/g, ""),
								'',
								{
									time: 5,
								}
							);
							this.task.isTimerRunningForCurrentUser = false;

							reject(response);
						}
					)
				;
			});
		}

		pauseTimer()
		{
			this.task.isTimerRunningForCurrentUser = false;

			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.pauseTimer', {taskId: this.task.id}))
					.call()
					.then(
						(response) => {
							console.log(response);

							const {task} = response.result;
							this.task.isTimerRunningForCurrentUser = (task.timerIsRunningForCurrentUser === 'Y');
							this.set(response.result.task.action);

							resolve(response);
						},
						(response) => {
							console.log(response);

							Notify.showMessage(
								response.error.description.replace(/<\/?[^>]+(>|$)/g, ""),
								'',
								{
									time: 5,
								}
							);
							this.task.isTimerRunningForCurrentUser = true;

							reject(response);
						}
					)
				;
			});
		}

		start()
		{
			this.task.status = Task.statusList.inprogress;

			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.start', {taskId: this.task.id}))
					.call()
					.then(
						(response) => {
							console.log(response);
							this.set(response.result.task.action);
							resolve(response);
						},
						(response) => {
							console.log(response);
							reject(response);
						}
					)
				;
			});
		}

		pause()
		{
			this.task.status = Task.statusList.pending;

			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.pause', {taskId: this.task.id}))
					.call()
					.then(
						(response) => {
							console.log(response);
							this.set(response.result.task.action);
							resolve(response);
						},
						(response) => {
							console.log(response);
							reject(response);
						}
					)
				;
			});
		}

		complete()
		{
			const oldStatus = this.task.status;
			this.task.status = Task.statusList.completed;

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
							console.log(response);
							this.set(response.result.task.action);
							resolve(response);
						},
						(response) => {
							console.log(response);
							Notify.showMessage(
								response.error.description.replace(/<\/?[^>]+(>|$)/g, ""),
								'',
								{
									time: 5,
								}
							);
							if (this.task.isResultRequired && !this.task.isOpenResultExists)
							{
								this.task.status = oldStatus;
							}
							reject(response);
						}
					)
				;
			});
		}

		renew()
		{
			this.task.status = Task.statusList.pending;

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
							console.log(response);
							this.set(response.result.task.action);
							resolve(response);
						},
						(response) => {
							console.log(response);
							reject(response);
						}
					)
				;
			});
		}

		approve()
		{
			this.task.status = Task.statusList.completed;

			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.approve', {taskId: this.task.id}))
					.call()
					.then(
						(response) => {
							console.log(response);
							this.set(response.result.task.action);
							resolve(response);
						},
						(response) => {
							console.log(response);
							reject(response);
						}
					)
				;
			});
		}

		disapprove()
		{
			this.task.status = Task.statusList.pending;

			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.disapprove', {taskId: this.task.id}))
					.call()
					.then(
						(response) => {
							console.log(response);
							this.set(response.result.task.action);
							resolve(response);
						},
						(response) => {
							console.log(response);
							reject(response);
						}
					)
				;
			});
		}

		pin()
		{
			this.task.isPinned = true;

			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.pin', {taskId: this.task.id}))
					.call()
					.then(
						(response) => {
							console.log(response);
							this.task.isPinned = true;
							resolve(response);
						},
						(response) => {
							console.log(response);
							this.task.isPinned = false;
							reject(response);
						}
					)
				;
			});
		}

		unpin()
		{
			this.task.isPinned = false;

			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.unpin', {taskId: this.task.id}))
					.call()
					.then(
						(response) => {
							console.log(response);
							this.task.isPinned = false;
							resolve(response);
						},
						(response) => {
							console.log(response);
							this.task.isPinned = true;
							reject(response);
						}
					)
				;
			});
		}

		mute()
		{
			this.task.isMuted = true;

			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.mute', {taskId: this.task.id}))
					.call()
					.then(
						(response) => {
							console.log(response);
							this.task.isMuted = true;
							resolve(response);
						},
						(response) => {
							console.log(response);
							this.task.isMuted = false;
							reject(response);
						}
					)
				;
			});
		}

		unmute()
		{
			this.task.isMuted = false;

			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.unmute', {taskId: this.task.id}))
					.call()
					.then(
						(response) => {
							console.log(response);
							this.task.isMuted = false;
							resolve(response);
						},
						(response) => {
							console.log(response);
							this.task.isMuted = true;
							reject(response);
						}
					);
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
				(new RequestExecutor('tasks.task.view.update', {taskId: this.task.id}))
					.call()
					.then(
						(response) => {
							console.log(response);
							resolve(response);
						},
						(response) => {
							console.log(response);
							reject(response);
						}
					)
				;
			});
		}

		ping()
		{
			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.ping', {taskId: this.task.id}))
					.call()
					.then(
						(response) => {
							console.log(response);
							resolve(response);
						},
						(response) => {
							console.log(response);
							reject(response);
						}
					)
				;
			});
		}

		addToFavorite()
		{
			this.canAddToFavorite = false;
			this.canRemoveFromFavorite = true;

			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.favorite.add', {taskId: this.task.id}))
					.call()
					.then(
						(response) => {
							console.log(response);
							this.canAddToFavorite = false;
							this.canRemoveFromFavorite = true;
							resolve(response);
						},
						(response) => {
							console.log(response);
							this.canAddToFavorite = true;
							this.canRemoveFromFavorite = false;
							reject(response);
						}
					)
				;
			});
		}

		removeFromFavorite()
		{
			this.canAddToFavorite = true;
			this.canRemoveFromFavorite = false;

			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.favorite.remove', {taskId: this.task.id}))
					.call()
					.then(
						(response) =>  {
							console.log(response);
							this.canAddToFavorite = true;
							this.canRemoveFromFavorite = false;
							resolve(response);
						},
						(response) => {
							console.log(response);
							this.canAddToFavorite = false;
							this.canRemoveFromFavorite = true;
							reject(response);
						}
					)
				;
			});
		}

		open(parentWidget)
		{
			if (Application.getApiVersion() < 31)
			{
				const defaultPathToTaskAdd = `${env.siteDir}mobile/tasks/snmrouter/?routePage=#action#&TASK_ID=#taskId#`;
				const pathToTaskAdd =
					BX.componentParameters.get('PATH_TO_TASK_ADD', defaultPathToTaskAdd)
						.replace('#action#', 'view')
						.replace('#taskId#', this.task.id)
				;

				PageManager.openPage({
					backdrop: {
						showOnTop: true,
						forceDismissOnSwipeDown: true,
					},
					url: pathToTaskAdd,
					cache: false,
					modal: false,
					title: (this.task.title || null),
				});
			}
			else
			{
				const taskId = this.task.id;
				const taskData = {
					taskId,
					id: taskId,
					title: 'TASK',
					taskInfo: this.task.getTaskInfo(),
				};
				const params = {
					parentWidget,
					userId: this.task.currentUser.id,
					taskObject: (this.task.canSendMyselfOnOpen ? this.task.exportProperties() : null),
				};
				delete taskData.taskInfo.project;

				(new Entry()).openTask(taskData, params);
			}
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
			const deadlineTime = deadline.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});

			return {
				isCompleted: {
					message: '',
					fontColor: '',
					backgroundColor: '',
				},
				isDeferred: {
					message: Loc.getMessage('MOBILE_TASKS_TASK_CARD_STATE_DEFERRED'),
					fontColor: '#333333',
					backgroundColor: '#FFFFFF',
					border: {
						color: '#A8ADB4',
						width: 2,
					},
				},
				isSupposedlyCompleted: {
					message: Loc.getMessage('MOBILE_TASKS_TASK_CARD_STATE_SUPPOSEDLY_COMPLETED'),
					fontColor: '#333333',
					backgroundColor: '#FFFFFF',
					border: {
						color: '#F7A700',
						width: 2,
					},
				},
				isExpired: {
					message: this.getExpiredTimeText(),
					fontColor: '#FFFFFF',
					backgroundColor: '#FF6864',
				},
				isToday: {
					message: `${Loc.getMessage(`${statePrefix}_TODAY`)} ${deadlineTime}`,
					fontColor: '#FFFFFF',
					backgroundColor: '#F9B933',
				},
				isTomorrow: {
					message: Loc.getMessage(`${statePrefix}_TOMORROW`),
					fontColor: '#FFFFFF',
					backgroundColor: '#A5C933',
				},
				isThisWeek: {
					message: Loc.getMessage(`${statePrefix}_THIS_WEEK`),
					fontColor: '#FFFFFF',
					backgroundColor: '#59D1F8',
				},
				isNextWeek: {
					message: Loc.getMessage(`${statePrefix}_NEXT_WEEK`),
					fontColor: '#FFFFFF',
					backgroundColor: '#3AD4CC',
				},
				isWoDeadline: {
					message: Loc.getMessage(`${statePrefix}_NO_DEADLINE`),
					fontColor: '#828B95',
					backgroundColor: '#E2E9EC',
				},
				isMoreThanTwoWeeks: {
					message: Loc.getMessage(`${statePrefix}_MORE_THAN_TWO_WEEKS`),
					fontColor: '#FFFFFF',
					backgroundColor: '#C2C6CB',
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
					value: 31536000,
					message: Loc.getMessage(`${expiredStatePrefix}_YEAR`),
				},
				month: {
					value: 2592000,
					message: Loc.getMessage(`${expiredStatePrefix}_MONTH`),
				},
				week: {
					value: 604800,
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
			if (!Type.isArray(fields))
			{
				fields = [fields];
			}

			fields.forEach((field) => {
				if (this.isValidField(field) && !this.changedFields.has(field))
				{
					this.changedFields.add(field);
				}
			});
		}

		removeChangedFields(fields)
		{
			if (!Type.isArray(fields))
			{
				fields = [fields];
			}

			fields.forEach((field) => {
				if (this.isFieldChanged(field))
				{
					this.changedFields.delete(field);
				}
			});
		}

		isFieldChanged(field)
		{
			return (this.isValidField(field) && this.changedFields.has(field));
		}

		getChangedFields()
		{
			return Array.from(this.changedFields);
		}

		clearChangedFields()
		{
			this.changedFields.clear();
		}

		isValidField(field)
		{
			return Object.values(Task.fields).includes(field);
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
				danger: '#ff5752',
				gray: '#a8adb4',
				success: '#9dcf00',
			};
		}

		static get backgroundColors()
		{
			return {
				default: '#FFFFFF',
				pinned: '#F4F5F7',
				blinking: '#FFFEDF',
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
					color: '#848E9E',
					position: 'right',
				},
				cancel: {
					id: 'cancel',
					title: Loc.getMessage(`${titlePrefix}_CANCEL`),
					textColor: '#828B95',
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
				none : '1',
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
			this.id = `tmp-id-${(new Date()).getTime()}`;
			this.guid = '';

			this.title = '';
			this.description = '';
			this.parsedDescription = '';
			this.groupId = 0;
			this.group = {
				id: 0,
				name: '',
				image: '',
			};
			this.timeElapsed = 0;
			this.timeEstimate = 0;
			this.commentsCount = 0;
			this.serviceCommentsCount = 0;

			this.status = Task.statusList.pending;
			this.subStatus = Task.statusList.pending;
			this.priority = Task.priority.none;
			this.mark = undefined;

			this.creator = {};
			this.responsible = {};
			this.accomplices = {};
			this.auditors = {};

			this.relatedTasks = {};
			this.subTasks = {};

			this.crm = undefined;
			this.tags = undefined;
			this.files = undefined;
			this.diskFiles = undefined;
			this.uploadedFiles = undefined;
			this.parentId = undefined;
			this.parentTask = {
				id: this.parentId,
				title: '',
			};

			this.counter = {};
			this.actions = {};

			this.isMuted = false;
			this.isPinned = false;
			this.isResultRequired = false;
			this.isResultExists = false;
			this.isOpenResultExists = false;
			this.isMatchWorkTime = false;
			this.allowChangeDeadline = false;
			this.allowTimeTracking = false;
			this.allowTaskControl = false;
			this.isTimerRunningForCurrentUser = false;

			this.deadline = null;
			this.activityDate = null;
			this.startDatePlan = null;
			this.endDatePlan = null;

			this.canSendMyselfOnOpen = true;
		}

		setData(row)
		{
			this.id = row.id;
			this.title = row.title;
			this.description = row.description;
			this.parsedDescription = row.parsedDescription;
			this.groupId = row.groupId;
			this.group = (this.groupId > 0 && row.group ? row.group : {id: 0, name: '', image: ''});
			this.timeElapsed = Number(row.timeElapsed);
			this.timeEstimate = Number(row.timeEstimate);
			this.commentsCount = Number(row.commentsCount);
			this.serviceCommentsCount = Number(row.serviceCommentsCount);

			this.status = row.status;
			this.subStatus = (row.subStatus || this.status);
			this.priority = row.priority;
			if (!Type.isUndefined(row.mark))
			{
				this.mark = (row.mark === '' ? Task.mark.none : row.mark);
			}

			this.creator = row.creator;
			this.responsible = row.responsible;
			this.accomplices = (Type.isArray(row.accomplicesData) ? {} : row.accomplicesData);
			this.auditors = (Type.isArray(row.auditorsData) ? {} : row.auditorsData);

			this.relatedTasks = (Type.isArray(row.relatedTasks) ? {} : row.relatedTasks);
			this.subTasks = (Type.isArray(row.subTasks) ? {} : row.subTasks);

			if (!Type.isUndefined(row.crm))
			{
				this.crm = (Type.isArray(row.crm) ? {} : row.crm);
			}
			if (!Type.isUndefined(row.tags))
			{
				this.tags = (Type.isArray(row.tags) ? {} : row.tags);
			}
			if (!Type.isUndefined(row.files))
			{
				this.files = (row.files || []);
			}
			if (!Type.isUndefined(row.uploadedFiles))
			{
				this.uploadedFiles = (row.uploadedFiles || []);
			}
			if (!Type.isUndefined(row.parentId))
			{
				this.parentId = (Number(row.parentId) || 0);
				this.parentTask = (this.parentId > 0 && row.parentTask ? row.parentTask : {id: this.parentId, title: ''});
			}

			this.counter = row.counter;
			this.actions = row.action;

			this.isMuted = (row.isMuted === 'Y');
			this.isPinned = (row.isPinned === 'Y');
			this.isResultRequired = (row.taskRequireResult === 'Y');
			this.isResultExists = (row.taskHasResult === 'Y');
			this.isOpenResultExists = (row.taskHasOpenResult === 'Y');
			this.isMatchWorkTime = (row.matchWorkTime === 'Y');
			this.allowChangeDeadline = (row.allowChangeDeadline === 'Y');
			this.allowTaskControl = (row.taskControl === 'Y');
			this.allowTimeTracking = (row.allowTimeTracking === 'Y');
			this.isTimerRunningForCurrentUser = (row.timerIsRunningForCurrentUser === 'Y');

			this.deadline = (Date.parse(row.deadline) || null);
			this.activityDate = (Date.parse(row.activityDate) || null);
			this.startDatePlan = (Date.parse(row.startDatePlan) || null);
			this.endDatePlan = (Date.parse(row.endDatePlan) || null);

			this.tryUpdateCurrentUserIcon(row.creator);
			this.tryUpdateCurrentUserIcon(row.responsible);
		}

		updateData(row)
		{
			const has = Object.prototype.hasOwnProperty;

			if (has.call(row, 'id'))
			{
				this.id = row.id;
			}
			if (has.call(row, 'title'))
			{
				this.title = row.title;
			}
			if (has.call(row, 'description'))
			{
				this.description = row.description;
			}
			if (has.call(row, 'parsedDescription'))
			{
				this.parsedDescription = row.parsedDescription;
			}
			if (has.call(row, 'groupId'))
			{
				this.groupId = row.groupId;
			}
			if (has.call(row, 'group'))
			{
				this.group = (this.groupId > 0 && row.group ? row.group : {id: 0, name: '', image: ''});
			}
			if (has.call(row, 'timeElapsed'))
			{
				this.timeElapsed = Number(row.timeElapsed);
			}
			if (has.call(row, 'timeEstimate'))
			{
				this.timeEstimate = Number(row.timeEstimate);
			}
			if (has.call(row, 'commentsCount'))
			{
				this.commentsCount = Number(row.commentsCount);
			}
			if (has.call(row, 'serviceCommentsCount'))
			{
				this.serviceCommentsCount = Number(row.serviceCommentsCount);
			}

			if (has.call(row, 'status'))
			{
				this.status = row.status;
			}
			if (has.call(row, 'subStatus'))
			{
				this.subStatus = row.subStatus;
			}
			if (has.call(row, 'priority'))
			{
				this.priority = row.priority;
			}
			if (has.call(row, 'mark'))
			{
				this.mark = (row.mark === '' ? Task.mark.none : row.mark);
			}

			if (has.call(row, 'creator'))
			{
				this.creator = row.creator;
				this.tryUpdateCurrentUserIcon(row.creator);
			}
			if (has.call(row, 'responsible'))
			{
				this.responsible = row.responsible;
				this.tryUpdateCurrentUserIcon(row.responsible);
			}
			if (has.call(row, 'accomplicesData'))
			{
				this.accomplices = (Type.isArray(row.accomplicesData) ? {} : row.accomplicesData);
			}
			if (has.call(row, 'auditorsData'))
			{
				this.auditors = (Type.isArray(row.auditorsData) ? {} : row.auditorsData);
			}

			if (has.call(row, 'relatedTasks'))
			{
				this.relatedTasks = (Type.isArray(row.relatedTasks) ? {} : row.relatedTasks);
			}
			if (has.call(row, 'subTasks'))
			{
				this.subTasks = (Type.isArray(row.subTasks) ? {} : row.subTasks);
			}

			if (has.call(row, 'crm'))
			{
				this.crm = (Type.isArray(row.crm) ? {} : row.crm);
			}
			if (has.call(row, 'tags'))
			{
				this.tags = (Type.isArray(row.tags) ? {} : row.tags);
			}
			if (has.call(row, 'files'))
			{
				this.files = row.files;
			}
			if (has.call(row, 'uploadedFiles'))
			{
				this.uploadedFiles = row.uploadedFiles;
			}
			if (has.call(row, 'parentId'))
			{
				this.parentId = Number(row.parentId);
			}
			if (has.call(row, 'parentTask'))
			{
				this.parentTask = (this.parentId > 0 && row.parentTask ? row.parentTask : {id: this.parentId, title: ''});
			}

			if (has.call(row, 'counter'))
			{
				this.counter = row.counter;
			}
			if (has.call(row, 'action'))
			{
				this.actions = row.action;
			}

			if (has.call(row, 'isMuted'))
			{
				this.isMuted = (row.isMuted === 'Y');
			}
			if (has.call(row, 'isPinned'))
			{
				this.isPinned = (row.isPinned === 'Y');
			}
			if (has.call(row, 'taskRequireResult'))
			{
				this.isResultRequired = (row.taskRequireResult === 'Y');
			}
			if (has.call(row, 'taskHasResult'))
			{
				this.isResultExists = (row.taskHasResult === 'Y');
			}
			if (has.call(row, 'taskHasOpenResult'))
			{
				this.isOpenResultExists = (row.taskHasOpenResult === 'Y');
			}
			if (has.call(row, 'matchWorkTime'))
			{
				this.isMatchWorkTime = (row.matchWorkTime === 'Y');
			}
			if (has.call(row, 'allowChangeDeadline'))
			{
				this.allowChangeDeadline = (row.allowChangeDeadline === 'Y');
			}
			if (has.call(row, 'taskControl'))
			{
				this.allowTaskControl = (row.taskControl === 'Y');
			}
			if (has.call(row, 'allowTimeTracking'))
			{
				this.allowTimeTracking = (row.allowTimeTracking === 'Y');
			}
			if (has.call(row, 'timerIsRunningForCurrentUser'))
			{
				this.isTimerRunningForCurrentUser = (row.timerIsRunningForCurrentUser === 'Y');
			}

			if (has.call(row, 'deadline'))
			{
				this.deadline = (Date.parse(row.deadline) || null);
			}
			if (has.call(row, 'activityDate'))
			{
				this.activityDate = (Date.parse(row.activityDate) || null);
			}
			if (has.call(row, 'startDatePlan'))
			{
				this.startDatePlan = (Date.parse(row.startDatePlan) || null);
			}
			if (has.call(row, 'endDatePlan'))
			{
				this.endDatePlan = (Date.parse(row.endDatePlan) || null);
			}
		}

		exportProperties()
		{
			return {
				currentUser: this.currentUser,
				isNewRecord: this.isNewRecord,

				_id: this._id,
				_guid: this._guid,
				_status: this._status,
				_subStatus: this._subStatus,

				title: this.title,
				description: this.description,
				parsedDescription: this.parsedDescription,
				groupId: this.groupId,
				group: this.group,
				timeElapsed: this.timeElapsed,
				timeEstimate: this.timeEstimate,
				commentsCount: this.commentsCount,
				serviceCommentsCount: this.serviceCommentsCount,

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
				diskFiles: this.diskFiles,
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
				Number(this.currentUser.id) === Number(user.id)
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
			return this.status === Task.statusList.waitCtrl;
		}

		get isSupposedlyCompletedCounts()
		{
			return (this.isSupposedlyCompleted && this.isCreator() && !this.isResponsible());
		}

		get isCompleted()
		{
			return this.status === Task.statusList.completed;
		}

		get isCompletedCounts()
		{
			return (this.isCompleted || (this.isSupposedlyCompleted && !this.isCreator()));
		}

		get isDeferred()
		{
			return this.status === Task.statusList.deferred;
		}

		get isWoDeadline()
		{
			return !this.deadline;
		}

		get isExpired()
		{
			const date = new Date();

			return (Boolean(this.deadline) && this.deadline <= date.getTime());
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
					color: '#F2A100',
					position: 'right',
				},
				approve: {
					identifier: Action.types.approve,
					title: Loc.getMessage(`${titlePrefix}_APPROVE`),
					iconName: 'action_accept',
					color: '#468EE5',
					position: 'right',
				},
				disapprove: {
					identifier: Action.types.disapprove,
					title: Loc.getMessage(`${titlePrefix}_DISAPPROVE_MSGVER_1`),
					iconName: 'action_finish_up',
					color: '#FF5752',
					position: 'right',
				},
				changeResponsible: {
					identifier: Action.types.changeResponsible,
					title: Loc.getMessage(`${titlePrefix}_CHANGE_RESPONSIBLE`),
					iconName: 'action_userlist',
					color: '#2F72B9',
					position: 'right',
				},
				delegate: {
					identifier: Action.types.delegate,
					title: Loc.getMessage(`${titlePrefix}_DELEGATE`),
					iconName: 'action_userlist',
					color: '#2F72B9',
					position: 'right',
				},
				ping: {
					identifier: Action.types.ping,
					title: Loc.getMessage(`${titlePrefix}_PING`),
					iconName: 'action_ping',
					color: '#05b5ab',
					position: 'right',
				},
				share: {
					identifier: Action.types.share,
					title: Loc.getMessage(`${titlePrefix}_SHARE`),
					iconName: 'action_share',
					color: '#6E7B8F',
					position: 'right',
				},
				changeGroup: {
					identifier: Action.types.changeGroup,
					title: Loc.getMessage(`${titlePrefix}_CHANGE_GROUP`),
					iconName: 'action_project',
					color: '#1BA09B',
					position: 'right',
				},
				startTimer: {
					identifier: Action.types.startTimer,
					title: Loc.getMessage(`${titlePrefix}_START`),
					iconName: 'action_start',
					color: '#38C4D6',
					position: 'right',
				},
				pauseTimer: {
					identifier: Action.types.pauseTimer,
					title: Loc.getMessage(`${titlePrefix}_PAUSE`),
					iconName: 'action_finish',
					color: '#38C4D6',
					position: 'right',
				},
				start: {
					identifier: Action.types.start,
					title: Loc.getMessage(`${titlePrefix}_START`),
					iconName: 'action_start',
					color: '#38C4D6',
					position: 'right',
				},
				pause: {
					identifier: Action.types.pause,
					title: Loc.getMessage(`${titlePrefix}_PAUSE`),
					iconName: 'action_finish',
					color: '#38C4D6',
					position: 'right',
				},
				renew: {
					identifier: Action.types.renew,
					title: Loc.getMessage(`${titlePrefix}_RENEW`),
					iconName: 'action_reload',
					color: '#05b5ab',
					position: 'right',
				},
				mute: {
					identifier: Action.types.mute,
					title: Loc.getMessage(`${titlePrefix}_MUTE`),
					iconName: 'action_mute',
					color: '#8BC84B',
					position: 'right',
				},
				unmute: {
					identifier: Action.types.unmute,
					title: Loc.getMessage(`${titlePrefix}_UNMUTE`),
					iconName: 'action_unmute',
					color: '#8BC84B',
					position: 'right',
				},
				unfollow: {
					identifier: Action.types.unfollow,
					title: Loc.getMessage(`${titlePrefix}_DONT_FOLLOW`),
					iconName: 'action_unfollow',
					color: '#AF6D4D',
					position: 'right',
				},
				remove: {
					identifier: Action.types.remove,
					title: Loc.getMessage(`${titlePrefix}_REMOVE`),
					iconName: 'action_remove',
					color: '#6E7B8F',
					position: 'right',
				},
				read: {
					identifier: Action.types.read,
					title: Loc.getMessage(`${titlePrefix}_READ`),
					iconName: 'action_read',
					color: '#E57BB6',
					position: 'left',
				},
				pin: {
					identifier: Action.types.pin,
					title: Loc.getMessage(`${titlePrefix}_PIN`),
					iconName: 'action_pin',
					color: '#468EE5',
					position: 'left',
				},
				unpin: {
					identifier: Action.types.unpin,
					title: Loc.getMessage(`${titlePrefix}_UNPIN`),
					iconName: `action_unpin`,
					color: '#468EE5',
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
				currentActions.splice(currentActions.findIndex(item => item.identifier === Action.types.delegate), 1);
			}

			if (Application.getApiVersion() < 34)
			{
				currentActions.splice(currentActions.findIndex(item => item.identifier === Action.types.share), 1);
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

		open(parentWidget = null)
		{
			this._actions.open(parentWidget);
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