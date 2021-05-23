/**
 * @bxjs_lang_path extension.php
 */

(() =>
{
	const pathToExtension = '/bitrix/mobileapp/mobile/extensions/bitrix/task/';

	class Task
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

		static get counterColors()
		{
			return {
				green: '#9DCF00',
				red: '#FF5752',
				gray: '#A8ADB4',
			};
		}

		static get backgroundColors()
		{
			return {
				default: '#FFFFFF',
				pinned: '#F4F5F7',
			};
		}

		static get actions()
		{
			const titlePrefix = 'MOBILE_TASKS_TASK_CARD_VIEW_ACTION';
			return {
				more: {
					identifier: 'more',
					title: BX.message(`${titlePrefix}_MORE`),
					iconName: 'more',
					color: '#848E9E',
					position: 'right',
				},
				cancel: {
					id: 'cancel',
					title: BX.message(`${titlePrefix}_CANCEL`),
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
					name: BX.message(`${statePrefix}_TODAY`),
				},
				tomorrow: {
					name: BX.message(`${statePrefix}_TOMORROW`),
				},
				thisWeek: {
					name: BX.message(`${statePrefix}_THIS_WEEK`),
				},
				nextWeek: {
					name: BX.message(`${statePrefix}_NEXT_WEEK`),
				},
				moreThanTwoWeeks: {
					name: BX.message(`${statePrefix}_MORE_THAN_TWO_WEEKS`),
				},
			};
		}

		static get popupImageUrls()
		{
			const urlPrefix = `${pathToExtension}images/mobile-task-popup-`;
			const urlPostfix = '.png';
			const names = {
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

		constructor(currentUser)
		{
			this.currentUser = currentUser;

			const defaultTaskUrl = `${env.siteDir}mobile/tasks/snmrouter/?routePage=#action#&TASK_ID=#taskId#`;
			this.taskUrlTemplate = BX.componentParameters.get('PATH_TO_TASK_ADD', defaultTaskUrl);
			this.error = false;

			this.setDefaultData();

			this.isNewRecord = true;
		}

		setDefaultData()
		{
			this.id = `tmp-id-${(new Date()).getTime()}`;
			this.guid = '';

			this.title = '';
			this.groupId = 0;
			this.group = {id: 0, name: '', image: ''};

			this.status = Task.statusList.pending;
			this.subStatus = Task.statusList.pending;

			this.creator = {};
			this.responsible = {};

			this.commentsCount = 0;
			this.newCommentsCount = 0;

			this.isMuted = false;
			this.isPinned = false;
			this.notViewed = false;

			this.accomplices = [];
			this.auditors = [];

			this.rawAccess = {};

			this.deadline = null;
			this.activityDate = null;
		}

		setData(row)
		{
			this.id = row.id;
			this.title = row.title;
			this.groupId = row.groupId;
			this.group = (this.groupId > 0 && row.group ? row.group : {id: 0, name: '', image: ''});

			this.status = row.status;
			this.subStatus = row.subStatus || this.status;

			this.creator = row.creator;
			this.responsible = row.responsible;

			this.commentsCount = row.commentsCount;
			this.newCommentsCount = row.newCommentsCount;

			this.isMuted = row.isMuted === 'Y';
			this.isPinned = row.isPinned === 'Y';
			this.notViewed = row.notViewed === 'Y';

			this.accomplices = row.accomplices || [];
			this.auditors = row.auditors || [];

			this.rawAccess = row.action;

			const deadline = Date.parse(row.deadline);
			const activityDate = Date.parse(row.activityDate);

			this.deadline = (deadline > 0 ? deadline : null);
			this.activityDate = (activityDate > 0 ? activityDate : null);

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
			const has = Object.prototype.hasOwnProperty;
			for (const key in data)
			{
				if (has.call(this, key))
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
				this._newCommentsCount = Number(value);
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

		isPureCreator(userId = null)
		{
			return Number(userId || this.currentUser.id) === Number(this.creator.id)
				&& Number(userId || this.currentUser.id) !== Number(this.responsible.id);
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

		isPureDoer(userId = null)
		{
			return this.isDoer(userId) && !this.isCreator(userId);
		}

		get isToday()
		{
			if (!this.deadline)
			{
				return false;
			}

			const deadline = new Date(this.deadline);
			const today = new Date();

			return deadline && Task.checkMatchDates(deadline, [today]);
		}

		get isTomorrow()
		{
			if (!this.deadline)
			{
				return false;
			}

			const deadline = new Date(this.deadline);
			const tomorrow = new Date();
			tomorrow.setDate(tomorrow.getDate() + 1);

			return deadline && Task.checkMatchDates(deadline, [tomorrow]);
		}

		get isThisWeek()
		{
			if (!this.deadline)
			{
				return false;
			}

			const deadline = new Date(this.deadline);
			const today = new Date();
			const thisWeekDays = [];

			for (let i = 1; i <= 7; i++)
			{
				const first = today.getDate() - today.getDay() + i;
				const day = new Date(today.setDate(first));
				thisWeekDays.push(day);
			}

			return deadline && Task.checkMatchDates(deadline, thisWeekDays);
		}

		get isNextWeek()
		{
			if (!this.deadline)
			{
				return false;
			}

			const deadline = new Date(this.deadline);
			const nextWeekDays = [];
			const nextWeekDay = new Date();
			nextWeekDay.setDate(nextWeekDay.getDate() + 7);

			for (let i = 1; i <= 7; i++)
			{
				const first = nextWeekDay.getDate() - nextWeekDay.getDay() + i;
				const day = new Date(nextWeekDay.setDate(first));
				nextWeekDays.push(day);
			}

			return deadline && Task.checkMatchDates(deadline, nextWeekDays);
		}

		get isMoreThanTwoWeeks()
		{
			return true;
		}

		get isNew()
		{
			return this.notViewed || [-2, 1].includes(this.subStatus);
		}

		get isNewCounts()
		{
			return this.isNew && this.isPureDoer();
		}

		get isWaitCtrl()
		{
			return this.status === Task.statusList.waitCtrl;
		}

		get isWaitCtrlCounts()
		{
			return this.isWaitCtrl && this.isCreator() && !this.isResponsible();
		}

		get isCompleted()
		{
			return this.status === Task.statusList.completed;
		}

		get isCompletedCounts()
		{
			return this.isCompleted || (this.isWaitCtrl && !this.isCreator());
		}

		get isDeferred()
		{
			return this.status === Task.statusList.deferred;
		}

		get isWoDeadline()
		{
			return !this.deadline;
		}

		get isWoDeadlineCounts()
		{
			const onePersonTask = (this.isCreator() && this.isResponsible());
			return this.isWoDeadline && ((this.isCreator() || this.isResponsible()) && !onePersonTask);
		}

		get isExpiredSoon()
		{
			const date = new Date();
			date.setDate(date.getDate() + 1);

			return this.deadline && this.deadline <= date.getTime();
		}

		get isExpiredSoonCounts()
		{
			return this.isExpiredSoon
				&& !this.isExpired
				&& this.isPureDoer()
				&& !this.isCompletedCounts;
		}

		get isExpired()
		{
			const date = new Date();
			return Boolean(this.deadline) && this.deadline <= date.getTime();
		}

		get isExpiredCounts()
		{
			return this.isExpired && this.isPureDoer() && !this.isCompletedCounts;
		}

		get expiredTime()
		{
			const expiredStatePrefix = 'MOBILE_TASKS_TASK_CARD_DEADLINE_STATE_EXPIRED';
			const extensions = {
				year: {
					value: 31536000,
					message: BX.message(`${expiredStatePrefix}_YEAR`),
				},
				month: {
					value: 2592000,
					message: BX.message(`${expiredStatePrefix}_MONTH`),
				},
				week: {
					value: 604800,
					message: BX.message(`${expiredStatePrefix}_WEEK`),
				},
				day: {
					value: 86400,
					message: BX.message(`${expiredStatePrefix}_DAY`),
				},
				hour: {
					value: 3600,
					message: BX.message(`${expiredStatePrefix}_HOUR`),
				},
				minute: {
					value: 60,
					message: BX.message(`${expiredStatePrefix}_MINUTE`),
				},
			};

			const date = new Date();
			let delta = (date.getTime() - this.deadline) / 1000;
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

			return expiredTime || extensions.minute.message.replace('#TIME#', 1);
		}

		get statesList()
		{
			const statePrefix = 'MOBILE_TASKS_TASK_CARD_DEADLINE_STATE';
			const deadline = new Date(this.deadline);
			const deadlineTime = deadline.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});

			return {
				isCompleted: {
					message: '',
					fontColor: '',
					backgroundColor: '',
				},
				isDeferred: {
					message: BX.message('MOBILE_TASKS_TASK_CARD_STATE_DEFERRED'),
					fontColor: '#333333',
					backgroundColor: '#FFFFFF',
					border: {
						color: '#A8ADB4',
						width: 2,
					},
				},
				isWaitCtrl: {
					message: BX.message('MOBILE_TASKS_TASK_CARD_STATE_SUPPOSEDLY_COMPLETED'),
					fontColor: '#333333',
					backgroundColor: '#FFFFFF',
					border: {
						color: '#F7A700',
						width: 2,
					},
				},
				isExpired: {
					message: this.expiredTime,
					fontColor: '#FFFFFF',
					backgroundColor: '#FF6864',
				},
				isToday: {
					message: BX.message(`${statePrefix}_TODAY`) + ` ${deadlineTime}`,
					fontColor: '#FFFFFF',
					backgroundColor: '#F9B933',
				},
				isTomorrow: {
					message: BX.message(`${statePrefix}_TOMORROW`),
					fontColor: '#FFFFFF',
					backgroundColor: '#A5C933',
				},
				isThisWeek: {
					message: BX.message(`${statePrefix}_THIS_WEEK`),
					fontColor: '#FFFFFF',
					backgroundColor: '#59D1F8',
				},
				isNextWeek: {
					message: BX.message(`${statePrefix}_NEXT_WEEK`),
					fontColor: '#FFFFFF',
					backgroundColor: '#3AD4CC',
				},
				isWoDeadline: {
					message: BX.message(`${statePrefix}_NO_DEADLINE`),
					fontColor: '#828B95',
					backgroundColor: '#E2E9EC',
				},
				isMoreThanTwoWeeks: {
					message: BX.message(`${statePrefix}_MORE_THAN_TWO_WEEKS`),
					fontColor: '#FFFFFF',
					backgroundColor: '#C2C6CB',
				},
			};
		}

		get can()
		{
			return {
				changeDeadline: this.rawAccess && this.rawAccess.changeDeadline,
				changeResponsible: (this.rawAccess && this.rawAccess.edit) || this.currentUser.isAdmin || this.isCreator(),
				changeGroup: this.rawAccess && this.rawAccess.edit,
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
				unfollow: this.isAuditor(),
				changePin: true,
				changeMute: true,
				read: true,
			};
		}

		getMessageInfo()
		{
			let count = this.newCommentsCount || 0;
			let color = Task.counterColors.green;

			if (this.isExpired && !this.isCompletedCounts && !this.isWaitCtrlCounts && !this.isDeferred)
			{
				count += 1;
				color = Task.counterColors.red;
			}

			if (this.isMuted)
			{
				color = Task.counterColors.gray;
			}

			return {count, color};
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
								GROUP_ID: this.groupId || 0,
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

		mute()
		{
			this.isMuted = true;

			return new Promise((resolve, reject) => {
				(new Request())
					.call('mute', {
						taskId: this.id,
					})
					.then(
						(response) => {
							console.log(response.result.task);
							this.error = false;

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

		unmute()
		{
			this.isMuted = false;

			return new Promise((resolve, reject) => {
				(new Request())
					.call('unmute', {
						taskId: this.id,
					})
					.then(
						(response) => {
							console.log(response.result.task);
							this.error = false;

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

		pin()
		{
			this.isPinned = true;

			return new Promise((resolve, reject) => {
				(new Request())
					.call('pin', {
						taskId: this.id,
					})
					.then(
						(response) => {
							console.log(response.result.task);
							this.error = false;

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

		unpin()
		{
			this.isPinned = false;

			return new Promise((resolve, reject) => {
				(new Request())
					.call('unpin', {
						taskId: this.id,
					})
					.then(
						(response) => {
							console.log(response.result.task);
							this.error = false;

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

		read()
		{
			this.newCommentsCount = 0;

			return new Promise((resolve, reject) => {
				(new Request())
					.call('view.update', {taskId: this.id})
					.then(
						(response) => {
							console.log(response.result.task);
							this.error = false;

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
			this.status = Task.statusList.completed;

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
				const params = {
					userId: this.currentUser.id,
					taskObject: this,
				};

				BX.postComponentEvent('taskbackground::task::action', [taskData, taskId, params]);
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
			const messageInfo = this.getMessageInfo();

			const taskInfo = {
				id: this.id,
				title: this.title || '',
				checked: this.isCompletedCounts,
				checkable: this.can.complete || this.can.approve || this.can.renew,
				state: state.message,
				date: this.activityDate / 1000,
				messageCount: messageInfo.count,
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
						backgroundColor: messageInfo.color,
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
			const states = this.statesList;
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
			const states = this.statesList;
			const currentStates = {};

			Object.keys(states).forEach((key) => {
				if (this[key])
				{
					currentStates[key] = states[key];
				}
			});

			return currentStates;
		}

		getSwipeActions()
		{
			const titlePrefix = 'MOBILE_TASKS_TASK_CARD_VIEW_ACTION';
			const actions = {
				changeDeadline: {
					identifier: 'changeDeadline',
					title: BX.message(`${titlePrefix}_CHANGE_DEADLINE`),
					iconName: 'action_term',
					color: '#F2A100',
					position: 'right',
				},
				approve: {
					identifier: 'approve',
					title: BX.message(`${titlePrefix}_APPROVE`),
					iconName: 'action_accept',
					color: '#468EE5',
					position: 'right',
				},
				disapprove: {
					identifier: 'disapprove',
					title: BX.message(`${titlePrefix}_DISAPPROVE`),
					iconName: 'action_finish_up',
					color: '#FF5752',
					position: 'right',
				},
				start: {
					identifier: 'start',
					title: BX.message(`${titlePrefix}_START`),
					iconName: 'action_start',
					color: '#38C4D6',
					position: 'right',
				},
				pause: {
					identifier: 'pause',
					title: BX.message(`${titlePrefix}_PAUSE`),
					iconName: 'action_finish',
					color: '#38C4D6',
					position: 'right',
				},
				renew: {
					identifier: 'renew',
					title: BX.message(`${titlePrefix}_RENEW`),
					iconName: 'action_reload',
					color: '#00B4AC',
					position: 'right',
				},
				changeResponsible: {
					identifier: 'changeResponsible',
					title: BX.message(`${titlePrefix}_CHANGE_RESPONSIBLE`),
					iconName: 'action_userlist',
					color: '#2F72B9',
					position: 'right',
				},
				delegate: {
					identifier: 'delegate',
					title: BX.message(`${titlePrefix}_DELEGATE`),
					iconName: 'action_userlist',
					color: '#2F72B9',
					position: 'right',
				},
				changeGroup: {
					identifier: 'changeGroup',
					title: BX.message(`${titlePrefix}_CHANGE_GROUP`),
					iconName: 'action_project',
					color: '#1BA09B',
					position: 'right',
				},
				changeMute: {
					identifier: (this.isMuted ? 'unmute' : 'mute'),
					title: BX.message(`${titlePrefix}_${this.isMuted ? 'UNMUTE' : 'MUTE'}`),
					iconName: `action_${(this.isMuted ? 'unmute' : 'mute')}`,
					color: '#8BC84B',
					position: 'right',
				},
				unfollow: {
					identifier: 'unfollow',
					title: BX.message(`${titlePrefix}_DONT_FOLLOW`),
					iconName: 'action_unfollow',
					color: '#AF6D4D',
					position: 'right',
				},
				remove: {
					identifier: 'remove',
					title: BX.message(`${titlePrefix}_REMOVE`),
					iconName: 'action_remove',
					color: '#6E7B8F',
					position: 'right',
				},
				read: {
					identifier: 'read',
					title: BX.message(`${titlePrefix}_READ`),
					iconName: 'action_read',
					color: '#E57BB6',
					position: 'left',
				},
				changePin: {
					identifier: (this.isPinned ? 'unpin' : 'pin'),
					title: BX.message(`${titlePrefix}_${this.isPinned ? 'UNPIN' : 'PIN'}`),
					iconName: `action_${(this.isPinned ? 'unpin' : 'pin')}`,
					color: '#468EE5',
					position: 'left',
				},
			};
			const currentActions = [];

			Object.keys(actions).forEach((key) => {
				if (this.can[key])
				{
					currentActions.push(actions[key]);
				}
			});

			if (this.can.changeResponsible && this.can.delegate)
			{
				currentActions.splice(currentActions.findIndex(item => item.identifier === 'delegate'), 1);
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

	this.Task = Task;
})();