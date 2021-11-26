/**
 * @bxjs_lang_path component.php
 */

include('InAppNotifier');

(() => {
	const pathToComponent = '/bitrix/mobileapp/mobile/components/bitrix/tasks.list/';
	const apiVersion = Application.getApiVersion();
	const platform = Application.getPlatform();
	const caches = new Map();

	class Util
	{
		static showError(message = null, title = '')
		{
			if (
				message !== null
				&& PageManager.getNavigator().isVisible()
				&& PageManager.getNavigator().isActiveTab()
			)
			{
				InAppNotifier.showNotification({message, title, backgroundColor: '#333333'});
			}
		}

		static resolveErrorMessageByAnswer(answer = null)
		{
			let messageError = BX.message('TASKS_SOME_THING_WENT_WRONG');

			if (answer && answer.ex)
			{
				switch (answer.ex.status)
				{
					case -2:
						messageError = BX.message('TASKS_NO_INTERNET_CONNECTION');
						break;

					case 0:
						messageError = BX.message('TASKS_UNEXPECTED_ANSWER');
						break;

					default:
						messageError = BX.message('TASKS_SOME_THING_WENT_WRONG');
						break;
				}
			}
			console.log('Error', answer);

			return messageError;
		}

		static debounce(fn, timeout, ctx)
		{
			let timer = 0;
			return function() {
				clearTimeout(timer);
				timer = setTimeout(() => fn.apply(ctx, arguments), timeout);
			};
		}
	}

	class Request
	{
		constructor(namespace = 'tasks.task.')
		{
			this.restNamespace = namespace;
		}

		call(methodName, params)
		{
			const method = this.restNamespace + methodName;

			this.abortCurrentRequest();

			return new Promise((resolve, reject) => {
				console.log({method, params});

				BX.rest.callMethod(method, params || {}, (response) => {
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

	class SectionHandler
	{
		static getInstance()
		{
			if (SectionHandler.instance == null)
			{
				SectionHandler.instance = new SectionHandler();
			}

			return SectionHandler.instance;
		}

		constructor()
		{
			console.log('init SectionHandler');
			this.clear();
		}

		clear()
		{
			this.items = {
				new: {
					id: 'new',
					title: '',
					foldable: false,
					folded: false,
					badgeValue: 0,
					backgroundColor: '#FFFFFF',
					styles: {title: {font: {size: 18}}},
				},
				pinned: {
					id: 'pinned',
					title: '',
					foldable: false,
					folded: false,
					badgeValue: 0,
					sortItemParams: {activityDate: 'desc'},
					backgroundColor: '#FFFFFF',
					styles: {title: {font: {size: 18}}},
				},
				default: {
					id: 'default',
					title: '',
					foldable: false,
					folded: false,
					badgeValue: 0,
					sortItemParams: {activityDate: 'desc'},
					backgroundColor: '#FFFFFF',
					styles: {title: {font: {size: 18}}},
				},
				empty: {
					id: 'empty',
					title: '',
					foldable: false,
					folded: false,
					badgeValue: 0,
					backgroundColor: '#FFFFFF',
					styles: {title: {font: {size: 18}}},
				},
			};
		}

		add(item)
		{
			const {id} = item;

			this.items[id] = {
				id: id || 'default',
				title: item.title || '',
				foldable: item.foldable || false,
				folded: item.folded || false,
				badgeValue: (this.has(id) ? (this.items[id].badgeValue || 0) : 0) + (item.badgeValue || 0),
				backgroundColor: item.backgroundColor || '#FFFFFF',
				styles: {title: {font: {size: 18}}},
			};
		}

		setSortItemParams(sectionId, sortItemParams)
		{
			if (this.has(sectionId))
			{
				this.items[sectionId].sortItemParams = sortItemParams;
			}
		}

		has(id)
		{
			return id in this.items;
		}

		get list()
		{
			return Object.values(this.items);
		}
	}

	class Order
	{
		static get fields()
		{
			return {
				activityDate: [
					{field: 'ACTIVITY_DATE', direction: 'DESC'},
					{field: 'ID', direction: 'DESC'},
				],
				deadline: [
					{field: 'DEADLINE', direction: 'ASC,NULLS'},
					{field: 'ID', direction: 'DESC'},
				],
			};
		}

		static get sectionOrderFields()
		{
			return {
				activityDate: 'desc',
				deadline: 'asc',
			};
		}

		constructor()
		{
			this.order = BX.componentParameters.get('ORDER', 'activityDate');
		}

		get()
		{
			const order = {};

			Order.fields[this.order].forEach((fieldData) => {
				order[fieldData.field] = fieldData.direction;
			});

			return order;
		}

		getForSearch()
		{
			const order = {};

			Order.fields.activityDate.forEach((fieldData) => {
				order[fieldData.field] = fieldData.direction;
			});

			return order;
		}

		changeOrder()
		{
			this.order = Object.keys(Order.fields).filter(key => key !== this.order)[0];
		}

		isDeadline()
		{
			return this.checkOrder('deadline');
		}

		isActivityDate()
		{
			return this.checkOrder('activityDate');
		}

		checkOrder(order)
		{
			return this.order === order;
		}

		get order()
		{
			return this._order || 'activityDate';
		}

		set order(order)
		{
			this._order = order;
		}
	}

	class Cache
	{
		constructor(storageName)
		{
			this.storageName = storageName;
			this.defaultData = {};
		}

		static getInstance(id)
		{
			if (!caches.has(id))
			{
				caches.set(id, (new Cache(id)));
			}
			return caches.get(id);
		}

		get()
		{
			return Application.storage.getObject(this.storageName, this.defaultData);
		}

		set(data)
		{
			Application.storage.setObject(this.storageName, data);
		}

		update(key, value)
		{
			const currentCache = this.get();
			currentCache[key] = value;
			this.set(currentCache);
		}

		setDefaultData(defaultData)
		{
			this.defaultData = defaultData;
		}
	}

	class Filter
	{
		static getColumn(matrix, column)
		{
			const columnItems = [];

			for (let i = 0; i < matrix.length; i++)
			{
				columnItems.push(matrix[i][column]);
			}

			return columnItems;
		}

		static get roleType()
		{
			return {
				all: 'view_all',
				responsible: 'view_role_responsible',
				accomplice: 'view_role_accomplice',
				originator: 'view_role_originator',
				auditor: 'view_role_auditor',
			};
		}

		static get counterType()
		{
			return {
				none: 'none',
				expired: 'expired',
				newComments: 'new_comments',
				supposedlyCompleted: 'supposedly_completed',
			};
		}

		static getPresetsMap()
		{
			return {
				[Filter.roleType.all]: [Filter.counterType.expired, Filter.counterType.newComments],
				[Filter.roleType.responsible]: [Filter.counterType.expired, Filter.counterType.newComments],
				[Filter.roleType.accomplice]: [Filter.counterType.expired, Filter.counterType.newComments],
				[Filter.roleType.originator]: [Filter.counterType.expired, Filter.counterType.newComments],
				[Filter.roleType.auditor]: [Filter.counterType.expired, Filter.counterType.newComments],
			};
		}

		/**
		 * @param {string} role
		 * @param {Task} task
		 * @return {string[]}
		 */
		static getCountersByRole(role, task)
		{
			const counterMap = {
				[Filter.counterType.none]: task.isMember(),
				[Filter.counterType.expired]: task.isExpired && !task.isMuted,
				[Filter.counterType.newComments]: task.newCommentsCount > 0 && !task.isMuted,
				[Filter.counterType.supposedlyCompleted]: task.isWaitCtrl,
			};
			switch (role)
			{
				case Filter.roleType.all:
					counterMap[Filter.counterType.supposedlyCompleted] = (
						counterMap[Filter.counterType.supposedlyCompleted] && task.isPureCreator()
					);
					break;

				case Filter.roleType.responsible:
					counterMap[Filter.counterType.none] = (
						counterMap[Filter.counterType.none] && task.isResponsible()
					);
					counterMap[Filter.counterType.supposedlyCompleted] = (
						counterMap[Filter.counterType.supposedlyCompleted] && task.isResponsible()
					);
					break;

				case Filter.roleType.accomplice:
					counterMap[Filter.counterType.none] = (
						counterMap[Filter.counterType.none] && task.isAccomplice()
					);
					counterMap[Filter.counterType.supposedlyCompleted] = (
						counterMap[Filter.counterType.supposedlyCompleted] && task.isAccomplice()
					);
					break;

				case Filter.roleType.originator:
					counterMap[Filter.counterType.none] = (
						counterMap[Filter.counterType.none] && task.isPureCreator()
					);
					counterMap[Filter.counterType.expired] = (
						counterMap[Filter.counterType.expired] && task.isPureCreator()
					);
					counterMap[Filter.counterType.newComments] = (
						counterMap[Filter.counterType.newComments] && task.isPureCreator()
					);
					counterMap[Filter.counterType.supposedlyCompleted] = (
						counterMap[Filter.counterType.supposedlyCompleted] && task.isPureCreator()
					);
					break;

				case Filter.roleType.auditor:
					counterMap[Filter.counterType.none] = (
						counterMap[Filter.counterType.none] && task.isAuditor()
					);
					counterMap[Filter.counterType.supposedlyCompleted] = (
						counterMap[Filter.counterType.supposedlyCompleted] && task.isAuditor()
					);
					break;

				default:
					break;
			}

			return {
				existing: Object.keys(counterMap).filter(counter => counterMap[counter]),
				notExisting: Object.keys(counterMap).filter(counter => !counterMap[counter]),
			};
		}

		constructor(list, currentUser, owner, groupId)
		{
			this.list = list;
			this.currentUser = currentUser;
			this.owner = owner;
			this.groupId = groupId || 0;

			this.role = BX.componentParameters.get('ROLE', Filter.roleType.all);
			this.counter = BX.componentParameters.get('COUNTER', Filter.counterType.none);
			this.showCompleted = BX.componentParameters.get('SHOW_COMPLETED', false);

			this.counters = {};
			this.cache = Cache.getInstance('tasks.task.list.filter.counters');

			this.setCounterValue(this.cache.get().counterValue || 0);
			this.setVisualCounters();

			ChatReadyCheck.wait().then(() => this.updateCounters());
		}

		updateCounters()
		{
			if (!this.isMyList())
			{
				return;
			}

			console.log('UPDATE COUNTERS');

			const action = `${(new Request()).restNamespace}counters.get`;
			const batchOperations = {};

			Object.keys(Filter.getPresetsMap()).forEach((role) => {
				batchOperations[role] = [action, {
					type: role,
					userId: this.owner.id || this.currentUser.id,
					groupId: this.groupId,
				}];
			});

			BX.rest.callBatch(batchOperations, (result) => {
				if (!result.view_all.answer.result)
				{
					return;
				}

				this.counters = {};

				Object.keys(Filter.getPresetsMap()).forEach((role) => {
					const subPresets = Filter.getPresetsMap()[role];
					const counter = result[role].answer.result;

					this.counters[role] = {};
					this.counters[`${role}_total`] = 0;

					subPresets.forEach((subPresetType) => {
						const subCounter = counter[subPresetType];
						const subCounterValue = (subCounter ? Number(subCounter.counter) : 0);

						this.counters[role][subPresetType] = subCounterValue;
						this.counters[`${role}_total`] += subCounterValue;
					});
				});

				this.setVisualCounters();
				this.saveCache();
			});
		}

		pseudoUpdateCounters(value, task = null)
		{
			if (!this.isMyList() || (task && task.isMuted))
			{
				return;
			}

			this.counters.view_all_total += value;
			if (this.counters.view_all_total < 0)
			{
				this.counters.view_all_total = 0;
			}

			this.setVisualCounters();
		}

		onUserCounter(data)
		{
			return new Promise((resolve) => {
				if (Number(this.currentUser.id) !== Number(data.userId) || !this.isMyList())
				{
					resolve();
					return;
				}

				const counters = data[0];

				if (!counters[this.role])
				{
					console.log({error: `${this.role} not found in counters`, counters});
				}

				this.counters = {};

				Object.keys(Filter.getPresetsMap()).forEach((presetType) => {
					const subPresets = Filter.getPresetsMap()[presetType];
					const counter = counters[presetType];

					this.counters[presetType] = counter;
					this.counters[`${presetType}_total`] = 0;

					subPresets.forEach((subPresetType) => {
						const subCounter = counter[subPresetType];
						const subCounterValue = (subCounter ? Number(subCounter) : 0);

						this.counters[`${presetType}_total`] += subCounterValue;
					});
				});

				this.setVisualCounters();
				this.saveCache();

				resolve();
			});
		}

		setVisualCounters(value)
		{
			const counterValue = value || this.getCounterValue();

			this.setDownMenuTasksCounter(counterValue);
			this.setListTopButtonCounter(counterValue);
		}

		setDownMenuTasksCounter(value)
		{
			if (this.isMyList())
			{
				Application.setBadges({tasks: value});
			}
		}

		setListTopButtonCounter(value)
		{
			if (this.isMyList())
			{
				Application.setBadges({[`tasks_list_more_button_${this.owner.id}_${this.role}`]: value});
			}
		}

		getCounterValue(type = Filter.roleType.all, subType = 'total')
		{
			if (!this.counters[type] && !this.counters[`${type}_${subType}`])
			{
				return 0;
			}

			if (subType === 'total')
			{
				return this.counters[`${type}_${subType}`] || 0;
			}

			return this.counters[type][subType] || 0;
		}

		setCounterValue(value)
		{
			this.counters.view_all_total = (this.isMyList() ? value : 0);
		}

		saveCache()
		{
			if (this.isMyList())
			{
				this.cache.set({counterValue: this.getCounterValue()});
			}
		}

		get()
		{
			const filterRoleMap = {
				[Filter.roleType.responsible]: 'R',
				[Filter.roleType.accomplice]: 'A',
				[Filter.roleType.originator]: 'O',
				[Filter.roleType.auditor]: 'U',
			};
			const currentUserId = this.owner.id || this.currentUser.id;
			const filter = {
				MEMBER: currentUserId,
				ZOMBIE: 'N',
				CHECK_PERMISSIONS: 'Y',
				IS_PINNED: 'N',
			};

			if (filterRoleMap[this.role])
			{
				filter.ROLE = filterRoleMap[this.role];
			}

			if (!this.isMyList())
			{
				delete filter.IS_PINNED;
			}

			if (this.groupId > 0)
			{
				filter.GROUP_ID = this.groupId;
				delete filter.MEMBER;
			}

			if (!this.showCompleted)
			{
				filter['::SUBFILTER-STATUS-OR'] = {
					'::LOGIC': 'OR',
					'::SUBFILTER-1': {
						REAL_STATUS: [2, 3, 4, 6],
					},
					'::SUBFILTER-2': {
						WITH_NEW_COMMENTS: 'Y',
					},
				};
			}

			switch (this.counter)
			{
				case Filter.counterType.newComments:
					filter.WITH_NEW_COMMENTS = 'Y';
					filter.IS_MUTED = 'N';
					break;

				case Filter.counterType.expired:
					filter.REAL_STATUS = [2, 3];
					filter.IS_MUTED = 'N';
					filter['<=DEADLINE'] = (new Date()).toISOString();
					break;

				case Filter.counterType.supposedlyCompleted:
					if (this.role === Filter.roleType.all || this.role === Filter.roleType.originator)
					{
						filter.CREATED_BY = currentUserId;
						filter['!RESPONSIBLE_ID'] = currentUserId;
					}
					else if (this.role === Filter.roleType.responsible)
					{
						filter.RESPONSIBLE_ID = currentUserId;
					}
					else if (this.role === Filter.roleType.accomplice)
					{
						filter.ACCOMPLICE = currentUserId;
					}
					else if (this.role === Filter.roleType.auditor)
					{
						filter.AUDITOR = currentUserId;
					}
					filter.REAL_STATUS = 4;
					break;

				case Filter.counterType.none:
				default:
					break;
			}

			return filter;
		}

		getForPinned()
		{
			const filter = this.get();
			filter.IS_PINNED = 'Y';

			return filter;
		}

		getForSearch(text)
		{
			const filter = {
				SEARCH_INDEX: text,
				MEMBER: this.currentUser.id,
			};

			if (!this.showCompleted)
			{
				filter['::SUBFILTER-STATUS-OR'] = {
					'::LOGIC': 'OR',
					'::SUBFILTER-1': {
						REAL_STATUS: [2, 3, 4, 6],
					},
					'::SUBFILTER-2': {
						WITH_NEW_COMMENTS: 'Y',
					},
				};
			}

			return filter;
		}

		isMyList()
		{
			return Number(this.currentUser.id) === Number(this.owner.id) && !this.groupId;
		}

		getRole()
		{
			return this.role;
		}

		setRole(role)
		{
			this.role = role;
		}

		getCounter()
		{
			return this.counter;
		}

		setCounter(counter)
		{
			this.counter = counter;
		}
	}

	class TasksListCache extends Cache
	{
		constructor(storageName)
		{
			super(storageName);
			this.init();
		}

		static getInstance(id)
		{
			if (!caches.has(id))
			{
				caches.set(id, (new TasksListCache(id)));
			}
			return caches.get(id);
		}

		init()
		{
			const roles = [
				Filter.roleType.all,
				Filter.roleType.responsible,
				Filter.roleType.accomplice,
				Filter.roleType.originator,
				Filter.roleType.auditor,
			];
			const counters = [
				Filter.counterType.none,
				Filter.counterType.expired,
				Filter.counterType.newComments,
				Filter.counterType.supposedlyCompleted,
			];
			const has = Object.prototype.hasOwnProperty;
			const cache = this.get();

			roles.forEach((role) => {
				if (!has.call(cache, role))
				{
					cache[role] = {};
				}
				counters.forEach((counter) => {
					if (!has.call(cache[role], counter))
					{
						cache[role][counter] = [];
					}
				});
			});
			this.set(cache);
		}

		/**
		 * @param {object} tasks
		 */
		addTask(tasks)
		{
			const cache = this.get();
			if (!cache || Object.keys(cache).length === 0)
			{
				return;
			}

			const {task, taskData} = tasks[Object.keys(tasks)[0]];
			const roleMap = {
				[Filter.roleType.all]: task.isMember(),
				[Filter.roleType.responsible]: task.isResponsible(),
				[Filter.roleType.accomplice]: task.isAccomplice(),
				[Filter.roleType.originator]: task.isPureCreator(),
				[Filter.roleType.auditor]: task.isAuditor(),
			};
			Object.keys(roleMap).forEach((role) => {
				if (roleMap[role])
				{
					const {existing} = Filter.getCountersByRole(role, task);
					existing.forEach(counter => cache[role][counter].splice(0, 0, taskData));
				}
			});
			this.set(cache);
			this.sortTasks();
		}

		/**
		 * @param {Object} tasks
		 */
		setTaskData(tasks)
		{
			const cache = this.get();
			if (!cache || Object.keys(cache).length === 0)
			{
				return;
			}

			Object.keys(tasks).forEach((taskId) => {
				const {task, taskData} = tasks[taskId];
				const roleMap = {
					[Filter.roleType.all]: task.isMember(),
					[Filter.roleType.responsible]: task.isResponsible(),
					[Filter.roleType.accomplice]: task.isAccomplice(),
					[Filter.roleType.originator]: task.isPureCreator(),
					[Filter.roleType.auditor]: task.isAuditor(),
				};
				Object.keys(roleMap).forEach((role) => {
					const {existing, notExisting} = Filter.getCountersByRole(role, task);
					if (roleMap[role])
					{
						existing.forEach((counter) => {
							const index = cache[role][counter].findIndex(cachedTask => cachedTask.id === taskId);
							if (index === -1)
							{
								cache[role][counter].splice(0, 0, taskData);
							}
							else
							{
								cache[role][counter][index] = taskData;
							}
						});
						notExisting.forEach((counter) => {
							const index = cache[role][counter].findIndex(cachedTask => cachedTask.id === taskId);
							if (index !== -1)
							{
								cache[role][counter].splice(index, 1);
							}
						});
					}
					else
					{
						existing.concat(notExisting).forEach((counter) => {
							const index = cache[role][counter].findIndex(cachedTask => cachedTask.id === taskId);
							if (index !== -1)
							{
								cache[role][counter].splice(index, 1);
							}
						});
					}
				});
			});
			this.set(cache);
			this.sortTasks();
		}

		/**
		 * @param {string} taskId
		 */
		removeTask(taskId)
		{
			const cache = this.get();
			if (!cache || Object.keys(cache).length === 0)
			{
				return;
			}
			Object.keys(cache).forEach((role) => {
				if (Object.values(Filter.roleType).includes(role))
				{
					Object.keys(cache[role]).forEach((counter) => {
						if (Object.values(Filter.counterType).includes(counter))
						{
							const index = cache[role][counter].findIndex(cachedTask => cachedTask.id === taskId);
							if (index !== -1)
							{
								cache[role][counter].splice(index, 1);
							}
						}
					});
				}
			});
			this.set(cache);
		}

		sortTasks()
		{
			const cache = this.get();
			if (!cache || Object.keys(cache).length === 0)
			{
				return;
			}
			Object.keys(cache).forEach((role) => {
				if (Object.values(Filter.roleType).includes(role))
				{
					Object.keys(cache[role]).forEach((counter) => {
						if (Object.values(Filter.counterType).includes(counter))
						{
							const pinned = cache[role][counter].filter(task => task.sectionCode === 'pinned');
							const others = cache[role][counter].filter(task => task.sectionCode !== 'pinned');
							cache[role][counter] = pinned.sort(TasksListCache.compare).concat(others.sort(TasksListCache.compare));
						}
					});
				}
			});
			this.set(cache);
		}

		static compare(x, y)
		{
			if (x.sortValues.activityDate > y.sortValues.activityDate)
			{
				return -1;
			}
			if (x.sortValues.activityDate < y.sortValues.activityDate)
			{
				return 1;
			}
			return 0;
		}
	}

	class Options
	{
		constructor()
		{
			const now = new Date();
			now.setDate(now.getDate() - 1);

			this.cache = Cache.getInstance('tasks.task.list.options');
			this.cache.setDefaultData({
				swipeShowHelper: {
					value: 0,
					limit: 2,
				},
				deadlines: {
					lastTime: now.getTime(),
					value: result.deadlines,
				},
				efficiency: false,
			});
		}

		get()
		{
			return this.cache.get();
		}

		set(options)
		{
			this.cache.set(options);
		}

		update(optionName, optionValue)
		{
			this.cache.update(optionName, optionValue);
		}
	}

	class Pull
	{
		/**
		 * @param {TaskList} list
		 */
		constructor(list)
		{
			this.list = list;
			this.queue = new Set();
			this.canExecute = true;
		}

		/**
		 * @return {Object}
		 */
		getEventHandlers()
		{
			return {
				task_view: {
					method: this.list.onPullView,
					context: this.list,
				},
				task_add: {
					method: this.list.onPullAdd,
					context: this.list,
				},
				task_update: {
					method: this.list.onPullUpdate,
					context: this.list,
				},
				task_remove: {
					method: this.list.onPullDelete,
					context: this.list,
				},
				comment_add: {
					method: this.list.onPullComment,
					context: this.list,
				},
				comment_read_all: {
					method: this.list.onPullCommentReadAll,
					context: this.list,
				},
				user_option_changed: {
					method: this.list.onUserOptionChanged,
					context: this.list,
				},
				user_efficiency_counter: {
					method: this.list.onUserEfficiencyCounter,
					context: this.list,
				},
				user_counter: {
					method: this.list.filter.onUserCounter,
					context: this.list.filter,
				},
			};
		}

		subscribe()
		{
			BX.PULL.subscribe({
				moduleId: 'tasks',
				callback: data => this.processPullEvent(data),
			});
		}

		clear()
		{
			this.queue.clear();
		}

		freeQueue()
		{
			const commonCommands = [
				'comment_read_all',
				'user_efficiency_counter',
				'user_counter',
			];
			this.queue = new Set([...this.queue].filter(event => commonCommands.includes(event.command)));

			const clearDuplicates = (result, event) => {
				if (
					typeof result[event.command] === 'undefined'
					|| event.extra.server_time_ago < result[event.command].extra.server_time_ago
				)
				{
					result[event.command] = event;
				}
				return result;
			};
			this.queue = new Set(Object.values([...this.queue].reduce(clearDuplicates, {})));

			const promises = [...this.queue].map(event => this.executePullEvent(event));
			return Promise.allSettled(promises);
		}

		processTaskEvents()
		{
			const processedTasks = new Map();

			this.queue.forEach((event) => {
				const has = Object.prototype.hasOwnProperty;
				const eventHandlers = this.getEventHandlers();
				const {command, params} = event;

				if (has.call(eventHandlers, command))
				{
					const taskId = params.TASK_ID || params.taskId || 0;
					if (taskId)
					{
						processedTasks.set(Number(taskId));
						this.queue.delete(event);
					}
				}
			});

			return processedTasks;
		}

		/**
		 * @param {object} data
		 */
		processPullEvent(data)
		{
			if (this.canExecute)
			{
				void this.executePullEvent(data);
			}
			else
			{
				this.queue.add(data);
			}
		}

		/**
		 * @param {object} data
		 */
		executePullEvent(data)
		{
			return new Promise((resolve, reject) => {
				const has = Object.prototype.hasOwnProperty;
				const eventHandlers = this.getEventHandlers();
				const {command, params} = data;
				if (has.call(eventHandlers, command))
				{
					const {method, context} = eventHandlers[command];
					if (method)
					{
						method.apply(context, [params]).then(() => resolve(), () => reject()).catch(() => reject());
					}
				}
			});
		}

		/**
		 * @return {boolean}
		 */
		getCanExecute()
		{
			return this.canExecute;
		}

		/**
		 * @param {boolean} canExecute
		 */
		setCanExecute(canExecute)
		{
			this.canExecute = canExecute;
		}
	}

	class Push
	{
		constructor(list)
		{
			this.list = list;

			this.manager = Application.getNotificationHistory('tasks_task');

			this.events = new Map();
			this.processedTasks = new Map();
		}

		updateList()
		{
			const data = this.manager.get();
			if (!data)
			{
				return;
			}

			const regs = [
				new RegExp('TASKS\\|([A-Z_]+)\\|([0-9]+)\\|([0-9]+)\\|([A-Z_]+)'),
				new RegExp('TASKS\\|([A-Z_]+)\\|([0-9]+)\\|([0-9]+)\\|([0-9]+)\\|(.+)')
			];

			Object.entries(data).forEach(([key, value]) => {
				for (const i in regs)
				{
					const matches = key.match(regs[i]);
					if (matches)
					{
						const [, entityType, taskId] = matches;
						let action;

						if (entityType === 'TASK')
						{
							action = matches[4];
						}
						else if (entityType === 'COMMENT')
						{
							action = matches[5];
						}

						this.collectEvents(value, taskId, action);
						return;
					}
				}
			});

			this.processEvents();
			this.manager.clear();
		}

		collectEvents(events, taskId, action)
		{
			events.forEach((event) => {
				const time = event.extra.server_time_unix;
				if (!this.events.has(taskId) || this.events.get(taskId).time < time)
				{
					this.events.set(taskId, {
						time,
						action,
						data: event.data,
					});
				}
			});
		}

		processEvents()
		{
			this.events.forEach((fields, taskId) => {
				switch (fields.action)
				{
					case 'TASK_DELETE':
						if (this.list.taskList.has(taskId))
						{
							this.removeTask(taskId);
						}
						break;

					case 'TASK_ADD':
						if (!this.list.taskList.has(taskId))
						{
							this.addTask(this.convertFields(fields.data));
						}
						break;

					case 'TASK_UPDATE':
					case 'TASK_STATUS_CHANGED_MESSAGE':
					case 'TASK_EXPIRED_SOON':
					case 'TASK_EXPIRED':
					case 'TASK_PINGED_STATUS':
					{
						const taskData = this.convertFields(fields.data);

						this.list.taskList.has(taskId)
							? this.updateTask(taskId, taskData)
							: this.addTask(taskData)
						;
						break;
					}

					default:
						break;
				}
			});
		}

		addTask(taskData)
		{
			const task = new Task(this.list.currentUser);
			task.setData(taskData);

			if (this.list.isTaskSuitList(task))
			{
				this.list.addItem(task);
				this.processedTasks.set(Number(task.id));
			}
		}

		updateTask(taskId, taskData)
		{
			const task = this.list.taskList.get(taskId);
			task.updateData(taskData);

			if (this.list.isTaskSuitList(task))
			{
				this.list.updateItem(taskId, task);
				this.processedTasks.set(Number(taskId));
			}
			else
			{
				this.removeTask(taskId);
			}
		}

		removeTask(taskId)
		{
			this.list.search.removeTask(taskId);
			this.list.removeItem(taskId);
		}

		convertFields(fields)
		{
			const map = {
				1: 'id',
				2: 'title',
				3: 'deadline',
				4: 'activityDate',
				5: 'status',
				6: 'newCommentsCount',

				20: 'groupId',
				21: 'group',
				22: 'image',
				23: 'name',

				30: 'creator',
				31: 'responsible',
				32: 'icon',

				41: 'accomplices',
				42: 'auditors',
			};
			const convertRecursively = function(object) {
				if (!object || typeof object !== 'object')
				{
					return object;
				}

				if (object instanceof Array)
				{
					return object.map(element => convertRecursively(element));
				}

				const result = {};
				for (const field in object)
				{
					if (!object.hasOwnProperty(field))
					{
						continue;
					}

					const key = map[field] ? map[field]: field;
					result[key] = convertRecursively(object[field]);
				}

				return result;
			};

			return convertRecursively(fields);
		}

		clear()
		{
			this.events.clear();
			this.manager.clear();
			this.processedTasks.clear();
		}
	}

	class TaskList
	{
		static getItemDataFromUser(user)
		{
			return {
				id: user.id,
				name: user.title,
				icon: user.imageUrl,
				link: '',
			};
		}

		static getItemDataFromGroup(group)
		{
			return {
				id: group.id,
				name: group.title,
				image: group.imageUrl,
			};
		}

		static get selectFields()
		{
			return [
				'ID',
				'TITLE',
				'STATUS',
				'CREATED_BY',
				'ACTIVITY_DATE',
				'RESPONSIBLE_ID',
				'DEADLINE',
				'COMMENTS_COUNT',
				'AUDITORS',
				'ACCOMPLICES',
				'NEW_COMMENTS_COUNT',
				'FAVORITE',
				'NOT_VIEWED',
				'GROUP_ID',
				'IS_MUTED',
				'IS_PINNED',
			];
		}

		static get statusList()
		{
			return Task.statusList;
		}

		static get titles()
		{
			return {
				[Filter.roleType.all]: BX.message('TASKS_LIST_HEADER_ROLE_ALL'),
				[Filter.roleType.responsible]: BX.message('TASKS_LIST_HEADER_ROLE_RESPONSIBLE'),
				[Filter.roleType.accomplice]: BX.message('TASKS_LIST_HEADER_ROLE_ACCOMPLICE'),
				[Filter.roleType.originator]: BX.message('TASKS_LIST_HEADER_ROLE_ORIGINATOR'),
				[Filter.roleType.auditor]: BX.message('TASKS_LIST_HEADER_ROLE_AUDITOR'),
				deadlines: BX.message('TASKS_LIST_HEADER_DEADLINES'),
			};
		}

		constructor(list, owner)
		{
			console.log('taskList.constructor');

			this.list = list;
			this.owner = {id: owner};
			this.currentUser = result.settings.userInfo;

			this.taskList = new Map();
			this.newTaskList = new Map();
			this.comments = new Map();

			const data = BX.componentParameters.get('DATA', {});
			this.groupId = parseInt(BX.componentParameters.get('GROUP_ID', 0), 10);
			this.group = {
				id: this.groupId,
				name: data.groupName,
				imageUrl: data.groupImageUrl,
			};

			this.start = 0;
			this.pageSize = 50;
			this.canShowSwipeActions = true;
			this.isRepeating = false;

			this.filter = new Filter(this.list, this.currentUser, this.owner, this.groupId);
			this.order = new Order();
			this.options = new Options();

			this.pull = new Pull(this);
			this.push = new Push(this);
			this.search = new Search(this);

			this.cache = TasksListCache.getInstance(`tasks.task.list_v3_${owner}`);

			this.getTaskLimitExceeded();
			this.getEfficiencyCounter();
			this.checkDeadlinesUpdate();
			this.prepareTaskGroupList();

			BX.addCustomEvent('onPullEvent-tasks', (command, params) => {
				if (command === 'user_counter')
				{
					this.filter.onUserCounter.apply(this.filter, [params]);
				}
			});

			BX.onViewLoaded(() => {
				this.list.setItems([
					{
						type: 'loading',
						title: BX.message('TASKS_LIST_BUTTON_NEXT_PROCESS'),
					},
				]);

				this.filter.updateCounters();

				this.setListListeners();
				this.bindEvents();
				this.setTopButtons();

				this.loadTasksFromCache();
				this.reload();
			});
		}

		isMyList()
		{
			return Number(this.currentUser.id) === Number(this.owner.id) && !this.groupId;
		}

		setListListeners()
		{
			const eventHandlers = {
				onRefresh: {
					callback: () => {
						if (!this.isRepeating)
						{
							this.reload();
							this.filter.updateCounters();
						}
					},
					context: this,
				},
				onNewItem: {
					callback: this.onNewItem,
					context: this,
				},
				onUserTypeText: {
					callback: this.search.onUserTypeText,
					context: this.search,
				},
				onSearchItemSelected: {
					callback: this.search.onSearchItemSelected,
					context: this.search,
				},
				onSearchShow: {
					callback: this.search.onSearchShow,
					context: this.search,
				},
				onSearchHide: {
					callback: this.search.onSearchHide,
					context: this.search,
				},
				onProjectSelected: {
					callback: this.onProjectSelected,
					context: this,
				},
				onItemSelected: {
					callback: this.onItemSelected,
					context: this,
				},
				onItemAction: {
					callback: this.onItemAction,
					context: this,
				},
				onItemChecked: {
					callback: this.onItemChecked,
					context: this,
				},
			};

			this.list.setListener((event, data) => {
				console.log(`Fire event: app.${event}`);
				if (eventHandlers[event])
				{
					eventHandlers[event].callback.apply(eventHandlers[event].context, [data]);
				}
			});
		}

		bindEvents()
		{
			BX.addCustomEvent('onAppActiveBefore', () => this.onAppActiveBefore());
			BX.addCustomEvent('onAppActive', () => this.onAppActive());
			BX.addCustomEvent('onAppPaused', () => this.onAppPaused());
			BX.addCustomEvent('task.view.onCommentsRead', eventData => this.onCommentsRead(eventData));
			BX.addCustomEvent('task.list.onToggleCompleted', () => this.onToggleCompleted());

			if (apiVersion >= 34)
			{
				BX.addCustomEvent('onTabsSelected', tabName => this.onTabsSelected(tabName));
			}

			this.pull.subscribe();
		}

		setTopButtons()
		{
			const isMainScreen = this.filter.getRole() === Filter.roleType.all
				&& this.filter.getCounter() === Filter.counterType.none;

			this.list.setRightButtons([
				{
					type: 'search',
					callback: () => this.list.showSearchBar(),
				},
				{
					type: (isMainScreen ? 'more' : 'more_active'),
					badgeCode: `tasks_list_more_button_${this.owner.id}_${this.filter.getRole()}`,
					callback: () => this.initPopupMenu(),
				},
			]);
			this.filter.setVisualCounters();
		}

		loadTasksFromCache()
		{
			console.log('loadTasksFromCache');

			BX.onViewLoaded(() => {
				const role = this.filter.getRole();
				const counter = this.filter.getCounter();
				const has = Object.prototype.hasOwnProperty;
				const cache = this.cache.get();

				let cachedTasks = [];
				if (has.call(cache, role) && has.call(cache[role], counter))
				{
					cachedTasks = cache[role][counter] || [];
				}
				if (!Array.isArray(cachedTasks) || cachedTasks.length < 1)
				{
					console.log('tasks cache is empty');
					return;
				}
				this.list.setItems(cachedTasks, null, BX.componentParameters.get('ANIMATE_CACHE_TASKS_LOADING', true));
			});
		}

		reload(taskListOffset = 0, showLoading = false)
		{
			BX.onViewLoaded(() => {
				console.log('reload');

				if (!this.isRepeating)
				{
					if (showLoading)
					{
						this.showLoading();
					}
					this.updateTitle(true);
				}

				let select = TaskList.selectFields;
				if (!this.isMyList())
				{
					select = select.filter(field => field !== 'IS_PINNED');
				}

				const {restNamespace} = new Request();
				const batchOperations = {
					all: [`${restNamespace}list`, {
						select,
						filter: this.filter.get(),
						order: this.order.get(),
						start: taskListOffset,
						params: {
							RETURN_ACCESS: 'Y',
							RETURN_USER_INFO: 'Y',
							RETURN_GROUP_INFO: 'Y',
							SEND_PULL: 'Y',
							COUNT_TOTAL: 'N',
						},
					}],
				};

				if (!taskListOffset && this.isMyList())
				{
					batchOperations.pinned = [`${restNamespace}list`, {
						select,
						filter: this.filter.getForPinned(),
						order: this.order.get(),
						params: {
							RETURN_ACCESS: 'Y',
							RETURN_USER_INFO: 'Y',
							RETURN_GROUP_INFO: 'Y',
							SEND_PULL: 'Y',
							COUNT_TOTAL: 'N',
						},
					}];
				}

				BX.rest.callBatch(batchOperations, (result) => {
					this.onReloadSuccess(result, showLoading, taskListOffset);
				});
			});
		}

		onReloadSuccess(response, showLoading, taskListOffset)
		{
			console.log('onReloadSuccess', response);

			const {all, pinned} = response;

			// if (Number(all.status) !== 200 || (pinned && Number(pinned.status) !== 200))
			// {
			// 	this.isRepeating = true;
			// 	setTimeout(() => this.reload(taskListOffset, showLoading), 5000);
			// 	return;
			// }
			// this.isRepeating = false;

			const tasks = (all ? all.answer.result.tasks : []) || [];
			const isNextPageExist = tasks.length > this.pageSize;
			if (isNextPageExist)
			{
				tasks.splice(this.pageSize, 1);
			}
			const pinnedTasks = (pinned ? pinned.answer.result.tasks : []) || [];
			const allTasks = pinnedTasks.concat(tasks);
			const isFirstPage = (taskListOffset === 0);

			this.start = taskListOffset + this.pageSize;

			if (isFirstPage)
			{
				this.comments.clear();
				this.taskList.clear();
				this.newTaskList.clear();
			}
			this.updateSections(isFirstPage);

			const items = [];
			allTasks.forEach((row) => {
				const task = new Task(this.currentUser);
				task.setData(row);
				const item = this.getItemDataFromTask(task);

				items.push(item);
				this.taskList.set(task.id, task);
			});

			if (isFirstPage && this.order.isActivityDate())
			{
				this.fillCache(items);
			}

			this.renderTaskListItems(items, isFirstPage, isNextPageExist);

			if (showLoading)
			{
				this.hideLoading();
			}
			this.updateTitle();
			this.list.stopRefreshing();
		}

		updateSections(clear = true)
		{
			const sectionHandler = SectionHandler.getInstance();

			if (clear)
			{
				console.log('sections.clear');
				sectionHandler.clear();
			}

			sectionHandler.setSortItemParams('default', {
				[this.order.order]: Order.sectionOrderFields[this.order.order],
			});
			sectionHandler.setSortItemParams('pinned', {
				[this.order.order]: Order.sectionOrderFields[this.order.order],
			});

			if (!sectionHandler.has('more'))
			{
				sectionHandler.add({id: 'more'});
			}

			this.list.setSections(sectionHandler.list);
		}

		fillCache(list)
		{
			console.log('fillCache');

			const role = this.filter.getRole();
			const counter = this.filter.getCounter();
			const cache = this.cache.get();
			cache[role][counter] = list;

			this.cache.set(cache);
		}

		/**
		 * @param {array} items
		 * @param {boolean} isFirstPage
		 * @param {boolean} isNextPageExist
		 */
		renderTaskListItems(items, isFirstPage, isNextPageExist)
		{
			if (items.length <= 0)
			{
				this.list.setItems([{
					id: '-none-',
					title: BX.message('TASKS_LIST_NOTHING_NOT_FOUND'),
					type: 'button',
					sectionCode: 'default',
					unselectable: true,
				}]);
				return;
			}

			if (isFirstPage)
			{
				const itemsWithHandledActions = this.handleSwipeActionsShow(items);
				this.list.setItems(itemsWithHandledActions, null, true);
				setTimeout(() => {
					this.list.updateItem({id: itemsWithHandledActions[0].id}, {showSwipeActions: false});
				}, 300);
			}
			else
			{
				this.list.removeItem({id: '-more-'});
				this.list.addItems(items);
			}

			if (isNextPageExist)
			{
				this.list.addItems([{
					id: '-more-',
					title: BX.message('TASKS_LIST_BUTTON_NEXT'),
					type: 'button',
					sectionCode: 'more',
				}]);
			}
		}

		handleSwipeActionsShow(items)
		{
			const {swipeShowHelper} = this.options.get();

			if ((swipeShowHelper.value < swipeShowHelper.limit) && this.canShowSwipeActions)
			{
				this.canShowSwipeActions = false;

				items[0].showSwipeActions = true;
				swipeShowHelper.value += 1;

				const name = (obj => Object.keys(obj)[0]);
				this.options.update(name({swipeShowHelper}), swipeShowHelper);
			}

			return items;
		}

		showLoading()
		{
			if (apiVersion >= 34)
			{
				dialogs.showSpinnerIndicator({
					color: '#777777',
					backgroundColor: '#77FFFFFF',
				});
			}
		}

		hideLoading()
		{
			if (apiVersion >= 34)
			{
				dialogs.hideSpinnerIndicator();
			}
		}

		onAppPaused()
		{
			this.pauseTime = new Date();

			this.pull.setCanExecute(false);
			this.pull.clear();
			this.push.clear();
		}

		onAppActiveBefore()
		{
			BX.onViewLoaded(() => this.push.updateList());
		}

		onAppActive()
		{
			this.activationTime = new Date();
			this.canShowSwipeActions = true;

			if (this.pauseTime)
			{
				const timePassed = this.activationTime.getTime() - this.pauseTime.getTime();
				const minutesPassed = Math.abs(Math.round(timePassed / 60000));

				if (minutesPassed > 30)
				{
					this.runOnAppActiveRepeatedActions();
				}
				else
				{
					this.updateTasksFromEvents();
				}
			}

			this.getTaskLimitExceeded();
			this.getEfficiencyCounter();
			this.checkDeadlinesUpdate();
		}

		updateTasksFromEvents()
		{
			this.updateTitle(true);

			setTimeout(() => {
				const tasksToUpdate = new Map([
					...this.pull.processTaskEvents(),
					...this.push.processedTasks
				]);
				const taskIds = [...tasksToUpdate.keys()].map(taskId => taskId.toString());

				if (taskIds.length > 30)
				{
					this.runOnAppActiveRepeatedActions();
				}
				else
				{
					let promises = [
						new Promise((resolve) => {
							this.pull.setCanExecute(true);
							this.pull.freeQueue().then(() => resolve());
						}),
					];
					if (taskIds.length)
					{
						promises.push(this.updateRecentUpdatedTasks(taskIds));
					}
					Promise.allSettled(promises).then(() => this.updateTitle());
				}
			}, 1000);
		}

		updateRecentUpdatedTasks(taskIds)
		{
			return new Promise((resolve, reject) => {
				(new Request())
					.call('list', {
						select: TaskList.selectFields,
						filter: {
							ID: taskIds,
						},
						params: {
							RETURN_ACCESS: 'Y',
							RETURN_USER_INFO: 'Y',
							RETURN_GROUP_INFO: 'Y',
						},
					})
					.then(
						(response) => {
							this.onRecentUpdatedTasksDataLoaded(taskIds, response);
							resolve();
						},
						(response) => {
							console.error(response);
							reject();
						}
					)
				;
			});
		}

		onRecentUpdatedTasksDataLoaded(taskIds, response)
		{
			const {tasks} = response.result;

			taskIds.forEach((taskId) => {
				const taskData = tasks.find(task => task.id === taskId);
				if (taskData)
				{
					if (this.taskList.has(taskId))
					{
						const task = this.taskList.get(taskId);
						task.setData(taskData);

						this.isTaskSuitList(task)
							? this.updateItem(taskId, task)
							: this.removeItem(taskId)
						;
					}
					else
					{
						const task = new Task(this.currentUser);
						task.setData(taskData);

						this.isTaskSuitList(task)
							? this.addItem(task)
							: this.removeItem(taskId)
						;
					}
				}
				else if (this.taskList.has(taskId))
				{
					this.removeItem(taskId);
				}
			});
		}

		runOnAppActiveRepeatedActions()
		{
			this.filter.updateCounters();
			this.reload();

			this.prepareTaskGroupList();

			this.pull.setCanExecute(true);
		}

		prepareTaskGroupList()
		{
			TaskGroupList.loadLastActiveProjects();
			TaskGroupList.validateLastSearchedProjects();
		}

		getTaskLimitExceeded()
		{
			(new Request()).call('limit.isExceeded').then((response) => {
				console.log('taskList:limit.isExceeded', response.result);
				this.taskLimitExceeded = response.result || false;
			});
		}

		checkDeadlinesUpdate()
		{
			const now = new Date();
			const lastDeadlinesGetTime = new Date(this.options.get().deadlines.lastTime);
			const dayChanged = now.getDate() !== lastDeadlinesGetTime.getDate();

			if (dayChanged)
			{
				(new Request('mobile.tasks.'))
					.call('deadlines.get')
					.then(response => this.options.update('deadlines', {
						lastTime: now.getTime(),
						value: response.result,
					}));
			}
		}

		getEfficiencyCounter()
		{
			this.efficiency = false;

			if (!this.isMyList())
			{
				return;
			}

			this.efficiency = this.options.get().efficiency;

			(new Request('mobile.tasks.'))
				.call('efficiency.get', {
					userId: this.owner.id,
				})
				.then((response) => {
					this.efficiency = response.result;
					this.options.update('efficiency', this.efficiency);
				});
		}

		onUserEfficiencyCounter(eventData)
		{
			console.log('onUserEfficiencyCounter', eventData, this.isMyList());

			if (!this.isMyList())
			{
				return;
			}

			const {value} = eventData;
			this.efficiency = value;
			this.options.update('efficiency', this.efficiency);
		}

		onTabsSelected(tabName)
		{
			const isMainScreen = this.filter.getRole() === Filter.roleType.all
				&& this.filter.getCounter() === Filter.counterType.none;

			if (tabName !== 'tasks' && (!isMainScreen || this.order.isDeadline()))
			{
				setTimeout(() => this.list.back(), 300);
			}
		}

		onCommentsRead(eventData)
		{
			console.log('task.view.onCommentsRead', eventData);

			const taskId = String(eventData.taskId);
			if (this.taskList.has(taskId))
			{
				this.updateItem(taskId, {newCommentsCount: 0});
			}
		}

		onUserOptionChanged(data)
		{
			return new Promise((resolve, reject) => {
				if (Number(data.USER_ID) !== Number(this.currentUser.id))
				{
					resolve();
					return;
				}

				const taskId = data.TASK_ID.toString();
				const added = data.ADDED;

				switch (Number(data.OPTION))
				{
					case Task.userOptions.muted:
						this.onMuteChanged(taskId, added);
						resolve();
						break;

					case Task.userOptions.pinned:
						this.onPinChanged(taskId, added).then(() => resolve()).catch(() => reject());
						break;

					default:
						break;
				}
			});
		}

		onMuteChanged(taskId, added)
		{
			if (this.taskList.has(taskId))
			{
				this.updateItem(taskId, {isMuted: added});
			}
		}

		onPinChanged(taskId, added)
		{
			return new Promise((resolve, reject) => {
				if (this.taskList.has(taskId))
				{
					this.updateItem(taskId, {isPinned: added});
					resolve();
				}
				else if (added)
				{
					(new Request())
						.call('get', {taskId})
						.then(
							(response) => {
								this.updateTaskListItem(taskId, response.result.task, 'pinned');
								resolve();
							},
							(response) => {
								console.log(response);
								reject();
							}
						);
				}
			});
		}

		onPullView(data)
		{
			return new Promise((resolve) => {
				if (Number(data.USER_ID) !== Number(this.currentUser.id))
				{
					return;
				}

				const taskId = data.TASK_ID.toString();
				if (this.taskList.has(taskId))
				{
					this.updateItem(taskId, {newCommentsCount: 0});
				}
				resolve();
			});
		}

		onPullComment(data)
		{
			return new Promise((resolve, reject) => {
				console.log('onPullComment', data);

				const [entityType, entityId] = data.entityXmlId.split('_');
				const {messageId} = data;

				if (!this.comments.has(entityId))
				{
					this.comments.set(entityId, new Set());
				}
				const taskComments = this.comments.get(entityId);

				if (entityType !== 'TASK' || taskComments.has(messageId))
				{
					resolve();
					return;
				}

				taskComments.add(messageId);
				this.comments.set(entityId, taskComments);

				(new Request())
					.call('get', {taskId: entityId})
					.then(
						(response) => {
							this.updateTaskListItem(entityId, response.result.task);
							resolve();
						},
						(response) => {
							console.log(response);
							reject();
						}
					);
			});
		}

		onPullCommentReadAll(data)
		{
			return new Promise((resolve) => {
				const groupId = Number(data.GROUP_ID);
				const userId = Number(data.USER_ID);

				if (
					(groupId > 0 && groupId !== this.groupId)
					|| (userId > 0 && userId !== this.owner.id)
				)
				{
					resolve();
					return;
				}

				const items = [];
				const tasks = {};

				this.taskList.forEach((task) => {
					task.newCommentsCount = 0;
					const item = this.getItemDataFromTask(task);
					items.push(item);
					tasks[task.id] = {task, taskData: item};
				});
				this.list.setItems(items, null, true);
				this.cache.setTaskData(tasks);

				resolve();
			});
		}

		onPullAdd(data)
		{
			return new Promise((resolve, reject) => {
				console.log('onPullAdd');

				if (data.params.addCommentExists !== false)
				{
					console.log('onPullAdd -> addCommentExists');
					resolve();
					return;
				}

				const taskId = data.TASK_ID.toString();
				const select = TaskList.selectFields;

				if (this.taskList.has(taskId))
				{
					resolve();
					return;
				}

				(new Request())
					.call('get', {taskId, select})
					.then(
						(response) => {
							this.updateTaskListItem(taskId, response.result.task);
							resolve();
						},
						(response) => {
							console.log(response);
							reject();
						}
					);
			});
		}

		onPullUpdate(data)
		{
			return new Promise((resolve, reject) => {
				console.log('onPullUpdate', data);

				if (data.params.updateCommentExists !== false)
				{
					console.log('onPullUpdate -> updateCommentExists');
					resolve();
					return;
				}

				const taskId = data.TASK_ID.toString();
				const select = TaskList.selectFields;

				(new Request())
					.call('get', {taskId, select})
					.then(
						(response) => {
							this.updateTaskListItem(taskId, response.result.task);
							resolve();
						},
						(response) => {
							console.log('onPullUpdate.get.error', response);
							reject();
						}
					);
			});
		}

		updateTaskListItem(taskId, row, sectionId = 'default')
		{
			const isMyNewTask = this.newTaskList.has(row.guid) || this.newTaskList.has(row.id);

			if (this.taskList.has(taskId))
			{
				const task = this.taskList.get(taskId);
				task.setData(row);

				if (this.isTaskSuitList(task) || isMyNewTask)
				{
					this.updateItem(taskId, task);
				}
				else
				{
					this.removeItem(taskId);
				}
			}
			else if (!isMyNewTask)
			{
				const task = new Task(this.currentUser);
				task.setData(row);

				if (this.isTaskSuitList(task))
				{
					if (this.taskList.has(task.id))
					{
						this.updateItem(task.id, task);
					}
					else
					{
						this.addItem(task, sectionId);
					}
				}
			}
		}

		/**
		 * @param {Task} task
		 * @return {boolean}
		 */
		isTaskSuitList(task)
		{
			return this.isTaskSuitFilter(task)
				&& this.isTaskSuitGroup(task);
		}

		/**
		 * @param {Task} task
		 * @return {boolean}
		 */
		isTaskSuitFilter(task)
		{
			const role = this.filter.getRole();
			const roleMap = {
				[Filter.roleType.all]: task.isMember(),
				[Filter.roleType.responsible]: task.isResponsible(),
				[Filter.roleType.accomplice]: task.isAccomplice(),
				[Filter.roleType.originator]: task.isPureCreator(),
				[Filter.roleType.auditor]: task.isAuditor(),
			};

			if (roleMap[role])
			{
				const counter = this.filter.getCounter();
				const {existing} = Filter.getCountersByRole(role, task);

				return existing.includes(counter);
			}

			return false;
		}

		/**
		 * @param {Task} task
		 * @return {boolean}
		 */
		isTaskSuitGroup(task)
		{
			return (this.groupId > 0 && this.groupId === Number(task.groupId)) || !this.groupId;
		}

		onPullDelete(data)
		{
			return new Promise((resolve) => {
				console.log('onPullDelete');
				const taskId = data.TASK_ID.toString();

				this.search.removeTask(taskId);
				this.removeItem(taskId);

				resolve();
			});
		}

		onNewItem(title)
		{
			console.log('onNewItem');

			const task = new Task(this.currentUser);
			this.newTaskList.set(task.guid);

			task.title = title;
			task.creator = this.currentUser;
			task.responsible = this.currentUser;
			task.activityDate = Date.now();
			task.groupId = this.groupId;
			if (this.groupId > 0)
			{
				task.group = {
					id: this.group.id,
					name: this.group.name,
					image: this.group.imageUrl,
				};
			}

			this.addItem(task, 'new');

			const oldTaskId = task.id;
			task.save().then(() => {
				this.newTaskList.set(task.id);
				this.updateItem(oldTaskId, {id: task.id});
			}, e => console.error(e));
		}

		getItemDataFromTask(task, withActions = true)
		{
			let itemData = task.getTaskInfo(withActions);

			itemData.backgroundColor = Task.backgroundColors.default;
			itemData.sectionCode = 'default';
			itemData.type = 'task';
			itemData.sortValues = {
				deadline: task.deadline || 9999999999999,
				activityDate: task.activityDate,
			};

			if (task.isPinned)
			{
				itemData.backgroundColor = Task.backgroundColors.pinned;
				itemData.sectionCode = 'pinned';
			}

			if (withActions)
			{
				itemData = this.handleItemActions(itemData);
			}

			return itemData;
		}

		handleItemActions(itemData)
		{
			let {actions} = itemData;

			if (!this.isMyList())
			{
				actions = actions.filter(action => !['pin', 'unpin'].includes(action.identifier));
			}

			if (this.taskLimitExceeded)
			{
				actions = actions.filter(action => action.identifier !== 'delegate');
			}

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
								sectionCode: 'default',
							});
						}
					});

					popupActions.push(Task.actions.cancel);

					actions = swipeActions;
					itemData.params.popupActions = popupActions;
				}
			}
			else
			{
				actions = actions.map((action) => {
					action.iconUrl = Task.popupImageUrls[action.identifier];
					return action;
				});

				itemData.menuMode = 'dialog';
			}

			itemData.actions = actions;

			return itemData;
		}

		addItem(task, sectionId = 'default')
		{
			BX.onViewLoaded(() => {
				const taskId = task.id;
				if (this.taskList.has(taskId))
				{
					return;
				}

				this.removeItem('-none-');

				const taskData = this.getItemDataFromTask(task);
				taskData.sectionCode = sectionId;
				if (sectionId === 'new')
				{
					taskData.actions = taskData.actions.filter(item => item.identifier === 'changeResponsible');
				}

				this.taskList.set(taskId, task);
				this.list.addItems([taskData]);
				this.cache.addTask({[taskId]: {task, taskData}});
			});
		}

		updateItem(id, fields)
		{
			BX.onViewLoaded(() => {
				const taskId = id.toString();

				if (!this.taskList.has(taskId))
				{
					return;
				}

				const task = this.taskList.get(taskId);
				const has = Object.prototype.hasOwnProperty;

				if (fields.id && fields.id !== taskId)
				{
					this.taskList.delete(taskId);
					this.taskList.set(fields.id.toString(), task);
				}

				Object.keys(fields).forEach((key) => {
					if (has.call(task, key) || has.call(Object.getPrototypeOf(task), key))
					{
						task[key] = fields[key];
					}
				});

				const taskData = this.getItemDataFromTask(task);
				taskData.sectionCode = (
					this.newTaskList.has(task.guid) || this.newTaskList.has(task.id) ? 'new' : taskData.sectionCode
				);

				console.log(`updateItem #${id}`, taskData);
				this.list.updateItem({id}, taskData);
				this.cache.setTaskData({[taskId]: {task, taskData}});
			});
		}

		removeItem(id)
		{
			BX.onViewLoaded(() => {
				this.taskList.delete(id);
				this.list.removeItem({id});
				this.cache.removeTask(id);
			});
		}

		onProjectSelected(data)
		{
			if (this.groupId > 0)
			{
				return;
			}

			let projectName = '';
			this.taskList.forEach((task) => {
				if (Number(task.group.id) === Number(data.id))
				{
					projectName = task.group.name;
				}
			});
			BX.postComponentEvent('taskbackground::task::action', [{
				groupId: data.id,
				groupName: projectName,
				groupImageUrl: data.imageUrl,
			}]);
		}

		onItemSelected(item)
		{
			const taskId = item.id.toString();

			if (taskId === '-more-')
			{
				this.list.updateItem(
					{id: '-more-'},
					{
						type: 'loading',
						title: BX.message('TASKS_LIST_BUTTON_NEXT_PROCESS'),
					}
				);
				this.reload(this.start);
			}
			else if (this.taskList.has(taskId))
			{
				const task = this.taskList.get(taskId);

				this.filter.pseudoUpdateCounters(-task.newCommentsCount, task);
				this.updateItem(taskId, {newCommentsCount: 0});

				task.open();
			}
		}

		onItemChecked(item)
		{
			const task = this.taskList.get(item.id.toString());

			if (task.isCompletedCounts)
			{
				this.updateItem(task.id, {
					status: Task.statusList.pending,
					activityDate: Date.now(),
				});
				task.renew();
			}
			else
			{
				this.updateItem(task.id, {
					status: Task.statusList.completed,
					activityDate: Date.now(),
				});
				task.complete();
			}
		}

		onItemAction(event)
		{
			const task = this.taskList.get(event.item.id);

			switch (event.action.identifier)
			{
				case 'ping':
					this.onPingAction(task);
					break;

				case 'changeDeadline':
					this.onChangeDeadlineAction(task);
					break;

				case 'approve':
					this.onApproveAction(task);
					break;

				case 'disapprove':
					this.onDisapproveAction(task);
					break;

				case 'start':
					this.onStartAction(task);
					break;

				case 'pause':
					this.onPauseAction(task);
					break;

				case 'renew':
					this.onRenewAction(task);
					break;

				case 'changeResponsible':
					this.onChangeResponsibleAction(task);
					break;

				case 'delegate':
					this.onDelegateAction(task);
					break;

				case 'changeGroup':
					this.onChangeGroupAction(task);
					break;

				case 'mute':
					this.onMuteAction(task);
					break;

				case 'unmute':
					this.onUnmuteAction(task);
					break;

				case 'unfollow':
					this.onUnfollowAction(task);
					break;

				case 'remove':
					this.onRemoveAction(task);
					break;

				case 'more':
					this.onMoreAction(task);
					return;

				case 'pin':
					this.onPinAction(task);
					break;

				case 'unpin':
					this.onUnpinAction(task);
					break;

				case 'read':
					this.onReadAction(task);
					break;

				default:
					break;
			}

			this.updateItem(task.id, {});
		}

		/**
		 * @param {Task} task
		 */
		onMoreAction(task)
		{
			const taskItemData = this.getItemDataFromTask(task);
			const actionsPopup = dialogs.createPopupMenu();
			actionsPopup.setData(taskItemData.params.popupActions, [{id: 'default'}], (eventName, item) => {
				if (eventName === 'onItemSelected')
				{
					this.onActionsPopupItemSelected(item, task);
				}
			});
			actionsPopup.setPosition('center');
			actionsPopup.show();
		}

		onActionsPopupItemSelected(item, task)
		{
			switch (item.id)
			{
				case 'ping':
					this.onPingAction(task);
					break;

				case 'changeDeadline':
					this.onChangeDeadlineAction(task);
					break;

				case 'approve':
					this.onApproveAction(task);
					break;

				case 'disapprove':
					this.onDisapproveAction(task);
					break;

				case 'start':
					this.onStartAction(task);
					break;

				case 'pause':
					this.onPauseAction(task);
					break;

				case 'renew':
					this.onRenewAction(task);
					break;

				case 'changeResponsible':
					this.onChangeResponsibleAction(task);
					break;

				case 'delegate':
					this.onDelegateAction(task);
					break;

				case 'changeGroup':
					this.onChangeGroupAction(task);
					break;

				case 'mute':
					this.onMuteAction(task);
					break;

				case 'unmute':
					this.onUnmuteAction(task);
					break;

				case 'unfollow':
					this.onUnfollowAction(task);
					break;

				case 'remove':
					this.onRemoveAction(task);
					break;

				case 'pin':
					this.onPinAction(task);
					break;

				case 'unpin':
					this.onUnpinAction(task);
					break;

				case 'read':
					this.onReadAction(task);
					break;

				case 'cancel':
					return;

				default:
					break;
			}

			this.updateItem(task.id, {});
		}

		/**
		 * @param {Task} task
		 */
		onPingAction(task)
		{
			void task.ping();
			this.updateItem(task.id, {activityDate: Date.now()});

			Notify.showIndicatorSuccess({
				text: BX.message('TASKS_LIST_PING_NOTIFICATION'),
				hideAfter: 1500,
			});
		}

		/**
		 * @param {Task} task
		 */
		onChangeDeadlineAction(task)
		{
			const pickerParams = {
				title: BX.message('TASKS_LIST_POPUP_SELECT_DATE'),
				type: 'datetime',
				value: task.deadline,
			};

			if (apiVersion >= 34)
			{
				pickerParams.items = [];

				Object.keys(Task.deadlines).forEach((key) => {
					const {deadlines} = this.options.get();
					pickerParams.items.push({
						name: Task.deadlines[key].name,
						value: deadlines.value[key] * 1000,
					});
				});
			}

			dialogs.showDatePicker(pickerParams, (eventName, ts) => {
				if (ts > 0 && ts !== task.deadline)
				{
					const oldCounterValue = task.getMessageInfo().count;
					this.updateItem(task.id, {deadline: ts, activityDate: Date.now()});
					const newCounterValue = task.getMessageInfo().count;
					this.filter.pseudoUpdateCounters(newCounterValue - oldCounterValue, task);
					task.saveDeadline();
				}
			});
		}

		/**
		 * @param {Task} task
		 */
		onApproveAction(task)
		{
			if (task.status === TaskList.statusList.waitCtrl)
			{
				task.rawAccess.approve = false;
				task.rawAccess.disapprove = false;
				task.rawAccess.complete = false;
				task.rawAccess.renew = true;
				task.approve().then(() => this.updateItem(task.id, {status: task.status}));
				this.updateItem(task.id, {
					status: task.status,
					activityDate: Date.now(),
				});
			}
		}

		/**
		 * @param {Task} task
		 */
		onDisapproveAction(task)
		{
			if (task.status === TaskList.statusList.waitCtrl)
			{
				task.rawAccess.approve = false;
				task.rawAccess.disapprove = false;
				task.rawAccess.renew = false;
				task.rawAccess.complete = false;
				task.rawAccess.start = true;
				task.disapprove().then(() => this.updateItem(task.id, {status: task.status}));
				this.updateItem(task.id, {status: task.status});
			}
		}

		/**
		 * @param {Task} task
		 */
		onStartAction(task)
		{
			if (task.status !== TaskList.statusList.inprogress)
			{
				task.rawAccess.start = false;
				task.rawAccess.pause = true;
				task.rawAccess.renew = false;
				task.start().then(() => this.updateItem(task.id, {status: task.status}));
				this.updateItem(task.id, {status: task.status});
			}
		}

		/**
		 * @param {Task} task
		 */
		onPauseAction(task)
		{
			if (task.status !== TaskList.statusList.pending)
			{
				task.rawAccess.start = true;
				task.rawAccess.pause = false;
				task.rawAccess.renew = false;
				task.pause().then(() => this.updateItem(task.id, {status: task.status}));
				this.updateItem(task.id, {status: task.status});
			}
		}

		/**
		 * @param {Task} task
		 */
		onRenewAction(task)
		{
			if (task.isCompletedCounts)
			{
				task.rawAccess.start = true;
				task.rawAccess.pause = false;
				task.rawAccess.renew = false;
				task.renew().then(() => this.updateItem(task.id, {
					status: task.status,
					activityDate: Date.now(),
				}));
				this.updateItem(task.id, {
					status: task.status,
					activityDate: Date.now(),
				});
			}
		}

		/**
		 * @param {Task} task
		 */
		onChangeResponsibleAction(task)
		{
			// UserList.openPicker({
			// 	title: BX.message('TASKS_LIST_POPUP_RESPONSIBLE'),
			// 	allowMultipleSelection: false,
			// 	listOptions: {
			// 		users: {
			// 			hideUnnamed: true,
			// 			useRecentSelected: true,
			// 		},
			// 	},
			// }).then((users) => {

			new TaskUserList(result.userList, {
				onSelect: (user) => {
					task.responsible = TaskList.getItemDataFromUser(user);
					if (!task.isMember(this.currentUser.id))
					{
						this.removeItem(task.id);
						this.filter.pseudoUpdateCounters(-task.getMessageInfo().count, task);
					}
					else
					{
						this.updateItem(task.id, {
							responsibleIcon: task.responsible.icon,
							activityDate: Date.now(),
						});
					}
					void task.save();
				},
			});
		}

		/**
		 * @param {Task} task
		 */
		onDelegateAction(task)
		{
			// UserList.openPicker({
			// 	title: BX.message('TASKS_LIST_POPUP_RESPONSIBLE'),
			// 	allowMultipleSelection: false,
			// 	listOptions: {
			// 		users: {
			// 			hideUnnamed: true,
			// 			useRecentSelected: true,
			// 		},
			// 	},
			// }).then((users) => {

			new TaskUserList(result.userList, {
				onSelect: (user) => {
					task.responsible = TaskList.getItemDataFromUser(user);
					this.updateItem(task.id, {
						responsibleIcon: task.responsible.icon,
						activityDate: Date.now(),
					});
					void task.delegate();
				},
			});
		}

		/**
		 * @param {Task} task
		 */
		onChangeGroupAction(task)
		{
			new TaskGroupList({
				onSelect: (group) => {
					if (Number(task.groupId) === Number(group.id))
					{
						return;
					}
					task.groupId = Number(group.id);
					task.group = TaskList.getItemDataFromGroup(group);
					this.updateItem(task.id, {});
					void task.save();
				},
			});
		}

		// /**
		//  * @param {Task} task
		//  */
		// onChangeGroupAction(task)
		// {
		// 	const selected = {};
		// 	const selectedGroup = {};
		//
		// 	if (Number(task.groupId) > 0)
		// 	{
		// 		selectedGroup.id = task.groupId;// (task.groupId + '').split('/').pop();
		// 		selectedGroup.title = task.group.name;
		// 		selectedGroup.imageUrl = task.group.image;
		//
		// 		selected.groups = [selectedGroup];
		// 	}
		//
		// 	(new RecipientList(['groups']))
		// 		.open({
		// 			returnShortFormat: false,
		// 			allowMultipleSelection: false,
		// 			title: BX.message('TASKS_LIST_POPUP_RESPONSIBLE'),
		// 			selected,
		// 		})
		// 		.then((recipients) => {
		// 			if (recipients.groups)
		// 			{
		// 				if (recipients.groups.length > 0)
		// 				{
		// 					const group = recipients.groups[0];
		//
		// 					if (Number(task.groupId) === Number(group.params.id))
		// 					{
		// 						return;
		// 					}
		//
		// 					task.groupId = Number(group.params.id);
		// 					task.group = TaskList.getItemDataFromGroup(group);
		// 					this.updateItem(task.id, {});
		// 					void task.save();
		// 				}
		// 				else if (Number(task.groupId) > 0)
		// 				{
		// 					task.groupId = 0;
		// 					task.group = {
		// 						id: 0,
		// 						name: '',
		// 						image: '',
		// 					};
		// 					this.updateItem(task.id, {});
		// 					void task.save();
		// 				}
		// 			}
		// 		});
		// }

		/**
		 * @param {Task} task
		 */
		onMuteAction(task)
		{
			this.updateItem(task.id, {isMuted: true});
			this.filter.pseudoUpdateCounters(-task.getMessageInfo().count);
			task.mute();
		}

		/**
		 * @param {Task} task
		 */
		onUnmuteAction(task)
		{
			this.updateItem(task.id, {isMuted: false});
			this.filter.pseudoUpdateCounters(task.getMessageInfo().count);
			task.unmute();
		}

		/**
		 * @param {Task} task
		 */
		onUnfollowAction(task)
		{
			task.auditors = task.auditors.filter(userId => userId !== Number(this.currentUser.id));

			if (!task.isMember(this.currentUser.id))
			{
				this.removeItem(task.id);
				this.filter.pseudoUpdateCounters(-task.getMessageInfo().count, task);
			}

			task.stopWatch();
		}

		/**
		 * @param {Task} task
		 */
		onRemoveAction(task)
		{
			dialogs.showActionSheet({
				title: BX.message('TASKS_CONFIRM_DELETE'),
				callback: (item) => {
					if (item.code === 'YES')
					{
						this.removeItem(task.id);
						this.filter.pseudoUpdateCounters(-task.getMessageInfo().count, task);

						(new Request())
							.call('delete', {taskId: task.id})
							.then(response => {}, response => console.log(response));
					}
				},
				items: [
					{title: BX.message('TASKS_CONFIRM_DELETE_YES'), code: 'YES'},
					{title: BX.message('TASKS_CONFIRM_DELETE_NO'), code: 'NO'},
				],
			});
		}

		/**
		 * @param {Task} task
		 */
		onPinAction(task)
		{
			this.updateItem(task.id, {isPinned: true});
			task.pin();
		}

		/**
		 * @param {Task} task
		 */
		onUnpinAction(task)
		{
			this.updateItem(task.id, {isPinned: false});
			task.unpin();
		}

		/**
		 * @param {Task} task
		 */
		onReadAction(task)
		{
			this.filter.pseudoUpdateCounters(-task.newCommentsCount, task);
			this.updateItem(task.id, {newCommentsCount: 0});
			task.read();
		}

		// popupMenu
		initPopupMenu()
		{
			const menuItems = this.preparePopupMenuItems();
			const menuSections = [{id: 'default'}];

			if (!this.popupMenu)
			{
				this.popupMenu = dialogs.createPopupMenu();
			}
			this.popupMenu.setData(menuItems, menuSections, (eventName, item) => {
				if (eventName === 'onItemSelected')
				{
					this.onPopupMenuItemSelected(item);
				}
			});
			this.popupMenu.show();
		}

		preparePopupMenuItems()
		{
			const urlPrefix = `${pathToComponent}images/mobile-tasks-list-popup-`;
			const roleItems = [
				{
					id: Filter.roleType.all,
					title: BX.message('TASKS_POPUP_MENU_ROLE_ALL'),
					iconUrl: `${urlPrefix}role-all.png`,
					iconName: 'finished_tasks',
					sectionCode: 'default',
					checked: this.filter.getRole() === Filter.roleType.all,
					counterValue: this.filter.getCounterValue(),
					counterStyle: {
						backgroundColor: '#FF5752',
					},
				},
				{
					id: Filter.roleType.responsible,
					title: BX.message('TASKS_POPUP_MENU_ROLE_RESPONSIBLE'),
					iconUrl: `${urlPrefix}role-responsible.png`,
					sectionCode: 'default',
					checked: this.filter.getRole() === Filter.roleType.responsible,
					counterValue: this.filter.getCounterValue(Filter.roleType.responsible),
					counterStyle: {
						backgroundColor: '#FF5752',
					},
				},
				{
					id: Filter.roleType.accomplice,
					title: BX.message('TASKS_POPUP_MENU_ROLE_ACCOMPLICE'),
					iconUrl: `${urlPrefix}role-accomplice.png`,
					sectionCode: 'default',
					checked: this.filter.getRole() === Filter.roleType.accomplice,
					counterValue: this.filter.getCounterValue(Filter.roleType.accomplice),
					counterStyle: {
						backgroundColor: '#FF5752',
					},
				},
				{
					id: Filter.roleType.originator,
					title: BX.message('TASKS_POPUP_MENU_ROLE_ORIGINATOR'),
					iconUrl: `${urlPrefix}role-originator.png`,
					sectionCode: 'default',
					checked: this.filter.getRole() === Filter.roleType.originator,
					counterValue: this.filter.getCounterValue(Filter.roleType.originator),
					counterStyle: {
						backgroundColor: '#FF5752',
					},
				},
				{
					id: Filter.roleType.auditor,
					title: BX.message('TASKS_POPUP_MENU_ROLE_AUDITOR'),
					iconUrl: `${urlPrefix}role-auditor.png`,
					sectionCode: 'default',
					checked: this.filter.getRole() === Filter.roleType.auditor,
					counterValue: this.filter.getCounterValue(Filter.roleType.auditor),
					counterStyle: {
						backgroundColor: '#FF5752',
					},
				},
			];
			const counterItems = [
				{
					id: Filter.counterType.newComments,
					title: BX.message('TASKS_POPUP_MENU_COUNTER_NEW_COMMENTS'),
					sectionCode: 'default',
					checked: this.filter.getCounter() === Filter.counterType.newComments,
					counterValue: this.filter.getCounterValue(this.filter.getRole(), Filter.counterType.newComments),
					counterStyle: {
						backgroundColor: '#9DCF00',
					},
				},
				{
					id: Filter.counterType.expired,
					title: BX.message('TASKS_POPUP_MENU_COUNTER_EXPIRED'),
					sectionCode: 'default',
					checked: this.filter.getCounter() === Filter.counterType.expired,
					counterValue: this.filter.getCounterValue(this.filter.getRole(), Filter.counterType.expired),
					counterStyle: {
						backgroundColor: '#FF5752',
					},
				},
				{
					id: Filter.counterType.supposedlyCompleted,
					title: BX.message('TASKS_POPUP_MENU_COUNTER_SUPPOSEDLY_COMPLETED'),
					sectionCode: 'default',
					checked: this.filter.getCounter() === Filter.counterType.supposedlyCompleted,
				},
			];
			const actionItems = [
				{
					id: 'toggleOrder',
					title: BX.message(
						this.order.isDeadline() ? 'TASKS_POPUP_MENU_ORDER_ACTIVITY' : 'TASKS_POPUP_MENU_ORDER_DEADLINE'
					),
					iconName: 'term',
					sectionCode: 'default',
					showTopSeparator: true,
				},
				{
					id: 'toggleCompletedTasks',
					title: BX.message(`TASKS_POPUP_MENU_${(!this.filter.showCompleted ? 'SHOW' : 'HIDE')}_CLOSED_TASKS`),
					iconUrl: `${urlPrefix}${(!this.filter.showCompleted ? 'show' : 'hide')}-completed.png`,
					sectionCode: 'default',
					disable: this.order.isDeadline(),
				},
				{
					id: 'readAll',
					title: BX.message('TASKS_POPUP_MENU_READ_ALL'),
					iconName: 'read',
					sectionCode: 'default',
				},
			];
			const menuItems = [];

			roleItems.forEach((roleItem) => {
				menuItems.push(roleItem);
				if (roleItem.id === this.filter.getRole())
				{
					counterItems.forEach(counterItem => menuItems.push(counterItem));
				}
			});
			actionItems.forEach(actionItem => menuItems.push(actionItem));

			if (
				apiVersion >= 38
				&& this.efficiency !== false
				&& this.efficiency >= 0
			)
			{
				menuItems.push({
					id: 'efficiency',
					title: BX.message('TASKS_POPUP_MENU_EFFICIENCY'),
					iconUrl: `${urlPrefix}efficiency.png`,
					sectionCode: 'default',
					counterValue: `${this.efficiency}%`,
					counterStyle: {
						backgroundColor: '#9DCF00',
					},
				});
			}

			return menuItems;
		}

		onPopupMenuItemSelected(item)
		{
			switch (item.id)
			{
				case Filter.roleType.all:
				case Filter.roleType.responsible:
				case Filter.roleType.accomplice:
				case Filter.roleType.originator:
				case Filter.roleType.auditor:
					this.onRoleClick(item.id);
					break;

				case Filter.counterType.expired:
				case Filter.counterType.newComments:
				case Filter.counterType.supposedlyCompleted:
					this.onCounterClick(item.id);
					break;

				case 'toggleOrder':
					this.onDeadlineSwitchClick();
					break;

				case 'readAll':
					this.onReadAllClick();
					break;

				case 'toggleCompletedTasks':
					BX.postComponentEvent('task.list.onToggleCompleted', []);
					break;

				case 'efficiency':
					this.openEfficiencyPage();
					break;

				default:
					this.reload(0, true);
					break;
			}
		}

		openEfficiencyPage()
		{
			BX.postComponentEvent('taskbackground::efficiency::open', [{
				userId: this.owner.id,
				groupId: this.groupId,
			}]);
		}

		onReadAllClick()
		{
			const items = [];
			let newCommentsRead = 0;

			this.taskList.forEach((task) => {
				newCommentsRead += task.newCommentsCount;
				task.newCommentsCount = 0;
				items.push(this.getItemDataFromTask(task));
			});
			this.list.setItems(items, null, true);
			this.filter.pseudoUpdateCounters(-newCommentsRead);

			(new Request())
				.call('comment.readAll', {
					groupId: this.groupId || null,
					userId: this.owner.id || this.currentUser.id,
					role: this.filter.getRole(),
				})
				.then((response) => {
					console.log(response);
					if (response.result === true)
					{
						Notify.showIndicatorSuccess({
							text: BX.message('TASKS_LIST_READ_ALL_NOTIFICATION'),
							hideAfter: 1500,
						});
					}
				});
		}

		onToggleCompleted()
		{
			this.filter.showCompleted = !this.filter.showCompleted;
			this.reload();
		}

		onDeadlineSwitchClick()
		{
			const {siteId, siteDir, languageId} = env;
			const currentRole = this.filter.getRole();
			const currentCounter = this.filter.getCounter();

			if (currentRole !== Filter.roleType.all || currentCounter !== Filter.counterType.none)
			{
				this.order.changeOrder();
				this.updateTitle();

				if (this.order.isDeadline())
				{
					this.filter.showCompleted = false;
				}

				this.setTopButtons();
				this.reload(0, true);

				return;
			}

			if (this.order.isDeadline())
			{
				this.list.back();
				return;
			}

			const newOrder = Object.keys(Order.fields).filter(key => key !== this.order.order)[0];
			const params = {
				COMPONENT_CODE: 'tasks.list',
				ROLE: currentRole,
				ORDER: newOrder,
				DATA: BX.componentParameters.get('DATA', {}),
				SHOW_COMPLETED: false,
				ANIMATE_CACHE_TASKS_LOADING: false,
				SITE_ID: siteId,
				LANGUAGE_ID: languageId,
				SITE_DIR: siteDir,
			};

			if (this.groupId > 0)
			{
				params.GROUP_ID = this.groupId;
			}
			else
			{
				params.USER_ID = this.owner.id;
			}

			PageManager.openComponent('JSStackComponent', {
				canOpenInDefault: false,
				scriptPath: availableComponents['tasks.list'].publicUrl,
				componentCode: 'tasks.list',
				params,
				// title: this.getFutureTitle(currentRole, newOrder),
				rootWidget: {
					name: 'tasks.list',
					settings: {
						objectName: 'list',
						useSearch: true,
						useLargeTitleMode: true,
					},
				},
			});
		}

		getFutureTitle(role, order)
		{
			const subHeader = (order === 'deadline' ? BX.message('TASKS_LIST_SUB_HEADER_DEADLINES') : '');
			return TaskList.titles[role].replace('#DEADLINES#', subHeader);
		}

		updateTitle(useProgress = false)
		{
			const titleParams = {
				useProgress,
				largeMode: true,
			};

			if (this.groupId > 0)
			{
				if (this.filter.getRole() === Filter.roleType.all)
				{
					titleParams.text = (this.order.isDeadline() ? TaskList.titles.deadlines : this.group.name);
				}
				else
				{
					titleParams.text = TaskList.titles[this.filter.getRole()].replace('#DEADLINES#', '');
				}
				titleParams.imageUrl = this.group.imageUrl;
			}
			else
			{
				const subHeader = (this.order.isDeadline() ? BX.message('TASKS_LIST_SUB_HEADER_DEADLINES') : '');
				titleParams.text = TaskList.titles[this.filter.getRole()].replace('#DEADLINES#', subHeader);
			}

			this.list.setTitle(titleParams);
		}

		onRoleClick(newRole)
		{
			const {siteId, siteDir, languageId} = env;
			const currentRole = this.filter.getRole();
			const currentCounter = this.filter.getCounter();

			if (
				currentRole === newRole
				|| (newRole === Filter.roleType.all && this.order.isActivityDate())
			)
			{
				this.list.back();
				return;
			}

			if (
				currentRole !== Filter.roleType.all
				|| currentCounter !== Filter.counterType.none
				|| (
					currentRole === Filter.roleType.all
					&& currentCounter === Filter.counterType.none
					&& this.order.isDeadline()
				)
			)
			{
				this.filter.setRole(newRole);
				this.filter.setCounter(Filter.counterType.none);

				this.updateTitle();
				this.setTopButtons();
				this.reload(0, true);

				return;
			}

			const params = {
				COMPONENT_CODE: 'tasks.list',
				ROLE: newRole,
				ORDER: this.order.order,
				DATA: BX.componentParameters.get('DATA', {}),
				SHOW_COMPLETED: this.filter.showCompleted,
				ANIMATE_CACHE_TASKS_LOADING: false,
				SITE_ID: siteId,
				LANGUAGE_ID: languageId,
				SITE_DIR: siteDir,
			};

			if (this.groupId > 0)
			{
				params.GROUP_ID = this.groupId;
			}
			else
			{
				params.USER_ID = this.owner.id;
			}

			PageManager.openComponent('JSStackComponent', {
				canOpenInDefault: false,
				scriptPath: availableComponents['tasks.list'].publicUrl,
				componentCode: 'tasks.list',
				params,
				// title: this.getFutureTitle(newRole, this.order.order),
				rootWidget: {
					name: 'tasks.list',
					settings: {
						objectName: 'list',
						useSearch: true,
						useLargeTitleMode: true,
					},
				},
			});
		}

		onCounterClick(newCounter)
		{
			const {siteId, siteDir, languageId} = env;
			const currentRole = this.filter.getRole();
			const currentCounter = this.filter.getCounter();

			if (currentCounter === newCounter)
			{
				if (currentRole === Filter.roleType.all && this.order.isActivityDate())
				{
					this.list.back();
				}
				else
				{
					this.filter.setCounter(Filter.counterType.none);
					this.reload(0, true);
				}
				return;
			}

			if (
				currentRole !== Filter.roleType.all
				|| currentCounter !== Filter.counterType.none
				|| (
					currentRole === Filter.roleType.all
					&& currentCounter === Filter.counterType.none
					&& this.order.isDeadline()
				)
			)
			{
				this.filter.setCounter(newCounter);

				this.updateTitle();
				this.setTopButtons();
				this.reload(0, true);

				return;
			}

			const params = {
				COMPONENT_CODE: 'tasks.list',
				ROLE: currentRole,
				COUNTER: newCounter,
				ORDER: this.order.order,
				DATA: BX.componentParameters.get('DATA', {}),
				SHOW_COMPLETED: this.filter.showCompleted,
				ANIMATE_CACHE_TASKS_LOADING: false,
				SITE_ID: siteId,
				LANGUAGE_ID: languageId,
				SITE_DIR: siteDir,
			};

			if (this.groupId > 0)
			{
				params.GROUP_ID = this.groupId;
			}
			else
			{
				params.USER_ID = this.owner.id;
			}

			PageManager.openComponent('JSStackComponent', {
				canOpenInDefault: false,
				scriptPath: availableComponents['tasks.list'].publicUrl,
				componentCode: 'tasks.list',
				params,
				// title: this.getFutureTitle(currentRole, this.order.order),
				rootWidget: {
					name: 'tasks.list',
					settings: {
						objectName: 'list',
						useSearch: true,
						useLargeTitleMode: true,
					},
				},
			});
		}
	}

	class Search
	{
		static get cacheKeys()
		{
			return {
				tasks: 'tasks',
				lastActiveProjects: 'lastActiveProjects',
				lastSearchedProjects: 'lastSearchedProjects',
			};
		}

		/**
		 * @param {TaskList} list
		 */
		constructor(list)
		{
			this.list = list;

			this.minSize = parseInt(BX.componentParameters.get('MIN_SEARCH_SIZE', 3), 10);
			this.maxTaskCount = 50;
			this.maxProjectCount = 15;
			this.text = '';

			this.taskList = new Map();
			this.projectList = new Map();
			this.commonProjectList = new Map();

			this.debounceFunction = this.getDebounceFunction();

			this.cache = Cache.getInstance(`tasks.task.search_${this.list.owner.id}`);
		}

		getDebounceFunction()
		{
			return Util.debounce((text) => {
				console.log('Search:fnUserTypeText');

				const searchResultItems = [].concat(
					this.renderProjectItems(),
					this.renderTaskItems(),
					this.renderLoadingItems()
				);
				const sections = [
					{id: 'project'},
					{id: 'default', title: BX.message('TASKS_LIST_SEARCH_SECTION_SEARCH_RESULTS')},
				];
				this.setSearchResultItems(searchResultItems, sections);

				const batchOperations = {
					projects: ['mobile.tasks.group.search', {
						searchText: text,
					}],
					tasks: ['tasks.task.list', {
						select: TaskList.selectFields,
						filter: this.list.filter.getForSearch(text),
						order: this.list.order.getForSearch(),
						params: {
							RETURN_ACCESS: 'Y',
							RETURN_USER_INFO: 'Y',
							RETURN_GROUP_INFO: 'Y',
							SEND_PULL: 'Y',
						},
					}],
				};
				BX.rest.callBatch(batchOperations, response => this.onSearchSuccess(response));
			}, 100, this);
		}

		onSearchSuccess(response)
		{
			const projects = response.projects.answer.result;
			const {tasks} = response.tasks.answer.result;

			this.projectList.clear();
			this.taskList.clear();

			this.fillProjectList(projects);
			this.fillTaskList(tasks);
			this.renderList();
		}

		fillProjectList(rows)
		{
			rows.forEach((row) => {
				this.projectList.set(row.id, row);
				this.commonProjectList.set(row.id, row);
			});
		}

		fillTaskList(rows)
		{
			rows.forEach((row) => {
				const rowId = row.id.toString();
				let task;

				if (this.list.taskList.has(rowId))
				{
					task = this.list.taskList.get(rowId);
				}
				else
				{
					task = new Task(this.list.currentUser);
					task.setData(row);
				}
				this.taskList.set(task.id, task);
			});
		}

		renderList(fromCache = false)
		{
			console.log('Search:renderList', {tasks: this.taskList.size, projects: this.projectList.size});

			let searchResultItems = this.renderProjectItems();
			let nextItems = this.renderEmptyResultItems();

			if (this.taskList.size > 0)
			{
				nextItems = this.renderTaskItems();
			}
			else if (fromCache)
			{
				nextItems = this.renderEmptyCacheItems();
			}
			searchResultItems = searchResultItems.concat(nextItems);

			const title = (fromCache ? 'TASKS_LIST_SEARCH_SECTION_LAST' : 'TASKS_LIST_SEARCH_SECTION_SEARCH_RESULTS');
			const sections = [
				{id: 'project'},
				{id: 'default', title: BX.message(title), backgroundColor: '#ffffff'},
			];
			console.log({title: 'search', searchResultItems, sections});
			this.setSearchResultItems(searchResultItems, sections);
		}

		renderProjectItems()
		{
			const projectItems = [];

			this.projectList.forEach((project) => {
				projectItems.push({
					id: project.id,
					title: project.name,
					imageUrl: project.image,
					type: 'project',
				});
			});

			if (projectItems.length < 1)
			{
				return [];
			}
			return [{type: 'carousel', sectionCode: 'project', childItems: projectItems}];
		}

		renderTaskItems()
		{
			const taskItems = [];

			this.taskList.forEach((task) => {
				const item = this.list.getItemDataFromTask(task, false);
				item.sectionCode = 'default';
				taskItems.push(item);
			});

			return taskItems;
		}

		renderLoadingItems()
		{
			return [{
				id: 0,
				type: 'loading',
				title: BX.message('TASKS_LIST_BUTTON_NEXT_PROCESS'),
				sectionCode: 'default',
				unselectable: true,
			}];
		}

		renderEmptyCacheItems()
		{
			return [{
				id: 0,
				type: 'button',
				title: BX.message('TASKS_LIST_SEARCH_HINT'),
				sectionCode: 'default',
				unselectable: true,
			}];
		}

		renderEmptyResultItems()
		{
			console.log('Empty');
			return [{
				id: 0,
				type: 'button',
				title: BX.message('TASKS_LIST_SEARCH_EMPTY_RESULT'),
				sectionCode: 'default',
				unselectable: true,
			}];
		}

		setSearchResultItems(items, sections)
		{
			this.list.list.setSearchResultItems(items, sections);
		}

		onUserTypeText(event)
		{
			console.log('Search:onUserTypeText');

			BX.onViewLoaded(() => {
				const text = event.text.trim();
				if (this.text === text)
				{
					return;
				}
				this.text = text;
				if (this.text.length < this.minSize)
				{
					this.projectList.clear();
					this.taskList.clear();

					if (this.text === '')
					{
						this.loadProjectsFromCache();
						this.loadTasksFromCache();
					}
					else
					{
						this.fillProjectList(this.getLocalSearchedProjects(this.text));
						this.fillTaskList(this.getLocalSearchedTasks(this.text));
					}
					this.renderList(this.text === '');
					return;
				}
				this.debounceFunction(this.text);
			});
		}

		getLocalSearchedProjects(text)
		{
			const localSearchedProjects = [];
			const added = {};

			this.commonProjectList.forEach((project) => {
				added[project.id] = false;
				const searchString = `${project.name}`.toLowerCase();
				searchString.split(' ').forEach((word) => {
					if (!added[project.id] && word.search(text.toLowerCase()) === 0)
					{
						localSearchedProjects.push(project);
						added[project.id] = true;
					}
				});
			});

			return localSearchedProjects;
		}

		getLocalSearchedTasks(text)
		{
			const localSearchedTasks = [];
			const added = {};

			this.list.taskList.forEach((task) => {
				added[task.id] = false;
				const searchString = `${task.title} ${task.creator.name} ${task.responsible.name}`.toLowerCase();
				searchString.split(' ').forEach((word) => {
					if (!added[task.id] && word.search(text.toLowerCase()) === 0)
					{
						localSearchedTasks.push(task);
						added[task.id] = true;
					}
				});
			});

			return localSearchedTasks;
		}

		onSearchShow()
		{
			this.fillCacheWithLastActiveProjects();
			this.loadProjectsFromCache();
			this.loadTasksFromCache();

			this.renderList(true);
		}

		fillCacheWithLastActiveProjects()
		{
			const cacheKey = Search.cacheKeys.lastActiveProjects;
			this.cache.update(cacheKey, this.list.lastActiveGroups.slice(0, this.maxProjectCount + 1));
		}

		loadProjectsFromCache()
		{
			console.log('Search:loadProjectsFromCache');

			const cache = this.cache.get();
			const lastSearchedProjects = cache[Search.cacheKeys.lastSearchedProjects] || [];
			let lastActiveProjects = cache[Search.cacheKeys.lastActiveProjects] || [];

			const ids = lastSearchedProjects.map(project => Number(project.id));
			lastActiveProjects = lastActiveProjects.filter(project => !ids.includes(Number(project.id)));

			let projects = lastSearchedProjects;
			if (projects.length < this.maxProjectCount)
			{
				const count = this.maxProjectCount - projects.length + 1;
				projects = projects.concat(lastActiveProjects.slice(0, count));
			}
			if (projects.length)
			{
				this.fillProjectList(projects);
				return true;
			}

			return false;
		}

		loadTasksFromCache()
		{
			console.log('Search:loadTasksFromCache');

			const tasks = this.cache.get()[Search.cacheKeys.tasks] || [];
			if (tasks.length)
			{
				this.fillTaskList(tasks);
				return true;
			}

			return false;
		}

		onSearchHide()
		{
			this.taskList.clear();
			this.projectList.clear();
		}

		onSearchItemSelected(event)
		{
			if (event.type === 'task')
			{
				this.onTaskSelected(event.id.toString());
			}
			else if (event.type === 'project')
			{
				this.onProjectSelected(event);
			}
		}

		onTaskSelected(taskId)
		{
			if (!this.taskList.has(taskId))
			{
				return;
			}

			(new Request()).call('list', {
				select: TaskList.selectFields,
				filter: {ID: taskId},
				params: {
					RETURN_ACCESS: 'Y',
					RETURN_USER_INFO: 'Y',
					RETURN_GROUP_INFO: 'Y',
					SEND_PULL: 'Y',
				},
			}).then((response) => {
				const rows = response.result.tasks || [];
				const row = rows[0];
				if (row)
				{
					const cacheKey = Search.cacheKeys.tasks;
					let cachedTasks = this.cache.get()[cacheKey] || [];
					cachedTasks = cachedTasks.filter(item => Number(item.id) !== Number(taskId));
					this.cache.update(cacheKey, [row].concat(cachedTasks.slice(0, this.maxTaskCount)));

					setTimeout(() => {
						const task = new Task(this.list.currentUser);
						task.setData(row);

						const newTaskList = new Map([[taskId, task]]);
						this.taskList.forEach((value, key) => newTaskList.set(key, value));
						this.taskList = newTaskList;

						if (this.text.length < this.minSize)
						{
							this.renderList(this.text === '');
						}
					}, 500);
				}
			});

			this.taskList.get(taskId).open();
		}

		onProjectSelected(data)
		{
			const project = {
				id: data.id,
				name: data.title,
				image: data.imageUrl,
			};
			const cacheKey = Search.cacheKeys.lastSearchedProjects;
			let projects = this.cache.get()[cacheKey] || [];
			projects = projects.filter(item => Number(item.id) !== Number(project.id));
			this.cache.update(cacheKey, [project].concat(projects.slice(0, this.maxProjectCount)));

			BX.postComponentEvent('taskbackground::task::action', [{
				groupId: data.id,
				groupName: data.title,
				groupImageUrl: data.imageUrl,
			}]);

			setTimeout(() => {
				const newProjectList = new Map([[project.id, project]]);
				this.projectList.forEach((value, key) => newProjectList.set(key, value));
				this.projectList = newProjectList;

				const newCommonProjectList = new Map([[project.id, project]]);
				this.commonProjectList.forEach((value, key) => newCommonProjectList.set(key, value));
				this.commonProjectList = newCommonProjectList;

				if (this.text.length < this.minSize)
				{
					this.renderList(this.text === '');
				}
			}, 500);
		}

		removeTask(taskId)
		{
			if (this.taskList.has(taskId))
			{
				this.taskList.delete(taskId);
				this.list.list.removeItem(taskId);
			}
		}
	}

	return new TaskList(list, parseInt(BX.componentParameters.get('USER_ID', 0), 10));
})();