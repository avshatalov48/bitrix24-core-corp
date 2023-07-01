include('InAppNotifier');

(() => {
	const pathToComponent = '/bitrix/mobileapp/mobile/components/bitrix/tasks.list/';
	const apiVersion = Application.getApiVersion();
	const platform = Application.getPlatform();
	const caches = new Map();

	const { EntityReady } = jn.require('entity-ready');

	class Util
	{
		static debounce(fn, timeout, ctx)
		{
			let timer = 0;
			return function() {
				clearTimeout(timer);
				timer = setTimeout(() => fn.apply(ctx, arguments), timeout);
			};
		}
	}

	class WelcomeScreen
	{
		static get type()
		{
			return {
				empty: 'empty',
				privateProject: 'privateProject',
			};
		}

		constructor(list)
		{
			this.list = list;
		}

		isEnabled()
		{
			return (apiVersion >= 40);
		}

		show(type = WelcomeScreen.type.privateProject)
		{

			if (this.isEnabled() && Object.values(WelcomeScreen.type).includes(type))
			{
				let upperText;
				let lowerText;
				let iconName;

				if (type === WelcomeScreen.type.privateProject)
				{
					upperText = BX.message('MOBILE_TASKS_LIST_WELCOME_SCREEN_PRIVATE_PROJECT_TITLE');
					lowerText = BX.message('MOBILE_TASKS_LIST_WELCOME_SCREEN_PRIVATE_PROJECT_SUBTITLE');
					iconName = 'ws_private_project';
				}
				else
				{
					upperText = BX.message('MOBILE_TASKS_LIST_WELCOME_SCREEN_EMPTY_TITLE');
					lowerText = BX.message('MOBILE_TASKS_LIST_WELCOME_SCREEN_EMPTY_SUBTITLE');
					iconName = 'ws_create_task';
				}

				this.list.list.welcomeScreen.show({upperText, lowerText, iconName});
			}
		}

		hide()
		{
			if (this.isEnabled())
			{
				this.list.list.welcomeScreen.hide();
			}
		}
	}

	class JoinButton
	{
		/**
		 * @param {TaskList} list
		 */
		constructor(list)
		{
			this.list = list;
		}

		isEnabled()
		{
			return (apiVersion >= 40);
		}

		showForOpened()
		{
			if (this.isEnabled())
			{
				this.list.list.showJoinButton({type: 'openProject'});
			}
		}

		showForPrivate(isRequested = false)
		{
			if (this.isEnabled())
			{
				if (isRequested)
				{
					this.list.list.showJoinButton({state: 'requested'});
				}
				else
				{
					this.list.list.showJoinButton({type: 'privateProject'});
				}
			}
		}

		onClick()
		{
			(new RequestExecutor('socialnetwork.api.usertogroup.join', {
				params: {
					groupId: this.list.groupId,
				},
			}))
				.call()
				.then((response) => {
					if (
						!this.list.group.isOpened
						&& response.result.success
						&& !response.result.confirmationNeeded
					)
					{
						this.list.initAccessibleList();
					}
				})
			;
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

		static get sections()
		{
			return {
				new: 'new',
				pinned: 'pinned',
				default: 'default',
				more: 'more',
				empty: 'empty',
			};
		}

		constructor()
		{
			this.clear();
		}

		clear()
		{
			const defaultSectionParams = {
				title: '',
				foldable: false,
				folded: false,
				badgeValue: 0,
				sortItemParams: {activityDate: 'desc'},
				backgroundColor: '#ffffff',
				styles: {title: {font: {size: 18}}},
			};

			this.items = {
				new: {...{id: SectionHandler.sections.new}, ...defaultSectionParams},
				pinned: {...{id: SectionHandler.sections.pinned}, ...defaultSectionParams},
				default: {...{id: SectionHandler.sections.default}, ...defaultSectionParams},
				more: {...{id: SectionHandler.sections.more}, ...defaultSectionParams},
				empty: {...{id: SectionHandler.sections.empty}, ...defaultSectionParams},
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
			return (id in this.items);
		}

		get list()
		{
			return Object.values(this.items);
		}
	}

	class Cache
	{
		constructor(cacheKey)
		{
			this.cacheKey = cacheKey;

			this.storage = Application.sharedStorage('tasksTaskList');
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
			const cache = this.storage.get(this.cacheKey);

			if (typeof cache === 'string')
			{
				return JSON.parse(cache);
			}

			return this.defaultData;
		}

		set(data)
		{
			this.storage.set(this.cacheKey, JSON.stringify(data));
		}

		update(key, value)
		{
			const currentCache = this.get();
			currentCache[key] = value;
			this.set(currentCache);
		}

		clear()
		{
			this.set({});
		}

		setDefaultData(defaultData)
		{
			this.defaultData = defaultData;
		}
	}

	class TasksCache extends Cache
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
				caches.set(id, (new TasksCache(id)));
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
			const groupId = parseInt(BX.componentParameters.get('GROUP_ID', 0), 10);
			const roleMap = {
				[Filter.roleType.all]: (groupId > 0 && groupId === Number(task.groupId) || task.isMember()),
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
				const groupId = parseInt(BX.componentParameters.get('GROUP_ID', 0), 10);
				const roleMap = {
					[Filter.roleType.all]: (groupId > 0 && groupId === Number(task.groupId) || task.isMember()),
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
							const pinned = cache[role][counter].filter(task => task.sectionCode === SectionHandler.sections.pinned);
							const others = cache[role][counter].filter(task => task.sectionCode !== SectionHandler.sections.pinned);
							cache[role][counter] = pinned.sort(TasksCache.compare).concat(others.sort(TasksCache.compare));
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
			const groupId = parseInt(BX.componentParameters.get('GROUP_ID', 0), 10);
			const counterMap = {
				[Filter.counterType.none]: (groupId > 0 && groupId === Number(task.groupId) || task.isMember()),
				[Filter.counterType.expired]: (task.getCounterMyExpiredCount() > 0),
				[Filter.counterType.newComments]: (task.getCounterMyNewCommentsCount() > 0),
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
			this.groupId = (groupId || 0);

			this.role = BX.componentParameters.get('ROLE', Filter.roleType.all);
			this.counter = BX.componentParameters.get('COUNTER', Filter.counterType.none);
			this.showCompleted = BX.componentParameters.get('SHOW_COMPLETED', false);

			this.counters = {};
			this.cache = Cache.getInstance(`filterCounters_${this.groupId}`);

			this.setCounterValue(this.cache.get().counterValue || 0);
			this.setVisualCounters();

			EntityReady.wait('chat').then(() => this.updateCounters());
		}

		updateCounters()
		{
			if (this.isAnotherUserList())
			{
				return;
			}

			console.log('UPDATE COUNTERS');

			const batchOperations = {};

			Object.keys(Filter.getPresetsMap()).forEach((role) => {
				batchOperations[role] = ['tasks.task.counters.get', {
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
			if (this.isAnotherUserList() || (task && task.isMuted))
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
				if (Number(this.currentUser.id) !== Number(data.userId) || this.isAnotherUserList())
				{
					resolve();
					return;
				}

				const counters = data[this.groupId];

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
			const counterValue = (value || this.getCounterValue());

			this.setDownMenuTasksCounter(counterValue);
			this.setListTopButtonCounter(counterValue);
			this.setTasksTabCounter(counterValue);
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
			if (!this.isAnotherUserList())
			{
				Application.setBadges({[`tasksTaskListMoreButton_${this.owner.id}_${this.groupId}`]: value});
			}
		}

		setTasksTabCounter(value)
		{
			if (this.list.isTabsMode)
			{
				if (this.isMyList())
				{
					BX.postComponentEvent('tasks.list:setVisualCounter', [{value}], 'tasks.tabs');
				}
				else if (this.isGroupList())
				{
					BX.postComponentEvent('tasks.list:setVisualCounter', [{
						value,
						guid: this.list.tabsGuid,
					}]);
				}
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
			this.counters.view_all_total = (!this.isAnotherUserList() ? value : 0);
		}

		saveCache()
		{
			if (!this.isAnotherUserList())
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
			const currentUserId = (this.owner.id || this.currentUser.id);
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
						REAL_STATUS: 5,
					},
				};
			}

			switch (this.counter)
			{
				case Filter.counterType.newComments:
					if (this.groupId > 0)
					{
						filter.MEMBER = currentUserId;
					}
					filter.WITH_NEW_COMMENTS = 'Y';
					filter.IS_MUTED = 'N';
					break;

				case Filter.counterType.expired:
					if (this.groupId > 0)
					{
						filter.MEMBER = currentUserId;
					}
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

		isGroupList()
		{
			return (this.groupId > 0);
		}

		isAnotherUserList()
		{
			return (Number(this.currentUser.id) !== Number(this.owner.id));
		}

		isMyList()
		{
			return (!this.isGroupList() && !this.isAnotherUserList());
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

	class MenuPopup
	{
		/**
		 * @param {TaskList} list
		 */
		constructor(list)
		{
			this.list = list;
			this.filter = list.filter;
			this.order = list.order;
		}

		show()
		{
			const menuItems = this.prepareItems();
			const menuSections = this.prepareSections();

			if (!this.popupMenu)
			{
				this.popupMenu = dialogs.createPopupMenu();
			}
			this.popupMenu.setData(menuItems, menuSections, (eventName, item) => {
				if (eventName === 'onItemSelected')
				{
					this.onItemSelected(item);
				}
			});
			this.popupMenu.show();
		}

		prepareSections()
		{
			return [{id: SectionHandler.sections.default}];
		}

		prepareItems()
		{

		}

		onItemSelected()
		{

		}
	}

	class OptionMenu extends MenuPopup
	{
		prepareItems()
		{
			const urlPrefix = `${pathToComponent}images/mobile-tasks-list-popup-`;

			return [
				{
					id: Filter.roleType.all,
					title: BX.message('TASKS_POPUP_MENU_ROLE_ALL'),
					iconUrl: `${urlPrefix}role-all.png`,
					iconName: 'finished_tasks',
					sectionCode: SectionHandler.sections.default,
					checked: (this.filter.getRole() === Filter.roleType.all),
					counterValue: this.filter.getCounterValue(),
					counterStyle: {
						backgroundColor: '#FF5752',
					},
				},
				{
					id: Filter.roleType.responsible,
					title: BX.message('TASKS_POPUP_MENU_ROLE_RESPONSIBLE'),
					iconUrl: `${urlPrefix}role-responsible.png`,
					sectionCode: SectionHandler.sections.default,
					checked: (this.filter.getRole() === Filter.roleType.responsible),
					counterValue: this.filter.getCounterValue(Filter.roleType.responsible),
					counterStyle: {
						backgroundColor: '#FF5752',
					},
				},
				{
					id: Filter.roleType.accomplice,
					title: BX.message('TASKS_POPUP_MENU_ROLE_ACCOMPLICE'),
					iconUrl: `${urlPrefix}role-accomplice.png`,
					sectionCode: SectionHandler.sections.default,
					checked: (this.filter.getRole() === Filter.roleType.accomplice),
					counterValue: this.filter.getCounterValue(Filter.roleType.accomplice),
					counterStyle: {
						backgroundColor: '#FF5752',
					},
				},
				{
					id: Filter.roleType.originator,
					title: BX.message('TASKS_POPUP_MENU_ROLE_ORIGINATOR'),
					iconUrl: `${urlPrefix}role-originator.png`,
					sectionCode: SectionHandler.sections.default,
					checked: (this.filter.getRole() === Filter.roleType.originator),
					counterValue: this.filter.getCounterValue(Filter.roleType.originator),
					counterStyle: {
						backgroundColor: '#FF5752',
					},
				},
				{
					id: Filter.roleType.auditor,
					title: BX.message('TASKS_POPUP_MENU_ROLE_AUDITOR'),
					iconUrl: `${urlPrefix}role-auditor.png`,
					sectionCode: SectionHandler.sections.default,
					checked: (this.filter.getRole() === Filter.roleType.auditor),
					counterValue: this.filter.getCounterValue(Filter.roleType.auditor),
					counterStyle: {
						backgroundColor: '#FF5752',
					},
				},
				{
					id: 'toggleOrder',
					title: BX.message(
						this.order.isDeadline() ? 'TASKS_POPUP_MENU_ORDER_ACTIVITY' : 'TASKS_POPUP_MENU_ORDER_DEADLINE'
					),
					iconName: 'term',
					sectionCode: SectionHandler.sections.default,
					showTopSeparator: true,
				},
				{
					id: 'toggleCompletedTasks',
					title: BX.message(`TASKS_POPUP_MENU_${(!this.filter.showCompleted ? 'SHOW' : 'HIDE')}_CLOSED_TASKS`),
					iconUrl: `${urlPrefix}${(!this.filter.showCompleted ? 'show' : 'hide')}-completed.png`,
					sectionCode: SectionHandler.sections.default,
					disable: this.order.isDeadline(),
				},
				{
					id: 'readAll',
					title: BX.message('TASKS_POPUP_MENU_READ_ALL'),
					iconName: 'read',
					sectionCode: SectionHandler.sections.default,
				},
			];
		}

		onItemSelected(item)
		{
			const {id} = item;

			switch (id)
			{
				case Filter.roleType.all:
				case Filter.roleType.responsible:
				case Filter.roleType.accomplice:
				case Filter.roleType.originator:
				case Filter.roleType.auditor:
					this.onRoleClick(id);
					break;

				case 'toggleOrder':
					this.onDeadlineSwitchClick();
					break;

				case 'readAll':
					this.onReadAllClick();
					break;

				case 'toggleCompletedTasks':
					this.onToggleCompleted();
					break;
			}
		}

		onRoleClick(newRole)
		{
			const currentRole = this.filter.getRole();

			if (currentRole === newRole)
			{
				this.filter.setRole(Filter.roleType.all);
			}
			else
			{
				this.filter.setRole(newRole);
			}

			this.list.updateTitle();
			this.list.reload(0, true);
		}

		onDeadlineSwitchClick()
		{
			this.order.changeOrder();
			if (this.order.isDeadline())
			{
				this.filter.showCompleted = false;
			}

			this.list.updateTitle();
			this.list.reload(0, true);
		}

		onToggleCompleted()
		{
			this.filter.showCompleted = !this.filter.showCompleted;
			this.list.reload();
		}

		onReadAllClick()
		{
			this.list.pseudoReadTasks([...this.list.taskList.keys()], true);

			(new RequestExecutor('tasks.task.comment.readAll', {
				groupId: this.list.groupId || null,
				userId: this.list.owner.id || this.list.currentUser.id,
				role: this.filter.getRole(),
			}))
				.call()
				.then((response) => {
					console.log(response);
					if (response.result === true)
					{
						Notify.showIndicatorSuccess({
							text: BX.message('TASKS_LIST_READ_ALL_NOTIFICATION'),
							hideAfter: 1500,
						});
					}
				})
			;
		}
	}

	class MoreMenu extends MenuPopup
	{
		prepareItems()
		{
			const urlPrefix = `${pathToComponent}images/mobile-tasks-list-popup-`;
			const roles = [
				{
					id: Filter.roleType.all,
					title: BX.message('TASKS_POPUP_MENU_ROLE_ALL'),
					iconUrl: `${urlPrefix}role-all.png`,
					iconName: 'finished_tasks',
					sectionCode: SectionHandler.sections.default,
					checked: (this.filter.getRole() === Filter.roleType.all),
					counterValue: this.filter.getCounterValue(),
					counterStyle: {
						backgroundColor: '#FF5752',
					},
				},
				{
					id: Filter.roleType.responsible,
					title: BX.message('TASKS_POPUP_MENU_ROLE_RESPONSIBLE'),
					iconUrl: `${urlPrefix}role-responsible.png`,
					sectionCode: SectionHandler.sections.default,
					checked: (this.filter.getRole() === Filter.roleType.responsible),
					counterValue: this.filter.getCounterValue(Filter.roleType.responsible),
					counterStyle: {
						backgroundColor: '#FF5752',
					},
				},
				{
					id: Filter.roleType.accomplice,
					title: BX.message('TASKS_POPUP_MENU_ROLE_ACCOMPLICE'),
					iconUrl: `${urlPrefix}role-accomplice.png`,
					sectionCode: SectionHandler.sections.default,
					checked: (this.filter.getRole() === Filter.roleType.accomplice),
					counterValue: this.filter.getCounterValue(Filter.roleType.accomplice),
					counterStyle: {
						backgroundColor: '#FF5752',
					},
				},
				{
					id: Filter.roleType.originator,
					title: BX.message('TASKS_POPUP_MENU_ROLE_ORIGINATOR'),
					iconUrl: `${urlPrefix}role-originator.png`,
					sectionCode: SectionHandler.sections.default,
					checked: (this.filter.getRole() === Filter.roleType.originator),
					counterValue: this.filter.getCounterValue(Filter.roleType.originator),
					counterStyle: {
						backgroundColor: '#FF5752',
					},
				},
				{
					id: Filter.roleType.auditor,
					title: BX.message('TASKS_POPUP_MENU_ROLE_AUDITOR'),
					iconUrl: `${urlPrefix}role-auditor.png`,
					sectionCode: SectionHandler.sections.default,
					checked: (this.filter.getRole() === Filter.roleType.auditor),
					counterValue: this.filter.getCounterValue(Filter.roleType.auditor),
					counterStyle: {
						backgroundColor: '#FF5752',
					},
				},
			];
			const counters = [
				{
					id: Filter.counterType.newComments,
					title: BX.message('TASKS_POPUP_MENU_COUNTER_NEW_COMMENTS'),
					sectionCode: SectionHandler.sections.default,
					checked: (this.filter.getCounter() === Filter.counterType.newComments),
					counterValue: this.filter.getCounterValue(this.filter.getRole(), Filter.counterType.newComments),
					counterStyle: {
						backgroundColor: '#9DCF00',
					},
				},
				{
					id: Filter.counterType.expired,
					title: BX.message('TASKS_POPUP_MENU_COUNTER_EXPIRED'),
					sectionCode: SectionHandler.sections.default,
					checked: (this.filter.getCounter() === Filter.counterType.expired),
					counterValue: this.filter.getCounterValue(this.filter.getRole(), Filter.counterType.expired),
					counterStyle: {
						backgroundColor: '#FF5752',
					},
				},
				{
					id: Filter.counterType.supposedlyCompleted,
					title: BX.message('TASKS_POPUP_MENU_COUNTER_SUPPOSEDLY_COMPLETED'),
					sectionCode: SectionHandler.sections.default,
					checked: (this.filter.getCounter() === Filter.counterType.supposedlyCompleted),
				},
			];
			const actions = [
				{
					id: 'toggleOrder',
					title: BX.message(
						this.order.isDeadline() ? 'TASKS_POPUP_MENU_ORDER_ACTIVITY' : 'TASKS_POPUP_MENU_ORDER_DEADLINE'
					),
					iconName: 'term',
					sectionCode: SectionHandler.sections.default,
					showTopSeparator: true,
				},
				{
					id: 'toggleCompletedTasks',
					title: BX.message(`TASKS_POPUP_MENU_${(!this.filter.showCompleted ? 'SHOW' : 'HIDE')}_CLOSED_TASKS`),
					iconUrl: `${urlPrefix}${(!this.filter.showCompleted ? 'show' : 'hide')}-completed.png`,
					sectionCode: SectionHandler.sections.default,
					disable: this.order.isDeadline(),
				},
				{
					id: 'readAll',
					title: BX.message('TASKS_POPUP_MENU_READ_ALL'),
					iconName: 'read',
					sectionCode: SectionHandler.sections.default,
					showTopSeparator: true,
				},
			];
			const menuItems = [];

			roles.forEach((role) => {
				menuItems.push(role);
				if (role.id === this.filter.getRole())
				{
					counters.forEach(counter => menuItems.push(counter));
				}
			});
			actions.forEach(action => menuItems.push(action));

			return menuItems;
		}

		onItemSelected(item)
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

				case 'toggleCompletedTasks':
					this.onToggleCompleted();
					break;

				case 'readAll':
					this.onReadAllClick();
					break;
			}
		}

		onRoleClick(newRole)
		{
			newRole = (this.filter.getRole() === newRole ? Filter.roleType.all : newRole);
			this.filter.setRole(newRole);
			this.filter.setCounter(Filter.counterType.none);

			this.list.updateTitle();
			this.list.setTopButtons();
			this.list.reload(0, true);
		}

		onCounterClick(newCounter)
		{
			newCounter = (this.filter.getCounter() === newCounter ? Filter.counterType.none : newCounter);
			this.filter.setCounter(newCounter);

			this.list.updateTitle();
			this.list.setTopButtons();
			this.list.reload(0, true);
		}

		onDeadlineSwitchClick()
		{
			this.order.changeOrder();
			if (this.order.isDeadline())
			{
				this.filter.showCompleted = false;
			}

			this.list.updateTitle();
			this.list.reload(0, true);
		}

		onToggleCompleted()
		{
			this.filter.showCompleted = !this.filter.showCompleted;
			this.list.reload();
		}

		onReadAllClick()
		{
			this.list.pseudoReadTasks([...this.list.taskList.keys()], true);

			let readAllFunction = this.getReadAllForProject();
			if (!this.list.isGroupList() || this.filter.getRole() !== Filter.roleType.all)
			{
				readAllFunction = this.getReadAllByRole();
			}
			readAllFunction.call().then(response => this.onReadAllSuccess(response.result));
		}

		onReadAllSuccess(result)
		{
			if (result)
			{
				Notify.showIndicatorSuccess({
					text: BX.message('TASKS_LIST_READ_ALL_NOTIFICATION'),
					hideAfter: 1500,
				});
			}
		}

		getReadAllForProject()
		{
			const options = {
				groupId: this.list.groupId,
			};
			return (new RequestExecutor('tasks.task.comment.readProject', options));
		}

		getReadAllByRole()
		{
			const options = {
				groupId: (this.list.groupId || null),
				userId: (this.list.owner.id || this.list.currentUser.id),
				role: this.filter.getRole(),
			};
			return (new RequestExecutor('tasks.task.comment.readAll', options));
		}
	}

	class Options
	{
		constructor()
		{
			const now = new Date();
			now.setDate(now.getDate() - 1);

			this.cache = Cache.getInstance('options');
			this.cache.setDefaultData({
				swipeShowHelper: {
					value: 0,
					limit: 2,
				},
				deadlines: {
					lastTime: now.getTime(),
					value: result.deadlines,
				},
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

			this.cache = Cache.getInstance('search');
			this.fillCacheWithLastActiveProjects();
		}

		fillCacheWithLastActiveProjects()
		{
			if (apiVersion >= 41)
			{
				return;
			}

			(new RequestExecutor('mobile.tasks.group.lastActive.get'))
				.call()
				.then((response) => {
					const cacheKey = Search.cacheKeys.lastActiveProjects;
					this.cache.update(cacheKey, response.result.slice(0, this.maxProjectCount + 1));
				})
			;
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
						params: TaskList.queryParams,
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
			if (apiVersion >= 41)
			{
				return [];
			}

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
			this.loadProjectsFromCache();
			this.loadTasksFromCache();

			this.renderList(true);
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

			(new RequestExecutor('tasks.task.list', {
				select: TaskList.selectFields,
				filter: {ID: taskId},
				params: TaskList.queryParams,
			}))
				.call()
				.then((response) => {
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
				})
			;

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

			if (apiVersion >= 41)
			{
				let projectFromList = null;
				this.list.taskList.forEach((task) => {
					if (Number(task.group.id) === Number(data.id))
					{
						projectFromList = task.group;
					}
				});

				const projectItem = {
					id: project.id,
					title: project.name,
					params: {
						avatar: (projectFromList ? projectFromList.image : project.image),
						initiatedByType: (
							projectFromList && projectFromList.additionalData
								? projectFromList.additionalData.initiatedByType
								: null
						),
						features: (
							projectFromList && projectFromList.additionalData
								? projectFromList.additionalData.features
								: []
						),
						membersCount: (projectFromList ? projectFromList.membersCount : 0),
						role: (
							projectFromList && projectFromList.additionalData
								? projectFromList.additionalData.role
								: null
						),
						opened: project.opened || false,
					},
				};

				const projectData = {
					siteId: BX.componentParameters.get('SITE_ID', env.siteId),
					siteDir: BX.componentParameters.get('SITE_DIR', env.siteDir),
					projectId: project.id,
					action: 'view',
					item: projectItem,
					newsPathTemplate: this.list.projectNewsPathTemplate,
					calendarWebPathTemplate: this.list.projectCalendarWebPathTemplate,
					currentUserId: parseInt(this.list.owner.id),
				};

				BX.postComponentEvent('projectbackground::project::action', [ projectData ], 'background');
			}
			else
			{
				BX.postComponentEvent('taskbackground::task::action', [{
					groupId: project.id,
					groupName: project.name,
					groupImageUrl: project.image,
					ownerId: this.list.owner.id,
					getProjectData: true,
				}]);
			}

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
				project_read_all: {
					method: this.list.onPullProjectReadAll,
					context: this.list,
				},
				user_option_changed: {
					method: this.list.onPullUserOptionChanged,
					context: this.list,
				},
				user_counter: {
					method: this.list.filter.onUserCounter,
					context: this.list.filter,
				},
				task_result_create: {
					method: this.list.onPullTaskResultCreate,
					context: this.list,
				},
				task_result_delete: {
					method: this.list.onPullTaskResultDelete,
					context: this.list,
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
				'project_read_all',
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

	/**
	 * @class TaskList
	 */
	class TaskList
	{
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
				'FAVORITE',
				'NOT_VIEWED',
				'GROUP_ID',
				'IS_MUTED',
				'IS_PINNED',
				'COUNTERS',
			];
		}

		static get queryParams()
		{
			return {
				RETURN_ACCESS: 'Y',
				RETURN_USER_INFO: 'Y',
				RETURN_GROUP_INFO: 'Y',
				SEND_PULL: 'Y',
				MODE: 'mobile',
				WITH_RESULT_INFO: 'Y',
			};
		}

		static get statusList()
		{
			return Task.statusList;
		}

		static get titles()
		{
			return {
				[Filter.roleType.all]: BX.message('TASKS_LIST_HEADER_ROLE_ALL_V2'),
				[Filter.roleType.responsible]: BX.message('TASKS_LIST_HEADER_ROLE_RESPONSIBLE'),
				[Filter.roleType.accomplice]: BX.message('TASKS_LIST_HEADER_ROLE_ACCOMPLICE'),
				[Filter.roleType.originator]: BX.message('TASKS_LIST_HEADER_ROLE_ORIGINATOR'),
				[Filter.roleType.auditor]: BX.message('TASKS_LIST_HEADER_ROLE_AUDITOR'),
				deadlines: BX.message('TASKS_LIST_HEADER_DEADLINES'),
			};
		}

		static get projectRoles()
		{
			return {
				owner: 'A',
				moderator: 'E',
				member: 'K',
				request: 'Z',
			};
		}

		static get initiatorTypes()
		{
			return {
				group: 'G',
				user: 'U',
			};
		}

		constructor(list, owner, params)
		{
			console.log('taskList.constructor');

			this.initCommon(list, owner, params);

			if (this.group.getData)
			{
				this.fillProjectData().then(() => {
					if (!this.group.id || this.group.isOpened || this.isMember())
					{
						this.initAccessibleList(params);
					}
					else
					{
						this.initInaccessibleList();
					}
				});
			}
			else if (!this.group.id || this.group.isOpened || this.isMember())
			{
				this.initAccessibleList(params);
			}
			else
			{
				this.initInaccessibleList();
			}
		}

		fillProjectData()
		{
			return new Promise((resolve, reject) => {
				(new RequestExecutor('socialnetwork.api.workgroup.get', {
					params: {
						select: [
							'AVATAR',
							'AVATAR_TYPES',
							'USER_DATA',
						],
						groupId: this.group.id,
					},
				}))
					.call()
					.then(
						(response) => {
							const {result} = response;
							this.group = {
								id: parseInt(result.ID, 10),
								name: result.NAME,
								imageUrl: (result.AVATAR || (result.AVATAR_TYPES[(result.AVATAR_TYPE || 'folder')].mobileUrl)),
								isOpened: (result.OPENED === 'Y'),
								role: result.USER_DATA.ROLE,
								initiatedByType: result.USER_DATA.INITIATED_BY_TYPE,
							};
							resolve();
						},
						() => reject(),
					)
				;
			});
		}

		initCommon(list, owner, params)
		{
			this.list = list;
			this.owner = {id: owner};
			this.currentUser = result.settings.userInfo;
			this.isTabsMode = params.isTabsMode;
			this.projectNewsPathTemplate = (params.projectNewsPathTemplate || '');
			this.projectCalendarWebPathTemplate = (params.projectCalendarWebPathTemplate || '');

			this.taskList = new Map();
			this.newTaskList = new Map();
			this.comments = new Map();

			const data = BX.componentParameters.get('DATA', {});
			this.groupId = parseInt(BX.componentParameters.get('GROUP_ID', 0), 10);
			this.group = {
				id: this.groupId,
				name: data.groupName,
				imageUrl: data.groupImageUrl,
				isOpened: data.groupOpened,
				role: data.relationRole,
				initiatedByType: data.relationInitiatedByType,
				getData: data.getProjectData,
			};

			this.search = new Search(this);
			this.welcomeScreen = new WelcomeScreen(this);
			this.joinButton = new JoinButton(this);

			BX.onViewLoaded(() => {
				this.setListListeners();
			});
		}

		initAccessibleList()
		{
			this.tabsGuid = BX.componentParameters.get('TABS_GUID', '');

			this.start = 0;
			this.pageSize = 50;
			this.canShowSwipeActions = true;
			this.isRepeating = false;

			this.filter = new Filter(this, this.currentUser, this.owner, this.groupId);
			this.order = new Order();
			this.options = new Options();

			// this.optionMenu = new OptionMenu(this);
			this.moreMenu = new MoreMenu(this);

			this.pull = new Pull(this);
			this.push = new Push(this);
			this.fileStorage = new TaskUploadFilesStorage();

			this.cache = TasksCache.getInstance(`tasksList_${this.owner.id}_${this.groupId}`);

			this.getTaskLimitExceeded();
			this.checkDeadlinesUpdate();

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

				this.setTopButtons();
				this.setFloatingButton();
				this.setJoinButton();
				this.bindEvents();

				this.filter.updateCounters();

				this.loadTasksFromCache();
				this.reload();
			});
		}

		initInaccessibleList()
		{
			BX.onViewLoaded(() => {
				this.welcomeScreen.show();

				if (this.group.role !== TaskList.projectRoles.request)
				{
					this.joinButton.showForPrivate();
				}
				else if (this.group.initiatedByType === TaskList.initiatorTypes.group)
				{
					this.joinButton.showForOpened();
				}
				else
				{
					this.joinButton.showForPrivate(true);
				}
			});
		}

		isMember()
		{
			const memberRoles = [
				TaskList.projectRoles.owner,
				TaskList.projectRoles.moderator,
				TaskList.projectRoles.member,
			];

			return memberRoles.includes(this.group.role);
		}

		isMyList()
		{
			return (!this.isGroupList() && !this.isAnotherUserList());
		}

		isGroupList()
		{
			return (this.groupId > 0);
		}

		isAnotherUserList()
		{
			return (Number(this.currentUser.id) !== Number(this.owner.id));
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
				onClickJoinButton: {
					callback: this.joinButton.onClick,
					context: this.joinButton,
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
			BX.addCustomEvent('task.view.onCommentsRead', eventData => this.onCommentsRead(eventData));
			BX.addCustomEvent('onFileUploadStatusChanged', this.onFileUploadStatusChange.bind(this));

			if (this.isTabsMode && this.isMyList())
			{
				BX.addCustomEvent('tasks.tabs:onTabSelected', eventData => this.onTabSelected(eventData));
				BX.addCustomEvent('tasks.tabs:onAppActiveBefore', () => this.onAppActiveBefore());
				BX.addCustomEvent('tasks.tabs:onAppActive', () => this.onAppActive());
				BX.addCustomEvent('tasks.tabs:onAppPaused', () => this.onAppPaused());
			}
			else
			{
				BX.addCustomEvent('onAppActiveBefore', () => this.onAppActiveBefore());
				BX.addCustomEvent('onAppActive', () => this.onAppActive());
				BX.addCustomEvent('onAppPaused', () => this.onAppPaused());
			}

			if (apiVersion >= 34)
			{
				BX.addCustomEvent('onTabsSelected', tabName => this.onTabsSelected(tabName));
			}

			this.pull.subscribe();
		}

		setTopButtons()
		{
			const isDefaultRole = this.filter.getRole() === Filter.roleType.all;
			const isDefaultCounter = this.filter.getCounter() === Filter.counterType.none;

			this.list.setRightButtons([
				{
					type: 'search',
					callback: () => this.list.showSearchBar(),
				},
				{
					type: (isDefaultRole && isDefaultCounter ? 'more' : 'more_active'),
					badgeCode: `tasksTaskListMoreButton_${this.owner.id}_${this.groupId}`,
					callback: () => this.moreMenu.show(),
				},
			]);
			this.filter.setVisualCounters();
		}

		setFloatingButton()
		{
			if (apiVersion >= 40)
			{
				this.getCanCreateTask().then(
					response => this.renderFloatingButton(response.result),
					response => console.error(response)
				);
			}
		}

		getCanCreateTask()
		{
			return new Promise((resolve, reject) => {
				if (!this.groupId)
				{
					resolve({result: true});
					return;
				}

				(new RequestExecutor('mobile.tasks.group.getCanCreateTask', {
					groupId: this.groupId,
				}))
					.call()
					.then(
						response => resolve(response),
						response => reject(response)
					)
				;
			});
		}

		renderFloatingButton(isExist = false)
		{
			if (isExist)
			{
				this.list.setFloatingButton({
					icon: 'plus',
					callback: () => this.list.showAudioInput(),
				});
			}
			else
			{
				this.list.setFloatingButton({});
			}
		}

		setJoinButton()
		{
			if (this.group.id > 0 && this.group.isOpened && !this.isMember())
			{
				this.joinButton.showForOpened();
			}
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
				cachedTasks = cachedTasks.map(task => ({...task, checkable: false}));

				this.list.setItems(cachedTasks, null, false);
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

				const batchOperations = {
					all: ['tasks.task.list', {
						select,
						filter: this.filter.get(),
						order: this.order.get(),
						start: taskListOffset,
						params: {
							...TaskList.queryParams,
							...{GET_PLUS_ONE: 'Y'},
						},
					}],
				};

				if (!taskListOffset && this.isMyList())
				{
					batchOperations.pinned = ['tasks.task.list', {
						select,
						filter: this.filter.getForPinned(),
						order: this.order.get(),
						params: {
							...TaskList.queryParams,
							...{GET_PLUS_ONE: 'Y'},
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
				sectionHandler.clear();
			}

			sectionHandler.setSortItemParams(SectionHandler.sections.default, {
				[this.order.order]: Order.sectionOrderFields[this.order.order],
			});
			sectionHandler.setSortItemParams(SectionHandler.sections.pinned, {
				[this.order.order]: Order.sectionOrderFields[this.order.order],
			});

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
				this.welcomeScreen.show(WelcomeScreen.type.empty);
				return;
			}

			if (isFirstPage)
			{
				this.welcomeScreen.hide();

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
					sectionCode: SectionHandler.sections.more,
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

		onTabSelected(data)
		{
			if (data.tabId === 'tasks.list')
			{
				this.onAppActiveBefore();
				this.onAppActive();
			}
			else
			{
				this.onAppPaused();
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
			BX.onViewLoaded(() => {
				if (this.push)
				{
					this.push.updateList();
				}
			});
		}

		onAppActive()
		{
			this.activationTime = new Date();
			this.canShowSwipeActions = true;

			if (this.pauseTime)
			{
				const timePassed = this.activationTime.getTime() - this.pauseTime.getTime();
				const minutesPassed = Math.abs(Math.round(timePassed / 60000));

				if (minutesPassed > 29)
				{
					this.runOnAppActiveRepeatedActions();
				}
				else
				{
					this.updateTasksFromEvents();
				}
			}

			this.getTaskLimitExceeded();
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
						promises.push(this.updateTasks(taskIds));
					}
					Promise.allSettled(promises).then(() => this.updateTitle());
				}
			}, 1000);
		}

		updateTasks(taskIds)
		{
			return new Promise((resolve, reject) => {
				const {IS_PINNED, ...filter} = {...this.filter.get(), ID: taskIds};

				(new RequestExecutor('tasks.task.list', {
					select: TaskList.selectFields,
					filter,
					params: TaskList.queryParams,
				}))
					.call()
					.then(
						(response) => {
							this.onUpdateTasksSuccess(taskIds, response.result.tasks);
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

		onUpdateTasksSuccess(taskIds, tasks)
		{
			taskIds.forEach((taskId) => {
				const taskData = tasks.find(task => Number(task.id) === Number(taskId));
				if (taskData)
				{
					this.updateTaskListItem(taskId, taskData);
				}
				else if (this.taskList.has(taskId))
				{
					this.removeItem(taskId);
				}
			});
		}

		updateTaskListItem(taskId, row, sectionId = SectionHandler.sections.default)
		{
			const isMyNewTask = (this.newTaskList.has(row.guid) || this.newTaskList.has(row.id));

			if (this.taskList.has(taskId))
			{
				const task = this.taskList.get(taskId);
				task.setData(row);

				(this.isTaskSuitList(task) || isMyNewTask)
					? this.updateItem(taskId, task)
					: this.removeItem(taskId)
				;
			}
			else if (!isMyNewTask)
			{
				const task = new Task(this.currentUser);
				task.setData(row);

				this.isTaskSuitList(task)
					? this.addItem(task, sectionId)
					: this.removeItem(taskId)
				;
			}
		}

		isTaskSuitList(task)
		{
			return (this.isTaskSuitFilter(task) && this.isTaskSuitGroup(task));
		}

		isTaskSuitFilter(task)
		{
			const role = this.filter.getRole();
			const roleMap = {
				[Filter.roleType.all]: ((this.groupId > 0 && this.groupId === Number(task.groupId)) || task.isMember()),
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

		isTaskSuitGroup(task)
		{
			return ((this.groupId > 0 && this.groupId === Number(task.groupId)) || !this.groupId);
		}

		runOnAppActiveRepeatedActions()
		{
			this.filter.updateCounters();
			this.reload();

			this.setFloatingButton();

			this.search.fillCacheWithLastActiveProjects();
			this.pull.setCanExecute(true);
		}

		getTaskLimitExceeded()
		{
			(new RequestExecutor('tasks.task.limit.isExceeded'))
				.call()
				.then((response) => {
					console.log('taskList:limit.isExceeded', response.result);
					this.taskLimitExceeded = response.result || false;
				})
			;
		}

		checkDeadlinesUpdate()
		{
			const now = new Date();
			const lastDeadlinesGetTime = new Date(this.options.get().deadlines.lastTime);
			const dayChanged = now.getDate() !== lastDeadlinesGetTime.getDate();

			if (dayChanged)
			{
				(new RequestExecutor('mobile.tasks.deadlines.get'))
					.call()
					.then((response) => {
						this.options.update('deadlines', {
							lastTime: now.getTime(),
							value: response.result,
						});
					})
				;
			}
		}

		onTabsSelected(tabName)
		{
			const isDefaultRole = this.filter.getRole() === Filter.roleType.all;
			const isDefaultCounter = this.filter.getCounter() === Filter.counterType.none;

			if (tabName !== 'tasks' && (!isDefaultRole || !isDefaultCounter || this.order.isDeadline()))
			{
				setTimeout(() => {
					this.filter.setRole(Filter.roleType.all);
					this.filter.setCounter(Filter.counterType.none);

					if (this.order.isDeadline())
					{
						this.order.changeOrder();
					}

					this.updateTitle();
					this.setTopButtons();
					this.reload();
				}, 300);
			}
		}

		onCommentsRead(eventData)
		{
			console.log('task.view.onCommentsRead', eventData);

			const taskId = String(eventData.taskId);
			if (this.taskList.has(taskId))
			{
				this.taskList.get(taskId).pseudoRead();
				this.updateItem(taskId);
			}
		}

		onPullUserOptionChanged(data)
		{
			return new Promise((resolve, reject) => {
				if (Number(data.USER_ID) === Number(this.currentUser.id))
				{
					this.updateTasks([data.TASK_ID.toString()]).then(() => resolve(), () => reject());
				}
			});
		}

		onPullView(data)
		{
			return new Promise((resolve, reject) => {
				if (Number(data.USER_ID) === Number(this.currentUser.id))
				{
					this.updateTasks([data.TASK_ID.toString()]).then(() => resolve(), () => reject());
				}
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

				this.updateTasks([entityId]).then(() => resolve(), () => reject());
			});
		}

		onPullCommentReadAll(data)
		{
			return new Promise((resolve) => {
				const userId = Number(data.USER_ID);
				const groupId = Number(data.GROUP_ID);
				const role = (data.ROLE || Filter.roleType.all);

				if (userId > 0 && userId !== this.owner.id)
				{
					resolve();
					return;
				}

				const taskIdsToRead = [];

				this.taskList.forEach((task, id) => {
					const roleCondition = {
						[Filter.roleType.all]: task.isMember(),
						[Filter.roleType.responsible]: task.isResponsible(),
						[Filter.roleType.accomplice]: task.isAccomplice(),
						[Filter.roleType.originator]: task.isPureCreator(),
						[Filter.roleType.auditor]: task.isAuditor(),
					};
					const groupCondition = (!groupId || Number(task.groupId) === groupId);
					if (roleCondition[role] && groupCondition)
					{
						taskIdsToRead.push(id);
					}
				});

				this.pseudoReadTasks(taskIdsToRead);
				resolve();
			});
		}

		onPullProjectReadAll(data)
		{
			return new Promise((resolve) => {
				const userId = Number(data.USER_ID);
				const groupId = Number(data.GROUP_ID);

				if (userId > 0 && userId !== this.owner.id)
				{
					resolve();
					return;
				}

				const taskIdsToRead = [];

				this.taskList.forEach((task, id) => {
					// TODO: check if this is not scrum project
					if (groupId ? Number(task.groupId) === groupId : Number(task.groupId) > 0)
					{
						taskIdsToRead.push(id);
					}
				});

				this.pseudoReadTasks(taskIdsToRead);
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

				this.updateTasks([data.TASK_ID.toString()]).then(() => resolve(), () => reject());
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

				this.updateTasks([data.TASK_ID.toString()]).then(() => resolve(), () => reject());
			});
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

		onPullTaskResultCreate(data)
		{
			return this.updateTaskResultData(data);
		}

		onPullTaskResultDelete(data)
		{
			return this.updateTaskResultData(data);
		}

		updateTaskResultData(data)
		{
			return new Promise((resolve) => {
				const taskId = String(data.taskId);
				if (this.taskList.has(taskId))
				{
					this.taskList.get(taskId).updateData({
						taskRequireResult: data.taskRequireResult,
						taskHasResult: data.taskHasResult,
					});
				}
				resolve();
			});
		}

		onNewItem(params)
		{
			console.log('onNewItem', params);

			const title = (apiVersion >= 40 ? params.text : params);
			const attachedFiles = this.prepareNewItemAttachedFiles((apiVersion >= 40 ? params.attachedFiles : null));

			if (title.trim().length === 0)
			{
				return;
			}

			const task = new Task(this.currentUser);
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
			task.files = attachedFiles.disk;

			this.newTaskList.set(task.guid);
			this.addItem(task, SectionHandler.sections.new);

			const oldTaskId = task.id;
			task.save().then(
				() => {
					this.newTaskList.set(task.id);
					this.attachFiles(attachedFiles, task.id);
					this.updateItem(oldTaskId, {id: task.id});
				},
				() => {
					this.newTaskList.delete(task.guid);
					this.removeItem(oldTaskId);
				}
			);
		}

		prepareNewItemAttachedFiles(attachedFiles)
		{
			const files = {
				disk: [],
				local: [],
			};

			if (!attachedFiles)
			{
				return files;
			}

			const getGuid = function() {
				const s4 = function() {
					return Math.floor((1 + Math.random()) * 0x10000).toString(16).substring(1);
				};
				return `${s4()}${s4()}-${s4()}-${s4()}-${s4()}-${s4()}${s4()}${s4()}`;
			};

			attachedFiles.forEach((file) => {
				if (file.dataAttributes)
				{
					files.disk.push(file.dataAttributes.VALUE);
				}
				else
				{
					const taskId = `task-${getGuid()}`;
					files.local.push({
						taskId,
						id: taskId,
						params: file,
						name: file.name,
						type: file.type,
						url: file.url,
						previewUrl: file.previewUrl,
						folderId: result.diskFolderId,
						onDestroyEventName: TaskUploaderEvents.FILE_SUCCESS_UPLOAD,
					});
				}
			});

			return files;
		}

		attachFiles(files, taskId)
		{
			const {local} = files;

			if (local.length <= 0)
			{
				return;
			}

			local.forEach(file => file.params.taskId = taskId);

			this.fileStorage.addFiles(local);
			BX.postComponentEvent('onFileUploadTaskReceived', [{files: local}], 'background');
		}

		onFileUploadStatusChange(eventName, eventData, taskId)
		{
			if (taskId.indexOf('task-') !== 0)
			{
				return false;
			}

			switch (eventName)
			{
				case BX.FileUploadEvents.FILE_CREATED:
				case BX.FileUploadEvents.FILE_UPLOAD_START:
				case BX.FileUploadEvents.FILE_UPLOAD_PROGRESS:
				case BX.FileUploadEvents.ALL_TASK_COMPLETED:
				case BX.FileUploadEvents.TASK_TOKEN_DEFINED:
				case BX.FileUploadEvents.TASK_CREATED:
				default:
					// do nothing
					break;

				case BX.FileUploadEvents.TASK_STARTED_FAILED:
				case BX.FileUploadEvents.FILE_CREATED_FAILED:
				case BX.FileUploadEvents.FILE_UPLOAD_FAILED:
				case BX.FileUploadEvents.TASK_CANCELLED:
				case BX.FileUploadEvents.TASK_NOT_FOUND:
				case BX.FileUploadEvents.FILE_READ_ERROR:
				case TaskUploaderEvents.FILE_SUCCESS_UPLOAD:
					this.fileStorage.removeFiles([taskId]);
					this.updateItem(eventData.file.extra.params.taskId, {});
					break;

				case TaskUploaderEvents.FILE_FAIL_UPLOAD:
					if (
						eventData.errors
						&& Array.isArray(eventData.errors)
						&& eventData.errors.length
					)
					{
						InAppNotifier.showNotification({
							backgroundColor: '#075776',
							time: 5,
							blur: true,
							message: `${eventData.errors[0].message}: ${eventData.file.name}`,
						});
					}
					this.fileStorage.removeFiles([taskId]);
					this.updateItem(eventData.file.extra.params.taskId, {});
					break;
			}

			return true;
		}

		getItemDataFromTask(task, withActions = true)
		{
			let itemData = task.getTaskInfo(withActions);

			itemData.backgroundColor = Task.backgroundColors.default;
			itemData.sectionCode = SectionHandler.sections.default;
			itemData.type = 'task';
			itemData.sortValues = {
				deadline: task.deadline || 9999999999999,
				activityDate: task.activityDate,
			};
			itemData.locked = (this.fileStorage.getArrayFiles().findIndex(file => file.params.taskId === task.id) >= 0);

			if (task.isPinned)
			{
				itemData.backgroundColor = Task.backgroundColors.pinned;
				itemData.sectionCode = SectionHandler.sections.pinned;
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
								sectionCode: SectionHandler.sections.default,
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

		addItem(task, sectionId = SectionHandler.sections.default)
		{
			BX.onViewLoaded(() => {
				const taskId = task.id;
				if (this.taskList.has(taskId))
				{
					return;
				}

				this.welcomeScreen.hide();

				const taskData = this.getItemDataFromTask(task);
				taskData.sectionCode = sectionId;
				if (sectionId === SectionHandler.sections.new)
				{
					if (apiVersion >= 38)
					{
						taskData.locked = true;
					}
					taskData.actions = taskData.actions.filter(item => item.identifier === 'changeResponsible');
				}

				this.taskList.set(taskId, task);
				this.list.addItems([taskData]);
				this.cache.addTask({[taskId]: {task, taskData}});
			});
		}

		updateItem(id, fields = {})
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
					this.newTaskList.has(task.guid) || this.newTaskList.has(task.id)
						? SectionHandler.sections.new
						: taskData.sectionCode
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

				if (this.taskList.size === 0)
				{
					this.welcomeScreen.show(WelcomeScreen.type.empty);
				}
			});
		}

		onProjectSelected(data)
		{
			if (this.groupId > 0)
			{
				return;
			}

			let project = null;
			this.taskList.forEach((task) => {
				if (Number(task.group.id) === Number(data.id))
				{
					project = task.group;
				}
			});

			if (apiVersion >= 41)
			{
				const item = {
					id: data.id,
					title: (project ? project.name : ''),
					params: {
						avatar: (project ? project.image : data.imageUrl),
						initiatedByType: (project && project.additionalData ? project.additionalData.initiatedByType : null),
						features: (project && project.additionalData ? project.additionalData.features : []),
						membersCount: project.membersCount,
						role: (project && project.additionalData ? project.additionalData.role : null),
						opened: project.opened || false,
					},
				};

				const projectData = {
					siteId: BX.componentParameters.get('SITE_ID', env.siteId),
					siteDir: BX.componentParameters.get('SITE_DIR', env.siteDir),
					projectId: data.id,
					action: 'view',
					item: item,
					newsPathTemplate: this.projectNewsPathTemplate,
					calendarWebPathTemplate: this.projectCalendarWebPathTemplate,
					currentUserId: parseInt(this.owner.id),
				};

				BX.postComponentEvent('projectbackground::project::action', [ projectData ], 'background');
			}
			else
			{
				BX.postComponentEvent('taskbackground::task::action', [{
					groupId: data.id,
					groupName: project.name,
					groupImageUrl: data.imageUrl,
					ownerId: this.owner.id,
					getProjectData: true,
				}]);
			}
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
				this.filter.pseudoUpdateCounters(-task.getCounterMyNewCommentsCount(), task);

				task.pseudoRead();
				this.updateItem(taskId);

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
			else if (task.isWaitCtrlCounts)
			{
				this.onApproveAction(task);
			}
			else
			{
				if (!task.isRequireResult || task.isHasResult)
				{
					this.updateItem(task.id, {
						status: Task.statusList.completed,
						activityDate: Date.now(),
					});
					task.complete();
				}
				else
				{
					const oldStatus = task.status;
					task.complete().then(() => {}, () => task.status = oldStatus);
					task.status = oldStatus;
					this.updateItem(task.id);
				}
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

			if (!this.popupMenu)
			{
				this.popupMenu = dialogs.createPopupMenu();
			}
			this.popupMenu.setData(
				taskItemData.params.popupActions,
				[{id: SectionHandler.sections.default}],
				(eventName, item) => {
					if (eventName === 'onItemSelected')
					{
						this.onActionsPopupItemSelected(item, task);
					}
				}
			);
			this.popupMenu.setPosition('center');
			this.popupMenu.show();
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
					this.updateItem(task.id, {deadline: ts, activityDate: Date.now()});
					void task.saveDeadline();
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
			(new RecipientSelector('TASKS_MEMBER_SELECTOR_EDIT_responsible', ['user']))
				.setSingleChoose(true)
				.setTitle(BX.message('TASKS_LIST_POPUP_RESPONSIBLE'))
				.setSelected({
					user: [{
						id: task.responsible.id,
						title: task.responsible.name,
						imageUrl: task.responsible.icon,
					}],
				})
				.open()
				.then((recipients) => {
					if (recipients.user && recipients.user.length > 0)
					{
						const user = recipients.user[0];
						if (Number(task.responsible.id) === Number(user.id))
						{
							return;
						}
						task.responsible = TaskList.getItemDataFromUser(user);
						if (!task.isMember(this.currentUser.id))
						{
							if (!this.groupId)
							{
								this.removeItem(task.id);
							}
							this.filter.pseudoUpdateCounters(-task.getCounterMyCount(), task);
						}
						else
						{
							this.updateItem(task.id, {
								responsibleIcon: task.responsible.icon,
								activityDate: Date.now(),
							});
						}
						void task.save();
					}
				})
			;
		}

		/**
		 * @param {Task} task
		 */
		onDelegateAction(task)
		{
			(new RecipientSelector('TASKS_MEMBER_SELECTOR_EDIT_responsible', ['user']))
				.setSingleChoose(true)
				.setTitle(BX.message('TASKS_LIST_POPUP_RESPONSIBLE'))
				.setSelected({
					user: [{
						id: task.responsible.id,
						title: task.responsible.name,
						imageUrl: task.responsible.image,
					}],
				})
				.open()
				.then((recipients) => {
					if (recipients.user && recipients.user.length)
					{
						const user = recipients.user[0];
						if (Number(task.responsible.id) === Number(user.id))
						{
							return;
						}
						task.responsible = TaskList.getItemDataFromUser(user);
						this.updateItem(task.id, {
							responsibleIcon: task.responsible.icon,
							activityDate: Date.now(),
						});
						void task.delegate();
					}
				})
			;
		}

		/**
		 * @param {Task} task
		 */
		onChangeGroupAction(task)
		{
			const selected = [];
			if (task.group.id > 0)
			{
				selected.push({
					id: task.group.id,
					title: task.group.name,
					imageUrl: task.group.image,
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
						if (Number(task.groupId) === Number(group.id))
						{
							return;
						}
						task.groupId = Number(group.id);
						task.group = TaskList.getItemDataFromGroup(group);
						this.updateItem(task.id, {activityDate: Date.now(),});
						void task.save();
					}
				})
			;
		}

		/**
		 * @param {Task} task
		 */
		onMuteAction(task)
		{
			this.updateItem(task.id, {isMuted: true});
			this.filter.pseudoUpdateCounters(-task.getCounterMyCount());
			task.mute();
		}

		/**
		 * @param {Task} task
		 */
		onUnmuteAction(task)
		{
			this.updateItem(task.id, {isMuted: false});
			this.filter.pseudoUpdateCounters(task.getCounterMyCount());
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
				if (!this.groupId)
				{
					this.removeItem(task.id);
				}
				this.filter.pseudoUpdateCounters(-task.getCounterMyCount(), task);
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
						this.filter.pseudoUpdateCounters(-task.getCounterMyCount(), task);

						(new RequestExecutor('tasks.task.delete', {taskId: task.id}))
							.call()
							.then(() => {}, response => console.log(response))
						;
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
			this.filter.pseudoUpdateCounters(-task.getCounterMyNewCommentsCount(), task);
			void task.read();
			this.updateItem(task.id);
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

			BX.postComponentEvent('tasks.list:updateTitle', [{
				useProgress,
				guid: this.tabsGuid,
			}]);
		}

		pseudoReadTasks(taskIds, pseudoUpdateCounters = false)
		{
			const items = [];
			const tasks = {};
			let newCommentsRead = 0;

			this.taskList.forEach((task) => {
				const taskId = String(task.id);
				if (taskIds.includes(taskId))
				{
					newCommentsRead += task.getCounterMyNewCommentsCount();
					task.pseudoRead();

					const taskItem = this.getItemDataFromTask(task);
					items.push({
						filter: {id: taskId},
						element: taskItem,
					});
					tasks[taskId] = {task, taskData: taskItem};
				}
			});
			this.list.updateItems(items);
			this.cache.setTaskData(tasks);

			if (pseudoUpdateCounters)
			{
				this.filter.pseudoUpdateCounters(-newCommentsRead);
			}
		}
	}

	return new TaskList(list, parseInt(BX.componentParameters.get('USER_ID', 0), 10), {
		projectNewsPathTemplate: BX.componentParameters.get('PROJECT_NEWS_PATH_TEMPLATE', ''),
		projectCalendarWebPathTemplate: BX.componentParameters.get('PROJECT_CALENDAR_WEB_PATH_TEMPLATE', ''),
		isTabsMode: BX.componentParameters.get('IS_TABS_MODE', false),
	});
})();
