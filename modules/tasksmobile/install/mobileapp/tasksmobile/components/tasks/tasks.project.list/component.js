(() => {
	const {debounce} = jn.require('utils/function');
	const {EntityReady} = jn.require('entity-ready');
	const {Loc} = jn.require('loc');
	const {magnifierWithMenuAndDot} = jn.require('assets/common');
	const {PresetList} = jn.require('tasks/layout/presetList');

	const apiVersion = Application.getApiVersion();
	const platform = Application.getPlatform();
	const caches = new Map();

	const isSearchByPresetsEnable = (apiVersion >= 49);

	class Loading
	{
		/**
		 * @param {ProjectList} list
		 */
		constructor(list)
		{
			this.list = list.list;
		}

		isEnabled()
		{
			return (apiVersion >= 34);
		}

		showForList()
		{
			if (this.isEnabled() && !this.isShowedForList)
			{
				dialogs.showSpinnerIndicator({
					color: '#777777',
					backgroundColor: '#77ffffff',
				});
				this.isShowedForList = true;
			}
		}

		hideForList()
		{
			if (this.isEnabled() && this.isShowedForList)
			{
				dialogs.hideSpinnerIndicator();
				this.isShowedForList = false;
			}
		}

		showForTitle()
		{
			this.list.setTitle({
				useProgress: true,
				largeMode: true,
				text: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_HEADER_PROJECTS'),
			});
		}

		hideForTitle()
		{
			this.list.setTitle({
				useProgress: false,
				largeMode: true,
				text: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_HEADER_PROJECTS'),
			});
		}
	}

	class WelcomeScreen
	{
		/**
		 * @param {ProjectList} list
		 */
		constructor(list)
		{
			this.list = list;
		}

		isEnabled()
		{
			return (apiVersion >= 40);
		}

		show()
		{
			if (this.isEnabled())
			{
				this.list.list.welcomeScreen.show({
					upperText: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_WELCOME_SCREEN_TITLE'),
					lowerText: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_WELCOME_SCREEN_SUBTITLE'),
					iconName: 'ws_open_project',
				});
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

			this.storage = Application.sharedStorage('tasksProjectList');
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

			if (typeof cache === "string")
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

	class ProjectCache extends Cache
	{
		constructor(cacheKey)
		{
			super(cacheKey);
			this.init();
		}

		getInstance(id)
		{
			if (!caches.has(id))
			{
				caches.set(id, (new ProjectCache(id)));
			}

			return caches.get(id);
		}

		init()
		{
			const has = Object.prototype.hasOwnProperty;
			const cache = this.get();

			Object.values(Filter.counterTypes).forEach((counterType) => {
				if (!has.call(cache, counterType))
				{
					cache[counterType] = [];
				}
			});

			this.set(cache);
		}

		/**
		 * @param {Project} project
		 * @param {Object} projectItem
		 */
		addProject(project, projectItem)
		{
			const cache = this.get();
			if (!cache || Object.keys(cache).length === 0)
			{
				return;
			}

			const countersMap = {
				[Filter.counterTypes.none]: true,
				[Filter.counterTypes.sonetTotalExpired]: (project.getCounterMyExpiredCount() > 0),
				[Filter.counterTypes.sonetTotalComments]: (project.getCounterMyNewCommentsCount() > 0),
				[Filter.counterTypes.sonetForeignExpired]: (project.getCounterProjectExpiredCount() > 0),
				[Filter.counterTypes.sonetForeignComments]: (project.getCounterProjectNewCommentsCount() > 0),
			};
			Object.keys(countersMap).forEach((counter) => {
				if (countersMap[counter])
				{
					cache[counter].splice(0, 0, projectItem);
				}
			});
			this.set(cache);
		}

		updateProject(projects)
		{
			const cache = this.get();
			if (!cache || Object.keys(cache).length === 0)
			{
				return;
			}

			Object.keys(projects).forEach((projectId) => {
				const {project, projectItem} = projects[projectId];
				const findCondition = (project) => Number(project.id) === Number(projectId);
				const countersMap = {
					[Filter.counterTypes.none]: true,
					[Filter.counterTypes.sonetTotalExpired]: (project.getCounterMyExpiredCount() > 0),
					[Filter.counterTypes.sonetTotalComments]: (project.getCounterMyNewCommentsCount() > 0),
					[Filter.counterTypes.sonetForeignExpired]: (project.getCounterProjectExpiredCount() > 0),
					[Filter.counterTypes.sonetForeignComments]: (project.getCounterProjectNewCommentsCount() > 0),
				};
				Object.keys(countersMap).forEach((counter) => {
					if (countersMap[counter])
					{
						const index = cache[counter].findIndex(findCondition);
						if (index !== -1)
						{
							cache[counter][index] = projectItem;
						}
						else
						{
							cache[counter].splice(0, 0, projectItem);
						}
					}
					else
					{
						const index = cache[counter].findIndex(findCondition);
						if (index !== -1)
						{
							cache[counter].splice(index, 1);
						}
					}
				});
			});
			this.set(cache);
		}

		removeProject(projectId)
		{
			const cache = this.get();
			if (!cache || Object.keys(cache).length === 0)
			{
				return;
			}

			Object.keys(cache).forEach((counter) => {
				const index = cache[counter].findIndex(project => Number(project.id) === Number(projectId));
				if (index !== -1)
				{
					cache[counter].splice(index, 1);
				}
			});
			this.set(cache);
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
			};
		}

		static get sectionOrderFields()
		{
			return {
				activityDate: 'desc',
			};
		}

		constructor()
		{
			this.order = 'activityDate';
		}

		get()
		{
			const order = {
				IS_PINNED: 'DESC',
			};

			Order.fields[this.order].forEach((fieldData) => {
				order[fieldData.field] = fieldData.direction;
			});

			return order;
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
		static get presetTypes()
		{
			return {
				none: 'none',
				default: 'my',
			};
		}

		static get counterTypes()
		{
			return {
				none: 'none',
				sonetTotalExpired: 'sonetTotalExpired',
				sonetTotalComments: 'sonetTotalComments',
				sonetForeignExpired: 'sonetForeignExpired',
				sonetForeignComments: 'sonetForeignComments',
			};
		}

		/**
		 * @param {ProjectList} list
		 * @param {Integer} userId
		 */
		constructor(list, userId)
		{
			this.list = list;
			this.userId = userId;

			this.preset = Filter.presetTypes.default;
			this.counter = Filter.counterTypes.none;
			this.counters = {};
			this.searchText = '';
			this.isShowMine = !isSearchByPresetsEnable;

			this.cache = Cache.getInstance('filterCounters');
			this.total = this.cache.get().counterValue || 0;

			EntityReady.wait('chat').then(() => this.updateCounters());
		}

		updateCounters()
		{
			console.log('ProjectList.Filter.updateCounters');

			(new RequestExecutor('tasks.project.counter.getTotal', {userId: this.userId}))
				.call()
				.then((response) => {
					this.counters = {};
					this.total = 0;

					Object.entries(response.result).forEach(([type, value]) => {
						this.counters[type] = value;
						if (
							type === Filter.counterTypes.sonetTotalExpired
							|| type === Filter.counterTypes.sonetTotalComments
						)
						{
							this.total += value;
						}
					});

					this.setVisualCounters();
					this.saveCache();
				})
			;
		}

		pseudoUpdateCounters(value)
		{
			this.total += value;
			this.total = (this.total < 0 ? 0 : this.total);

			this.setVisualCounters();
		}

		setVisualCounters(value = null)
		{
			value = (value || this.total);

			Application.setBadges({
				[`tasksProjectListMoreButton_${this.userId}`]: value,
			});
			BX.postComponentEvent('tasks.project.list:setVisualCounter', [{value}], 'tasks.tabs');
		}

		saveCache()
		{
			this.cache.set({counterValue: this.total});
		}

		get()
		{
			const filter = {};

			if (this.searchText)
			{
				filter.FIND = this.searchText;
			}

			if (this.isShowMine)
			{
				filter['MEMBER'] = this.userId;
			}

			switch (this.counter)
			{
				case Filter.counterTypes.sonetTotalExpired:
					filter.COUNTERS = 'EXPIRED';
					break;

				case Filter.counterTypes.sonetTotalComments:
					filter.COUNTERS = 'NEW_COMMENTS';
					break;

				case Filter.counterTypes.sonetForeignExpired:
					filter.COUNTERS = 'PROJECT_EXPIRED';
					break;

				case Filter.counterTypes.sonetForeignComments:
					filter.COUNTERS = 'PROJECT_NEW_COMMENTS';
					break;

				case Filter.counterTypes.none:
				default:
					break;
			}

			return filter;
		}

		getCounterValue(type)
		{
			return this.counters[type] || 0;
		}

		getSearchText()
		{
			return this.searchText;
		}

		setSearchText(searchText)
		{
			this.searchText = searchText;
		}

		getPreset()
		{
			return this.preset;
		}

		setPreset(preset)
		{
			this.preset = preset;
		}

		getCounter()
		{
			return this.counter;
		}

		setCounter(counter)
		{
			this.counter = counter;
		}

		getIsShowMine()
		{
			return this.isShowMine;
		}

		setIsShowMine(isShowMine)
		{
			this.isShowMine = isShowMine;
		}
	}

	class MoreMenu
	{
		static get counterColors()
		{
			return {
				gray: '#a8adb4',
				green: '#9dcf00',
				red: '#ff5752',
			};
		}

		/**
		 * @param {ProjectList} list
		 */
		constructor(list)
		{
			this.list = list;
			this.filter = list.filter;
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
			let items = [
				{
					id: Filter.counterTypes.sonetTotalComments,
					title: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_FILTER_COUNTER_MY_NEW_COMMENTS'),
					sectionCode: 'default',
					checked: (this.filter.getCounter() === Filter.counterTypes.sonetTotalComments),
					counterValue: this.filter.getCounterValue(Filter.counterTypes.sonetTotalComments),
					counterStyle: {
						backgroundColor: MoreMenu.counterColors.green,
					},
				},
				{
					id: Filter.counterTypes.sonetTotalExpired,
					title: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_FILTER_COUNTER_MY_EXPIRED'),
					sectionCode: 'default',
					checked: (this.filter.getCounter() === Filter.counterTypes.sonetTotalExpired),
					counterValue: this.filter.getCounterValue(Filter.counterTypes.sonetTotalExpired),
					counterStyle: {
						backgroundColor: MoreMenu.counterColors.red,
					},
				},
				{
					id: Filter.counterTypes.sonetForeignComments,
					title: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_FILTER_COUNTER_OTHER_NEW_COMMENTS'),
					sectionCode: 'default',
					checked: (this.filter.getCounter() === Filter.counterTypes.sonetForeignComments),
					counterValue: this.filter.getCounterValue(Filter.counterTypes.sonetForeignComments),
					counterStyle: {
						backgroundColor: MoreMenu.counterColors.gray,
					},
				},
				{
					id: Filter.counterTypes.sonetForeignExpired,
					title: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_FILTER_COUNTER_OTHER_EXPIRED'),
					sectionCode: 'default',
					checked: (this.filter.getCounter() === Filter.counterTypes.sonetForeignExpired),
					counterValue: this.filter.getCounterValue(Filter.counterTypes.sonetForeignExpired),
					counterStyle: {
						backgroundColor: MoreMenu.counterColors.gray,
					},
				},
				{
					id: 'toggleShowMine',
					title: Loc.getMessage(
						this.filter.getIsShowMine()
							? 'MOBILE_TASKS_PROJECT_LIST_ACTION_SHOW_ALL'
							: 'MOBILE_TASKS_PROJECT_LIST_ACTION_SHOW_MINE'
					),
					sectionCode: SectionHandler.sections.default,
					showTopSeparator: true,
				},
				{
					id: 'readAll',
					title: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_ACTION_READ_ALL'),
					iconName: 'read',
					sectionCode: SectionHandler.sections.default,
					showTopSeparator: true,
				},
			];

			if (isSearchByPresetsEnable)
			{
				items = items.filter((item) => item.id !== 'toggleShowMine');
			}

			return items;
		}

		onItemSelected(item)
		{
			switch (item.id)
			{
				case Filter.counterTypes.sonetTotalExpired:
				case Filter.counterTypes.sonetTotalComments:
				case Filter.counterTypes.sonetForeignExpired:
				case Filter.counterTypes.sonetForeignComments:
					this.onCounterChange(item.id);
					break;

				case 'toggleShowMine':
					this.onToggleShowMineAction();
					break;

				case 'readAll':
					this.onReadAllAction();
					break;
			}
		}

		onCounterChange(newCounter)
		{
			newCounter = (this.filter.getCounter() === newCounter ? Filter.counterTypes.none : newCounter);
			this.filter.setCounter(newCounter);

			this.list.setTopButtons();
			this.list.reload(0, true);
		}

		onToggleShowMineAction()
		{
			this.filter.setIsShowMine(!this.filter.getIsShowMine());

			this.list.setTopButtons();
			this.list.reload(0, true);
		}

		onReadAllAction()
		{
			this.list.pseudoReadProjects([...this.list.projectList.keys()]);

			(new RequestExecutor('tasks.viewedGroup.project.markAsRead', {fields: {groupId: 0}}))
				.call()
				.then((response) => {
					if (response.result === true)
					{
						Notify.showIndicatorSuccess({
							text: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_NOTIFICATION_READ_ALL'),
							hideAfter: 1500,
						});
					}
				})
			;
		}
	}

	class Action
	{
		static get swipeActions()
		{
			const imagePrefix = `${component.path}images/mobile-tasks-projectlist-swipe-`;

			return {
				about: {
					identifier: 'about',
					title: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_ACTION_ABOUT'),
					iconName: 'action_project',
					iconUrl: `${imagePrefix}about.png`,
					color: '#f2a100',
					position: 'right',
				},
				members: {
					identifier: 'members',
					title: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_ACTION_MEMBERS'),
					iconName: 'action_userlist',
					iconUrl: `${imagePrefix}members.png`,
					color: '#2f72b9',
					position: 'right',
				},
				join: {
					identifier: 'join',
					title: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_ACTION_JOIN'),
					iconName: 'action_accept',
					iconUrl: `${imagePrefix}join.png`,
					color: '#468ee5',
					position: 'right',
				},
				// leave: {
				// 	identifier: 'leave',
				// 	title: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_ACTION_LEAVE'),
				// 	iconName: 'action_skip',
				// 	iconUrl: `${imagePrefix}leave.png`,
				// 	color: '#848e9e',
				// 	position: 'right',
				// },
				read: {
					identifier: 'read',
					title: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_ACTION_READ'),
					iconName: 'action_read',
					iconUrl: `${imagePrefix}read.png`,
					color: '#e57bb6',
					position: 'left',
				},
				pin: {
					identifier: 'pin',
					title: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_ACTION_PIN'),
					iconName: 'action_pin',
					iconUrl: `${imagePrefix}pin.png`,
					color: '#468ee5',
					position: 'left',
				},
				unpin: {
					identifier: 'unpin',
					title: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_ACTION_UNPIN'),
					iconName: 'action_unpin',
					iconUrl: `${imagePrefix}unpin.png`,
					color: '#468ee5',
					position: 'left',
				},
			};
		}

		/**
		 * @param {ProjectList} list
		 */
		constructor(list)
		{
			this.list = list;
		}

		fill(project, itemData)
		{
			itemData.menuMode = (platform !== 'ios' ? 'dialog' : 'swipe');
			itemData.actions = Object.values(Action.swipeActions).filter(action => project.actions[action.identifier]);

			if (!project.isOpened)
			{
				const joinActionIndex = itemData.actions.findIndex(action => action.identifier === 'join');
				if (joinActionIndex >= 0)
				{
					delete itemData.actions[joinActionIndex];
				}
			}

			return itemData;
		}

		onItemAction(event)
		{
			const project = this.list.projectList.get(event.item.id);

			switch (event.action.identifier)
			{
				case 'pin':
					this.onPinAction(project);
					break;

				case 'unpin':
					this.onUnpinAction(project);
					break;

				case 'read':
					this.onReadAction(project);
					break;

				case 'about':
					this.onAboutAction(project);
					break;

				case 'members':
					this.onMembersAction(project);
					break;

				case 'join':
					this.onJoinAction({id: project.id});
					break;

				case 'leave':
					this.onLeaveAction(project);
					break;

				default:
					break;
			}

			this.list.updateItem(project.id);
		}

		onPinAction(project)
		{
			void project.pin();
		}

		onUnpinAction(project)
		{
			void project.unpin();
		}

		onReadAction(project)
		{
			this.list.filter.pseudoUpdateCounters(-project.getNewCommentsCount());
			void project.read();
		}

		onAboutAction(project)
		{
			ProjectViewManager.open(this.list.userId, project.id);
		}

		onMembersAction(project)
		{
			PageManager.openWidget('list', {
				backdrop: {
					bounceEnable: false,
					swipeAllowed: true,
					showOnTop: true,
					hideNavigationBar: false,
					horizontalSwipeAllowed: false,
				},
				useSearch: true,
				useLargeTitleMode: true,
				title: 'Project members',
				onReady: (list) => {
					new ProjectMemberList(list, this.list.userId, project.id, {
						isOwner: project.isOwner(),
						canInvite: project.actions.invite,
						minSearchSize: 3,
					})
				},
				onError: error => console.log(error),
			});
		}

		onJoinAction(event)
		{
			const projectId = String(event.id);

			if (this.list.projectList.has(projectId))
			{
				const project = this.list.projectList.get(projectId);

				if (project.isOpened)
				{
					project.join().then(() => this.list.updateItem(projectId));

					const projectItem = this.list.prepareListItem(project);
					projectItem.joinButtonState = 'animated';
					this.list.list.updateItem({id: projectId}, projectItem);
				}
			}
		}

		onLeaveAction(project)
		{
			void project.leave();
		}
	}

	class Pull
	{
		static get commonEvents()
		{
			return [
				'project_read_all',
				'comment_read_all',
			];
		}

		static get counterEvents()
		{
			return [
				'onAfterTaskAdd',
				'onAfterTaskDelete',
				'onAfterTaskRestore',
				'onAfterTaskView',
				'onAfterTaskMute',
				'onAfterCommentAdd',
				'onAfterCommentDelete',
				'onProjectPermUpdate',
			];
		}

		static get userOptions()
		{
			return {
				pinned: 2,
			}
		}

		/**
		 * @param {ProjectList} list
		 * @param {Integer} userId
		 */
		constructor(list, userId)
		{
			this.list = list;
			this.userId = userId;

			this.queue = new Set();
			this.counterEventData = new Map();

			this.canExecute = true;

			this.extendWatch();
			this.startWatch().then(() => this.subscribe());
		}

		getEventHandlers()
		{
			return {
				project_add: {
					method: this.onProjectAdd,
					context: this,
				},
				project_update: {
					method: this.onProjectUpdate,
					context: this,
				},
				project_remove: {
					method: this.onProjectRemove,
					context: this,
				},
				project_user_add: {
					method: this.onProjectUserAdd,
					context: this,
				},
				project_user_update: {
					method: this.onProjectUserUpdate,
					context: this,
				},
				project_user_remove: {
					method: this.onProjectUserRemove,
					context: this,
				},
				project_user_option_changed: {
					method: this.onProjectUserOptionChanged,
					context: this,
				},
				project_read_all: {
					method: this.onProjectCommentsReadAll,
					context: this,
				},
				comment_read_all: {
					method: this.onProjectCommentsReadAll,
					context: this,
				},
				project_counter: {
					method: this.onProjectCounter,
					context: this,
				},
			};
		}

		startWatch()
		{
			return new Promise((resolve, reject) => {
				(new RequestExecutor('mobile.tasks.project.list.startWatch'))
					.call()
					.then(
						(response) => resolve(response),
						(response) => {
							console.error(response);
							reject(response);
						}
					)
				;
			});
		}

		extendWatch()
		{
			BX.PULL.extendWatch('TASKS_PROJECTS', true);
			setTimeout(() => this.extendWatch(), 29 * 60 * 1000);
		}

		subscribe()
		{
			BX.PULL.subscribe({
				moduleId: 'tasks',
				callback: data => this.processPullEvent(data),
			});
		}

		processPullEvent(data)
		{
			if (this.getCanExecute())
			{
				void this.executePullEvent(data);
			}
			else
			{
				this.queue.add(data);
			}
		}

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

		freeQueue()
		{
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

			this.queue = new Set([...this.queue].filter(event => Pull.commonEvents.includes(event.command)));
			this.queue = new Set(Object.values([...this.queue].reduce(clearDuplicates, {})));

			const promises = [...this.queue].map(event => this.executePullEvent(event));

			return Promise.allSettled(promises);
		}

		clear()
		{
			this.queue.clear();
		}

		getProjectsToUpdateFromEvents()
		{
			const processed = [];

			this.queue.forEach((event) => {
				const has = Object.prototype.hasOwnProperty;
				const eventHandlers = this.getEventHandlers();
				const {command, params} = event;

				if (has.call(eventHandlers, command))
				{
					const projectId = (params.ID || params.GROUP_ID || 0);
					if (projectId)
					{
						processed.push(String(projectId));
						this.queue.delete(event);
					}
				}
			});

			return processed;
		}

		getCanExecute()
		{
			return this.canExecute;
		}

		setCanExecute(canExecute)
		{
			this.canExecute = canExecute;
		}

		onProjectAdd(data)
		{
			return this.list.updateProjects([String(data.ID)]);
		}

		onProjectUpdate(data)
		{
			return this.list.updateProjects([String(data.ID)]);
		}

		onProjectRemove(data)
		{
			return new Promise((resolve) => {
				this.list.removeItem(String(data.ID));
				resolve();
			});
		}

		onProjectUserAdd(data)
		{
			return this.list.updateProjects([String(data.GROUP_ID)]);
		}

		onProjectUserUpdate(data)
		{
			return this.list.updateProjects([String(data.GROUP_ID)]);
		}

		onProjectUserRemove(data)
		{
			return this.list.updateProjects([String(data.GROUP_ID)]);
		}

		onProjectUserOptionChanged(data)
		{
			return new Promise((resolve, reject) => {
				if (Number(data.USER_ID) !== Number(this.userId))
				{
					resolve();
					return;
				}
				switch (data.OPTION)
				{
					case Pull.userOptions.pinned:
						this.onProjectPinChanged(String(data.PROJECT_ID), data.ADDED)
							.then(() => resolve())
							.catch(() => reject())
						;
						break;

					default:
						break;
				}
			});
		}

		onProjectPinChanged(projectId, added)
		{
			return new Promise((resolve, reject) => {
				if (this.list.projectList.has(projectId))
				{
					this.list.updateItem(projectId, {isPinned: (added ? 'Y' : 'N')});
					resolve();
				}
				else if (added)
				{
					this.list.updateProjects([projectId])
						.then(() => resolve(), () => reject())
						.catch(() => reject())
					;
				}
			});
		}

		onProjectCommentsReadAll(data)
		{
			return new Promise((resolve) => {
				const userId = Number(data.USER_ID);
				if (userId > 0 && userId !== this.userId)
				{
					resolve();
					return;
				}

				const projectId = String(data.GROUP_ID);
				if (Number(projectId) > 0)
				{
					if (this.list.projectList.has(projectId))
					{
						this.list.pseudoReadProjects([projectId]);
					}
				}
				else
				{
					this.list.pseudoReadProjects([...this.list.projectList.keys()]);
				}

				resolve();
			});
		}

		onProjectCounter(data)
		{
			this.list.filter.updateCounters();

			const projectId = String(data.GROUP_ID);
			const event = data.EVENT;

			if (!Pull.counterEvents.includes(event))
			{
				return;
			}

			if (!this.timer)
			{
				this.timer = setTimeout(() => {
					this.freeCounterQueue();
				}, 1000);
			}

			if (!this.counterEventData.has(projectId))
			{
				this.counterEventData.set(projectId, event);
			}
		}

		freeCounterQueue()
		{
			void this.list.updateProjects([...this.counterEventData.keys()]);
			this.counterEventData.clear();
			this.timer = null;
		}

		onAppActive()
		{
			this.clear();
			this.extendWatch();
			this.startWatch().then(() => this.setCanExecute(true));
		}
	}

	/**
	 * @class ProjectList
	 */
	class ProjectList
	{
		static get backgroundColors()
		{
			return {
				default: '#ffffff',
				pinned: '#f4f5f7',
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

		static get select()
		{
			return [
				'ID',
				'NAME',
				'IMAGE_ID',
				'AVATAR_TYPE',
				'NUMBER_OF_MODERATORS',
				'NUMBER_OF_MEMBERS',
				'OPENED',
				'CLOSED',
				'VISIBLE',
				'IS_EXTRANET',
				'USER_GROUP_ID',
				'ACTIVITY_DATE',
				'IS_PINNED',
				'MEMBERS',
				'COUNTERS',
				'ACTIONS',
			];
		}

		static get avatarTypes()
		{
			return {
				public: 'status_task_public',
				private: 'status_task_private',
				secret: 'status_task_secret',
				extranet: 'status_task_extranet',
			};
		}

		constructor(list, userId, params)
		{
			console.log('ProjectList.constructor', userId);

			this.list = list;
			this.userId = userId;
			this.newsPathTemplate = (params.projectNewsPathTemplate || '');
			this.calendarWebPathTemplate = (params.projectCalendarWebPathTemplate || '');

			this.start = 0;
			this.pageSize = 50;

			this.projectList = new Map();

			this.cache = new ProjectCache(`projectList_${this.userId}`);
			this.order = new Order();
			this.filter = new Filter(this, this.userId);
			this.moreMenu = new MoreMenu(this);
			this.welcomeScreen = new WelcomeScreen(this);
			this.loading = new Loading(this);
			this.action = new Action(this);
			this.pull = new Pull(this, this.userId);

			this.debounceSearch = debounce(
				(text) => {
					this.filter.setSearchText(text);
					this.setTopButtons();
					this.reload(0, true);
				},
				500,
				this
			);
			this.getPresets();

			BX.onViewLoaded(() => {
				this.list.setItems([
					{
						type: 'loading',
						title: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_LOADING'),
					},
				]);

				this.setTopButtons();
				this.setFloatingButton();
				this.setListListeners();
				this.bindEvents();

				this.filter.updateCounters();

				this.loadProjectsFromCache();
				this.reload();
			});
		}

		getPresets()
		{
			if (!isSearchByPresetsEnable)
			{
				return;
			}

			(new RequestExecutor('tasksmobile.Filter.getProjectListPresets'))
				.call()
				.then((response) => {
					this.presets = response.result;
					if (this.searchLayout)
					{
						this.searchLayout.updateState({
							presets: this.presets,
							currentPreset: this.filter.getPreset(),
						});
					}
				})
			;
		}

		setTopButtons()
		{
			const isDefaultSearch = (this.filter.getPreset() === Filter.presetTypes.default && !this.filter.getSearchText());

			this.list.setRightButtons([
				{
					type: 'search',
					badgeCode: 'tasksProjectListSearchButton',
					svg: {
						content: magnifierWithMenuAndDot('#a8adb4', (isDefaultSearch ? null : '#2fc6f6')),
					},
					callback: () => this.onSearchClick(),
				},
				{
					type: (this.filter.getCounter() === Filter.counterTypes.none ? 'more' : 'more_active'),
					badgeCode: `tasksProjectListMoreButton_${this.userId}`,
					callback: () => this.moreMenu.show(),
				},
			]);
			this.filter.setVisualCounters();
		}

		onSearchClick()
		{
			if (isSearchByPresetsEnable)
			{
				if (!this.isSearchInit)
				{
					this.isSearchInit = true;

					this.list.search.mode = 'layout';
					this.list.search.on('textChanged', ({text}) => this.debounceSearch(text));
					this.list.search.on('cancel', () => {
						if (
							this.filter.getSearchText()
							|| this.filter.getPreset() !== Filter.presetTypes.default
						)
						{
							this.filter.setSearchText('');
							this.filter.setPreset(Filter.presetTypes.default);

							this.setTopButtons();
							this.reload(0, true);
						}
					});
				}
				this.searchLayout = new PresetList({
					presets: this.presets,
					currentPreset: this.filter.getPreset(),
				});
				this.searchLayout.on('presetSelected', (preset) => {
					if (preset.id === this.filter.getPreset())
					{
						this.filter.setPreset(Filter.presetTypes.none);
					}
					else
					{
						this.filter.setPreset(preset.id);
						this.filter.setCounter(Filter.counterTypes.none);
					}
					this.setTopButtons();
					this.reload(0, true);
				});
				this.list.search.text = this.filter.getSearchText();
				this.list.search.show(this.searchLayout, 46);
			}
			else
			{
				if (!this.isSearchInit)
				{
					this.isSearchInit = true;

					this.list.search.mode = 'bar';
					this.list.search.on('textChanged', ({text}) => this.debounceSearch(text));
					this.list.search.on('cancel', () => {
						if (
							this.filter.getSearchText()
							|| this.filter.getPreset() !== Filter.presetTypes.default
						)
						{
							this.filter.setSearchText('');
							this.filter.setPreset(Filter.presetTypes.default);

							this.setTopButtons();
							this.reload(0, true);
						}
					});
					this.list.search.on('clickEnter', () => this.list.search.close());
				}
				this.list.search.text = this.filter.getSearchText();
				this.list.search.show();
			}
		}

		setFloatingButton()
		{
			this.getCanCreateProject().then(
				response => this.renderFloatingButton(response),
				response => console.error(response)
			);
		}

		getCanCreateProject()
		{
			return new Promise((resolve, reject) => {
				(new RequestExecutor('socialnetwork.api.workgroup.getCanCreate'))
					.call()
					.then(
						response => resolve(response.result),
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
					callback: () => this.addProject(),
				});
			}
			else
			{
				this.list.setFloatingButton({});
			}
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
				onItemSelected: {
					callback: this.onItemSelected,
					context: this,
				},
				onItemAction: {
					callback: this.action.onItemAction,
					context: this.action,
				},
				eventJoin: {
					callback: this.action.onJoinAction,
					context: this.action,
				},
				onScroll: {
					callback: () => {
						if (isSearchByPresetsEnable)
						{
							this.list.search.close();
						}
					},
					context: this,
				},
			};

			this.list.setListener((event, data) => {
				console.log(`ProjectList.appEvent.${event}`);
				if (eventHandlers[event])
				{
					eventHandlers[event].callback.apply(eventHandlers[event].context, [data]);
				}
			});
		}

		bindEvents()
		{
			BX.addCustomEvent('tasks.tabs:onTabSelected', eventData => this.onTabSelected(eventData));
			BX.addCustomEvent('tasks.tabs:onAppActive', () => this.onAppActive());
			BX.addCustomEvent('tasks.tabs:onAppPaused', () => this.onAppPaused());
		}

		loadProjectsFromCache()
		{
			const counter = this.filter.getCounter();
			const has = Object.prototype.hasOwnProperty;
			const cache = this.cache.get();

			let cachedProjects = [];
			if (has.call(cache, counter))
			{
				cachedProjects = cache[counter] || [];
			}

			if (!Array.isArray(cachedProjects) || cachedProjects.length < 1)
			{
				console.log('ProjectList.loadProjectsFromCache.empty');
				return;
			}

			this.list.setItems(cachedProjects, null, false);
		}

		reload(offset = 0, showLoading = false)
		{
			if (showLoading)
			{
				this.loading.showForList();
			}
			this.loading.showForTitle();

			const params = {
				mode: 'mobile',
				listMode: 'tasks_project',
			};
			if (isSearchByPresetsEnable)
			{
				params.siftThroughFilter = {
					presetId: this.filter.getPreset(),
				};
			}

			BX.rest.callMethod(
				'tasks.project.list',
				{
					select: ProjectList.select,
					filter: this.filter.get(),
					order: this.order.get(),
					start: offset,
					params,
				},
				response => this.onReloadSuccess(response, showLoading, offset)
			);
		}

		onReloadSuccess(response, showLoading, offset)
		{
			console.log('ProjectList.onReloadSuccess', response);

			this.start = offset + this.pageSize;

			const isFirstPage = (offset === 0);
			if (isFirstPage)
			{
				this.projectList.clear();
			}
			this.updateSections(isFirstPage);

			const {projects} = response.answer.result;
			const items = [];
			projects.forEach((row) => {
				const project = new Project(this.userId);
				project.setData(row);

				this.projectList.set(String(project.id), project);
				items.push(this.prepareListItem(project));
			});

			console.log('ProjectList.onReloadSuccess:items', items);

			if (isFirstPage)
			{
				this.fillCache(items);
			}

			const isNextPageExist = (this.projectList.size < response.answer.total);
			this.renderProjectListItems(items, isFirstPage, isNextPageExist);

			if (showLoading)
			{
				this.loading.hideForList();
			}
			this.loading.hideForTitle();

			this.list.stopRefreshing();
		}

		updateSections(clear = true)
		{
			const sectionHandler = SectionHandler.getInstance();

			if (clear)
			{
				sectionHandler.clear();
			}

			sectionHandler.setSortItemParams(SectionHandler.sections.pinned, {
				[this.order.order]: Order.sectionOrderFields[this.order.order],
			});
			sectionHandler.setSortItemParams(SectionHandler.sections.default, {
				[this.order.order]: Order.sectionOrderFields[this.order.order],
			});

			this.list.setSections(sectionHandler.list);
		}

		/**
		 * @param {Project} project
		 * @param {bool} withActions
		 */
		prepareListItem(project, withActions = true)
		{
			let itemData = {
				id: String(project.id),
				title: project.name || '',
				imageUrl: project.image || '',
				date: project.activityDate / 1000,
				messageCount: project.counter.value,
				joinButtonState: (project.actions.join && project.isOpened ? 'showed' : 'hidden'),
				creatorIcons: project.getHeadIcons(),
				creatorCount: project.getHeadCount(),
				responsibleIcons: project.getMemberIcons(),
				responsibleCount: project.getMemberCount(),
				styles: {
					counter: {
						backgroundColor: ProjectList.counterColors[project.counter.color],
					},
					date: {
						image: {
							name: (project.isPinned ? 'message_pin' : ''),
						},
						font: {
							size: 13,
						},
					},
				},
				backgroundColor: ProjectList.backgroundColors.default,
				sectionCode: SectionHandler.sections.default,
				sortValues: {
					activityDate: project.activityDate,
				},
				type: 'project',
			};

			if (project.counter.isHidden)
			{
				itemData.messageCount = 0;
			}
			if (project.isPinned)
			{
				itemData.backgroundColor = ProjectList.backgroundColors.pinned;
				itemData.sectionCode = SectionHandler.sections.pinned;
			}
			if (apiVersion >= 40)
			{
				itemData.styles.avatar = {
					image: {
						name: ProjectList.avatarTypes[project.getType()],
					},
				};
			}
			if (withActions)
			{
				itemData = this.action.fill(project, itemData);
			}

			return itemData;
		}

		fillCache(list)
		{
			const counter = this.filter.getCounter();
			const cache = this.cache.get();
			cache[counter] = list;

			this.cache.set(cache);
		}

		renderProjectListItems(items, isFirstPage, isNextPageExist)
		{
			if (items.length <= 0)
			{
				this.welcomeScreen.show();
				return;
			}

			if (isFirstPage)
			{
				this.welcomeScreen.hide();
				this.list.setItems(items);
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
					title: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_NEXT_PAGE'),
					type: 'button',
					sectionCode: SectionHandler.sections.more,
				}]);
			}
		}

		onItemSelected(item)
		{
			const projectId = item.id.toString();

			if (projectId === '-more-')
			{
				this.list.updateItem(
					{id: '-more-'},
					{
						type: 'loading',
						title: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_LOADING'),
					}
				);
				this.reload(this.start);
			}
			else if (this.projectList.has(projectId))
			{
				const project = this.projectList.get(projectId);

				const projectItem = {
					id: project.id,
					title: project.name,
					params: {
						avatar: project.image,
						initiatedByType: project.additionalData.initiatedByType,
						features: (project.additionalData.features || []),
						membersCount: (project.getHeadCount() + project.getMemberCount()),
						role: project.additionalData.role,
						opened: project.isOpened,
					},
				};

				const projectData = {
					siteId: BX.componentParameters.get('SITE_ID', env.siteId),
					siteDir: BX.componentParameters.get('SITE_DIR', env.siteDir),
					projectId: project.id,
					action: 'view',
					item: projectItem,
					newsPathTemplate: this.newsPathTemplate,
					calendarWebPathTemplate: this.calendarWebPathTemplate,
					currentUserId: parseInt(this.userId || 0),
				};

				BX.postComponentEvent('projectbackground::project::action', [ projectData ], 'background');
			}
		}

		addProject()
		{
			ProjectCreateManager.open(this.userId);
		}

		addItem(projectData)
		{
			BX.onViewLoaded(() => {
				const projectId = String(projectData.id);

				if (this.projectList.has(projectId))
				{
					return;
				}

				const project = new Project(this.userId);
				project.setData(projectData);
				this.projectList.set(projectId, project);

				this.welcomeScreen.hide();

				const projectItem = this.prepareListItem(project);
				this.list.addItems([projectItem]);
				this.cache.addProject(project, projectItem);
			});
		}

		updateItem(id, projectData = {})
		{
			BX.onViewLoaded(() => {
				const projectId = id.toString();

				if (!this.projectList.has(projectId))
				{
					return;
				}

				const project = this.projectList.get(projectId);
				project.updateData(projectData);

				const projectItem = this.prepareListItem(project);
				this.list.updateItem({id: projectId}, projectItem);
				this.cache.updateProject({[projectId]: {project, projectItem}});
			});
		}

		removeItem(id)
		{
			BX.onViewLoaded(() => {
				this.projectList.delete(id);
				this.list.removeItem({id});
				this.cache.removeProject(id);

				if (this.projectList.size === 0)
				{
					this.welcomeScreen.show();
				}
			});
		}

		onTabSelected(data)
		{
			if (data.tabId === 'tasks.project.list')
			{
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
		}

		onAppActive()
		{
			this.activationTime = new Date();

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
					this.updateProjectsFromEvents();
				}
			}
		}

		runOnAppActiveRepeatedActions()
		{
			this.setFloatingButton();
			this.getPresets();

			this.filter.updateCounters();
			this.reload();

			this.pull.onAppActive();
		}

		updateProjectsFromEvents()
		{
			this.loading.showForTitle();

			setTimeout(() => {
				const projectIds = this.pull.getProjectsToUpdateFromEvents();
				if (projectIds.length > 15)
				{
					this.runOnAppActiveRepeatedActions();
					return;
				}

				const promises = [
					new Promise((resolve) => {
						this.pull.extendWatch();
						this.pull.startWatch().then(() => {
							this.pull.setCanExecute(true);
							this.pull.freeQueue().then(() => resolve());
						});
					}),
				];
				if (projectIds.length)
				{
					promises.push(
						new Promise((resolve) => {
							this.filter.updateCounters();
							resolve();
						})
					);
					promises.push(this.updateProjects(projectIds));
				}
				Promise.allSettled(promises).then(() => this.loading.hideForTitle());
			}, 1000);
		}

		updateProjects(projectIds)
		{
			return new Promise((resolve, reject) => {
				const params = {
					mode: 'mobile',
					listMode: 'tasks_project',
				};
				if (isSearchByPresetsEnable)
				{
					params.siftThroughFilter = {
						presetId: this.filter.getPreset(),
					};
				}
				(new RequestExecutor('tasks.project.list', {
					select: ProjectList.select,
					filter: {...this.filter.get(), ID: projectIds},
					params,
				}))
					.call()
					.then(
						(response) => {
							this.onUpdateProjectsSuccess(projectIds, response.result.projects);
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

		onUpdateProjectsSuccess(projectIds, projects)
		{
			projectIds.forEach((projectId) => {
				const projectData = projects.find(project => Number(project.id) === Number(projectId));
				if (projectData)
				{
					this.projectList.has(projectId)
						? this.updateItem(projectId, projectData)
						: this.addItem(projectData)
					;
				}
				else if (this.projectList.has(projectId))
				{
					this.removeItem(projectId);
				}
			});
		}

		pseudoReadProjects(projectIds)
		{
			const items = [];
			const projects = {};
			let newCommentsRead = 0;

			this.projectList.forEach((project) => {
				const projectId = String(project.id);
				if (projectIds.includes(projectId))
				{
					newCommentsRead += project.getNewCommentsCount();
					project.pseudoRead();

					const projectItem = this.prepareListItem(project);
					items.push({
						filter: {id: projectId},
						element: projectItem,
					});
					projects[projectId] = {project, projectItem};
				}
			});
			this.list.updateItems(items);
			this.cache.updateProject(projects);
			this.filter.pseudoUpdateCounters(-newCommentsRead);
		}
	}

	return new ProjectList(list, parseInt(BX.componentParameters.get('USER_ID', 0), 10), {
		projectNewsPathTemplate: BX.componentParameters.get('PROJECT_NEWS_PATH_TEMPLATE', ''),
		projectCalendarWebPathTemplate: BX.componentParameters.get('PROJECT_CALENDAR_WEB_PATH_TEMPLATE', ''),
	});
})();
