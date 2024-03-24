(() => {
	const require = (ext) => jn.require(ext);

	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { BatchRequestsExecutor } = require('rest/batch-requests-executor');
	const { debounce } = require('utils/function');
	const { EntityReady } = require('entity-ready');
	const { magnifierWithMenuAndDot, moreWithDot } = require('assets/common');
	const { PresetList } = require('tasks/layout/presetList');
	const { Spotlight } = require('spotlight');
	const { TaskCreate } = require('tasks/layout/task/create');
	const { TaskFilter } = require('tasks/filter/task');
	const colorUtils = require('utils/color');
	const { TaskStatus } = require('tasks/enum');

	const platform = Application.getPlatform();
	const caches = new Map();

	class Console
	{
		static log()
		{
			// eslint-disable-next-line no-console
			console.log(...arguments);
		}

		static error()
		{
			// eslint-disable-next-line no-console
			console.error(...arguments);
		}
	}

	class Loading
	{
		/**
		 * @param {TaskList} list
		 */
		constructor(list)
		{
			this.list = list.list;
		}

		show()
		{
			if (!this.isShowed)
			{
				dialogs.showSpinnerIndicator({
					color: AppTheme.colors.base3,
					backgroundColor: colorUtils.transparent(AppTheme.colors.base8, 0.7),
				});
				this.isShowed = true;
			}
		}

		hide()
		{
			if (this.isShowed)
			{
				dialogs.hideSpinnerIndicator();
				this.isShowed = false;
			}
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

		show(type = WelcomeScreen.type.privateProject)
		{
			if (Object.values(WelcomeScreen.type).includes(type))
			{
				let upperText;
				let lowerText;
				let iconName;

				if (type === WelcomeScreen.type.privateProject)
				{
					upperText = Loc.getMessage('MOBILE_TASKS_LIST_WELCOME_SCREEN_PRIVATE_PROJECT_TITLE');
					lowerText = Loc.getMessage('MOBILE_TASKS_LIST_WELCOME_SCREEN_PRIVATE_PROJECT_SUBTITLE');
					iconName = 'ws_private_project';
				}
				else
				{
					upperText = Loc.getMessage('MOBILE_TASKS_LIST_WELCOME_SCREEN_EMPTY_TITLE');
					lowerText = Loc.getMessage('MOBILE_TASKS_LIST_WELCOME_SCREEN_EMPTY_SUBTITLE');
					iconName = 'ws_create_task';
				}

				this.list.list.welcomeScreen.show({ upperText, lowerText, iconName });
			}
		}

		hide()
		{
			this.list.list.welcomeScreen.hide();
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

		showForOpened()
		{
			this.list.list.showJoinButton({ type: 'openProject' });
		}

		showForPrivate(isRequested = false)
		{
			if (isRequested)
			{
				this.list.list.showJoinButton({ state: 'requested' });
			}
			else
			{
				this.list.list.showJoinButton({ type: 'privateProject' });
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
				.catch(() => {})
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
				sortItemParams: { activityDate: 'desc' },
				backgroundColor: AppTheme.colors.bgContentPrimary,
				styles: {
					title:
						{
							font: { size: 18 },
						},
				},
			};

			this.items = {
				pinned: { id: SectionHandler.sections.pinned, ...defaultSectionParams },
				default: { id: SectionHandler.sections.default, ...defaultSectionParams },
				more: { id: SectionHandler.sections.more, ...defaultSectionParams },
				empty: { id: SectionHandler.sections.empty, ...defaultSectionParams },
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
		/**
		 * @param {string} id
		 * @param {TaskList} list
		 * @return {TasksCache}
		 */
		static getInstance(id, list)
		{
			if (!caches.has(id))
			{
				caches.set(id, (new TasksCache(id, list)));
			}

			return caches.get(id);
		}

		/**
		 * @param {string} storageName
		 * @param {TaskList} list
		 */
		constructor(storageName, list)
		{
			super(storageName);

			this.list = list;
		}

		/**
		 * @param {array<Task>} tasks
		 */
		setTaskData(tasks)
		{
			const cachedTasks = this.get();
			if (Object.prototype.hasOwnProperty.call(cachedTasks, Filter.roleType.all))
			{
				return;
			}

			tasks.forEach((task) => {
				if (this.list.isTaskSuitDefaultList(task))
				{
					cachedTasks[task.id] = task.exportProperties();
				}
			});
			this.set(cachedTasks);
		}

		/**
		 * @param {string} taskId
		 */
		removeTask(taskId)
		{
			const cachedTasks = this.get();
			if (Object.prototype.hasOwnProperty.call(cachedTasks, Filter.roleType.all))
			{
				return;
			}

			if (Object.keys(cachedTasks).includes(taskId))
			{
				delete cachedTasks[taskId];
				this.set(cachedTasks);
			}
		}
	}

	class Select
	{
		constructor()
		{
			this.getPinned = true;
		}

		get()
		{
			let fields = [
				'ID',
				'TITLE',
				'DESCRIPTION',
				'STATUS',
				'GROUP_ID',

				'CREATED_BY',
				'RESPONSIBLE_ID',
				'ACCOMPLICES',
				'AUDITORS',

				'DEADLINE',
				'ACTIVITY_DATE',

				'FAVORITE',
				'NOT_VIEWED',
				'IS_MUTED',
				'IS_PINNED',
				'MATCH_WORK_TIME',
				'TIME_SPENT_IN_LOGS',
				'TIME_ESTIMATE',
				'PRIORITY',

				'COUNTERS',
				'COMMENTS_COUNT',
				'SERVICE_COMMENTS_COUNT',
			];

			if (!this.getPinned)
			{
				fields = fields.filter((field) => field !== 'IS_PINNED');
			}

			return fields;
		}

		setGetPinned(getPinned)
		{
			this.getPinned = getPinned;
		}
	}

	class Filter
	{
		static get presetType()
		{
			return {
				none: 'none',
				default: 'filter_tasks_in_progress',
			};
		}

		static get roleType()
		{
			return TaskFilter.roleType;
		}

		static get counterType()
		{
			return TaskFilter.counterType;
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
		 * @param {TaskList} list
		 * @param {Object} currentUser
		 * @param {Object} owner
		 * @param {Integer} groupId
		 */
		constructor(list, currentUser, owner, groupId)
		{
			this.list = list;
			this.currentUser = currentUser;
			this.owner = owner;
			this.groupId = (groupId || 0);

			this.searchText = '';

			this.preset = Filter.presetType.default;
			this.role = BX.componentParameters.get('ROLE', Filter.roleType.all);
			this.counter = BX.componentParameters.get('COUNTER', Filter.counterType.none);
			this.showCompleted = false;

			this.counters = {};
			this.cache = Cache.getInstance(`filterCounters_${this.groupId}`);

			this.setCounterValue(this.cache.get().counterValue || 0);
			this.setVisualCounters();

			EntityReady.wait('chat').then(() => this.updateCounters()).catch(() => {});
		}

		updateCounters()
		{
			if (this.list.isAnotherUserList())
			{
				return;
			}

			Console.log('UPDATE COUNTERS');

			const batchOperations = {};

			Object.keys(Filter.getPresetsMap()).forEach((role) => {
				batchOperations[role] = [
					'tasks.task.counters.get', {
						type: role,
						userId: this.owner.id || this.currentUser.id,
						groupId: this.groupId,
					},
				];
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
			if (this.list.isAnotherUserList() || (task && task.isMuted))
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
				if (Number(this.currentUser.id) !== Number(data.userId) || this.list.isAnotherUserList())
				{
					resolve();

					return;
				}

				const counters = data[this.groupId];

				if (!counters[this.role])
				{
					Console.log({ error: `${this.role} not found in counters`, counters });
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

		setVisualCounters()
		{
			const counterValue = this.getCounterValue();

			this.setDownMenuTasksCounter(counterValue);
			this.setTasksTabCounter(counterValue);
		}

		setDownMenuTasksCounter(value)
		{
			if (this.list.isMyList())
			{
				Application.setBadges({ tasks: value });
			}
		}

		setTasksTabCounter(value)
		{
			if (this.list.isTabsMode)
			{
				if (this.list.isMyList())
				{
					BX.postComponentEvent('tasks.list:setVisualCounter', [{ value }], 'tasks.tabs');
				}
				else if (this.list.isGroupList())
				{
					BX.postComponentEvent('tasks.list:setVisualCounter', [
						{
							value,
							guid: this.list.tabsGuid,
						},
					]);
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
			this.counters.view_all_total = (this.list.isAnotherUserList() ? 0 : value);
		}

		saveCache()
		{
			if (!this.list.isAnotherUserList())
			{
				this.cache.set({ counterValue: this.getCounterValue() });
			}
		}

		get()
		{
			const currentUserId = (this.owner.id || this.currentUser.id);
			const filter = {
				MEMBER: currentUserId,
				ZOMBIE: 'N',
				CHECK_PERMISSIONS: 'Y',
				IS_PINNED: 'N',
			};

			if (this.searchText)
			{
				filter.SEARCH_INDEX = this.searchText;
			}

			if (this.groupId > 0)
			{
				filter.GROUP_ID = this.groupId;
				delete filter.MEMBER;
			}

			delete filter.MEMBER;

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

				case Filter.counterType.none:
				default:
					break;
			}

			return filter;
		}

		isDefaultSearch()
		{
			return (this.searchText === '');
		}

		isDefaultPreset()
		{
			return (this.preset === Filter.presetType.default);
		}

		isDefaultRole()
		{
			return (this.role === Filter.roleType.all);
		}

		isDefaultCounter()
		{
			return (this.counter === Filter.counterType.none);
		}

		getSearchText()
		{
			return this.searchText;
		}

		setSearchText(text)
		{
			this.searchText = text;
		}

		getPreset()
		{
			return this.preset;
		}

		setPreset(preset)
		{
			this.preset = preset;
			this.setRole(this.list.taskFilter.getRoleByPreset(preset) || Filter.roleType.all);
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

	class Order
	{
		static get fields()
		{
			return {
				activityDate: [
					{
						field: 'ACTIVITY_DATE',
						direction: 'DESC',
					},
					{
						field: 'ID',
						direction: 'DESC',
					},
				],
				deadline: [
					{
						field: 'DEADLINE',
						direction: 'ASC,NULLS',
					},
					{
						field: 'ID',
						direction: 'DESC',
					},
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

		changeOrder()
		{
			this.order = Object.keys(Order.fields).find((key) => key !== this.order);
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

		getOrder()
		{
			return this.order;
		}

		setOrder(order)
		{
			this.order = order;
		}
	}

	class Params
	{
		/**
		 * @param {TaskList} list
		 * @param {Filter} filter
		 */
		constructor(list, filter)
		{
			this.list = list;
			this.filter = filter;

			this.getPlusOne = false;
		}

		get()
		{
			const params = {
				MODE: 'mobile',
				RETURN_ACCESS: 'Y',
				RETURN_USER_INFO: 'Y',
				RETURN_GROUP_INFO: 'Y',
				WITH_RESULT_INFO: 'Y',
				WITH_TIMER_INFO: 'Y',
				WITH_PARSED_DESCRIPTION: 'Y',
			};

			if (this.getPlusOne)
			{
				params.GET_PLUS_ONE = 'Y';
			}

			params.SIFT_THROUGH_FILTER = {
				userId: this.list.owner.id,
				groupId: this.list.groupId,
				presetId: this.filter.getPreset(),
			};

			return params;
		}

		setGetPlusOne(getPlusOne)
		{
			this.getPlusOne = getPlusOne;
		}
	}

	class MoreMenu
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
			const menuSections = [{ id: SectionHandler.sections.default }];

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

		prepareItems()
		{
			const urlPrefix = `${component.path}images/mobile-tasks-list-popup-`;
			let menuItems = [
				{
					id: Filter.counterType.expired,
					title: Loc.getMessage('TASKS_POPUP_MENU_COUNTER_EXPIRED'),
					iconUrl: `${urlPrefix}expired.png`,
					sectionCode: SectionHandler.sections.default,
					checked: (this.filter.getCounter() === Filter.counterType.expired),
					counterValue: this.filter.getCounterValue(this.filter.getRole(), Filter.counterType.expired),
					counterStyle: {
						backgroundColor: AppTheme.colors.accentMainAlert,
					},
				},
				{
					id: Filter.counterType.newComments,
					title: Loc.getMessage('TASKS_POPUP_MENU_COUNTER_NEW_COMMENTS'),
					iconUrl: `${urlPrefix}new-comments.png`,
					sectionCode: SectionHandler.sections.default,
					checked: (this.filter.getCounter() === Filter.counterType.newComments),
					counterValue: this.filter.getCounterValue(this.filter.getRole(), Filter.counterType.newComments),
					counterStyle: {
						backgroundColor: AppTheme.colors.accentMainSuccess,
					},
					showTopSeparator: true,
				},
				{
					id: 'toggleOrder',
					title: Loc.getMessage(`TASKS_POPUP_MENU_ORDER_${this.order.isDeadline() ? 'ACTIVITY_MSGVER_1' : 'DEADLINE'}`),
					iconUrl: `${urlPrefix}order.png`,
					sectionCode: SectionHandler.sections.default,
					showTopSeparator: true,
				},
				{
					id: 'readAll',
					title: Loc.getMessage('TASKS_POPUP_MENU_READ_ALL'),
					iconUrl: `${urlPrefix}read-all.png`,
					sectionCode: SectionHandler.sections.default,
					showTopSeparator: true,
				},
			];
			menuItems = menuItems.filter((item) => item.id !== 'toggleCompletedTasks');

			return menuItems;
		}

		onItemSelected(item)
		{
			switch (item.id)
			{
				case Filter.counterType.expired:
				case Filter.counterType.newComments:
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

				default:
					break;
			}
		}

		onCounterClick(counter)
		{
			const newCounter = (this.filter.getCounter() === counter ? Filter.counterType.none : counter);

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
			readAllFunction
				.call()
				.then((response) => {
					if (response.result)
					{
						Notify.showIndicatorSuccess({
							text: Loc.getMessage('TASKS_LIST_READ_ALL_NOTIFICATION'),
							hideAfter: 1500,
						});
					}
				})
				.catch(() => {})
			;
		}

		getReadAllForProject()
		{
			const options = {
				groupId: this.list.groupId,
			};

			return (new RequestExecutor('tasks.viewedGroup.project.markAsRead', { fields: options }));
		}

		getReadAllByRole()
		{
			const options = {
				groupId: (this.list.groupId || null),
				userId: (this.list.owner.id || this.list.currentUser.id),
				role: this.filter.getRole(),
			};

			return (new RequestExecutor('tasks.viewedGroup.user.markAsRead', { fields: options }));
		}
	}

	class Options
	{
		static getDefaultOptions()
		{
			const now = new Date();
			now.setDate(now.getDate() - 1);

			return {
				swipeShowHelper: {
					value: 0,
					limit: 2,
				},
				deadlines: {
					lastTime: now.getTime(),
					value: result.deadlines,
				},
			};
		}

		constructor()
		{
			this.cache = Cache.getInstance('options');
			this.cache.setDefaultData(Options.getDefaultOptions());
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
				task_timer_start: {
					method: this.list.onTaskTimerStart,
					context: this.list,
				},
				task_timer_stop: {
					method: this.list.onTaskTimerStop,
					context: this.list,
				},
			};
		}

		subscribe()
		{
			BX.PULL.subscribe({
				moduleId: 'tasks',
				callback: (data) => this.processPullEvent(data),
			});
		}

		clear()
		{
			this.queue.clear();
		}

		freeQueue()
		{
			const commonCommands = new Set([
				'comment_read_all',
				'project_read_all',
				'user_counter',
			]);
			this.queue = new Set([...this.queue].filter((event) => commonCommands.has(event.command)));

			const clearDuplicates = (accumulator, event) => {
				const result = accumulator;
				if (
					typeof result[event.command] === 'undefined'
					|| event.extra.server_time_ago < result[event.command].extra.server_time_ago
				)
				{
					result[event.command] = event;
				}

				return result;
			};
			this.queue = new Set(
				Object.values([...this.queue].reduce((accumulator, event) => clearDuplicates(accumulator, event), {})),
			);

			const promises = [...this.queue].map((event) => this.executePullEvent(event));

			return Promise.allSettled(promises);
		}

		processTaskEvents()
		{
			const processedTasks = new Map();

			this.queue.forEach((event) => {
				const has = Object.prototype.hasOwnProperty;
				const eventHandlers = this.getEventHandlers();
				const { command, params } = event;

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
				const { command, params } = data;
				if (has.call(eventHandlers, command))
				{
					const { method, context } = eventHandlers[command];
					if (method)
					{
						method.apply(context, [params]).then(() => resolve(), () => reject()).catch(() => reject());
					}
				}
			});
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
		static convertFields(fields)
		{
			let convertedFields = fields;

			convertedFields = Push.convertKeys(convertedFields);
			convertedFields = Push.convertValues(convertedFields);

			return convertedFields;
		}

		static convertKeys(fields)
		{
			// see \CTaskNotifications::preparePushData
			const map = {
				1: 'id',
				2: 'title',
				3: 'deadline',
				4: 'activityDate',
				5: 'status',

				20: 'groupId',
				21: 'group',
				22: 'image',
				23: 'name',

				30: 'creator',
				31: 'responsible',
				32: 'icon',

				41: 'accomplices',
				42: 'auditors',

				50: 'counter',
				51: 'counters',
				52: 'color',
				53: 'value',
				54: 'expired',
				55: 'newComments',
				56: 'projectExpired',
				57: 'projectNewComments',
			};
			const convertRecursively = (object) => {
				if (!object || typeof object !== 'object')
				{
					return object;
				}

				if (Array.isArray(object))
				{
					return object.map((element) => convertRecursively(element));
				}

				const result = {};
				Object.entries(object).forEach(([key, value]) => {
					const newKey = (map[key] || key);
					result[newKey] = convertRecursively(value);
				});

				return result;
			};

			return convertRecursively(fields);
		}

		static convertValues(fields)
		{
			const convertedFields = fields;

			convertedFields.accomplicesData = {};
			convertedFields.auditorsData = {};

			if (convertedFields.accomplices)
			{
				convertedFields.accomplices.forEach((userId) => {
					convertedFields.accomplicesData[userId] = { id: userId };
				});
			}

			if (convertedFields.auditors)
			{
				convertedFields.auditors.forEach((userId) => {
					convertedFields.auditorsData[userId] = { id: userId };
				});
			}

			delete convertedFields.accomplices;
			delete convertedFields.auditors;

			return convertedFields;
		}

		/**
		 * @param {TaskList} list
		 */
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
				/TASKS\|([A-Z_]+)\|(\d+)\|(\d+)\|([A-Z_]+)/,
				/TASKS\|([A-Z_]+)\|(\d+)\|(\d+)\|(\d+)\|(.+)/,
			];
			Object.entries(data).forEach(([key, value]) => {
				for (const reg of regs)
				{
					const matches = key.match(reg);
					if (matches)
					{
						const [, entityType, taskId] = matches;
						let action = null;

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
							this.addTask(Push.convertFields(fields.data));
						}
						break;

					case 'TASK_UPDATE':
					case 'TASK_STATUS_CHANGED_MESSAGE':
					case 'TASK_EXPIRED_SOON':
					case 'TASK_EXPIRED':
					case 'TASK_PINGED_STATUS':
					{
						const taskData = Push.convertFields(fields.data);

						if (this.list.taskList.has(taskId))
						{
							this.updateTask(taskId, taskData);
						}
						else
						{
							this.addTask(taskData);
						}
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

			if (this.list.isTaskSuitCurrentList(task))
			{
				this.list.addItem(task);
				this.processedTasks.set(Number(task.id));
			}
		}

		updateTask(taskId, taskData)
		{
			const task = this.list.taskList.get(taskId);
			task.updateData(taskData);

			if (this.list.isTaskSuitCurrentList(task))
			{
				this.list.updateItem(taskId, task);
				this.processedTasks.set(Number(taskId));
			}
		}

		removeTask(taskId)
		{
			this.list.removeItem(taskId);
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

		static get titles()
		{
			return {
				[Filter.roleType.all]: Loc.getMessage('TASKS_LIST_HEADER_ROLE_ALL_V2'),
				[Filter.roleType.responsible]: Loc.getMessage('TASKS_LIST_HEADER_ROLE_RESPONSIBLE'),
				[Filter.roleType.accomplice]: Loc.getMessage('TASKS_LIST_HEADER_ROLE_ACCOMPLICE'),
				[Filter.roleType.originator]: Loc.getMessage('TASKS_LIST_HEADER_ROLE_ORIGINATOR'),
				[Filter.roleType.auditor]: Loc.getMessage('TASKS_LIST_HEADER_ROLE_AUDITOR'),
				deadlines: Loc.getMessage('TASKS_LIST_HEADER_DEADLINES'),
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

		static showSpotlights()
		{
			const searchButtonSpotlight = new Spotlight({
				target: 'tasksTaskListSearchButton',
				text: Loc.getMessage('TASKS_LIST_SEARCH_SPOTLIGHT_TEXT'),
			});
			searchButtonSpotlight.show();
		}

		constructor(list, owner, params)
		{
			Console.log('taskList.constructor');

			this.checkIsNewDashboardActive();
			this.initCommon(list, owner, params);

			if (this.group.id && this.group.getData)
			{
				this.fillProjectData()
					.then(() => {
						if (!this.group.id || this.group.isOpened || this.isMember())
						{
							this.initAccessibleList(params);
						}
						else
						{
							this.initInaccessibleList();
						}
					})
					.catch(() => {})
				;
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

		checkIsNewDashboardActive()
		{
			BX.ajax.runAction('tasksmobile.Settings.isNewDashboardActive').then((result) => {
				if (result.data)
				{
					Application.clearCache();
					Application.storageById('tasksmobile').setObject('settings', { isNewDashboardActive: true });
					Application.relogin();
				}
			});
		}

		initCommon(list, owner, params)
		{
			this.list = list;
			this.owner = { id: owner };
			this.currentUser = result.settings.currentUser;
			this.isTabsMode = params.isTabsMode;
			this.projectNewsPathTemplate = (params.projectNewsPathTemplate || '');
			this.projectCalendarWebPathTemplate = (params.projectCalendarWebPathTemplate || '');

			this.taskList = new Map();
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

			this.welcomeScreen = new WelcomeScreen(this);
			this.joinButton = new JoinButton(this);

			BX.onViewLoaded(() => {
				this.setListListeners();
			});
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
							const { result } = response;
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
					.catch(() => reject())
				;
			});
		}

		initAccessibleList()
		{
			this.tabsGuid = BX.componentParameters.get('TABS_GUID', '');

			this.start = 0;
			this.pageSize = 50;
			this.canShowSwipeActions = true;

			this.select = new Select();
			this.filter = new Filter(this, this.currentUser, this.owner, this.groupId);
			this.order = new Order();
			this.params = new Params(this, this.filter);
			this.options = new Options();
			this.moreMenu = new MoreMenu(this);
			this.loading = new Loading(this);

			this.pull = new Pull(this);
			this.push = new Push(this);
			this.batchRequestsExecutor = new BatchRequestsExecutor();

			this.cache = TasksCache.getInstance(`tasksList_${this.owner.id}_${this.groupId}`, this);

			this.debounceSearch = debounce(
				(text) => {
					this.filter.setSearchText(text);
					this.setTopButtons();
					this.reload(0, true);
				},
				500,
				this,
			);

			this.taskFilter = new TaskFilter();

			this.taskFilter.fillPresets(this.groupId)
				.then(() => {
					if (this.searchLayout)
					{
						this.searchLayout.updateState({
							presets: this.taskFilter.getPresets(),
							currentPreset: this.filter.getPreset(),
						});
					}
				})
				.catch(() => {})
			;

			this.getTaskLimitExceeded();
			this.checkDeadlinesUpdate();
			this.getOwnerData();

			BX.addCustomEvent('onPullEvent-tasks', (command, params) => {
				if (command === 'user_counter')
				{
					this.filter.onUserCounter.apply(this.filter, [params]);
				}
			});

			BX.onViewLoaded(() => {
				this.updateSections(true);

				this.list.setItems([
					{
						type: 'loading',
						title: Loc.getMessage('TASKS_LIST_BUTTON_NEXT_PROCESS'),
					},
				]);

				this.setTopButtons();
				this.setFloatingButton();
				this.setJoinButton();
				this.bindEvents();

				if (platform === 'ios')
				{
					TaskList.showSpotlights();
				}

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
						this.reload();
						this.filter.updateCounters();
					},
					context: this,
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
				onScroll: {
					callback: () => {
						this.list.search.close();
					},
					context: this,
				},
			};

			if (platform !== 'ios')
			{
				eventHandlers.onViewShown = {
					callback: TaskList.showSpotlights,
					context: this,
				};
			}

			this.list.setListener((event, data) => {
				Console.log(`Fire event: app.${event}`);
				if (eventHandlers[event])
				{
					eventHandlers[event].callback.apply(eventHandlers[event].context, [data]);
				}
			});
		}

		bindEvents()
		{
			BX.addCustomEvent('task.view.onCommentsRead', (eventData) => this.onCommentsRead(eventData));
			BX.addCustomEvent('onTabsSelected', (tabName) => this.onTabsSelected(tabName));

			if (this.isTabsMode && this.isMyList())
			{
				BX.addCustomEvent('tasks.tabs:onTabSelected', (eventData) => this.onTabSelected(eventData));
				BX.addCustomEvent('tasks.tabs:onAppActiveBefore', (eventData) => this.onAppActiveBefore(eventData));
				BX.addCustomEvent('tasks.tabs:onAppActive', (eventData) => this.onAppActive(eventData));
				BX.addCustomEvent('tasks.tabs:onAppPaused', (eventData) => this.onAppPaused(eventData));
			}
			else
			{
				BX.addCustomEvent('onAppActiveBefore', () => this.onAppActiveBefore());
				BX.addCustomEvent('onAppActive', () => this.onAppActive());
				BX.addCustomEvent('onAppPaused', () => this.onAppPaused());
			}

			this.pull.subscribe();
		}

		setTopButtons()
		{
			const searchBackground = (!this.filter.isDefaultPreset() || !this.filter.isDefaultSearch() ? AppTheme.colors.accentBrandBlue : null);
			const moreBackground = (this.filter.isDefaultCounter() ? null : AppTheme.colors.accentBrandBlue);
			const moreDot = (this.filter.getCounterValue() > 0 ? AppTheme.colors.accentMainAlert : null);

			this.list.setRightButtons([
				{
					type: 'search',
					badgeCode: 'tasksTaskListSearchButton',
					svg: {
						content: magnifierWithMenuAndDot(AppTheme.colors.base4, searchBackground),
					},
					callback: () => this.onSearchClick(),
				},
				{
					badgeCode: `tasksTaskListMoreButton_${this.owner.id}_${this.groupId}`,
					svg: {
						content: moreWithDot(AppTheme.colors.base4, moreBackground, moreDot),
					},
					callback: () => this.moreMenu.show(),
				},
			]);
			this.filter.setVisualCounters();
		}

		onSearchClick()
		{
			if (!this.isSearchInit)
			{
				this.isSearchInit = true;

				this.list.search.mode = 'layout';
				this.list.search.on('textChanged', ({ text }) => this.debounceSearch(text));
				this.list.search.on('cancel', () => {
					if (!this.filter.isDefaultPreset() || !this.filter.isDefaultSearch())
					{
						this.filter.setSearchText('');
						this.filter.setPreset(Filter.presetType.default);

						this.setTopButtons();
						this.reload(0, true);
					}
				});
			}
			this.searchLayout = new PresetList({
				presets: this.taskFilter.getPresets(),
				currentPreset: this.filter.getPreset(),
			});
			this.searchLayout.on('presetSelected', (preset) => {
				if (preset.id === this.filter.getPreset())
				{
					this.filter.setPreset(Filter.presetType.none);
				}
				else
				{
					this.filter.setPreset(preset.id);
					this.filter.setRole(Filter.roleType.all);
					this.filter.setCounter(Filter.counterType.none);
				}
				this.setTopButtons();
				this.reload(0, true);
			});
			this.list.search.text = this.filter.getSearchText();
			this.list.search.show(this.searchLayout, 46);
		}

		setFloatingButton()
		{
			this.getCanCreateTask()
				.then(
					(response) => this.renderFloatingButton(response.result),
					(response) => Console.error(response),
				)
				.catch((response) => Console.log(response))
			;
		}

		getCanCreateTask()
		{
			return new Promise((resolve, reject) => {
				if (!this.groupId)
				{
					resolve({ result: true });

					return;
				}

				(new RequestExecutor('mobile.tasks.group.getCanCreateTask', {
					groupId: this.groupId,
				}))
					.call()
					.then(
						(response) => resolve(response),
						(response) => reject(response),
					)
					.catch((response) => reject(response))
				;
			});
		}

		renderFloatingButton(isExist = false)
		{
			if (isExist)
			{
				this.list.setFloatingButton({
					icon: 'plus',
					callback: () => {
						const taskCreateParameters = {
							currentUser: this.currentUser,
							diskFolderId: result.diskFolderId,
							deadlines: this.options.get().deadlines.value,
							initialTaskData: {
								responsible: this.owner,
							},
						};
						if (this.groupId > 0)
						{
							taskCreateParameters.initialTaskData.groupId = this.groupId;
							taskCreateParameters.initialTaskData.group = {
								id: this.group.id,
								name: this.group.name,
								image: this.group.imageUrl,
							};
						}
						TaskCreate.open(taskCreateParameters);
					},
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
			Console.log('loadTasksFromCache');

			BX.onViewLoaded(() => {
				const tasks = this.cache.get();
				if (Object.keys(tasks).length === 0)
				{
					Console.log('Cache is empty');

					return;
				}

				if (Object.prototype.hasOwnProperty.call(tasks, Filter.roleType.all)) // old cache
				{
					return;
				}

				const items = Object.values(tasks).map((taskData) => {
					const task = new Task(this.currentUser);
					task.importProperties(taskData);
					this.taskList.set(task.id, task);

					return {
						...this.getItemDataFromTask(task),
						checkable: false,
					};
				});
				this.list.setItems(items, null, false);
			});
		}

		reload(taskListOffset = 0, showLoading = false)
		{
			BX.onViewLoaded(() => {
				Console.log('reload');

				if (showLoading)
				{
					this.loading.show();
				}
				this.updateTitle(true);

				this.select.setGetPinned(this.isMyList());
				this.params.setGetPlusOne(true);

				const batchOperations = {
					all: [
						'tasks.task.list', {
							select: this.select.get(),
							filter: this.filter.get(),
							order: this.order.get(),
							params: this.params.get(),
							start: taskListOffset,
						},
					],
				};

				if (!taskListOffset && this.isMyList())
				{
					batchOperations.pinned = [
						'tasks.task.list', {
							select: this.select.get(),
							filter: {
								...this.filter.get(),
								IS_PINNED: 'Y',
							},
							order: this.order.get(),
							params: this.params.get(),
						},
					];
				}

				this.batchRequestsExecutor.execute(batchOperations)
					.then((result) => this.onReloadSuccess(result, showLoading, taskListOffset))
					.catch((error) => this.onReloadFail(error, showLoading))
				;
			});
		}

		onReloadFail(error, showLoading)
		{
			Console.log('onReloadFail', error);

			if (showLoading)
			{
				this.loading.hide();
			}
			this.list.stopRefreshing();
		}

		onReloadSuccess(response, showLoading, taskListOffset)
		{
			Console.log('onReloadSuccess', response);

			const { all, pinned } = response;
			const tasks = (all ? all.answer.result.tasks : []) || [];
			const isNextPageExist = tasks.length > this.pageSize;
			if (isNextPageExist)
			{
				tasks.splice(this.pageSize, 1);
			}
			const pinnedTasks = (pinned ? pinned.answer.result.tasks : []) || [];
			const allTasks = [...pinnedTasks, ...tasks];
			const isFirstPage = (taskListOffset === 0);

			this.start = taskListOffset + this.pageSize;

			if (isFirstPage)
			{
				this.comments.clear();
				this.taskList.clear();
			}

			const items = [];
			allTasks.forEach((row) => {
				const task = new Task(this.currentUser);
				task.setData(row);
				const item = this.getItemDataFromTask(task);
				items.push(item);
				this.taskList.set(task.id, task);
			});

			this.fillCache(isFirstPage);
			this.updateSections(isFirstPage);
			this.renderTaskListItems(items, isFirstPage, isNextPageExist);

			if (showLoading)
			{
				this.loading.hide();
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
				[this.order.getOrder()]: Order.sectionOrderFields[this.order.getOrder()],
			});
			sectionHandler.setSortItemParams(SectionHandler.sections.pinned, {
				[this.order.getOrder()]: Order.sectionOrderFields[this.order.getOrder()],
			});

			this.list.setSections(sectionHandler.list);
		}

		fillCache(isFirstPage)
		{
			if (
				isFirstPage
				&& this.filter.isDefaultPreset()
				&& this.filter.isDefaultRole()
				&& this.filter.isDefaultCounter()
				&& this.order.isActivityDate()
			)
			{
				const tasks = {};
				this.taskList.forEach((task, taskId) => {
					tasks[taskId] = task.exportProperties();
				});
				this.cache.set(tasks);

				Console.log('Cache filled with:', tasks);
			}
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
				this.list.setItems([], null, false);

				return;
			}

			if (isFirstPage)
			{
				this.welcomeScreen.hide();

				const itemsWithHandledActions = this.handleSwipeActionsShow(items);
				this.list.setItems(itemsWithHandledActions, null, true);
				setTimeout(() => {
					this.list.updateItem({ id: itemsWithHandledActions[0].id }, { showSwipeActions: false });
				}, 300);
			}
			else
			{
				this.list.removeItem({ id: '-more-' });
				this.list.addItems(items);
			}

			if (isNextPageExist)
			{
				this.list.addItems([
					{
						id: '-more-',
						title: Loc.getMessage('TASKS_LIST_BUTTON_NEXT'),
						type: 'button',
						sectionCode: SectionHandler.sections.more,
					},
				]);
			}
		}

		handleSwipeActionsShow(items)
		{
			let { swipeShowHelper } = this.options.get();
			if (!swipeShowHelper)
			{
				swipeShowHelper = Options.getDefaultOptions().swipeShowHelper;
				this.options.update('swipeShowHelper', swipeShowHelper);
			}

			const handledItems = items;

			if ((swipeShowHelper.value < swipeShowHelper.limit) && this.canShowSwipeActions)
			{
				this.canShowSwipeActions = false;

				handledItems[0].showSwipeActions = true;
				swipeShowHelper.value += 1;

				this.options.update('swipeShowHelper', swipeShowHelper);
			}

			return handledItems;
		}

		onTabSelected(data)
		{
			if (data.tabId === 'tasks.list')
			{
				this.onAppActiveBefore(data);
				this.onAppActive(data);
			}
			else
			{
				this.onAppPaused(data, true);
			}
		}

		onAppPaused(data, force = false)
		{
			if (!force && data.tabId !== 'tasks.list')
			{
				return;
			}

			this.pauseTime = new Date();

			this.pull.setCanExecute(false);
			this.pull.clear();
			this.push.clear();
		}

		onAppActiveBefore(data)
		{
			if (data.tabId !== 'tasks.list')
			{
				return;
			}

			BX.onViewLoaded(() => {
				if (this.push)
				{
					this.push.updateList();
				}
			});
		}

		onAppActive(data)
		{
			if (data.tabId !== 'tasks.list')
			{
				return;
			}

			this.checkIsNewDashboardActive();

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
					...this.push.processedTasks,
				]);
				const taskIds = [...tasksToUpdate.keys()].map((taskId) => taskId.toString());

				if (taskIds.length > 30)
				{
					this.runOnAppActiveRepeatedActions();
				}
				else
				{
					const promises = [
						new Promise((resolve) => {
							this.pull.setCanExecute(true);
							this.pull.freeQueue().then(() => resolve()).catch(() => {});
						}),
					];
					if (taskIds.length > 0)
					{
						promises.push(this.updateTasks(taskIds));
					}
					Promise.allSettled(promises).then(() => this.updateTitle()).catch(() => {});
				}
			}, 1000);
		}

		updateTasks(taskIds)
		{
			return new Promise((resolve, reject) => {
				const { IS_PINNED, ...filter } = { ...this.filter.get(), ID: taskIds };

				this.select.setGetPinned(true);
				this.params.setGetPlusOne(false);

				(new RequestExecutor('tasks.task.list', {
					select: this.select.get(),
					filter,
					params: this.params.get(),
				}))
					.call()
					.then(
						(response) => {
							this.onUpdateTasksSuccess(taskIds, response.result.tasks);
							resolve();
						},
						(response) => {
							Console.error(response);
							reject();
						},
					)
					.catch((response) => {
						Console.error(response);
						reject();
					})
				;
			});
		}

		onUpdateTasksSuccess(taskIds, tasks)
		{
			taskIds.forEach((taskId) => {
				const taskData = tasks.find((task) => Number(task.id) === Number(taskId));
				if (taskData)
				{
					if (this.taskList.has(taskId))
					{
						const task = this.taskList.get(taskId);
						task.setData(taskData);
						this.updateItem(taskId, task);
					}
					else
					{
						const task = new Task(this.currentUser);
						task.setData(taskData);
						this.addItem(task);
					}
				}
				else if (this.taskList.has(taskId))
				{
					this.removeItem(taskId);
				}
			});
		}

		isTaskSuitDefaultList(task)
		{
			return this.isTaskSuitList(
				Filter.presetType.default,
				Filter.roleType.all,
				Filter.counterType.none,
				'',
				this.groupId,
				task,
			);
		}

		isTaskSuitCurrentList(task)
		{
			return this.isTaskSuitList(
				this.filter.getPreset(),
				this.filter.getRole(),
				this.filter.getCounter(),
				this.filter.getSearchText(),
				this.groupId,
				task,
			);
		}

		/**
		 * @param {string} preset
		 * @param {string} role
		 * @param {string} counter
		 * @param {string} searchText
		 * @param {integer} groupId
		 * @param {Task} task
		 * @return {boolean}
		 */
		isTaskSuitList(preset, role, counter, searchText, groupId, task)
		{
			return (
				this.taskFilter.isTaskSuitPreset(preset, task)
				&& this.taskFilter.isTaskSuitRoleCounter(role, counter, task)
				&& this.taskFilter.isTaskSuitSearch(searchText, task)
				&& this.taskFilter.isTaskSuitGroup(groupId, task)
			);
		}

		runOnAppActiveRepeatedActions()
		{
			this.filter.updateCounters();
			this.reload();

			this.setFloatingButton();

			this.pull.setCanExecute(true);
		}

		getTaskLimitExceeded()
		{
			(new RequestExecutor('tasks.task.limit.isExceeded'))
				.call()
				.then((response) => {
					Console.log('taskList:limit.isExceeded', response.result);
					this.taskLimitExceeded = response.result || false;
				})
				.catch(() => {})
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
					.catch(() => {})
				;
			}
		}

		getOwnerData()
		{
			(new RequestExecutor('tasksmobile.User.getUsersData', { userIds: [this.owner.id] }))
				.call()
				.then((response) => {
					this.owner = response.result[this.owner.id];
				})
				.catch(() => {})
			;
		}

		onTabsSelected(tabName)
		{
			if (
				tabName !== 'tasks'
				&& (
					this.order.isDeadline()
					|| !this.filter.isDefaultPreset()
					|| !this.filter.isDefaultRole()
					|| !this.filter.isDefaultCounter()
				)
			)
			{
				setTimeout(() => {
					this.filter.setPreset(Filter.presetType.default);
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
			Console.log('task.view.onCommentsRead', eventData);

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
					this.updateTasks([data.TASK_ID.toString()])
						.then(() => resolve(), () => reject())
						.catch(() => reject())
					;
				}
			});
		}

		// eslint-disable-next-line sonarjs/no-identical-functions
		onPullView(data)
		{
			return new Promise((resolve, reject) => {
				if (Number(data.USER_ID) === Number(this.currentUser.id))
				{
					this.updateTasks([data.TASK_ID.toString()])
						.then(() => resolve(), () => reject())
						.catch(() => reject())
					;
				}
			});
		}

		onPullComment(data)
		{
			return new Promise((resolve, reject) => {
				Console.log('onPullComment', data);

				const [entityType, entityId] = data.entityXmlId.split('_');
				const { messageId } = data;

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

				this.updateTasks([entityId])
					.then(() => resolve(), () => reject())
					.catch(() => reject())
				;
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
				Console.log('onPullAdd');

				if (data.params.addCommentExists !== false)
				{
					Console.log('onPullAdd -> addCommentExists');
					resolve();

					return;
				}

				this.updateTasks([data.TASK_ID.toString()])
					.then(() => resolve(), () => reject())
					.catch(() => reject())
				;
			});
		}

		onPullUpdate(data)
		{
			return new Promise((resolve, reject) => {
				Console.log('onPullUpdate', data);

				if (data.params.updateCommentExists !== false)
				{
					Console.log('onPullUpdate -> updateCommentExists');
					resolve();

					return;
				}

				this.updateTasks([data.TASK_ID.toString()])
					.then(() => resolve(), () => reject())
					.catch(() => reject())
				;
			});
		}

		onPullDelete(data)
		{
			return new Promise((resolve) => {
				Console.log('onPullDelete');
				this.removeItem(data.TASK_ID.toString());
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
						taskHasOpenResult: data.taskHasOpenResult,
						taskHasResult: data.taskHasResult,
					});
				}
				resolve();
			});
		}

		onTaskTimerStart(data)
		{
			return new Promise((resolve) => {
				const taskId = String(data.taskId);
				if (this.taskList.has(taskId))
				{
					const task = this.taskList.get(taskId);
					task.updateData({
						timerIsRunningForCurrentUser: 'Y',
						timeElapsed: data.timeElapsed,
					});
					task.updateActions({
						canStartTimer: false,
						canPauseTimer: true,
					});
				}
				resolve();
			});
		}

		onTaskTimerStop(data)
		{
			return new Promise((resolve) => {
				const taskId = String(data.taskId);
				if (this.taskList.has(taskId))
				{
					const task = this.taskList.get(taskId);
					if (Number(data.userId) === Number(task.currentUser.id))
					{
						task.updateData({ timerIsRunningForCurrentUser: 'N' });
						task.updateActions({
							canStartTimer: true,
							canPauseTimer: false,
						});
					}
					task.updateData({ timeElapsed: data.timeElapsed[task.currentUser.id] });
				}
				resolve();
			});
		}

		/**
		 * @param {Task} task
		 * @param {boolean} withActions
		 * @return {object}
		 */
		getItemDataFromTask(task, withActions = true)
		{
			let itemData = task.getTaskInfo(withActions);

			itemData.backgroundColor = Task.backgroundColors.default;
			itemData.sectionCode = SectionHandler.sections.default;
			itemData.type = 'task';
			itemData.sortValues = {
				deadline: task.deadline || 9_999_999_999_999,
				activityDate: task.activityDate,
			};
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
			const handledItemData = itemData;
			let { actions } = itemData;

			if (!this.isMyList())
			{
				actions = actions.filter((action) => !['pin', 'unpin'].includes(action.identifier));
			}

			if (this.taskLimitExceeded)
			{
				actions = actions.filter((action) => action.identifier !== 'delegate');
			}

			if (platform === 'ios')
			{
				const leftSwipeActions = actions.filter((action) => action.position === 'left');

				if (actions.length > 4 + leftSwipeActions.length)
				{
					const swipeActions = [...leftSwipeActions, Task.actions.more];
					const popupActions = [];

					actions.filter((action) => !leftSwipeActions.includes(action)).forEach((action) => {
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
					handledItemData.params.popupActions = popupActions;
				}
			}
			else
			{
				actions = actions.map((action) => ({
					...action,
					iconUrl: Task.popupImageUrls[action.identifier],
				}));
				handledItemData.menuMode = 'dialog';
			}

			handledItemData.actions = actions;

			return handledItemData;
		}

		addItem(task)
		{
			BX.onViewLoaded(() => {
				const taskId = task.id;
				if (this.taskList.has(taskId))
				{
					return;
				}

				this.welcomeScreen.hide();

				const taskData = this.getItemDataFromTask(task);

				this.taskList.set(taskId, task);
				this.list.addItems([taskData]);
				this.list.blinkItems([taskId], Task.backgroundColors.blinking);
				this.cache.setTaskData([task]);
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

				Console.log(`updateItem #${id}`, taskData);
				this.list.updateItem({ id }, taskData);
				this.cache.setTaskData([task]);
			});
		}

		removeItem(id)
		{
			BX.onViewLoaded(() => {
				this.taskList.delete(id);
				this.list.removeItem({ id });
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
				item,
				newsPathTemplate: this.projectNewsPathTemplate,
				calendarWebPathTemplate: this.projectCalendarWebPathTemplate,
				currentUserId: parseInt(this.owner.id, 10),
			};

			BX.postComponentEvent('projectbackground::project::action', [projectData], 'background');
		}

		onItemSelected(item)
		{
			const taskId = item.id.toString();

			if (taskId === '-more-')
			{
				this.list.updateItem(
					{ id: '-more-' },
					{
						type: 'loading',
						title: Loc.getMessage('TASKS_LIST_BUTTON_NEXT_PROCESS'),
					},
				);
				this.reload(this.start);
			}
			else if (this.taskList.has(taskId))
			{
				this.taskList.get(taskId).open();
			}
		}

		onItemChecked(item)
		{
			const task = this.taskList.get(item.id.toString());

			if (task.isCompletedCounts)
			{
				this.updateItem(task.id, {
					status: TaskStatus.PENDING,
					activityDate: Date.now(),
				});
				void task.renew();
			}
			else if (task.isSupposedlyCompletedCounts)
			{
				this.onApproveAction(task);
			}
			else if (!task.isResultRequired || task.isOpenResultExists)
			{
				this.updateItem(task.id, {
					status: TaskStatus.COMPLETED,
					activityDate: Date.now(),
				});
				void task.complete();
			}
			else
			{
				task.complete().then(() => {}, () => this.updateItem(task.id)).catch(() => {});
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

				case 'startTimer':
					this.onStartTimerAction(task);
					break;

				case 'pauseTimer':
					this.onPauseTimerAction(task);
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

				case 'share':
					this.onShareAction(task);
					break;

				default:
					break;
			}

			this.sendActionAnalytics(event.action.identifier, task);
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
				[{ id: SectionHandler.sections.default }],
				(eventName, item) => {
					if (eventName === 'onItemSelected')
					{
						this.onActionsPopupItemSelected(item, task);
					}
				},
			);
			this.popupMenu.setPosition('center');
			this.popupMenu.show();
		}

		/**
		 * @param {string} action
		 * @param {Task} task
		 */
		sendActionAnalytics(action, task)
		{
			analytics.send('tasks_list_action', {
				action,
				canUpdateTask: task.actions.edit,
				canChangeDeadline: task.actions.changeDeadline,
			});
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

				case 'startTimer':
					this.onStartTimerAction(task);
					break;

				case 'pauseTimer':
					this.onPauseTimerAction(task);
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

				case 'share':
					this.onShareAction(task);
					break;

				case 'cancel':
					return;

				default:
					break;
			}

			this.sendActionAnalytics(item.id, task);
			this.updateItem(task.id, {});
		}

		/**
		 * @param {Task} task
		 */
		onPingAction(task)
		{
			void task.ping();
			this.updateItem(task.id, { activityDate: Date.now() });

			Notify.showIndicatorSuccess({
				text: Loc.getMessage('TASKS_LIST_PING_NOTIFICATION'),
				hideAfter: 1500,
			});
		}

		/**
		 * @param {Task} task
		 */
		onChangeDeadlineAction(task)
		{
			const pickerParams = {
				title: Loc.getMessage('TASKS_LIST_POPUP_SELECT_DATE'),
				type: 'datetime',
				value: task.deadline,
				items: [],
			};
			Object.keys(Task.deadlines).forEach((key) => {
				const { deadlines } = this.options.get();
				pickerParams.items.push({
					name: Task.deadlines[key].name,
					value: deadlines.value[key] * 1000,
				});
			});

			dialogs.showDatePicker(pickerParams, (eventName, ts) => {
				if (ts > 0 && ts !== task.deadline)
				{
					this.updateItem(task.id, { deadline: ts, activityDate: Date.now() });
					void task.saveDeadline();
				}
			});
		}

		/**
		 * @param {Task} task
		 */
		onApproveAction(task)
		{
			if (task.status === TaskStatus.SUPPOSEDLY_COMPLETED)
			{
				task.updateActions({
					canApprove: false,
					canDisapprove: false,
					canComplete: false,
					canRenew: true,
				});
				task.approve().then(() => this.updateItem(task.id, { status: task.status })).catch(() => {});
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
			if (task.status === TaskStatus.SUPPOSEDLY_COMPLETED)
			{
				task.updateActions({
					canApprove: false,
					canDisapprove: false,
					canRenew: false,
					canComplete: false,
					canStart: true,
				});
				task.disapprove().then(() => this.updateItem(task.id, { status: task.status })).catch(() => {});
				this.updateItem(task.id, { status: task.status });
			}
		}

		/**
		 * @param {Task} task
		 */
		onStartTimerAction(task)
		{
			task.updateActions({
				canStartTimer: false,
				canPauseTimer: true,
				canStart: false,
				canPause: false,
				canRenew: false,
			});
			task.startTimer().then(() => this.updateItem(task.id, { status: task.status })).catch(() => {});
			this.updateItem(task.id, { status: task.status });
		}

		/**
		 * @param {Task} task
		 */
		onPauseTimerAction(task)
		{
			task.updateActions({
				canStartTimer: true,
				canPauseTimer: false,
				canStart: false,
				canPause: false,
				canRenew: false,
			});
			task.pauseTimer().then(() => this.updateItem(task.id, { status: task.status })).catch(() => {});
			this.updateItem(task.id, { status: task.status });
		}

		/**
		 * @param {Task} task
		 */
		onStartAction(task)
		{
			if (task.status !== TaskStatus.IN_PROGRESS)
			{
				task.updateActions({
					canStartTimer: false,
					canPauseTimer: false,
					canStart: false,
					canPause: true,
					canRenew: false,
				});
				task.start().then(() => this.updateItem(task.id, { status: task.status })).catch(() => {});
				this.updateItem(task.id, { status: task.status });
			}
		}

		/**
		 * @param {Task} task
		 */
		onPauseAction(task)
		{
			if (task.status !== TaskStatus.PENDING)
			{
				task.updateActions({
					canStartTimer: false,
					canPauseTimer: false,
					canStart: true,
					canPause: false,
					canRenew: false,
				});
				task.pause().then(() => this.updateItem(task.id, { status: task.status })).catch(() => {});
				this.updateItem(task.id, { status: task.status });
			}
		}

		/**
		 * @param {Task} task
		 */
		onRenewAction(task)
		{
			if (task.isCompletedCounts)
			{
				task.updateActions({
					canStart: true,
					canPause: false,
					canRenew: false,
				});
				task.renew()
					.then(() => {
						this.updateItem(task.id, {
							status: task.status,
							activityDate: Date.now(),
						});
					})
					.catch(() => {})
				;
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
				.setTitle(Loc.getMessage('TASKS_LIST_POPUP_RESPONSIBLE'))
				.setSelected({
					user: [
						{
							id: task.responsible.id,
							title: task.responsible.name,
							imageUrl: task.responsible.icon,
						},
					],
				})
				.open()
				.then((recipients) => {
					if (recipients.user && recipients.user.length > 0)
					{
						const user = recipients.user[0];
						if (Number(task.responsible.id) !== Number(user.id))
						{
							this.updateItem(task.id, {
								responsible: TaskList.getItemDataFromUser(user),
								activityDate: Date.now(),
							});
							if (!task.isMember(this.currentUser.id))
							{
								this.filter.pseudoUpdateCounters(-task.getCounterMyCount(), task);
							}
							void task.save();
						}
					}
				})
				.catch(() => {})
			;
		}

		/**
		 * @param {Task} task
		 */
		onDelegateAction(task)
		{
			(new RecipientSelector('TASKS_MEMBER_SELECTOR_EDIT_responsible', ['user']))
				.setSingleChoose(true)
				.setTitle(Loc.getMessage('TASKS_LIST_POPUP_RESPONSIBLE'))
				.setSelected({
					user: [
						{
							id: task.responsible.id,
							title: task.responsible.name,
							imageUrl: task.responsible.image,
						},
					],
				})
				.open()
				.then((recipients) => {
					if (recipients.user && recipients.user.length > 0)
					{
						const user = recipients.user[0];
						if (Number(task.responsible.id) !== Number(user.id))
						{
							this.updateItem(task.id, {
								responsible: TaskList.getItemDataFromUser(user),
								activityDate: Date.now(),
							});
							void task.delegate();
						}
					}
				})
				.catch(() => {})
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
				.setTitle(Loc.getMessage('TASKS_LIST_POPUP_PROJECT'))
				.setSelected({ project: selected })
				.open()
				.then((recipients) => {
					if (recipients.project && recipients.project.length > 0)
					{
						const group = recipients.project[0];
						if (Number(task.groupId) === Number(group.id))
						{
							return;
						}
						// eslint-disable-next-line no-param-reassign
						task.groupId = Number(group.id);
						// eslint-disable-next-line no-param-reassign
						task.group = TaskList.getItemDataFromGroup(group);
						this.updateItem(task.id, { activityDate: Date.now() });
						void task.save();
					}
				})
				.catch(() => {})
			;
		}

		/**
		 * @param {Task} task
		 */
		onMuteAction(task)
		{
			this.updateItem(task.id, { isMuted: true });
			this.filter.pseudoUpdateCounters(-task.getCounterMyCount());
			task.mute();
		}

		/**
		 * @param {Task} task
		 */
		onUnmuteAction(task)
		{
			this.updateItem(task.id, { isMuted: false });
			this.filter.pseudoUpdateCounters(task.getCounterMyCount());
			task.unmute();
		}

		/**
		 * @param {Task} task
		 */
		onUnfollowAction(task)
		{
			// eslint-disable-next-line no-param-reassign
			delete task.auditors[this.currentUser.id.toString()];

			if (!task.isMember(this.currentUser.id))
			{
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
				title: Loc.getMessage('TASKS_CONFIRM_DELETE'),
				callback: (item) => {
					if (item.code === 'YES')
					{
						this.removeItem(task.id);
						this.filter.pseudoUpdateCounters(-task.getCounterMyCount(), task);

						(new RequestExecutor('tasks.task.delete', { taskId: task.id }))
							.call()
							.then(() => {}, (response) => Console.log(response))
							.catch((response) => Console.log(response));
					}
				},
				items: [
					{ title: Loc.getMessage('TASKS_CONFIRM_DELETE_YES'), code: 'YES' },
					{ title: Loc.getMessage('TASKS_CONFIRM_DELETE_NO'), code: 'NO' },
				],
			});
		}

		/**
		 * @param {Task} task
		 */
		onPinAction(task)
		{
			this.updateItem(task.id, { isPinned: true });
			task.pin();
		}

		/**
		 * @param {Task} task
		 */
		onUnpinAction(task)
		{
			this.updateItem(task.id, { isPinned: false });
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

		/**
		 * @param {Task} task
		 */
		onShareAction(task)
		{
			dialogs.showSharingDialog({
				message: `${currentDomain}/company/personal/user/${this.currentUser.id}/tasks/task/view/${task.id}/`,
			});
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
				const subHeader = (this.order.isDeadline() ? Loc.getMessage('TASKS_LIST_SUB_HEADER_DEADLINES') : '');
				titleParams.text = TaskList.titles[this.filter.getRole()].replace('#DEADLINES#', subHeader);
			}

			this.list.setTitle(titleParams);

			BX.postComponentEvent('tasks.list:updateTitle', [
				{
					useProgress,
					guid: this.tabsGuid,
				},
			]);
		}

		pseudoReadTasks(taskIds, pseudoUpdateCounters = false)
		{
			const items = [];
			const tasks = [];
			let newCommentsRead = 0;

			this.taskList.forEach((task) => {
				if (taskIds.includes(task.id))
				{
					newCommentsRead += task.getCounterMyNewCommentsCount();
					task.pseudoRead();

					items.push({
						filter: { id: task.id },
						element: this.getItemDataFromTask(task),
					});
					tasks.push(task);
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

	const userId = parseInt(BX.componentParameters.get('USER_ID', 0), 10);
	const params = {
		projectNewsPathTemplate: BX.componentParameters.get('PROJECT_NEWS_PATH_TEMPLATE', ''),
		projectCalendarWebPathTemplate: BX.componentParameters.get('PROJECT_CALENDAR_WEB_PATH_TEMPLATE', ''),
		isTabsMode: BX.componentParameters.get('IS_TABS_MODE', false),
	};

	return new TaskList(list, userId, params);
})();
